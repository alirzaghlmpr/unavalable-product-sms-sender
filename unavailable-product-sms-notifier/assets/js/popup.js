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
    var closeBtn   = modal ? modal.querySelector('.upsn-modal__close')  : null;
    var backdrop   = modal ? modal.querySelector('.upsn-modal__backdrop') : null;
    var form       = document.getElementById('upsn-form');
    var phoneInput = document.getElementById('upsn-phone');
    var phoneError = document.getElementById('upsn-phone-error');
    var submitBtn  = document.getElementById('upsn-submit-btn');
    var msgDiv     = document.getElementById('upsn-form-message');

    if (!modal || !openBtn) {
        return;
    }

    // Store original submit label so we can restore it after errors
    var originalSubmitLabel = submitBtn ? submitBtn.textContent : '';

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

        // Restore form fields visibility in case a previous success hid them
        if (form) {
            form.querySelectorAll('.upsn-success-state').forEach(function (el) {
                el.remove();
            });
            showFormFields();
        }

        if (submitBtn) {
            submitBtn.disabled    = false;
            submitBtn.textContent = originalSubmitLabel;
        }
    }

    function hideFormFields() {
        if (!form) return;
        form.querySelectorAll('label, #upsn-phone, #upsn-phone-error, #upsn-submit-btn, #upsn-form-message').forEach(function (el) {
            el.style.display = 'none';
        });
    }

    function showFormFields() {
        if (!form) return;
        form.querySelectorAll('label, #upsn-phone, #upsn-phone-error, #upsn-submit-btn, #upsn-form-message').forEach(function (el) {
            el.style.display = '';
        });
    }

    function showSuccessState() {
        hideFormFields();

        var successHtml =
            '<div class="upsn-success-state">' +
                '<div class="upsn-success-state__icon">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                        '<polyline points="20 6 9 17 4 12"></polyline>' +
                    '</svg>' +
                '</div>' +
                '<p class="upsn-success-state__text">' + escapeHtml(upsnData.i18n.success) + '</p>' +
            '</div>';

        form.insertAdjacentHTML('beforeend', successHtml);
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
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
                        showSuccessState();
                        return;
                    }

                    // Error — restore button and show message
                    submitBtn.disabled    = false;
                    submitBtn.textContent = originalSubmitLabel;

                    var code = response.data && response.data.code;
                    showMessage(
                        code === 'already_registered' ? upsnData.i18n.alreadyDone : upsnData.i18n.error,
                        'error'
                    );
                },
                error: function () {
                    submitBtn.disabled    = false;
                    submitBtn.textContent = originalSubmitLabel;
                    showMessage(upsnData.i18n.error, 'error');
                }
            });
        });
    }

})(jQuery);
