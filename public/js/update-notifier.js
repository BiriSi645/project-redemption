(() => {
    'use strict';

    const script = document.currentScript;
    const notice = document.getElementById('update-notice');
    const reloadButton = document.getElementById('apply-update');
    if (!script || !notice || !reloadButton) return;

    const initialVersion = script.dataset.currentVersion;
    let updateFound = false;

    async function checkVersion() {
        if (updateFound || document.visibilityState !== 'visible') return;
        try {
            const response = await fetch(script.dataset.versionUrl, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) return;
            const data = await response.json();
            if (data.version && data.version !== initialVersion) {
                updateFound = true;
                notice.classList.add('visible');
            }
        } catch (error) {
            // Geçici bağlantı sorunlarında bir sonraki kontrolde tekrar denenecek.
        }
    }

    reloadButton.addEventListener('click', () => window.location.reload());
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') checkVersion();
    });
    setInterval(checkVersion, 60000);
})();
