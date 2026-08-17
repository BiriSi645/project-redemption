(() => {
    'use strict';
    const button = document.querySelector('[data-account-button]');
    const panel = document.getElementById('account-popover');
    if (!button || !panel) return;

    function close() {
        panel.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-label', 'Hesap menüsünü aç');
    }

    button.addEventListener('click', () => {
        const opening = panel.hidden;
        document.querySelectorAll('#message-popover, #notification-popover').forEach((item) => {
            item.hidden = true;
        });
        document
            .querySelectorAll('[data-message-nav], [data-notification-button]')
            .forEach((item) => item.setAttribute('aria-expanded', 'false'));
        if (opening) {
            panel.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            button.setAttribute('aria-label', 'Hesap menüsünü kapat');
        } else close();
    });
    document.addEventListener('click', (event) => {
        if (!panel.hidden && !event.target.closest('.account-popover-wrap')) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            close();
            button.focus();
        }
    });
})();
