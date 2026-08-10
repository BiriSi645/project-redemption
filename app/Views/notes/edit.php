    <?= $this->extend('layouts/main') ?>

    <?= $this->section('content') ?>

    <h1>Not Düzenle</h1>
    <?php if (session()->getFlashdata('errors')): ?>

        <?php foreach (session()->getFlashdata('errors') as $error): ?>
            <p style="color: red;">
                <?= esc($error) ?>
            </p>
        <?php endforeach; ?>

    <?php endif; ?> 

    <form method="post" action="/notes/<?= $note['id'] ?>">

        <label>Başlık</label>
        <br>

        <input
            type="text"
            name="title"
            value="<?= esc($note['title']) ?>"
        >

        <br><br>

        <label>Not</label>
        <br>

        <textarea name="content"><?= esc($note['content']) ?></textarea>

        <br><br>

        <button type="submit">Güncelle</button>

    </form>
    <?= $this->endSection() ?>
