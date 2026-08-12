<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    .dashboard-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 26px;
        margin-bottom: 24px;
        border-radius: 16px;
        background: linear-gradient(135deg, #111827, #1e3a8a);
        color: #fff;
    }

    .dashboard-hero h1 { margin: 0 0 8px; }
    .dashboard-hero p { margin: 0; color: #dbeafe; }
    .dashboard-hero .button { white-space: nowrap; background: #fff; color: #111827; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }

    .stat-card {
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
    }

    .stat-label { color: #6b7280; font-size: 14px; }
    .stat-value { display: block; margin-top: 8px; font-size: 30px; font-weight: 700; }

    .dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(260px, .7fr);
        gap: 24px;
    }

    .panel {
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .panel-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 14px;
    }

    .panel-title h2 { margin: 0; font-size: 20px; }
    .panel-title a { color: #2563eb; text-decoration: none; }

    .latest-notes { padding: 0; margin: 0; list-style: none; }
    .latest-notes li { padding: 13px 0; border-bottom: 1px solid #e5e7eb; }
    .latest-notes li:last-child { border-bottom: 0; }
    .latest-notes a { color: #111827; font-weight: 700; text-decoration: none; }
    .note-meta { margin-top: 5px; color: #6b7280; font-size: 13px; }

    .quick-links { display: grid; gap: 10px; }
    .quick-link {
        display: block;
        padding: 14px;
        border-radius: 10px;
        background: #f3f4f6;
        color: #111827;
        text-decoration: none;
        font-weight: 700;
    }
    .quick-link:hover { background: #e5e7eb; }
    .coming-soon { color: #9ca3af; cursor: default; }

    .overview-grid { display:grid; grid-template-columns:minmax(300px,.8fr) minmax(0,1.2fr); gap:24px; margin-bottom:24px; }
    .mini-calendar-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; }
    .mini-calendar-head h2 { margin:0; font-size:20px; }
    .mini-calendar { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
    .mini-weekday { padding:4px 0; color:#6b7280; text-align:center; font-size:11px; font-weight:700; }
    .mini-day { position:relative; display:grid; place-items:center; min-height:34px; border-radius:8px; color:#374151; font-size:13px; text-decoration:none; }
    .mini-day.outside { color:#cbd5e1; }
    .mini-day.today { background:#111827; color:#fff; font-weight:700; }
    .mini-day.has-event:not(.today) { background:#eff6ff; color:#1d4ed8; font-weight:700; }
    .mini-dots { position:absolute; bottom:3px; display:flex; gap:2px; }
    .mini-dot { width:4px; height:4px; border-radius:50%; background:#2563eb; }
    .mini-dot.journal { background:#9333ea; }
    .dashboard-habits { display:grid; gap:10px; }
    .dashboard-habit { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:12px; padding:12px; border-radius:10px; background:#f3f4f6; }
    .dashboard-habit.done { background:#dcfce7; }
    .dashboard-habit strong { display:block; }
    .dashboard-habit small { color:#6b7280; }
    .dashboard-habit .button { padding:8px 10px; }
    .dashboard-habit-goal { color:#166534; font-size:13px; font-weight:700; white-space:nowrap; }

    @media (max-width: 850px) {
        .stats-grid { grid-template-columns: 1fr; }
        .dashboard-grid { grid-template-columns: 1fr; }
        .overview-grid { grid-template-columns:1fr; }
        .dashboard-hero { align-items: flex-start; flex-direction: column; }
    }
</style>

<section class="dashboard-hero">
    <div>
        <h1>Hoş geldiniz, <?= esc(session()->get('username')) ?>!</h1>
        <p>Not paylaşın, çalışma sürenizi takip edin ve güne odaklanın.</p>
    </div>
    <a class="button" href="<?= site_url('notes/create') ?>">Yeni Not Oluştur</a>
</section>

<section class="stats-grid" aria-label="Genel özet">
    <article class="stat-card">
        <span class="stat-label">Notlarım</span>
        <span class="stat-value"><?= $ownNoteCount ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Public notlar</span>
        <span class="stat-value"><?= $publicNoteCount ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label"><?= $isAdmin ? 'Tüm görünür notlar' : 'Erişebildiğim notlar' ?></span>
        <span class="stat-value"><?= $visibleCount ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Bekleyen görevler</span>
        <span class="stat-value"><?= $pendingTaskCount ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Bugün bitecek</span>
        <span class="stat-value"><?= $dueTodayCount ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Günlük kayıtlarım</span>
        <span class="stat-value"><?= $journalEntryCount ?></span>
    </article>
</section>

<div class="overview-grid">
    <section class="panel">
        <?php $monthNames=[1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık']; ?>
        <div class="mini-calendar-head"><h2><?= $monthNames[(int)$calendarFirstDay->format('n')] ?> <?= $calendarFirstDay->format('Y') ?></h2><a href="<?= site_url('calendar') ?>">Büyüt</a></div>
        <div class="mini-calendar" aria-label="Bu ayın takvimi">
            <?php foreach(['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'] as $weekday): ?><div class="mini-weekday"><?= $weekday ?></div><?php endforeach; ?>
            <?php $gridStart=$calendarFirstDay->modify('-'.((int)$calendarFirstDay->format('N')-1).' days'); for($dayIndex=0;$dayIndex<42;$dayIndex++): $calendarDate=$gridStart->modify('+'.$dayIndex.' days'); $dateKey=$calendarDate->format('Y-m-d'); $dayEvents=$calendarEvents[$dateKey] ?? []; ?>
                <a class="mini-day <?= $calendarDate->format('m')!==$calendarFirstDay->format('m')?'outside':'' ?> <?= $dateKey===date('Y-m-d')?'today':'' ?> <?= $dayEvents?'has-event':'' ?>" href="<?= site_url('calendar') ?>?month=<?= $calendarDate->format('Y-m') ?>" title="<?= esc((string)(($dayEvents['tasks'] ?? 0).' görev, '.($dayEvents['journals'] ?? 0).' günlük'), 'attr') ?>">
                    <?= $calendarDate->format('j') ?>
                    <?php if($dayEvents): ?><span class="mini-dots"><?php if(!empty($dayEvents['tasks'])): ?><i class="mini-dot"></i><?php endif; ?><?php if(!empty($dayEvents['journals'])): ?><i class="mini-dot journal"></i><?php endif; ?></span><?php endif; ?>
                </a>
            <?php endfor; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-title"><h2>Bu Dönemin Alışkanlıkları</h2><a href="<?= site_url('habits') ?>">Tümünü gör</a></div>
        <?php if(empty($dashboardHabits)): ?><p style="color:#6b7280">Henüz etkin bir alışkanlık yok. <a href="<?= site_url('habits/create') ?>">İlkini ekleyin.</a></p><?php else: ?>
            <div class="dashboard-habits">
                <?php foreach(array_slice($dashboardHabits,0,5) as $habit): ?>
                    <div class="dashboard-habit <?= $habit['completed']?'done':'' ?>">
                        <div><strong><?= esc($habit['title']) ?></strong><small><?= esc($habit['goal_label']) ?> · <?= (int)$habit['completed_count'] ?>/<?= (int)$habit['target_count'] ?> tamamlandı</small></div>
                        <?php if($habit['completed'] && !$habit['completed_today']): ?><span class="dashboard-habit-goal">✓ Hedef tamam</span><?php else: ?><form method="post" action="<?= site_url('habits/'.$habit['id'].'/complete') ?>"><?= csrf_field() ?><button class="button <?= $habit['completed_today']?'secondary':'' ?>" type="submit"><?= $habit['completed_today']?'Bugünü Geri Al':'Bugün Yaptım' ?></button></form><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="dashboard-grid">
    <section class="panel">
        <div class="panel-title">
            <h2>Son Notlar</h2>
            <a href="<?= site_url('notes') ?>">Tümünü gör</a>
        </div>

        <?php if (empty($latestNotes)): ?>
            <p style="color:#6b7280">Henüz görüntülenecek bir not yok.</p>
        <?php else: ?>
            <ul class="latest-notes">
                <?php foreach ($latestNotes as $note): ?>
                    <li>
                        <a href="<?= site_url('notes/' . $note['id']) ?>"><?= esc($note['title']) ?></a>
                        <div class="note-meta">
                            <?= (int) $note['user_id'] === $userId ? 'Sizin notunuz' : esc($note['owner_name'] ?? 'Bilinmiyor') ?> ·
                            <?= (int) $note['is_public'] === 1 ? 'Public' : 'Özel' ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <div class="panel-title"><h2>Hızlı Erişim</h2></div>
        <nav class="quick-links" aria-label="Hızlı erişim">
            <a class="quick-link" href="<?= site_url('calendar') ?>">Takvimi Aç</a>
            <a class="quick-link" href="<?= site_url('notes') ?>">Notlarımı Aç</a>
            <a class="quick-link" href="<?= site_url('tasks') ?>">Görevlerimi Aç</a>
            <a class="quick-link" href="<?= site_url('habits') ?>">Alışkanlıklarımı Aç</a>
            <a class="quick-link" href="<?= site_url('timer') ?>">Kronometreyi Aç</a>
            <a class="quick-link" href="<?= site_url('journal') ?>">Günlüğümü Aç</a>
            <a class="quick-link" href="<?= site_url('profile') ?>">Profil ve Ayarlar</a>
        </nav>
    </aside>
</div>

<?php if (! empty($reminderTasks)): ?>
<section class="panel" style="margin-top:24px">
    <div class="panel-title"><h2>Yaklaşan ve Geciken Görevler</h2><a href="<?= site_url('calendar') ?>">Takvimde gör</a></div>
    <ul class="latest-notes">
        <?php foreach ($reminderTasks as $task): ?>
            <?php $deadline = $task['due_date'].'T'.($task['due_time'] ?: '23:59:59'); $overdue = strtotime($deadline) < time(); ?>
            <li><a href="<?= site_url('tasks/'.$task['id'].'/edit') ?>"><?= esc($task['title']) ?></a><div class="note-meta" style="<?= $overdue?'color:#dc2626;font-weight:700':'' ?>"><?= $overdue?'Gecikti':'Bitiş' ?>: <?= date('d.m.Y H:i',strtotime($deadline)) ?></div></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php if ((int) session()->get('notifications_enabled') === 1): ?>
<button id="notification-permission" class="button secondary" type="button" style="margin-top:16px;display:none">Bildirim İzni Ver</button>
<script>
(() => {
    const tasks = <?= json_encode(array_map(static fn($task)=>['id'=>(int)$task['id'],'title'=>$task['title'],'deadline'=>$task['due_date'].'T'.($task['due_time'] ?: '23:59:59')], $reminderTasks), JSON_UNESCAPED_UNICODE|JSON_HEX_TAG) ?>;
    if (!('Notification' in window)) return;
    const permissionButton = document.getElementById('notification-permission');
    const notify = () => tasks.forEach(task => {
        const remaining = new Date(task.deadline).getTime() - Date.now();
        const key = `task-reminder-${task.id}-${task.deadline}`;
        if (remaining <= 86400000 && !localStorage.getItem(key)) {
            new Notification('Görev hatırlatıcısı', { body: `${task.title} · ${remaining < 0 ? 'Süresi geçti' : '24 saatten az kaldı'}` });
            localStorage.setItem(key, '1');
        }
    });
    if (Notification.permission === 'granted') notify();
    else if (Notification.permission === 'default') {
        permissionButton.style.display = 'inline-block';
        permissionButton.addEventListener('click', () => Notification.requestPermission().then(permission => { permissionButton.style.display='none'; if (permission === 'granted') notify(); }));
    }
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
