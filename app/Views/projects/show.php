<?= $this->extend("layouts/main") ?>
<?= $this->section("content") ?>
<?php
$accepted = array_values(array_filter($members, static fn($m) => $m["status"] === "accepted"));
$pending = array_values(array_filter($members, static fn($m) => $m["status"] === "pending"));
$columns = [
    "todo" => ["Yapılacak", "#64748b"],
    "in_progress" => ["Devam ediyor", "#f59e0b"],
    "done" => ["Tamamlandı", "#22c55e"],
];
$isOwner = $membership["role"] === "owner";
?>
<style>
    .project-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        padding: 22px;
        margin-bottom: 20px;
        border-left: 7px solid var(--project-color);
        border-radius: 13px;
        background: #f8fafc
    }

    .project-hero h1 {
        margin: 0 0 8px
    }

    .project-hero p {
        max-width: 750px;
        margin: 0;
        color: #6b7280;
        line-height: 1.55
    }

    .workspace-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 290px;
        gap: 18px
    }

    .workspace-panel {
        padding: 17px;
        border: 1px solid #e5e7eb;
        border-radius: 13px
    }

    .workspace-panel h2,
    .workspace-panel h3 {
        margin-top: 0
    }

    .kanban {
        display: grid;
        grid-template-columns: repeat(3, minmax(220px, 1fr));
        gap: 12px;
        overflow-x: auto
    }

    .kanban-column {
        padding: 12px;
        border-radius: 12px;
        background: #f3f4f6
    }

    .kanban-column h3 {
        display: flex;
        justify-content: space-between;
        margin: 0 0 12px
    }

    .work-item {
        padding: 13px;
        margin-bottom: 9px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff
    }

    .work-item h4 {
        margin: 0 0 7px
    }

    .work-item p,
    .work-item small {
        color: #6b7280
    }

    .work-item p {
        white-space: pre-wrap
    }

    .work-item select {
        width: 100%;
        margin-top: 9px;
        padding: 7px
    }

    .work-state {
        display: block;
        margin-top: 10px;
        padding: 8px;
        border-radius: 7px;
        background: #f3f4f6;
        text-align: center
    }

    .new-item-form {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 9px;
        margin-bottom: 18px
    }

    .new-item-form textarea {
        grid-column: 1/-1;
        min-height: 75px
    }

    .new-item-form button {
        grid-column: 1/-1
    }

    .member-list {
        display: grid;
        gap: 8px
    }

    .member {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 9px;
        border-radius: 9px;
        background: #f8fafc
    }

    .member-avatar {
        display: grid;
        width: 34px;
        height: 34px;
        place-items: center;
        border-radius: 50%;
        background: var(--project-color);
        color: #fff;
        font-weight: 800
    }

    .member small {
        display: block;
        color: #6b7280
    }

    .invite-form {
        display: grid;
        gap: 8px;
        margin-bottom: 18px
    }

    .invite-form label {
        margin: 0
    }

    .project-back {
        white-space: nowrap
    }

    @media(max-width:950px) {
        .workspace-grid {
            grid-template-columns: 1fr
        }

        .workspace-side {
            order: -1
        }
    }

    @media(max-width:650px) {
        .project-hero {
            flex-direction: column
        }

        .new-item-form {
            grid-template-columns: 1fr
        }

        .new-item-form>* {
            grid-column: 1 !important
        }
    }

    html[data-theme="dark"] .project-hero,
    html[data-theme="dark"] .workspace-panel,
    html[data-theme="dark"] .work-item {
        background: #1e293b;
        border-color: #475569
    }

    html[data-theme="dark"] .kanban-column,
    html[data-theme="dark"] .member,
    html[data-theme="dark"] .work-state {
        background: #0f172a
    }

    html[data-theme="dark"] .project-hero p,
    html[data-theme="dark"] .work-item p,
    html[data-theme="dark"] .work-item small {
        color: #94a3b8
    }
</style>
<style>
    .workspace-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px
    }

    .workspace-panel-head h2 {
        margin: 0
    }

    .work-drawer-backdrop {
        position: fixed;
        inset: 0;
        z-index: 3999;
        border: 0;
        background: rgba(15, 23, 42, .55);
        opacity: 0;
        pointer-events: none;
        transition: .2s
    }

    .work-drawer {
        position: fixed;
        inset: 0 0 0 auto;
        z-index: 4000;
        width: min(440px, 100%);
        padding: 24px;
        overflow-y: auto;
        background: #fff;
        box-shadow: -18px 0 45px rgba(15, 23, 42, .25);
        transform: translateX(105%);
        transition: transform .22s ease
    }

    .work-drawer.open {
        transform: translateX(0)
    }

    .work-drawer-backdrop.open {
        opacity: 1;
        pointer-events: auto
    }

    .work-drawer-head {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 22px
    }

    .work-drawer-head h2 {
        margin: 0 0 5px
    }

    .work-drawer-head p {
        margin: 0;
        color: #6b7280
    }

    .work-drawer-close {
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

    .work-drawer .new-item-form {
        display: grid;
        grid-template-columns: 1fr;
        gap: 13px;
        margin: 0
    }

    .work-drawer .new-item-form>* {
        grid-column: 1
    }

    .work-drawer .new-item-form label {
        margin: 0
    }

    .work-drawer .new-item-form input,
    .work-drawer .new-item-form select,
    .work-drawer .new-item-form textarea {
        width: 100%
    }

    .work-drawer .new-item-form textarea {
        min-height: 110px
    }

    @media(max-width:650px) {
        .workspace-panel-head {
            align-items: stretch;
            flex-direction: column
        }

        .workspace-panel-head .button {
            width: 100%
        }
    }

    html[data-theme="dark"] .work-drawer {
        background: #1e293b;
        color: #e5e7eb
    }

    html[data-theme="dark"] .work-drawer-close {
        background: #0f172a;
        color: #fff
    }

    html[data-theme="dark"] .work-drawer-head p {
        color: #94a3b8
    }
</style>
<header class="project-hero" style="--project-color:<?= esc(
    $project["color"],
    "attr",
) ?>">
    <div>
        <h1><?= esc($project["name"]) ?></h1>
        <p><?= $project["description"]
    ? nl2br(esc($project["description"]))
    : "Bu proje için henüz açıklama eklenmedi." ?></p>
    </div><a class="button secondary project-back" href="<?= site_url(
    "projects",
) ?>">Tüm projeler</a>
</header>
<?php if ($errors = session()->getFlashdata("errors")): ?><div class="alert error"><?php foreach (
    $errors
    as $error
): ?><div><?= esc($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<div class="workspace-grid">
    <main>
        <section class="workspace-panel">
            <header class="workspace-panel-head">
                <h2>Proje işleri</h2><button class="button" type="button" data-work-drawer-open>+
                    Yeni iş</button>
            </header>
            <div class="kanban"><?php foreach ($columns as $key => $column):
    $columnItems = array_values(
        array_filter($items, static fn($item) => $item["status"] === $key),
    ); ?><section class="kanban-column">
                    <h3><span style="color:<?= $column[1] ?>"><?= $column[0] ?></span><small><?= count(
    $columnItems,
) ?></small></h3><?php
foreach ($columnItems as $item):
    $canInteract =
        $isOwner ||
        (int) ($item["assigned_to"] ?? 0) ===
            (int) session()->get("user_id"); ?><article class="work-item">
                        <h4><?= esc(
    $item["title"],
) ?></h4><?php if ($item["description"]): ?><p><?= esc(
    $item["description"],
) ?></p><?php endif; ?><small><?=
($item["assignee_username"] ? "Atanan: " . esc($item["assignee_username"]) : "Atanmamış") .
($item["due_date"] ? " · " . date("d.m.Y", strtotime($item["due_date"])) : "")
?></small>
                        <?php if ($isOwner): ?><form method="post" action="<?= site_url(
    "projects/" . $project["id"] . "/items/" . $item["id"] . "/assign",
) ?>"><?= csrf_field() ?><select name="assigned_to" onchange="this.form.submit()">
                                <option value="">Atanmamış</option><?php foreach (
    $accepted
    as $member
): ?><option value="<?= (int) $member["user_id"] ?>" <?= (int) ($item["assigned_to"] ?? 0) ===
(int) $member["user_id"]
    ? "selected"
    : "" ?>><?= esc(
    $member["username"],
) ?></option><?php endforeach; ?>
                            </select></form><?php endif; ?>
                        <?php if ($canInteract): ?><form method="post" action="<?= site_url(
    "projects/" . $project["id"] . "/items/" . $item["id"] . "/status",
) ?>"><?= csrf_field() ?><select name="status" onchange="this.form.submit()">
                                <option value="todo" <?= $key ===
"todo"
    ? "selected"
    : "" ?>>Yapılacak</option>
                                <option value="in_progress" <?= $key === "in_progress"
    ? "selected"
    : "" ?>>Devam ediyor</option>
                                <option value="done" <?= $key === "done"
    ? "selected"
    : "" ?>>Tamamlandı</option>
                            </select></form><?php else: ?><span class="work-state">Yalnızca
                            görüntüleme</span><?php endif; ?>
                    </article><?php
endforeach;
if ($columnItems === []): ?><small>Bu sütunda iş yok.</small><?php endif;
?>
                </section><?php
endforeach; ?></div>
        </section>
    </main>
    <aside class="workspace-side">
        <section class="workspace-panel">
            <h2>Üyeler · <?= count(
    $accepted,
) ?></h2><?php if ($isOwner): ?><form class="invite-form" method="post" action="<?= site_url(
    "projects/" . $project["id"] . "/invite",
) ?>"><?= csrf_field() ?><label>Kullanıcı davet et</label><input type="text" name="username"
                    required placeholder="Tam kullanıcı adı"><button class="button"
                    type="submit">Davet gönder</button></form><?php endif; ?><div
                class="member-list"><?php foreach (
    $accepted
    as $member
): ?><div class="member" style="--project-color:<?= esc(
    $project["color"],
    "attr",
) ?>"><span class="member-avatar"><?= esc(
    mb_strtoupper(mb_substr($member["username"], 0, 1)),
) ?></span>
                    <div><strong><?= esc($member["username"]) ?></strong><small><?= $member["role"] ===
"owner"
    ? "Proje sahibi"
    : "Üye" ?></small></div>
                </div><?php endforeach; ?></div><?php if (
    $isOwner &&
    $pending !== []
): ?><h3 style="margin-top:20px">Bekleyen davetler</h3><?php foreach (
    $pending
    as $member
): ?><div class="member"><span class="member-avatar">?</span>
                <div><strong><?= esc(
    $member["username"],
) ?></strong><small>Yanıt bekleniyor</small></div>
            </div><?php endforeach;endif; ?>
        </section>
    </aside>
</div>
<button class="work-drawer-backdrop" type="button" aria-label="Yeni iş panelini kapat"
    data-work-drawer-close></button>
<aside class="work-drawer <?= old(
    "title",
) !== null
    ? "open"
    : "" ?>" aria-hidden="<?= old("title") !== null
    ? "false"
    : "true" ?>" data-work-drawer>
    <header class="work-drawer-head">
        <div>
            <h2>Yeni iş oluştur</h2>
            <p>İşi tanımlayın, tarihlerini belirleyin ve bir proje üyesine atayın.</p>
        </div><button class="work-drawer-close" type="button" aria-label="Kapat"
            data-work-drawer-close>×</button>
    </header>
    <form class="new-item-form" method="post" action="<?= site_url(
    "projects/" . $project["id"] . "/items",
) ?>"><?= csrf_field() ?><label>İş başlığı<input type="text" name="title" value="<?= esc(
    old("title"),
) ?>" maxlength="160" required placeholder="Örn. giriş ekranını tamamla"></label><?php if (
    $isOwner
): ?><label>Atanan kişi<select name="assigned_to">
                <option value="">Atanmamış</option><?php foreach (
    $accepted
    as $member
): ?><option value="<?= (int) $member["user_id"] ?>" <?= (int) old("assigned_to") ===
(int) $member["user_id"]
    ? "selected"
    : "" ?>><?= esc(
    $member["username"],
) ?></option><?php endforeach; ?>
            </select></label><label>Gantt başlığı<select name="section_id">
                <option value="">Başlıksız</option><?php foreach (
    $sections
    as $section
): ?><option value="<?= (int) $section["id"] ?>" <?= (int) old("section_id") ===
(int) $section["id"]
    ? "selected"
    : "" ?>><?= esc(
    $section["name"],
) ?></option><?php endforeach; ?>
            </select></label><?php else: ?><input type="hidden" name="assigned_to" value=""><input
            type="hidden" name="section_id" value="">
        <div class="work-state">Atamayı proje sahibi yapacak</div><?php endif; ?><label>Başlangıç
            tarihi<input type="date" name="start_date" value="<?= esc(
    old("start_date"),
) ?>"></label><label>Bitiş tarihi<input type="date" name="due_date" value="<?= esc(
    old("due_date"),
) ?>"></label><label>Açıklama<textarea name="description" maxlength="3000"
                placeholder="Kısa açıklama veya kabul kriteri..."><?= esc(
    old("description"),
) ?></textarea></label><button class="button" type="submit">İşi oluştur</button>
    </form>
</aside>
<?= $this->include("projects/_timeline") ?>
<script>
    (() => {
        const drawer = document.querySelector('[data-work-drawer]'),
            backdrop = document.querySelector('.work-drawer-backdrop'),
            openButton = document.querySelector('[data-work-drawer-open]');
        if (!drawer || !backdrop || !openButton) return;
        const open = () => {
                drawer.classList.add('open');
                backdrop.classList.add('open');
                drawer.setAttribute('aria-hidden', 'false');
                drawer.querySelector('input[name="title"]')?.focus()
            },
            close = () => {
                drawer.classList.remove('open');
                backdrop.classList.remove('open');
                drawer.setAttribute('aria-hidden', 'true');
                openButton.focus()
            };
        openButton.addEventListener('click', open);
        document.querySelectorAll('[data-work-drawer-close]').forEach(button => button
            .addEventListener('click', close));
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && drawer.classList.contains('open')) close()
        });
        if (drawer.classList.contains('open')) open()
    })()
</script>
<?= $this->endSection() ?>
