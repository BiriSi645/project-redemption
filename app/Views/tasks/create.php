<?= $this->extend("layouts/main") ?>

<?= $this->section("content") ?>
<style>
    .task-form {
        max-width: 720px;
    }

    .task-form select,
    .task-form input[type="date"],
    .task-form input[type="time"] {
        width: 100%;
        padding: 11px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        font: inherit;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
    }

    @media (max-width:620px) {
        .form-grid {
            grid-template-columns: 1fr;
            gap: 0;
        }
    }
</style>

<div class="task-form">
    <h1>Yeni Görev</h1>

    <?php if ($errors = session()->getFlashdata("errors")): ?>
    <div class="alert error"><?php foreach ($errors as $error): ?><div><?= esc(
    $error,
) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url("tasks") ?>">
        <?= csrf_field() ?>
        <label for="title">Başlık</label>
        <input id="title" type="text" name="title" value="<?= esc(
            old("title"),
        ) ?>" maxlength="255" data-speech-input required>

        <label for="description">Açıklama</label>
        <textarea id="description" name="description" maxlength="5000" data-speech-input><?= esc(
            old("description"),
        ) ?></textarea>

        <label for="category">Kategori</label>
        <input id="category" type="text" name="category" value="<?= esc(
            old("category", "Genel"),
        ) ?>" maxlength="100" placeholder="Örn. İş, Okul, Kişisel" required>

        <div class="form-grid">
            <div>
                <label for="priority">Öncelik</label>
                <select id="priority" name="priority" required>
                    <option value="low" <?= old("priority") === "low"
                        ? "selected"
                        : "" ?>>Düşük</option>
                    <option value="medium" <?= old("priority", "medium") === "medium"
                        ? "selected"
                        : "" ?>>Orta</option>
                    <option value="high" <?= old("priority") === "high"
                        ? "selected"
                        : "" ?>>Yüksek</option>
                </select>
            </div>
            <div>
                <label for="due_date">Son tarih</label>
                <input id="due_date" type="date" name="due_date" value="<?= esc(
                    old("due_date"),
                ) ?>">
            </div>
            <div>
                <label for="due_time">Son saat</label>
                <input id="due_time" type="time" name="due_time" value="<?= esc(
                    old("due_time", "23:59"),
                ) ?>">
            </div>
        </div>

        <div style="display:flex; gap:8px; margin-top:22px">
            <button class="button" type="submit">Görevi Kaydet</button>
            <a class="button secondary" href="<?= site_url("tasks") ?>">İptal</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>