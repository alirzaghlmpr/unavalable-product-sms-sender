(function () {
    'use strict';

    var i18n = (window.upsnAdmin && window.upsnAdmin.i18n) || {};

    // ── Requests page: bulk selection ─────────────────────────────────────
    function initRequests(form) {
        var checkAll = document.getElementById('upsn-check-all');
        var bar      = document.getElementById('upsn-bulkbar');
        var counter  = document.getElementById('upsn-bulk-count');
        var boxes    = form.querySelectorAll('input[name="request_ids[]"]');

        function refresh() {
            var checked = form.querySelectorAll('input[name="request_ids[]"]:checked').length;

            boxes.forEach(function (box) {
                var row = box.closest('tr');
                if (row) row.classList.toggle('is-selected', box.checked);
            });

            if (checkAll) {
                checkAll.checked       = checked > 0 && checked === boxes.length;
                checkAll.indeterminate = checked > 0 && checked < boxes.length;
            }

            bar.hidden = checked === 0;
            counter.textContent = (i18n.selected || '%d').replace('%d', checked);
        }

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                boxes.forEach(function (box) { box.checked = checkAll.checked; });
                refresh();
            });
        }

        boxes.forEach(function (box) { box.addEventListener('change', refresh); });
        refresh();
    }

    // ── Settings page ─────────────────────────────────────────────────────
    function initSettings(form) {
        var layout   = document.getElementById('upsn-layout');
        var preview  = document.getElementById('upsn-preview');
        var dirtyEl  = document.getElementById('upsn-dirty');
        var tabs     = document.querySelectorAll('.upsn-subtab');
        var panels   = document.querySelectorAll('.upsn-tabpanel');
        var dirty    = false;
        var saving   = false;
        var state    = 'form';

        function field(key) {
            return form.querySelector('[name="upsn_settings[' + key + ']"]');
        }

        function value(key) {
            var checked = form.querySelector('[name="upsn_settings[' + key + ']"]:checked');
            if (checked) return checked.value;
            var el = field(key);
            return el ? el.value : '';
        }

        function showField(key, show) {
            var el = form.querySelector('.upsn-field[data-field="' + key + '"]');
            if (el) el.hidden = !show;
        }

        // ── Tabs ──────────────────────────────────────────────────────────
        function activate(id) {
            var known = false;
            tabs.forEach(function (tab) {
                var on = tab.dataset.tab === id;
                known = known || on;
                tab.classList.toggle('is-active', on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
                if (on) layout.classList.toggle('has-preview', tab.dataset.preview === '1');
            });
            if (!known) return activate('look');

            panels.forEach(function (panel) { panel.hidden = panel.dataset.tab !== id; });

            try { sessionStorage.setItem('upsnSettingsTab', id); } catch (e) { /* storage blocked */ }
            if (history.replaceState) history.replaceState(null, '', '#' + id);
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () { activate(tab.dataset.tab); });
        });

        var initial = location.hash.replace('#', '');
        if (!initial) {
            try { initial = sessionStorage.getItem('upsnSettingsTab') || ''; } catch (e) { initial = ''; }
        }
        activate(initial || 'look');

        // ── Fields that only apply to some choices ────────────────────────
        var gatewayFields = {
            sms_api_key:     ['smsir', 'kavenegar'],
            sms_username:    ['farazsms', 'melipayamak'],
            sms_password:    ['farazsms', 'melipayamak'],
            sms_line_number: ['farazsms'],
            sms_param_name:  ['smsir', 'kavenegar', 'farazsms']
        };

        function applyConditions() {
            var mode = value('button_visibility');
            showField('button_categories', mode === 'categories');
            showField('button_products',   mode === 'products');

            var gateway = value('sms_gateway');
            Object.keys(gatewayFields).forEach(function (key) {
                showField(key, gatewayFields[key].indexOf(gateway) !== -1);
            });
        }

        // ── Live preview ──────────────────────────────────────────────────
        var cssVars = {
            button_bg:       ['--upsn-btn-bg'],
            button_color:    ['--upsn-btn-color'],
            button_radius:   ['--upsn-btn-radius', 'px'],
            overlay_opacity: ['--upsn-overlay-alpha', '', function (v) { return v / 100; }],
            modal_bg:        ['--upsn-modal-bg'],
            modal_radius:    ['--upsn-modal-radius', 'px'],
            input_border:    ['--upsn-input-border'],
            input_focus:     ['--upsn-input-focus'],
            submit_bg:       ['--upsn-submit-bg'],
            submit_color:    ['--upsn-submit-color'],
            submit_radius:   ['--upsn-submit-radius', 'px'],
            success_color:   ['--upsn-success-color'],
            error_color:     ['--upsn-error-color']
        };

        var texts = {
            button_label:    '.upsn-notify-btn .upsn-btn__label',
            modal_title:     '#upsn-modal-title',
            modal_subtitle:  '.upsn-modal__subtitle',
            phone_label:     '.upsn-label',
            submit_label:    '#upsn-submit-btn .upsn-btn__label',
            success_message: '.upsn-success-state__text'
        };

        function syncPreview() {
            if (!preview) return;

            Object.keys(cssVars).forEach(function (key) {
                var def = cssVars[key];
                var raw = value(key);
                var out = def[2] ? def[2](raw) : raw + (def[1] || '');
                preview.style.setProperty(def[0], out);
            });

            var dir = value('modal_text_dir');
            preview.style.setProperty('--upsn-text-dir', dir);
            preview.querySelector('.upsn-modal').setAttribute('dir', dir);

            Object.keys(texts).forEach(function (key) {
                var el = preview.querySelector(texts[key]);
                if (el) el.textContent = value(key);
            });

            var note = preview.querySelector('.upsn-note');
            if (note) {
                var noteText = value('privacy_note');
                note.hidden = noteText === '';
                note.querySelector('span').textContent = noteText;
            }

            applyState();
        }

        // Preview states: what the popup looks like for each thing that can happen
        function applyState() {
            if (!preview) return;

            var box     = preview.querySelector('.upsn-modal__box');
            var input   = preview.querySelector('#upsn-phone');
            var wrap    = preview.querySelector('.upsn-input');
            var error   = preview.querySelector('#upsn-phone-error');
            var message = preview.querySelector('#upsn-form-message');

            box.classList.toggle('is-success', state === 'success');
            input.classList.toggle('is-invalid', state === 'invalid');
            input.value = state === 'invalid' ? '0912' : (state === 'error' ? '09123456789' : '');
            wrap.classList.toggle('is-valid', state === 'error');
            error.textContent = state === 'invalid' ? value('invalid_phone_error') : '';
            message.textContent = state === 'error' ? value('already_registered_error') : '';
            message.className   = 'upsn-form-message' + (state === 'error' ? ' is-error' : '');

            preview.querySelectorAll('.upsn-preview__states button').forEach(function (btn) {
                btn.classList.toggle('is-active', btn.dataset.state === state);
            });
        }

        if (preview) {
            preview.querySelectorAll('.upsn-preview__states button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    state = btn.dataset.state;
                    applyState();
                });
            });

            // The preview is for looking, not submitting
            preview.addEventListener('submit', function (e) { e.preventDefault(); });
            preview.querySelectorAll('button:not(.upsn-preview__states button)').forEach(function (btn) {
                btn.addEventListener('click', function (e) { e.preventDefault(); });
            });
        }

        // ── Input read-outs (colour hex, slider value) ────────────────────
        function updateReadouts(target) {
            if (target.type === 'color') {
                var code = target.parentNode.querySelector('code');
                if (code) code.textContent = target.value;
            } else if (target.type === 'range') {
                var out = target.parentNode.querySelector('output');
                if (out) out.textContent = target.value + (out.dataset.unit || '');
            }
        }

        function onEdit(e) {
            updateReadouts(e.target);
            applyConditions();
            syncPreview();

            if (e.type === 'change' || e.type === 'input') {
                dirty = true;
                if (dirtyEl) dirtyEl.hidden = false;
            }
        }

        form.addEventListener('input', onEdit);
        form.addEventListener('change', onEdit);

        // Product / category pickers are select2 widgets and only fire jQuery events
        if (window.jQuery) {
            window.jQuery(form).on('change', 'select.wc-enhanced-select, select.wc-product-search', function () {
                dirty = true;
                if (dirtyEl) dirtyEl.hidden = false;
            });
        }

        form.addEventListener('submit', function () { saving = true; });
        window.addEventListener('beforeunload', function (e) {
            if (dirty && !saving) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        applyConditions();
        syncPreview();

        // Drop ?settings-updated so a reload doesn't replay the "saved" toast
        if (history.replaceState && /[?&]settings-updated=/.test(location.search)) {
            var clean = location.search.replace(/([?&])settings-updated=[^&]*&?/, '$1').replace(/[?&]$/, '');
            history.replaceState(null, '', location.pathname + clean + location.hash);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var requests = document.getElementById('upsn-bulk-form');
        if (requests) initRequests(requests);

        var settings = document.getElementById('upsn-settings-form');
        if (settings) initSettings(settings);
    });
})();
