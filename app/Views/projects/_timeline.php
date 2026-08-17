<?php
$isOwner = $isOwner ?? ($membership["role"] ?? "") === "owner";
$sections = $sections ?? [];
$scheduled = array_values(
    array_filter(
        $items,
        static fn($item) => !empty($item["start_date"]) || !empty($item["due_date"]),
    ),
);
$unscheduled = array_values(
    array_filter(
        $items,
        static fn($item) => empty($item["start_date"]) && empty($item["due_date"]),
    ),
);
$statusLabels = ["todo" => "Yapılacak", "in_progress" => "Devam ediyor", "done" => "Tamamlandı"];
$statusClasses = ["todo" => "todo", "in_progress" => "progress", "done" => "done"];
$sectionGroups = [];
foreach ($sections as $section) {
    $sectionGroups[(int) $section["id"]] = ["section" => $section, "items" => []];
}
$unsectioned = [];
foreach ($scheduled as $item) {
    $sectionId = (int) ($item["section_id"] ?? 0);
    if ($sectionId > 0 && isset($sectionGroups[$sectionId])) {
        $sectionGroups[$sectionId]["items"][] = $item;
    } else {
        $unsectioned[] = $item;
    }
}
$dates = [];
foreach ($scheduled as $item) {
    if ($item["start_date"]) {
        $dates[] = strtotime($item["start_date"]);
    }
    if ($item["due_date"]) {
        $dates[] = strtotime($item["due_date"]);
    }
}
$rangeStart = $dates ? strtotime(date("Y-m-d", min($dates))) : strtotime(date("Y-m-d"));
$rangeEnd = $dates ? strtotime(date("Y-m-d", max($dates))) : $rangeStart;
$totalDays = max(1, (int) floor(($rangeEnd - $rangeStart) / 86400) + 1);
$stepDays = $totalDays > 42 ? 7 : 1;
$slotCount = max(1, (int) ceil($totalDays / $stepDays));
$slots = [];
for ($i = 0; $i < $slotCount; $i++) {
    $slots[] = strtotime("+" . $i * $stepDays . " days", $rangeStart);
}
?>
<style>
    .gantt {
        margin-top: 20px;
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff
    }

    .gantt-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px
    }

    .gantt-head h2 {
        margin: 0 0 5px
    }

    .gantt-head p {
        margin: 0;
        color: #6b7280
    }

    .gantt-head-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap
    }

    .gantt-period {
        padding: 7px 11px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap
    }

    .gantt-export-button {
        flex: 0 0 auto;
        white-space: nowrap
    }

    .gantt-section-manager {
        padding: 13px;
        margin-bottom: 16px;
        border: 1px solid #dbeafe;
        border-radius: 11px;
        background: #f8fafc
    }

    .gantt-section-create {
        display: flex;
        gap: 8px
    }

    .gantt-section-create input {
        flex: 1;
        min-width: 120px
    }

    .gantt-section-list {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 10px
    }

    .gantt-section-chip {
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 6px 7px 6px 10px;
        border-radius: 999px;
        background: #e0e7ff;
        color: #3730a3;
        font-size: 12px;
        font-weight: 700
    }

    .gantt-section-chip form {
        margin: 0
    }

    .gantt-section-delete {
        display: grid;
        width: 22px;
        height: 22px;
        padding: 0;
        place-items: center;
        border: 0;
        border-radius: 50%;
        background: #c7d2fe;
        color: #3730a3;
        cursor: pointer
    }

    .gantt-scroll {
        overflow: auto;
        border: 1px solid #e5e7eb;
        border-radius: 12px
    }

    .gantt-board {
        min-width: max(760px, calc(300px + var(--slot-count) * var(--slot-width)))
    }

    .gantt-row {
        display: grid;
        grid-template-columns: 300px minmax(calc(var(--slot-count) * var(--slot-width)), 1fr);
        min-height: 64px;
        border-bottom: 1px solid #e5e7eb
    }

    .gantt-row:last-child {
        border-bottom: 0
    }

    .gantt-group-row {
        display: flex;
        position: sticky;
        left: 0;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-height: 42px;
        padding: 9px 14px;
        border-bottom: 1px solid #c7d2fe;
        background: #eef2ff;
        color: #3730a3
    }

    .gantt-group-row strong {
        font-size: 14px
    }

    .gantt-group-row span {
        font-size: 12px
    }

    .gantt-task {
        position: sticky;
        left: 0;
        z-index: 2;
        padding: 12px 14px;
        border-right: 1px solid #e5e7eb;
        background: #fff
    }

    .gantt-task strong {
        display: block;
        margin-bottom: 5px
    }

    .gantt-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
        color: #6b7280;
        font-size: 12px
    }

    .gantt-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #94a3b8
    }

    .gantt-dot.progress {
        background: #f59e0b
    }

    .gantt-dot.done {
        background: #22c55e
    }

    .gantt-calendar {
        display: grid;
        grid-template-columns: repeat(var(--slot-count), minmax(var(--slot-width), 1fr));
        align-items: center;
        background: repeating-linear-gradient(90deg, transparent 0, transparent calc((100% / var(--slot-count)) - 1px), #e5e7eb calc((100% / var(--slot-count)) - 1px), #e5e7eb calc(100% / var(--slot-count)))
    }

    .gantt-header {
        min-height: 48px;
        background: #f8fafc
    }

    .gantt-header .gantt-task {
        display: flex;
        align-items: center;
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        font-weight: 800
    }

    .gantt-date {
        align-self: stretch;
        display: grid;
        place-items: center;
        padding: 6px 2px;
        color: #64748b;
        font-size: 11px;
        text-align: center
    }

    .gantt-date b {
        display: block;
        color: #334155;
        font-size: 12px
    }

    .gantt-bar {
        align-self: center;
        height: 32px;
        margin: 0 4px;
        padding: 7px 10px;
        overflow: hidden;
        border-radius: 8px;
        background: #64748b;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        line-height: 18px;
        text-overflow: ellipsis;
        white-space: nowrap;
        box-shadow: 0 4px 10px rgba(15, 23, 42, .16)
    }

    .gantt-bar.progress {
        background: #d97706
    }

    .gantt-bar.done {
        background: #16a34a
    }

    .gantt-edit {
        margin-top: 8px
    }

    .gantt-edit summary {
        cursor: pointer;
        color: #2563eb;
        font-size: 12px
    }

    .gantt-edit-form {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 6px;
        margin-top: 7px
    }

    .gantt-edit-form input,
    .gantt-edit-form button {
        min-width: 0;
        padding: 6px;
        font-size: 11px
    }

    .gantt-section-select {
        margin-top: 8px
    }

    .gantt-section-select select {
        width: 100%;
        padding: 6px;
        font-size: 11px
    }

    .gantt-empty {
        padding: 36px;
        text-align: center;
        color: #6b7280
    }

    .gantt-backlog {
        margin-top: 20px
    }

    .gantt-backlog h3 {
        margin: 0 0 10px
    }

    .gantt-backlog-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 10px
    }

    .gantt-backlog-card {
        padding: 13px;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        background: #f8fafc
    }

    .gantt-backlog-card strong,
    .gantt-backlog-card small {
        display: block
    }

    .gantt-backlog-card small {
        margin-top: 5px;
        color: #6b7280
    }

    .gantt-section-create input[type="color"] {
        flex: 0 0 48px;
        width: 48px;
        min-width: 48px;
        height: 40px;
        padding: 3px;
        cursor: pointer
    }

    .gantt-section-color-form {
        display: flex;
        margin: 0
    }

    .gantt-section-color {
        width: 26px;
        height: 26px;
        padding: 2px;
        border: 0;
        border-radius: 50%;
        background: transparent;
        cursor: pointer
    }

    .gantt-group-row {
        border-color: var(--section-color);
        background: color-mix(in srgb, var(--section-color) 14%, white);
        color: var(--section-color)
    }

    .gantt-row.section-colored .gantt-task {
        box-shadow: inset 4px 0 var(--section-color)
    }

    .gantt-row.section-colored .gantt-bar,
    .gantt-row.section-colored .gantt-bar.progress,
    .gantt-row.section-colored .gantt-bar.done {
        background: var(--section-color)
    }

    @media(max-width:650px) {
        .gantt {
            padding: 14px
        }

        .gantt-head {
            flex-direction: column
        }

        .gantt-head-actions,
        .gantt-export-button {
            width: 100%
        }

        .gantt-export-button {
            text-align: center
        }

        .gantt-section-create {
            display: grid
        }

        .gantt-section-create input[type="color"] {
            width: 100%;
            min-width: 0
        }

        .gantt-row {
            grid-template-columns: 220px minmax(calc(var(--slot-count) * var(--slot-width)), 1fr)
        }

        .gantt-board {
            min-width: max(650px, calc(220px + var(--slot-count) * var(--slot-width)))
        }

        .gantt-task {
            padding: 10px
        }

        .gantt-edit-form {
            grid-template-columns: 1fr
        }

        .gantt-edit-form button {
            width: 100%
        }
    }

    html[data-theme="dark"] .gantt {
        background: #1e293b;
        border-color: #475569
    }

    html[data-theme="dark"] .gantt-scroll,
    html[data-theme="dark"] .gantt-row,
    html[data-theme="dark"] .gantt-task {
        border-color: #334155
    }

    html[data-theme="dark"] .gantt-task {
        background: #1e293b
    }

    html[data-theme="dark"] .gantt-header,
    html[data-theme="dark"] .gantt-header .gantt-task,
    html[data-theme="dark"] .gantt-backlog-card,
    html[data-theme="dark"] .gantt-section-manager {
        background: #0f172a
    }

    html[data-theme="dark"] .gantt-calendar {
        background: repeating-linear-gradient(90deg, transparent 0, transparent calc((100% / var(--slot-count)) - 1px), #334155 calc((100% / var(--slot-count)) - 1px), #334155 calc(100% / var(--slot-count)))
    }

    html[data-theme="dark"] .gantt-head p,
    html[data-theme="dark"] .gantt-meta,
    html[data-theme="dark"] .gantt-backlog-card small {
        color: #94a3b8
    }

    html[data-theme="dark"] .gantt-date,
    html[data-theme="dark"] .gantt-date b {
        color: #cbd5e1
    }

    html[data-theme="dark"] .gantt-period {
        background: #172554;
        color: #bfdbfe
    }

    html[data-theme="dark"] .gantt-section-manager {
        border-color: #334155
    }

    html[data-theme="dark"] .gantt-group-row {
        border-color: #3730a3;
        background: #1e1b4b;
        color: #c7d2fe
    }

    html[data-theme="dark"] .gantt-group-row {
        border-color: var(--section-color);
        background: color-mix(in srgb, var(--section-color) 22%, #0f172a);
        color: color-mix(in srgb, var(--section-color) 60%, white)
    }

    html[data-theme="dark"] .gantt-section-chip {
        background: color-mix(in srgb, currentColor 22%, #0f172a) !important
    }

    @media print {
        @page {
            size: landscape;
            margin: 10mm
        }

        body.gantt-printing * {
            visibility: hidden !important
        }

        body.gantt-printing .gantt,
        body.gantt-printing .gantt * {
            visibility: visible !important
        }

        body.gantt-printing .gantt {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            background: #fff !important;
            color: #111827 !important
        }

        body.gantt-printing .gantt-export-button,
        body.gantt-printing .gantt-edit,
        body.gantt-printing .gantt-section-manager,
        body.gantt-printing .gantt-section-select,
        body.gantt-printing .gantt-backlog {
            display: none !important
        }

        body.gantt-printing .gantt-scroll {
            overflow: visible;
            border-color: #d1d5db
        }

        body.gantt-printing .gantt-board {
            width: 100%;
            min-width: 0 !important
        }

        body.gantt-printing .gantt-row {
            grid-template-columns: 220px minmax(0, 1fr);
            break-inside: avoid
        }

        body.gantt-printing .gantt-task,
        body.gantt-printing .gantt-header,
        body.gantt-printing .gantt-header .gantt-task {
            background: #fff !important;
            color: #111827 !important
        }

        body.gantt-printing .gantt-group-row {
            background: #eef2ff !important;
            color: #3730a3 !important
        }

        body.gantt-printing .gantt-calendar {
            grid-template-columns: repeat(var(--slot-count), minmax(0, 1fr));
            background-color: #fff !important
        }

        body.gantt-printing .gantt-date,
        body.gantt-printing .gantt-date b,
        body.gantt-printing .gantt-meta {
            color: #334155 !important
        }
    }

    @media print {
        body.gantt-printing .gantt-group-row {
            border-color: var(--section-color) !important;
            background: color-mix(in srgb, var(--section-color) 14%, white) !important;
            color: var(--section-color) !important
        }
    }
</style>
<section class="gantt" style="--slot-count:<?= $slotCount ?>;--slot-width:<?= $stepDays === 1
    ? "54px"
    : "76px" ?>">
    <header class="gantt-head">
        <div>
            <h2>İş zaman şeması</h2>
            <p>Görevleri başlıklara ayırarak tarih ve sorumlulara göre takip edin.</p>
        </div>
        <div class="gantt-head-actions"><?php if (
    $scheduled !== []
): ?><span class="gantt-period"><?= date("d.m.Y", $rangeStart) ?> — <?= date(
     "d.m.Y",
     $rangeEnd,
 ) ?></span><?php endif; ?><button class="button secondary gantt-export-button" type="button"
                data-gantt-export>Dışa aktar</button></div>
    </header>
    <?php if (
    $isOwner
): ?><section class="gantt-section-manager">
        <form class="gantt-section-create" method="post" action="<?= site_url(
    "projects/" . $project["id"] . "/sections",
) ?>"><?= csrf_field() ?><input type="text" name="name" minlength="2" maxlength="100" required
                placeholder="Yeni başlık (örn. Tasarım)"><input type="color" name="color"
                value="#6366f1" required aria-label="Başlık rengi"><button class="button"
                type="submit">Başlık ekle</button></form><?php if (
    $sections !== []
): ?><div class="gantt-section-list"><?php foreach (
    $sections
    as $section
): ?><span class="gantt-section-chip" style="background:color-mix(in srgb,<?= esc(
    $section["color"],
    "attr",
) ?> 18%,white);color:<?= esc(
     $section["color"],
     "attr",
 ) ?>">
                <form class="gantt-section-color-form" method="post" action="<?= site_url(
    "projects/" . $project["id"] . "/sections/" . $section["id"],
) ?>"><?= csrf_field() ?><input class="gantt-section-color" type="color" name="color" value="<?= esc(
    $section["color"],
    "attr",
) ?>" aria-label="<?= esc(
    $section["name"],
    "attr",
) ?> rengini değiştir" onchange="this.form.submit()"></form><?= esc(
     $section["name"],
 ) ?><form method="post" action="<?= site_url(
    "projects/" . $project["id"] . "/sections/" . $section["id"] . "/delete",
) ?>" onsubmit="return confirm('Bu başlık silinsin mi? Görevler başlıksız kalacak.')">
                    <?= csrf_field() ?><button class="gantt-section-delete" type="submit"
                        aria-label="<?= esc(
    $section["name"],
    "attr",
) ?> başlığını sil">×</button></form>
            </span><?php endforeach; ?></div><?php endif; ?>
    </section><?php endif; ?>
    <?php if (
    $scheduled === []
): ?><div class="gantt-empty">Şemada gösterilecek tarihli bir iş bulunmuyor. Yeni iş oluştururken
        başlangıç ve bitiş tarihi ekleyebilirsin.</div><?php else: ?>
    <div class="gantt-scroll">
        <div class="gantt-board">
            <div class="gantt-row gantt-header">
                <div class="gantt-task">Görev / sorumlu</div>
                <div class="gantt-calendar"><?php foreach (
    $slots
    as $slot
): ?><div class="gantt-date"><b><?= date("d", $slot) ?></b><?= $stepDays === 1
    ? date("M", $slot)
    : date("d.m", $slot) ?></div><?php endforeach; ?></div>
            </div>
            <?php
$renderGroups = array_values($sectionGroups);
if ($unsectioned !== []) {
    $renderGroups[] = ["section" => null, "items" => $unsectioned];
}
foreach ($renderGroups as $group):

    $groupName = $group["section"]["name"] ?? "Başlıksız görevler";
    $groupColor = $group["section"]["color"] ?? "#64748b";
    ?>
            <div class="gantt-group-row" style="--section-color:<?= esc(
    $groupColor,
    "attr",
) ?>"><strong><?= esc($groupName) ?></strong><span><?= count($group["items"]) ?> görev</span></div>
            <?php foreach ($group["items"] as $item):

    $start = strtotime($item["start_date"] ?: $item["due_date"]);
    $end = strtotime($item["due_date"] ?: $item["start_date"]);
    if ($end < $start) {
        [$start, $end] = [$end, $start];
    }
    $startColumn = (int) floor(($start - $rangeStart) / (86400 * $stepDays)) + 1;
    $endColumn = (int) floor(($end - $rangeStart) / (86400 * $stepDays)) + 2;
    $statusClass = $statusClasses[$item["status"]] ?? "todo";
    ?>
            <div class="gantt-row section-colored" style="--section-color:<?= esc(
    $groupColor,
    "attr",
) ?>">
                <div class="gantt-task"><strong><?= esc(
    $item["title"],
) ?></strong>
                    <div class="gantt-meta"><i class="gantt-dot <?= $statusClass ?>"></i><span><?= esc(
    $statusLabels[$item["status"]] ?? $item["status"],
) ?></span><span>·</span><span><?= esc(
    $item["assignee_username"] ?: "Atanmamış",
) ?></span></div><?php if (
    $isOwner
): ?><form class="gantt-section-select" method="post" action="<?= site_url(
    "projects/" . $project["id"] . "/items/" . $item["id"] . "/section",
) ?>"><?= csrf_field() ?><select name="section_id" aria-label="Gantt başlığı"
                            onchange="this.form.submit()">
                            <option value="">Başlıksız</option><?php foreach (
    $sections
    as $section
): ?><option value="<?= (int) $section["id"] ?>" <?= (int) ($item["section_id"] ?? 0) ===
(int) $section["id"]
    ? "selected"
    : "" ?>><?= esc(
    $section["name"],
) ?></option><?php endforeach; ?>
                        </select></form>
                    <details class="gantt-edit">
                        <summary>Tarihleri düzenle</summary>
                        <form class="gantt-edit-form" method="post" action="<?= site_url(
    "projects/" . $project["id"] . "/items/" . $item["id"] . "/schedule",
) ?>"><?= csrf_field() ?><input type="date" name="start_date" value="<?= esc(
    $item["start_date"],
) ?>" aria-label="Başlangıç tarihi"><input type="date" name="due_date" value="<?= esc(
    $item["due_date"],
) ?>" aria-label="Bitiş tarihi"><button class="button" type="submit">Kaydet</button></form>
                    </details><?php endif; ?>
                </div>
                <div class="gantt-calendar"><span class="gantt-bar <?= $statusClass ?>"
                        style="grid-column:<?= $startColumn ?> / <?= $endColumn ?>" title="<?= esc(
    $item["title"],
    "attr",
) ?>"><?= esc($item["title"]) ?></span></div>
            </div>
            <?php
endforeach;
endforeach;
?>
        </div>
    </div><?php endif; ?>
    <?php if (
    $unscheduled !== []
): ?><section class="gantt-backlog">
        <h3>Tarihi belirlenmemiş işler · <?= count(
    $unscheduled,
) ?></h3>
        <div class="gantt-backlog-list"><?php foreach (
    $unscheduled
    as $item
): ?><article class="gantt-backlog-card"><strong><?= esc($item["title"]) ?></strong><small><?= esc(
    $item["assignee_username"] ?: "Atanmamış",
) ?></small><?php if (
    $isOwner
): ?><form class="gantt-section-select" method="post" action="<?= site_url(
    "projects/" . $project["id"] . "/items/" . $item["id"] . "/section",
) ?>"><?= csrf_field() ?><select name="section_id" aria-label="Gantt başlığı"
                        onchange="this.form.submit()">
                        <option value="">Başlıksız</option><?php foreach (
    $sections
    as $section
): ?><option value="<?= (int) $section["id"] ?>" <?= (int) ($item["section_id"] ?? 0) ===
(int) $section["id"]
    ? "selected"
    : "" ?>><?= esc(
    $section["name"],
) ?></option><?php endforeach; ?>
                    </select></form>
                <details class="gantt-edit">
                    <summary>Şemaya ekle</summary>
                    <form class="gantt-edit-form" method="post" action="<?= site_url(
    "projects/" . $project["id"] . "/items/" . $item["id"] . "/schedule",
) ?>"><?= csrf_field() ?><input type="date" name="start_date" aria-label="Başlangıç tarihi"><input
                            type="date" name="due_date" aria-label="Bitiş tarihi"><button
                            class="button" type="submit">Ekle</button></form>
                </details><?php endif; ?>
            </article><?php endforeach; ?></div>
    </section><?php endif; ?>
</section>
<script>
    (() => {
        const button = document.querySelector('[data-gantt-export]');
        if (!button) return;
        button.addEventListener('click', () => {
            document.body.classList.add('gantt-printing');
            window.print()
        });
        window.addEventListener('afterprint', () => document.body.classList.remove(
            'gantt-printing'))
    })()
</script>