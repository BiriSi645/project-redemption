import { createHmac, timingSafeEqual } from 'node:crypto';
import { createServer } from 'node:http';
import mysql from 'mysql2/promise';
import { WebSocket, WebSocketServer } from 'ws';

const AUDIENCE = 'project-redemption-realtime';
const POLL_INTERVAL_MS = 600;
const PRESENCE_TOUCH_MS = 30000;
const EVENT_RETENTION_MINUTES = 10;

const server = createServer((request, response) => {
    response.writeHead(426, {
        'Content-Type': 'application/json; charset=utf-8',
        'Cache-Control': 'no-store',
    });
    response.end(JSON.stringify({ success: false, message: 'WebSocket upgrade gerekli.' }));
});

const wss = new WebSocketServer({
    server,
    maxPayload: 8 * 1024,
});

const clientsByUser = new Map();
let authenticatedClientCount = 0;
let pool = null;
let lastEventId = 0;
let cursorInitialized = false;
let scanInFlight = false;
let lastPresenceTouchAt = 0;
let lastCleanupAt = 0;

function databaseSslOptions() {
    const encodedCa = String(
        process.env.DB_SSL_CA_BASE64 || ''
    ).trim();

    const configuredCa = encodedCa
        ? Buffer.from(encodedCa, 'base64').toString('utf8')
        : String(process.env.DB_SSL_CA || '')
            .replace(/\\n/g, '\n')
            .trim();

    if (configuredCa) {
        return {
            ca: configuredCa,
            rejectUnauthorized: true,
            minVersion: 'TLSv1.2',
        };
    }

    return {
        rejectUnauthorized: false,
        minVersion: 'TLSv1.2',
    };
}

function getPool() {
    if (pool) return pool;

    const required = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
    for (const key of required) {
        if (!process.env[key]) {
            throw new Error(`${key} environment variable eksik.`);
        }
    }

    pool = mysql.createPool({
        host: process.env.DB_HOST,
        port: Number(process.env.DB_PORT || 3306),
        database: process.env.DB_DATABASE,
        user: process.env.DB_USERNAME,
        password: process.env.DB_PASSWORD,
        charset: 'utf8mb4',
        waitForConnections: true,
        connectionLimit: 3,
        queueLimit: 0,
        enableKeepAlive: true,
        keepAliveInitialDelay: 0,
        ssl: databaseSslOptions(),
    });

    return pool;
}

function sameOrigin(request) {
    const origin = String(request.headers.origin || '');
    const forwardedHost = String(request.headers['x-forwarded-host'] || '')
        .split(',')[0]
        .trim();
    const requestHost = forwardedHost || String(request.headers.host || '').trim();

    if (!origin || !requestHost) return false;

    try {
        return new URL(origin).host.toLowerCase() === requestHost.toLowerCase();
    } catch {
        return false;
    }
}

function verifyToken(token) {
    const secret = process.env.REALTIME_SECRET || '';
    if (secret.length < 32 || typeof token !== 'string') return null;

    const parts = token.split('.');
    if (parts.length !== 2 || !parts[0] || !parts[1]) return null;

    let actualSignature;
    try {
        actualSignature = Buffer.from(parts[1], 'base64url');
    } catch {
        return null;
    }

    const expectedSignature = createHmac('sha256', secret)
        .update(parts[0])
        .digest();

    if (
        actualSignature.length !== expectedSignature.length ||
        !timingSafeEqual(actualSignature, expectedSignature)
    ) {
        return null;
    }

    let payload;
    try {
        payload = JSON.parse(Buffer.from(parts[0], 'base64url').toString('utf8'));
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

function sendJson(socket, payload) {
    if (socket.readyState !== WebSocket.OPEN) return;

    try {
        socket.send(JSON.stringify(payload));
    } catch {
        // Bağlantı kapanırken oluşan gönderim hatasını yok say.
    }
}

function register(socket, userId) {
    let sockets = clientsByUser.get(userId);
    if (!sockets) {
        sockets = new Set();
        clientsByUser.set(userId, sockets);
    }

    sockets.add(socket);
    socket.userId = userId;
    socket.authenticated = true;
    authenticatedClientCount += 1;
}

function unregister(socket) {
    if (!socket.authenticated || !socket.userId) return;

    const sockets = clientsByUser.get(socket.userId);
    sockets?.delete(socket);
    if (sockets?.size === 0) clientsByUser.delete(socket.userId);

    socket.authenticated = false;
    authenticatedClientCount = Math.max(0, authenticatedClientCount - 1);
}

function broadcast(payload) {
    for (const socket of wss.clients) {
        if (socket.authenticated) sendJson(socket, payload);
    }
}

function sendToUser(userId, payload) {
    for (const socket of clientsByUser.get(userId) || []) {
        sendJson(socket, payload);
    }
}

async function initializeCursor() {
    if (cursorInitialized) return;

    const database = getPool();
    const [rows] = await database.query(
        'SELECT COALESCE(MAX(id), 0) AS last_id FROM realtime_events',
    );
    lastEventId = Number(rows?.[0]?.last_id || 0);
    cursorInitialized = true;
}

async function publishPresenceEvent() {
    const database = getPool();
    await database.execute(
        "INSERT INTO realtime_events (recipient_user_id, event_type, payload, created_at) VALUES (NULL, 'presence', '{}', NOW())",
    );
}

async function authenticateSocket(socket, token) {
    const identity = verifyToken(token);
    if (!identity) return false;

    const database = getPool();
    const [rows] = await database.execute(
        'SELECT id FROM users WHERE id = ? AND is_active = 1 LIMIT 1',
        [identity.userId],
    );

    if (!Array.isArray(rows) || rows.length !== 1) return false;

    await initializeCursor();
    await database.execute(
        'UPDATE users SET last_seen_at = NOW() WHERE id = ?',
        [identity.userId],
    );

    register(socket, identity.userId);
    await publishPresenceEvent();

    sendJson(socket, {
        type: 'ready',
        userId: identity.userId,
        serverAt: Date.now(),
    });

    return true;
}

async function touchPresence() {
    if (clientsByUser.size === 0) return;

    const ids = [...clientsByUser.keys()].filter((id) => Number.isInteger(id) && id > 0);
    if (ids.length === 0) return;

    const placeholders = ids.map(() => '?').join(',');
    await getPool().execute(
        `UPDATE users SET last_seen_at = NOW() WHERE id IN (${placeholders})`,
        ids,
    );
}

async function cleanupEvents() {
    await getPool().execute(
        `DELETE FROM realtime_events
         WHERE created_at < (NOW() - INTERVAL ${EVENT_RETENTION_MINUTES} MINUTE)`,
    );
}

async function scanEvents() {
    if (scanInFlight || authenticatedClientCount === 0 || !cursorInitialized) return;
    scanInFlight = true;

    try {
        const database = getPool();
        const [rows] = await database.execute(
            `SELECT id, recipient_user_id, event_type, payload
             FROM realtime_events
             WHERE id > ?
             ORDER BY id ASC
             LIMIT 250`,
            [lastEventId],
        );

        for (const row of rows) {
            lastEventId = Math.max(lastEventId, Number(row.id || 0));

            let payload = {};
            if (row.payload) {
                try {
                    payload = JSON.parse(row.payload);
                } catch {
                    payload = {};
                }
            }

            const event = {
                ...payload,
                type: String(row.event_type || ''),
            };

            const recipient = Number(row.recipient_user_id || 0);
            if (recipient > 0) {
                sendToUser(recipient, event);
            } else {
                broadcast(event);
            }
        }

        const now = Date.now();
        if (now - lastPresenceTouchAt >= PRESENCE_TOUCH_MS) {
            lastPresenceTouchAt = now;
            await touchPresence();
        }

        if (now - lastCleanupAt >= 60000) {
            lastCleanupAt = now;
            await cleanupEvents();
        }
    } catch (error) {
        console.error('REALTIME_SCAN_ERROR', error);
        try {
            await pool?.end();
        } catch {
            // Eski pool zaten kapanmış olabilir.
        }
        pool = null;
    } finally {
        scanInFlight = false;
    }
}

wss.on('connection', (socket, request) => {
    if (!sameOrigin(request)) {
        socket.close(4003, 'Origin reddedildi');
        return;
    }

    socket.authenticated = false;
    socket.userId = 0;

    const authTimer = setTimeout(() => {
        if (!socket.authenticated) socket.close(4001, 'Kimlik doğrulama gerekli');
    }, 5000);

    socket.on('message', async (raw) => {
        let message;
        try {
            message = JSON.parse(raw.toString('utf8'));
        } catch {
            return;
        }

        if (!socket.authenticated) {
            if (message?.type !== 'auth' || typeof message?.token !== 'string') {
                socket.close(4001, 'Geçersiz kimlik doğrulama');
                return;
            }

            try {
                const accepted = await authenticateSocket(socket, message.token);
                if (!accepted) {
                    socket.close(4001, 'Geçersiz kimlik doğrulama');
                    return;
                }
                clearTimeout(authTimer);
            } catch (error) {
                console.error('REALTIME_AUTH_ERROR', error);
                socket.close(1011, 'Realtime servisi hazır değil');
            }
            return;
        }

        if (message?.type === 'ping') {
            sendJson(socket, {
                type: 'pong',
                sentAt: Number(message.sentAt || 0),
                serverAt: Date.now(),
            });
        }
    });

    socket.on('close', () => {
        clearTimeout(authTimer);
        unregister(socket);
    });

    socket.on('error', () => {
        // close olayı bağlantı kaydını temizleyecek.
    });
});

setInterval(() => {
    void scanEvents();
}, POLL_INTERVAL_MS).unref();

export default server;
