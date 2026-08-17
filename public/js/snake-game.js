(() => {
    'use strict';
    const root = document.getElementById('snake-game');
    const canvas = document.getElementById('snake-board');
    if (!root || !canvas) return;
    const context = canvas.getContext('2d');
    const scoreElement = document.getElementById('snake-score');
    const bestElement = document.getElementById('snake-best');
    const levelElement = document.getElementById('snake-level');
    const overlay = document.getElementById('snake-overlay');
    const overlayTitle = document.getElementById('snake-overlay-title');
    const overlayText = document.getElementById('snake-overlay-text');
    const startButton = document.getElementById('snake-start');
    const pauseButton = document.getElementById('snake-pause');
    const restartButton = document.getElementById('snake-restart');
    const scoreStatus = document.getElementById('snake-score-status');
    const leaderboardElement = document.getElementById('snake-leaderboard');
    const gridSize = 20;
    const cell = canvas.width / gridSize;
    const directions = {
        up: { x: 0, y: -1 },
        down: { x: 0, y: 1 },
        left: { x: -1, y: 0 },
        right: { x: 1, y: 0 },
    };
    const storageKey = `project-redemption:snake:${root.dataset.userId}`;
    let snake, food, direction, pendingDirection, score, level, timer, running, paused, ended;
    let touchStart = null;
    let best = Number(root.dataset.personalBest) || 0;
    bestElement.textContent = String(best);

    const clearSaved = () => {
        try {
            localStorage.removeItem(storageKey);
        } catch (error) {}
    };
    function directionName(value) {
        return (
            Object.keys(directions).find(
                (name) => directions[name].x === value.x && directions[name].y === value.y,
            ) || 'right'
        );
    }
    function save() {
        if (ended || !running) return ended ? clearSaved() : undefined;
        try {
            localStorage.setItem(
                storageKey,
                JSON.stringify({
                    version: 1,
                    snake,
                    food,
                    direction: directionName(direction),
                    pendingDirection: directionName(pendingDirection),
                    score,
                    level,
                }),
            );
        } catch (error) {}
    }
    function restore() {
        let saved;
        try {
            saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
        } catch (error) {
            return false;
        }
        if (
            !saved ||
            saved.version !== 1 ||
            !Array.isArray(saved.snake) ||
            saved.snake.length < 3 ||
            !saved.food ||
            !directions[saved.direction] ||
            !directions[saved.pendingDirection]
        )
            return false;
        const validPart = (part) =>
            Number.isInteger(part.x) &&
            Number.isInteger(part.y) &&
            part.x >= 0 &&
            part.x < gridSize &&
            part.y >= 0 &&
            part.y < gridSize;
        if (!saved.snake.every(validPart) || !validPart(saved.food)) {
            clearSaved();
            return false;
        }
        clearTimeout(timer);
        snake = saved.snake;
        food = saved.food;
        direction = directions[saved.direction];
        pendingDirection = directions[saved.pendingDirection];
        score = Math.max(0, Number(saved.score) || 0);
        level = Math.max(1, Math.min(9, Number(saved.level) || 1));
        running = true;
        paused = true;
        ended = false;
        scoreElement.textContent = String(score);
        levelElement.textContent = String(level);
        if (score > best) {
            best = score;
            bestElement.textContent = String(best);
        }
        startButton.disabled = true;
        pauseButton.disabled = false;
        pauseButton.textContent = 'Devam et';
        showOverlay(
            'Oyun duraklatıldı',
            'Kaldığınız yerden devam etmek için düğmeye veya boşluk tuşuna basın.',
        );
        draw();
        return true;
    }

    function reset() {
        clearTimeout(timer);
        clearSaved();
        snake = [
            { x: 10, y: 10 },
            { x: 9, y: 10 },
            { x: 8, y: 10 },
        ];
        direction = directions.right;
        pendingDirection = directions.right;
        score = 0;
        level = 1;
        running = false;
        paused = false;
        ended = false;
        food = createFood();
        scoreElement.textContent = '0';
        levelElement.textContent = '1';
        pauseButton.disabled = true;
        pauseButton.textContent = 'Duraklat';
        startButton.disabled = false;
        showOverlay(
            'Oyuna hazır mısınız?',
            'Başlat düğmesine basın veya yön tuşlarından birini kullanın.',
        );
        draw();
    }

    function createFood() {
        const empty = [];
        for (let y = 0; y < gridSize; y++)
            for (let x = 0; x < gridSize; x++)
                if (!snake?.some((part) => part.x === x && part.y === y)) empty.push({ x, y });
        return empty[Math.floor(Math.random() * empty.length)] || { x: 0, y: 0 };
    }

    function start() {
        if (ended) reset();
        if (running && !paused) return;
        running = true;
        paused = false;
        overlay.hidden = true;
        startButton.disabled = true;
        pauseButton.disabled = false;
        pauseButton.textContent = 'Duraklat';
        save();
        schedule();
    }

    function schedule() {
        clearTimeout(timer);
        if (running && !paused) timer = setTimeout(tick, Math.max(65, 145 - (level - 1) * 10));
    }

    function tick() {
        direction = pendingDirection;
        const head = { x: snake[0].x + direction.x, y: snake[0].y + direction.y };
        const ate = head.x === food.x && head.y === food.y;
        const bodyToCheck = ate ? snake : snake.slice(0, -1);
        if (
            head.x < 0 ||
            head.x >= gridSize ||
            head.y < 0 ||
            head.y >= gridSize ||
            bodyToCheck.some((part) => part.x === head.x && part.y === head.y)
        )
            return gameOver();
        snake.unshift(head);
        if (ate) {
            score += 10;
            level = Math.min(9, Math.floor(score / 50) + 1);
            food = createFood();
            scoreElement.textContent = String(score);
            levelElement.textContent = String(level);
            if (score > best) {
                best = score;
                bestElement.textContent = String(best);
            }
        } else snake.pop();
        draw();
        save();
        schedule();
    }

    function setDirection(name, autoStart = true) {
        const next = directions[name];
        if (!next) return;
        if (next.x === -direction.x && next.y === -direction.y) return;
        pendingDirection = next;
        if (autoStart && !running) start();
    }

    function togglePause() {
        if (!running || ended) return;
        paused = !paused;
        pauseButton.textContent = paused ? 'Devam et' : 'Duraklat';
        if (paused) {
            clearTimeout(timer);
            showOverlay('Oyun duraklatıldı', 'Devam etmek için düğmeye veya boşluk tuşuna basın.');
            save();
        } else {
            overlay.hidden = true;
            save();
            schedule();
        }
    }

    function gameOver() {
        running = false;
        ended = true;
        clearTimeout(timer);
        clearSaved();
        startButton.disabled = false;
        pauseButton.disabled = true;
        showOverlay('Oyun bitti', `Skorunuz: ${score} · Yeniden denemek için başlatın.`);
        if (score > 0) saveScore();
    }
    async function saveScore() {
        scoreStatus.textContent = 'Skor kaydediliyor…';
        const body = new URLSearchParams({
            game: 'snake',
            difficulty: 'default',
            score: String(score),
            [root.dataset.csrfName]: root.dataset.csrfHash,
        });
        try {
            const response = await fetch(root.dataset.scoreUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const data = await response.json();
            if (!response.ok || !data.success)
                throw new Error(data.message || 'Skor kaydedilemedi.');
            best = Number(data.personalBest) || best;
            bestElement.textContent = String(best);
            renderLeaderboard(data.leaderboard);
            scoreStatus.textContent = data.improved
                ? 'Yeni kişisel rekorunuz kaydedildi!'
                : 'Skor kaydedildi; kişisel rekorunuz değişmedi.';
        } catch (error) {
            scoreStatus.textContent = 'Skor şu anda kaydedilemedi.';
        }
    }
    function renderLeaderboard(entries) {
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
            const item = document.createElement('li'),
                rank = document.createElement('span'),
                player = document.createElement('span'),
                value = document.createElement('strong');
            rank.className = 'leaderboard-rank';
            rank.textContent = medals[index];
            player.className = 'leaderboard-player';
            player.textContent = entry.username;
            value.textContent = `${entry.score} puan`;
            item.append(rank, player, value);
            leaderboardElement.appendChild(item);
        });
    }
    function showOverlay(title, text) {
        overlayTitle.textContent = title;
        overlayText.textContent = text;
        overlay.hidden = false;
    }
    function draw() {
        context.fillStyle = '#07140d';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.strokeStyle = 'rgba(255,255,255,.035)';
        context.lineWidth = 1;
        for (let i = 1; i < gridSize; i++) {
            context.beginPath();
            context.moveTo(i * cell, 0);
            context.lineTo(i * cell, canvas.height);
            context.stroke();
            context.beginPath();
            context.moveTo(0, i * cell);
            context.lineTo(canvas.width, i * cell);
            context.stroke();
        }
        context.fillStyle = '#ef4444';
        context.beginPath();
        context.arc((food.x + 0.5) * cell, (food.y + 0.5) * cell, cell * 0.34, 0, Math.PI * 2);
        context.fill();
        snake.forEach((part, index) => {
            context.fillStyle = index === 0 ? '#4ade80' : '#22c55e';
            const inset = index === 0 ? 2 : 3;
            context.fillRect(
                part.x * cell + inset,
                part.y * cell + inset,
                cell - inset * 2,
                cell - inset * 2,
            );
        });
    }
    document
        .querySelectorAll('[data-direction]')
        .forEach((button) =>
            button.addEventListener('pointerdown', () => setDirection(button.dataset.direction)),
        );
    document.addEventListener('keydown', (event) => {
        const map = {
            ArrowUp: 'up',
            w: 'up',
            W: 'up',
            ArrowDown: 'down',
            s: 'down',
            S: 'down',
            ArrowLeft: 'left',
            a: 'left',
            A: 'left',
            ArrowRight: 'right',
            d: 'right',
            D: 'right',
        };
        if (map[event.key]) {
            event.preventDefault();
            setDirection(map[event.key]);
        } else if (event.code === 'Space') {
            event.preventDefault();
            togglePause();
        }
    });
    canvas.addEventListener(
        'touchstart',
        (event) => {
            const t = event.changedTouches[0];
            touchStart = { x: t.clientX, y: t.clientY };
        },
        { passive: true },
    );
    canvas.addEventListener(
        'touchend',
        (event) => {
            if (!touchStart) return;
            const t = event.changedTouches[0],
                dx = t.clientX - touchStart.x,
                dy = t.clientY - touchStart.y;
            if (Math.max(Math.abs(dx), Math.abs(dy)) > 20)
                setDirection(
                    Math.abs(dx) > Math.abs(dy)
                        ? dx > 0
                            ? 'right'
                            : 'left'
                        : dy > 0
                          ? 'down'
                          : 'up',
                );
            touchStart = null;
        },
        { passive: true },
    );
    startButton.addEventListener('click', start);
    pauseButton.addEventListener('click', togglePause);
    restartButton.addEventListener('click', reset);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && running && !paused) togglePause();
    });
    window.addEventListener('pagehide', () => {
        clearTimeout(timer);
        save();
    });
    if (!restore()) reset();
})();
