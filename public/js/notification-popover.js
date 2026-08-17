(() => {
    'use strict';
    const button = document.querySelector('[data-notification-button]');
    const panel = document.getElementById('notification-popover');
    const list = panel?.querySelector('[data-notification-preview-list]');
    if (!button || !panel || !list) return;
    let loadedAt = 0;
    let loading = false;

    function updateBadges(total) {
        document.querySelectorAll('[data-notification-nav]').forEach((target) => {
            let badge = target.querySelector('.notification-badge');
            if (total > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'notification-badge';
                    target.appendChild(badge);
                }
                badge.textContent = total > 99 ? '99+' : String(total);
            } else badge?.remove();
        });
    }

    function render(items) {
        list.innerHTML = '';
        if (!items.length) {
            const empty = document.createElement('div');
            empty.className = 'message-popover-state';
            empty.textContent = 'Henüz bildiriminiz yok.';
            list.appendChild(empty);
            return;
        }
        const fragment = document.createDocumentFragment();
        items.forEach((notification) => {
            const item = document.createElement('a');
            item.className = `message-preview-item${notification.unread ? ' unread' : ''}`;
            item.href = notification.url;
            const icon = document.createElement('span');
            icon.className = 'message-preview-avatar notification-preview-avatar';
            icon.textContent = notification.icon;
            const copy = document.createElement('span');
            copy.className = 'message-preview-copy notification-preview-copy';
            const message = document.createElement('span');
            message.textContent = notification.message;
            copy.appendChild(message);
            const meta = document.createElement('span');
            meta.className = 'message-preview-meta';
            const time = document.createElement('time');
            time.textContent = notification.time;
            meta.appendChild(time);
            item.append(icon, copy, meta);
            fragment.appendChild(item);
        });
        list.appendChild(fragment);
    }

    async function load() {
        if (loading || Date.now() - loadedAt < 10000) return;
        loading = true;
        if (!loadedAt)
            list.innerHTML = '<div class="message-popover-state">Bildirimler yükleniyor…</div>';
        try {
            const response = await fetch(button.dataset.previewUrl, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error();
            render(data.notifications || []);
            updateBadges(Number(data.unreadCount) || 0);
            loadedAt = Date.now();
        } catch (error) {
            list.innerHTML =
                '<div class="message-popover-state">Bildirimler şu anda yüklenemedi.</div>';
        } finally {
            loading = false;
        }
    }

    function close() {
        panel.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-label', 'Bildirimleri aç');
    }
    button.addEventListener('click', () => {
        const opening = panel.hidden;
        const messagePanel = document.getElementById('message-popover'),
            messageButton = document.querySelector('[data-message-nav]');
        if (messagePanel) messagePanel.hidden = true;
        if (messageButton) {
            messageButton.setAttribute('aria-expanded', 'false');
            messageButton.setAttribute('aria-label', 'Mesajları aç');
        }
        if (opening) {
            panel.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            button.setAttribute('aria-label', 'Bildirimleri kapat');
            load();
        } else close();
    });
    document.addEventListener('click', (event) => {
        if (!panel.hidden && !event.target.closest('.notification-popover-wrap')) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            close();
            button.focus();
        }
    });
    document.addEventListener('project:notifications-updated', () => {
        loadedAt = 0;
        if (!panel.hidden) load();
    });
})();
