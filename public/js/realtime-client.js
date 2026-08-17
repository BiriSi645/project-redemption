(() => {
    'use strict';
    const root = document.querySelector('[data-realtime-client]');
    if (!root || !('WebSocket' in window)) return;
    let socket = null,
        reconnectTimer = null,
        stopped = false,
        attempts = 0;
    function websocketUrl() {
        if (root.dataset.websocketUrl) {
            return root.dataset.websocketUrl;
        }

        const isLocal =
            location.hostname === 'localhost' ||
            location.hostname === '127.0.0.1' ||
            location.hostname === '::1';

        if (isLocal) {
            return 'ws://127.0.0.1:8081';
        }

        return `${location.protocol === 'https:' ? 'wss' : 'ws'}://${location.host}/api/ws`;
    }

    async function authenticate(activeSocket) {
        const tokenUrl = root.dataset.tokenUrl;
        if (!tokenUrl) throw new Error('Realtime token adresi bulunamadı.');

        const response = await fetch(tokenUrl, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });
        const payload = await response.json().catch(() => null);

        if (!response.ok || !payload?.success || typeof payload.token !== 'string') {
            throw new Error(payload?.message || 'Realtime token alınamadı.');
        }

        if (activeSocket !== socket || activeSocket.readyState !== WebSocket.OPEN) return;
        activeSocket.send(JSON.stringify({ type: 'auth', token: payload.token }));
    }

    function connect() {
        if (
            stopped ||
            document.hidden ||
            (socket && [WebSocket.CONNECTING, WebSocket.OPEN].includes(socket.readyState))
        )
            return;
        try {
            socket = new WebSocket(websocketUrl());
        } catch (error) {
            scheduleReconnect();
            return;
        }
        const activeSocket = socket;
        activeSocket.addEventListener('open', () => {
            authenticate(activeSocket).catch(() => activeSocket.close(4001, 'Kimlik doğrulama başarısız'));
        });
        activeSocket.addEventListener('message', (event) => {
            let data;
            try {
                data = JSON.parse(event.data);
            } catch (error) {
                return;
            }
            if (data.type === 'ready') {
                attempts = 0;
                document.dispatchEvent(new CustomEvent('project:realtime-connected'));
            } else if (data.type === 'direct-message') {
                document.dispatchEvent(
                    new CustomEvent('project:realtime-message', { detail: data }),
                );
                document.dispatchEvent(new CustomEvent('project:messages-updated'));
            } else if (data.type === 'notification') {
                document.dispatchEvent(
                    new CustomEvent('project:realtime-notification', { detail: data }),
                );
                document.dispatchEvent(new CustomEvent('project:notifications-updated'));
            } else if (data.type === 'game-room') {
                document.dispatchEvent(new CustomEvent('project:realtime-game', { detail: data }));
            } else if (data.type === 'presence') {
                document.dispatchEvent(
                    new CustomEvent('project:presence-updated', { detail: data }),
                );
            }
        });
        activeSocket.addEventListener('close', () => {
            if (socket === activeSocket) socket = null;
            document.dispatchEvent(new CustomEvent('project:realtime-disconnected'));
            scheduleReconnect();
        });
        activeSocket.addEventListener('error', () => activeSocket.close());
    }
    function scheduleReconnect() {
        if (stopped || document.hidden) return;
        clearTimeout(reconnectTimer);
        reconnectTimer = setTimeout(connect, Math.min(1000 * 2 ** attempts++, 15000));
    }
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) connect();
    });
    window.addEventListener(
        'pagehide',
        () => {
            stopped = true;
            clearTimeout(reconnectTimer);
            socket?.close(1000, 'Sayfa kapandı');
        },
        { once: true },
    );
    connect();
})();
