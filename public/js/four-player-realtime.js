(() => {
    const root = document.getElementById('four-player-game');
    if (!root) return;

    document.addEventListener('project:realtime-game', (event) => {
        const detail = event.detail || {};
        if (String(detail.roomCode || '').toUpperCase() !== root.dataset.roomCode.toUpperCase()) return;
        document.dispatchEvent(new CustomEvent('project:four-player-refresh'));
    });
})();
