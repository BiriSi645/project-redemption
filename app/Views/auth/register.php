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
    <title>Kayıt Ol | Project Redemption</title>
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
            max-width: 460px;
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
            background: #fee2e2;
            color: #991b1b;
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
        <h1>Kayıt Ol</h1>
        <p class="subtitle">Notlarınızı güvenle saklamak için hesabınızı oluşturun.</p>

        <?php if ($errors = session()->getFlashdata("errors")): ?>
        <div class="alert">
            <?php foreach ($errors as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= site_url("register") ?>" autocomplete="off">
            <?= csrf_field() ?>
            <label for="username">Kullanıcı adı</label>
            <input id="username" type="text" name="username" value="<?= esc(
            old("username"),
        ) ?>" autocomplete="username" required>

            <label for="email">E-posta</label>
            <input id="email" type="email" name="email" value="<?= esc(
            old("email"),
        ) ?>" autocomplete="email" required>

            <label for="password">Şifre</label>
            <input id="password" type="password" name="password" minlength="6"
                autocomplete="new-password" required>

            <label for="password_confirm">Şifre tekrar</label>
            <input id="password_confirm" type="password" name="password_confirm" minlength="6"
                autocomplete="new-password" required>

            <button type="submit">Hesap Oluştur</button>
        </form>

        <p class="footer">Zaten hesabınız var mı? <a href="<?= site_url("login") ?>">Giriş yapın</a>
        </p>
    </main>
    <footer class="site-footer">Made with <span class="heart" aria-label="love">♥</span> by Halide.
    </footer>
    <?= view("partials/update_notifier", [
    "codeVersion" => (new \App\Libraries\CodeVersion())->current(),
]) ?>
</body>

</html>

<script>
    (function() {
        if (!new URLSearchParams(location.search).has('debugAutofill')) return;
        const p = document.getElementById('password') || document.querySelector(
            'input[type=password]');
        console.log('debugAutofill(register): started', {
            passwordFound: !!p,
            location: location.href
        });
        if (!p) return;
        console.log('debugAutofill(register): initial', {
            value: p.value,
            readOnly: p.readOnly,
            disabled: p.disabled
        });
        ['keydown', 'keypress', 'keyup', 'input', 'focus', 'blur'].forEach(evt => p
            .addEventListener(evt, e => {
                console.log('debugAutofill(register) event', evt, {
                    key: e.key,
                    value: p.value
                });
            }));
        (function inspectAncestors(el) {
            let cur = el;
            while (cur) {
                try {
                    const s = getComputedStyle(cur);
                    console.log('debugAutofill(register) ancestor', cur.tagName, {
                        pointerEvents: s.pointerEvents,
                        opacity: s.opacity
                    });
                } catch (e) {}
                cur = cur.parentElement;
            }
        })(p);
    })();
</script>

<script>
    // Autofill visual-fix (opt-in): enable with ?autofillFix=1
    (function() {
        let p = document.getElementById('password') || document.querySelector(
            'input[type=password]');
        if (!p) return;
        const shouldEnable = new URLSearchParams(location.search).has('autofillFix') || !!p
            .value;
        if (!shouldEnable) return;
        let lastVal = p.value;
        let lastKey = null;
        let composing = false;
        let pending = null;

        function attachHandlers(input) {
            input.addEventListener('keydown', onKeydown);
            input.addEventListener('keyup', onKeyup);
            input.addEventListener('beforeinput', onBeforeInput);
            input.addEventListener('input', onInput);
            input.addEventListener('paste', onPaste);
            input.addEventListener('compositionstart', () => composing = true);
            input.addEventListener('compositionend', () => composing = false);
        }

        function onKeydown(e) {
            lastVal = p.value;
            lastKey = e.key;
        }

        function replaceWithClone(input, newValue, caretPos) {
            try {
                const clone = input.cloneNode(true);
                clone.value = newValue;
                input.parentNode.replaceChild(clone, input);
                p = clone;
                attachHandlers(p);
                try {
                    p.focus();
                } catch (e) {}
                try {
                    p.setSelectionRange(caretPos, caretPos);
                } catch (e) {}
                p.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                console.log('autofillFix(register): replacedWithClone', {
                    newValue: newValue
                });
            } catch (err) {
                console.error('autofillFix(register): replace error', err);
            }
        }

        function onBeforeInput(e) {
            if (composing) return;
            try {
                const old = p.value;
                const data = e.data;
                const start = p.selectionStart;
                const end = p.selectionEnd;
                const inputType = e.inputType;
                pending = {
                    oldValue: old,
                    data: data,
                    inputType: inputType,
                    start: start,
                    end: end
                };
                console.log('autofillFix(register): beforeinput', pending);

                if (inputType && (inputType.startsWith('insert') || inputType ===
                        'deleteContentBackward' || inputType === 'deleteContentForward')) {
                    try {
                        e.preventDefault();
                    } catch (err) {}
                    if (inputType.startsWith('insert')) {
                        const expected = old.slice(0, start) + (data || '') + old.slice(end);
                        p.value = expected;
                        const newPos = start + (data ? data.length : 0);
                        try {
                            p.setSelectionRange(newPos, newPos);
                        } catch (err) {}
                        p.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                        console.log('autofillFix(register): beforeinput applied manually', {
                            expected
                        });
                    } else if (inputType === 'deleteContentBackward') {
                        const newPos = Math.max(start - 1, 0);
                        const expected = old.slice(0, newPos) + old.slice(end);
                        p.value = expected;
                        try {
                            p.setSelectionRange(newPos, newPos);
                        } catch (err) {}
                        p.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                        console.log('autofillFix(register): delete backward applied manually', {
                            expected
                        });
                    } else if (inputType === 'deleteContentForward') {
                        const expected = old.slice(0, start) + old.slice(Math.min(end + 1, old
                            .length));
                        const newPos = start;
                        p.value = expected;
                        try {
                            p.setSelectionRange(newPos, newPos);
                        } catch (err) {}
                        p.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                        console.log('autofillFix(register): delete forward applied manually', {
                            expected
                        });
                    }
                    pending = null;
                }
            } catch (err) {
                pending = null;
            }
        }

        function onInput(e) {
            if (composing) {
                pending = null;
                return;
            }
            if (!pending) return;
            try {
                const oldVal = pending.oldValue || '';
                const data = pending.data || '';
                const start = typeof pending.start === 'number' ? pending.start : oldVal.length;
                const end = typeof pending.end === 'number' ? pending.end : start;
                const expected = oldVal.slice(0, start) + data + oldVal.slice(end);

                if (p.value === oldVal) {
                    // browser did not apply the edit visually — try to set it
                    p.value = expected;
                    const newPos = start + data.length;
                    try {
                        p.setSelectionRange(newPos, newPos);
                    } catch (e) {}
                    p.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    console.log('autofillFix(register): repaired by setting value', {
                        expected
                    });
                    setTimeout(() => {
                        if (p && p.value === oldVal) {
                            replaceWithClone(p, expected, newPos);
                        }
                    }, 30);
                } else if (p.value !== expected) {
                    // mismatch: log for debugging
                    console.log('autofillFix(register): input mismatch', {
                        value: p.value,
                        expected
                    });
                }
            } catch (err) {
                console.error('autofillFix(register): input handler error', err);
            }
            pending = null;
        }

        function onPaste(e) {
            if (composing) return;
            try {
                const paste = (e.clipboardData && e.clipboardData.getData) ? e.clipboardData
                    .getData('text') : null;
                if (!paste) return;
                const start = p.selectionStart || p.value.length;
                const end = p.selectionEnd || start;
                const expected = p.value.slice(0, start) + paste + p.value.slice(end);
                setTimeout(() => {
                    if (p.value === p.value) {
                        p.value = expected;
                        try {
                            p.setSelectionRange(start + paste.length, start + paste
                                .length);
                        } catch (e) {}
                        p.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                        console.log('autofillFix(register): paste applied', {
                            expected
                        });
                    }
                }, 25);
            } catch (err) {
                console.error('autofillFix(register): paste error', err);
            }
        }

        attachHandlers(p);
    })();
</script>

<script>
    // Ensure autofilled new-password fields are editable
    (function() {
        function ensureEditable(input) {
            if (!input || !input.value) return;
            // Don't change type while user is actively typing
            if (document.activeElement === input) return;
            try {
                input.setSelectionRange(input.value.length, input.value.length);
                input.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                return;
            } catch (e) {}
            // don't toggle input.type to avoid stealing focus while the user types
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input[type="password"]').forEach(input => {
                if (input.value) ensureEditable(input);
                input.addEventListener('click', () => ensureEditable(input));
                input.addEventListener('focus', () => {
                    try {
                        input.removeAttribute('readonly');
                    } catch (e) {}
                    try {
                        input.disabled = false;
                    } catch (e) {}
                    try {
                        input.setSelectionRange(input.value.length,
                            input.value.length);
                    } catch (e) {}
                    try {
                        input.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                    } catch (e) {}
                });
            });

            // Poll briefly to catch autofill that happens slightly after load
            const initial = new Map();
            document.querySelectorAll('input[type="password"]').forEach(i => initial
                .set(i, i.value));
            let tries = 0;
            const poll = setInterval(() => {
                tries++;
                document.querySelectorAll('input[type="password"]').forEach(
                i => {
                    if (i.value && initial.get(i) !== i.value) {
                        ensureEditable(i);
                        initial.set(i, i.value);
                        clearInterval(poll);
                    }
                });
                if (tries > 20) clearInterval(poll);
            }, 100);
        });
    })();
</script>