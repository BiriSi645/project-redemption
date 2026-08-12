<?php $selectedTheme = session()->get('theme') ?? 'system'; ?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?= esc($selectedTheme, 'attr') ?>">
<?php if ($selectedTheme === 'system'): ?><script>document.documentElement.dataset.theme=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';</script><?php endif; ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Project Redemption') ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .app { display: flex; min-height: 100vh; }
        .sidebar { width: 240px; padding: 24px 16px; background: #111827; color: #fff; }
        .sidebar-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .sidebar h2 { margin: 0 0 24px; }
        .menu-toggle { display: none; align-items: center; justify-content: center; width: 44px; height: 44px; padding: 0; border: 1px solid #374151; border-radius: 9px; background: #1f2937; color: #fff; cursor: pointer; }
        .menu-toggle:hover { background: #374151; }
        .menu-toggle:focus-visible { outline: 3px solid #60a5fa; outline-offset: 2px; }
        .menu-icon, .menu-icon::before, .menu-icon::after { display: block; width: 21px; height: 2px; border-radius: 2px; background: currentColor; transition: transform .2s ease, opacity .2s ease; }
        .menu-icon { position: relative; }
        .menu-icon::before, .menu-icon::after { position: absolute; left: 0; content: ''; }
        .menu-icon::before { top: -7px; }
        .menu-icon::after { top: 7px; }
        .menu-toggle[aria-expanded="true"] .menu-icon { background: transparent; }
        .menu-toggle[aria-expanded="true"] .menu-icon::before { top: 0; transform: rotate(45deg); }
        .menu-toggle[aria-expanded="true"] .menu-icon::after { top: 0; transform: rotate(-45deg); }
        .sidebar a { display: block; padding: 10px 12px; margin-bottom: 6px; border-radius: 8px; color: #d1d5db; text-decoration: none; }
        .sidebar a:hover { background: #1f2937; color: #fff; }
        .main { display: flex; flex-direction: column; flex: 1; min-width: 0; padding: 32px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
        .topbar-account { display: flex; align-items: center; justify-content: flex-end; gap: 10px; }
        .user { color: #4b5563; }
        .logout { padding: 8px 12px; border: 0; border-radius: 8px; background: #dc2626; color: #fff; cursor: pointer; }
        .content { padding: 24px; border-radius: 14px; background: #fff; }
        .site-footer { width: calc(100% + 240px); margin-top: auto; margin-left: -240px; padding: 28px 12px 4px; color: #6b7280; text-align: center; font-size: 14px; }
        .site-footer .heart { color: #dc2626; }
        .button { display: inline-block; padding: 10px 14px; border: 0; border-radius: 8px; background: #111827; color: #fff; text-decoration: none; cursor: pointer; }
        .button.secondary { background: #e5e7eb; color: #111827; }
        .button.danger { background: #dc2626; }
        .alert { padding: 12px; margin-bottom: 18px; border-radius: 8px; }
        .alert.success { background: #dcfce7; color: #166534; }
        .alert.error { background: #fee2e2; color: #991b1b; }
        .pagination { display:flex; justify-content:center; flex-wrap:wrap; gap:6px; padding:24px 0 0; margin:0; list-style:none; }
        .pagination li { margin:0; }
        .pagination a, .pagination span { display:grid; min-width:36px; height:36px; padding:0 10px; place-items:center; border-radius:8px; background:#e5e7eb; color:#111827; text-decoration:none; }
        .pagination a:hover { background:#d1d5db; }
        .pagination .active a, .pagination .active span { background:#2563eb; color:#fff; }
        .pagination .disabled a, .pagination .disabled span { opacity:.45; }
        input[type="text"], textarea { width: 100%; padding: 11px; border: 1px solid #d1d5db; border-radius: 8px; font: inherit; }
        textarea { min-height: 180px; resize: vertical; }
        label { display: block; margin: 16px 0 6px; font-weight: 700; }
        .speech-input-wrap { position: relative; }
        .speech-input-wrap input, .speech-input-wrap textarea { padding-right: 52px; }
        .speech-button { position: absolute; top: 8px; right: 8px; display: grid; place-items: center; width: 36px; height: 36px; padding: 0; border: 1px solid #d1d5db; border-radius: 9px; background: #fff; color: #374151; font-size: 18px; cursor: pointer; }
        .speech-single-line-wrap .speech-button { top: 50%; transform: translateY(-50%); }
        .speech-button:hover { background: #f3f4f6; }
        .speech-button.listening { border-color: #ef4444; background: #fee2e2; color: #b91c1c; animation: speech-pulse 1.2s infinite; }
        .speech-button:disabled { cursor: not-allowed; opacity: .45; }
        .speech-status { display: block; min-height: 18px; margin-top: 5px; color: #6b7280; font-size: 12px; }
        .speech-status.error { color: #b91c1c; }
        @keyframes speech-pulse { 50% { box-shadow: 0 0 0 5px rgba(239, 68, 68, .16); } }
        .profile-link { display:inline-block; margin-left:10px; color:#2563eb; text-decoration:none; }
        .topbar-account .profile-link { margin-left:0; }
        html[data-theme="dark"] body { background:#0f172a; color:#e5e7eb; }
        html[data-theme="dark"] .content, html[data-theme="dark"] .stat-card, html[data-theme="dark"] .panel, html[data-theme="dark"] .task-card, html[data-theme="dark"] .journal-card, html[data-theme="dark"] .settings-card, html[data-theme="dark"] .habit-card, html[data-theme="dark"] .admin-stat, html[data-theme="dark"] .admin-panel { background:#1e293b !important; border-color:#334155 !important; color:#e5e7eb; }
        html[data-theme="dark"] input, html[data-theme="dark"] textarea, html[data-theme="dark"] select, html[data-theme="dark"] .speech-button { background:#0f172a !important; border-color:#475569 !important; color:#e5e7eb !important; }
        html[data-theme="dark"] .button.secondary, html[data-theme="dark"] .quick-link { background:#334155; color:#e5e7eb; }
        html[data-theme="dark"] .habit-period, html[data-theme="dark"] .dashboard-habit { background:#334155 !important; }
        html[data-theme="dark"] .mini-day { color:#cbd5e1; }
        html[data-theme="dark"] .mini-day.has-event:not(.today) { background:#1e3a8a; color:#dbeafe; }
        html[data-theme="dark"] .user, html[data-theme="dark"] .site-footer, html[data-theme="dark"] .task-description, html[data-theme="dark"] .journal-preview { color:#94a3b8; }
        html[data-theme="dark"] .note-card { background:#1e293b; border-color:#475569; }
        html[data-theme="dark"] .note-title-link, html[data-theme="dark"] .note-detail h1, html[data-theme="dark"] .task-title { color:#f8fafc; }
        html[data-theme="dark"] .note-preview, html[data-theme="dark"] .note-detail-content { color:#e2e8f0; }
        html[data-theme="dark"] .notes-intro, html[data-theme="dark"] .note-meta, html[data-theme="dark"] .task-meta, html[data-theme="dark"] .tasks-header p { color:#cbd5e1 !important; }
        html[data-theme="dark"] .task-card.completed .task-title { color:#cbd5e1; }
        html[data-theme="dark"] .task-description { color:#d1d5db; }
        html[data-theme="dark"] .task-filter { background:#334155; color:#e2e8f0; }
        html[data-theme="dark"] .task-filter.active { background:#2563eb; color:#fff; }
        html[data-theme="dark"] .task-check { background:#0f172a; border-color:#94a3b8; }
        html[data-theme="dark"] .task-card.completed .task-check { background:#16a34a; border-color:#4ade80; }
        html[data-theme="dark"] .countdown { background:#172554; color:#dbeafe; }
        html[data-theme="dark"] .countdown-deadline { color:#93c5fd; }
        html[data-theme="dark"] .countdown.overdue { background:#450a0a; color:#fecaca; }
        html[data-theme="dark"] .priority-high { background:#7f1d1d; color:#fecaca; }
        html[data-theme="dark"] .priority-medium { background:#78350f; color:#fde68a; }
        html[data-theme="dark"] .priority-low { background:#14532d; color:#bbf7d0; }
        html[data-theme="dark"] .latest-notes a { color:#f8fafc; }
        html[data-theme="dark"] .profile-link, html[data-theme="dark"] .panel-title a { color:#60a5fa; }
        html[data-theme="dark"] .comments-section { border-color:#334155; }
        html[data-theme="dark"] .comment-form, html[data-theme="dark"] .comment-card { background:#0f172a; border-color:#475569; }
        html[data-theme="dark"] .comment-meta, html[data-theme="dark"] .comment-form-footer small, html[data-theme="dark"] .comments-empty { color:#94a3b8; }
        html[data-theme="dark"] .comment-meta strong, html[data-theme="dark"] .comment-card p { color:#e2e8f0; }
        html[data-theme="dark"] .admin-head p, html[data-theme="dark"] .admin-stat span, html[data-theme="dark"] .activity-main small, html[data-theme="dark"] .activity-time, html[data-theme="dark"] .user-list small, html[data-theme="dark"] .log-path { color:#94a3b8 !important; }
        html[data-theme="dark"] .chart-day strong { color:#e2e8f0; }
        html[data-theme="dark"] .admin-table th, html[data-theme="dark"] .admin-table td, html[data-theme="dark"] .log-table th, html[data-theme="dark"] .log-table td, html[data-theme="dark"] .activity-list li, html[data-theme="dark"] .user-list li { border-color:#334155; }
        html[data-theme="dark"] .log-action { background:#312e81; color:#c7d2fe; }
        html[data-theme="dark"] .pagination a { background:#334155; color:#e2e8f0; }
        html[data-theme="dark"] .calendar-toolbar, html[data-theme="dark"] .calendar-weekday { background:#0f172a; border-color:#334155; }
        html[data-theme="dark"] .date-jump { border-color:#334155; }
        html[data-theme="dark"] .calendar-day { background:#1e293b; border-color:#334155; }
        html[data-theme="dark"] .calendar-day.outside { background:#172033; }
        html[data-theme="dark"] .calendar-day.selected { background:#422006; }
        html[data-theme="dark"] .calendar-grid { border-color:#334155; }
        html[data-theme="dark"] .day-number, html[data-theme="dark"] .calendar-filter-summary, html[data-theme="dark"] .calendar-legend { color:#cbd5e1; }
        @media (prefers-color-scheme: dark) { html[data-theme="system"] body { background:#0f172a; color:#e5e7eb; } html[data-theme="system"] .content { background:#1e293b; } }
        @media (max-width:760px) { .content-filter { grid-template-columns:1fr !important; } .topbar { align-items:flex-start; flex-direction:column; } .topbar-account { width:100%; align-self:flex-end; justify-content:flex-end; flex-wrap:wrap; text-align:right; } }
        @media (max-width: 760px) {
            .app { display: block; }
            .sidebar { width: 100%; padding: 12px 16px; }
            .sidebar h2 { margin: 0; font-size: 20px; }
            .menu-toggle { display: inline-flex; }
            .sidebar-nav { display: none; padding-top: 12px; }
            .sidebar-nav.open { display: block; }
            .sidebar-nav a:last-child { margin-bottom: 0; }
            .main { padding: 18px; }
            .site-footer { width: 100%; margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Project Redemption</h2>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="sidebar-navigation" aria-label="Menüyü aç">
                <span class="menu-icon" aria-hidden="true"></span>
            </button>
        </div>
        <nav class="sidebar-nav" id="sidebar-navigation" aria-label="Ana menü">
        <a href="<?= site_url('dashboard') ?>">Ana Sayfa</a>
        <a href="<?= site_url('notes') ?>">Notlar</a>
        <a href="<?= site_url('calendar') ?>">Takvim</a>
        <a href="<?= site_url('tasks') ?>">Görevler</a>
        <a href="<?= site_url('habits') ?>">Alışkanlık Takibi</a>
        <a href="<?= site_url('timer') ?>">Kronometre</a>
        <a href="<?= site_url('journal') ?>">Günlük</a>
        <?php if (session()->get('role') === 'admin'): ?>
            <a href="<?= site_url('admin') ?>">Admin Dashboard</a>
            <a href="<?= site_url('admin/users') ?>">Kullanıcı Yönetimi</a>
            <a href="<?= site_url('admin/logs') ?>">Aktivite Logları</a>
        <?php endif; ?>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <strong><?= esc($title ?? 'Project Redemption') ?></strong>
            <div class="topbar-account">
                <span class="user"><?= esc(session()->get('username')) ?><?= session()->get('role') === 'admin' ? ' · Admin' : '' ?></span>
                <a class="profile-link" href="<?= site_url('profile') ?>">Ayarlar</a>
                <form method="post" action="<?= site_url('logout') ?>" style="display:inline">
                    <?= csrf_field() ?>
                    <button class="logout" type="submit">Çıkış Yap</button>
                </form>
            </div>
        </div>

        <div class="content">
            <?php if ($success = session()->getFlashdata('success')): ?>
                <div class="alert success"><?= esc($success) ?></div>
            <?php endif; ?>
            <?= $this->renderSection('content') ?>
        </div>

        <footer class="site-footer">
            Made with <span class="heart" aria-label="love">♥</span> by Halide.
        </footer>
    </main>
</div>
<script src="<?= base_url('js/speech-input.js') ?>"></script>
<?php if ($draftKey = session()->getFlashdata('clearJournalDraft')): ?>
<script>try{localStorage.removeItem(<?= json_encode($draftKey) ?>)}catch(error){/* Depolama kullanılamıyorsa kayıt yine tamamlanmıştır. */}</script>
<?php endif; ?>
<script>
    (() => {
        const button = document.querySelector('.menu-toggle');
        const navigation = document.querySelector('.sidebar-nav');
        if (!button || !navigation) return;

        const closeMenu = () => {
            navigation.classList.remove('open');
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-label', 'Menüyü aç');
        };

        button.addEventListener('click', () => {
            const isOpen = navigation.classList.toggle('open');
            button.setAttribute('aria-expanded', String(isOpen));
            button.setAttribute('aria-label', isOpen ? 'Menüyü kapat' : 'Menüyü aç');
        });

        navigation.addEventListener('click', event => {
            if (event.target.closest('a') && window.innerWidth <= 760) closeMenu();
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeMenu();
        });
    })();
</script>
<script>localStorage.setItem('project-redemption-theme', <?= json_encode($selectedTheme) ?>);localStorage.setItem('project-redemption-theme-default-v2','1');</script>
</body>
</html>
