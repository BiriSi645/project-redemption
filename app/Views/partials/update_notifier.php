<style>
    .update-notice {
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 10000;
        display: none;
        align-items: center;
        gap: 14px;
        max-width: min(520px, calc(100vw - 32px));
        padding: 14px 16px;
        border: 1px solid #93c5fd;
        border-radius: 12px;
        background: #eff6ff;
        color: #1e3a8a;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .2);
        box-sizing: border-box;
        overflow: visible;
    }

    .update-notice.visible {
        display: flex
    }

    .update-notice-text {
        flex: 1;
        min-width: 0;
        /* allow flex item to shrink */
    }

    .update-notice-text strong,
    .update-notice-text span {
        display: block
    }

    .update-notice-text span {
        margin-top: 3px;
        font-size: 12px;
        color: #475569
    }

    .update-notice button {
        flex: 0 0 auto;
        width: auto;
        max-width: 100%;
        padding: 9px 16px;
        border: 0;
        border-radius: 8px;
        background: #2563eb;
        color: #fff;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
        min-width: 120px;
        white-space: nowrap;
    }

    html[data-theme="dark"] .update-notice {
        border-color: #1d4ed8;
        background: #172554;
        color: #dbeafe
    }

    html[data-theme="dark"] .update-notice-text span {
        color: #bfdbfe
    }

    @media(max-width:520px) {
        .update-notice {
            right: 16px;
            bottom: 16px;
            left: 16px;
            flex-direction: column;
            align-items: stretch
        }

        .update-notice button {
            width: 100%
        }
    }
</style>
<aside class="update-notice" id="update-notice" role="status" aria-live="polite">
    <div class="update-notice-text"><strong>Yeni güncelleme geldi</strong><span>Yeni sürümü
            kullanmak için sayfayı yenileyin.</span></div>
    <button type="button" id="apply-update">Sayfayı yenile</button>
</aside>
<script src="<?= base_url("js/update-notifier.js") ?>" data-current-version="<?= esc(
    $codeVersion,
    "attr",
) ?>" data-version-url="<?= site_url("system/version") ?>"></script>