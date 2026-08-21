<?= $this->extend("layouts/main") ?>
<?= $this->section("content") ?>

<style>
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .settings-card {
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .settings-card h2 {
        margin-top: 0;
    }

    .settings-card p {
        line-height: 1.5;
    }

    .settings-card label {
        display: block;
        margin-top: 14px;
        margin-bottom: 6px;
    }

    .settings-card input,
    .settings-card select {
        width: 100%;
        padding: 11px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        font: inherit;
        box-sizing: border-box;
    }

    .settings-card button {
        margin-top: 16px;
    }

    .settings-card.danger-zone {
        border-color: #fecaca;
    }

    .current-email {
        padding: 11px 13px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f8fafc;
        color: #374151;
        word-break: break-word;
    }

    .email-info {
        margin-top: 12px;
        padding: 12px;
        border-radius: 8px;
        background: #eff6ff;
        color: #1e40af;
        font-size: 14px;
    }

    .pending-email-box {
        margin-top: 18px;
        padding: 16px;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        background: #eff6ff;
    }

    .pending-email-box h3 {
        margin: 0 0 8px;
        font-size: 16px;
    }

    .pending-email-box p {
        margin: 5px 0;
    }

    .verification-code-input {
        font-size: 22px !important;
        font-weight: 700;
        letter-spacing: 6px;
        text-align: center;
    }

    .export-links {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .checkbox-row {
        display: flex !important;
        gap: 8px;
        align-items: center;
        font-weight: normal;
    }

    .checkbox-row input {
        width: auto;
    }

    html[data-theme="dark"] .current-email {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    html[data-theme="dark"] .email-info,
    html[data-theme="dark"] .pending-email-box {
        background: #172554;
        border-color: #1d4ed8;
        color: #bfdbfe;
    }

    @media(max-width: 800px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<h1>Profil ve Ayarlar</h1>

<?php if ($errors = session()->getFlashdata("errors")): ?>
    <div class="alert error">
        <?php foreach ($errors as $error): ?>
            <div><?= esc($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($success = session()->getFlashdata("success")): ?>
    <div class="alert success">
        <?= esc($success) ?>
    </div>
<?php endif; ?>

<div class="settings-grid">

    <!-- GENEL AYARLAR -->
    <section class="settings-card">
        <h2>Hesap ve Görünüm</h2>

        <label>Mevcut e-posta</label>

        <div class="current-email">
            <?= esc($user["email"]) ?>
        </div>

        <p class="email-info">
            E-posta adresinizi değiştirmek için
            "E-posta Değiştir" bölümünü kullanın.
        </p>

        <form
            method="post"
            action="<?= site_url("profile") ?>"
        >
            <?= csrf_field() ?>

            <label for="theme">
                Tema
            </label>

            <select
                id="theme"
                name="theme"
            >
                <option
                    value="light"
                    <?= $user["theme"] === "light"
                        ? "selected"
                        : "" ?>
                >
                    Açık
                </option>

                <option
                    value="dark"
                    <?= $user["theme"] === "dark"
                        ? "selected"
                        : "" ?>
                >
                    Koyu
                </option>

                <option
                    value="system"
                    <?= $user["theme"] === "system"
                        ? "selected"
                        : "" ?>
                >
                    Sistem ayarı
                </option>
            </select>

            <label class="checkbox-row">
                <input
                    type="checkbox"
                    name="notifications_enabled"
                    value="1"
                    <?= (int) $user["notifications_enabled"] === 1
                        ? "checked"
                        : "" ?>
                >

                Yaklaşan görevler için tarayıcı bildirimi kullan
            </label>

            <button
                class="button"
                type="submit"
            >
                Ayarları Kaydet
            </button>
        </form>
    </section>


    <!-- E-POSTA DEĞİŞTİR -->
    <section class="settings-card">
        <h2>E-posta Değiştir</h2>

        <p>
            Yeni e-posta adresinizi doğrulamadan
            mevcut adresiniz değiştirilmeyecektir.
        </p>

        <form
            method="post"
            action="<?= site_url("profile/email/request") ?>"
            autocomplete="off"
        >
            <?= csrf_field() ?>

            <label for="new_email">
                Yeni e-posta
            </label>

            <input
                id="new_email"
                type="email"
                name="new_email"
                value="<?= esc(old("new_email")) ?>"
                autocomplete="username"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                required
            >

            <label for="email_current_password">
                Mevcut şifre
            </label>

            <input
                id="email_current_password"
                type="password"
                name="email_current_password"
                autocomplete="new-password"
                required
            >

            <button
                class="button"
                type="submit"
            >
                Doğrulama Kodu Gönder
            </button>
        </form>


        <?php if (! empty($user["pending_email"])): ?>

            <div class="pending-email-box">

                <h3>
                    Doğrulama bekleniyor
                </h3>

                <p>
                    Şu adrese gönderilen
                    6 haneli kodu girin:
                </p>

                <strong>
                    <?= esc($user["pending_email"]) ?>
                </strong>

                <form
                    method="post"
                    action="<?= site_url(
                        "profile/email/verify"
                    ) ?>"
                >
                    <?= csrf_field() ?>

                    <label for="email_verification_code">
                        Doğrulama kodu
                    </label>

                    <input
                        class="verification-code-input"
                        id="email_verification_code"
                        type="text"
                        name="code"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        minlength="6"
                        pattern="[0-9]{6}"
                        placeholder="000000"
                        required
                    >

                    <button
                        class="button"
                        type="submit"
                    >
                        E-postayı Doğrula
                    </button>
                </form>

            </div>

        <?php endif; ?>

    </section>


    <!-- ŞİFRE DEĞİŞTİR -->
    <section class="settings-card">
        <h2>Şifre Değiştir</h2>

        <form
            method="post"
            action="<?= site_url(
                "profile/password"
            ) ?>"
        >
            <?= csrf_field() ?>

            <label for="current_password">
                Mevcut şifre
            </label>

            <input
                id="current_password"
                type="password"
                name="current_password"
                autocomplete="current-password"
                required
            >

            <label for="password">
                Yeni şifre
            </label>

            <input
                id="password"
                type="password"
                name="password"
                autocomplete="new-password"
                minlength="<?= \App\Libraries\PasswordPolicy::MIN_LENGTH ?>"
                required
            >

            <label for="password_confirm">
                Yeni şifre tekrar
            </label>

            <input
                id="password_confirm"
                type="password"
                name="password_confirm"
                autocomplete="new-password"
                minlength="<?= \App\Libraries\PasswordPolicy::MIN_LENGTH ?>"
                required
            >

            <button
                class="button"
                type="submit"
            >
                Şifreyi Değiştir
            </button>
        </form>
    </section>


    <!-- VERİLERİ İNDİR -->
    <section class="settings-card">
        <h2>Verilerimi İndir</h2>

        <p>
            Notlarınız, görevleriniz ve günlük
            kayıtlarınız size özel bir dosya
            olarak hazırlanır.
        </p>

        <div class="export-links">

            <a
                class="button secondary"
                href="<?= site_url(
                    "profile/export/json"
                ) ?>"
            >
                JSON
            </a>

            <a
                class="button secondary"
                href="<?= site_url(
                    "profile/export/csv"
                ) ?>"
            >
                CSV
            </a>

            <a
                class="button secondary"
                href="<?= site_url(
                    "profile/export/txt"
                ) ?>"
            >
                Metin
            </a>

        </div>
    </section>


    <!-- HESABI SİL -->
    <section class="settings-card danger-zone">
        <h2>Hesabı Sil</h2>

        <p>
            Bu işlem notlarınızı, görevlerinizi
            ve günlüklerinizi kalıcı olarak siler. Gönderdiğiniz mesajlar,
            diğer katılımcıların konuşma geçmişinde “Silinmiş kullanıcı” adıyla korunur.
        </p>

        <form
            method="post"
            action="<?= site_url(
                "profile/delete"
            ) ?>"
            onsubmit="return confirm(
                'Hesabınız silinsin mi? Diğer kullanıcıların konuşma geçmişindeki mesajlarınız korunacaktır.'
            )"
        >
            <?= csrf_field() ?>

            <label for="delete_password">
                Şifreniz
            </label>

            <input
                id="delete_password"
                type="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button
                class="button danger"
                type="submit"
            >
                Hesabımı Sil
            </button>
        </form>
    </section>

</div>

<?= $this->endSection() ?>
