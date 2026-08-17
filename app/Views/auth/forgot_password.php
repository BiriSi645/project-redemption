<!DOCTYPE html>
<html lang="tr">

<head>
    <script>
        (() => {
            let t = localStorage.getItem('project-redemption-theme');
            if (!localStorage.getItem('project-redemption-theme-default-v2')) {
                if (!t || t === 'light') {
                    t = 'system';
                    localStorage.setItem('project-redemption-theme', t)
                }
                localStorage.setItem('project-redemption-theme-default-v2', '1')
            }
            if (t === 'dark' || ((t || 'system') === 'system' && matchMedia(
                    '(prefers-color-scheme:dark)').matches)) document.documentElement.style
                .colorScheme = 'dark'
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum | Project Redemption</title>
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
            box-shadow: 0 12px 32px rgba(17, 24, 39, .1);
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
            padding: 12px;
            margin: 16px 0;
            border-radius: 8px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .success {
            background: #dcfce7;
            color: #166534;
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
            color: #e5e7eb
        }

        html[style*="dark"] .auth-card {
            background: #1e293b
        }

        html[style*="dark"] input {
            background: #0f172a;
            border-color: #475569;
            color: #e5e7eb
        }
    </style>
</head>

<body>

    <main class="auth-card">

        <h1>Şifremi Unuttum</h1>

        <p>
            Hesabınıza bağlı e-posta adresini girin.
            Şifre sıfırlama bağlantısını e-posta adresinize göndereceğiz.
        </p>

        <?php if ($error = session()->getFlashdata("error")): ?>
        <div class="alert error">
            <?= esc($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success = session()->getFlashdata("success")): ?>
        <div class="alert success">
            <?= esc($success) ?>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= site_url("forgot-password") ?>">
            <?= csrf_field() ?>

            <label for="email">E-posta</label>
            <input id="email" type="email" name="email" value="<?= esc(old("email")) ?>" required>

            <button type="submit">Şifre Sıfırlama Bağlantısı Gönder</button>
        </form>

        <p style="text-align:center;margin-top:16px;"><a href="<?= site_url(
        "login",
    ) ?>">Giriş ekranına dön</a></p>

    </main>

    <footer class="site-footer">Made with <span class="heart" aria-label="love">♥</span> by Halide.
    </footer>

    <?= view("partials/update_notifier", [
    "codeVersion" => (new \App\Libraries\CodeVersion())->current(),
]) ?>

</body>

</html>