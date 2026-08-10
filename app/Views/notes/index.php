
    <?= $this->extend('layouts/main') ?>

    <?= $this->section('content') ?>

    <h1>Notlarım</h1>

    <a href="/notes/create">Yeni Not Oluştur</a>

    <hr>

    <?php if (empty($notes)): ?>

        <p>Henüz hiç not yok.</p>

    <?php else: ?>

        <?php foreach ($notes as $note): ?>

            <h2><?= esc($note['title']) ?></h2>
            <p><?= esc($note['content']) ?></p>

            <a href="/notes/<?= $note['id'] ?>/edit">
                Düzenle
            </a>

            <form method="post" action="/notes/<?= $note['id'] ?>/delete">
                <button type="submit">Sil</button>
            </form>

        <?php endforeach; ?>

    <?php endif; ?>
    
    <?= $this->endSection() ?>