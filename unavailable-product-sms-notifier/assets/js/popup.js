(function ($) {
    'use strict';

    // ── Phone validation (Iranian mobile) ─────────────────────────────────
    // Accepts: 09xxxxxxxxx | 9xxxxxxxxx | +989xxxxxxxxx | 00989xxxxxxxxx
    // Phase 2: replace regex if SMS provider requires a different pattern.
    function isValidPhone(phone) {
        return /^(\+98|0098|0)?9[0-9]{9}$/.test(phone.trim());
    }

    var modal      = document.getElementById('upsn-modal');
    var openBtn    = document.getElementById('upsn-open-btn');
    var closeBtn   = modal ? modal.querySelector('.upsn-modal__close') : null;
    var backdrop   = modal ? modal.querySelector('.upsn-modal__backdrop') : null;
    var form       = document.getElementById('upsn-form');
    var phoneInput = document.getElementById('upsn-phone');
    var phoneError = document.getElementById('upsn-phone-error');
    var submitBtn  = document.getElementById('upsn-submit-btn');
    var msgDiv     = document.getElementById('upsn-form-message');

    if (!modal || !openBtn) {
        return;
    }

    // ── Open / close helpers ───────────────────────────────────────────────
    function openModal() {
        modal.removeAttribute('hidden');
        if (phoneInput) phoneInput.focus();
    }

    function closeModal() {
        modal.setAttribute('hidden', '');
        resetForm();
    }

    function resetForm() {
        if (form) form.reset();
        clearError();
        clearMessage();
    }

    function clearError() {
        if (phoneInput) phoneInput.classList.remove('is-invalid');
        if (phoneError) phoneError.textContent = '';
    }

    function showError(msg) {
        if (phoneInput) phoneInput.classList.add('is-invalid');
        if (phoneError) phoneError.textContent = msg;
    }

    function showMessage(msg, type) {
        if (!msgDiv) return;
        msgDiv.textContent = msg;
        msgDiv.className   = 'upsn-form-message is-' + type;
    }

    function clearMessage() {
        if (!msgDiv) return;
        msgDiv.textContent = '';
        msgDiv.className   = 'upsn-form-message';
    }

    // ── Event listeners ────────────────────────────────────────────────────
    openBtn.addEventListener('click', openModal);
    if (closeBtn)  closeBtn.addEventListener('click', closeModal);
    if (backdrop)  backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hasAttribute('hidden')) {
            closeModal();
        }
    });

    // Live validation on blur
    if (phoneInput) {
        phoneInput.addEventListener('blur', function () {
            if (this.value && !isValidPhone(this.value)) {
                showError(upsnData.i18n.invalidPhone);
            } else {
                clearError();
            }
        });

        phoneInput.addEventListener('input', function () {
            if (!this.classList.contains('is-invalid')) return;
            if (isValidPhone(this.value)) clearError();
        });
    }

    // ── Form submit ────────────────────────────────────────────────────────
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearMessage();

            var phone = phoneInput ? phoneInput.value.trim() : '';

            if (!isValidPhone(phone)) {
                showError(upsnData.i18n.invalidPhone);
                if (phoneInput) phoneInput.focus();
                return;
            }

            clearError();
            submitBtn.disabled    = true;
            submitBtn.textContent = upsnData.i18n.sending;

            $.ajax({
                url:  upsnData.ajaxUrl,
                type: 'POST',
                data: {
                    action:     'upsn_save_request',
                    nonce:      upsnData.nonce,
                    product_id: upsnData.productId,
                    phone:      phone
                },
                success: function (response) {
                    if (response.success) {
                        showMessage(upsnData.i18n.success, 'success');
                        if (form) form.reset();
                        submitBtn.disabled = true; // keep disabled after success
                        return;
                    }

                    var code = response.data && response.data.code;
                    if (code === 'already_registered') {
                        showMessage(upsnData.i18n.alreadyDone, 'error');
                        submitBtn.disabled = false;
                    } else {
                        showMessage(upsnData.i18n.error, 'error');
                        submitBtn.disabled = false;
                    }
                },
                error: function () {
                    showMessage(upsnData.i18n.error, 'error');
                    submitBtn.disabled = false;
                },
                complete: function () {
                    if (!submitBtn.disabled) {
                        submitBtn.textContent = upsnData.i18n.notifyMe || 'Notify Me';
                    }
                }
            });
        });
    }

})(jQuery);
