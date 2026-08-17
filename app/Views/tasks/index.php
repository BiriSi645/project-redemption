<?= $this->extend("layouts/main") ?>

<?= $this->section("content") ?>
<style>
    .tasks-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
    }

    .tasks-header h1 {
        margin: 0 0 6px;
    }

    .tasks-header p {
        margin: 0;
        color: #6b7280;
    }

    .task-progress-overview {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 18px;
        padding: 18px 20px;
        margin-bottom: 22px;
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        background: linear-gradient(135deg, #eff6ff, #f5f3ff);
    }

    .task-progress-ring {
        display: grid;
        width: 76px;
        height: 76px;
        place-items: center;
        position: relative;
        border-radius: 50%;
        background: conic-gradient(#2563eb calc(var(--progress) * 1%), #dbeafe 0);
    }

    .task-progress-ring::after {
        position: absolute;
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: #fff;
        content: '';
    }

    .task-progress-ring strong {
        position: relative;
        z-index: 1;
        color: #1d4ed8;
        font-size: 17px;
    }

    .task-progress-copy strong {
        display: block;
        margin-bottom: 5px;
        color: #1e3a8a;
        font-size: 18px;
    }

    .task-progress-copy span {
        color: #475569;
        line-height: 1.5;
    }

    .task-progress-numbers {
        color: #1e40af;
        text-align: right;
        white-space: nowrap;
    }

    .task-progress-numbers strong {
        display: block;
        font-size: 23px;
    }

    .task-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 22px;
    }

    .task-filter {
        padding: 8px 12px;
        border-radius: 999px;
        background: #f3f4f6;
        color: #374151;
        text-decoration: none;
    }

    .task-filter.active {
        background: #111827;
        color: #fff;
    }

    .task-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        align-items: start;
    }

    .task-card {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 16px;
        align-items: start;
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .task-card.completed {
        background: #f9fafb;
    }

    .task-card.completed .task-title {
        color: #6b7280;
        text-decoration: line-through;
    }

    .task-check {
        width: 30px;
        height: 30px;
        padding: 0;
        border: 2px solid #9ca3af;
        border-radius: 50%;
        background: #fff;
        color: #fff;
        cursor: pointer;
        font-weight: 700;
    }

    .task-check:disabled {
        border-color: #dc2626;
        background: #fee2e2;
        color: #991b1b;
        cursor: not-allowed;
        opacity: .8;
    }

    .completed .task-check {
        border-color: #16a34a;
        background: #16a34a;
    }

    .task-title {
        margin: 0 0 7px;
        font-size: 18px;
    }

    .task-description {
        margin: 8px 0;
        color: #4b5563;
        white-space: pre-wrap;
    }

    .task-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        align-items: center;
        color: #6b7280;
        font-size: 13px;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 999px;
        font-weight: 700;
    }

    .priority-high {
        background: #fee2e2;
        color: #991b1b;
    }

    .priority-medium {
        background: #fef3c7;
        color: #92400e;
    }

    .priority-low {
        background: #dcfce7;
        color: #166534;
    }

    .countdown {
        padding: 11px 12px;
        margin-top: 13px;
        border-radius: 9px;
        background: #eff6ff;
        color: #1e3a8a;
    }

    .countdown-label {
        display: block;
        margin-bottom: 4px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .countdown-value {
        display: block;
        font-size: 17px;
        font-variant-numeric: tabular-nums;
    }

    .countdown-deadline {
        display: block;
        margin-top: 4px;
        color: #64748b;
        font-size: 12px;
    }

    .countdown.overdue {
        background: #fee2e2;
        color: #991b1b;
    }

    .task-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        grid-column: 2;
    }

    .task-actions form {
        margin: 0;
    }

    .task-actions .button {
        padding: 8px 11px;
    }

    .empty-state {
        padding: 42px 20px;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        text-align: center;
        color: #6b7280;
    }

    html[data-theme="dark"] .task-progress-overview {
        border-color: #1d4ed8;
        background: linear-gradient(135deg, #172554, #2e1065);
    }

    html[data-theme="dark"] .task-progress-ring::after {
        background: #1e293b;
    }

    html[data-theme="dark"] .task-progress-copy strong,
    html[data-theme="dark"] .task-progress-numbers {
        color: #93c5fd;
    }

    html[data-theme="dark"] .task-progress-copy span {
        color: #cbd5e1;
    }

    @media (max-width:900px) {
        .task-list {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width:720px) {
        .tasks-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .task-progress-overview {
            grid-template-columns: auto 1fr;
        }

        .task-progress-numbers {
            grid-column: 1/-1;
            text-align: left;
        }
    }
</style>

<div class="tasks-header">
    <div>
        <h1>Görevler</h1>
        <p>Yapılacak işlerinizi planlayın ve ilerlemenizi takip edin.</p>
    </div>
    <a class="button" href="<?= site_url("tasks/create") ?>">Yeni Görev</a>
</div>

<?php
$taskPercent = (int) $progressSummary["percent"];
if ((int) $progressSummary["total"] === 0) {
    $taskMotivation = ["📝 İlk görevinizi oluşturun", "Başlamak, ilerlemenin ilk adımıdır."];
} elseif ($taskPercent === 100) {
    $taskMotivation = [
        "🏆 Tüm görevler tamamlandı!",
        "Harika iş çıkardınız; listenizde bekleyen hiçbir görev kalmadı.",
    ];
} elseif ($taskPercent >= 75) {
    $taskMotivation = [
        "🔥 Son düzlüktesiniz!",
        "Görevlerin çoğu tamamlandı; ritminizi biraz daha koruyun.",
    ];
} elseif ($taskPercent >= 50) {
    $taskMotivation = [
        "🚀 Yolun yarısını geçtiniz!",
        "İlerlemeniz görünür hâle geldi, aynı tempoyla devam edin.",
    ];
} elseif ($taskPercent >= 25) {
    $taskMotivation = [
        "💪 Güzel bir başlangıç!",
        "Tamamladığınız her görev yükünüzü biraz daha hafifletiyor.",
    ];
} else {
    $taskMotivation = [
        "🌱 Küçük adımlar birikiyor",
        "Bugün yalnızca bir görevi bitirmek bile yüzdenizi yükseltecek.",
    ];
}
?>
<section class="task-progress-overview" aria-label="Görev başarı özeti">
    <div class="task-progress-ring" style="--progress:<?= $taskPercent ?>">
        <strong>%<?= $taskPercent ?></strong></div>
    <div class="task-progress-copy">
        <strong><?= $taskMotivation[0] ?></strong><span><?= $taskMotivation[1] ?></span></div>
    <div class="task-progress-numbers"><strong><?= (int) $progressSummary[
        "completed"
    ] ?> / <?= (int) $progressSummary["total"] ?></strong><span>görev tamamlandı</span></div>
</section>

<nav class="task-filters" aria-label="Görev filtreleri">
    <a class="task-filter <?= $activeStatus === "all" ? "active" : "" ?>" href="<?= site_url(
    "tasks",
) ?>">Tümü</a>
    <a class="task-filter <?= $activeStatus === "pending" ? "active" : "" ?>" href="<?= site_url(
    "tasks",
) ?>?status=pending">Bekleyenler</a>
    <a class="task-filter <?= $activeStatus === "completed" ? "active" : "" ?>" href="<?= site_url(
    "tasks",
) ?>?status=completed">Tamamlananlar</a>
</nav>

<form class="content-filter" method="get" action="<?= site_url(
    "tasks",
) ?>"
    style="display:grid; grid-template-columns:2fr 1fr 1fr auto auto; gap:9px; margin-bottom:22px">
    <input type="hidden" name="status" value="<?= esc($activeStatus, "attr") ?>">
    <input type="search" name="q" value="<?= esc(
        $search,
    ) ?>" placeholder="Görevlerde ara…"
        style="padding:10px; border:1px solid #d1d5db; border-radius:8px">
    <select name="category"
        style="padding:10px; border:1px solid #d1d5db; border-radius:8px; background:#fff">
        <option value="">Tüm kategoriler</option>
        <?php foreach ($categories as $category): ?><option value="<?= esc(
    $category,
    "attr",
) ?>" <?= $activeCategory === $category ? "selected" : "" ?>><?= esc(
    $category,
) ?></option><?php endforeach; ?>
    </select>
    <select name="priority"
        style="padding:10px; border:1px solid #d1d5db; border-radius:8px; background:#fff">
        <option value="">Tüm öncelikler</option>
        <option value="high" <?= $activePriority === "high" ? "selected" : "" ?>>Yüksek</option>
        <option value="medium" <?= $activePriority === "medium" ? "selected" : "" ?>>Orta</option>
        <option value="low" <?= $activePriority === "low" ? "selected" : "" ?>>Düşük</option>
    </select>
    <button class="button" type="submit">Filtrele</button>
    <a class="button secondary" href="<?= site_url("tasks") ?>">Temizle</a>
</form>

<?php if (empty($tasks)): ?>
<div class="empty-state">
    <p>Bu filtrede görüntülenecek görev bulunmuyor.</p>
    <a class="button" href="<?= site_url("tasks/create") ?>">İlk Görevi Oluştur</a>
</div>
<?php else: ?>
<div class="task-list">
    <?php $nowIstanbul = new DateTimeImmutable("now", new DateTimeZone("Europe/Istanbul")); ?>
    <?php foreach ($tasks as $task): ?>
    <?php
            $completed = $task["status"] === "completed";
            $priorityLabels = ["low" => "Düşük", "medium" => "Orta", "high" => "Yüksek"];
            $dueTime = $task["due_time"] ? substr($task["due_time"], 0, 8) : "23:59:59";
            $deadline = $task["due_date"] ? $task["due_date"] . "T" . $dueTime : null;
            $deadlineObject = $deadline
                ? new DateTimeImmutable($deadline, new DateTimeZone("Europe/Istanbul"))
                : null;
            $expired = !$completed && $deadlineObject && $deadlineObject <= $nowIstanbul;
            ?>
    <article class="task-card <?= $completed ? "completed" : "" ?>">
        <form method="post" action="<?= site_url("tasks/" . $task["id"] . "/toggle") ?>">
            <?= csrf_field() ?>
            <button class="task-check" type="submit" title="<?= $completed
                        ? "Yeniden aç"
                        : ($expired
                            ? "Süreyi uzatmadan tamamlanamaz"
                            : "Tamamla") ?>" aria-label="<?= $completed
    ? "Görevi yeniden aç"
    : ($expired
        ? "Süresi dolmuş görev"
        : "Görevi tamamla") ?>" <?= $expired ? "disabled" : "" ?>>
                <?= $completed ? "✓" : ($expired ? "!" : "") ?>
            </button>
        </form>

        <div>
            <h2 class="task-title"><?= esc($task["title"]) ?></h2>
            <?php if ($task["description"]): ?>
            <p class="task-description"><?= esc($task["description"]) ?></p>
            <?php endif; ?>
            <div class="task-meta">
                <span class="badge priority-<?= esc(
                            $task["priority"],
                            "attr",
                        ) ?>"><?= $priorityLabels[$task["priority"]] ?? "Orta" ?> öncelik</span>
                <span><?= esc($task["category"] ?? "Genel") ?></span>
                <?php if ($completed): ?><span>Tamamlandı</span><?php endif; ?>
            </div>

            <?php if ($deadline && !$completed): ?>
            <div class="countdown" data-deadline="<?= esc($deadline, "attr") ?>">
                <span class="countdown-label">Kalan süre</span>
                <strong class="countdown-value">Hesaplanıyor…</strong>
                <span class="countdown-deadline">Bitiş: <?= date(
                                "d.m.Y",
                                strtotime($task["due_date"]),
                            ) ?> · <?= esc(substr($dueTime, 0, 5)) ?></span>
            </div>
            <?php elseif ($completed && $task["completed_at"]): ?>
            <div class="countdown">
                <span class="countdown-label">Tamamlanma zamanı</span>
                <strong class="countdown-value"><?= date(
                                "d.m.Y · H:i",
                                strtotime($task["completed_at"]),
                            ) ?></strong>
            </div>
            <?php endif; ?>
        </div>

        <div class="task-actions">
            <a class="button secondary" href="<?= site_url(
                        "tasks/" . $task["id"] . "/edit",
                    ) ?>">Düzenle</a>
            <form method="post" action="<?= site_url(
                        "tasks/" . $task["id"] . "/delete",
                    ) ?>" onsubmit="return confirm('Bu görev silinsin mi?')">
                <?= csrf_field() ?>
                <button class="button danger" type="submit">Sil</button>
            </form>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?= $pager->links() ?>
<?php endif; ?>

<script>
    (() => {
        'use strict';

        const countdowns = Array.from(document.querySelectorAll('[data-deadline]'));

        function formatDuration(milliseconds) {
            const totalSeconds = Math.floor(Math.abs(milliseconds) / 1000);
            const days = Math.floor(totalSeconds / 86400);
            const hours = Math.floor((totalSeconds % 86400) / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            return `${days} gün ${String(hours).padStart(2, '0')} saat ${String(minutes).padStart(2, '0')} dk ${String(seconds).padStart(2, '0')} sn`;
        }

        function updateCountdowns() {
            const now = Date.now();

            countdowns.forEach(element => {
                const deadline = new Date(element.dataset.deadline).getTime();
                const value = element.querySelector('.countdown-value');

                if (!Number.isFinite(deadline)) {
                    value.textContent = 'Geçersiz tarih';
                    return;
                }

                const remaining = deadline - now;
                const expired = remaining < 0;
                const completeButton = element.closest('.task-card')?.querySelector(
                    '.task-check');
                element.classList.toggle('overdue', expired);
                element.querySelector('.countdown-label').textContent = expired ?
                    'Süre doldu' : 'Kalan süre';
                value.textContent = (expired ? 'Gecikme: ' : '') + formatDuration(
                    remaining);
                if (completeButton) {
                    completeButton.disabled = expired;
                    completeButton.textContent = expired ? '!' : '';
                    completeButton.title = expired ? 'Süreyi uzatmadan tamamlanamaz' :
                        'Tamamla';
                    completeButton.setAttribute('aria-label', expired ?
                        'Süresi dolmuş görev' : 'Görevi tamamla');
                }
            });
        }

        updateCountdowns();
        window.setInterval(() => {
            if (!document.hidden) updateCountdowns();
        }, 1000);
    })();
</script>
<?= $this->endSection() ?>