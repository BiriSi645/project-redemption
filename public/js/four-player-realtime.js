(() => {
    const root = document.getElementById('four-player-game');
    if (!root) return;

    let localActionUntil = 0;
    const markLocalAction = () => {
        localActionUntil = Date.now() + 1800;
    };

    root.addEventListener('click', (event) => {
        if (event.target.closest('[data-okey-action], [data-monopoly-action], #auction-bid, #auction-fold, #four-rematch')) {
            markLocalAction();
        }
    }, true);
    root.addEventListener('drop', markLocalAction, true);

    document.addEventListener('project:realtime-game', (event) => {
        const detail = event.detail || {};
        if (String(detail.roomCode || '').toUpperCase() !== root.dataset.roomCode.toUpperCase()) return;
        if (Date.now() < localActionUntil) return;
        window.location.reload();
    });
})();
