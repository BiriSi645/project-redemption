(() => {
    'use strict';

    const root = document.getElementById('sudoku-game');
    const board = document.getElementById('sudoku-board');

    if (!root || !board) return;

    const basePuzzles = JSON.parse(
        document.getElementById('sudoku-puzzle-data').textContent
    );

    const difficulty =
        document.getElementById('sudoku-difficulty');

    const timeEl =
        document.getElementById('sudoku-time');

    const errorsEl =
        document.getElementById('sudoku-errors');

    const bestEl =
        document.getElementById('sudoku-best');

    const message =
        document.getElementById('sudoku-message');

    const status =
        document.getElementById('sudoku-score-status');

    const leaderboard =
        document.getElementById('sudoku-leaderboard');

    const subtitle =
        document.getElementById(
            'game-leaderboard-subtitle'
        );

    const restartButton =
        document.getElementById('sudoku-restart');

    const scoreData = JSON.parse(
        document.getElementById(
            'sudoku-score-data'
        ).textContent
    );

    const labels = {
        beginner: 'Başlangıç seviyesi',
        medium: 'Orta seviye',
        expert: 'Zor seviye',
    };

    const storageKey =
        `project-redemption:sudoku:${root.dataset.userId}`;

    let values;
    let givens;
    let selected;
    let errors;
    let seconds;
    let timer;
    let ended;
    let paused;

    let currentPuzzle = '';
    let currentSolution = '';

    let resetRequest = 0;

    const format = (value) =>
        `${Math.floor(value / 60)}:${String(
            value % 60
        ).padStart(2, '0')}`;

    const clearSaved = () => {
        try {
            localStorage.removeItem(storageKey);
        } catch (error) {
            // localStorage kullanılamıyorsa oyun
            // çalışmaya devam eder.
        }
    };

    function save() {
        if (ended) {
            clearSaved();
            return;
        }

        try {
            localStorage.setItem(
                storageKey,
                JSON.stringify({
                    version: 2,
                    difficulty: difficulty.value,
                    puzzle: currentPuzzle,
                    solution: currentSolution,
                    values,
                    errors,
                    seconds,
                })
            );
        } catch (error) {
            // localStorage kullanılamıyorsa
            // kayıt yapılmadan devam edilir.
        }
    }

    function startTimer() {
        clearInterval(timer);

        paused = false;

        timer = setInterval(() => {
            seconds++;

            timeEl.textContent =
                format(seconds);

            save();
        }, 1000);
    }

    function setLevelUi() {
        const best = Number(
            scoreData.personalBests[
                difficulty.value
            ]
        );

        bestEl.textContent =
            best
                ? format(best)
                : '—';

        subtitle.textContent =
            labels[difficulty.value];

        renderLeaderboard(
            scoreData.leaderboards[
                difficulty.value
            ] || []
        );
    }

    async function fetchPuzzle(level) {
        const response = await fetch(
            `${root.dataset.puzzleUrl}/${encodeURIComponent(level)}`,
            {
                headers: {
                    'X-Requested-With':
                        'XMLHttpRequest',
                },
                cache: 'no-store',
            }
        );

        const data =
            await response.json();

        if (
            !response.ok
            || !data.success
            || !data.puzzle
        ) {
            throw new Error(
                'Puzzle üretilemedi.'
            );
        }

        return data.puzzle;
    }

    async function reset() {
        const requestId =
            ++resetRequest;

        clearInterval(timer);
        clearSaved();

        ended = true;
        paused = true;
        selected = null;

        restartButton.disabled = true;

        message.className =
            'sudoku-message';

        message.textContent =
            'Yeni Sudoku hazırlanıyor…';

        status.textContent = '';

        const level =
            difficulty.value;

        let data;

        try {
            data =
                await fetchPuzzle(level);
        } catch (error) {
            /*
             * Sunucuya ulaşılamazsa
             * sayfaya gömülü geçerli puzzle
             * ile oyun yine açılabilsin.
             */
            data =
                basePuzzles[level];
        }

        /*
         * Kullanıcı arka arkaya çok hızlı
         * yeni oyun isterse eski isteğin
         * sonucu yeni tahtanın üzerine
         * yazılmasın.
         */
        if (
            requestId !== resetRequest
        ) {
            return;
        }

        currentPuzzle =
            String(
                data.puzzle || ''
            );

        currentSolution =
            String(
                data.solution || ''
            );

        if (
            currentPuzzle.length !== 81
            || currentSolution.length !== 81
        ) {
            message.className =
                'sudoku-message lose';

            message.textContent =
                'Sudoku şu anda oluşturulamadı.';

            restartButton.disabled = false;

            return;
        }

        values =
            currentPuzzle
                .split('')
                .map(Number);

        givens =
            values.map(Boolean);

        selected = null;

        errors = 0;
        seconds = 0;

        ended = false;
        paused = false;

        timeEl.textContent = '0:00';
        errorsEl.textContent = '0 / 3';

        message.className =
            'sudoku-message';

        message.textContent =
            'Bir boş hücre seçip sayı girin.';

        setLevelUi();
        render();

        restartButton.disabled = false;

        startTimer();

        save();
    }

    function restore() {
        let saved;

        try {
            saved = JSON.parse(
                localStorage.getItem(
                    storageKey
                ) || 'null'
            );
        } catch (error) {
            return false;
        }

        if (
            !saved
            || saved.version !== 2
            || !basePuzzles[
                saved.difficulty
            ]
            || typeof saved.puzzle
                !== 'string'
            || saved.puzzle.length
                !== 81
            || typeof saved.solution
                !== 'string'
            || saved.solution.length
                !== 81
            || !Array.isArray(
                saved.values
            )
            || saved.values.length
                !== 81
        ) {
            clearSaved();

            return false;
        }

        difficulty.value =
            saved.difficulty;

        currentPuzzle =
            saved.puzzle;

        currentSolution =
            saved.solution;

        /*
         * Kaydedilen oyun üzerinde
         * başlangıçta sabit olan sayıların
         * değişmediğini kontrol et.
         */
        if (
            saved.values.some(
                (value, index) =>
                    currentPuzzle[index]
                        !== '0'
                    && Number(value)
                        !== Number(
                            currentPuzzle[index]
                        )
            )
        ) {
            clearSaved();

            return false;
        }

        values =
            saved.values.map(Number);

        givens =
            currentPuzzle
                .split('')
                .map(Number)
                .map(Boolean);

        selected = null;

        errors = Math.max(
            0,
            Math.min(
                2,
                Number(saved.errors) || 0
            )
        );

        seconds = Math.max(
            0,
            Number(saved.seconds) || 0
        );

        ended = false;
        paused = true;

        timeEl.textContent =
            format(seconds);

        errorsEl.textContent =
            `${errors} / 3`;

        message.className =
            'sudoku-message';

        message.textContent =
            'Oyun kaldığınız yerden yüklendi ve duraklatıldı. Bir hücre seçerek devam edin.';

        status.textContent = '';

        setLevelUi();
        render();

        return true;
    }

    function resume() {
        if (
            !paused
            || ended
        ) {
            return;
        }

        startTimer();

        message.textContent =
            'Oyun devam ediyor.';
    }

    function render() {
        board.innerHTML = '';

        values.forEach(
            (value, index) => {
                const cell =
                    document.createElement(
                        'button'
                    );

                cell.type = 'button';

                cell.className =
                    'sudoku-cell';

                cell.dataset.index =
                    String(index);

                cell.textContent =
                    value || '';

                cell.setAttribute(
                    'role',
                    'gridcell'
                );

                cell.setAttribute(
                    'aria-label',
                    value
                        ? `Satır ${Math.floor(index / 9) + 1}, sütun ${(index % 9) + 1}, ${value}`
                        : `Satır ${Math.floor(index / 9) + 1}, sütun ${(index % 9) + 1}, boş`
                );

                if (givens[index]) {
                    cell.classList.add(
                        'given'
                    );
                }

                if (selected !== null) {
                    const sameRow =
                        Math.floor(
                            selected / 9
                        )
                        ===
                        Math.floor(
                            index / 9
                        );

                    const sameCol =
                        selected % 9
                        ===
                        index % 9;

                    const sameBox =
                        Math.floor(
                            Math.floor(
                                selected / 9
                            ) / 3
                        )
                        ===
                        Math.floor(
                            Math.floor(
                                index / 9
                            ) / 3
                        )
                        &&
                        Math.floor(
                            (index % 9) / 3
                        )
                        ===
                        Math.floor(
                            (selected % 9) / 3
                        );

                    if (
                        sameRow
                        || sameCol
                        || sameBox
                    ) {
                        cell.classList.add(
                            'related'
                        );
                    }

                    if (
                        index
                        === selected
                    ) {
                        cell.classList.add(
                            'selected'
                        );
                    }
                }

                board.appendChild(cell);
            }
        );

        document
            .querySelectorAll(
                '.sudoku-pad [data-number]'
            )
            .forEach((button) => {
                button.hidden =
                    values.filter(
                        (value) =>
                            value
                            ===
                            Number(
                                button.dataset.number
                            )
                    ).length >= 9;
            });
    }

    function select(index) {
        if (
            ended
            || givens[index]
        ) {
            return;
        }

        resume();

        selected = index;

        render();
    }

    function enter(number) {
        if (
            ended
            || selected === null
        ) {
            return;
        }

        resume();

        const solution =
            Number(
                currentSolution[
                    selected
                ]
            );

        if (
            number !== solution
        ) {
            errors++;

            errorsEl.textContent =
                `${errors} / 3`;

            const cell =
                board.querySelector(
                    `[data-index="${selected}"]`
                );

            cell?.classList.add(
                'wrong'
            );

            if (errors >= 3) {
                ended = true;

                clearInterval(timer);
                clearSaved();

                message.className =
                    'sudoku-message lose';

                message.textContent =
                    'Üç yanlış hakkınızı kullandınız. Oyun bitti.';
            } else {
                message.textContent =
                    `Yanlış sayı. ${3 - errors} yanlış hakkınız kaldı.`;

                save();
            }

            return;
        }

        values[selected] = number;

        message.textContent =
            'Doğru! Devam edin.';

        const completed =
            values.every(
                (value, index) =>
                    value
                    ===
                    Number(
                        currentSolution[
                            index
                        ]
                    )
            );

        if (completed) {
            ended = true;

            clearInterval(timer);
            clearSaved();

            message.className =
                'sudoku-message win';

            message.textContent =
                `Tebrikler! Sudoku’yu ${format(
                    Math.max(
                        1,
                        seconds
                    )
                )} sürede çözdünüz.`;

            saveScore(
                Math.max(
                    1,
                    seconds
                )
            );
        } else {
            save();
        }

        render();
    }

    async function saveScore(
        finalTime
    ) {
        status.textContent =
            'Süreniz kaydediliyor…';

        const level =
            difficulty.value;

        const body =
            new URLSearchParams({
                game: 'sudoku',
                difficulty: level,
                score:
                    String(finalTime),
                [root.dataset.csrfName]:
                    root.dataset.csrfHash,
            });

        try {
            const response =
                await fetch(
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

            const data =
                await response.json();

            if (
                !response.ok
                || !data.success
            ) {
                throw new Error();
            }

            scoreData.personalBests[
                level
            ] = Number(
                data.personalBest
            );

            scoreData.leaderboards[
                level
            ] = data.leaderboard;

            bestEl.textContent =
                format(
                    data.personalBest
                );

            renderLeaderboard(
                data.leaderboard
            );

            status.textContent =
                data.improved
                    ? 'Yeni kişisel rekorunuz kaydedildi!'
                    : 'Süreniz kaydedildi; kişisel rekorunuz değişmedi.';
        } catch (error) {
            status.textContent =
                'Süreniz şu anda kaydedilemedi.';
        }
    }

    function renderLeaderboard(
        entries
    ) {
        leaderboard.innerHTML = '';

        if (!entries.length) {
            const item =
                document.createElement(
                    'li'
                );

            item.className =
                'leaderboard-empty';

            item.textContent =
                'Henüz kayıtlı skor yok.';

            leaderboard.appendChild(
                item
            );

            return;
        }

        const medals = [
            '🥇',
            '🥈',
            '🥉',
        ];

        entries.forEach(
            (entry, index) => {
                const item =
                    document.createElement(
                        'li'
                    );

                const rank =
                    document.createElement(
                        'span'
                    );

                const player =
                    document.createElement(
                        'span'
                    );

                const value =
                    document.createElement(
                        'strong'
                    );

                rank.textContent =
                    medals[index];

                player.className =
                    'leaderboard-player';

                player.textContent =
                    entry.username;

                value.textContent =
                    format(
                        Number(
                            entry.score
                        )
                    );

                item.append(
                    rank,
                    player,
                    value
                );

                leaderboard.appendChild(
                    item
                );
            }
        );
    }

    board.addEventListener(
        'click',
        (event) => {
            const cell =
                event.target.closest(
                    '.sudoku-cell'
                );

            if (cell) {
                select(
                    Number(
                        cell.dataset.index
                    )
                );
            }
        }
    );

    document
        .querySelector(
            '.sudoku-pad'
        )
        .addEventListener(
            'click',
            (event) => {
                const button =
                    event.target.closest(
                        '[data-number]'
                    );

                if (
                    button
                    && !button.hidden
                ) {
                    enter(
                        Number(
                            button.dataset.number
                        )
                    );
                }
            }
        );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                /^[1-9]$/.test(
                    event.key
                )
            ) {
                enter(
                    Number(event.key)
                );
            }
        }
    );

    difficulty.addEventListener(
        'change',
        () => void reset()
    );

    restartButton.addEventListener(
        'click',
        () => void reset()
    );

    document.addEventListener(
        'visibilitychange',
        () => {
            if (
                document.hidden
                && !paused
                && !ended
            ) {
                clearInterval(timer);

                paused = true;

                message.textContent =
                    'Oyun duraklatıldı. Bir hücre seçerek devam edin.';

                save();
            }
        }
    );

    window.addEventListener(
        'pagehide',
        () => {
            clearInterval(timer);

            save();
        }
    );

    if (!restore()) {
        void reset();
    }
})();