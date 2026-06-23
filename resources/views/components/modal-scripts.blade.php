{{-- resources/views/components/modal-scripts.blade.php --}}
{{-- Include once in your layouts/dashboard.blade.php before </body> --}}

<script>
/**
 * ModalAlert — global helper to open/close modal-alert & modal-loading components.
 *
 * Usage:
 *   ModalAlert.show('errorModal', { title: 'Gagal!', subtitle: 'Detail pesan.' });
 *   ModalAlert.success('Buku berhasil disimpan!');
 *   ModalAlert.error('Terjadi kesalahan saat menyimpan anotasi!');
 *   ModalAlert.loading('loadingModal');
 *   ModalAlert.close('loadingModal');
 *   ModalAlert.confirm('confirmModal', { title: '...', subtitle: '...' }, callbackFn);
 */
const ModalAlert = (() => {
    const DISMISS_MS = 3000;

    function _el(id) { return document.getElementById(id); }

    function _animate(modal, show) {
        const backdrop = modal.querySelector('.modal-backdrop');
        const card     = modal.querySelector('.modal-card');
        if (!backdrop || !card) {
            // loading modal — simpler structure
            modal.classList.toggle('hidden', !show);
            modal.classList.toggle('flex',    show);
            return;
        }

        if (show) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                card.classList.remove('scale-90', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            });
        } else {
            backdrop.classList.add('opacity-0');
            card.classList.add('scale-90', 'opacity-0');
            card.classList.remove('scale-100', 'opacity-100');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }
    }

    function show(id, opts = {}) {
        const modal = _el(id);
        if (!modal) return;

        if (opts.title)    { const t = _el(id + '-title');    if (t) t.textContent = opts.title; }
        if (opts.subtitle) { const s = _el(id + '-subtitle'); if (s) { s.textContent = opts.subtitle; s.classList.remove('hidden'); } }

        _animate(modal, true);

        const autoDismiss = modal.dataset.autoDismiss === 'true';
        if (autoDismiss) {
            const bar = _el(id + '-progress');
            if (bar) {
                bar.style.transition = 'none';
                bar.style.width      = '100%';
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        bar.style.transition = `width ${DISMISS_MS}ms linear`;
                        bar.style.width      = '0%';
                    });
                });
            }
            setTimeout(() => close(id), DISMISS_MS);
        }
    }

    function close(id) {
        const modal = _el(id);
        if (modal) _animate(modal, false);
    }

    function loading(id) {
        const modal = _el(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    /**
     * confirm(id, opts, onConfirm)
     * opts: { title, subtitle }
     * onConfirm: function called when user presses the confirm button
     */
    function confirm(id, opts = {}, onConfirm) {
        show(id, opts);
        const btn = _el(id + '-confirm-btn');
        if (btn) {
            // Clone to remove previous listeners
            const fresh = btn.cloneNode(true);
            btn.parentNode.replaceChild(fresh, btn);
            fresh.addEventListener('click', () => {
                close(id);
                if (typeof onConfirm === 'function') onConfirm();
            });
        }
    }

    // Convenience shortcuts — requires modals with ids: globalSuccessModal, globalErrorModal
    function success(title, subtitle = '') { show('globalSuccessModal', { title, subtitle }); }
    function error(title, subtitle = '')   { show('globalErrorModal',   { title, subtitle }); }

    return { show, close, loading, confirm, success, error };
})();

// Auto-open modals that were pre-rendered with data-open="true" (e.g. from session flash)
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-modal-auto]').forEach(modal => {
        ModalAlert.show(modal.id);
    });
});
</script>