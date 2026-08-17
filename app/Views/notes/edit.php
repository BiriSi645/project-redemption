<?= $this->extend("layouts/main") ?>

<?= $this->section("content") ?>
<h1>Notu Düzenle</h1>

<?php if ($errors = session()->getFlashdata("errors")): ?>
<div class="alert error">
    <?php foreach ($errors as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= site_url("notes/" . $note["id"]) ?>">
    <?= csrf_field() ?>
    <label for="title">Başlık</label>
    <input id="title" type="text" name="title" value="<?= esc(
        old("title", $note["title"]),
    ) ?>" maxlength="255" data-speech-input required>

    <label for="content">Not</label>
    <textarea id="content" name="content" data-speech-input required><?= esc(
        old("content", $note["content"]),
    ) ?></textarea>
    <small>Bir kullanıcıyı etiketlemek için @kullaniciadi yazın. Etiket içeren not public
        olmalıdır.</small>

    <label for="category">Kategori</label>
    <input id="category" type="text" name="category" value="<?= esc(
        old("category", $note["category"] ?? "Genel"),
    ) ?>" maxlength="100" required>

    <label style="display:flex; gap:8px; align-items:center; font-weight:normal">
        <input type="checkbox" name="is_public" value="1" <?= old(
            "is_public",
            (string) $note["is_public"],
        ) === "1"
            ? "checked"
            : "" ?>>
        Bu not public olsun ve diğer kullanıcılar görebilsin
    </label>

    <div style="display:flex; gap:8px; margin-top:22px">
        <button class="button" type="submit">Güncelle</button>
        <a class="button secondary" href="<?= site_url("notes") ?>">İptal</a>
    </div>
</form>
<?= $this->endSection() ?>