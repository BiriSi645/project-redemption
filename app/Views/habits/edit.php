<?= $this->extend("layouts/main") ?>
<?= $this->section("content") ?>
<style>
    .habit-form {
        max-width: 720px
    }

    .habit-form select,
    .habit-form input[type="number"] {
        width: 100%;
        padding: 11px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        font: inherit
    }

    .frequency-help {
        color: #6b7280;
        font-size: 13px
    }
</style>
<div class="habit-form">
    <h1>Alışkanlığı Düzenle</h1>
    <?php if (
        $errors = session()->getFlashdata("errors")
    ): ?><div class="alert error"><?php foreach ($errors as $error): ?><div><?= esc(
    $error,
) ?></div><?php endforeach; ?></div><?php endif; ?>
    <form method="post" action="<?= site_url("habits/" . $habit["id"]) ?>">
        <?= csrf_field() ?>
        <label for="title">Başlık</label><input id="title" type="text" name="title" value="<?= esc(
            old("title", $habit["title"]),
        ) ?>" maxlength="255" data-speech-input required>
        <label for="description">Açıklama</label><textarea id="description" name="description"
            maxlength="2000" data-speech-input><?= esc(
            old("description", $habit["description"] ?? ""),
        ) ?></textarea>
        <?php $frequency = old("frequency", $habit["frequency"]); ?>
        <label for="frequency">Tekrarlama sıklığı</label><select id="frequency" name="frequency"
            required>
            <option value="daily" <?= $frequency ===
        "daily"
            ? "selected"
            : "" ?>>Her gün</option>
            <option value="weekly" <?= $frequency === "weekly"
    ? "selected"
    : "" ?>>Her hafta</option>
            <option value="monthly" <?= $frequency === "monthly"
    ? "selected"
    : "" ?>>Her ay</option>
        </select>
        <label for="target_count">Bu dönem içinde kaç farklı gün?</label><input id="target_count"
            type="number" name="target_count" value="<?= esc(
            old("target_count", $habit["target_count"] ?? 1),
        ) ?>" min="1" max="31" required>
        <p id="frequency-help" class="frequency-help"></p>
        <div style="display:flex;gap:8px;margin-top:22px"><button class="button"
                type="submit">Güncelle</button><a class="button secondary" href="<?= site_url(
            "habits",
        ) ?>">İptal</a></div>
    </form>
</div>
<script>
    (() => {
        const frequency = document.getElementById('frequency'),
            target = document.getElementById('target_count'),
            help = document.getElementById('frequency-help');
        const sync = () => {
            const limits = {
                    daily: 1,
                    weekly: 7,
                    monthly: 31
                },
                labels = {
                    daily: 'Her gün bir kez işaretlemeniz beklenir.',
                    weekly: 'Her hafta seçtiğiniz sayıda farklı gün işaretlemeniz beklenir.',
                    monthly: 'Her ay seçtiğiniz sayıda farklı gün işaretlemeniz beklenir.'
                };
            target.max = limits[frequency.value];
            if (frequency.value === 'daily') target.value = 1;
            else if (Number(target.value) > limits[frequency.value]) target.value = limits[
                frequency.value];
            help.textContent = labels[frequency.value];
        };
        frequency.addEventListener('change', sync);
        sync();
    })();
</script>
<?= $this->endSection() ?>