(() => {
    'use strict';

    const root = document.getElementById('tetris-game');
    const canvas = document.getElementById('tetris-board');
    const nextCanvas = document.getElementById('tetris-next');

    if (!root || !canvas || !nextCanvas) {
        return;
    }

    const context = canvas.getContext('2d');
    const nextContext = nextCanvas.getContext('2d');

    const scoreElement = document.getElementById('tetris-score');
    const bestElement = document.getElementById('tetris-best');
    const levelElement = document.getElementById('tetris-level');
    const linesElement = document.getElementById('tetris-lines');

    const overlay = document.getElementById('tetris-overlay');
    const overlayTitle = document.getElementById('tetris-overlay-title');
    const overlayText = document.getElementById('tetris-overlay-text');

    const newGameButton = document.getElementById('tetris-new-game');
    const pauseButton = document.getElementById('tetris-pause');

    const scoreStatus = document.getElementById('tetris-score-status');
    const leaderboardElement = document.getElementById('tetris-leaderboard');

    const COLS = 10;
    const ROWS = 20;
    const CELL = 30;

    const COLORS = {
        I: '#22d3ee',
        O: '#facc15',
        T: '#a855f7',
        S: '#22c55e',
        Z: '#ef4444',
        J: '#3b82f6',
        L: '#f97316',
    };

    const SHAPES = {
        I: [
            [0, 0, 0, 0],
            [1, 1, 1, 1],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
        ],

        O: [
            [1, 1],
            [1, 1],
        ],

        T: [
            [0, 1, 0],
            [1, 1, 1],
            [0, 0, 0],
        ],

        S: [
            [0, 1, 1],
            [1, 1, 0],
            [0, 0, 0],
        ],

        Z: [
            [1, 1, 0],
            [0, 1, 1],
            [0, 0, 0],
        ],

        J: [
            [1, 0, 0],
            [1, 1, 1],
            [0, 0, 0],
        ],

        L: [
            [0, 0, 1],
            [1, 1, 1],
            [0, 0, 0],
        ],
    };

    let board;
    let current;
    let next;
    let bag;

    let score;
    let level;
    let lines;

    let paused;
    let ended;

    let dropInterval;
    let lastDrop;

    let animationFrame;

    let best = Number(root.dataset.personalBest) || 0;

    bestElement.textContent = String(best);

    function createBoard() {
        return Array.from(
            { length: ROWS },
            () => Array(COLS).fill(null)
        );
    }

    function shuffleBag() {
        const pieces = Object.keys(SHAPES);

        for (let index = pieces.length - 1; index > 0; index--) {
            const random = Math.floor(
                Math.random() * (index + 1)
            );

            [pieces[index], pieces[random]] = [
                pieces[random],
                pieces[index],
            ];
        }

        bag.push(...pieces);
    }

    function nextPieceType() {
        if (bag.length === 0) {
            shuffleBag();
        }

        return bag.shift();
    }

    function createPiece(type = nextPieceType()) {
        const matrix = SHAPES[type].map(row => [...row]);

        return {
            type,
            matrix,
            x:
                Math.floor(COLS / 2)
                - Math.ceil(matrix[0].length / 2),
            y: 0,
        };
    }

    function collision(piece, x, y, matrix) {
        for (let row = 0; row < matrix.length; row++) {
            for (
                let column = 0;
                column < matrix[row].length;
                column++
            ) {
                if (!matrix[row][column]) {
                    continue;
                }

                const boardX = x + column;
                const boardY = y + row;

                if (
                    boardX < 0
                    || boardX >= COLS
                    || boardY >= ROWS
                ) {
                    return true;
                }

                if (
                    boardY >= 0
                    && board[boardY][boardX]
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    function spawnPiece() {
        current = next || createPiece();
        next = createPiece();

        current.x =
            Math.floor(COLS / 2)
            - Math.ceil(current.matrix[0].length / 2);

        current.y = 0;

        drawNext();

        if (
            collision(
                current,
                current.x,
                current.y,
                current.matrix
            )
        ) {
            gameOver();
        }
    }

    function mergePiece() {
        current.matrix.forEach((row, rowIndex) => {
            row.forEach((value, columnIndex) => {
                if (!value) {
                    return;
                }

                const y = current.y + rowIndex;
                const x = current.x + columnIndex;

                if (y >= 0) {
                    board[y][x] = current.type;
                }
            });
        });
    }

    function rotateMatrix(matrix) {
        return matrix[0].map(
            (_, index) =>
                matrix
                    .map(row => row[index])
                    .reverse()
        );
    }

    function rotate() {
        if (paused || ended || !current) {
            return;
        }

        const rotated = rotateMatrix(current.matrix);

        const kicks = [0, -1, 1, -2, 2];

        for (const offset of kicks) {
            if (
                !collision(
                    current,
                    current.x + offset,
                    current.y,
                    rotated
                )
            ) {
                current.x += offset;
                current.matrix = rotated;

                draw();

                return;
            }
        }
    }

    function move(direction) {
        if (paused || ended || !current) {
            return;
        }

        const targetX = current.x + direction;

        if (
            !collision(
                current,
                targetX,
                current.y,
                current.matrix
            )
        ) {
            current.x = targetX;
            draw();
        }
    }

    function softDrop(manual = true) {
        if (paused || ended || !current) {
            return;
        }

        if (
            !collision(
                current,
                current.x,
                current.y + 1,
                current.matrix
            )
        ) {
            current.y++;

            if (manual) {
                score++;
                updateStats();
            }

            draw();

            return;
        }

        lockPiece();
    }

    function hardDrop() {
        if (paused || ended || !current) {
            return;
        }

        let distance = 0;

        while (
            !collision(
                current,
                current.x,
                current.y + 1,
                current.matrix
            )
        ) {
            current.y++;
            distance++;
        }

        score += distance * 2;

        lockPiece();
    }

    function lockPiece() {
        mergePiece();
        clearLines();
        spawnPiece();

        updateStats();
        draw();
    }

    function clearLines() {
        let cleared = 0;

        for (let row = ROWS - 1; row >= 0; row--) {
            if (board[row].every(Boolean)) {
                board.splice(row, 1);
                board.unshift(Array(COLS).fill(null));

                cleared++;
                row++;
            }
        }

        if (cleared === 0) {
            return;
        }

        const points = {
            1: 100,
            2: 300,
            3: 500,
            4: 800,
        };

        score += points[cleared] * level;

        lines += cleared;

        level = Math.floor(lines / 10) + 1;

        dropInterval = Math.max(
            100,
            800 - ((level - 1) * 65)
        );
    }

    function ghostPosition() {
        let y = current.y;

        while (
            !collision(
                current,
                current.x,
                y + 1,
                current.matrix
            )
        ) {
            y++;
        }

        return y;
    }

    function drawCell(
        targetContext,
        x,
        y,
        size,
        color,
        alpha = 1
    ) {
        targetContext.globalAlpha = alpha;

        targetContext.fillStyle = color;

        targetContext.fillRect(
            (x * size) + 1,
            (y * size) + 1,
            size - 2,
            size - 2
        );

        targetContext.globalAlpha = 1;
    }

    function drawGrid() {
        context.strokeStyle = 'rgba(255,255,255,.055)';
        context.lineWidth = 1;

        for (let x = 1; x < COLS; x++) {
            context.beginPath();
            context.moveTo(x * CELL, 0);
            context.lineTo(x * CELL, canvas.height);
            context.stroke();
        }

        for (let y = 1; y < ROWS; y++) {
            context.beginPath();
            context.moveTo(0, y * CELL);
            context.lineTo(canvas.width, y * CELL);
            context.stroke();
        }
    }

    function drawMatrix(
        matrix,
        offsetX,
        offsetY,
        type,
        alpha = 1
    ) {
        matrix.forEach((row, rowIndex) => {
            row.forEach((value, columnIndex) => {
                if (!value) {
                    return;
                }

                drawCell(
                    context,
                    offsetX + columnIndex,
                    offsetY + rowIndex,
                    CELL,
                    COLORS[type],
                    alpha
                );
            });
        });
    }

    function draw() {
        context.fillStyle = '#111827';

        context.fillRect(
            0,
            0,
            canvas.width,
            canvas.height
        );

        drawGrid();

        board.forEach((row, y) => {
            row.forEach((type, x) => {
                if (!type) {
                    return;
                }

                drawCell(
                    context,
                    x,
                    y,
                    CELL,
                    COLORS[type]
                );
            });
        });

        if (!current || ended) {
            return;
        }

        drawMatrix(
            current.matrix,
            current.x,
            ghostPosition(),
            current.type,
            0.20
        );

        drawMatrix(
            current.matrix,
            current.x,
            current.y,
            current.type
        );
    }

    function drawNext() {
        nextContext.fillStyle = '#111827';

        nextContext.fillRect(
            0,
            0,
            nextCanvas.width,
            nextCanvas.height
        );

        if (!next) {
            return;
        }

        const size = 25;

        const width = next.matrix[0].length * size;
        const height = next.matrix.length * size;

        const startX = (nextCanvas.width - width) / 2;
        const startY = (nextCanvas.height - height) / 2;

        next.matrix.forEach((row, rowIndex) => {
            row.forEach((value, columnIndex) => {
                if (!value) {
                    return;
                }

                nextContext.fillStyle = COLORS[next.type];

                nextContext.fillRect(
                    startX + (columnIndex * size) + 1,
                    startY + (rowIndex * size) + 1,
                    size - 2,
                    size - 2
                );
            });
        });
    }

    function updateStats() {
        scoreElement.textContent = String(score);
        levelElement.textContent = String(level);
        linesElement.textContent = String(lines);

        if (score > best) {
            best = score;
            bestElement.textContent = String(best);
        }
    }

    function showOverlay(title, text) {
        overlayTitle.textContent = title;
        overlayText.textContent = text;
        overlay.hidden = false;
    }

    function hideOverlay() {
        overlay.hidden = true;
    }

    function togglePause() {
        if (ended) {
            return;
        }

        paused = !paused;

        if (paused) {
            pauseButton.textContent = 'Devam et';

            showOverlay(
                'Oyun duraklatıldı',
                'Devam etmek için P, Esc veya düğmeye basın.'
            );
        } else {
            pauseButton.textContent = 'Duraklat';

            hideOverlay();

            lastDrop = performance.now();
        }
    }

    function gameOver() {
        if (ended) {
            return;
        }

        ended = true;

        showOverlay(
            'Oyun bitti',
            `Skorunuz: ${score} · Yeni oyun ile tekrar deneyebilirsiniz.`
        );

        if (score > 0) {
            saveScore();
        }
    }

    async function saveScore() {
        scoreStatus.textContent = 'Skor kaydediliyor…';

        const body = new URLSearchParams({
            game: 'tetris',
            difficulty: 'default',
            score: String(score),
            [root.dataset.csrfName]:
                root.dataset.csrfHash,
        });

        try {
            const response = await fetch(
                root.dataset.scoreUrl,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded;charset=UTF-8',

                        'X-Requested-With':
                            'XMLHttpRequest',
                    },

                    body,
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || 'Skor kaydedilemedi.'
                );
            }

            best =
                Number(data.personalBest)
                || best;

            bestElement.textContent = String(best);

            renderLeaderboard(data.leaderboard);

            scoreStatus.textContent =
                data.improved
                    ? 'Yeni kişisel rekorunuz kaydedildi!'
                    : 'Skor kaydedildi; kişisel rekorunuz değişmedi.';
        } catch (error) {
            console.error(error);

            scoreStatus.textContent =
                'Skor şu anda kaydedilemedi.';
        }
    }

    function renderLeaderboard(entries) {
        if (!leaderboardElement) {
            return;
        }

        leaderboardElement.innerHTML = '';

        if (!entries.length) {
            const empty = document.createElement('li');

            empty.className = 'leaderboard-empty';
            empty.textContent = 'Henüz kayıtlı skor yok.';

            leaderboardElement.appendChild(empty);

            return;
        }

        const medals = ['🥇', '🥈', '🥉'];

        entries.forEach((entry, index) => {
            const item = document.createElement('li');

            const rank = document.createElement('span');
            const player = document.createElement('span');
            const value = document.createElement('strong');

            rank.className = 'leaderboard-rank';
            rank.textContent = medals[index] || String(index + 1);

            player.className = 'leaderboard-player';
            player.textContent = entry.username;

            value.textContent = `${entry.score} puan`;

            item.append(
                rank,
                player,
                value
            );

            leaderboardElement.appendChild(item);
        });
    }

    function resetGame() {
        cancelAnimationFrame(animationFrame);

        board = createBoard();

        bag = [];

        current = null;
        next = null;

        score = 0;
        lines = 0;
        level = 1;

        dropInterval = 800;

        paused = false;
        ended = false;

        scoreStatus.textContent = '';

        pauseButton.textContent = 'Duraklat';

        hideOverlay();

        shuffleBag();

        next = createPiece();

        spawnPiece();

        updateStats();

        lastDrop = performance.now();

        draw();

        animationFrame = requestAnimationFrame(loop);
    }

    function loop(time) {
        if (!paused && !ended) {
            if (time - lastDrop >= dropInterval) {
                softDrop(false);

                lastDrop = time;
            }

            draw();
        }

        animationFrame = requestAnimationFrame(loop);
    }

    function action(name) {
        switch (name) {
            case 'left':
                move(-1);
                break;

            case 'right':
                move(1);
                break;

            case 'down':
                softDrop(true);
                break;

            case 'rotate':
                rotate();
                break;

            case 'drop':
                hardDrop();
                break;

            case 'pause':
                togglePause();
                break;
        }
    }

    document.addEventListener('keydown', event => {
        const gameKeys = [
            'ArrowLeft',
            'ArrowRight',
            'ArrowDown',
            'ArrowUp',
            ' ',
        ];

        if (gameKeys.includes(event.key)) {
            event.preventDefault();
        }

        switch (event.key) {
            case 'ArrowLeft':
                action('left');
                break;

            case 'ArrowRight':
                action('right');
                break;

            case 'ArrowDown':
                action('down');
                break;

            case 'ArrowUp':
                action('rotate');
                break;

            case ' ':
                action('drop');
                break;

            case 'p':
            case 'P':
            case 'Escape':
                action('pause');
                break;
        }
    });

    document
        .querySelectorAll('[data-tetris-action]')
        .forEach(button => {
            button.addEventListener(
                'pointerdown',
                () => action(
                    button.dataset.tetrisAction
                )
            );
        });

    newGameButton.addEventListener(
        'click',
        resetGame
    );

    pauseButton.addEventListener(
        'click',
        togglePause
    );

    document.addEventListener(
        'visibilitychange',
        () => {
            if (
                document.hidden
                && !paused
                && !ended
            ) {
                togglePause();
            }
        }
    );

    resetGame();
})();