<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<h1>Yeni Not</h1>

<?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert error">
        <?php foreach ($errors as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= site_url('notes') ?>">
    <?= csrf_field() ?>
    <label for="title">Başlık</label>
    <input id="title" type="text" name="title" value="<?= esc(old('title')) ?>" maxlength="255" required>

    <label for="content">Not</label>
    <textarea id="content" name="content" required><?= esc(old('content')) ?></textarea>

    <label style="display:flex; gap:8px; align-items:center; font-weight:normal">
        <input type="checkbox" name="is_public" value="1" <?= old('is_public') === '1' ? 'checked' : '' ?>>
        Bu not public olsun ve diğer kullanıcılar görebilsin
    </label>

    <div style="display:flex; gap:8px; margin-top:22px">
        <button class="button" type="submit">Kaydet</button>
        <a class="button secondary" href="<?= site_url('notes') ?>">İptal</a>
    </div>
</form>
<?= $this->endSection() ?>
