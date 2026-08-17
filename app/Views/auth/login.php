<!DOCTYPE html>
<html lang="tr">

<head>

    <script>
        (() => {
            let t = localStorage.getItem('project-redemption-theme');

            if (!localStorage.getItem('project-redemption-theme-default-v2')) {
                if (!t || t === 'light') {
                    t = 'system';
                    localStorage.setItem(
                        'project-redemption-theme',
                        t
                    );
                }

                localStorage.setItem(
                    'project-redemption-theme-default-v2',
                    '1'
                );
            }

            if (
                t === 'dark' ||
                (
                    (t || 'system') === 'system' &&
                    matchMedia(
                        '(prefers-color-scheme: dark)'
                    ).matches
                )
            ) {
                document.documentElement.style.colorScheme = 'dark';
            }
            (() => {
                const resendButton =
                    document.querySelector(
                        '[data-resend-countdown]'
                    );

                if (!resendButton) return;

                let seconds = Number(
                    resendButton.dataset
                    .resendCountdown || 0
                );

                if (seconds <= 0) return;

                const tick = () => {
                    if (seconds <= 0) {
                        resendButton.disabled = false;

                        resendButton.textContent =
                            'Yeni Kod Gönder';

                        return;
                    }

                    resendButton.disabled = true;

                    resendButton.textContent =
                        `Yeni Kod Gönder (${seconds} sn)`;

                    seconds -= 1;

                    window.setTimeout(
                        tick,
                        1000
                    );
                };

                tick();
            })();
        })();
    </script>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Giriş Yap | Project Redemption
    </title>


    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            padding: 24px;

            font-family: Arial, sans-serif;

            background: #f3f4f6;
            color: #111827;
        }


        .auth-card {
            width: 100%;
            max-width: 420px;

            padding: 32px;

            border-radius: 16px;

            background: #fff;

            box-shadow:
                0 12px 32px rgba(17, 24, 39, .1);
        }


        h1 {
            margin: 0 0 8px;
        }


        .subtitle {
            margin: 0 0 24px;
            color: #6b7280;
        }


        label {
            display: block;

            margin: 16px 0 6px;

            font-weight: 600;
        }


        input {
            width: 100%;

            padding: 12px;

            border: 1px solid #d1d5db;
            border-radius: 8px;

            font: inherit;
        }


        .password-wrap {
            position: relative;
        }


        .password-wrap input {
            padding-right: 72px;
        }


        .password-toggle {
            position: absolute;

            top: 50%;
            right: 8px;

            width: auto;

            margin: 0;

            padding: 7px 9px;

            transform: translateY(-50%);

            border: 0;
            border-radius: 6px;

            background: transparent;

            color: #2563eb;

            font-size: 13px;

            cursor: pointer;
        }


        .password-toggle:hover {
            background: #eff6ff;
        }


        .password-toggle:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 1px;
        }

        .verification-button:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .verification-code {
            text-align: center;
            letter-spacing: .45em;
            font-size: 24px;
            font-weight: 700;
        }

        .verification-meta {
            font-size: 13px;
        }

        button {
            width: 100%;

            margin-top: 22px;

            padding: 12px;

            border: 0;
            border-radius: 8px;

            background: #111827;
            color: #fff;

            font: inherit;
            font-weight: 700;

            cursor: pointer;
        }


        .alert {
            padding: 12px 14px;

            margin: 16px 0;

            border-radius: 8px;

            line-height: 1.5;
        }


        .alert p {
            margin: 8px 0;
        }


        .alert p:first-child {
            margin-top: 0;
        }


        .alert p:last-child {
            margin-bottom: 0;
        }


        .error {
            background: #fee2e2;
            color: #991b1b;
        }


        .success {
            background: #dcfce7;
            color: #166534;
        }


        .warning {
            background: #fef3c7;
            color: #92400e;
        }


        .verification-button {
            margin-top: 12px;

            background: #92400e;
        }


        .forgot-password {
            margin-top: 10px;

            text-align: right;

            font-size: 14px;
        }


        .footer {
            margin: 22px 0 0;

            text-align: center;

            color: #6b7280;
        }


        a {
            color: #2563eb;
        }


        .site-footer {
            margin-top: 24px;

            color: #6b7280;

            text-align: center;

            font-size: 14px;
        }


        .site-footer .heart {
            color: #dc2626;
        }


        html[style*="dark"] body {
            background: #0f172a;
            color: #e5e7eb;
        }


        html[style*="dark"] .auth-card {
            background: #1e293b;
        }


        html[style*="dark"] input {
            background: #0f172a;

            border-color: #475569;

            color: #e5e7eb;
        }


        html[style*="dark"] .password-toggle:hover {
            background: #334155;
        }


        html[style*="dark"] .warning {
            background: #422006;
            color: #fde68a;
        }
    </style>

</head>


<body>


    <main class="auth-card">


        <h1>
            Giriş Yap
        </h1>


        <p class="subtitle">
            Notlarınıza erişmek için hesabınıza giriş yapın.
        </p>


        <!-- errors FLASHDATA -->
        <?php if ($errors = session()->getFlashdata("errors")): ?>

        <div class="alert error">

            <?php foreach ((array) $errors as $error): ?>

            <div>
                <?= esc($error) ?>
            </div>

            <?php endforeach; ?>

        </div>

        <?php endif; ?>


        <!-- error FLASHDATA -->
        <?php if ($error = session()->getFlashdata("error")): ?>

        <div class="alert error">
            <?= esc($error) ?>
        </div>

        <?php endif; ?>


        <!-- SUCCESS -->
        <?php if ($success = session()->getFlashdata("success")): ?>

        <div class="alert success">
            <?= esc($success) ?>
        </div>

        <?php endif; ?>


        <!-- E-POSTA DOĞRULAMA UYARISI -->
        <?php if ($verification = session()->getFlashdata("verification_pending")): ?>

        <div class="alert warning">

            <strong>
                E-posta doğrulaması gerekli.
            </strong>

            <p>
                <strong>
                    <?= esc($verification["email"]) ?>
                </strong>
                adresine gönderilen 6 haneli
                doğrulama kodunu girin.
            </p>

            <?php if (!empty($verification["can_verify"])): ?>

            <form method="post" action="<?= site_url("verify-email") ?>" autocomplete="off">

                <?= csrf_field() ?>

                <input type="hidden" name="email" value="<?= esc($verification["email"]) ?>">

                <label for="verification-code">
                    Doğrulama kodu
                </label>

                <input class="verification-code" id="verification-code" name="code" type="text"
                    inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                    autocomplete="one-time-code" placeholder="000000" required>

                <button class="verification-button" type="submit">
                    Kodu Doğrula
                </button>

            </form>

            <p class="verification-meta">
                Kod 10 dakika geçerlidir.
                Kalan deneme hakkı:
                <?= (int) ($verification["attempts_remaining"] ?? 0) ?>.
            </p>

            <?php else: ?>

            <p>
                Mevcut doğrulama kodunuz
                kullanılamıyor veya süresi
                dolmuş. Yeni bir kod isteyin.
            </p>

            <?php endif; ?>


            <form method="post" action="<?= site_url("resend-verification") ?>">

                <?= csrf_field() ?>

                <input type="hidden" name="email" value="<?= esc($verification["email"]) ?>">

                <?php $resendWait = (int) ($verification["resend_after_seconds"] ?? 0); ?>

                <button class="verification-button" type="submit"
                    data-resend-countdown="<?= $resendWait ?>"
                    <?= empty($verification["can_resend"]) ? "disabled" : "" ?>>

                    <?= empty($verification["can_resend"])
                        ? "Yeni Kod Gönder (" . $resendWait . " sn)"
                        : "Yeni Kod Gönder" ?>

                </button>

            </form>

            <p class="verification-meta">
                E-posta gelmediyse spam /
                gereksiz klasörünü de kontrol edin.
            </p>

        </div>

        <?php endif; ?>


        <!-- LOGIN FORM -->
        <form method="post" action="<?= site_url("login") ?>">


            <?= csrf_field() ?>


            <label for="email">
                E-posta
            </label>


            <input id="email" type="email" name="email" value="<?= esc(old("email")) ?>"
                autocomplete="username" autocapitalize="none" spellcheck="false" required>


            <label for="password">
                Şifre
            </label>


            <div class="password-wrap">


                <input id="password" type="password" name="password" autocomplete="current-password"
                    required>


                <button class="password-toggle" type="button" aria-controls="password"
                    aria-pressed="false">
                    Göster
                </button>


            </div>


            <!-- ŞİFREMİ UNUTTUM -->
            <div class="forgot-password">

                <a href="<?= site_url("forgot-password") ?>">
                    Şifremi unuttum?
                </a>

            </div>


            <button type="submit">
                Giriş Yap
            </button>


        </form>


        <p class="footer">

            Hesabınız yok mu?

            <a href="<?= site_url("register") ?>">
                Kayıt olun
            </a>

        </p>


    </main>


    <footer class="site-footer">

        Made with

        <span class="heart" aria-label="love">
            ♥
        </span>

        by Halide.

    </footer>


    <script>
        (() => {

            const password =
                document.getElementById('password');

            const toggle =
                document.querySelector(
                    '.password-toggle'
                );


            if (!password || !toggle) {
                return;
            }


            toggle.addEventListener(
                'click',
                () => {

                    const showPassword =
                        password.type === 'password';


                    password.type =
                        showPassword ?
                        'text' :
                        'password';


                    toggle.textContent =
                        showPassword ?
                        'Gizle' :
                        'Göster';


                    toggle.setAttribute(
                        'aria-pressed',
                        String(showPassword)
                    );


                    password.focus();


                    password.setSelectionRange(
                        password.value.length,
                        password.value.length
                    );

                }
            );

        })();
    </script>


    <?= view("partials/update_notifier", [
    "codeVersion" => (new \App\Libraries\CodeVersion())->current(),
]) ?>


</body>

</html>