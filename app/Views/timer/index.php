<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    .time-shell { max-width: 780px; margin: 0 auto; text-align: center; }
    .time-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-width: 430px; margin: 0 auto 28px; padding: 5px; border-radius: 12px; background: #e5e7eb; }
    .time-tab { padding: 11px 18px; border: 0; border-radius: 9px; background: transparent; color: #4b5563; font: inherit; font-weight: 700; cursor: pointer; }
    .time-tab.active { background: #fff; color: #111827; box-shadow: 0 2px 8px rgba(17, 24, 39, .1); }
    .time-panel[hidden] { display: none; }
    .time-description { margin: 0 0 28px; color: #6b7280; }
    .time-display { padding: 36px 14px; border: 1px solid #e5e7eb; border-radius: 18px; background: #f9fafb; font-family: "Courier New", monospace; font-size: clamp(38px, 9vw, 78px); font-weight: 700; font-variant-numeric: tabular-nums; letter-spacing: -4px; color: #111827; }
    .time-display.running { border-color: #86efac; background: #f0fdf4; color: #166534; }
    .time-display.finished { border-color: #fca5a5; background: #fef2f2; color: #b91c1c; }
    .time-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin: 24px 0 32px; }
    .time-action { min-width: 120px; padding: 12px 18px; border: 0; border-radius: 9px; font: inherit; font-weight: 700; cursor: pointer; }
    .time-action:disabled { cursor: not-allowed; opacity: .45; }
    .start { background: #16a34a; color: #fff; }
    .pause { background: #f59e0b; color: #111827; }
    .lap { background: #2563eb; color: #fff; }
    .reset { background: #e5e7eb; color: #111827; }
    .laps { margin-top: 28px; text-align: left; }
    .laps-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
    .laps-header h2 { margin: 0; }
    .clear-laps { border: 0; background: transparent; color: #dc2626; cursor: pointer; }
    .lap-list { padding: 0; margin: 0; list-style: none; border-top: 1px solid #e5e7eb; }
    .lap-list li { display: flex; justify-content: space-between; padding: 12px 4px; border-bottom: 1px solid #e5e7eb; font-variant-numeric: tabular-nums; }
    .empty-laps { padding: 18px 0; color: #6b7280; }
    .countdown-inputs { display: grid; grid-template-columns: repeat(3, minmax(0, 130px)); justify-content: center; gap: 12px; margin-bottom: 22px; }
    .countdown-inputs label { margin: 0; color: #4b5563; font-size: 13px; }
    .countdown-inputs input { width: 100%; margin-top: 6px; padding: 12px 8px; border: 1px solid #d1d5db; border-radius: 9px; text-align: center; font: inherit; font-size: 20px; font-weight: 700; }
    .countdown-inputs input:disabled { background: #f3f4f6; color: #6b7280; }
    .timer-message { min-height: 24px; margin: -12px 0 12px; color: #b91c1c; font-weight: 700; }
    @media (max-width: 520px) { .countdown-inputs { gap: 7px; } .time-action { min-width: 105px; } }
</style>

<section class="time-shell" aria-labelledby="time-title">
    <h1 id="time-title">Zaman Araçları</h1>

    <div class="time-tabs" role="tablist" aria-label="Zaman aracı seçimi">
        <button id="stopwatch-tab" class="time-tab active" type="button" role="tab" aria-selected="true" aria-controls="stopwatch-panel">Kronometre</button>
        <button id="countdown-tab" class="time-tab" type="button" role="tab" aria-selected="false" aria-controls="countdown-panel">Zamanlayıcı</button>
    </div>

    <div id="stopwatch-panel" class="time-panel" role="tabpanel" aria-labelledby="stopwatch-tab">
        <p class="time-description">Çalışma sürenizi takip edin ve istediğiniz an tur kaydedin.</p>
        <div id="stopwatch-display" class="time-display" role="timer">00:00:00.00</div>
        <div class="time-actions">
            <button id="stopwatch-start" class="time-action start" type="button">Başlat</button>
            <button id="stopwatch-pause" class="time-action pause" type="button" disabled>Duraklat</button>
            <button id="stopwatch-lap" class="time-action lap" type="button" disabled>Tur Kaydet</button>
            <button id="stopwatch-reset" class="time-action reset" type="button" disabled>Sıfırla</button>
        </div>
        <div class="laps">
            <div class="laps-header">
                <h2>Turlar</h2>
                <button id="clear-laps" class="clear-laps" type="button" hidden>Turları Temizle</button>
            </div>
            <p id="empty-laps" class="empty-laps">Henüz tur kaydedilmedi.</p>
            <ol id="lap-list" class="lap-list"></ol>
        </div>
    </div>

    <div id="countdown-panel" class="time-panel" role="tabpanel" aria-labelledby="countdown-tab" hidden>
        <p class="time-description">Süreyi belirleyin; zaman dolduğunda sesli ve görsel bildirim alın.</p>
        <div class="countdown-inputs">
            <label>Saat<input id="countdown-hours" type="number" min="0" max="99" value="0" inputmode="numeric"></label>
            <label>Dakika<input id="countdown-minutes" type="number" min="0" max="59" value="5" inputmode="numeric"></label>
            <label>Saniye<input id="countdown-seconds" type="number" min="0" max="59" value="0" inputmode="numeric"></label>
        </div>
        <div id="countdown-display" class="time-display" role="timer" aria-live="polite">00:05:00</div>
        <div class="time-actions">
            <button id="countdown-start" class="time-action start" type="button">Başlat</button>
            <button id="countdown-pause" class="time-action pause" type="button" disabled>Duraklat</button>
            <button id="countdown-reset" class="time-action reset" type="button">Sıfırla</button>
        </div>
        <div id="countdown-message" class="timer-message" role="status"></div>
    </div>
</section>

<script>
(() => {
    'use strict';

    const userKey = '<?= (int) session()->get('user_id') ?>';
    const stopwatchKey = `project-redemption-timer-${userKey}`;
    const countdownKey = `project-redemption-countdown-${userKey}`;
    const activeTabKey = `project-redemption-time-tab-${userKey}`;

    const tabs = {
        stopwatch: document.getElementById('stopwatch-tab'),
        countdown: document.getElementById('countdown-tab'),
    };
    const panels = {
        stopwatch: document.getElementById('stopwatch-panel'),
        countdown: document.getElementById('countdown-panel'),
    };

    function selectTab(name) {
        Object.keys(tabs).forEach(key => {
            const active = key === name;
            tabs[key].classList.toggle('active', active);
            tabs[key].setAttribute('aria-selected', String(active));
            panels[key].hidden = !active;
        });
        localStorage.setItem(activeTabKey, name);
    }

    tabs.stopwatch.addEventListener('click', () => selectTab('stopwatch'));
    tabs.countdown.addEventListener('click', () => selectTab('countdown'));

    // Kronometre
    const swDisplay = document.getElementById('stopwatch-display');
    const swStart = document.getElementById('stopwatch-start');
    const swPause = document.getElementById('stopwatch-pause');
    const swLap = document.getElementById('stopwatch-lap');
    const swReset = document.getElementById('stopwatch-reset');
    const clearLaps = document.getElementById('clear-laps');
    const lapList = document.getElementById('lap-list');
    const emptyLaps = document.getElementById('empty-laps');
    let swFrame = null;

    function loadStopwatch() {
        const fallback = { elapsed: 0, startedAt: null, running: false, laps: [] };
        try {
            const saved = JSON.parse(localStorage.getItem(stopwatchKey));
            if (!saved || typeof saved.elapsed !== 'number' || !Array.isArray(saved.laps)) return fallback;
            return { elapsed: Math.max(0, saved.elapsed), startedAt: Number.isFinite(saved.startedAt) ? saved.startedAt : null, running: saved.running === true && Number.isFinite(saved.startedAt), laps: saved.laps.filter(Number.isFinite) };
        } catch (error) { return fallback; }
    }

    let swState = loadStopwatch();
    const saveStopwatch = () => localStorage.setItem(stopwatchKey, JSON.stringify(swState));
    const stopwatchNow = () => swState.elapsed + (swState.running && swState.startedAt ? Date.now() - swState.startedAt : 0);

    function formatStopwatch(milliseconds) {
        const cs = Math.floor(milliseconds / 10);
        const hours = Math.floor(cs / 360000);
        const minutes = Math.floor((cs % 360000) / 6000);
        const seconds = Math.floor((cs % 6000) / 100);
        return [hours, minutes, seconds].map(value => String(value).padStart(2, '0')).join(':') + '.' + String(cs % 100).padStart(2, '0');
    }

    function renderStopwatchClock() {
        swDisplay.textContent = formatStopwatch(stopwatchNow());
        if (swState.running && !document.hidden) swFrame = requestAnimationFrame(renderStopwatchClock);
    }

    function renderStopwatch() {
        cancelAnimationFrame(swFrame);
        renderStopwatchClock();
        const hasTime = stopwatchNow() > 0;
        swStart.textContent = hasTime && !swState.running ? 'Devam Et' : 'Başlat';
        swStart.disabled = swState.running;
        swPause.disabled = !swState.running;
        swLap.disabled = !swState.running;
        swReset.disabled = !hasTime && swState.laps.length === 0;
        swDisplay.classList.toggle('running', swState.running);
        lapList.innerHTML = '';
        swState.laps.slice().reverse().forEach((lapTime, index) => {
            const item = document.createElement('li');
            const label = document.createElement('span');
            const value = document.createElement('strong');
            label.textContent = `Tur ${swState.laps.length - index}`;
            value.textContent = formatStopwatch(lapTime);
            item.append(label, value);
            lapList.appendChild(item);
        });
        emptyLaps.hidden = swState.laps.length > 0;
        clearLaps.hidden = swState.laps.length === 0;
    }

    swStart.addEventListener('click', () => { swState.startedAt = Date.now(); swState.running = true; saveStopwatch(); renderStopwatch(); });
    swPause.addEventListener('click', () => { swState.elapsed = stopwatchNow(); swState.startedAt = null; swState.running = false; saveStopwatch(); renderStopwatch(); });
    swLap.addEventListener('click', () => { swState.laps.push(stopwatchNow()); saveStopwatch(); renderStopwatch(); });
    swReset.addEventListener('click', () => { if (confirm('Kronometre ve turlar sıfırlansın mı?')) { swState = { elapsed: 0, startedAt: null, running: false, laps: [] }; saveStopwatch(); renderStopwatch(); } });
    clearLaps.addEventListener('click', () => { swState.laps = []; saveStopwatch(); renderStopwatch(); });

    // Geri sayım zamanlayıcısı
    const cdDisplay = document.getElementById('countdown-display');
    const cdHours = document.getElementById('countdown-hours');
    const cdMinutes = document.getElementById('countdown-minutes');
    const cdSeconds = document.getElementById('countdown-seconds');
    const cdStart = document.getElementById('countdown-start');
    const cdPause = document.getElementById('countdown-pause');
    const cdReset = document.getElementById('countdown-reset');
    const cdMessage = document.getElementById('countdown-message');
    let cdInterval = null;

    function loadCountdown() {
        const fallback = { duration: 300000, remaining: 300000, endAt: null, running: false, finished: false };
        try {
            const saved = JSON.parse(localStorage.getItem(countdownKey));
            if (!saved || typeof saved.duration !== 'number' || typeof saved.remaining !== 'number') return fallback;
            return { duration: Math.max(0, saved.duration), remaining: Math.max(0, saved.remaining), endAt: Number.isFinite(saved.endAt) ? saved.endAt : null, running: saved.running === true && Number.isFinite(saved.endAt), finished: saved.finished === true };
        } catch (error) { return fallback; }
    }

    let cdState = loadCountdown();
    const saveCountdown = () => localStorage.setItem(countdownKey, JSON.stringify(cdState));
    const countdownNow = () => cdState.running && cdState.endAt ? Math.max(0, cdState.endAt - Date.now()) : cdState.remaining;

    function formatCountdown(milliseconds) {
        const total = Math.ceil(Math.max(0, milliseconds) / 1000);
        const hours = Math.floor(total / 3600);
        const minutes = Math.floor((total % 3600) / 60);
        const seconds = total % 60;
        return [hours, minutes, seconds].map(value => String(value).padStart(2, '0')).join(':');
    }

    function durationFromInputs() {
        const hours = Math.min(99, Math.max(0, Number(cdHours.value) || 0));
        const minutes = Math.min(59, Math.max(0, Number(cdMinutes.value) || 0));
        const seconds = Math.min(59, Math.max(0, Number(cdSeconds.value) || 0));
        return (hours * 3600 + minutes * 60 + seconds) * 1000;
    }

    function updateInputs(milliseconds) {
        const total = Math.floor(milliseconds / 1000);
        cdHours.value = Math.floor(total / 3600);
        cdMinutes.value = Math.floor((total % 3600) / 60);
        cdSeconds.value = total % 60;
    }

    function playAlarm() {
        try {
            const audio = new (window.AudioContext || window.webkitAudioContext)();
            [0, .3, .6].forEach(delay => {
                const oscillator = audio.createOscillator();
                const gain = audio.createGain();
                oscillator.frequency.value = 880;
                gain.gain.setValueAtTime(.18, audio.currentTime + delay);
                gain.gain.exponentialRampToValueAtTime(.001, audio.currentTime + delay + .2);
                oscillator.connect(gain).connect(audio.destination);
                oscillator.start(audio.currentTime + delay);
                oscillator.stop(audio.currentTime + delay + .22);
            });
        } catch (error) { /* Ses desteği yoksa görsel bildirim devam eder. */ }
    }

    function finishCountdown() {
        clearInterval(cdInterval);
        cdState = { ...cdState, remaining: 0, endAt: null, running: false, finished: true };
        saveCountdown();
        playAlarm();
        renderCountdown();
    }

    function tickCountdown() {
        const remaining = countdownNow();
        cdDisplay.textContent = formatCountdown(remaining);
        if (cdState.running && remaining <= 0) finishCountdown();
    }

    function renderCountdown() {
        clearInterval(cdInterval);
        const remaining = countdownNow();
        cdDisplay.textContent = formatCountdown(remaining);
        cdDisplay.classList.toggle('running', cdState.running);
        cdDisplay.classList.toggle('finished', cdState.finished);
        cdMessage.textContent = cdState.finished ? 'Süre doldu!' : '';
        cdStart.textContent = remaining > 0 && remaining < cdState.duration && !cdState.running ? 'Devam Et' : 'Başlat';
        cdStart.disabled = cdState.running;
        cdPause.disabled = !cdState.running;
        [cdHours, cdMinutes, cdSeconds].forEach(input => { input.disabled = cdState.running || (remaining < cdState.duration && remaining > 0); });
        if (cdState.running && !document.hidden) cdInterval = setInterval(tickCountdown, 1000);
    }

    [cdHours, cdMinutes, cdSeconds].forEach(input => input.addEventListener('input', () => {
        cdState.duration = durationFromInputs();
        cdState.remaining = cdState.duration;
        cdState.finished = false;
        saveCountdown();
        renderCountdown();
    }));

    cdStart.addEventListener('click', () => {
        if (cdState.finished || cdState.remaining <= 0 || cdState.remaining === cdState.duration) {
            cdState.duration = durationFromInputs();
            cdState.remaining = cdState.duration;
        }
        if (cdState.remaining <= 0) { cdMessage.textContent = 'Lütfen sıfırdan büyük bir süre girin.'; return; }
        cdState.endAt = Date.now() + cdState.remaining;
        cdState.running = true;
        cdState.finished = false;
        saveCountdown();
        renderCountdown();
    });

    cdPause.addEventListener('click', () => {
        cdState.remaining = countdownNow();
        cdState.endAt = null;
        cdState.running = false;
        saveCountdown();
        renderCountdown();
    });

    cdReset.addEventListener('click', () => {
        const duration = cdState.duration || 300000;
        cdState = { duration, remaining: duration, endAt: null, running: false, finished: false };
        updateInputs(duration);
        saveCountdown();
        renderCountdown();
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            cancelAnimationFrame(swFrame);
            clearInterval(cdInterval);
        } else {
            renderStopwatch();
            if (cdState.running && countdownNow() <= 0) finishCountdown(); else renderCountdown();
        }
    });

    updateInputs(cdState.duration);
    selectTab(localStorage.getItem(activeTabKey) === 'countdown' ? 'countdown' : 'stopwatch');
    renderStopwatch();
    if (cdState.running && countdownNow() <= 0) finishCountdown(); else renderCountdown();
})();
</script>
<?= $this->endSection() ?>
