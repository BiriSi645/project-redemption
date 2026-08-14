(() => {
    'use strict';

    const stack = document.querySelector('[data-live-toast-stack]');
    if (!stack) return;

    const statusUrl = stack.dataset.statusUrl;
    const memory = new Map();
    let loading = false;
    let previousMessageUnread = null;

    function stored(key) {
        try { return sessionStorage.getItem(key); } catch (error) { return memory.get(key) ?? null; }
    }

    function store(key, value) {
        try { sessionStorage.setItem(key, value); } catch (error) { memory.set(key, value); }
    }

    function setBadge(target, total) {
        if (!target) return;
        let badge = target.querySelector('.notification-badge');
        if (total > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                target.appendChild(badge);
            }
            badge.textContent = total > 99 ? '99+' : String(total);
        } else {
            badge?.remove();
        }
    }

    function dismiss(toast) {
        if (!toast?.isConnected) return;
        toast.classList.add('leaving');
        window.setTimeout(() => toast.remove(), 180);
    }

    function showToast(item) {
        if (!item) return;
        while (stack.children.length >= 3) stack.firstElementChild?.remove();

        const toast = document.createElement('article');
        toast.className = 'live-toast';
        const title = document.createElement('strong');
        const text = document.createElement('p');
        const link = document.createElement('a');
        const close = document.createElement('button');
        title.textContent = item.title;
        text.textContent = item.text;
        link.href = item.url;
        link.textContent = 'Aç →';
        close.type = 'button';
        close.setAttribute('aria-label', 'Bildirimi kapat');
        close.textContent = '×';
        close.addEventListener('click', () => dismiss(toast));
        toast.append(title, text, link, close);
        stack.appendChild(toast);
        window.setTimeout(() => dismiss(toast), 10000);
    }

    function processItem(key, item, allowToast = true) {
        if (!item) return false;
        const id = String(item.id);
        const lastId = stored(key);
        if (lastId === null) {
            store(key, id);
            return false;
        }
        if (Number(id) <= Number(lastId)) return false;
        store(key, id);
        if (allowToast) showToast(item);
        return true;
    }

    async function refresh() {
        if (loading || document.hidden) return;
        loading = true;
        try {
            const response = await fetch(statusUrl, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) return;
            const data = await response.json();
            if (!data.success) return;

            const messageUnread = Number(data.messageUnread) || 0;
            document.querySelectorAll('[data-message-nav]').forEach(target => setBadge(target, messageUnread));
            const notificationUnread = Number(data.notificationUnread) || 0;
            document.querySelectorAll('[data-notification-nav]').forEach(target => setBadge(target, notificationUnread));

            const messageChanged = processItem('pr-live-message', data.latestMessage, !document.getElementById('direct-chat'));
            const notificationChanged = processItem('pr-live-notification', data.latestNotification);
            const duplicateNote = notificationChanged && data.latestNotification?.noteId === data.latestPublicNote?.id;
            processItem('pr-live-public-note', data.latestPublicNote, !duplicateNote);

            if (messageChanged || (previousMessageUnread !== null && previousMessageUnread !== messageUnread)) {
                document.dispatchEvent(new CustomEvent('project:messages-updated'));
            }
            if (notificationChanged) document.dispatchEvent(new CustomEvent('project:notifications-updated'));
            previousMessageUnread = messageUnread;
        } catch (error) {
            // Geçici bağlantı sorununda bir sonraki kontrolü bekle.
        } finally {
            loading = false;
        }
    }

    refresh();
    const interval = window.setInterval(refresh, 10000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    document.addEventListener('project:realtime-message', refresh);
    document.addEventListener('project:realtime-notification', refresh);
    window.addEventListener('pagehide', () => window.clearInterval(interval), { once: true });
})();
