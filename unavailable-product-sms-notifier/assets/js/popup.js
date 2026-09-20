(function ($) {
    'use strict';

    // ── Phone helpers (Iranian mobile) ────────────────────────────────────
    // Accepts: 09xxxxxxxxx | 9xxxxxxxxx | +989xxxxxxxxx | 00989xxxxxxxxx
    // Phase 2: replace regex if SMS provider requires a different pattern.
    function isValidPhone(phone) {
        return /^(\+98|0098|0)?9[0-9]{9}$/.test(phone.trim());
    }

    // Shoppers on a Persian keyboard type ۰-۹ (or ٠-٩); turn them into 0-9 and
    // drop anything that can't be part of a number.
    function normalizePhone(value) {
        return value
            .replace(/[۰-۹]/g, function (d) { return String(d.charCodeAt(0) - 0x06F0); })
            .replace(/[٠-٩]/g, function (d) { return String(d.charCodeAt(0) - 0x0660); })
            .replace(/[^\d+]/g, '');
    }

    var modal      = document.getElementById('upsn-modal');
    var openBtn    = document.getElementById('upsn-open-btn');

    if (!modal || !openBtn) {
        return;
    }

    var box         = modal.querySelector('.upsn-modal__box');
    var closeBtn    = modal.querySelector('.upsn-modal__close');
    var backdrop    = modal.querySelector('.upsn-modal__backdrop');
    var doneBtn     = modal.querySelector('.upsn-success-state__done');
    var successText = modal.querySelector('.upsn-success-state__text');
    var form        = document.getElementById('upsn-form');
    var phoneInput  = document.getElementById('upsn-phone');
    var inputWrap   = phoneInput ? phoneInput.closest('.upsn-input') : null;
    var phoneError  = document.getElementById('upsn-phone-error');
    var submitBtn   = document.getElementById('upsn-submit-btn');
    var submitLabel = submitBtn ? submitBtn.querySelector('.upsn-btn__label') : null;
    var msgDiv      = document.getElementById('upsn-form-message');
    var lastFocus   = null;

    var originalSubmitLabel = submitLabel ? submitLabel.textContent : '';

    // Theme wrappers with transforms or their own stacking context can trap a
    // position:fixed popup; the body is always safe.
    document.body.appendChild(modal);

    // ── Open / close ───────────────────────────────────────────────────────
    function openModal() {
        lastFocus = document.activeElement;
        modal.removeAttribute('hidden');
        document.documentElement.classList.add('upsn-modal-open');
        if (phoneInput) phoneInput.focus();
    }

    function closeModal() {
        modal.setAttribute('hidden', '');
        document.documentElement.classList.remove('upsn-modal-open');
        resetForm();
        if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
    }

    function resetForm() {
        box.classList.remove('is-success');
        if (form) form.reset();
        clearError();
        clearMessage();
        setLoading(false);
        if (inputWrap) inputWrap.classList.remove('is-valid');
    }

    // Keep Tab / Shift+Tab inside the popup while it is open
    function trapFocus(e) {
        var nodes = Array.prototype.filter.call(
            modal.querySelectorAll('button, input, a[href], [tabindex]:not([tabindex="-1"])'),
            function (el) { return !el.disabled && el.offsetParent !== null; }
        );
        if (!nodes.length) return;

        var first = nodes[0];
        var last  = nodes[nodes.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    // ── UI state ───────────────────────────────────────────────────────────
    function setLoading(loading) {
        if (!submitBtn) return;
        submitBtn.disabled = loading;
        submitBtn.classList.toggle('is-loading', loading);
        if (submitLabel) {
            submitLabel.textContent = loading ? upsnData.i18n.sending : originalSubmitLabel;
        }
    }

    function clearError() {
        if (phoneInput) {
            phoneInput.classList.remove('is-invalid');
            phoneInput.removeAttribute('aria-invalid');
        }
        if (phoneError) phoneError.textContent = '';
    }

    function showError(msg) {
        if (phoneInput) {
            // Restart the shake when the shopper submits a bad number twice in a row
            phoneInput.classList.remove('is-invalid');
            void phoneInput.offsetWidth;
            phoneInput.classList.add('is-invalid');
            phoneInput.setAttribute('aria-invalid', 'true');
        }
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

    function messageFor(code) {
        if (code === 'already_registered') return upsnData.i18n.alreadyDone;
        if (code === 'ip_limit')           return upsnData.i18n.ipLimit;
        if (code === 'phone_limit')        return upsnData.i18n.phoneLimit;
        return upsnData.i18n.error;
    }

    function showSuccess() {
        box.classList.add('is-success');
        if (successText) {
            successText.setAttribute('tabindex', '-1');
            successText.focus();
        }
    }

    // ── Event listeners ────────────────────────────────────────────────────
    openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (doneBtn)  doneBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (e) {
        if (modal.hasAttribute('hidden')) return;

        if (e.key === 'Escape') {
            closeModal();
        } else if (e.key === 'Tab') {
            trapFocus(e);
        }
    });

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            var raw  = this.value;
            var norm = normalizePhone(raw);

            if (norm !== raw) {
                var caret = normalizePhone(raw.slice(0, this.selectionStart)).length;
                this.value = norm;
                try { this.setSelectionRange(caret, caret); } catch (err) { /* type=tel in some browsers */ }
            }

            var valid = isValidPhone(norm);
            if (inputWrap) inputWrap.classList.toggle('is-valid', valid);
            if (valid && this.classList.contains('is-invalid')) clearError();
        });

        // Validate when the shopper leaves the field, but only if they typed something
        phoneInput.addEventListener('blur', function () {
            if (this.value && !isValidPhone(this.value)) {
                showError(upsnData.i18n.invalidPhone);
            } else {
                clearError();
            }
        });
    }

    // ── Form submit ────────────────────────────────────────────────────────
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearMessage();

            var phone = phoneInput ? normalizePhone(phoneInput.value) : '';

            if (!isValidPhone(phone)) {
                showError(upsnData.i18n.invalidPhone);
                if (phoneInput) phoneInput.focus();
                return;
            }

            clearError();
            setLoading(true);

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
                    setLoading(false);

                    if (response.success) {
                        showSuccess();
                        return;
                    }

                    showMessage(messageFor(response.data && response.data.code), 'error');
                },
                // The server answers rejected requests with 4xx/5xx (409 already
                // registered, 429 rate limited, ...), so the reason arrives here.
                error: function (xhr) {
                    setLoading(false);

                    var body = xhr.responseJSON;
                    showMessage(messageFor(body && body.data && body.data.code), 'error');
                }
            });
        });
    }

})(jQuery);
