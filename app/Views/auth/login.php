<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap | Project Redemption</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .auth-card { width: 100%; max-width: 420px; padding: 32px; border-radius: 16px; background: #fff; box-shadow: 0 12px 32px rgba(17, 24, 39, .1); }
        h1 { margin: 0 0 8px; }
        .subtitle { margin: 0 0 24px; color: #6b7280; }
        label { display: block; margin: 16px 0 6px; font-weight: 600; }
        input { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font: inherit; }
        button { width: 100%; margin-top: 22px; padding: 12px; border: 0; border-radius: 8px; background: #111827; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .alert { padding: 12px; margin: 16px 0; border-radius: 8px; }
        .error { background: #fee2e2; color: #991b1b; }
        .success { background: #dcfce7; color: #166534; }
        .footer { margin: 22px 0 0; text-align: center; color: #6b7280; }
        a { color: #2563eb; }
    </style>
</head>
<body>
<main class="auth-card">
    <h1>Giriş Yap</h1>
    <p class="subtitle">Notlarınıza erişmek için hesabınıza giriş yapın.</p>

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
        <input id="email" type="email" name="email" value="<?= esc(old('email')) ?>" autocomplete="email" required>

        <label for="password">Şifre</label>
        <input id="password" type="password" name="password" autocomplete="current-password" required>

        <button type="submit">Giriş Yap</button>
    </form>

    <p class="footer">Hesabınız yok mu? <a href="<?= site_url('register') ?>">Kayıt olun</a></p>
</main>
</body>
</html>
