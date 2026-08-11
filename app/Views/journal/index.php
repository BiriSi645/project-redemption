<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    .journal-header { display:flex; align-items:center; justify-content:space-between; gap:18px; margin-bottom:24px; }
    .journal-header h1 { margin:0 0 6px; }
    .journal-header p { margin:0; color:#6b7280; }
    .privacy-notice { padding:12px 14px; margin-bottom:22px; border-radius:10px; background:#eef2ff; color:#3730a3; }
    .journal-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; }
    .journal-card { display:flex; flex-direction:column; min-height:230px; padding:20px; border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
    .journal-card-top { display:flex; justify-content:space-between; gap:12px; align-items:start; }
    .journal-date { color:#6b7280; font-size:14px; }
    .journal-mood { font-size:26px; line-height:1; }
    .journal-title { margin:12px 0 8px; }
    .journal-title a { color:#111827; text-decoration:none; }
    .journal-preview { flex:1; margin:0 0 16px; color:#4b5563; line-height:1.55; }
    .journal-meta { margin-bottom:13px; color:#6b7280; font-size:13px; }
    .journal-actions { display:flex; flex-wrap:wrap; gap:8px; }
    .journal-actions form { margin:0; }
    .journal-actions .button { padding:8px 11px; }
    .empty-state { grid-column:1/-1; padding:44px 20px; border:1px dashed #d1d5db; border-radius:14px; text-align:center; color:#6b7280; }
    @media (max-width:850px) { .journal-grid { grid-template-columns:1fr; } }
    @media (max-width:650px) { .journal-header { align-items:flex-start; flex-direction:column; } }
</style>

<?php
    $moods = [
        'great' => ['Harika', '😄'],
        'good' => ['İyi', '🙂'],
        'neutral' => ['Normal', '😐'],
        'bad' => ['Kötü', '😕'],
        'awful' => ['Çok kötü', '😞'],
    ];
?>

<div class="journal-header">
    <div>
        <h1><?= $isAdmin ? 'Tüm Günlükler' : 'Günlüğüm' ?></h1>
        <p><?= $isAdmin ? 'Admin olarak tüm kullanıcıların private günlüklerini görüntülüyorsunuz.' : 'Düşüncelerinizi ve gününüzü yalnızca size özel kaydedin.' ?></p>
    </div>
    <a class="button" href="<?= site_url('journal/create') ?>">Yeni Kayıt</a>
</div>

<div class="privacy-notice">
    🔒 Günlük kayıtları public yapılamaz. <?= $isAdmin ? 'Kayıtları yalnızca sahibi düzenleyebilir veya silebilir.' : 'Kayıtlarınızı yalnızca siz ve admin görüntüleyebilir.' ?>
</div>

<div class="journal-grid">
    <?php if (empty($entries)): ?>
        <div class="empty-state">
            <p>Henüz günlük kaydı bulunmuyor.</p>
            <a class="button" href="<?= site_url('journal/create') ?>">İlk Kaydı Oluştur</a>
        </div>
    <?php else: ?>
        <?php foreach ($entries as $entry): ?>
            <?php $isOwner = (int) $entry['user_id'] === $userId; ?>
            <article class="journal-card">
                <div class="journal-card-top">
                    <time class="journal-date" datetime="<?= esc($entry['entry_date'], 'attr') ?>"><?= date('d.m.Y', strtotime($entry['entry_date'])) ?></time>
                    <span class="journal-mood" title="<?= esc($moods[$entry['mood']][0] ?? 'Normal', 'attr') ?>"><?= $moods[$entry['mood']][1] ?? '😐' ?></span>
                </div>
                <h2 class="journal-title"><a href="<?= site_url('journal/' . $entry['id']) ?>"><?= esc($entry['title']) ?></a></h2>
                <p class="journal-preview"><?= esc(mb_strimwidth($entry['content'], 0, 240, '…')) ?></p>
                <?php if ($isAdmin): ?>
                    <div class="journal-meta">Sahibi: <?= esc($entry['owner_name'] ?? 'Bilinmiyor') ?><?= $isOwner ? ' · Sizin kaydınız' : '' ?></div>
                <?php endif; ?>
                <div class="journal-actions">
                    <a class="button secondary" href="<?= site_url('journal/' . $entry['id']) ?>">Oku</a>
                    <?php if ($isOwner): ?>
                        <a class="button" href="<?= site_url('journal/' . $entry['id'] . '/edit') ?>">Düzenle</a>
                        <form method="post" action="<?= site_url('journal/' . $entry['id'] . '/delete') ?>" onsubmit="return confirm('Bu günlük kaydı silinsin mi?')">
                            <?= csrf_field() ?>
                            <button class="button danger" type="submit">Sil</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
