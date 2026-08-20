(() => {
    'use strict';

    const vectors = {
        up: [0, -1],
        down: [0, 1],
        left: [-1, 0],
        right: [1, 0],
    };

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function mount({ root, board, statusEl, room: initialRoom }) {
        let room = initialRoom;
        let socket = null;
        let stopped = false;
        let ready = false;
        let phase = room.status === 'waiting' ? 'waiting' : 'connecting';
        let phaseMessage = '';
        let reconnectTimer = null;
        let reconnectAttempts = 0;
        let inputSeq = 0;
        let animationFrame = null;
        let serverClockOffset = 0;
        let clockInitialized = false;
        let tickAt = Date.now();
        let nextStepAt = tickAt + 180;
        let stepMs = 180;
        let liveState = room.state;
        let role = null;
        let pingTimer = null;
        let lastPongAt = Date.now();

        const controls = document.getElementById('room-controls');
        const guestName = document.getElementById('guest-name');
        const guestLegend = document.getElementById('guest-legend');

        function wsUrl() {
            const configured = String(root.dataset.snakeWsUrl || '').trim();
            if (!configured) return '';
            return configured.replace(/\/$/, '');
        }

        function updateClock(serverAt) {
            const observed = Number(serverAt) - Date.now();
            if (!Number.isFinite(observed)) return;

            if (!clockInitialized) {
                serverClockOffset = observed;
                clockInitialized = true;
            } else {
                // Jitter'ı azaltmak için hafif EWMA.
                serverClockOffset = serverClockOffset * 0.85 + observed * 0.15;
            }
        }

        function serverNow() {
            return Date.now() + serverClockOffset;
        }

        function updateNames() {
            if (guestName) guestName.textContent = room.guest?.username || 'Bekleniyor…';
            if (guestLegend) guestLegend.textContent = room.guest?.username || 'Oyuncu 2';
        }

        function updateStatus() {
            updateNames();
            statusEl.className = `room-status ${room.status}`;

            if (!root.dataset.snakeWsUrl) {
                statusEl.textContent = 'Yılan WebSocket adresi ayarlanmamış.';
                return;
            }

            if (!ready && room.status !== 'completed') {
                statusEl.textContent = 'Yılan oyun sunucusuna bağlanılıyor…';
                return;
            }

            if (room.status === 'completed' || liveState?.completed) {
                statusEl.className = 'room-status completed';
                const winner = Number(liveState?.winnerId || 0);
                if (!winner) {
                    statusEl.textContent = 'Oyun berabere tamamlandı.';
                } else {
                    const result =
                        winner === Number(room.currentUserId) ? 'Kazandınız!' : 'Rakibiniz kazandı.';
                    const reason =
                        liveState?.reason === 'collision'
                            ? ' Bir oyuncu çarpıştı.'
                            : ' Hedef uzunluğa ulaşıldı.';
                    statusEl.textContent = result + reason;
                }
                return;
            }

            if (phaseMessage) {
                statusEl.textContent = phaseMessage;
                return;
            }

            if (phase === 'waiting') {
                statusEl.textContent = `Arkadaşınızın katılması bekleniyor. Oda kodu: ${room.code}`;
                return;
            }

            if (phase === 'paused') {
                statusEl.textContent = 'Bir oyuncunun bağlantısı bekleniyor…';
                return;
            }

            if (phase === 'countdown') {
                statusEl.textContent = 'Hazır olun — oyun başlıyor.';
                return;
            }

            const hostLength = Number(liveState?.snakes?.host?.length || 0);
            const guestLength = Number(liveState?.snakes?.guest?.length || 0);
            statusEl.textContent =
                `${room.host.username}: ${hostLength} · ` +
                `${room.guest?.username || 'Oyuncu 2'}: ${guestLength} · ` +
                `Hedef: ${Number(liveState?.targetLength || 15)}`;
        }

        function setControlsEnabled() {
            if (!controls) return;
            const enabled = ready && phase === 'playing' && room.status !== 'completed';
            controls.style.pointerEvents = enabled ? 'auto' : 'none';
            controls.style.opacity = enabled ? '1' : '.5';
        }

        function canvas() {
            let element = board.querySelector('canvas');
            if (!element) {
                board.innerHTML = '';
                element = document.createElement('canvas');
                element.className = 'shared-snake-canvas';
                element.width = 720;
                element.height = 720;
                element.setAttribute('aria-label', 'İki oyunculu Yılan oyun alanı');
                board.appendChild(element);
            }
            return element;
        }

        function predictedSnake(snake, direction) {
            const [dx, dy] = vectors[direction] || [0, 0];
            return snake.map((part, index) => {
                if (index === 0) {
                    return { x: Number(part.x) + dx, y: Number(part.y) + dy };
                }
                return {
                    x: Number(snake[index - 1].x),
                    y: Number(snake[index - 1].y),
                };
            });
        }

        function interpolateSnake(from, to, progress) {
            return from.map((part, index) => {
                const target = to[index] || part;
                return {
                    x: Number(part.x) + (Number(target.x) - Number(part.x)) * progress,
                    y: Number(part.y) + (Number(target.y) - Number(part.y)) * progress,
                };
            });
        }

        function visualState() {
            if (!liveState?.snakes?.host || !liveState?.snakes?.guest) return null;

            // Offline Yılan gibi hücre-hücre hareket: serverdan gelen gerçek grid
            // konumlarını doğrudan çiziyoruz. WebSocket düşük gecikmeli kalır,
            // fakat iki hücre arasında görsel interpolation/kayma yapılmaz.
            return {
                grid: Number(liveState.grid || 30),
                food: liveState.food,
                snakes: liveState.snakes,
            };
        }

        function draw() {
            animationFrame = requestAnimationFrame(draw);
            const visual = visualState();
            if (!visual) return;

            const surface = canvas();
            const ctx = surface.getContext('2d', { alpha: false });
            const grid = visual.grid;
            const cell = surface.width / grid;

            ctx.fillStyle = '#07140d';
            ctx.fillRect(0, 0, surface.width, surface.height);

            ctx.strokeStyle = 'rgba(255,255,255,.035)';
            ctx.lineWidth = 1;
            for (let i = 1; i < grid; i += 1) {
                const pos = i * cell;
                ctx.beginPath();
                ctx.moveTo(pos, 0);
                ctx.lineTo(pos, surface.height);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo(0, pos);
                ctx.lineTo(surface.width, pos);
                ctx.stroke();
            }

            if (visual.food) {
                ctx.fillStyle = '#ef4444';
                ctx.beginPath();
                ctx.arc(
                    (Number(visual.food.x) + 0.5) * cell,
                    (Number(visual.food.y) + 0.5) * cell,
                    cell * 0.34,
                    0,
                    Math.PI * 2,
                );
                ctx.fill();
            }

            [
                ['host', '#22c55e', '#86efac'],
                ['guest', '#a855f7', '#e9d5ff'],
            ].forEach(([player, bodyColor, headColor]) => {
                const snake = visual.snakes[player] || [];
                snake.forEach((part, index) => {
                    const inset = Math.max(1, Math.round(cell * 0.08));
                    ctx.fillStyle = index === 0 ? headColor : bodyColor;
                    ctx.fillRect(
                        Number(part.x) * cell + inset,
                        Number(part.y) * cell + inset,
                        cell - inset * 2,
                        cell - inset * 2,
                    );
                });
            });
        }

        function applyPacket(packet) {
            updateClock(packet.serverAt);
            phase = String(packet.phase || phase);
            phaseMessage = String(packet.message || '');
            stepMs = Number(packet.stepMs || stepMs || 180);
            tickAt = Number(packet.tickAt || tickAt || Date.now());
            nextStepAt = Number(packet.nextStepAt || tickAt + stepMs);

            if (packet.room) {
                room = {
                    ...room,
                    ...packet.room,
                    currentUserId: room.currentUserId,
                };
                if (packet.room.state) liveState = packet.room.state;
            }

            if (packet.type === 'ready') {
                ready = true;
                reconnectAttempts = 0;
                role = packet.role || role;
            }

            if (packet.type === 'room-closed') {
                ready = false;
                phaseMessage = packet.message || 'Oda artık mevcut değil.';
            }

            updateStatus();
            setControlsEnabled();
        }

        async function realtimeToken() {
            const url = String(root.dataset.realtimeTokenUrl || '').trim();
            if (!url) throw new Error('Realtime token adresi bulunamadı.');

            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload?.success || typeof payload.token !== 'string') {
                throw new Error(payload?.message || 'Yılan bağlantı tokenı alınamadı.');
            }
            return payload.token;
        }

        function send(payload) {
            if (!socket || socket.readyState !== WebSocket.OPEN || !ready) return false;
            socket.send(JSON.stringify(payload));
            return true;
        }

        function direction(direction) {
            if (!vectors[direction] || phase !== 'playing' || !ready) return;
            inputSeq += 1;
            send({ type: 'direction', direction, seq: inputSeq });
        }

        function startPing() {
            clearInterval(pingTimer);
            pingTimer = setInterval(() => {
                if (!socket || socket.readyState !== WebSocket.OPEN || !ready) return;
                socket.send(JSON.stringify({ type: 'ping', sentAt: Date.now() }));

                if (Date.now() - lastPongAt > 45000) {
                    socket.close(4000, 'Heartbeat timeout');
                }
            }, 15000);
        }

        function scheduleReconnect() {
            if (stopped) return;
            clearTimeout(reconnectTimer);
            const delay = Math.min(700 * 2 ** reconnectAttempts++, 8000);
            reconnectTimer = setTimeout(connect, delay);
        }

        function connect() {
            if (stopped) return;
            const url = wsUrl();
            if (!url) {
                ready = false;
                phaseMessage = 'SNAKE_WEBSOCKET_URL ayarlanmamış.';
                updateStatus();
                setControlsEnabled();
                return;
            }

            if (socket && [WebSocket.CONNECTING, WebSocket.OPEN].includes(socket.readyState)) return;

            ready = false;
            phaseMessage = 'Yılan oyun sunucusuna bağlanılıyor…';
            updateStatus();
            setControlsEnabled();

            try {
                socket = new WebSocket(url);
            } catch {
                scheduleReconnect();
                return;
            }

            const active = socket;

            active.addEventListener('open', async () => {
                try {
                    const token = await realtimeToken();
                    if (active !== socket || active.readyState !== WebSocket.OPEN) return;
                    active.send(
                        JSON.stringify({
                            type: 'auth',
                            token,
                            roomCode: room.code,
                        }),
                    );
                } catch (error) {
                    phaseMessage = error.message || 'Yılan kimlik doğrulaması başarısız.';
                    updateStatus();
                    active.close(4001, 'Auth failed');
                }
            });

            active.addEventListener('message', (event) => {
                let packet;
                try {
                    packet = JSON.parse(event.data);
                } catch {
                    return;
                }

                if (packet.type === 'pong') {
                    lastPongAt = Date.now();
                    updateClock(packet.serverAt);
                    return;
                }

                if (packet.type === 'error') {
                    phaseMessage = packet.message || 'Yılan sunucusu hatası.';
                    updateStatus();
                    return;
                }

                if (['ready', 'state', 'phase', 'room-closed'].includes(packet.type)) {
                    applyPacket(packet);
                }
            });

            active.addEventListener('close', () => {
                if (active !== socket) return;
                socket = null;
                ready = false;
                phase = room.status === 'completed' ? 'completed' : 'paused';
                phaseMessage =
                    room.status === 'completed'
                        ? ''
                        : 'Yılan sunucusu bağlantısı koptu; yeniden bağlanılıyor…';
                clearInterval(pingTimer);
                updateStatus();
                setControlsEnabled();
                if (room.status !== 'completed') scheduleReconnect();
            });

            active.addEventListener('error', () => {
                active.close();
            });

            lastPongAt = Date.now();
            startPing();
        }

        controls?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-direction]');
            if (!button) return;
            direction(button.dataset.direction);
        });

        document.addEventListener('keydown', (event) => {
            const mapping = {
                ArrowUp: 'up',
                w: 'up',
                W: 'up',
                ArrowDown: 'down',
                s: 'down',
                S: 'down',
                ArrowLeft: 'left',
                a: 'left',
                A: 'left',
                ArrowRight: 'right',
                d: 'right',
                D: 'right',
            };
            const value = mapping[event.key];
            if (!value) return;
            event.preventDefault();
            direction(value);
        });

        document.getElementById('leave-room-form')?.addEventListener('submit', () => {
            stopped = true;
            clearTimeout(reconnectTimer);
            clearInterval(pingTimer);
            socket?.close(1000, 'Oda terk edildi');
        });

        window.addEventListener(
            'pagehide',
            () => {
                stopped = true;
                clearTimeout(reconnectTimer);
                clearInterval(pingTimer);
                socket?.close(1000, 'Sayfa kapandı');
            },
            { once: true },
        );

        // Browser yeniden görünür olduğunda bağlantı koptuysa hızla toparla.
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !stopped && (!socket || socket.readyState === WebSocket.CLOSED)) {
                connect();
            }
        });

        updateStatus();
        setControlsEnabled();
        animationFrame = requestAnimationFrame(draw);
        connect();

        return () => {
            stopped = true;
            clearTimeout(reconnectTimer);
            clearInterval(pingTimer);
            if (animationFrame) cancelAnimationFrame(animationFrame);
            socket?.close(1000, 'Unmount');
        };
    }

    window.ProjectSnakeRealtime = { mount };
})();
