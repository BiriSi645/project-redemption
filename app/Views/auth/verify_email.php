<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-posta Doğrulama | Project Redemption</title>
    <script>
        (() => {
            const theme = localStorage.getItem('project-redemption-theme') || 'system';
            if (theme === 'dark' || (theme === 'system' && matchMedia(
                    '(prefers-color-scheme: dark)').matches)) document.documentElement.style
                .colorScheme = 'dark'
        })()
    </script>
    <style>
        * {
            box-sizing: border-box
        }

        body {
            display: grid;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            place-items: center;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, sans-serif
        }

        .verify-card {
            width: min(440px, 100%);
            padding: 32px;
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 12px 35px rgba(17, 24, 39, .12)
        }

        .verify-icon {
            display: grid;
            width: 58px;
            height: 58px;
            margin-bottom: 18px;
            place-items: center;
            border-radius: 50%;
            background: #fef3c7;
            font-size: 27px
        }

        .verify-card h1 {
            margin: 0 0 9px
        }

        .verify-copy {
            margin: 0 0 22px;
            color: #6b7280;
            line-height: 1.6
        }

        .verify-copy strong {
            color: #374151
        }

        .alert {
            padding: 12px 14px;
            margin: 0 0 16px;
            border-radius: 9px;
            line-height: 1.5
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b
        }

        .alert.success {
            background: #dcfce7;
            color: #166534
        }

        label {
            display: block;
            margin: 14px 0 7px;
            font-weight: 700
        }

        .verification-code {
            width: 100%;
            padding: 13px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            font: 700 25px Arial, sans-serif;
            letter-spacing: .42em;
            text-align: center
        }

        .button {
            display: block;
            width: 100%;
            padding: 12px;
            margin-top: 14px;
            border: 0;
            border-radius: 9px;
            background: #111827;
            color: #fff;
            font: 700 15px Arial, sans-serif;
            text-align: center;
            text-decoration: none;
            cursor: pointer
        }

        .button.secondary {
            background: #e5e7eb;
            color: #111827
        }

        .button:disabled {
            opacity: .55;
            cursor: not-allowed
        }

        .verify-meta {
            margin: 12px 0 0;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5
        }

        .verify-divider {
            height: 1px;
            margin: 22px 0;
            background: #e5e7eb
        }

        .back-link {
            display: block;
            margin-top: 18px;
            color: #2563eb;
            text-align: center
        }

        html[style*="dark"] body {
            background: #0f172a;
            color: #e5e7eb
        }

        html[style*="dark"] .verify-card {
            background: #1e293b
        }

        html[style*="dark"] .verify-copy,
        html[style*="dark"] .verify-meta {
            color: #94a3b8
        }

        html[style*="dark"] .verify-copy strong {
            color: #e2e8f0
        }

        html[style*="dark"] .verification-code {
            border-color: #475569;
            background: #0f172a;
            color: #fff
        }

        html[style*="dark"] .button.secondary {
            background: #334155;
            color: #e5e7eb
        }

        html[style*="dark"] .verify-divider {
            background: #334155
        }
    </style>
</head>

<body>
    <main class="verify-card">
        <div class="verify-icon">✉</div>
        <h1>E-postanı doğrula</h1>
        <p class="verify-copy"><strong><?= esc(
        $verification["email"],
    ) ?></strong> adresine gönderilen 6 haneli kodu gir.</p>
        <?php if ($error = session()->getFlashdata("error")): ?><div class="alert error"><?= esc(
    $error,
) ?></div><?php endif; ?>
        <?php if ($success = session()->getFlashdata("success")): ?><div class="alert success"><?= esc(
    $success,
) ?></div><?php endif; ?>
        <?php if (!empty($verification["can_verify"])): ?>
        <form method="post" action="<?= site_url("verify-email") ?>" autocomplete="off">
            <?= csrf_field() ?><input type="hidden" name="email" value="<?= esc(
    $verification["email"],
    "attr",
) ?>">
            <label for="verification-code">Doğrulama kodu</label>
            <input class="verification-code" id="verification-code" name="code" type="text"
                inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                placeholder="000000" required autofocus>
            <button class="button" type="submit">Kodu doğrula</button>
        </form>
        <p class="verify-meta">Kod 10 dakika geçerlidir. Kalan deneme hakkı: <?= (int) ($verification[
            "attempts_remaining"
        ] ?? 0) ?>.</p>
        <?php else: ?><div class="alert error">Kod kullanılamıyor veya süresi dolmuş. Yeni bir kod
            iste.</div><?php endif; ?>
        <div class="verify-divider"></div>
        <form method="post" action="<?= site_url("resend-verification") ?>">
            <?= csrf_field() ?><input type="hidden" name="email" value="<?= esc(
    $verification["email"],
    "attr",
) ?>">
            <?php $wait = (int) ($verification["resend_after_seconds"] ?? 0); ?>
            <button class="button secondary" type="submit" data-resend-countdown="<?= $wait ?>" <?= empty(
    $verification["can_resend"]
)
    ? "disabled"
    : "" ?>><?= empty($verification["can_resend"])
    ? "Yeni kod gönder (" . $wait . " sn)"
    : "Yeni kod gönder" ?></button>
        </form>
        <p class="verify-meta">E-posta gelmediyse spam veya gereksiz klasörünü de kontrol et.</p>
        <a class="back-link" href="<?= site_url("login") ?>">Giriş sayfasına dön</a>
    </main>
    <script>
        (() => {
            const button = document.querySelector('[data-resend-countdown]');
            if (!button) return;
            let seconds = Number(button.dataset.resendCountdown) || 0;
            if (seconds <= 0) return;
            const tick = () => {
                if (seconds <= 0) {
                    button.disabled = false;
                    button.textContent = 'Yeni kod gönder';
                    return
                }
                button.disabled = true;
                button.textContent = `Yeni kod gönder (${seconds} sn)`;
                seconds--;
                setTimeout(tick, 1000)
            };
            tick()
        })()
    </script>
</body>

</html>