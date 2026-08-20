<?= $this->extend("layouts/main") ?>
<?= $this->section("content") ?>
<style>
    .games-head {
        margin-bottom: 24px
    }

    .games-head h1 {
        margin: 0 0 7px
    }

    .games-head p {
        margin: 0;
        color: #6b7280
    }

    .games-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px
    }

    .game-card {
        display: flex;
        min-height: 260px;
        flex-direction: column;
        padding: 24px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
        position: relative
    }

    .game-card::after {
        position: absolute;
        right: -22px;
        bottom: -35px;
        font-size: 130px;
        opacity: .08;
        transform: rotate(-8deg)
    }

    .game-card.snake::after {
        content: '🐍'
    }
    .game-card.tetris::after {
        content: '▦'
    }

    .tetris .game-icon {
        background: #ede9fe;
        color: #6d28d9;
        font-weight: 800
    }

    .game-card.mines::after {
        content: '💣'
    }

    .game-card.sudoku::after {
        content: '9'
    }

    .game-icon {
        display: grid;
        width: 58px;
        height: 58px;
        place-items: center;
        border-radius: 15px;
        font-size: 31px
    }

    .snake .game-icon {
        background: #dcfce7
    }

    .mines .game-icon {
        background: #fee2e2
    }

    .sudoku .game-icon {
        background: #dbeafe;
        color: #1d4ed8;
        font-weight: 800
    }

    .game-card h2 {
        margin: 18px 0 8px
    }

    .game-card p {
        flex: 1;
        margin: 0 0 20px;
        color: #6b7280;
        line-height: 1.6
    }

    .game-card .button {
        align-self: flex-start;
        position: relative;
        z-index: 1
    }

    html[data-theme="dark"] .game-card {
        background: #1e293b;
        border-color: #334155
    }

    @media(max-width:900px) {
        .games-grid {
            grid-template-columns: repeat(2, 1fr)
        }
    }

    @media(max-width:700px) {
        .games-grid {
            grid-template-columns: 1fr
        }

        .game-card {
            min-height: 230px
        }
    }
</style>
<header class="games-head">
    <h1>Oyunlar</h1>
    <p>Kısa bir mola verin ve en iyi skorunuzu geliştirin.</p>
    <p>Oyun isteklerinizi önemsiyoruz. Eklenmesini istediğiniz oyunları bildirmeyi unutmayınız.</p>
</header>
<p style="margin:-10px 0 20px"><a class="button" href="<?= site_url(
    "games/multiplayer",
) ?>">👥 Birlikte oyna</a></p>
<div class="games-grid">
    <article class="game-card snake"><span class="game-icon" aria-hidden="true">🐍</span>
        <h2>Yılan Oyunu</h2>
        <p>Yemi toplayın, uzayın ve duvarlara ya da kendinize çarpmadan mümkün olan en yüksek skora
            ulaşın.</p><a class="button" href="<?= site_url(
        "games/snake",
    ) ?>">Oyunu aç</a>
    </article>
    <article class="game-card tetris">
        <span class="game-icon" aria-hidden="true">▦</span>

        <h2>Tetris</h2>

        <p>
            Blokları doğru yerleştirin, satırları temizleyin
            ve seviye yükseldikçe artan hıza ayak uydurun.
        </p>

        <a class="button" href="<?= site_url('games/tetris') ?>">
            Oyunu aç
        </a>
    </article>
    <article class="game-card mines"><span class="game-icon" aria-hidden="true">💣</span>
        <h2>Mayın Tarlası</h2>
        <p>Sayılardan yararlanarak mayınları bulun. Bilgisayarda sağ tıkla, telefonda bayrak moduyla
            işaretleyin.</p><a class="button" href="<?= site_url(
        "games/minesweeper",
    ) ?>">Oyunu aç</a>
    </article>
    <article class="game-card sudoku"><span class="game-icon" aria-hidden="true">9</span>
        <h2>Sudoku</h2>
        <p>Satırları, sütunları ve 3×3 kutuları tamamlayın. Üç zorluk seviyesinde en iyi sürenizi
            geliştirin.</p><a class="button" href="<?= site_url(
        "games/sudoku",
    ) ?>">Oyunu aç</a>
    </article>
</div>
<?= $this->endSection() ?>