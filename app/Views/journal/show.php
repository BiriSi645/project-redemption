<?= $this->extend("layouts/main") ?>

<?= $this->section("content") ?>
<?php
$moods = [
    "great" => ["Harika", "😄"],
    "good" => ["İyi", "🙂"],
    "neutral" => ["Normal", "😐"],
    "bad" => ["Kötü", "😕"],
    "awful" => ["Çok kötü", "😞"],
];
$mood = $moods[$entry["mood"]] ?? $moods["neutral"];
?>
<style>
    .journal-entry {
        max-width: 850px;
        margin: 0 auto;
    }

    .entry-heading {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        align-items: start;
        padding-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .entry-heading h1 {
        margin: 8px 0;
    }

    .entry-date,
    .entry-owner {
        color: #6b7280;
    }

    .entry-mood {
        padding: 10px 13px;
        border-radius: 10px;
        background: #f3f4f6;
        white-space: nowrap;
    }

    .entry-content {
        min-height: 220px;
        padding: 28px 0;
        line-height: 1.8;
        white-space: pre-wrap;
    }

    .entry-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding-top: 18px;
        border-top: 1px solid #e5e7eb;
    }

    .entry-actions form {
        margin: 0;
    }
</style>

<article class="journal-entry">
    <header class="entry-heading">
        <div>
            <time class="entry-date" datetime="<?= esc($entry["entry_date"], "attr") ?>"><?= date(
    "d.m.Y",
    strtotime($entry["entry_date"]),
) ?></time>
            <h1><?= esc($entry["title"]) ?></h1>
            <?php if (session()->get("role") === "admin"): ?>
            <div class="entry-owner">Sahibi: <?= esc(
                    $entry["owner_name"] ?? "Bilinmiyor",
                ) ?></div>
            <?php endif; ?>
        </div>
        <div class="entry-mood"><?= $mood[1] ?> <?= esc($mood[0]) ?></div>
    </header>

    <div class="entry-content"><?= esc($entry["content"]) ?></div>

    <footer class="entry-actions">
        <a class="button secondary" href="<?= site_url("journal") ?>">Günlüğe Dön</a>
        <?php if ($isOwner): ?>
        <a class="button" href="<?= site_url(
                "journal/" . $entry["id"] . "/edit",
            ) ?>">Düzenle</a>
        <form method="post" action="<?= site_url(
                "journal/" . $entry["id"] . "/delete",
            ) ?>" onsubmit="return confirm('Bu günlük kaydı silinsin mi?')">
            <?= csrf_field() ?>
            <button class="button danger" type="submit">Sil</button>
        </form>
        <?php endif; ?>
    </footer>
</article>
<?= $this->endSection() ?>