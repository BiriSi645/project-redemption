(() => {
    'use strict';
    const root = document.querySelector('[data-messenger-dock]');
    if (!root) return;
    const launcher = root.querySelector('[data-messenger-launcher]'), panel = root.querySelector('[data-messenger-panel]'), conversations = root.querySelector('[data-messenger-conversations]'), chat = root.querySelector('[data-messenger-chat]'), chatList = root.querySelector('[data-chat-list]'), chatName = root.querySelector('[data-chat-name]'), chatAvatar = root.querySelector('[data-chat-avatar]'), chatPageLink = root.querySelector('[data-chat-page-link]'), form = root.querySelector('[data-chat-form]'), input = root.querySelector('[data-chat-input]');
    let active = null, lastId = 0, polling = false, timer = null, loadedAt = 0;
    const endpoint = (id, suffix = '') => `${root.dataset.messageBase}/${id}${suffix}`;

    function badge(total) {
        let item = launcher.querySelector('.notification-badge');
        if (total > 0) {
            if (!item) { item = document.createElement('span'); item.className = 'notification-badge'; launcher.appendChild(item); }
            item.textContent = total > 99 ? '99+' : String(total);
        } else item?.remove();
    }
    function state(target, text) {
        target.replaceChildren();
        const item = document.createElement('div'); item.className = 'messenger-state'; item.textContent = text; target.appendChild(item);
    }
    function render(items) {
        conversations.replaceChildren();
        if (!items.length) { state(conversations, 'Henüz bir konuşmanız yok.'); return; }
        items.forEach((conversation) => {
            const button = document.createElement('button'); button.type = 'button'; button.className = `messenger-conversation${conversation.unreadCount > 0 ? ' unread' : ''}`;
            const avatar = document.createElement('span'); avatar.className = 'messenger-avatar'; avatar.textContent = conversation.initial;
            const copy = document.createElement('span'); copy.className = 'messenger-conversation-copy';
            const name = document.createElement('strong'); name.textContent = conversation.username;
            const preview = document.createElement('span'); preview.textContent = conversation.preview; copy.append(name, preview);
            const meta = document.createElement('span'); meta.className = 'messenger-conversation-meta';
            const time = document.createElement('time'); time.textContent = conversation.time; meta.appendChild(time);
            if (conversation.unreadCount > 0) { const unread = document.createElement('b'); unread.textContent = conversation.unreadCount > 99 ? '99+' : String(conversation.unreadCount); meta.appendChild(unread); }
            button.append(avatar, copy, meta); button.addEventListener('click', () => openChat(conversation)); conversations.appendChild(button);
        });
    }
    async function load(force = false) {
        if (!force && Date.now() - loadedAt < 10000) return;
        try {
            const response = await fetch(root.dataset.previewUrl, { cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json(); if (!response.ok || !data.success) throw new Error();
            loadedAt = Date.now(); render(data.conversations || []); badge(Number(data.unreadCount) || 0);
        } catch { state(conversations, 'Konuşmalar şu anda yüklenemedi.'); }
    }
    function append(message) {
        if (chatList.querySelector(`[data-message-id="${message.id}"]`)) return;
        chatList.querySelector('.messenger-state')?.remove();
        const bubble = document.createElement('article'); bubble.className = `messenger-bubble${message.mine ? ' mine' : ''}`; bubble.dataset.messageId = String(message.id);
        const text = document.createElement('p'); text.textContent = message.body;
        const time = document.createElement('time'); time.textContent = message.time; bubble.append(text, time); chatList.appendChild(bubble); lastId = Math.max(lastId, Number(message.id) || 0);
    }
    async function poll() {
        if (!active || polling || document.hidden) return;
        polling = true;
        try {
            const response = await fetch(`${endpoint(active.id, '/poll')}?after=${lastId}`, { cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json(); if (!response.ok || !data.success) throw new Error();
            const nearBottom = chatList.scrollHeight - chatList.scrollTop - chatList.clientHeight < 100;
            (data.messages || []).forEach(append); badge(Number(data.totalUnread) || 0); if (nearBottom) chatList.scrollTop = chatList.scrollHeight;
        } catch { if (!lastId) state(chatList, 'Mesajlar şu anda yüklenemedi.'); }
        finally { polling = false; clearTimeout(timer); if (active) timer = setTimeout(poll, 5000); }
    }
    function openChat(conversation) {
        active = conversation; lastId = 0; panel.hidden = true; chat.hidden = false; chatName.textContent = conversation.username; chatAvatar.textContent = conversation.initial; chatPageLink.href = conversation.url; state(chatList, 'Mesajlar yükleniyor…'); launcher.setAttribute('aria-expanded', 'true'); clearTimeout(timer); poll().then(() => { chatList.scrollTop = chatList.scrollHeight; input.focus(); });
    }
    function close() { panel.hidden = true; chat.hidden = true; active = null; clearTimeout(timer); launcher.setAttribute('aria-expanded', 'false'); }
    launcher.addEventListener('click', () => { if (!panel.hidden || !chat.hidden) { close(); return; } panel.hidden = false; launcher.setAttribute('aria-expanded', 'true'); load(); });
    root.querySelector('[data-messenger-close]')?.addEventListener('click', close);
    root.querySelector('[data-chat-close]')?.addEventListener('click', close);
    form.addEventListener('submit', async (event) => {
        event.preventDefault(); const body = input.value.trim(); if (!body || !active) return;
        const submit = form.querySelector('button'); submit.disabled = true;
        const payload = new URLSearchParams({ body, [root.dataset.csrfName]: root.dataset.csrfHash });
        try {
            const response = await fetch(endpoint(active.id, '/send'), { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }, body: payload });
            const data = await response.json(); if (data.csrfHash) root.dataset.csrfHash = data.csrfHash; if (!response.ok || !data.success) throw new Error(data.message || 'Mesaj gönderilemedi.');
            append(data.message); input.value = ''; chatList.scrollTop = chatList.scrollHeight; loadedAt = 0; document.dispatchEvent(new CustomEvent('project:messages-updated'));
        } catch (error) { window.alert(error.message); }
        finally { submit.disabled = false; input.focus(); }
    });
    input.addEventListener('keydown', (event) => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); form.requestSubmit(); } });
    document.addEventListener('project:messages-updated', () => { loadedAt = 0; load(true); });
    document.addEventListener('project:realtime-message', (event) => { if (Number(event.detail?.conversationId) === Number(active?.id)) { clearTimeout(timer); poll(); } });
    document.addEventListener('visibilitychange', () => { if (!document.hidden && active) poll(); });
})();
