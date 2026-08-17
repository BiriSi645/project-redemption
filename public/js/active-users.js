(() => {
    'use strict';
    const panel = document.querySelector('[data-active-users]');
    if (!panel) return;
    const count = panel.querySelector('[data-active-count]');

    function render(users) {
        count.textContent = `${users.length} çevrimiçi`;
        let list = panel.querySelector('[data-active-list]');
        const empty = panel.querySelector('[data-active-empty]');
        if (!list) {
            list = document.createElement('div');
            list.className = 'active-users-list';
            list.dataset.activeList = '';
            panel.appendChild(list);
        }
        list.innerHTML = '';
        empty?.remove();
        if (!users.length) {
            const message = document.createElement('p');
            message.className = 'active-users-empty';
            message.dataset.activeEmpty = '';
            message.textContent = 'Şu anda aktif kullanıcı görünmüyor.';
            panel.appendChild(message);
            list.remove();
            return;
        }

        const fragment = document.createDocumentFragment();
        users.forEach((user) => {
            const item = document.createElement('a');
            item.className = 'active-user';
            item.href = user.profileUrl;
            item.title = 'Profili görüntüle';
            const avatar = document.createElement('span');
            avatar.className = 'active-user-avatar';
            avatar.setAttribute('aria-hidden', 'true');
            avatar.textContent = Array.from(user.username)[0]?.toLocaleUpperCase('tr-TR') || '?';
            avatar.appendChild(document.createElement('i'));
            const info = document.createElement('span');
            const name = document.createElement('strong');
            const state = document.createElement('small');
            name.textContent = user.username;
            state.textContent =
                Number(user.id) === Number(panel.dataset.currentUser)
                    ? 'Siz'
                    : user.role === 'admin'
                      ? 'Admin'
                      : 'Çevrimiçi';
            info.append(name, state);
            item.append(avatar, info);
            fragment.appendChild(item);
        });
        list.appendChild(fragment);
    }

    async function refresh() {
        if (document.hidden) return;
        try {
            const response = await fetch(panel.dataset.usersUrl, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.ok) render((await response.json()).users || []);
        } catch (error) {
            // Son başarılı liste ekranda kalır.
        }
    }

    window.setInterval(refresh, 30000);
    document.addEventListener('project:presence-updated', refresh);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refresh();
    });
})();
