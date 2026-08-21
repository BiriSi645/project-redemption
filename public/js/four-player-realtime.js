(() => {
    'use strict';

    const root = document.getElementById('four-player-game');
    if (!root) return;

    const fallbackDelay = 8000;
    let realtimeConnected = false;
    let fallbackTimer = null;
    let requestInFlight = null;

    function publishRoom(room) {
        document.dispatchEvent(new CustomEvent('project:four-player-state', {
            detail: { room },
        }));
    }

    async function loadState() {
        if (requestInFlight) return requestInFlight;
        requestInFlight = (async () => {
            try {
                const response = await fetch(root.dataset.stateUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                });
                const payload = await response.json();
                if (response.ok && payload.success && payload.room) publishRoom(payload.room);
            } catch {
                // Sonraki fallback turu tekrar deneyecek.
            } finally {
                requestInFlight = null;
            }
        })();
        return requestInFlight;
    }

    function stopFallbackPolling() {
        if (fallbackTimer === null) return;
        window.clearInterval(fallbackTimer);
        fallbackTimer = null;
    }

    function startFallbackPolling() {
        if (realtimeConnected || fallbackTimer !== null) return;
        fallbackTimer = window.setInterval(loadState, fallbackDelay);
    }

    document.addEventListener('project:realtime-connected', () => {
        realtimeConnected = true;
        stopFallbackPolling();
        loadState();
    });

    document.addEventListener('project:realtime-disconnected', () => {
        realtimeConnected = false;
        loadState();
        startFallbackPolling();
    });

    document.addEventListener('project:realtime-game', (event) => {
        const detail = event.detail || {};
        if (String(detail.roomCode || '').toUpperCase() !== root.dataset.roomCode.toUpperCase()) return;
        loadState();
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) return;
        loadState();
        if (!realtimeConnected) startFallbackPolling();
    });

    loadState();
    startFallbackPolling();
})();
