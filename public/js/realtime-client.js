(() => {
    'use strict';
    const root = document.querySelector('[data-realtime-client]');
    if (!root || !('WebSocket' in window)) return;
    let socket = null,
        reconnectTimer = null,
        stopped = false,
        attempts = 0;
    const websocketUrl = () =>
        root.dataset.websocketUrl ||
        `${location.protocol === 'https:' ? 'wss' : 'ws'}://${location.hostname}:8081`;
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
        socket.addEventListener('message', (event) => {
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
        socket.addEventListener('close', () => {
            socket = null;
            document.dispatchEvent(new CustomEvent('project:realtime-disconnected'));
            scheduleReconnect();
        });
        socket.addEventListener('error', () => socket?.close());
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
