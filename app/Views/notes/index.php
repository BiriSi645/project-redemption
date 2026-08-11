<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:24px">
    <div>
        <h1 style="margin:0 0 6px"><?= $isAdmin ? 'Tüm Notlar' : 'Notlar' ?></h1>
        <div style="color:#6b7280">
            <?= $isAdmin ? 'Admin olarak tüm kullanıcıların notlarını görebilirsiniz.' : 'Kendi notlarınız ve diğer kullanıcıların public notları.' ?>
        </div>
    </div>
    <a class="button" href="<?= site_url('notes/create') ?>">Yeni Not</a>
</div>

<?php if (empty($notes)): ?>
    <p>Görüntülenebilecek bir not bulunmuyor.</p>
<?php else: ?>
    <div style="display:grid; gap:16px">
        <?php foreach ($notes as $note): ?>
            <?php $isOwner = (int) $note['user_id'] === $userId; ?>
            <article style="padding:18px; border:1px solid #e5e7eb; border-radius:12px">
                <div style="display:flex; justify-content:space-between; gap:16px; align-items:start">
                    <div>
                        <h2 style="margin:0 0 8px">
                            <a href="<?= site_url('notes/' . $note['id']) ?>" style="color:#111827; text-decoration:none">
                                <?= esc($note['title']) ?>
                            </a>
                        </h2>
                        <div style="font-size:14px; color:#6b7280">
                            Sahibi: <?= esc($note['owner_name'] ?? 'Bilinmiyor') ?> ·
                            <?= (int) $note['is_public'] === 1 ? 'Public' : 'Özel' ?>
                        </div>
                    </div>
                    <?php if ($isOwner): ?>
                        <span style="padding:5px 9px; border-radius:999px; background:#dbeafe; color:#1d4ed8; font-size:13px">Sizin notunuz</span>
                    <?php endif; ?>
                </div>

                <p><?= esc(mb_strimwidth($note['content'], 0, 220, '…')) ?></p>

                <div style="display:flex; gap:8px; align-items:center">
                    <a class="button secondary" href="<?= site_url('notes/' . $note['id']) ?>">Görüntüle</a>
                    <?php if ($isOwner): ?>
                        <a class="button" href="<?= site_url('notes/' . $note['id'] . '/edit') ?>">Düzenle</a>
                    <?php endif; ?>
                    <?php if ($isOwner || $isAdmin): ?>
                        <form method="post" action="<?= site_url('notes/' . $note['id'] . '/delete') ?>" onsubmit="return confirm('Bu not silinsin mi?')">
                            <?= csrf_field() ?>
                            <button class="button danger" type="submit">Sil</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
