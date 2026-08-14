<!DOCTYPE html>
<html lang="tr">
<head>
    <script>(()=>{let t=localStorage.getItem('project-redemption-theme');if(!localStorage.getItem('project-redemption-theme-default-v2')){if(!t||t==='light'){t='system';localStorage.setItem('project-redemption-theme',t)}localStorage.setItem('project-redemption-theme-default-v2','1')}if(t==='dark'||((t||'system')==='system'&&matchMedia('(prefers-color-scheme:dark)').matches))document.documentElement.style.colorScheme='dark'})();</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap | Project Redemption</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .auth-card { width: 100%; max-width: 420px; padding: 32px; border-radius: 16px; background: #fff; box-shadow: 0 12px 32px rgba(17, 24, 39, .1); }
        h1 { margin: 0 0 8px; }
        .subtitle { margin: 0 0 24px; color: #6b7280; }
        label { display: block; margin: 16px 0 6px; font-weight: 600; }
        input { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font: inherit; }
        .password-wrap { position: relative; }
        .password-wrap input { padding-right: 72px; }
        .password-toggle { position: absolute; top: 50%; right: 8px; width: auto; margin: 0; padding: 7px 9px; transform: translateY(-50%); border: 0; border-radius: 6px; background: transparent; color: #2563eb; font-size: 13px; }
        .password-toggle:hover { background: #eff6ff; }
        .password-toggle:focus-visible { outline: 2px solid #2563eb; outline-offset: 1px; }
        button { width: 100%; margin-top: 22px; padding: 12px; border: 0; border-radius: 8px; background: #111827; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .alert { padding: 12px; margin: 16px 0; border-radius: 8px; }
        .error { background: #fee2e2; color: #991b1b; }
        .success { background: #dcfce7; color: #166534; }
        .footer { margin: 22px 0 0; text-align: center; color: #6b7280; }
        a { color: #2563eb; }
        .site-footer { margin-top: 24px; color: #6b7280; text-align: center; font-size: 14px; }
        .site-footer .heart { color: #dc2626; }
        html[style*="dark"] body{background:#0f172a;color:#e5e7eb}html[style*="dark"] .auth-card{background:#1e293b}html[style*="dark"] input{background:#0f172a;border-color:#475569;color:#e5e7eb}html[style*="dark"] .password-toggle:hover{background:#334155}
    </style>
</head>
<body>
<main class="auth-card">
    <h1>Giriş Yap</h1>
    <p class="subtitle">Notlarınıza erişmek için hesabınıza giriş yapın.</p>
    



    <?php if (session()->has('verification_pending')): ?>

        <?php $verification = session('verification_pending'); ?>

        <div class="alert alert-warning">

            <strong>E-posta doğrulaması gerekli.</strong>

            <?php if (! $verification['can_resend']): ?>

                <p>
                    Hesabınıza giriş yapmadan önce e-posta adresinizi
                    doğrulamanız gerekiyor.
                </p>

                <p>
                    Doğrulama bağlantısı e-posta adresinize gönderildi.
                    Lütfen gelen kutunuzu ve
                    <strong>spam / gereksiz</strong>
                    klasörünüzü kontrol edin.
                </p>

            <?php else: ?>

                <p>
                    E-posta doğrulama bağlantınızın süresi dolmuş.
                </p>

                <p>
                    Yeni bir doğrulama bağlantısı gönderebilirsiniz.
                </p>

                <form
                    method="post"
                    action="<?= site_url('resend-verification') ?>"
                >
                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="email"
                        value="<?= esc($verification['email']) ?>"
                    >

                    <button type="submit" class="btn">
                        Doğrulama Mailini Tekrar Gönder
                    </button>
                </form>

            <?php endif; ?>

        </div>

    <?php endif; ?>




    <?php if ($errors = session()->getFlashdata('errors')): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success = session()->getFlashdata('success')): ?>
        <div class="alert success"><?= esc($success) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('login') ?>">
        <?= csrf_field() ?>

        <label for="email">E-posta</label>
        <input
            id="email"
            type="email"
            name="email"
            value="<?= esc(old('email')) ?>"
            autocomplete="username"
            autocapitalize="none"
            spellcheck="false"
            required
        >

        <label for="password">Şifre</label>

        <div class="password-wrap">
            <input
                id="password"
                type="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button
                class="password-toggle"
                type="button"
                aria-controls="password"
                aria-pressed="false"
            >
                Göster
            </button>
        </div>

        <button type="submit">
            Giriş Yap
        </button>
    </form>

    <p class="footer">Hesabınız yok mu? <a href="<?= site_url('register') ?>">Kayıt olun</a></p>
</main>
<footer class="site-footer">Made with <span class="heart" aria-label="love">♥</span> by Halide.</footer>
<script>
    (() => {
        const password = document.getElementById('password');
        const toggle = document.querySelector('.password-toggle');
        if (!password || !toggle) return;

        toggle.addEventListener('click', () => {
            const showPassword = password.type === 'password';
            password.type = showPassword ? 'text' : 'password';
            toggle.textContent = showPassword ? 'Gizle' : 'Göster';
            toggle.setAttribute('aria-pressed', String(showPassword));
            password.focus();
            password.setSelectionRange(password.value.length, password.value.length);
        });
    })();
</script>
<?= view('partials/update_notifier', ['codeVersion' => (new \App\Libraries\CodeVersion())->current()]) ?>
</body>
</html>
