<?= $this->extend("layouts/main") ?>
<?= $this->section("content") ?>
<style>
    .projects-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 22px
    }

    .projects-head h1 {
        margin: 0 0 6px
    }

    .projects-head p,
    .project-card p,
    .project-meta {
        color: #6b7280
    }

    .project-create {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 90px;
        gap: 12px;
        padding: 18px;
        margin-bottom: 24px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #f8fafc
    }

    .project-create h2 {
        grid-column: 1/-1;
        margin: 0
    }

    .project-create label {
        margin: 0
    }

    .project-create textarea {
        grid-column: 1/-1;
        min-height: 90px
    }

    .project-create button {
        grid-column: 1/-1
    }

    .project-color {
        width: 100%;
        height: 44px;
        padding: 3px;
        border: 1px solid #d1d5db;
        border-radius: 8px
    }

    .invitation-strip {
        display: grid;
        gap: 10px;
        margin-bottom: 24px
    }

    .invitation-mini {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px;
        border-left: 5px solid var(--project-color);
        border-radius: 10px;
        background: #eff6ff
    }

    .project-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(245px, 1fr));
        gap: 15px
    }

    .project-card {
        display: grid;
        gap: 12px;
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-top: 5px solid var(--project-color);
        border-radius: 13px;
        color: inherit;
        text-decoration: none
    }

    .project-card h2 {
        margin: 0
    }

    .project-card p {
        margin: 0;
        min-height: 42px
    }

    .project-stats {
        display: flex;
        gap: 8px
    }

    .project-stats span {
        padding: 5px 8px;
        border-radius: 7px;
        background: #f3f4f6;
        font-size: 12px
    }

    .project-empty {
        padding: 45px;
        text-align: center;
        color: #6b7280
    }

    @media(max-width:650px) {

        .projects-head,
        .invitation-mini {
            flex-direction: column
        }

        .project-create {
            grid-template-columns: 1fr
        }

        .project-create>* {
            grid-column: 1 !important
        }
    }

    html[data-theme="dark"] .project-create,
    html[data-theme="dark"] .project-card {
        background: #1e293b;
        border-color: #475569
    }

    html[data-theme="dark"] .invitation-mini {
        background: #172554
    }

    html[data-theme="dark"] .project-stats span {
        background: #334155
    }

    html[data-theme="dark"] .projects-head p,
    html[data-theme="dark"] .project-card p,
    html[data-theme="dark"] .project-meta {
        color: #94a3b8
    }
</style>
<style>
    .project-create-button {
        white-space: nowrap
    }

    .project-drawer-backdrop {
        position: fixed;
        inset: 0;
        z-index: 3999;
        border: 0;
        background: rgba(15, 23, 42, .52);
        opacity: 0;
        pointer-events: none;
        transition: .2s
    }

    .project-drawer {
        position: fixed;
        top: 0;
        right: 0;
        z-index: 4000;
        width: min(430px, 100%);
        height: 100dvh;
        padding: 24px;
        overflow-y: auto;
        background: #fff;
        box-shadow: -18px 0 45px rgba(15, 23, 42, .25);
        transform: translateX(105%);
        transition: transform .22s ease
    }

    .project-drawer.open {
        transform: translateX(0)
    }

    .project-drawer-backdrop.open {
        opacity: 1;
        pointer-events: auto
    }

    .project-drawer-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 20px
    }

    .project-drawer-head h2 {
        margin: 0
    }

    .project-drawer-close {
        display: grid;
        width: 38px;
        height: 38px;
        padding: 0;
        place-items: center;
        border: 0;
        border-radius: 50%;
        background: #f3f4f6;
        font-size: 22px;
        cursor: pointer
    }

    .project-drawer .project-create {
        grid-template-columns: 1fr;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent
    }

    .project-drawer .project-create h2 {
        display: none
    }

    .project-drawer .project-create>* {
        grid-column: 1 !important
    }

    html[data-theme="dark"] .project-drawer {
        background: #1e293b;
        color: #e5e7eb
    }

    html[data-theme="dark"] .project-drawer-close {
        background: #334155;
        color: #fff
    }

    @media(max-width:650px) {
        .project-create-button {
            width: 100%
        }
    }
</style>
<header class="projects-head">
    <div>
        <h1>Projeler</h1>
        <p>Ortak işleri, üyeleri ve ilerlemeyi tek çalışma alanında yönetin.</p>
    </div><button class="button project-create-button" type="button" data-project-drawer-open>+ Yeni
        Proje</button>
</header>
<?php if ($errors = session()->getFlashdata("errors")): ?><div class="alert error"><?php foreach (
    $errors
    as $error
): ?><div><?= esc($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if (
    $invitations !== []
): ?><section class="invitation-strip">
    <h2>Bekleyen davetler</h2><?php foreach (
    $invitations
    as $invite
): ?><article class="invitation-mini" style="--project-color:<?= esc(
    $invite["color"],
    "attr",
) ?>">
        <div><strong><?= esc($invite["name"]) ?></strong>
            <div class="project-meta"><?= esc(
    $invite["inviter_username"] ?? "Bir yönetici",
) ?> sizi davet etti.</div>
        </div><a class="button" href="<?= site_url(
     "projects/invitations/" . $invite["id"],
 ) ?>">Daveti görüntüle</a>
    </article><?php endforeach; ?>
</section><?php endif; ?>
<button class="project-drawer-backdrop" type="button" aria-label="Yeni proje panelini kapat"
    data-project-drawer-close></button>
<aside class="project-drawer <?= old(
    "name",
) !== null
    ? "open"
    : "" ?>" aria-hidden="<?= old("name") !== null
    ? "false"
    : "true" ?>" data-project-drawer>
    <header class="project-drawer-head">
        <div>
            <h2>Yeni proje oluştur</h2>
            <p class="project-meta">Proje bilgilerini girip çalışma alanını başlatın.</p>
        </div><button class="project-drawer-close" type="button" aria-label="Kapat"
            data-project-drawer-close>×</button>
    </header>
    <form class="project-create" method="post" action="<?= site_url(
    "projects",
) ?>"><?= csrf_field() ?><h2>Yeni proje oluştur</h2><label>Proje adı<input type="text" name="name"
                value="<?= esc(
    old("name"),
) ?>" maxlength="120" required></label><label>Renk<input class="project-color" type="color"
                name="color" value="<?= esc(
    old("color") ?: "#2563eb",
    "attr",
) ?>"></label><label>Açıklama<textarea name="description" maxlength="5000"
                placeholder="Projenin amacı ve kısa kapsamı..."><?= esc(
    old("description"),
) ?></textarea></label><button class="button" type="submit">Projeyi oluştur</button></form>
</aside>
<?php if (
    $projects === []
): ?><div class="project-empty">Henüz dahil olduğunuz bir proje yok.</div><?php else: ?><div
    class="project-grid"><?php foreach (
    $projects
    as $project
):

    $total = (int) $project["item_count"];
    $done = (int) $project["done_count"];
    ?><a class="project-card" style="--project-color:<?= esc(
    $project["color"],
    "attr",
) ?>" href="<?= site_url("projects/" . $project["id"]) ?>">
        <h2><?= esc(
    $project["name"],
) ?></h2>
        <p><?= esc(
    mb_strimwidth($project["description"] ?: "Henüz açıklama eklenmedi.", 0, 130, "…"),
) ?></p>
        <div class="project-stats"><span><?= (int) $project[
    "member_count"
] ?> üye</span><span><?= $done ?>/<?= $total ?> tamamlandı</span></div><small
            class="project-meta">Sahibi: <?= esc(
     $project["owner_username"],
 ) ?></small>
    </a><?php
endforeach; ?></div><?= $pager->links("projects") ?><?php endif; ?>
<script>
    (() => {
        const drawer = document.querySelector('[data-project-drawer]'),
            backdrop = document.querySelector('.project-drawer-backdrop'),
            openButton = document.querySelector('[data-project-drawer-open]');
        if (!drawer || !backdrop || !openButton) return;
        const open = () => {
                drawer.classList.add('open');
                backdrop.classList.add('open');
                drawer.setAttribute('aria-hidden', 'false');
                drawer.querySelector('input[name="name"]')?.focus();
            },
            close = () => {
                drawer.classList.remove('open');
                backdrop.classList.remove('open');
                drawer.setAttribute('aria-hidden', 'true');
                openButton.focus();
            };
        openButton.addEventListener('click', open);
        document.querySelectorAll('[data-project-drawer-close]').forEach(button => button
            .addEventListener('click', close));
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && drawer.classList.contains('open')) close()
        });
        if (drawer.classList.contains('open')) open();
    })()
</script>
<?= $this->endSection() ?>
