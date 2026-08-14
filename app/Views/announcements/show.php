<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<style>.announcement-wrap{max-width:820px;margin:0 auto}.announcement-card{padding:clamp(20px,4vw,38px);border:1px solid #e5e7eb;border-radius:16px}.announcement-badge{display:inline-flex;padding:5px 10px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:700}.announcement-badge.update{background:#ecfdf5;color:#047857}.announcement-card h1{margin:14px 0 8px}.announcement-meta{color:#6b7280;font-size:13px}.announcement-content{margin:28px 0;line-height:1.75;white-space:pre-wrap;overflow-wrap:anywhere}.announcement-actions{display:flex;flex-wrap:wrap;gap:10px}html[data-theme="dark"] .announcement-card{border-color:#475569}html[data-theme="dark"] .announcement-badge{background:#1e3a8a;color:#dbeafe}html[data-theme="dark"] .announcement-badge.update{background:#064e3b;color:#d1fae5}</style>
<div class="announcement-wrap"><article class="announcement-card">
    <span class="announcement-badge <?= $announcement['type'] === 'update' ? 'update' : '' ?>"><?= $announcement['type'] === 'update' ? 'Güncelleme notu' : 'Genel duyuru' ?></span>
    <h1><?= esc($announcement['title']) ?></h1>
    <div class="announcement-meta"><?= esc($announcement['author_username'] ?? 'Yönetim') ?> · <?= date('d.m.Y · H:i', strtotime($announcement['created_at'])) ?></div>
    <div class="announcement-content"><?= esc($announcement['content']) ?></div>
    <div class="announcement-actions">
        <?php if (! empty($announcement['target_path']) && preg_match('#^[a-zA-Z0-9/_-]+$#', $announcement['target_path'])): ?><a class="button" href="<?= site_url($announcement['target_path']) ?>">İlgili sayfayı aç</a><?php endif; ?>
        <a class="button secondary" href="<?= site_url('notifications') ?>">Bildirimlere dön</a>
    </div>
</article></div>
<?= $this->endSection() ?>
