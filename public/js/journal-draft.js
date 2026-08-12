(() => {
    const form = document.querySelector('[data-journal-draft]');
    if (!form) return;

    const storageKey = form.dataset.draftKey;
    const status = form.querySelector('[data-draft-status]');
    const clearButton = form.querySelector('[data-clear-draft]');
    const fields = ['entry_date', 'mood', 'title', 'content'];
    let saveTimer;

    const setStatus = message => {
        if (status) status.textContent = message;
    };

    const readDraft = () => {
        try {
            const value = localStorage.getItem(storageKey);
            return value ? JSON.parse(value) : null;
        } catch (error) {
            setStatus('Taslak depolama bu tarayıcıda kullanılamıyor.');
            return null;
        }
    };

    const collectDraft = () => {
        const values = {};
        fields.forEach(name => {
            const field = form.elements.namedItem(name);
            if (field) values[name] = field.value;
        });
        return { values, savedAt: new Date().toISOString() };
    };

    const saveDraft = () => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(collectDraft()));
            const time = new Intl.DateTimeFormat('tr-TR', { hour: '2-digit', minute: '2-digit' }).format(new Date());
            setStatus(`Taslak kaydedildi · ${time}`);
            if (clearButton) clearButton.hidden = false;
        } catch (error) {
            setStatus('Taslak kaydedilemedi. Tarayıcı depolama alanını kontrol edin.');
        }
    };

    const draft = readDraft();
    if (draft?.values) {
        fields.forEach(name => {
            const field = form.elements.namedItem(name);
            if (field && typeof draft.values[name] === 'string') field.value = draft.values[name];
        });
        setStatus('Kaydedilmemiş taslağınız geri yüklendi.');
        if (clearButton) clearButton.hidden = false;
    }

    form.addEventListener('input', () => {
        setStatus('Taslak kaydediliyor…');
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveDraft, 400);
    });
    form.addEventListener('change', () => {
        clearTimeout(saveTimer);
        saveDraft();
    });

    clearButton?.addEventListener('click', () => {
        if (!confirm('Kaydedilmemiş taslak silinsin mi?')) return;
        try { localStorage.removeItem(storageKey); } catch (error) { return; }
        window.location.reload();
    });

    window.addEventListener('pagehide', () => {
        clearTimeout(saveTimer);
        saveDraft();
    });
})();
