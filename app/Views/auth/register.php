<!DOCTYPE html>
<html lang="tr">
<head>
    <script>(()=>{let t=localStorage.getItem('project-redemption-theme');if(!localStorage.getItem('project-redemption-theme-default-v2')){if(!t||t==='light'){t='system';localStorage.setItem('project-redemption-theme',t)}localStorage.setItem('project-redemption-theme-default-v2','1')}if(t==='dark'||((t||'system')==='system'&&matchMedia('(prefers-color-scheme:dark)').matches))document.documentElement.style.colorScheme='dark'})();</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol | Project Redemption</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .auth-card { width: 100%; max-width: 460px; padding: 32px; border-radius: 16px; background: #fff; box-shadow: 0 12px 32px rgba(17, 24, 39, .1); }
        h1 { margin: 0 0 8px; }
        .subtitle { margin: 0 0 24px; color: #6b7280; }
        label { display: block; margin: 16px 0 6px; font-weight: 600; }
        input { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font: inherit; }
        button { width: 100%; margin-top: 22px; padding: 12px; border: 0; border-radius: 8px; background: #111827; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .alert { padding: 12px; margin: 16px 0; border-radius: 8px; background: #fee2e2; color: #991b1b; }
        .footer { margin: 22px 0 0; text-align: center; color: #6b7280; }
        a { color: #2563eb; }
        .site-footer { margin-top: 24px; color: #6b7280; text-align: center; font-size: 14px; }
        .site-footer .heart { color: #dc2626; }
        html[style*="dark"] body{background:#0f172a;color:#e5e7eb}html[style*="dark"] .auth-card{background:#1e293b}html[style*="dark"] input{background:#0f172a;border-color:#475569;color:#e5e7eb}
    </style>
</head>
<body>
<main class="auth-card">
    <h1>Kayıt Ol</h1>
    <p class="subtitle">Notlarınızı güvenle saklamak için hesabınızı oluşturun.</p>

    <?php if ($errors = session()->getFlashdata('errors')): ?>
        <div class="alert">
            <?php foreach ($errors as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('register') ?>">
        <?= csrf_field() ?>
        <label for="username">Kullanıcı adı</label>
        <input id="username" type="text" name="username" value="<?= esc(old('username')) ?>" autocomplete="username" required>

        <label for="email">E-posta</label>
        <input id="email" type="email" name="email" value="<?= esc(old('email')) ?>" autocomplete="email" required>

        <label for="password">Şifre</label>
        <input id="password" type="password" name="password" minlength="6" autocomplete="new-password" required>

        <label for="password_confirm">Şifre tekrar</label>
        <input id="password_confirm" type="password" name="password_confirm" minlength="6" autocomplete="new-password" required>

        <button type="submit">Hesap Oluştur</button>
    </form>

    <p class="footer">Zaten hesabınız var mı? <a href="<?= site_url('login') ?>">Giriş yapın</a></p>
</main>
<footer class="site-footer">Made with <span class="heart" aria-label="love">♥</span> by Halide.</footer>
</body>
</html>
