<?= $this->extend("layouts/main") ?>
<?= $this->section("content") ?>
<style>
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px
    }

    .settings-card {
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 12px
    }

    .settings-card h2 {
        margin-top: 0
    }

    .settings-card input,
    .settings-card select {
        width: 100%;
        padding: 11px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        font: inherit
    }

    .settings-card.danger-zone {
        border-color: #fecaca
    }

    .export-links {
        display: flex;
        flex-wrap: wrap;
        gap: 8px
    }

    .checkbox-row {
        display: flex;
        gap: 8px;
        align-items: center;
        font-weight: normal
    }

    .checkbox-row input {
        width: auto
    }

    @media(max-width:800px) {
        .settings-grid {
            grid-template-columns: 1fr
        }
    }
</style>

<h1>Profil ve Ayarlar</h1>
<?php if ($errors = session()->getFlashdata("errors")): ?><div class="alert error"><?php foreach (
    $errors
    as $error
): ?><div><?= esc($error) ?></div><?php endforeach; ?></div><?php endif; ?>

<div class="settings-grid">
    <section class="settings-card">
        <h2>Hesap ve Görünüm</h2>
        <form method="post" action="<?= site_url("profile") ?>">
            <?= csrf_field() ?>
            <label for="email">E-posta</label><input id="email" type="email" name="email" value="<?= esc(
                old("email", $user["email"]),
            ) ?>" required>
            <label for="theme">Tema</label>
            <select id="theme" name="theme">
                <option value="light" <?= $user["theme"] === "light"
                ? "selected"
                : "" ?>>Açık</option>
                <option value="dark" <?= $user["theme"] === "dark"
    ? "selected"
    : "" ?>>Koyu</option>
                <option value="system" <?= $user["theme"] === "system"
    ? "selected"
    : "" ?>>Sistem ayarı</option>
            </select>
            <label class="checkbox-row"><input type="checkbox" name="notifications_enabled"
                    value="1" <?= (int) $user[
                "notifications_enabled"
            ] === 1
                ? "checked"
                : "" ?>> Yaklaşan görevler için tarayıcı bildirimi kullan</label>
            <button class="button" type="submit">Ayarları Kaydet</button>
        </form>
    </section>

    <section class="settings-card">
        <h2>Şifre Değiştir</h2>
        <form method="post" action="<?= site_url("profile/password") ?>">
            <?= csrf_field() ?>
            <label for="current_password">Mevcut şifre</label><input id="current_password"
                type="password" name="current_password" required>
            <label for="password">Yeni şifre</label><input id="password" type="password"
                name="password" minlength="8" required>
            <label for="password_confirm">Yeni şifre tekrar</label><input id="password_confirm"
                type="password" name="password_confirm" minlength="8" required>
            <button class="button" type="submit">Şifreyi Değiştir</button>
        </form>
    </section>

    <section class="settings-card">
        <h2>Verilerimi İndir</h2>
        <p>Notlarınız, görevleriniz ve günlük kayıtlarınız size özel bir dosya olarak hazırlanır.
        </p>
        <div class="export-links"><a class="button secondary" href="<?= site_url(
            "profile/export/json",
        ) ?>">JSON</a><a class="button secondary" href="<?= site_url(
    "profile/export/csv",
) ?>">CSV</a><a class="button secondary" href="<?= site_url(
    "profile/export/txt",
) ?>">Metin</a></div>
    </section>

    <section class="settings-card danger-zone">
        <h2>Hesabı Sil</h2>
        <p>Bu işlem notlarınızı, görevlerinizi ve günlüklerinizi kalıcı olarak siler.</p>
        <form method="post" action="<?= site_url(
            "profile/delete",
        ) ?>"
            onsubmit="return confirm('Hesabınız ve tüm kişisel verileriniz kalıcı olarak silinsin mi?')">
            <?= csrf_field() ?><label for="delete_password">Şifreniz</label><input
                id="delete_password" type="password" name="password" required><button
                class="button danger" type="submit">Hesabımı Sil</button>
        </form>
    </section>
</div>
<?= $this->endSection() ?>