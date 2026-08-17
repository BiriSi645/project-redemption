(() => {
    'use strict';
    const panel = document.querySelector('[data-room-invites]');
    if (!panel) return;
    function render(users) {
        panel.querySelector('.invite-users')?.remove();
        panel.querySelector('.invite-empty')?.remove();
        const available = users.filter(
            (user) => Number(user.id) !== Number(panel.dataset.currentUser),
        );
        if (!available.length) {
            const empty = document.createElement('p');
            empty.className = 'invite-empty';
            empty.textContent = 'Şu anda davet edilebilecek başka bir aktif kullanıcı yok.';
            panel.appendChild(empty);
            return;
        }
        const list = document.createElement('div');
        list.className = 'invite-users';
        available.forEach((user) => {
            const item = document.createElement('div');
            item.className = 'invite-user';
            const name = document.createElement('span');
            name.textContent = user.username;
            const form = document.createElement('form');
            form.method = 'post';
            form.action = panel.dataset.inviteUrl.replace('__USER__', String(user.id));
            form.dataset.roomPreservingAction = '';
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = panel.dataset.csrfName;
            csrf.value = panel.dataset.csrfHash;
            const button = document.createElement('button');
            button.className = 'button';
            button.type = 'submit';
            button.textContent = 'Davet et';
            form.append(csrf, button);
            form.addEventListener('submit', () =>
                document.dispatchEvent(new CustomEvent('project:room-preserve')),
            );
            item.append(name, form);
            list.appendChild(item);
        });
        panel.appendChild(list);
    }
    async function refresh() {
        try {
            const response = await fetch(panel.dataset.usersUrl, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.ok) render((await response.json()).users || []);
        } catch (error) {
            /* Son başarılı liste korunur. */
        }
    }
    document.addEventListener('project:presence-updated', refresh);
    window.setInterval(refresh, 30000);
})();
