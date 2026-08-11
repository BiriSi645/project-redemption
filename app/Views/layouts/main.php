<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Project Redemption') ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .app { display: flex; min-height: 100vh; }
        .sidebar { width: 240px; padding: 24px 16px; background: #111827; color: #fff; }
        .sidebar h2 { margin: 0 0 24px; }
        .sidebar a { display: block; padding: 10px 12px; margin-bottom: 6px; border-radius: 8px; color: #d1d5db; text-decoration: none; }
        .sidebar a:hover { background: #1f2937; color: #fff; }
        .main { flex: 1; min-width: 0; padding: 32px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
        .user { color: #4b5563; }
        .logout { padding: 8px 12px; border: 0; border-radius: 8px; background: #dc2626; color: #fff; cursor: pointer; }
        .content { padding: 24px; border-radius: 14px; background: #fff; }
        .button { display: inline-block; padding: 10px 14px; border: 0; border-radius: 8px; background: #111827; color: #fff; text-decoration: none; cursor: pointer; }
        .button.secondary { background: #e5e7eb; color: #111827; }
        .button.danger { background: #dc2626; }
        .alert { padding: 12px; margin-bottom: 18px; border-radius: 8px; }
        .alert.success { background: #dcfce7; color: #166534; }
        .alert.error { background: #fee2e2; color: #991b1b; }
        input[type="text"], textarea { width: 100%; padding: 11px; border: 1px solid #d1d5db; border-radius: 8px; font: inherit; }
        textarea { min-height: 180px; resize: vertical; }
        label { display: block; margin: 16px 0 6px; font-weight: 700; }
        @media (max-width: 760px) { .app { display: block; } .sidebar { width: 100%; } .main { padding: 18px; } }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <h2>Project Redemption</h2>
        <a href="<?= site_url('dashboard') ?>">Ana Sayfa</a>
        <a href="<?= site_url('notes') ?>">Notlarım</a>
        <a href="<?= site_url('tasks') ?>">Görevler</a>
        <a href="<?= site_url('timer') ?>">Kronometre</a>
        <a href="<?= site_url('journal') ?>">Günlük</a>
        <a href="#">AI Asistan</a>
    </aside>

    <main class="main">
        <div class="topbar">
            <strong><?= esc($title ?? 'Project Redemption') ?></strong>
            <div>
                <span class="user"><?= esc(session()->get('username')) ?><?= session()->get('role') === 'admin' ? ' · Admin' : '' ?></span>
                <form method="post" action="<?= site_url('logout') ?>" style="display:inline; margin-left:12px">
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
    </main>
</div>
</body>
</html>
