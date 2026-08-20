<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
    .game-page {
        max-width: 1100px;
        margin: 0 auto;
    }

    .game-titlebar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 18px;
    }

    .game-titlebar h1 {
        margin: 0 0 6px;
    }

    .game-titlebar p {
        margin: 0;
        color: #6b7280;
    }

    .game-layout {
        display: grid;
        grid-template-columns: minmax(0, 760px) minmax(240px, 1fr);
        align-items: start;
        gap: 18px;
    }

    .tetris-main {
        display: grid;
        grid-template-columns: minmax(250px, 330px) minmax(180px, 1fr);
        align-items: start;
        gap: 18px;
    }

    .tetris-board-wrap {
        position: relative;
        width: 100%;
        max-width: 330px;
        margin: 0 auto;
        border-radius: 16px;
        overflow: hidden;
        background: #111827;
        border: 1px solid #374151;
    }

    #tetris-board {
        display: block;
        width: 100%;
        height: auto;
        aspect-ratio: 1 / 2;
        background: #111827;
    }

    .tetris-overlay {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        padding: 24px;
        background: rgba(15, 23, 42, .82);
        color: #fff;
        text-align: center;
    }

    .tetris-overlay[hidden] {
        display: none;
    }

    .tetris-overlay strong {
        display: block;
        margin-bottom: 8px;
        font-size: 26px;
    }

    .tetris-overlay span {
        display: block;
        line-height: 1.5;
    }

    .tetris-side {
        display: grid;
        gap: 14px;
    }

    .tetris-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .tetris-stat {
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
        text-align: center;
    }

    .tetris-stat span {
        display: block;
        color: #6b7280;
        font-size: 12px;
    }

    .tetris-stat strong {
        display: block;
        margin-top: 3px;
        font-size: 21px;
    }

    .tetris-next-box {
        padding: 15px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
    }

    .tetris-next-box h3 {
        margin: 0 0 10px;
        font-size: 15px;
    }

    #tetris-next {
        display: block;
        width: 130px;
        height: 130px;
        max-width: 100%;
        margin: 0 auto;
        border-radius: 10px;
        background: #111827;
    }

    .tetris-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .tetris-actions button {
        flex: 1;
        min-width: 120px;
    }

    .tetris-help {
        padding: 14px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.7;
    }

    .tetris-help strong {
        color: inherit;
    }

    .tetris-mobile-controls {
        display: none;
        grid-template-columns: repeat(3, 1fr);
        gap: 7px;
        margin-top: 14px;
    }

    .tetris-mobile-controls button {
        min-height: 48px;
        font-size: 20px;
    }

    .score-save-status {
        min-height: 18px;
        margin: 10px 0 0;
        color: #6b7280;
        text-align: center;
        font-size: 12px;
    }

    .game-leaderboard {
        position: sticky;
        top: 18px;
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 15px;
        background: #fff;
    }

    .leaderboard-head {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .leaderboard-head>span {
        font-size: 30px;
    }

    .leaderboard-head h2 {
        margin: 0;
        font-size: 19px;
    }

    .leaderboard-head p {
        margin: 3px 0 0;
        color: #6b7280;
        font-size: 12px;
    }

    .leaderboard-list {
        display: grid;
        gap: 8px;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .leaderboard-list li {
        display: grid;
        grid-template-columns: 30px minmax(0, 1fr) auto;
        align-items: center;
        gap: 7px;
        padding: 11px 9px;
        border-radius: 9px;
        background: #f8fafc;
    }

    .leaderboard-player {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .leaderboard-empty {
        display: block !important;
        color: #6b7280;
        text-align: center;
    }

    .leaderboard-note {
        margin: 13px 0 0;
        color: #6b7280;
        font-size: 11px;
        line-height: 1.4;
    }

    html[data-theme="dark"] .tetris-stat,
    html[data-theme="dark"] .tetris-next-box,
    html[data-theme="dark"] .tetris-help,
    html[data-theme="dark"] .game-leaderboard,
    html[data-theme="dark"] .leaderboard-list li {
        background: #0f172a;
        border-color: #334155;
    }

    @media(max-width:900px) {
        .game-layout {
            grid-template-columns: 1fr;
        }

        .game-leaderboard {
            position: static;
        }
    }

    @media(max-width:700px) {
        .game-titlebar {
            flex-direction: column;
        }

        .game-titlebar .button {
            width: 100%;
            text-align: center;
        }

        .tetris-main {
            grid-template-columns: 1fr;
        }

        .tetris-board-wrap,
        .tetris-mobile-controls,
        .score-save-status {
            width: 100%;
            max-width: 330px;
            margin-right: auto;
            margin-left: auto;
        }

        .tetris-mobile-controls {
            display: grid;
            touch-action: manipulation;
            user-select: none;
        }

        .tetris-mobile-controls button {
            min-width: 0;
        }

        #tetris-board {
            touch-action: none;
        }
    }

    @media(max-width:480px) {
        .game-titlebar h1 {
            font-size: 26px;
        }

        .game-titlebar p {
            font-size: 14px;
            line-height: 1.45;
        }

        .tetris-side {
            gap: 10px;
        }

        .tetris-stat {
            padding: 9px 6px;
        }

        .tetris-stat strong {
            font-size: 18px;
        }

        .tetris-next-box {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 86px;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
        }

        .tetris-next-box h3 {
            margin: 0;
        }

        #tetris-next {
            width: 86px;
            height: 86px;
        }

        .tetris-actions button {
            min-width: 0;
        }

        .tetris-help {
            font-size: 12px;
            line-height: 1.55;
        }

        .game-leaderboard {
            padding: 14px;
        }

        .leaderboard-list li {
            grid-template-columns: 26px minmax(0, 1fr) auto;
            padding: 10px 7px;
            font-size: 13px;
        }
    }
</style>

<div
    class="game-page"
    id="tetris-game"
    data-user-id="<?= (int) session()->get('user_id') ?>"
    data-personal-best="<?= (int) $personalBest ?>"
    data-score-url="<?= site_url('games/score') ?>"
    data-csrf-name="<?= csrf_token() ?>"
    data-csrf-hash="<?= csrf_hash() ?>"
>
    <header class="game-titlebar">
        <div>
            <h1>▦ Tetris</h1>
            <p>
                Satırları temizleyin, seviyenizi yükseltin
                ve mümkün olan en yüksek skora ulaşın.
            </p>
        </div>

        <a
            class="button secondary"
            href="<?= site_url('games') ?>"
        >
            Oyunlara dön
        </a>
    </header>

    <div class="game-layout">

        <div>
            <div class="tetris-main">

                <div>
                    <div class="tetris-board-wrap">

                        <canvas
                            id="tetris-board"
                            width="300"
                            height="600"
                            aria-label="Tetris oyun alanı"
                        ></canvas>

                        <div
                            class="tetris-overlay"
                            id="tetris-overlay"
                            hidden
                        >
                            <div>
                                <strong id="tetris-overlay-title">
                                    Oyun bitti
                                </strong>

                                <span id="tetris-overlay-text"></span>
                            </div>
                        </div>

                    </div>

                    <div class="tetris-mobile-controls">

                        <button
                            class="button secondary"
                            type="button"
                            data-tetris-action="left"
                        >
                            ←
                        </button>

                        <button
                            class="button secondary"
                            type="button"
                            data-tetris-action="rotate"
                        >
                            ↻
                        </button>

                        <button
                            class="button secondary"
                            type="button"
                            data-tetris-action="right"
                        >
                            →
                        </button>

                        <button
                            class="button secondary"
                            type="button"
                            data-tetris-action="down"
                        >
                            ↓
                        </button>

                        <button
                            class="button secondary"
                            type="button"
                            data-tetris-action="drop"
                        >
                            ⇊
                        </button>

                        <button
                            class="button secondary"
                            type="button"
                            data-tetris-action="pause"
                        >
                            ⏸
                        </button>

                    </div>

                    <p
                        class="score-save-status"
                        id="tetris-score-status"
                        aria-live="polite"
                    ></p>
                </div>

                <aside class="tetris-side">

                    <section class="tetris-stats">

                        <div class="tetris-stat">
                            <span>Skor</span>
                            <strong id="tetris-score">0</strong>
                        </div>

                        <div class="tetris-stat">
                            <span>En yüksek</span>
                            <strong id="tetris-best">
                                <?= (int) $personalBest ?>
                            </strong>
                        </div>

                        <div class="tetris-stat">
                            <span>Seviye</span>
                            <strong id="tetris-level">1</strong>
                        </div>

                        <div class="tetris-stat">
                            <span>Satır</span>
                            <strong id="tetris-lines">0</strong>
                        </div>

                    </section>

                    <section class="tetris-next-box">
                        <h3>Sıradaki parça</h3>

                        <canvas
                            id="tetris-next"
                            width="130"
                            height="130"
                        ></canvas>
                    </section>

                    <div class="tetris-actions">

                        <button
                            class="button"
                            id="tetris-new-game"
                            type="button"
                        >
                            Yeni oyun
                        </button>

                        <button
                            class="button secondary"
                            id="tetris-pause"
                            type="button"
                        >
                            Duraklat
                        </button>

                    </div>

                    <div class="tetris-help">
                        <strong>Kontroller</strong><br>
                        ← → Hareket<br>
                        ↓ Hızlı düşür<br>
                        ↑ Döndür<br>
                        Space Sert düşür<br>
                        P / Esc Duraklat
                    </div>

                </aside>
            </div>
        </div>

        <?= view('games/_leaderboard', [
            'scores' => $leaderboard,
            'subtitle' => 'Tetris',
            'elementId' => 'tetris-leaderboard',
            'unit' => ' puan',
        ]) ?>

    </div>
</div>

<script src="<?= base_url('js/tetris.js?v=2') ?>"></script>

<?= $this->endSection() ?>
