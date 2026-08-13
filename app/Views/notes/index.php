<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    .notes-page{max-width:860px;margin:0 auto}.notes-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:22px}.notes-hero h1{margin:0 0 6px;font-size:30px}.notes-intro,.note-meta{color:#6b7280}.new-note-button{white-space:nowrap}.feed-tabs{display:flex;gap:6px;padding:5px;margin-bottom:16px;border:1px solid #e5e7eb;border-radius:12px;background:#f8fafc}.feed-tab{flex:1;padding:9px 12px;border-radius:8px;color:#4b5563;text-align:center;text-decoration:none;font-size:14px;font-weight:700}.feed-tab:hover{background:#fff;color:#111827}.feed-tab.active{background:#fff;color:#2563eb;box-shadow:0 1px 4px rgba(15,23,42,.1)}.notes-filter{display:grid;grid-template-columns:minmax(180px,2fr) minmax(140px,1fr) auto auto;gap:10px;margin-bottom:22px}.notes-filter input,.notes-filter select{min-width:0;padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font:inherit}.notes-feed{display:grid;gap:18px}.note-card{overflow:hidden;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 3px 14px rgba(15,23,42,.05);transition:transform .18s ease,box-shadow .18s ease}.note-card:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(15,23,42,.09)}.note-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:18px 20px 0}.note-author{display:flex;align-items:center;min-width:0;gap:11px}.author-avatar{display:grid;flex:0 0 42px;width:42px;height:42px;place-items:center;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;font-size:17px;font-weight:800;text-transform:uppercase}.author-details{min-width:0}.author-name{display:block;overflow:hidden;color:#111827;font-size:15px;text-overflow:ellipsis;white-space:nowrap}.note-meta{margin-top:3px;font-size:12px}.visibility-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap}.visibility-badge.public{background:#dcfce7;color:#166534}.visibility-badge.private{background:#f3f4f6;color:#4b5563}.note-card-body{display:block;padding:17px 20px 14px;color:inherit;text-decoration:none}.note-title{margin:0 0 10px;color:#111827;font-size:22px;line-height:1.3}.note-preview{margin:0;color:#4b5563;line-height:1.7;white-space:pre-line}.note-category{display:inline-block;margin-top:14px;color:#2563eb;font-size:13px;font-weight:700}.note-card-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 20px;border-top:1px solid #eef0f3}.note-social-actions,.note-owner-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.note-action{display:inline-flex;align-items:center;gap:6px;padding:7px 9px;border:0;border-radius:8px;background:transparent;color:#4b5563;text-decoration:none;font:inherit;font-size:13px;font-weight:700;cursor:pointer}.note-action:hover{background:#f3f4f6;color:#111827}.note-action.edit{color:#2563eb}.note-action.delete{color:#dc2626}.note-owner-actions form{margin:0}.empty-feed{padding:48px 24px;border:1px dashed #cbd5e1;border-radius:16px;text-align:center}.empty-feed strong{display:block;margin-bottom:7px;font-size:18px}.empty-feed p{margin:0;color:#6b7280}
    html[data-theme="dark"] .feed-tabs,html[data-theme="dark"] .visibility-badge.private{background:#0f172a;border-color:#334155}html[data-theme="dark"] .feed-tab{color:#94a3b8}html[data-theme="dark"] .feed-tab:hover,html[data-theme="dark"] .feed-tab.active{background:#334155;color:#93c5fd}html[data-theme="dark"] .note-card{background:#1e293b;border-color:#475569}html[data-theme="dark"] .author-name,html[data-theme="dark"] .note-title{color:#f8fafc}html[data-theme="dark"] .note-preview{color:#cbd5e1}html[data-theme="dark"] .note-card-footer{border-color:#334155}html[data-theme="dark"] .note-action{color:#cbd5e1}html[data-theme="dark"] .note-action:hover{background:#334155;color:#fff}html[data-theme="dark"] .visibility-badge.public{background:#14532d;color:#bbf7d0}html[data-theme="dark"] .visibility-badge.private{color:#cbd5e1}
    @media(max-width:760px){.notes-hero{align-items:flex-start;flex-direction:column}.new-note-button{width:100%;text-align:center}.notes-filter{grid-template-columns:1fr}.note-card-head{padding:16px 16px 0}.note-card-body{padding:15px 16px 13px}.note-card-footer{align-items:flex-start;padding:11px 16px;flex-direction:column}.note-title{font-size:20px}.feed-tab{padding:9px 5px;font-size:12px}}
    @media(max-width:420px){.visibility-badge{padding:5px 7px}.visibility-label{display:none}.notes-hero h1{font-size:26px}}
</style>

<?php
$scopeLabels = ['all' => 'Tüm akış', 'public' => 'Herkese açık', 'mine' => 'Notlarım'];
$scopeUrl = static fn (string $scope): string => site_url('notes') . '?' . http_build_query(['scope' => $scope]);
?>

<div class="notes-page">
    <header class="notes-hero">
        <div>
            <h1><?= $isAdmin ? 'Not Akışı' : 'Topluluk Notları' ?></h1>
            <div class="notes-intro"><?= $isAdmin ? 'Topluluktaki paylaşımları ve özel notları yönetin.' : 'Fikirlerinizi paylaşın, diğer kullanıcıların notlarını keşfedin.' ?></div>
        </div>
        <a class="button new-note-button" href="<?= site_url('notes/create') ?>">＋ Not paylaş</a>
    </header>

    <?= view('partials/active_users', ['activeUsers' => $activeUsers]) ?>

    <nav class="feed-tabs" aria-label="Not görünümü">
        <?php foreach ($scopeLabels as $scope => $label): ?>
            <a class="feed-tab <?= $activeScope === $scope ? 'active' : '' ?>" href="<?= esc($scopeUrl($scope), 'attr') ?>" <?= $activeScope === $scope ? 'aria-current="page"' : '' ?>><?= esc($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <form class="content-filter notes-filter" method="get" action="<?= site_url('notes') ?>">
        <input type="hidden" name="scope" value="<?= esc($activeScope, 'attr') ?>">
        <input type="search" name="q" value="<?= esc($search) ?>" placeholder="Akışta ara…" aria-label="Notlarda ara">
        <select name="category" aria-label="Kategori">
            <option value="">Tüm kategoriler</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= esc($category, 'attr') ?>" <?= $activeCategory === $category ? 'selected' : '' ?>><?= esc($category) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button" type="submit">Filtrele</button>
        <a class="button secondary" href="<?= esc($scopeUrl($activeScope), 'attr') ?>">Temizle</a>
    </form>

    <?php if (empty($notes)): ?>
        <div class="empty-feed">
            <strong>Burada henüz bir paylaşım yok</strong>
            <p>İlk notu paylaşabilir veya filtreleri temizleyebilirsiniz.</p>
        </div>
    <?php else: ?>
        <div class="notes-feed">
            <?php foreach ($notes as $note): ?>
                <?php
                $isOwner = (int) $note['user_id'] === $userId;
                $ownerName = $note['owner_name'] ?? 'Bilinmiyor';
                $initial = mb_substr($ownerName, 0, 1);
                $createdAt = ! empty($note['created_at']) ? strtotime($note['created_at']) : false;
                ?>
                <article class="note-card">
                    <header class="note-card-head">
                        <a class="note-author" href="<?= site_url('users/' . $note['user_id']) ?>" style="text-decoration:none">
                            <span class="author-avatar" aria-hidden="true"><?= esc($initial) ?></span>
                            <div class="author-details">
                                <strong class="author-name"><?= esc($ownerName) ?><?= $isOwner ? ' · Siz' : '' ?></strong>
                                <div class="note-meta"><?= $createdAt ? esc(date('d.m.Y · H:i', $createdAt)) : 'Tarih bilinmiyor' ?></div>
                            </div>
                        </a>
                        <span class="visibility-badge <?= (int) $note['is_public'] === 1 ? 'public' : 'private' ?>" title="<?= (int) $note['is_public'] === 1 ? 'Tüm kullanıcılar görebilir' : 'Yalnızca siz ve admin görebilir' ?>">
                            <span aria-hidden="true"><?= (int) $note['is_public'] === 1 ? '◉' : '●' ?></span>
                            <span class="visibility-label"><?= (int) $note['is_public'] === 1 ? 'Herkese açık' : 'Özel' ?></span>
                        </span>
                    </header>

                    <a class="note-card-body" href="<?= site_url('notes/' . $note['id']) ?>">
                        <h2 class="note-title"><?= esc($note['title']) ?></h2>
                        <p class="note-preview"><?= esc(mb_strimwidth($note['content'], 0, 360, '…')) ?></p>
                        <span class="note-category">#<?= esc(str_replace(' ', '', $note['category'] ?? 'Genel')) ?></span>
                    </a>

                    <footer class="note-card-footer">
                        <div class="note-social-actions">
                            <a class="note-action" href="<?= site_url('notes/' . $note['id']) ?>#comments" aria-label="Yorumları görüntüle">💬 <?= (int) ($note['comment_count'] ?? 0) ?> yorum</a>
                            <a class="note-action" href="<?= site_url('notes/' . $note['id']) ?>">Notu oku →</a>
                        </div>
                        <?php if ($isOwner || $isAdmin): ?>
                            <div class="note-owner-actions">
                                <?php if ($isOwner): ?><a class="note-action edit" href="<?= site_url('notes/' . $note['id'] . '/edit') ?>">Düzenle</a><?php endif; ?>
                                <form method="post" action="<?= site_url('notes/' . $note['id'] . '/delete') ?>" onsubmit="return confirm('Bu not silinsin mi?')">
                                    <?= csrf_field() ?><button class="note-action delete" type="submit">Sil</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
        <?= $pager->links() ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
