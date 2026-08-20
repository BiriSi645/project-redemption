import { createHmac, randomInt, timingSafeEqual } from 'node:crypto';
import { createServer } from 'node:http';
import mysql from 'mysql2/promise';
import { WebSocket, WebSocketServer } from 'ws';

const AUDIENCE = 'project-redemption-realtime';
const STEP_MS = clampInt(process.env.SNAKE_STEP_MS, 180, 100, 500);
const DEFAULT_GRID = clampInt(process.env.SNAKE_GRID, 30, 20, 60);
const DEFAULT_TARGET = clampInt(process.env.SNAKE_TARGET_LENGTH, 15, 5, 100);
const AUTH_TIMEOUT_MS = 6000;
const LOOP_MS = 12;
const PERSIST_MS = 5000;
const EMPTY_ROOM_TTL_MS = 30000;
const COUNTDOWN_MS = 900;
const MAX_INPUT_QUEUE = 2;

const vectors = {
    up: [0, -1],
    down: [0, 1],
    left: [-1, 0],
    right: [1, 0],
};

const opposite = {
    up: 'down',
    down: 'up',
    left: 'right',
    right: 'left',
};

const allowedOrigins = new Set(
    String(process.env.ALLOWED_ORIGINS || '')
        .split(',')
        .map((value) => value.trim())
        .filter(Boolean),
);

let pool = null;
const sessions = new Map();

function clampInt(value, fallback, min, max) {
    const parsed = Number.parseInt(String(value ?? ''), 10);
    if (!Number.isFinite(parsed)) return fallback;
    return Math.max(min, Math.min(max, parsed));
}

function dbPool() {
    if (pool) return pool;

    for (const key of ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD']) {
        if (!process.env[key]) throw new Error(`${key} environment variable eksik.`);
    }

    const host = String(process.env.DB_HOST);
    const localHost = ['localhost', '127.0.0.1', '::1'].includes(host.toLowerCase());
    const sslEnabled = String(process.env.DB_SSL ?? (localHost ? 'false' : 'true')).toLowerCase() !== 'false';

    pool = mysql.createPool({
        host,
        port: Number(process.env.DB_PORT || 3306),
        database: process.env.DB_DATABASE,
        user: process.env.DB_USERNAME,
        password: process.env.DB_PASSWORD,
        charset: 'utf8mb4',
        waitForConnections: true,
        connectionLimit: 4,
        queueLimit: 0,
        enableKeepAlive: true,
        keepAliveInitialDelay: 0,
        ...(sslEnabled ? { ssl: { rejectUnauthorized: false } } : {}),
    });

    return pool;
}

function originAllowed(request) {
    const origin = String(request.headers.origin || '').trim();
    if (!origin) return false;

    if (allowedOrigins.has(origin)) return true;

    try {
        const parsed = new URL(origin);
        return (
            ['localhost', '127.0.0.1', '::1'].includes(parsed.hostname) &&
            process.env.NODE_ENV !== 'production'
        );
    } catch {
        return false;
    }
}

function verifyToken(token) {
    const secret = String(process.env.REALTIME_SECRET || '');
    if (secret.length < 32 || typeof token !== 'string') return null;

    const [payloadPart, signaturePart, extra] = token.split('.');
    if (!payloadPart || !signaturePart || extra !== undefined) return null;

    let actualSignature;
    try {
        actualSignature = Buffer.from(signaturePart, 'base64url');
    } catch {
        return null;
    }

    const expectedSignature = createHmac('sha256', secret).update(payloadPart).digest();
    if (
        actualSignature.length !== expectedSignature.length ||
        !timingSafeEqual(actualSignature, expectedSignature)
    ) {
        return null;
    }

    let payload;
    try {
        payload = JSON.parse(Buffer.from(payloadPart, 'base64url').toString('utf8'));
    } catch {
        return null;
    }

    const now = Math.floor(Date.now() / 1000);
    if (
        payload?.aud !== AUDIENCE ||
        Number(payload?.sub || 0) < 1 ||
        Number(payload?.exp || 0) < now ||
        Number(payload?.iat || 0) > now + 30
    ) {
        return null;
    }

    return {
        userId: Number(payload.sub),
        username: String(payload.name || 'Kullanıcı'),
    };
}

function send(socket, payload) {
    if (socket.readyState !== WebSocket.OPEN) return;
    try {
        socket.send(JSON.stringify(payload));
    } catch {
        // socket close sırasında oluşabilecek hatayı yok say
    }
}

function broadcast(session, payload) {
    for (const sockets of session.sockets.values()) {
        for (const socket of sockets) send(socket, payload);
    }
}

function connected(session, userId) {
    return (session.sockets.get(userId)?.size || 0) > 0;
}

function bothPlayersConnected(session) {
    return Boolean(
        session.hostId > 0 &&
        session.guestId > 0 &&
        connected(session, session.hostId) &&
        connected(session, session.guestId),
    );
}

function roleFor(session, userId) {
    if (session.hostId === userId) return 'host';
    if (session.guestId === userId) return 'guest';
    return null;
}

function publicState(state) {
    return {
        grid: Number(state.grid || DEFAULT_GRID),
        targetLength: Number(state.targetLength || DEFAULT_TARGET),
        snakes: {
            host: state.snakes.host.map((part) => ({ x: Number(part.x), y: Number(part.y) })),
            guest: state.snakes.guest.map((part) => ({ x: Number(part.x), y: Number(part.y) })),
        },
        directions: {
            host: String(state.directions.host),
            guest: String(state.directions.guest),
        },
        food: { x: Number(state.food.x), y: Number(state.food.y) },
        startedAt: state.startedAt ? Number(state.startedAt) : null,
        completed: Boolean(state.completed),
        completedAt: state.completedAt ? Number(state.completedAt) : null,
        winnerId: state.winnerId ? Number(state.winnerId) : null,
        loserId: state.loserId ? Number(state.loserId) : null,
        reason: state.reason ? String(state.reason) : null,
        round: Math.max(1, Number(state.round || 1)),
        rematchReady: {
            host: Boolean(state.rematchReady?.host),
            guest: Boolean(state.rematchReady?.guest),
        },
    };
}

function roomPayload(session) {
    return {
        code: session.code,
        game: 'snake',
        status: session.state.completed ? 'completed' : session.dbStatus,
        host: { id: session.hostId, username: session.hostUsername },
        guest: session.guestId
            ? { id: session.guestId, username: session.guestUsername || 'Oyuncu 2' }
            : null,
        state: publicState(session.state),
    };
}

function nextDirections(session) {
    return {
        host: session.directionQueues.host[0] || session.state.directions.host,
        guest: session.directionQueues.guest[0] || session.state.directions.guest,
    };
}

function statePacket(session, type = 'state') {
    return {
        type,
        roomCode: session.code,
        phase: session.phase,
        message: phaseMessage(session),
        room: roomPayload(session),
        nextDirections: nextDirections(session),
        tick: session.tick,
        tickAt: session.tickAt,
        nextStepAt: session.nextStepAt,
        stepMs: STEP_MS,
        serverAt: Date.now(),
    };
}

function phaseMessage(session) {
    if (session.state.completed) return '';
    if (!session.guestId) return 'İkinci oyuncunun odaya katılması bekleniyor.';
    if (session.phase === 'countdown') return 'Hazır olun — oyun başlıyor.';
    if (session.phase === 'paused') return 'Bir oyuncunun bağlantısı bekleniyor…';
    if (session.phase === 'playing') return '';
    return 'Yılan sunucusuna bağlanılıyor…';
}

async function fetchRoom(code, userId) {
    const [rows] = await dbPool().execute(
        `SELECT gr.*, host.username AS host_username, guest.username AS guest_username
         FROM game_rooms gr
         JOIN users host ON host.id = gr.host_user_id
         LEFT JOIN users guest ON guest.id = gr.guest_user_id
         WHERE gr.code = ? AND gr.game = 'snake'
         LIMIT 1`,
        [String(code).toUpperCase()],
    );

    const row = rows?.[0];
    if (!row) throw new Error('Oda bulunamadı.');

    const hostId = Number(row.host_user_id);
    const guestId = Number(row.guest_user_id || 0);
    if (userId !== hostId && userId !== guestId) throw new Error('Bu odaya erişemezsiniz.');

    return row;
}

function normalizeState(row) {
    let state;
    try {
        state = JSON.parse(row.state || '{}');
    } catch {
        state = {};
    }

    if (!state?.snakes?.host || !state?.snakes?.guest || !state?.directions || !state?.food) {
        throw new Error('Yılan oda verisi geçersiz. Odayı yeniden oluşturun.');
    }

    state.grid = Number(state.grid || DEFAULT_GRID);
    state.targetLength = Number(state.targetLength || DEFAULT_TARGET);
    state.completed = Boolean(state.completed);
    state.completedAt = state.completedAt ? Number(state.completedAt) : null;
    state.winnerId = state.winnerId ? Number(state.winnerId) : null;
    state.loserId = state.loserId ? Number(state.loserId) : null;
    state.reason = state.reason || null;
    state.round = Math.max(1, Number(state.round || 1));
    state.rematchReady = {
        host: Boolean(state.rematchReady?.host),
        guest: Boolean(state.rematchReady?.guest),
    };

    return state;
}

async function getSession(row) {
    const code = String(row.code).toUpperCase();
    let session = sessions.get(code);

    if (!session) {
        session = {
            id: Number(row.id),
            code,
            hostId: Number(row.host_user_id),
            guestId: Number(row.guest_user_id || 0),
            hostUsername: String(row.host_username || 'Oyuncu 1'),
            guestUsername: String(row.guest_username || ''),
            dbStatus: String(row.status || 'waiting'),
            dbVersion: Number(row.version || 1),
            state: normalizeState(row),
            sockets: new Map(),
            directionQueues: { host: [], guest: [] },
            phase: 'waiting',
            tick: 0,
            tickAt: Date.now(),
            nextStepAt: Date.now() + STEP_MS,
            lastPersistAt: 0,
            emptySince: null,
            persistInFlight: false,
            persistPending: false,
            persistPendingFinal: false,
            finalPersisted: String(row.status || '') === 'completed',
        };
        sessions.set(code, session);
    } else {
        // Oda henüz başlamadıysa DB'deki guest/status değişimini içeri al.
        session.guestId = Number(row.guest_user_id || session.guestId || 0);
        session.guestUsername = String(row.guest_username || session.guestUsername || '');
        session.dbStatus = String(row.status || session.dbStatus);
        session.dbVersion = Math.max(session.dbVersion, Number(row.version || 0));

        if (session.phase === 'waiting' && session.dbStatus === 'playing' && row.state) {
            session.state = normalizeState(row);
        }
    }

    updatePhase(session);
    return session;
}

function addSocket(session, socket, userId) {
    let set = session.sockets.get(userId);
    if (!set) {
        set = new Set();
        session.sockets.set(userId, set);
    }
    set.add(socket);
    session.emptySince = null;
    updatePhase(session);
}

function removeSocket(session, socket, userId) {
    const set = session.sockets.get(userId);
    set?.delete(socket);
    if (set?.size === 0) session.sockets.delete(userId);

    if (session.sockets.size === 0) session.emptySince = Date.now();
    updatePhase(session);
}

function updatePhase(session) {
    if (session.state.completed || session.dbStatus === 'completed') {
        session.phase = 'completed';
        return;
    }

    if (!session.guestId || session.dbStatus === 'waiting') {
        session.phase = 'waiting';
        return;
    }

    if (!bothPlayersConnected(session)) {
        session.phase = 'paused';
        return;
    }

    if (session.phase !== 'playing' && session.phase !== 'countdown') {
        session.phase = 'countdown';
        session.nextStepAt = Date.now() + COUNTDOWN_MS;
        broadcast(session, statePacket(session, 'phase'));
    }
}

function queueDirection(session, player, direction) {
    if (!vectors[direction]) return false;

    const queue = session.directionQueues[player];
    const base = queue.length > 0 ? queue[queue.length - 1] : session.state.directions[player];

    if (direction === base) return true;
    if (opposite[base] === direction) return false;
    if (queue.length >= MAX_INPUT_QUEUE) queue.shift();
    queue.push(direction);
    return true;
}

function applyQueuedDirection(session, player) {
    const queued = session.directionQueues[player].shift();
    if (queued && vectors[queued] && opposite[session.state.directions[player]] !== queued) {
        session.state.directions[player] = queued;
    }
}

function samePoint(a, b) {
    return Number(a.x) === Number(b.x) && Number(a.y) === Number(b.y);
}

function snakeStep(session) {
    const state = session.state;
    const grid = Number(state.grid || DEFAULT_GRID);

    const heads = {};
    const eats = {};

    for (const player of ['host', 'guest']) {
        const [dx, dy] = vectors[state.directions[player]];
        heads[player] = {
            x: Number(state.snakes[player][0].x) + dx,
            y: Number(state.snakes[player][0].y) + dy,
        };
        eats[player] = samePoint(heads[player], state.food);
    }

    const dead = { host: false, guest: false };

    for (const player of ['host', 'guest']) {
        const head = heads[player];
        if (head.x < 0 || head.x >= grid || head.y < 0 || head.y >= grid) {
            dead[player] = true;
        }

        const opponent = player === 'host' ? 'guest' : 'host';
        const ownBody = eats[player]
            ? state.snakes[player]
            : state.snakes[player].slice(0, -1);
        const opponentBody = eats[opponent]
            ? state.snakes[opponent]
            : state.snakes[opponent].slice(0, -1);

        for (const part of [...ownBody, ...opponentBody]) {
            if (samePoint(part, head)) {
                dead[player] = true;
                break;
            }
        }
    }

    const headToHead = samePoint(heads.host, heads.guest);
    const swap =
        samePoint(heads.host, state.snakes.guest[0]) &&
        samePoint(heads.guest, state.snakes.host[0]);
    if (headToHead || swap) {
        dead.host = true;
        dead.guest = true;
    }

    if (dead.host || dead.guest) {
        const winnerId =
            dead.host && !dead.guest
                ? session.guestId
                : dead.guest && !dead.host
                  ? session.hostId
                  : null;
        const loserId =
            winnerId === session.hostId
                ? session.guestId
                : winnerId === session.guestId
                  ? session.hostId
                  : null;
        finish(session, winnerId, loserId, 'collision');
        return;
    }

    for (const player of ['host', 'guest']) {
        state.snakes[player].unshift(heads[player]);
        if (!eats[player]) state.snakes[player].pop();
    }

    if (eats.host || eats.guest) state.food = randomFood(state);

    const hostLength = state.snakes.host.length;
    const guestLength = state.snakes.guest.length;
    const target = Number(state.targetLength || DEFAULT_TARGET);
    if (Math.max(hostLength, guestLength) >= target) {
        const winnerId =
            hostLength === guestLength
                ? null
                : hostLength > guestLength
                  ? session.hostId
                  : session.guestId;
        finish(session, winnerId, null, 'target');
    }

    // Input current in-progress cell motion'ı kesmez. Yön değişikliği
    // bir sonraki 180 ms'lik hücre hareketinde uygulanır.
    if (!state.completed) {
        applyQueuedDirection(session, 'host');
        applyQueuedDirection(session, 'guest');
    }
}

function randomFood(state) {
    const occupied = new Set();
    for (const part of [...state.snakes.host, ...state.snakes.guest]) {
        occupied.add(`${part.x}:${part.y}`);
    }

    const free = [];
    const grid = Number(state.grid || DEFAULT_GRID);
    for (let y = 0; y < grid; y += 1) {
        for (let x = 0; x < grid; x += 1) {
            if (!occupied.has(`${x}:${y}`)) free.push({ x, y });
        }
    }

    if (free.length === 0) return { x: 0, y: 0 };
    return free[randomInt(0, free.length)];
}

function freshSnakeState(session) {
    const grid = Number(session.state.grid || DEFAULT_GRID);
    const targetLength = Number(session.state.targetLength || DEFAULT_TARGET);
    const round = Math.max(1, Number(session.state.round || 1)) + 1;

    const hostHeadX = Math.max(3, Math.floor(grid * 0.24));
    const hostY = Math.max(2, Math.floor(grid * 0.30));
    const guestHeadX = Math.min(grid - 4, Math.floor(grid * 0.74));
    const guestY = Math.min(grid - 3, Math.floor(grid * 0.67));

    return {
        grid,
        targetLength,
        snakes: {
            host: [
                { x: hostHeadX, y: hostY },
                { x: hostHeadX - 1, y: hostY },
                { x: hostHeadX - 2, y: hostY },
            ],
            guest: [
                { x: guestHeadX, y: guestY },
                { x: guestHeadX + 1, y: guestY },
                { x: guestHeadX + 2, y: guestY },
            ],
        },
        directions: { host: 'right', guest: 'left' },
        food: { x: Math.floor(grid / 2), y: Math.floor(grid / 2) },
        startedAt: null,
        completed: false,
        completedAt: null,
        winnerId: null,
        loserId: null,
        reason: null,
        round,
        rematchReady: { host: false, guest: false },
    };
}

function finish(session, winnerId, loserId, reason) {
    session.state.completed = true;
    session.state.completedAt = Math.floor(Date.now() / 1000);
    session.state.winnerId = winnerId || null;
    session.state.loserId = loserId || null;
    session.state.reason = reason;
    session.state.rematchReady = { host: false, guest: false };
    session.dbStatus = 'completed';
    session.phase = 'completed';
    session.finalPersisted = false;
}

async function persistSession(session, final = false) {
    if (session.persistInFlight) {
        session.persistPending = true;
        session.persistPendingFinal = session.persistPendingFinal || final;
        return;
    }
    session.persistInFlight = true;

    try {
        const hostOnline = connected(session, session.hostId) ? 1 : 0;
        const guestOnline = session.guestId && connected(session, session.guestId) ? 1 : 0;
        const status = session.state.completed ? 'completed' : session.dbStatus;

        const [result] = await dbPool().execute(
            `UPDATE game_rooms
             SET state = ?,
                 status = ?,
                 updated_at = NOW(),
                 host_room_seen_at = IF(? = 1, NOW(), host_room_seen_at),
                 guest_room_seen_at = IF(? = 1, NOW(), guest_room_seen_at),
                 version = version + ?
             WHERE id = ? AND game = 'snake'`,
            [
                JSON.stringify(session.state),
                status,
                hostOnline,
                guestOnline,
                final ? 1 : 0,
                session.id,
            ],
        );

        if (Number(result?.affectedRows || 0) === 0) {
            broadcast(session, {
                type: 'room-closed',
                roomCode: session.code,
                message: 'Oda artık mevcut değil.',
            });
            sessions.delete(session.code);
            return;
        }

        session.lastPersistAt = Date.now();
        if (final) session.finalPersisted = true;
    } catch (error) {
        console.error('SNAKE_PERSIST_ERROR', session.code, error);
    } finally {
        session.persistInFlight = false;
        if (session.persistPending) {
            const pendingFinal = session.persistPendingFinal;
            session.persistPending = false;
            session.persistPendingFinal = false;
            void persistSession(session, pendingFinal);
        }
    }
}

async function authenticate(socket, message) {
    const identity = verifyToken(message?.token);
    if (!identity) throw new Error('Geçersiz kimlik doğrulama.');

    const code = String(message?.roomCode || '').trim().toUpperCase();
    if (!/^[A-Z0-9]{6}$/.test(code)) throw new Error('Geçersiz oda kodu.');

    const row = await fetchRoom(code, identity.userId);
    if (String(row.status) === 'completed') {
        // Tamamlanmış oda yine görüntülenebilsin.
    }

    const session = await getSession(row);
    addSocket(session, socket, identity.userId);

    socket.authenticated = true;
    socket.userId = identity.userId;
    socket.roomCode = code;

    send(socket, {
        ...statePacket(session, 'ready'),
        userId: identity.userId,
        role: roleFor(session, identity.userId),
    });

    broadcast(session, statePacket(session, 'phase'));
    void persistSession(session);
}

function handleDirection(socket, message) {
    const session = sessions.get(socket.roomCode);
    if (!session || session.state.completed) return;
    if (!['countdown', 'playing'].includes(session.phase)) return;

    const player = roleFor(session, socket.userId);
    if (!player) return;

    const direction = String(message?.direction || '');
    if (!queueDirection(session, player, direction)) return;

    broadcast(session, {
        type: 'direction',
        roomCode: session.code,
        player,
        direction,
        nextDirections: nextDirections(session),
        inputSeq: Number(message?.seq || 0),
        serverAt: Date.now(),
    });
}

function handleRematch(socket) {
    const session = sessions.get(socket.roomCode);
    if (!session || !session.state.completed) return;

    const player = roleFor(session, socket.userId);
    if (!player) return;

    session.state.rematchReady = {
        host: Boolean(session.state.rematchReady?.host),
        guest: Boolean(session.state.rematchReady?.guest),
    };
    session.state.rematchReady[player] = true;

    if (session.state.rematchReady.host && session.state.rematchReady.guest) {
        session.state = freshSnakeState(session);
        session.dbStatus = 'playing';
        session.directionQueues = { host: [], guest: [] };
        session.tick = 0;
        session.tickAt = Date.now();
        session.nextStepAt = Date.now() + COUNTDOWN_MS;
        session.finalPersisted = false;
        session.phase = bothPlayersConnected(session) ? 'countdown' : 'paused';
        broadcast(session, statePacket(session, 'phase'));
        void persistSession(session, true);
        return;
    }

    broadcast(session, statePacket(session));
    void persistSession(session, true);
}

function cleanupSocket(socket) {
    if (!socket.authenticated || !socket.roomCode) return;
    const session = sessions.get(socket.roomCode);
    if (!session) return;

    removeSocket(session, socket, socket.userId);
    broadcast(session, statePacket(session, 'phase'));
    void persistSession(session);
}

async function gameLoop() {
    const now = Date.now();

    for (const session of [...sessions.values()]) {
        if (session.state.completed) {
            if (!session.finalPersisted) void persistSession(session, true);
        } else if (session.phase === 'countdown' && now >= session.nextStepAt) {
            session.phase = 'playing';
            applyQueuedDirection(session, 'host');
            applyQueuedDirection(session, 'guest');
            session.tickAt = now;
            session.nextStepAt = now + STEP_MS;
            if (!session.state.startedAt) session.state.startedAt = Math.floor(now / 1000);
            broadcast(session, statePacket(session));
        } else if (session.phase === 'playing') {
            if (!bothPlayersConnected(session)) {
                session.phase = 'paused';
                broadcast(session, statePacket(session, 'phase'));
            } else if (now >= session.nextStepAt) {
                // Gecikme olduğunda birkaç adımı birden telafi etmiyoruz.
                // Tek adım ilerletip saati yeniden kuruyoruz: teleport yok.
                snakeStep(session);
                session.tick += 1;
                session.tickAt = now;
                session.nextStepAt = now + STEP_MS;
                broadcast(session, statePacket(session));

                if (session.state.completed) void persistSession(session, true);
            }
        }

        // Tur bitmiş olsa bile oyuncular odada kaldığı sürece DB presence'ını
        // canlı tut. Aksi halde PHP cleanup tamamlanmış odayı terk edilmiş sanabilir.
        if (now - session.lastPersistAt >= PERSIST_MS) {
            void persistSession(session);
        }

        if (
            session.emptySince &&
            now - session.emptySince >= EMPTY_ROOM_TTL_MS &&
            session.sockets.size === 0
        ) {
            await persistSession(session, session.state.completed);
            sessions.delete(session.code);
        }
    }
}

const server = createServer((request, response) => {
    if (request.url === '/health') {
        response.writeHead(200, {
            'Content-Type': 'application/json; charset=utf-8',
            'Cache-Control': 'no-store',
        });
        response.end(
            JSON.stringify({
                ok: true,
                service: 'project-redemption-snake',
                rooms: sessions.size,
                stepMs: STEP_MS,
            }),
        );
        return;
    }

    response.writeHead(426, {
        'Content-Type': 'application/json; charset=utf-8',
        'Cache-Control': 'no-store',
    });
    response.end(JSON.stringify({ success: false, message: 'WebSocket upgrade gerekli.' }));
});

const wss = new WebSocketServer({ server, maxPayload: 8 * 1024 });

wss.on('connection', (socket, request) => {
    if (!originAllowed(request)) {
        socket.close(4003, 'Origin reddedildi');
        return;
    }

    socket.authenticated = false;
    socket.userId = 0;
    socket.roomCode = '';
    socket.isAlive = true;

    const authTimer = setTimeout(() => {
        if (!socket.authenticated) socket.close(4001, 'Kimlik doğrulama gerekli');
    }, AUTH_TIMEOUT_MS);

    socket.on('pong', () => {
        socket.isAlive = true;
    });

    socket.on('message', async (raw) => {
        let message;
        try {
            message = JSON.parse(raw.toString('utf8'));
        } catch {
            return;
        }

        if (!socket.authenticated) {
            if (message?.type !== 'auth') {
                socket.close(4001, 'Önce kimlik doğrulama gerekli');
                return;
            }

            try {
                await authenticate(socket, message);
                clearTimeout(authTimer);
            } catch (error) {
                console.error('SNAKE_AUTH_ERROR', error);
                send(socket, { type: 'error', message: error.message || 'Bağlantı reddedildi.' });
                socket.close(4001, 'Kimlik doğrulama başarısız');
            }
            return;
        }

        if (message?.type === 'direction') {
            handleDirection(socket, message);
            return;
        }

        if (message?.type === 'rematch') {
            handleRematch(socket);
            return;
        }

        if (message?.type === 'sync') {
            const session = sessions.get(socket.roomCode);
            if (session) send(socket, statePacket(session));
            return;
        }

        if (message?.type === 'ping') {
            send(socket, { type: 'pong', sentAt: Number(message.sentAt || 0), serverAt: Date.now() });
        }
    });

    socket.on('close', () => {
        clearTimeout(authTimer);
        cleanupSocket(socket);
    });

    socket.on('error', () => {
        // close handler temizleyecek
    });
});

const heartbeatTimer = setInterval(() => {
    for (const socket of wss.clients) {
        if (!socket.isAlive) {
            socket.terminate();
            continue;
        }
        socket.isAlive = false;
        socket.ping();
    }
}, 20000);
heartbeatTimer.unref();

const loopTimer = setInterval(() => {
    void gameLoop();
}, LOOP_MS);
loopTimer.unref();

const port = Number(process.env.PORT || 10000);
server.listen(port, '0.0.0.0', () => {
    console.log(`Project Redemption Snake server listening on 0.0.0.0:${port}`);
    console.log(`Snake step: ${STEP_MS}ms`);
});

async function shutdown(signal) {
    console.log(`${signal} received; snake sessions persist ediliyor...`);
    clearInterval(heartbeatTimer);
    clearInterval(loopTimer);

    await Promise.allSettled([...sessions.values()].map((session) => persistSession(session, session.state.completed)));

    for (const socket of wss.clients) {
        send(socket, { type: 'server-restarting', message: 'Yılan sunucusu yeniden başlatılıyor.' });
        socket.close(1012, 'Service restart');
    }

    server.close(async () => {
        try {
            await pool?.end();
        } catch {
            // ignore
        }
        process.exit(0);
    });

    setTimeout(() => process.exit(0), 8000).unref();
}

process.on('SIGTERM', () => void shutdown('SIGTERM'));
process.on('SIGINT', () => void shutdown('SIGINT'));
