<?= $this->extend("layouts/main") ?>
<?= $this->section("content") ?>
<style>
    .invite-card {
        max-width: 650px;
        margin: 35px auto;
        padding: clamp(22px, 5vw, 42px);
        border: 1px solid #e5e7eb;
        border-top: 6px solid var(--project-color);
        border-radius: 16px;
        text-align: center
    }

    .invite-card h1 {
        margin: 10px 0
    }

    .invite-card p {
        color: #6b7280;
        line-height: 1.65
    }

    .invite-actions {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 25px
    }

    .invite-actions form {
        margin: 0
    }

    .invite-actions button {
        min-width: 130px
    }

    @media(max-width:500px) {
        .invite-actions {
            flex-direction: column
        }

        .invite-actions button {
            width: 100%
        }
    }

    html[data-theme="dark"] .invite-card {
        border-color: #475569
    }

    html[data-theme="dark"] .invite-card p {
        color: #cbd5e1
    }
</style>
<article class="invite-card" style="--project-color:<?= esc(
    $invitation["color"],
    "attr",
) ?>">
    <div>◆ PROJE DAVETİ</div>
    <h1><?= esc($invitation["name"]) ?></h1>
    <p><strong><?= esc(
    $invitation["inviter_username"] ?? "Bir proje yöneticisi",
) ?></strong> sizi bu projeye katılmaya davet etti. Proje içeriği daveti kabul ettikten sonra
        görüntülenebilir.</p><?php if (
    $invitation["status"] === "pending"
): ?><div class="invite-actions">
        <form method="post" action="<?= site_url(
    "projects/invitations/" . $invitation["id"],
) ?>"><?= csrf_field() ?><input type="hidden" name="decision" value="accept"><button class="button"
                type="submit">Kabul et</button></form>
        <form method="post" action="<?= site_url(
    "projects/invitations/" . $invitation["id"],
) ?>"><?= csrf_field() ?><input type="hidden" name="decision" value="reject"><button
                class="button danger" type="submit">Reddet</button></form>
    </div><?php else: ?><p>Bu davet daha önce <?= $invitation[
    "status"
] === "accepted"
    ? "kabul edildi"
    : "reddedildi" ?>.</p><a class="button secondary" href="<?= site_url(
    "projects",
) ?>">Projelere dön</a><?php endif; ?>
</article>
<?= $this->endSection() ?>
