<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    .journal-form { max-width:800px; }
    .journal-form input[type="date"], .journal-form select { width:100%; padding:11px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font:inherit; }
    .journal-form textarea { min-height:300px; line-height:1.6; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .privacy-note { padding:11px 13px; margin:16px 0; border-radius:9px; background:#eef2ff; color:#3730a3; }
    @media (max-width:620px) { .form-grid { grid-template-columns:1fr; gap:0; } }
</style>

<div class="journal-form">
    <h1>Günlük Kaydını Düzenle</h1>
    <div class="privacy-note">🔒 Bu kayıt private kalacak; public olarak paylaşılamaz.</div>

    <?php if ($errors = session()->getFlashdata('errors')): ?>
        <div class="alert error"><?php foreach ($errors as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('journal/' . $entry['id']) ?>">
        <?= csrf_field() ?>
        <?php $selectedMood = old('mood', $entry['mood']); ?>
        <div class="form-grid">
            <div>
                <label for="entry_date">Tarih</label>
                <input id="entry_date" type="date" name="entry_date" value="<?= esc(old('entry_date', $entry['entry_date'])) ?>" required>
            </div>
            <div>
                <label for="mood">O gün nasıl hissediyordunuz?</label>
                <select id="mood" name="mood" required>
                    <option value="great" <?= $selectedMood === 'great' ? 'selected' : '' ?>>😄 Harika</option>
                    <option value="good" <?= $selectedMood === 'good' ? 'selected' : '' ?>>🙂 İyi</option>
                    <option value="neutral" <?= $selectedMood === 'neutral' ? 'selected' : '' ?>>😐 Normal</option>
                    <option value="bad" <?= $selectedMood === 'bad' ? 'selected' : '' ?>>😕 Kötü</option>
                    <option value="awful" <?= $selectedMood === 'awful' ? 'selected' : '' ?>>😞 Çok kötü</option>
                </select>
            </div>
        </div>

        <label for="title">Başlık</label>
        <input id="title" type="text" name="title" value="<?= esc(old('title', $entry['title'])) ?>" maxlength="255" required>

        <label for="content">Günlük</label>
        <textarea id="content" name="content" maxlength="20000" required><?= esc(old('content', $entry['content'])) ?></textarea>

        <div style="display:flex; gap:8px; margin-top:22px">
            <button class="button" type="submit">Güncelle</button>
            <a class="button secondary" href="<?= site_url('journal/' . $entry['id']) ?>">İptal</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
