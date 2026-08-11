<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    .timer-shell {
        max-width: 760px;
        margin: 0 auto;
        text-align: center;
    }

    .timer-description {
        margin: 0 0 28px;
        color: #6b7280;
    }

    .timer-display {
        padding: 36px 20px;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: #f9fafb;
        font-family: "Courier New", monospace;
        font-size: clamp(44px, 10vw, 82px);
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        letter-spacing: -4px;
        color: #111827;
    }

    .timer-display.running {
        border-color: #86efac;
        background: #f0fdf4;
        color: #166534;
    }

    .timer-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
        margin: 24px 0 32px;
    }

    .timer-action {
        min-width: 120px;
        padding: 12px 18px;
        border: 0;
        border-radius: 9px;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    .timer-action:disabled {
        cursor: not-allowed;
        opacity: .45;
    }

    .start { background: #16a34a; color: #fff; }
    .pause { background: #f59e0b; color: #111827; }
    .lap { background: #2563eb; color: #fff; }
    .reset { background: #e5e7eb; color: #111827; }

    .laps {
        margin-top: 28px;
        text-align: left;
    }

    .laps-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }

    .laps-header h2 { margin: 0; }

    .clear-laps {
        border: 0;
        background: transparent;
        color: #dc2626;
        cursor: pointer;
    }

    .lap-list {
        padding: 0;
        margin: 0;
        list-style: none;
        border-top: 1px solid #e5e7eb;
    }

    .lap-list li {
        display: flex;
        justify-content: space-between;
        padding: 12px 4px;
        border-bottom: 1px solid #e5e7eb;
        font-variant-numeric: tabular-nums;
    }

    .empty-laps {
        padding: 18px 0;
        color: #6b7280;
    }
</style>

<section class="timer-shell" aria-labelledby="timer-title">
    <h1 id="timer-title">Kronometre</h1>
    <p class="timer-description">Çalışma sürenizi takip edin ve istediğiniz an tur kaydedin.</p>

    <div id="timer-display" class="timer-display" role="timer" aria-live="off">00:00:00.00</div>

    <div class="timer-actions">
        <button id="start-button" class="timer-action start" type="button">Başlat</button>
        <button id="pause-button" class="timer-action pause" type="button" disabled>Duraklat</button>
        <button id="lap-button" class="timer-action lap" type="button" disabled>Tur Kaydet</button>
        <button id="reset-button" class="timer-action reset" type="button" disabled>Sıfırla</button>
    </div>

    <div class="laps">
        <div class="laps-header">
            <h2>Turlar</h2>
            <button id="clear-laps-button" class="clear-laps" type="button" hidden>Turları Temizle</button>
        </div>
        <p id="empty-laps" class="empty-laps">Henüz tur kaydedilmedi.</p>
        <ol id="lap-list" class="lap-list"></ol>
    </div>
</section>

<script>
(() => {
    'use strict';

    const storageKey = 'project-redemption-timer-<?= (int) session()->get('user_id') ?>';
    const display = document.getElementById('timer-display');
    const startButton = document.getElementById('start-button');
    const pauseButton = document.getElementById('pause-button');
    const lapButton = document.getElementById('lap-button');
    const resetButton = document.getElementById('reset-button');
    const clearLapsButton = document.getElementById('clear-laps-button');
    const lapList = document.getElementById('lap-list');
    const emptyLaps = document.getElementById('empty-laps');

    let state = loadState();
    let animationFrame = null;

    function loadState() {
        const fallback = { elapsed: 0, startedAt: null, running: false, laps: [] };

        try {
            const saved = JSON.parse(localStorage.getItem(storageKey));

            if (!saved || typeof saved.elapsed !== 'number' || !Array.isArray(saved.laps)) {
                return fallback;
            }

            return {
                elapsed: Math.max(0, saved.elapsed),
                startedAt: typeof saved.startedAt === 'number' ? saved.startedAt : null,
                running: saved.running === true && typeof saved.startedAt === 'number',
                laps: saved.laps.filter(Number.isFinite),
            };
        } catch (error) {
            return fallback;
        }
    }

    function saveState() {
        localStorage.setItem(storageKey, JSON.stringify(state));
    }

    function elapsedNow() {
        return state.elapsed + (state.running && state.startedAt ? Date.now() - state.startedAt : 0);
    }

    function formatTime(milliseconds) {
        const centiseconds = Math.floor(milliseconds / 10);
        const hours = Math.floor(centiseconds / 360000);
        const minutes = Math.floor((centiseconds % 360000) / 6000);
        const seconds = Math.floor((centiseconds % 6000) / 100);
        const fraction = centiseconds % 100;

        return [hours, minutes, seconds]
            .map(value => String(value).padStart(2, '0'))
            .join(':') + '.' + String(fraction).padStart(2, '0');
    }

    function renderTimer() {
        display.textContent = formatTime(elapsedNow());

        if (state.running) {
            animationFrame = requestAnimationFrame(renderTimer);
        }
    }

    function renderControls() {
        const hasElapsed = elapsedNow() > 0;
        startButton.textContent = hasElapsed && !state.running ? 'Devam Et' : 'Başlat';
        startButton.disabled = state.running;
        pauseButton.disabled = !state.running;
        lapButton.disabled = !state.running;
        resetButton.disabled = !hasElapsed && state.laps.length === 0;
        display.classList.toggle('running', state.running);
    }

    function renderLaps() {
        lapList.innerHTML = '';

        state.laps.slice().reverse().forEach((lapTime, reverseIndex) => {
            const item = document.createElement('li');
            const label = document.createElement('span');
            const value = document.createElement('strong');
            label.textContent = `Tur ${state.laps.length - reverseIndex}`;
            value.textContent = formatTime(lapTime);
            item.append(label, value);
            lapList.appendChild(item);
        });

        const hasLaps = state.laps.length > 0;
        emptyLaps.hidden = hasLaps;
        clearLapsButton.hidden = !hasLaps;
    }

    function render() {
        cancelAnimationFrame(animationFrame);
        renderTimer();
        renderControls();
        renderLaps();
    }

    startButton.addEventListener('click', () => {
        state.startedAt = Date.now();
        state.running = true;
        saveState();
        render();
    });

    pauseButton.addEventListener('click', () => {
        state.elapsed = elapsedNow();
        state.startedAt = null;
        state.running = false;
        saveState();
        render();
    });

    lapButton.addEventListener('click', () => {
        state.laps.push(elapsedNow());
        saveState();
        renderLaps();
        renderControls();
    });

    resetButton.addEventListener('click', () => {
        if (!confirm('Kronometre ve kaydedilen turlar sıfırlansın mı?')) {
            return;
        }

        state = { elapsed: 0, startedAt: null, running: false, laps: [] };
        saveState();
        render();
    });

    clearLapsButton.addEventListener('click', () => {
        state.laps = [];
        saveState();
        renderLaps();
        renderControls();
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            render();
        }
    });

    render();
})();
</script>
<?= $this->endSection() ?>
