<?= $this->extend("layouts/main") ?>
<?= $this->section("content") ?>
<style>
    .admin-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 22px
    }

    .admin-head h1 {
        margin: 0 0 6px
    }

    .admin-head p {
        margin: 0;
        color: #6b7280
    }

    .admin-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px
    }

    .admin-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
        gap: 12px;
        margin-bottom: 22px
    }

    .admin-stat {
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff
    }

    .admin-stat span {
        color: #6b7280;
        font-size: 13px
    }

    .admin-stat strong {
        display: block;
        margin-top: 6px;
        font-size: 28px
    }

    .admin-stat.warning strong {
        color: #dc2626
    }

    .admin-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr);
        gap: 20px
    }

    .admin-panel {
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 12px
    }

    .admin-panel-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px
    }

    .admin-panel-title h2 {
        margin: 0;
        font-size: 19px
    }

    .admin-panel-title a {
        color: #2563eb;
        text-decoration: none
    }

    .activity-chart {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        align-items: end;
        gap: 8px;
        height: 170px;
        padding-top: 16px
    }

    .chart-day {
        display: grid;
        grid-template-rows: 1fr auto auto;
        align-items: end;
        height: 100%;
        text-align: center;
        color: #6b7280;
        font-size: 11px
    }

    .chart-bar {
        width: 100%;
        min-height: 4px;
        border-radius: 6px 6px 2px 2px;
        background: #2563eb
    }

    .chart-day strong {
        margin: 5px 0 2px;
        color: #111827;
        font-size: 12px
    }

    .activity-list,
    .user-list {
        padding: 0;
        margin: 0;
        list-style: none
    }

    .activity-list li,
    .user-list li {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: start;
        gap: 12px;
        padding: 11px 0;
        border-bottom: 1px solid #e5e7eb
    }

    .activity-main,
    .user-main {
        min-width: 0;
        overflow-wrap: anywhere
    }

    .activity-list li:last-child,
    .user-list li:last-child {
        border-bottom: 0
    }

    .activity-main strong {
        display: block
    }

    .user-main strong,
    .user-main small {
        display: block
    }

    .activity-main small,
    .user-list small {
        color: #6b7280
    }

    .activity-time {
        color: #6b7280;
        font-size: 12px;
        white-space: nowrap
    }

    .user-date {
        white-space: nowrap
    }

    .status-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        margin-right: 7px;
        border-radius: 50%;
        background: #22c55e
    }

    .status-dot.failed {
        background: #ef4444
    }

    @media(max-width:900px) {
        .admin-grid {
            grid-template-columns: 1fr
        }
    }

    @media(max-width:650px) {
        .admin-head {
            flex-direction: column
        }

        .admin-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr))
        }

        .activity-list li,
        .user-list li {
            grid-template-columns: minmax(0, 1fr)
        }

        .activity-time,
        .user-date {
            justify-self: start
        }
    }
</style>
<header class="admin-head">
    <div>
        <h1>Admin Dashboard</h1>
        <p>Sistemin güncel durumunu ve son kullanıcı hareketlerini izleyin.</p>
    </div>
    <nav class="admin-nav"><a class="button" href="<?= site_url(
    "admin/users",
) ?>">Yönetim Merkezi</a><a class="button secondary" href="<?= site_url(
    "admin/logs",
) ?>">Tüm Loglar</a></nav>
</header>

<section class="admin-stats">
    <article class="admin-stat"><span>Toplam kullanıcı</span><strong><?= $counts[
        "users"
    ] ?></strong></article>
    <article class="admin-stat"><span>Aktif kullanıcı</span><strong><?= $counts[
        "activeUsers"
    ] ?></strong></article>
    <article class="admin-stat"><span>Bugünkü hareket</span><strong><?= $todayActivity ?></strong>
    </article>
    <article class="admin-stat warning"><span>7 günlük başarısız
            giriş</span><strong><?= $failedLogins ?></strong></article>
    <article class="admin-stat"><span>Not / yorum</span><strong><?= $counts[
        "notes"
    ] ?> / <?= $counts["comments"] ?></strong></article>
    <article class="admin-stat"><span>Görev / alışkanlık</span><strong><?= $counts[
        "tasks"
    ] ?> / <?= $counts["habits"] ?></strong></article>
    <article class="admin-stat"><span>Günlük kayıtları</span><strong><?= $counts[
        "journals"
    ] ?></strong></article>
</section>

<div class="admin-grid">
    <div>
        <section class="admin-panel">
            <div class="admin-panel-title">
                <h2>Son 7 Günlük Aktivite</h2><a href="<?= site_url(
                "admin/logs",
            ) ?>">Detaylar</a>
            </div>
            <?php
            $totals = array_column($activityByDay, "total", "activity_date");
            $activityValues = array_map("intval", array_values($totals));
            $max = $activityValues === [] ? 1 : max(1, max($activityValues));
            ?>
            <div class="activity-chart">
                <?php for ($offset = 6; $offset >= 0; $offset--):

                    $day = date("Y-m-d", strtotime("-{$offset} days"));
                    $total = (int) ($totals[$day] ?? 0);
                    ?>
                <div class="chart-day">
                    <div class="chart-bar" style="height:<?= max(
                        4,
                        (int) round(($total / $max) * 110),
                    ) ?>px" title="<?= $total ?> hareket"></div>
                    <strong><?= $total ?></strong><span><?= date(
    "d.m",
    strtotime($day),
) ?></span>
                </div>
                <?php
                endfor; ?>
            </div>
        </section>
        <section class="admin-panel" style="margin-top:20px">
            <div class="admin-panel-title">
                <h2>Son Hareketler</h2><a href="<?= site_url(
                "admin/logs",
            ) ?>">Tümünü gör</a>
            </div>
            <?php if (
                empty($recentLogs)
            ): ?><p>Henüz aktivite kaydı yok.</p><?php else: ?><ul class="activity-list"><?php foreach (
    $recentLogs
    as $log
): ?><li>
                    <div class="activity-main"><strong><span class="status-dot <?= (int) $log["status_code"] >=
400
    ? "failed"
    : "" ?>"></span><?= esc($log["description"]) ?></strong><small><?= esc(
    $log["username"] ?? "Misafir/Silinmiş kullanıcı",
) ?> · <?= esc($log["action"]) ?> · <?= esc(
     $log["ip_address"],
 ) ?></small></div><time class="activity-time"><?= date(
    "d.m H:i",
    strtotime($log["created_at"]),
) ?></time>
                </li><?php endforeach; ?></ul><?php endif; ?>
        </section>
    </div>
    <aside class="admin-panel">
        <div class="admin-panel-title">
            <h2>Yeni Kullanıcılar</h2><a href="<?= site_url(
            "admin/users",
        ) ?>">Yönet</a>
        </div>
        <ul class="user-list"><?php foreach ($recentUsers as $user): ?><li>
                <div class="user-main"><strong><?= esc(
    $user["username"],
) ?></strong><small><?= esc($user["email"]) ?></small></div><small class="user-date"><?= date(
    "d.m.Y",
    strtotime($user["created_at"]),
) ?></small>
            </li><?php endforeach; ?></ul>
    </aside>
</div>
<?= $this->endSection() ?>
