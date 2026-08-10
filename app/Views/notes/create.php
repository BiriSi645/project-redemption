    <?= $this->extend('layouts/main') ?>

    <?= $this->section('content') ?>
    <h1>Yeni Not</h1>
    <?php if (session()->getFlashdata('errors')): ?>

        <?php foreach (session()->getFlashdata('errors') as $error): ?>
            <p style="color: red;">
                <?= esc($error) ?>
            </p>
    <?php endforeach; ?>

<?php endif; ?>
    <form method="post" action="/notes">
        <label>Başlık</label>
        <br>

        <input type="text" name="title">

        <br><br>

        <label>Not</label>
        <br>

        <textarea name="content"></textarea>

        <br><br>

        <button type="submit">Kaydet</button>
    </form>
    <?= $this->endSection() ?>