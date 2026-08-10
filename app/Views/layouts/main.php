<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $title ?? 'Project Redemption' ?></title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #222;
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 220px;
            background: #111827;
            color: white;
            padding: 24px 16px;
        }

        .sidebar h2 {
            margin-top: 0;
        }

        .sidebar a {
            display: block;
            color: #d1d5db;
            text-decoration: none;
            padding: 10px 12px;
            margin-bottom: 6px;
            border-radius: 8px;
        }

        .sidebar a:hover {
            background: #1f2937;
            color: white;
        }

        .main {
            flex: 1;
            padding: 32px;
        }

        .topbar {
            margin-bottom: 24px;
        }

        .content {
            background: white;
            padding: 24px;
            border-radius: 14px;
        }
    </style>
</head>

<body>

<div class="app">

    <aside class="sidebar">
        <h2>Project Redemption</h2>

        <a href="<?= site_url('/') ?>">Ana Sayfa</a>
        <a href="<?= site_url('notes') ?>">Notlarım</a>
        <a href="#">Görevler</a>
        <a href="#">Kronometre</a>
        <a href="#">Günlük</a>
        <a href="#">AI Asistan</a>
    </aside>

    <main class="main">

        <div class="topbar">
            <strong><?= $title ?? 'Project Redemption' ?></strong>
        </div>

        <div class="content">
            <?= $this->renderSection('content') ?>
        </div>

    </main>

</div>

</body>
</html>