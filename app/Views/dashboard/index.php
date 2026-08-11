<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    .dashboard-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 26px;
        margin-bottom: 24px;
        border-radius: 16px;
        background: linear-gradient(135deg, #111827, #1e3a8a);
        color: #fff;
    }

    .dashboard-hero h1 { margin: 0 0 8px; }
    .dashboard-hero p { margin: 0; color: #dbeafe; }
    .dashboard-hero .button { white-space: nowrap; background: #fff; color: #111827; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }

    .stat-card {
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
    }

    .stat-label { color: #6b7280; font-size: 14px; }
    .stat-value { display: block; margin-top: 8px; font-size: 30px; font-weight: 700; }

    .dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(260px, .7fr);
        gap: 24px;
    }

    .panel {
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .panel-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 14px;
    }

    .panel-title h2 { margin: 0; font-size: 20px; }
    .panel-title a { color: #2563eb; text-decoration: none; }

    .latest-notes { padding: 0; margin: 0; list-style: none; }
    .latest-notes li { padding: 13px 0; border-bottom: 1px solid #e5e7eb; }
    .latest-notes li:last-child { border-bottom: 0; }
    .latest-notes a { color: #111827; font-weight: 700; text-decoration: none; }
    .note-meta { margin-top: 5px; color: #6b7280; font-size: 13px; }

    .quick-links { display: grid; gap: 10px; }
    .quick-link {
        display: block;
        padding: 14px;
        border-radius: 10px;
        background: #f3f4f6;
        color: #111827;
        text-decoration: none;
        font-weight: 700;
    }
    .quick-link:hover { background: #e5e7eb; }
    .coming-soon { color: #9ca3af; cursor: default; }

    @media (max-width: 850px) {
        .stats-grid { grid-template-columns: 1fr; }
        .dashboard-grid { grid-template-columns: 1fr; }
        .dashboard-hero { align-items: flex-start; flex-direction: column; }
    }
</style>

<section class="dashboard-hero">
    <div>
        <h1>Hoş geldiniz, <?= esc(session()->get('username')) ?>!</h1>
        <p>Notlarınızı yönetin, çalışma sürenizi takip edin ve güne odaklanın.</p>
    </div>
    <a class="button" href="<?= site_url('notes/create') ?>">Yeni Not Oluştur</a>
</section>

<section class="stats-grid" aria-label="Genel özet">
    <article class="stat-card">
        <span class="stat-label">Notlarım</span>
        <span class="stat-value"><?= $ownNoteCount ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Public notlar</span>
        <span class="stat-value"><?= $publicNoteCount ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label"><?= $isAdmin ? 'Tüm görünür notlar' : 'Erişebildiğim notlar' ?></span>
        <span class="stat-value"><?= $visibleCount ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Bekleyen görevler</span>
        <span class="stat-value"><?= $pendingTaskCount ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Bugün bitecek</span>
        <span class="stat-value"><?= $dueTodayCount ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Günlük kayıtlarım</span>
        <span class="stat-value"><?= $journalEntryCount ?></span>
    </article>
</section>

<div class="dashboard-grid">
    <section class="panel">
        <div class="panel-title">
            <h2>Son Notlar</h2>
            <a href="<?= site_url('notes') ?>">Tümünü gör</a>
        </div>

        <?php if (empty($latestNotes)): ?>
            <p style="color:#6b7280">Henüz görüntülenecek bir not yok.</p>
        <?php else: ?>
            <ul class="latest-notes">
                <?php foreach ($latestNotes as $note): ?>
                    <li>
                        <a href="<?= site_url('notes/' . $note['id']) ?>"><?= esc($note['title']) ?></a>
                        <div class="note-meta">
                            <?= (int) $note['user_id'] === $userId ? 'Sizin notunuz' : esc($note['owner_name'] ?? 'Bilinmiyor') ?> ·
                            <?= (int) $note['is_public'] === 1 ? 'Public' : 'Özel' ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <div class="panel-title"><h2>Hızlı Erişim</h2></div>
        <nav class="quick-links" aria-label="Hızlı erişim">
            <a class="quick-link" href="<?= site_url('notes') ?>">Notlarımı Aç</a>
            <a class="quick-link" href="<?= site_url('tasks') ?>">Görevlerimi Aç</a>
            <a class="quick-link" href="<?= site_url('timer') ?>">Kronometreyi Aç</a>
            <a class="quick-link" href="<?= site_url('journal') ?>">Günlüğümü Aç</a>
        </nav>
    </aside>
</div>
<?= $this->endSection() ?>
