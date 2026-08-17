<aside class="game-leaderboard">
    <div class="leaderboard-head"><span aria-hidden="true">🏆</span>
        <div>
            <h2>En İyi 3</h2>
            <p id="game-leaderboard-subtitle"><?= esc(
        $subtitle,
    ) ?></p>
        </div>
    </div>
    <ol class="leaderboard-list" id="<?= esc($elementId, "attr") ?>">
        <?php if (
            empty($scores)
        ): ?><li class="leaderboard-empty">Henüz kayıtlı skor yok.</li><?php else:foreach (
                $scores
                as $index => $entry
            ): ?>
        <li><span class="leaderboard-rank"><?= ["🥇", "🥈", "🥉"][
                $index
            ] ?></span><span class="leaderboard-player"><?= esc(
    $entry["username"],
) ?></span><strong><?=
(int) $entry["score"] . $unit
?></strong></li>
        <?php endforeach;endif; ?>
    </ol>
    <p class="leaderboard-note">Yalnızca her oyuncunun en iyi sonucu gösterilir.</p>
</aside>
