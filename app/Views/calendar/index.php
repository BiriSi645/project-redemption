<?= $this->extend("layouts/main") ?>

<?= $this->section("content") ?>
<style>
    .calendar-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }

    .calendar-head h1 {
        margin: 0;
    }

    .calendar-nav {
        display: flex;
        gap: 8px;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(120px, 1fr));
        border-top: 1px solid #e5e7eb;
        border-left: 1px solid #e5e7eb;
        overflow: auto;
    }

    .calendar-weekday,
    .calendar-day {
        border-right: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
    }

    .calendar-weekday {
        padding: 10px;
        background: #f3f4f6;
        text-align: center;
        font-weight: 700;
    }

    .calendar-day {
        min-height: 135px;
        padding: 9px;
        background: #fff;
    }

    .calendar-day.outside {
        background: #f9fafb;
    }

    .calendar-day.today {
        box-shadow: inset 0 0 0 2px #2563eb;
    }

    .calendar-day.selected {
        box-shadow: inset 0 0 0 3px #f59e0b;
        background: #fffbeb;
    }

    .calendar-day.today.selected {
        box-shadow: inset 0 0 0 2px #2563eb, inset 0 0 0 5px #fbbf24;
    }

    .day-number {
        display: block;
        margin-bottom: 7px;
        color: #6b7280;
        font-weight: 700;
    }

    .calendar-event {
        display: block;
        padding: 6px 7px;
        margin-bottom: 5px;
        border-radius: 7px;
        overflow: hidden;
        color: #111827;
        text-decoration: none;
        font-size: 12px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .calendar-event.task {
        background: #dbeafe;
        color: #1e40af;
    }

    .calendar-event.completed {
        background: #dcfce7;
        color: #166534;
        text-decoration: line-through;
    }

    .calendar-event.journal {
        background: #f3e8ff;
        color: #6b21a8;
    }

    .calendar-event.reminder {
        display: flex;
        align-items: center;
        gap: 5px;
        background: #fef3c7;
        color: #92400e;
    }

    .calendar-reminder-copy {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .calendar-reminder-open {
        display: block;
        flex: 1;
        min-width: 0;
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        text-align: left;
        cursor: pointer;
    }

    .calendar-reminder-delete {
        margin: 0 0 0 auto;
    }

    .calendar-reminder-delete button {
        display: grid;
        width: 20px;
        height: 20px;
        padding: 0;
        place-items: center;
        border: 0;
        border-radius: 50%;
        background: #fde68a;
        color: #92400e;
        cursor: pointer;
    }

    .calendar-day[data-calendar-date] {
        cursor: pointer;
    }

    .calendar-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 16px;
        color: #6b7280;
        font-size: 13px;
    }

    .calendar-toolbar {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        padding: 13px;
        margin-bottom: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 11px;
        background: #f8fafc
    }

    .calendar-filters,
    .date-jump {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        margin: 0
    }

    .calendar-filter-field {
        width: 185px
    }

    .calendar-filter-field.status {
        width: 170px
    }

    .calendar-filters label,
    .date-jump label {
        display: block;
        margin: 0 0 5px;
        font-size: 12px
    }

    .calendar-filters select,
    .date-jump input {
        width: 100%;
        height: 40px;
        padding: 8px 10px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        font: inherit
    }

    .calendar-filters .button,
    .date-jump-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        padding: 0 13px;
        line-height: 1;
        white-space: nowrap
    }

    .date-jump {
        padding-left: 10px;
        margin-left: auto;
        border-left: 1px solid #d1d5db
    }

    .date-jump-field {
        width: 165px
    }

    .date-jump-button {
        background: #2563eb;
        font-weight: 700
    }

    .date-jump-button:hover {
        background: #1d4ed8
    }

    .calendar-filter-summary {
        margin: -5px 0 16px;
        color: #6b7280;
        font-size: 13px
    }

    @media(max-width:1050px) {
        .calendar-toolbar {
            align-items: stretch;
            flex-wrap: wrap
        }

        .date-jump {
            margin-left: 0
        }
    }

    @media(max-width:900px) {
        .calendar-scroll {
            overflow-x: auto
        }

        .calendar-grid {
            min-width: 850px
        }
    }

    @media(max-width:650px) {
        .calendar-head {
            align-items: stretch;
            flex-direction: column
        }

        .calendar-head h1 {
            text-align: center
        }

        .calendar-nav {
            justify-content: space-between
        }

        .calendar-toolbar,
        .calendar-filters,
        .date-jump {
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 100%
        }

        .calendar-filter-field,
        .calendar-filter-field.status,
        .date-jump-field {
            width: auto
        }

        .date-jump {
            padding: 10px 0 0;
            border-top: 1px solid #d1d5db;
            border-left: 0
        }

        .calendar-filters .button,
        .date-jump-button {
            text-align: center
        }
    }

    /* Keep the filters within the available content width. */
    .calendar-toolbar {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
    }

    .calendar-filters {
        display: grid;
        grid-template-columns: minmax(150px, 1fr) minmax(145px, 1fr) auto auto;
        width: 100%;
        min-width: 0;
    }

    .calendar-filter-field,
    .calendar-filter-field.status,
    .date-jump-field {
        width: auto;
        min-width: 0;
    }

    .calendar-filter-field select,
    .date-jump-field input {
        width: 100%;
        min-width: 0;
    }

    .calendar-filters .button {
        box-sizing: border-box;
        font: inherit;
        font-weight: 400;
    }

    .calendar-apply-button {
        border: 1px solid #1d4ed8;
        background: #2563eb;
    }

    .calendar-apply-button:hover {
        border-color: #1e40af;
        background: #1d4ed8;
    }

    .date-jump {
        display: grid;
        grid-template-columns: minmax(145px, 165px) auto;
        min-width: 0;
    }

    @media(max-width:1100px) {
        .calendar-toolbar {
            grid-template-columns: minmax(0, 1fr)
        }

        .date-jump {
            grid-template-columns: minmax(0, 1fr) auto;
            width: 100%;
            margin-left: 0;
            padding: 10px 0 0;
            border-left: 0;
            border-top: 1px solid #d1d5db;
        }
    }

    @media(max-width:700px) {

        .calendar-toolbar,
        .calendar-filters {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }

        .calendar-toolbar {
            grid-template-columns: minmax(0, 1fr)
        }

        .date-jump {
            grid-template-columns: minmax(0, 1fr) auto
        }
    }

    @media(max-width:430px) {

        .calendar-filters,
        .date-jump {
            grid-template-columns: minmax(0, 1fr)
        }

        .calendar-filters .button,
        .date-jump .button {
            width: 100%;
            justify-content: center
        }
    }

    .reminder-dialog {
        width: min(460px, calc(100% - 28px));
        padding: 0;
        border: 0;
        border-radius: 15px;
        background: #fff;
        color: #111827;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .35)
    }

    .reminder-dialog::backdrop {
        background: rgba(15, 23, 42, .62)
    }

    .reminder-dialog-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 20px 22px;
        border-bottom: 1px solid #e5e7eb
    }

    .reminder-dialog-head h2 {
        margin: 0 0 5px
    }

    .reminder-dialog-head p {
        margin: 0;
        color: #6b7280;
        font-size: 13px
    }

    .reminder-dialog-close {
        display: grid;
        width: 36px;
        height: 36px;
        padding: 0;
        place-items: center;
        border: 0;
        border-radius: 50%;
        background: #f3f4f6;
        font-size: 21px;
        cursor: pointer
    }

    .reminder-form {
        display: grid;
        gap: 14px;
        padding: 22px
    }

    .reminder-form label {
        margin: 0
    }

    .reminder-form input,
    .reminder-form textarea {
        width: 100%
    }

    .reminder-form textarea {
        min-height: 90px;
        resize: vertical
    }

    .reminder-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px
    }

    .calendar-add-reminder {
        white-space: nowrap
    }

    html[data-theme="dark"] .reminder-dialog {
        background: #1e293b;
        color: #e5e7eb
    }

    html[data-theme="dark"] .reminder-dialog-head {
        border-color: #334155
    }

    html[data-theme="dark"] .reminder-dialog-head p {
        color: #94a3b8
    }

    html[data-theme="dark"] .reminder-dialog-close {
        background: #334155;
        color: #fff
    }

    html[data-theme="dark"] .calendar-event.reminder {
        background: #422006;
        color: #fde68a
    }

    html[data-theme="dark"] .calendar-reminder-delete button {
        background: #78350f;
        color: #fef3c7
    }

    @media(max-width:500px) {
        .reminder-form-actions {
            display: grid
        }

        .reminder-form-actions .button {
            width: 100%;
            text-align: center
        }
    }

    .reminder-detail-body {
        display: grid;
        gap: 17px;
        padding: 22px
    }

    .reminder-detail-field small {
        display: block;
        margin-bottom: 5px;
        color: #6b7280;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em
    }

    .reminder-detail-field p {
        margin: 0;
        line-height: 1.6;
        white-space: pre-wrap;
        overflow-wrap: anywhere
    }

    .reminder-detail-empty {
        color: #9ca3af;
        font-style: italic
    }

    html[data-theme="dark"] .reminder-detail-field small {
        color: #94a3b8
    }
</style>

<?php
$filterQuery = ["type" => $activeType, "status" => $activeTaskStatus];
$previousQuery = http_build_query(["month" => $previous] + $filterQuery);
$nextQuery = http_build_query(["month" => $next] + $filterQuery);
$todayQuery = http_build_query($filterQuery);
?>

<div class="calendar-head">
    <a class="button secondary" href="<?= site_url("calendar") ?>?<?= esc(
    $previousQuery,
    "attr",
) ?>">← Önceki</a>
    <h1><?= esc($monthLabel) ?></h1>
    <div class="calendar-nav">
        <button class="button calendar-add-reminder" type="button" data-reminder-open>+
            Hatırlatıcı</button>
        <a class="button secondary" href="<?= site_url("calendar") ?>?<?= esc(
    $todayQuery,
    "attr",
) ?>">Bugün</a>
        <a class="button secondary" href="<?= site_url("calendar") ?>?<?= esc(
    $nextQuery,
    "attr",
) ?>">Sonraki →</a>
    </div>
</div>

<div class="calendar-toolbar">
    <form class="calendar-filters" method="get" action="<?= site_url("calendar") ?>">
        <input type="hidden" name="month" value="<?= esc($firstDay->format("Y-m"), "attr") ?>">
        <div class="calendar-filter-field"><label for="calendar-type">İçerik</label><select
                id="calendar-type" name="type">
                <option value="all" <?= $activeType ===
        "all"
            ? "selected"
            : "" ?>>Tüm içerikler</option>
                <option value="tasks" <?= $activeType === "tasks"
    ? "selected"
    : "" ?>>Sadece görevler</option>
                <option value="journals" <?= $activeType === "journals"
    ? "selected"
    : "" ?>>Sadece günlükler</option>
                <option value="reminders" <?= $activeType === "reminders"
    ? "selected"
    : "" ?>>Sadece hatırlatıcılar</option>
            </select></div>
        <div class="calendar-filter-field status"><label for="calendar-status">Görev
                durumu</label><select id="calendar-status" name="status">
                <option value="all" <?= $activeTaskStatus ===
        "all"
            ? "selected"
            : "" ?>>Tüm görevler</option>
                <option value="pending" <?= $activeTaskStatus === "pending"
    ? "selected"
    : "" ?>>Sadece bekleyenler</option>
                <option value="completed" <?= $activeTaskStatus ===
"completed"
    ? "selected"
    : "" ?>>Sadece tamamlananlar</option>
            </select></div>
        <button class="button calendar-apply-button" type="submit">Uygula</button><a
            class="button secondary" href="<?= site_url(
            "calendar",
        ) ?>?month=<?= esc($firstDay->format("Y-m"), "attr") ?>">Temizle</a>
    </form>
    <form class="date-jump" method="get" action="<?= site_url("calendar") ?>">
        <input type="hidden" name="type" value="<?= esc(
            $activeType,
            "attr",
        ) ?>"><input type="hidden" name="status" value="<?= esc($activeTaskStatus, "attr") ?>">
        <div class="date-jump-field"><label for="calendar-date">Tarihe git</label><input
                id="calendar-date" type="date" name="date" value="<?= esc(
            $selectedDate,
            "attr",
        ) ?>" required></div><button class="button date-jump-button" type="submit">Git →</button>
    </form>
</div>
<p class="calendar-filter-summary">Bu görünümde <?= $taskCount ?> görev, <?= $journalCount ?> günlük
    kaydı ve <?= $reminderCount ?> hatırlatıcı gösteriliyor.</p>

<div class="calendar-scroll">
    <div class="calendar-grid">
        <?php foreach (
            ["Pzt", "Sal", "Çar", "Per", "Cum", "Cmt", "Paz"]
            as $weekday
        ): ?><div class="calendar-weekday"><?= $weekday ?></div><?php endforeach; ?>
        <?php
        $gridStart = $firstDay->modify("-" . ((int) $firstDay->format("N") - 1) . " days");
        for ($index = 0; $index < 42; $index++):

            $date = $gridStart->modify("+" . $index . " days");
            $dateKey = $date->format("Y-m-d");
            $outside = $date->format("m") !== $firstDay->format("m");
            ?>
        <div class="calendar-day <?= $outside ? "outside" : "" ?> <?= $dateKey === date("Y-m-d")
     ? "today"
     : "" ?> <?= $dateKey === $selectedDate ? "selected" : "" ?>" data-calendar-date="<?= esc(
    $dateKey,
    "attr",
) ?>">
            <span class="day-number"><?= $date->format("j") ?></span>
            <?php foreach ($events[$dateKey] ?? [] as $event): ?>
            <?php if ($event["type"] === "task"):
                        $task = $event["data"]; ?>
            <a class="calendar-event task <?= $task["status"] === "completed"
                            ? "completed"
                            : "" ?>" href="<?= site_url(
    "tasks/" . $task["id"] . "/edit",
) ?>" title="<?= esc($task["title"], "attr") ?>">
                <?=
                            ($task["due_time"] ? esc(substr($task["due_time"], 0, 5)) . " · " : "") .
                            esc($task["title"])
                            ?>
            </a>
            <?php
                    elseif ($event["type"] === "journal"):
                        $entry = $event["data"]; ?>
            <a class="calendar-event journal" href="<?= site_url(
                            "journal/" . $entry["id"],
                        ) ?>" title="<?= esc($entry["title"], "attr") ?>">📓 <?= esc(
    $entry["title"],
) ?></a>
            <?php
                    else:
                        $reminder = $event["data"]; ?>
            <div class="calendar-event reminder"><button class="calendar-reminder-open"
                    type="button" data-reminder-detail data-reminder-title="<?= esc(
                            $reminder["title"],
                            "attr",
                        ) ?>" data-reminder-time="<?= esc(
    date("d.m.Y · H:i", strtotime($reminder["remind_at"])),
    "attr",
) ?>" data-reminder-description="<?= esc(
    $reminder["details"] ?? "",
    "attr",
) ?>"><span class="calendar-reminder-copy">⏰ <?= esc(
    substr($reminder["remind_at"], 11, 5),
) ?> · <?= esc(
     $reminder["title"],
 ) ?></span></button>
                <form class="calendar-reminder-delete" method="post" action="<?= site_url(
    "calendar/reminders/" . $reminder["id"] . "/delete",
) ?>" onsubmit="return confirm('Bu hatırlatıcı silinsin mi?')"><?= csrf_field() ?><button
                        type="submit" aria-label="Hatırlatıcıyı sil">×</button></form>
            </div>
            <?php
                    endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
        endfor;
        ?>
    </div>
</div>
<div class="calendar-legend"><?php
if (
    in_array($activeType, ["all", "tasks"], true)
): ?><span>🔵 Bekleyen görev</span><span>🟢 Tamamlanan görev</span><?php endif;
if (in_array($activeType, ["all", "journals"], true)): ?><span>🟣 Günlük kaydı</span><?php endif;
if (in_array($activeType, ["all", "reminders"], true)): ?><span>🟡 Hatırlatıcı</span><?php endif;
?></div>
<dialog class="reminder-dialog" data-reminder-dialog>
    <header class="reminder-dialog-head">
        <div>
            <h2>Hatırlatıcı oluştur</h2>
            <p>Takvimde seçtiğin gün ve saatte seni uyarır.</p>
        </div><button class="reminder-dialog-close" type="button" aria-label="Kapat"
            data-reminder-close>×</button>
    </header>
    <form class="reminder-form" method="post" action="<?= site_url("calendar/reminders") ?>">
        <?= csrf_field() ?>
        <label>Başlık<input type="text" name="title" value="<?= esc(
            old("title"),
        ) ?>" minlength="2" maxlength="160" required
                placeholder="Örn. toplantı notlarını hazırla"></label>
        <label>Tarih ve saat<input type="datetime-local" name="remind_at" value="<?= esc(
            old("remind_at"),
        ) ?>" required data-reminder-datetime></label>
        <label>Açıklama (isteğe bağlı)<textarea name="details" maxlength="1000"
                placeholder="Hatırlatıcıyla ilgili kısa bir not..."><?= esc(
            old("details"),
        ) ?></textarea></label>
        <div class="reminder-form-actions"><button class="button secondary" type="button"
                data-reminder-close>Vazgeç</button><button class="button"
                type="submit">Hatırlatıcıyı kaydet</button></div>
    </form>
</dialog>
<dialog class="reminder-dialog" data-reminder-detail-dialog>
    <header class="reminder-dialog-head">
        <div>
            <h2 data-reminder-detail-title>Hatırlatıcı</h2>
            <p>Hatırlatıcı detayları</p>
        </div><button class="reminder-dialog-close" type="button" aria-label="Kapat"
            data-reminder-detail-close>×</button>
    </header>
    <div class="reminder-detail-body">
        <div class="reminder-detail-field"><small>Tarih ve saat</small>
            <p data-reminder-detail-time></p>
        </div>
        <div class="reminder-detail-field"><small>Açıklama</small>
            <p data-reminder-detail-description></p>
        </div>
        <div class="reminder-form-actions"><button class="button secondary" type="button"
                data-reminder-detail-close>Kapat</button></div>
    </div>
</dialog>
<script>
    (() => {
        const dialog = document.querySelector('[data-reminder-dialog]');
        const dateTimeInput = dialog?.querySelector('[data-reminder-datetime]');
        if (!dialog || !dateTimeInput) return;

        const formatLocalDateTime = value =>
            `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}T${String(value.getHours()).padStart(2, '0')}:${String(value.getMinutes()).padStart(2, '0')}`;
        const defaultTimeFor = date => {
            const now = new Date();
            const today =
                `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
            if (date !== today) return `${date}T09:00`;
            const next = new Date(now.getTime() + 60 * 60 * 1000);
            next.setSeconds(0, 0);
            next.setMinutes(Math.ceil(next.getMinutes() / 5) * 5);
            return formatLocalDateTime(next);
        };
        const openDialog = date => {
            if (!dateTimeInput.value) dateTimeInput.value = defaultTimeFor(date);
            dialog.showModal();
            dialog.querySelector('input[name="title"]')?.focus();
        };

        document.querySelectorAll('[data-calendar-date]').forEach(day => day.addEventListener(
            'dblclick', event => {
                if (event.target.closest('.calendar-event')) return;
                dateTimeInput.value = defaultTimeFor(day.dataset.calendarDate);
                openDialog(day.dataset.calendarDate);
            }));
        document.querySelector('[data-reminder-open]')?.addEventListener('click', () => {
            const selected = document.querySelector('.calendar-day.selected')?.dataset
                .calendarDate;
            const now = new Date();
            const today =
                `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
            openDialog(selected || today);
        });
        dialog.querySelectorAll('[data-reminder-close]').forEach(button => button
            .addEventListener('click', () => dialog.close()));
        dialog.addEventListener('click', event => {
            if (event.target === dialog) dialog.close();
        });
        if (dateTimeInput.value) dialog.showModal();
    })();
</script>
<script>
    (() => {
        const dialog = document.querySelector('[data-reminder-detail-dialog]');
        if (!dialog) return;
        const title = dialog.querySelector('[data-reminder-detail-title]');
        const time = dialog.querySelector('[data-reminder-detail-time]');
        const description = dialog.querySelector('[data-reminder-detail-description]');

        document.querySelectorAll('[data-reminder-detail]').forEach(button => button
            .addEventListener('click', () => {
                title.textContent = button.dataset.reminderTitle || 'Hatırlatıcı';
                time.textContent = button.dataset.reminderTime || 'Tarih belirtilmemiş';
                description.textContent = button.dataset.reminderDescription ||
                    'Açıklama eklenmemiş.';
                description.classList.toggle('reminder-detail-empty', !button.dataset
                    .reminderDescription);
                dialog.showModal();
            }));
        dialog.querySelectorAll('[data-reminder-detail-close]').forEach(button => button
            .addEventListener('click', () => dialog.close()));
        dialog.addEventListener('click', event => {
            if (event.target === dialog) dialog.close();
        });
    })();
</script>
<?= $this->endSection() ?>
