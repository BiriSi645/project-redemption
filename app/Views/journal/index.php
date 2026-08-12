<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    .journal-header { display:flex; align-items:center; justify-content:space-between; gap:18px; margin-bottom:24px; }
    .journal-header h1 { margin:0 0 6px; }
    .journal-header p { margin:0; color:#6b7280; }
    .privacy-notice { padding:12px 14px; margin-bottom:22px; border-radius:10px; background:#eef2ff; color:#3730a3; }
    .journal-scope { display:flex; gap:6px; max-width:420px; padding:5px; margin-bottom:18px; border:1px solid #e5e7eb; border-radius:11px; background:#f8fafc; }
    .journal-scope a { flex:1; padding:9px 12px; border-radius:7px; color:#4b5563; text-align:center; text-decoration:none; font-size:14px; font-weight:700; }
    .journal-scope a.active { background:#fff; color:#2563eb; box-shadow:0 1px 4px rgba(15,23,42,.1); }
    html[data-theme="dark"] .journal-scope { background:#0f172a; border-color:#334155; }
    html[data-theme="dark"] .journal-scope a { color:#94a3b8; }
    html[data-theme="dark"] .journal-scope a.active { background:#334155; color:#93c5fd; }
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
        <h1><?= $isAdmin && $activeScope === 'all' ? 'Tüm Günlükler' : 'Günlüğüm' ?></h1>
        <p><?= $isAdmin && $activeScope === 'all' ? 'Yönetim amacıyla diğer kullanıcıların günlüklerini görüntülüyorsunuz.' : 'Düşüncelerinizi ve gününüzü yalnızca size özel kaydedin.' ?></p>
    </div>
    <a class="button" href="<?= site_url('journal/create') ?>">Yeni Kayıt</a>
</div>

<?php if ($isAdmin): ?>
    <nav class="journal-scope" aria-label="Günlük görünümü">
        <a class="<?= $activeScope === 'mine' ? 'active' : '' ?>" href="<?= site_url('journal') ?>" <?= $activeScope === 'mine' ? 'aria-current="page"' : '' ?>>Kendi günlüklerim</a>
        <a class="<?= $activeScope === 'all' ? 'active' : '' ?>" href="<?= site_url('journal') ?>?scope=all" <?= $activeScope === 'all' ? 'aria-current="page"' : '' ?>>Tüm günlükler</a>
    </nav>
<?php endif; ?>

<div class="privacy-notice">
    🔒 <?= $isAdmin && $activeScope === 'all' ? 'Diğer kullanıcıların günlüklerini görüntüleyebilirsiniz; yalnızca kendi kayıtlarınızı düzenleyebilir veya silebilirsiniz.' : 'Günlük kayıtları public yapılamaz. Kayıtları yalnızca sahibi düzenleyebilir veya silebilir.' ?>
</div>

<form class="content-filter" method="get" action="<?= site_url('journal') ?>" style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr auto auto; gap:9px; margin-bottom:22px">
    <?php if ($isAdmin): ?><input type="hidden" name="scope" value="<?= esc($activeScope, 'attr') ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= esc($search) ?>" placeholder="Günlüklerde ara…" style="padding:10px; border:1px solid #d1d5db; border-radius:8px">
    <select name="mood" style="padding:10px; border:1px solid #d1d5db; border-radius:8px; background:#fff">
        <option value="">Tüm ruh hâlleri</option>
        <?php foreach (['great'=>'Harika','good'=>'İyi','neutral'=>'Normal','bad'=>'Kötü','awful'=>'Çok kötü'] as $value=>$label): ?>
            <option value="<?= $value ?>" <?= $activeMood === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" value="<?= esc($dateFrom) ?>" title="Başlangıç tarihi" style="padding:10px; border:1px solid #d1d5db; border-radius:8px">
    <input type="date" name="date_to" value="<?= esc($dateTo) ?>" title="Bitiş tarihi" style="padding:10px; border:1px solid #d1d5db; border-radius:8px">
    <button class="button" type="submit">Filtrele</button>
    <a class="button secondary" href="<?= site_url('journal') ?><?= $isAdmin && $activeScope === 'all' ? '?scope=all' : '' ?>">Temizle</a>
</form>

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
<?php if (! empty($entries)): ?><?= $pager->links() ?><?php endif; ?>
<?= $this->endSection() ?>
