(() => {
    'use strict';

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const fields = document.querySelectorAll('[data-speech-input]');
    let activeRecognition = null;
    let activeButton = null;

    function getStatus(button) {
        return button.closest('.speech-input-wrap').nextElementSibling;
    }

    function setStatus(button, message, isError = false) {
        const status = getStatus(button);
        status.textContent = message;
        status.classList.toggle('error', isError);
    }

    function stopActiveRecognition() {
        if (activeRecognition) {
            activeRecognition.stop();
        }
    }

    function insertTranscript(field, transcript) {
        const start = typeof field.selectionStart === 'number' ? field.selectionStart : field.value.length;
        const end = typeof field.selectionEnd === 'number' ? field.selectionEnd : field.value.length;
        const before = field.value.slice(0, start);
        const after = field.value.slice(end);
        const leadingSpace = before && !/\s$/.test(before) ? ' ' : '';
        const trailingSpace = after && !/^\s/.test(after) ? ' ' : '';
        const text = leadingSpace + transcript.trim() + trailingSpace;

        field.setRangeText(text, start, end, 'end');
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.focus();
    }

    fields.forEach(field => {
        const wrapper = document.createElement('div');
        const button = document.createElement('button');
        const status = document.createElement('span');

        wrapper.className = field.tagName === 'TEXTAREA'
            ? 'speech-input-wrap speech-textarea-wrap'
            : 'speech-input-wrap speech-single-line-wrap';
        field.parentNode.insertBefore(wrapper, field);
        wrapper.appendChild(field);

        button.type = 'button';
        button.className = 'speech-button';
        button.innerHTML = '<span aria-hidden="true">🎙️</span>';
        button.setAttribute('aria-label', 'Sesli yazmayı başlat');
        button.title = 'Sesli yaz';
        wrapper.appendChild(button);

        status.className = 'speech-status';
        status.setAttribute('aria-live', 'polite');
        wrapper.insertAdjacentElement('afterend', status);

        if (!SpeechRecognition) {
            button.disabled = true;
            button.title = 'Tarayıcınız sesli yazmayı desteklemiyor';
            status.textContent = 'Sesli yazma bu tarayıcıda desteklenmiyor.';
            return;
        }

        button.addEventListener('click', () => {
            if (activeButton === button) {
                stopActiveRecognition();
                return;
            }

            if (activeRecognition) {
                stopActiveRecognition();
                window.setTimeout(() => button.click(), 250);
                return;
            }

            const recognition = new SpeechRecognition();
            recognition.lang = 'tr-TR';
            recognition.continuous = true;
            recognition.interimResults = false;

            recognition.onstart = () => {
                activeRecognition = recognition;
                activeButton = button;
                button.classList.add('listening');
                button.setAttribute('aria-label', 'Sesli yazmayı durdur');
                button.title = 'Dinlemeyi durdur';
                setStatus(button, 'Dinleniyor… Bitirmek için mikrofona tekrar basın.');
            };

            recognition.onresult = event => {
                for (let index = event.resultIndex; index < event.results.length; index += 1) {
                    if (event.results[index].isFinal) {
                        insertTranscript(field, event.results[index][0].transcript);
                    }
                }
            };

            recognition.onerror = event => {
                const messages = {
                    'not-allowed': 'Mikrofon izni verilmedi.',
                    'audio-capture': 'Kullanılabilir mikrofon bulunamadı.',
                    'network': 'Ses tanıma servisine bağlanılamadı.',
                    'no-speech': 'Konuşma algılanamadı.',
                };
                setStatus(button, messages[event.error] || 'Sesli yazma sırasında bir hata oluştu.', true);
            };

            recognition.onend = () => {
                button.classList.remove('listening');
                button.setAttribute('aria-label', 'Sesli yazmayı başlat');
                button.title = 'Sesli yaz';
                if (!getStatus(button).classList.contains('error')) {
                    setStatus(button, 'Sesli yazma durduruldu.');
                }
                if (activeRecognition === recognition) {
                    activeRecognition = null;
                    activeButton = null;
                }
            };

            try {
                recognition.start();
            } catch (error) {
                setStatus(button, 'Sesli yazma başlatılamadı.', true);
            }
        });
    });
})();
