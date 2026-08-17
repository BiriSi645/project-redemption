<?= $this->extend("layouts/main") ?>
<?= $this->section("content") ?>
<section class="public-profile">
    <?php if (
        $errors = session()->getFlashdata("errors")
    ): ?><div class="alert error"><?php foreach ($errors as $error): ?><div><?= esc(
    $error,
) ?></div><?php endforeach; ?></div><?php endif; ?>
    <header class="profile-hero">
        <span class="profile-avatar"><?= esc(mb_substr($profileUser["username"], 0, 1)) ?></span>
        <div class="profile-summary">
            <div id="profile-display">
                <h1><?= esc($profileUser["username"]) ?></h1>
                <p><?=
                ($profileUser["role"] === "admin" ? "Admin · " : "") .
                date("d.m.Y", strtotime($profileUser["created_at"]))
                ?> tarihinden beri üye</p>
                <div class="profile-level">
                    <div class="profile-level-head"><strong>Seviye <?= $levelSummary[
                    "level"
                ] ?></strong><span><?= $levelSummary[
    "xp"
] ?> XP</span></div>
                    <div class="profile-level-track"><i style="width:<?= $levelSummary[
     "progress"
 ] ?>%"></i></div><small>Sonraki seviyeye <?= max(
    0,
    $levelSummary["next"] - $levelSummary["xp"],
) ?> XP kaldı</small>
                </div>
                <?php if (!empty($profileUser["bio"])): ?><div class="profile-bio"><?= esc(
    $profileUser["bio"],
) ?></div><?php elseif (
                    $isOwnProfile
                ): ?><div class="profile-bio profile-bio-empty">Henüz biyografi eklemediniz.</div>
                <?php endif; ?>
                <?php if (
                    !empty($profileUser["profile_url"])
                ): ?><a class="profile-link" href="<?= esc(
    $profileUser["profile_url"],
    "attr",
) ?>" target="_blank" rel="noopener noreferrer">🔗 <?= esc(
    parse_url($profileUser["profile_url"], PHP_URL_HOST) ?: $profileUser["profile_url"],
) ?></a><?php elseif (
                    $isOwnProfile
                ): ?><div class="profile-link-empty">Henüz profil bağlantısı eklemediniz.</div>
                <?php endif; ?>
                <?php if (
                    $isOwnProfile
                ): ?><button class="button secondary profile-edit-button" id="profile-edit-button"
                    type="button">Profili Düzenle</button><?php endif; ?>
            </div>

            <?php if ($isOwnProfile): ?>
            <form class="profile-edit-form<?= $errors
                    ? " open"
                    : "" ?>" id="profile-edit-form" method="post" action="<?= site_url(
    "users/profile",
) ?>">
                <?= csrf_field() ?>
                <label for="profile-username">Kullanıcı adı</label>
                <input id="profile-username" name="username" value="<?= esc(
                        old("username", $profileUser["username"]),
                    ) ?>" minlength="3" maxlength="100" required>
                <label for="profile-bio">Biyografi</label>
                <textarea id="profile-bio" name="bio" maxlength="300" rows="4"
                    placeholder="Kendinizden kısaca bahsedin..."><?= esc(
                        old("bio", $profileUser["bio"] ?? ""),
                    ) ?></textarea>
                <small class="bio-counter"><span id="bio-count">0</span>/300</small>
                <label for="profile-url">Profil bağlantısı</label>
                <input id="profile-url" type="text" name="profile_url" value="<?= esc(
                        old("profile_url", $profileUser["profile_url"] ?? ""),
                    ) ?>" maxlength="500" inputmode="url"
                    placeholder="ornek.com veya https://ornek.com">
                <small class="profile-url-help">Web siteniz, portföyünüz veya sosyal medya
                    profiliniz olabilir.</small>
                <div class="profile-form-actions"><button class="button"
                        type="submit">Kaydet</button><button class="button secondary"
                        id="profile-edit-cancel" type="button">İptal</button></div>
            </form>
            <?php endif; ?>
        </div>
    </header>
    <?php if (!$isOwnProfile): ?>
    <div style="margin:-12px 0 24px;text-align:right">
        <form class="profile-message-action" style="display:inline" method="post" action="<?= site_url(
                "messages/start/" . $profileUser["id"],
            ) ?>"><?= csrf_field() ?><button class="button" type="submit">✉ Mesaj Gönder</button>
        </form>
        <?php if (!empty($blockedByMe)): ?>
        <form style="display:inline" method="post" action="<?= site_url(
                    "messages/unblock/" . $profileUser["id"],
                ) ?>"><?= csrf_field() ?><button class="button secondary" type="submit">Engeli
                Kaldır</button></form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <h2>Public notlar</h2>
    <?php if (
        $notes === []
    ): ?><p class="empty-profile">Bu kullanıcının henüz public notu yok.</p><?php endif; ?>
    <div class="profile-notes">
        <?php foreach ($notes as $note): ?><a href="<?= site_url(
    "notes/" . $note["id"],
) ?>"><strong><?= esc($note["title"]) ?></strong><span><?= esc(
    mb_strimwidth($note["content"], 0, 150, "…"),
) ?></span><small><?= date(
    "d.m.Y · H:i",
    strtotime($note["created_at"]),
) ?></small></a><?php endforeach; ?>
    </div>
    <?= $pager->links("profile_notes") ?>
</section>
<style>
    .public-profile {
        max-width: 780px;
        margin: auto
    }

    .profile-hero {
        display: flex;
        align-items: flex-start;
        gap: 18px;
        padding: 22px;
        margin-bottom: 28px;
        border: 1px solid #e5e7eb;
        border-radius: 16px
    }

    .profile-avatar {
        display: grid;
        flex: 0 0 64px;
        width: 64px;
        height: 64px;
        place-items: center;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563eb, #7c3aed);
        color: #fff;
        font-size: 28px;
        font-weight: 800
    }

    .profile-summary {
        flex: 1;
        min-width: 0
    }

    .profile-hero h1 {
        margin: 0 0 5px
    }

    .profile-hero p {
        margin: 0;
        color: #6b7280
    }

    .profile-level {
        max-width: 430px;
        margin-top: 14px
    }

    .profile-level-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 6px
    }

    .profile-level-head span,
    .profile-level small {
        color: #6b7280;
        font-size: 12px
    }

    .profile-level-track {
        height: 9px;
        overflow: hidden;
        border-radius: 999px;
        background: #e5e7eb
    }

    .profile-level-track i {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #2563eb, #7c3aed)
    }

    .profile-bio {
        max-width: 600px;
        margin-top: 12px;
        line-height: 1.55;
        white-space: pre-wrap;
        overflow-wrap: anywhere
    }

    .profile-bio-empty,
    .profile-link-empty {
        color: #9ca3af;
        font-style: italic
    }

    .profile-link {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        gap: 5px;
        margin-top: 10px;
        color: #2563eb;
        font-weight: 700;
        text-decoration: none;
        overflow-wrap: anywhere
    }

    .profile-link:hover {
        text-decoration: underline
    }

    .profile-link-empty {
        margin-top: 9px;
        font-size: 13px
    }

    .profile-edit-button {
        margin-top: 16px
    }

    .profile-edit-form {
        display: none
    }

    .profile-edit-form.open {
        display: block
    }

    .profile-edit-form label {
        margin-top: 12px
    }

    .profile-edit-form input,
    .profile-edit-form textarea {
        width: 100%
    }

    .profile-edit-form textarea {
        min-height: 105px
    }

    .bio-counter {
        display: block;
        margin-top: 5px;
        color: #6b7280;
        text-align: right;
        font-size: 12px
    }

    .profile-url-help {
        display: block;
        margin-top: 5px;
        color: #6b7280;
        font-size: 12px
    }

    .profile-form-actions {
        display: flex;
        gap: 8px;
        margin-top: 14px
    }

    .profile-notes {
        display: grid;
        gap: 12px
    }

    .profile-notes a {
        display: grid;
        gap: 7px;
        padding: 17px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        color: inherit;
        text-decoration: none
    }

    .profile-notes a:hover {
        border-color: #93c5fd
    }

    .profile-notes span,
    .profile-notes small,
    .empty-profile {
        color: #6b7280
    }

    html[data-theme="dark"] .profile-hero,
    html[data-theme="dark"] .profile-notes a {
        border-color: #475569
    }

    html[data-theme="dark"] .profile-level-track {
        background: #334155
    }

    html[data-theme="dark"] .profile-hero p,
    html[data-theme="dark"] .profile-notes span,
    html[data-theme="dark"] .profile-notes small {
        color: #cbd5e1
    }

    html[data-theme="dark"] .profile-link {
        color: #93c5fd
    }

    @media(max-width:560px) {
        .profile-hero {
            flex-direction: column
        }

        .profile-summary {
            width: 100%
        }
    }
</style>
<?php if (
    $isOwnProfile
): ?><script>
    (() => {
        const display = document.getElementById('profile-display'),
            form = document.getElementById('profile-edit-form'),
            open = document.getElementById('profile-edit-button'),
            cancel = document.getElementById('profile-edit-cancel'),
            bio = document.getElementById('profile-bio'),
            count = document.getElementById('bio-count');
        if (!form) return;
        const update = () => count.textContent = String(Array.from(bio.value).length);
        const show = () => {
            display.hidden = true;
            form.classList.add('open');
            update();
            document.getElementById('profile-username').focus()
        };
        const hide = () => {
            form.classList.remove('open');
            display.hidden = false
        };
        open?.addEventListener('click', show);
        cancel?.addEventListener('click', hide);
        bio?.addEventListener('input', update);
        if (form.classList.contains('open')) {
            display.hidden = true;
            update()
        }
    })()
</script><?php endif; ?>
<?= $this->endSection() ?>
