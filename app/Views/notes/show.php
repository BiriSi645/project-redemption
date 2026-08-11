<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<article>
    <div style="display:flex; justify-content:space-between; align-items:start; gap:16px">
        <div>
            <h1 style="margin-top:0"><?= esc($note['title']) ?></h1>
            <p style="color:#6b7280">
                Sahibi: <?= esc($note['owner_name'] ?? 'Bilinmiyor') ?> ·
                <?= (int) $note['is_public'] === 1 ? 'Public' : 'Özel' ?>
            </p>
        </div>
        <?php if ($isOwner): ?>
            <a class="button" href="<?= site_url('notes/' . $note['id'] . '/edit') ?>">Düzenle</a>
        <?php endif; ?>
    </div>

    <div style="margin:24px 0; line-height:1.7; white-space:pre-wrap"><?= esc($note['content']) ?></div>
    <div style="display:flex; flex-wrap:wrap; gap:8px">
        <a class="button secondary" href="<?= site_url('notes') ?>">Notlara Dön</a>
        <?php if ($canDelete): ?>
            <form method="post" action="<?= site_url('notes/' . $note['id'] . '/delete') ?>" onsubmit="return confirm('Bu not kalıcı olarak silinsin mi?')">
                <?= csrf_field() ?>
                <button class="button danger" type="submit">Sil</button>
            </form>
        <?php endif; ?>
    </div>
</article>
<?= $this->endSection() ?>
