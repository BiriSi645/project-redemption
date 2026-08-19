(() => {
    'use strict';
    const root = document.getElementById('shared-game'),
        board = document.getElementById('shared-board'),
        statusEl = document.getElementById('room-status');
    if (!root || !board) return;
    let room = JSON.parse(document.getElementById('initial-room-data').textContent);

    // Online Yılan kendi sürekli WebSocket game loop'unu kullanır.
    // Böylece PHP/Aiven polling tick zinciri tamamen devreden çıkar.
    if (room.game === 'snake' && window.ProjectSnakeRealtime?.mount) {
        window.ProjectSnakeRealtime.mount({ root, board, statusEl, room });
        return;
    }

    let
        selected = null,
        mode = 'reveal',
        sending = false,
        loadingState = false,
        pollTimer = null,
        failures = 0,
        leaving = false,
        preservingRoom = false,
        realtimeConnected = false,
        queuedSnakeDirection = null,
        snakeAnimationFrame = null,
        snakeVisualFrom = null,
        snakeVisualTo = null,
        snakeVisualStartedAt = 0,
        snakeVisualSignature = '';
    function playerClass(owner) {
        if (!owner) return '';
        return Number(owner) === Number(room.host.id) ? 'host-move' : 'guest-move';
    }
    function updateStatus() {
        const guest = document.getElementById('guest-name'),
            legend = document.getElementById('guest-legend');
        guest.textContent = room.guest?.username || 'Bekleniyor…';
        legend.textContent = room.guest?.username || 'Oyuncu 2';
        statusEl.className = `room-status ${room.status}`;
        if (room.status === 'waiting')
            statusEl.textContent = `Arkadaşınızın katılması bekleniyor. Oda kodu: ${room.code}`;
        else if (room.status === 'completed') {
            if (room.game === 'snake') {
                const winner = Number(room.state.winnerId || 0);
                if (!winner) {
                    statusEl.textContent = 'Oyun berabere tamamlandı.';
                } else {
                    const resultText =
                        winner === Number(room.currentUserId)
                            ? 'Kazandınız!'
                            : 'Rakibiniz kazandı.';
                    const reasonText =
                        room.state.reason === 'collision'
                            ? ' Bir oyuncu çarpıştı.'
                            : ' Hedef uzunluğa ulaşıldı.';
                    statusEl.textContent = resultText + reasonText;
                }
            } else if (room.game === 'minesweeper' && room.state.lost)
                statusEl.textContent = 'Bir mayın açıldı. Bu oyun tamamlandı.';
            else if (room.game === 'sudoku' && room.state.failed)
                statusEl.textContent = 'Üç yanlış hakkınızı kullandınız. Oyun bitti.';
            else {
                const elapsed =
                    room.state.completedAt && room.state.startedAt
                        ? room.state.completedAt - room.state.startedAt
                        : null;
                statusEl.textContent = `Tebrikler, oyunu birlikte tamamladınız${elapsed ? `! Süre: ${elapsed} sn` : '!'}`;
            }
        } else if (room.game === 'snake') {
            statusEl.textContent =
                `${room.host.username}: ${room.state.snakes.host.length} · ` +
                `${room.guest?.username || 'Oyuncu 2'}: ${room.state.snakes.guest.length} · ` +
                `Hedef: ${room.state.targetLength}`;
        } else if (room.game === 'sudoku') {
            const mistakes = Number(room.state.mistakes || 0);
            statusEl.textContent = `Oyun başladı — toplam ${3 - mistakes} yanlış hakkınız kaldı (${mistakes} / 3).`;
        } else
            statusEl.textContent =
                'Oyun başladı — yaptığınız hamle arkadaşınızın ekranında da görünecek.';
    }
    function render() {
        updateStatus();
        if (room.game === 'sudoku') renderSudoku();
        else if (room.game === 'snake') renderSnake();
        else renderMines();
        const controls = document.getElementById('room-controls');
        controls.style.pointerEvents = room.status === 'playing' ? 'auto' : 'none';
        controls.style.opacity = room.status === 'playing' ? '1' : '.5';
    }
    function renderSudoku() {
        board.innerHTML = '';
        const fragment = document.createDocumentFragment();
        room.state.values.forEach((value, index) => {
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'shared-cell';
            cell.dataset.index = String(index);
            cell.textContent = value === '0' ? '' : value;
            if (room.state.puzzle[index] !== '0') cell.classList.add('given');
            else {
                const ownerClass = playerClass(room.state.owners[index]);
                if (ownerClass) cell.classList.add(ownerClass);
            }
            if (index === selected) cell.classList.add('selected');
            cell.disabled = room.status !== 'playing' || room.state.puzzle[index] !== '0';
            fragment.appendChild(cell);
        });
        board.appendChild(fragment);
    }
    function renderMines() {
        const state = room.state,
            total = state.rows * state.cols,
            mines = new Set(state.minesFound || []),
            revealed = new Set(state.revealed),
            flags = new Set(state.flags),
            fragment = document.createDocumentFragment();
        board.innerHTML = '';
        board.style.gridTemplateColumns = `repeat(${state.cols},minmax(0,1fr))`;
        board.style.maxWidth = `${Math.min(580, state.cols * 37)}px`;
        for (let index = 0; index < total; index++) {
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'shared-cell';
            cell.dataset.index = String(index);
            if (revealed.has(index)) {
                cell.classList.add('revealed');
                const ownerClass = playerClass(state.revealOwners[String(index)]);
                if (ownerClass) cell.classList.add(ownerClass);
                const number = state.numbers[String(index)];
                if (number) {
                    cell.dataset.number = String(number);
                    cell.textContent = String(number);
                }
            } else if (flags.has(index)) {
                cell.classList.add('flagged');
                const ownerClass = playerClass(state.flagOwners[String(index)]);
                if (ownerClass) cell.classList.add(ownerClass);
                cell.textContent = '🚩';
            } else if (mines.has(index)) {
                cell.classList.add('mine');
                cell.textContent = '💣';
            }
            cell.disabled = room.status !== 'playing';
            fragment.appendChild(cell);
        }
        board.appendChild(fragment);
    }

    function snakeSnapshot(state) {
        return {
            grid: Number(state.grid),
            food: { x: Number(state.food.x), y: Number(state.food.y) },
            snakes: {
                host: state.snakes.host.map((part) => ({ x: Number(part.x), y: Number(part.y) })),
                guest: state.snakes.guest.map((part) => ({ x: Number(part.x), y: Number(part.y) })),
            },
        };
    }

    function snakeSignature(state) {
        return JSON.stringify({
            food: state.food,
            host: state.snakes.host,
            guest: state.snakes.guest,
        });
    }

    function interpolateSnake(fromSnake, toSnake, progress) {
        return toSnake.map((target, index) => {
            const source = fromSnake[Math.min(index, Math.max(0, fromSnake.length - 1))] || target;
            return {
                x: source.x + (target.x - source.x) * progress,
                y: source.y + (target.y - source.y) * progress,
            };
        });
    }

    function currentSnakeVisual(now = performance.now()) {
        if (!snakeVisualTo) return null;
        if (!snakeVisualFrom) return snakeVisualTo;

        const duration = 175;
        const progress = Math.max(0, Math.min(1, (now - snakeVisualStartedAt) / duration));
        const eased = progress * progress * (3 - 2 * progress);

        return {
            grid: snakeVisualTo.grid,
            food: snakeVisualTo.food,
            snakes: {
                host: interpolateSnake(snakeVisualFrom.snakes.host, snakeVisualTo.snakes.host, eased),
                guest: interpolateSnake(snakeVisualFrom.snakes.guest, snakeVisualTo.snakes.guest, eased),
            },
        };
    }

    function snakeCanvas() {
        let canvas = board.querySelector('canvas');

        if (!canvas) {
            board.innerHTML = '';
            canvas = document.createElement('canvas');
            canvas.className = 'shared-snake-canvas';
            canvas.width = 720;
            canvas.height = 720;
            canvas.setAttribute('aria-label', 'İki oyunculu Yılan oyun alanı');
            board.appendChild(canvas);
        }

        return canvas;
    }

    function drawSnakeFrame() {
        snakeAnimationFrame = null;
        if (room.game !== 'snake') return;

        const visual = currentSnakeVisual();
        if (!visual) return;

        const canvas = snakeCanvas();
        const context = canvas.getContext('2d');
        const grid = visual.grid;
        const size = canvas.width / grid;

        context.fillStyle = '#07140d';
        context.fillRect(0, 0, canvas.width, canvas.height);

        context.strokeStyle = 'rgba(255,255,255,.035)';
        context.lineWidth = 1;
        for (let i = 1; i < grid; i++) {
            context.beginPath();
            context.moveTo(i * size, 0);
            context.lineTo(i * size, canvas.height);
            context.stroke();

            context.beginPath();
            context.moveTo(0, i * size);
            context.lineTo(canvas.width, i * size);
            context.stroke();
        }

        const food = visual.food;
        context.fillStyle = '#ef4444';
        context.beginPath();
        context.arc(
            (food.x + 0.5) * size,
            (food.y + 0.5) * size,
            size * 0.34,
            0,
            Math.PI * 2,
        );
        context.fill();

        [
            ['host', '#22c55e', '#86efac'],
            ['guest', '#a855f7', '#e9d5ff'],
        ].forEach(([player, bodyColor, headColor]) => {
            visual.snakes[player].forEach((part, index) => {
                const inset = Math.max(1, Math.round(size * 0.08));
                context.fillStyle = index === 0 ? headColor : bodyColor;
                context.fillRect(
                    part.x * size + inset,
                    part.y * size + inset,
                    size - inset * 2,
                    size - inset * 2,
                );
            });
        });

        // Hedef kareye varana kadar 60 FPS çiz. Yeni server state'i gelirse
        // renderSnake() mevcut ara pozisyondan yeni hedefe yumuşak geçiş başlatır.
        if (snakeVisualFrom && performance.now() - snakeVisualStartedAt < 190) {
            snakeAnimationFrame = requestAnimationFrame(drawSnakeFrame);
        }
    }

    function renderSnake() {
        snakeCanvas();

        const signature = snakeSignature(room.state);
        if (signature === snakeVisualSignature) {
            if (!snakeAnimationFrame) {
                snakeAnimationFrame = requestAnimationFrame(drawSnakeFrame);
            }
            return;
        }

        const next = snakeSnapshot(room.state);
        const current = currentSnakeVisual();

        snakeVisualFrom = current || next;
        snakeVisualTo = next;
        snakeVisualStartedAt = performance.now();
        snakeVisualSignature = signature;

        if (snakeAnimationFrame) {
            cancelAnimationFrame(snakeAnimationFrame);
        }
        snakeAnimationFrame = requestAnimationFrame(drawSnakeFrame);
    }

    async function sendMove(data) {
        if (room.status !== 'playing') return;

        if (sending) {
            if (room.game === 'snake' && data.direction) {
                queuedSnakeDirection = data.direction;
            }
            return;
        }

        sending = true;
        const body = new URLSearchParams({
            ...data,
            [root.dataset.csrfName]: root.dataset.csrfHash,
        });
        try {
            const response = await fetch(root.dataset.moveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const result = await response.json();
            if (result.csrfHash) root.dataset.csrfHash = result.csrfHash;
            if (!response.ok || !result.success)
                throw new Error(result.message || 'Hamle gönderilemedi.');
            room = result.room;
            failures = 0;
            render();
            schedulePoll();
        } catch (error) {
            statusEl.textContent = error.message || 'Bağlantı kurulamadı; tekrar deneyin.';
        } finally {
            sending = false;

            if (room.game === 'snake' && queuedSnakeDirection && room.status === 'playing') {
                const nextDirection = queuedSnakeDirection;
                queuedSnakeDirection = null;
                sendMove({ direction: nextDirection });
            }
        }
    }
    board.addEventListener('click', (event) => {
        if (room.game === 'snake') return;
        const cell = event.target.closest('.shared-cell');
        if (!cell) return;
        const index = Number(cell.dataset.index);
        if (room.game === 'sudoku') {
            selected = index;
            render();
            return;
        }
        if (cell.classList.contains('revealed')) return;
        sendMove({ index: String(index), action: mode });
    });
    board.addEventListener('dblclick', (event) => {
        if (room.game !== 'minesweeper') return;
        const cell = event.target.closest('.shared-cell.revealed');
        if (!cell) return;
        event.preventDefault();
        event.stopPropagation();
        sendMove({ index: cell.dataset.index, action: 'chord' });
    });
    board.addEventListener('contextmenu', (event) => {
        if (room.game !== 'minesweeper') return;
        const cell = event.target.closest('.shared-cell');
        if (!cell) return;
        event.preventDefault();
        sendMove({ index: cell.dataset.index, action: 'flag' });
    });
    document.getElementById('room-controls').addEventListener('click', (event) => {
        const number = event.target.closest('[data-number]'),
            modeButton = event.target.closest('[data-mode]'),
            directionButton = event.target.closest('[data-direction]');
        if (number && selected !== null)
            sendMove({ index: String(selected), number: number.dataset.number });
        if (modeButton) {
            mode = modeButton.dataset.mode;
            document
                .querySelectorAll('[data-mode]')
                .forEach((item) => item.classList.toggle('active', item === modeButton));
        }
        if (directionButton) {
            sendMove({ direction: directionButton.dataset.direction });
        }
    });

    document.addEventListener('keydown', (event) => {
        if (room.game !== 'snake' || room.status !== 'playing') return;

        const directions = {
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

        const direction = directions[event.key];
        if (!direction) return;

        event.preventDefault();
        sendMove({ direction });
    });
    function pollDelay() {
        if (room.game === 'snake' && room.status === 'playing') {
            return Math.min(180 * 2 ** failures, 2500);
        }

        // WebSocket bağlıyken Sudoku/Mayın Tarlası state polling'i yapmıyoruz;
        // yalnızca oda presence'ını canlı tutan hafif heartbeat kalıyor.
        if (realtimeConnected) return 10000;
        if (document.hidden) return 8000;
        if (room.status === 'waiting') return 1500;

        return Math.min(750 * 2 ** failures, 10000);
    }
    function schedulePoll(immediate = false, overrideDelay = null) {
        clearTimeout(pollTimer);

        if (room.status === 'completed') {
            return;
        }

        const delay = immediate
            ? 0
            : (overrideDelay === null ? pollDelay() : Math.max(0, overrideDelay));

        pollTimer = setTimeout(poll, delay);
    }
    async function loadState() {
        if (loadingState) return;
        loadingState = true;
        try {
            const response = await fetch(root.dataset.stateUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                }),
                result = await response.json();
            if (response.ok && result.success) {
                const previousStatus = room.status;
                room = result.room;
                failures = 0;
                render();

                if (
                    room.game === 'snake' &&
                    previousStatus !== 'playing' &&
                    room.status === 'playing'
                ) {
                    schedulePoll(true);
                }
            }
        } finally {
            loadingState = false;
        }
    }
    async function poll() {
        if (sending) {
            schedulePoll();
            return;
        }

        const requestStartedAt = performance.now();

        try {
            const response = await fetch(root.dataset.versionUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                }),
                result = await response.json();
            if (!response.ok || !result.success) throw new Error();
            failures = 0;

            if (room.game === 'snake' && result.room) {
                room = result.room;
                render();
            } else if (Number(result.version) !== Number(room.version)) {
                await loadState();
            }
        } catch (error) {
            failures = Math.min(failures + 1, 4);
        } finally {
            if (room.game === 'snake' && room.status === 'playing') {
                // 180 ms'yi response tamamlandıktan SONRA beklemek yerine,
                // iki request başlangıcı arasındaki hedef süre olarak koru.
                // Böylece ağ süresi oyun tick'ine tekrar eklenmez.
                const elapsed = performance.now() - requestStartedAt;
                schedulePoll(false, Math.max(0, 180 - elapsed));
            } else {
                schedulePoll();
            }
        }
    }
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) schedulePoll(true);
        else schedulePoll();
    });
    document.addEventListener('project:realtime-connected', () => {
        realtimeConnected = true;

        clearTimeout(pollTimer);

        loadState();
        schedulePoll();
    });
    document.addEventListener('project:realtime-disconnected', () => {
        realtimeConnected = false;
        schedulePoll(true);
    });
    document.addEventListener('project:realtime-game', (event) => {
        const update = event.detail;

        if (
            String(update?.roomCode || '').toUpperCase() !==
                String(room.code).toUpperCase() ||
            Number(update?.version) <= Number(room.version)
        ) {
            return;
        }

        loadState();
    });
    document.getElementById('leave-room-form')?.addEventListener('submit', () => {
        leaving = true;
    });
    document.querySelectorAll('[data-room-preserving-action]').forEach((form) =>
        form.addEventListener('submit', () => {
            preservingRoom = true;
        }),
    );
    document.addEventListener('project:room-preserve', () => {
        preservingRoom = true;
    });
    window.addEventListener(
        'pagehide',
        () => {
            if (leaving || preservingRoom) return;
            leaving = true;
            const body = new URLSearchParams({ [root.dataset.csrfName]: root.dataset.csrfHash });
            fetch(root.dataset.leaveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
                keepalive: true,
            }).catch(() => {});
        },
        { once: true },
    );
    render();
    schedulePoll();
})();
