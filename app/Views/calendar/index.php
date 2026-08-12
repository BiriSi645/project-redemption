<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    .calendar-head { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:20px; }
    .calendar-head h1 { margin:0; }
    .calendar-nav { display:flex; gap:8px; }
    .calendar-grid { display:grid; grid-template-columns:repeat(7,minmax(120px,1fr)); border-top:1px solid #e5e7eb; border-left:1px solid #e5e7eb; overflow:auto; }
    .calendar-weekday, .calendar-day { border-right:1px solid #e5e7eb; border-bottom:1px solid #e5e7eb; }
    .calendar-weekday { padding:10px; background:#f3f4f6; text-align:center; font-weight:700; }
    .calendar-day { min-height:135px; padding:9px; background:#fff; }
    .calendar-day.outside { background:#f9fafb; }
    .calendar-day.today { box-shadow:inset 0 0 0 2px #2563eb; }
    .calendar-day.selected { box-shadow:inset 0 0 0 3px #f59e0b; background:#fffbeb; }
    .calendar-day.today.selected { box-shadow:inset 0 0 0 2px #2563eb,inset 0 0 0 5px #fbbf24; }
    .day-number { display:block; margin-bottom:7px; color:#6b7280; font-weight:700; }
    .calendar-event { display:block; padding:6px 7px; margin-bottom:5px; border-radius:7px; overflow:hidden; color:#111827; text-decoration:none; font-size:12px; text-overflow:ellipsis; white-space:nowrap; }
    .calendar-event.task { background:#dbeafe; color:#1e40af; }
    .calendar-event.completed { background:#dcfce7; color:#166534; text-decoration:line-through; }
    .calendar-event.journal { background:#f3e8ff; color:#6b21a8; }
    .calendar-legend { display:flex; flex-wrap:wrap; gap:14px; margin-top:16px; color:#6b7280; font-size:13px; }
    .calendar-toolbar{display:flex;align-items:flex-end;gap:10px;padding:13px;margin-bottom:12px;border:1px solid #e5e7eb;border-radius:11px;background:#f8fafc}.calendar-filters,.date-jump{display:flex;align-items:flex-end;gap:8px;margin:0}.calendar-filter-field{width:185px}.calendar-filter-field.status{width:170px}.calendar-filters label,.date-jump label{display:block;margin:0 0 5px;font-size:12px}.calendar-filters select,.date-jump input{width:100%;height:40px;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font:inherit}.calendar-filters .button,.date-jump-button{display:inline-flex;align-items:center;justify-content:center;height:40px;padding:0 13px;line-height:1;white-space:nowrap}.date-jump{padding-left:10px;margin-left:auto;border-left:1px solid #d1d5db}.date-jump-field{width:165px}.date-jump-button{background:#2563eb;font-weight:700}.date-jump-button:hover{background:#1d4ed8}.calendar-filter-summary{margin:-5px 0 16px;color:#6b7280;font-size:13px}@media(max-width:1050px){.calendar-toolbar{align-items:stretch;flex-wrap:wrap}.date-jump{margin-left:0}}@media(max-width:900px){.calendar-scroll{overflow-x:auto}.calendar-grid{min-width:850px}}@media(max-width:650px){.calendar-head{align-items:stretch;flex-direction:column}.calendar-head h1{text-align:center}.calendar-nav{justify-content:space-between}.calendar-toolbar,.calendar-filters,.date-jump{display:grid;grid-template-columns:1fr 1fr;width:100%}.calendar-filter-field,.calendar-filter-field.status,.date-jump-field{width:auto}.date-jump{padding:10px 0 0;border-top:1px solid #d1d5db;border-left:0}.calendar-filters .button,.date-jump-button{text-align:center}}
</style>

<?php
    $filterQuery = ['type' => $activeType, 'status' => $activeTaskStatus];
    $previousQuery = http_build_query(['month' => $previous] + $filterQuery);
    $nextQuery = http_build_query(['month' => $next] + $filterQuery);
    $todayQuery = http_build_query($filterQuery);
?>

<div class="calendar-head">
    <a class="button secondary" href="<?= site_url('calendar') ?>?<?= esc($previousQuery, 'attr') ?>">← Önceki</a>
    <h1><?= esc($monthLabel) ?></h1>
    <div class="calendar-nav">
        <a class="button secondary" href="<?= site_url('calendar') ?>?<?= esc($todayQuery, 'attr') ?>">Bugün</a>
        <a class="button secondary" href="<?= site_url('calendar') ?>?<?= esc($nextQuery, 'attr') ?>">Sonraki →</a>
    </div>
</div>

<div class="calendar-toolbar">
    <form class="calendar-filters" method="get" action="<?= site_url('calendar') ?>">
        <input type="hidden" name="month" value="<?= esc($firstDay->format('Y-m'), 'attr') ?>">
        <div class="calendar-filter-field"><label for="calendar-type">İçerik</label><select id="calendar-type" name="type"><option value="all" <?= $activeType==='all'?'selected':'' ?>>Görevler ve günlükler</option><option value="tasks" <?= $activeType==='tasks'?'selected':'' ?>>Sadece görevler</option><option value="journals" <?= $activeType==='journals'?'selected':'' ?>>Sadece günlükler</option></select></div>
        <div class="calendar-filter-field status"><label for="calendar-status">Görev durumu</label><select id="calendar-status" name="status"><option value="all" <?= $activeTaskStatus==='all'?'selected':'' ?>>Tüm görevler</option><option value="pending" <?= $activeTaskStatus==='pending'?'selected':'' ?>>Sadece bekleyenler</option><option value="completed" <?= $activeTaskStatus==='completed'?'selected':'' ?>>Sadece tamamlananlar</option></select></div>
        <button class="button" type="submit">Uygula</button><a class="button secondary" href="<?= site_url('calendar') ?>?month=<?= esc($firstDay->format('Y-m'), 'attr') ?>">Temizle</a>
    </form>
    <form class="date-jump" method="get" action="<?= site_url('calendar') ?>">
        <input type="hidden" name="type" value="<?= esc($activeType, 'attr') ?>"><input type="hidden" name="status" value="<?= esc($activeTaskStatus, 'attr') ?>">
        <div class="date-jump-field"><label for="calendar-date">Tarihe git</label><input id="calendar-date" type="date" name="date" value="<?= esc($selectedDate, 'attr') ?>" required></div><button class="button date-jump-button" type="submit">Git →</button>
    </form>
</div>
<p class="calendar-filter-summary">Bu görünümde <?= $taskCount ?> görev ve <?= $journalCount ?> günlük kaydı gösteriliyor.</p>

<div class="calendar-scroll">
    <div class="calendar-grid">
        <?php foreach (['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'] as $weekday): ?><div class="calendar-weekday"><?= $weekday ?></div><?php endforeach; ?>
        <?php
            $gridStart = $firstDay->modify('-' . ((int) $firstDay->format('N') - 1) . ' days');
            for ($index = 0; $index < 42; $index++):
                $date = $gridStart->modify('+' . $index . ' days');
                $dateKey = $date->format('Y-m-d');
                $outside = $date->format('m') !== $firstDay->format('m');
        ?>
            <div class="calendar-day <?= $outside ? 'outside' : '' ?> <?= $dateKey === date('Y-m-d') ? 'today' : '' ?> <?= $dateKey === $selectedDate ? 'selected' : '' ?>">
                <span class="day-number"><?= $date->format('j') ?></span>
                <?php foreach ($events[$dateKey] ?? [] as $event): ?>
                    <?php if ($event['type'] === 'task'): $task = $event['data']; ?>
                        <a class="calendar-event task <?= $task['status'] === 'completed' ? 'completed' : '' ?>" href="<?= site_url('tasks/' . $task['id'] . '/edit') ?>" title="<?= esc($task['title'], 'attr') ?>">
                            <?= $task['due_time'] ? esc(substr($task['due_time'],0,5)) . ' · ' : '' ?><?= esc($task['title']) ?>
                        </a>
                    <?php else: $entry = $event['data']; ?>
                        <a class="calendar-event journal" href="<?= site_url('journal/' . $entry['id']) ?>" title="<?= esc($entry['title'], 'attr') ?>">📓 <?= esc($entry['title']) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>
<div class="calendar-legend"><?php if($activeType!=='journals'):?><span>🔵 Bekleyen görev</span><span>🟢 Tamamlanan görev</span><?php endif;?><?php if($activeType!=='tasks'):?><span>🟣 Günlük kaydı</span><?php endif;?></div>
<?= $this->endSection() ?>
