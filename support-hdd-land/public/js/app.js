/* Saramin Hard — public/js/app.js */
(function () {
    'use strict';

    /* =========================================================
     * Utilities
     * ========================================================= */

    var DIGIT_MAP = {
        '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
        '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
        '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
        '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
    };
    var PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    function convertDigits(value) {
        return String(value == null ? '' : value).replace(/[۰-۹٠-٩]/g, function (ch) {
            return DIGIT_MAP[ch] || ch;
        });
    }

    function toPersianDigits(value) {
        return String(value == null ? '' : value).replace(/[0-9]/g, function (d) {
            return PERSIAN_DIGITS[+d];
        });
    }

    function normalizePhoneDigits(value) {
        var digits = convertDigits(value).replace(/\D+/g, '');
        if (digits.indexOf('98') === 0 && digits.length >= 12) {
            digits = '0' + digits.slice(2);
        }
        if (digits.length === 10 && digits.charAt(0) === '9') {
            digits = '0' + digits;
        }
        return digits;
    }

    function formatNumber(value) {
        var n = Math.round(Number(value) || 0);
        var neg = n < 0;
        n = Math.abs(n);
        var str = String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return (neg ? '-' : '') + str;
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function firstErrorMessage(errors) {
        if (!errors || typeof errors !== 'object') return '';
        for (var key in errors) {
            if (!Object.prototype.hasOwnProperty.call(errors, key)) continue;
            var val = errors[key];
            if (Array.isArray(val) && val.length) return val[0];
            if (typeof val === 'string' && val) return val;
        }
        return '';
    }

    function triggerFormSubmit(form) {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }
        var tmpBtn = document.createElement('button');
        tmpBtn.type = 'submit';
        tmpBtn.style.position = 'absolute';
        tmpBtn.style.left = '-9999px';
        tmpBtn.style.width = '1px';
        tmpBtn.style.height = '1px';
        form.appendChild(tmpBtn);
        tmpBtn.click();
        form.removeChild(tmpBtn);
    }

    /* =========================================================
     * 1) Global toggle switches
     * ========================================================= */

    function syncToggleUI(field) {
        var checkbox = field.querySelector('[data-toggle-input]');
        var btn = field.querySelector('[data-toggle-btn]');
        var offInput = field.querySelector('[data-toggle-off]');
        if (!checkbox || !btn) return;

        var on = !!checkbox.checked;
        btn.classList.toggle('is-on', on);
        btn.classList.toggle('is-off', !on);
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');

        var stateEl = btn.querySelector('.toggle-state');
        if (stateEl) {
            stateEl.textContent = on
                ? (btn.getAttribute('data-on-text') || 'روشن')
                : (btn.getAttribute('data-off-text') || 'خاموش');
        }

        // Only one value (on or off) should ever be posted to the server.
        if (offInput) offInput.disabled = on;
    }

    function initToggles() {
        var fields = document.querySelectorAll('[data-toggle-field]');
        fields.forEach(function (field) {
            syncToggleUI(field);
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-toggle-btn]');
            if (!btn) return;
            var field = btn.closest('[data-toggle-field]');
            if (!field) return;
            var checkbox = field.querySelector('[data-toggle-input]');
            if (!checkbox) return;

            e.preventDefault();
            checkbox.checked = !checkbox.checked;
            syncToggleUI(field);
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    /* =========================================================
     * 2) Workspace tabs
     * ========================================================= */

    function activateWorkspaceTab(tab) {
        var container = tab.closest('[data-workspace-tabs]')
            || (tab.closest('.ws-tabs') && tab.closest('.ws-tabs').parentElement)
            || document;

        var key = tab.getAttribute('data-ws-tab');
        if (!key) return;

        var tabs = container.querySelectorAll('[data-ws-tab]');
        var panes = container.querySelectorAll('[data-ws-pane]');

        tabs.forEach(function (t) {
            t.classList.toggle('active', t === tab);
        });
        panes.forEach(function (pane) {
            pane.classList.toggle('active', pane.getAttribute('data-ws-pane') === key);
        });
    }

    function initWorkspaceTabs() {
        document.addEventListener('click', function (e) {
            var tab = e.target.closest('[data-ws-tab]');
            if (!tab) return;
            e.preventDefault();
            activateWorkspaceTab(tab);
            var key = tab.getAttribute('data-ws-tab');
            if (key && history.replaceState) {
                try { history.replaceState(null, '', '#' + key); } catch (err) {}
            }
        });

        // Open tab from ?tab=, data-active-tab, or URL hash (#backup)
        var params = new URLSearchParams(location.search || '');
        var fromQuery = params.get('tab') || '';
        var root = document.querySelector('[data-workspace-tabs]');
        var fromData = root ? (root.getAttribute('data-active-tab') || '') : '';
        var hash = (location.hash || '').replace(/^#/, '');
        var key = fromQuery || fromData || hash;
        if (key) {
            var tab = document.querySelector('[data-ws-tab="' + key + '"]');
            if (tab) activateWorkspaceTab(tab);
        }
    }

    /* =========================================================
     * 3) Confirm buttons
     * ========================================================= */

    function initConfirmButtons() {
        // One confirm only: forms on submit, non-form controls on click.
        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-confirm]');
            if (!el) return;
            if (el.tagName === 'FORM') return; // handled on submit
            var message = el.getAttribute('data-confirm') || 'مطمئن هستید؟';
            if (!window.confirm(message)) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        }, true);

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || !form.hasAttribute || !form.hasAttribute('data-confirm')) return;
            if (form.getAttribute('data-exit-otp-blocked') === '1') {
                e.preventDefault();
                window.alert('برای خروج این قبض ابتدا کد تأیید مشتری را ارسال و تأیید کنید (یا مدیر عبور بزند).');
                var otpBox = document.getElementById('rx-exit-otp');
                if (otpBox && otpBox.scrollIntoView) {
                    otpBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }
            if (form.getAttribute('data-confirm-accepted') === '1') {
                form.removeAttribute('data-confirm-accepted');
                return;
            }
            var message = form.getAttribute('data-confirm') || 'مطمئن هستید؟';
            if (!window.confirm(message)) {
                e.preventDefault();
                return;
            }
            form.setAttribute('data-confirm-accepted', '1');
        }, true);
    }

    /* =========================================================
     * 5) ASCII / barcode + Persian-keyboard → English helpers
     * ========================================================= */

    // Windows Persian keyboard (unshifted) → English QWERTY key
    var FA_KEYBOARD_TO_EN = {
        'ض': 'q', 'ص': 'w', 'ث': 'e', 'ق': 'r', 'ف': 't', 'غ': 'y', 'ع': 'u', 'ه': 'i', 'خ': 'o', 'ح': 'p',
        'ج': '[', 'چ': ']',
        'ش': 'a', 'س': 's', 'ی': 'd', 'ي': 'd', 'ب': 'f', 'ل': 'g', 'ا': 'h', 'ت': 'j', 'ن': 'k', 'م': 'l',
        'ک': ';', 'ك': ';', 'گ': "'",
        'ظ': 'z', 'ط': 'x', 'ز': 'c', 'ر': 'v', 'ذ': 'b', 'د': 'n', 'پ': 'm', 'و': ',',
        // shifted / common extras when user meant Latin
        'ْ': '`', 'ٓ': '~', 'ٰ': 'Q', 'ـ': 'W', 'ژ': 'C',
        'آ': 'H', 'ة': 'M', 'ء': 'X', 'ئ': 'S', 'ؤ': 'A',
        '؟': '?', '،': ',', '؛': ';'
    };

    function persianKeyboardToEnglish(value) {
        var raw = convertDigits(String(value == null ? '' : value));
        var out = '';
        for (var i = 0; i < raw.length; i++) {
            var ch = raw.charAt(i);
            if (Object.prototype.hasOwnProperty.call(FA_KEYBOARD_TO_EN, ch)) {
                out += FA_KEYBOARD_TO_EN[ch];
            } else if (/[\u0600-\u06FF]/.test(ch)) {
                // unknown Arabic/Persian glyph: drop (serial/model must be Latin)
                continue;
            } else {
                out += ch;
            }
        }
        return out;
    }

    function isSerialOrModelField(el) {
        if (!el) return false;
        // Opt-in attribute, or reception serial/model fields (barcode-enabled Latin fields).
        if (el.matches && el.matches('[data-fa-en]')) return true;
        var name = (el.getAttribute('name') || el.getAttribute('data-name') || '').toLowerCase();
        var isLatinDeviceField = name.indexOf('serial') !== -1
            || name === 'model'
            || name === 'brand'
            || name === 'brand_model';
        return isLatinDeviceField && el.matches && el.matches('[data-barcode], [data-ascii-en], [data-fa-en]');
    }

    function convertAsciiField(el, opts) {
        if (!el || typeof el.value !== 'string') return;
        opts = opts || {};
        // Serial/model (manual + barcode): Windows Persian layout → English QWERTY, then digits.
        // Other data-ascii-en fields (phone, Persian search text): digits only.
        var converted = isSerialOrModelField(el)
            ? persianKeyboardToEnglish(el.value)
            : convertDigits(el.value);
        if (isSerialOrModelField(el)) {
            // فقط حروف/اعداد انگلیسی و جداکننده‌های رایج سریال — زبان دیگر حذف شود.
            converted = String(converted).toUpperCase().replace(/[^A-Z0-9\-_\/\.\+ ]+/g, '');
        }
        if (converted === el.value) return;

        el.value = converted;
        // Barcode wedges append at end — keep caret at end to avoid dropped chars.
        if (opts.caret === 'end') {
            try { el.setSelectionRange(converted.length, converted.length); } catch (err) {}
            return;
        }
        if (typeof el.selectionStart === 'number') {
            try {
                var pos = Math.min(converted.length, el.selectionStart);
                el.setSelectionRange(pos, pos);
            } catch (err2) {}
        }
    }

    function convertAsciiFieldsIn(scope) {
        (scope || document).querySelectorAll('[data-ascii-en], [data-barcode]').forEach(function (el) {
            convertAsciiField(el, { caret: 'end' });
        });
    }

    function focusNextAfterBarcode(el) {
        var root = el.closest('form') || document;
        var focusables = Array.prototype.slice.call(
            root.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])')
        ).filter(function (node) {
            if (node === el) return true;
            if (node.tabIndex < 0) return false;
            if (node.offsetParent === null && node.type !== 'file') return false;
            return true;
        });
        var idx = focusables.indexOf(el);
        if (idx >= 0 && idx < focusables.length - 1) {
            focusables[idx + 1].focus();
            if (typeof focusables[idx + 1].select === 'function') {
                try { focusables[idx + 1].select(); } catch (err) {}
            }
        }
    }

    function initAsciiFields() {
        // Live typing for serial/model: FA→EN + strip non-English immediately.
        document.addEventListener('input', function (e) {
            var el = e.target;
            if (!el || !el.matches) return;
            if (!(el.matches('[data-ascii-en]') || el.matches('[data-barcode]') || el.matches('[data-fa-en]'))) return;
            if (isSerialOrModelField(el) || el.matches('[data-fa-en]')) {
                convertAsciiField(el, { caret: 'end' });
                return;
            }
            convertAsciiField(el, { caret: 'end' });
        }, true);

        // 'blur' does not bubble, but capturing listeners on ancestors still fire.
        document.addEventListener('blur', function (e) {
            var el = e.target;
            if (!el || !el.matches) return;
            if (el.matches('[data-ascii-en]') || el.matches('[data-barcode]') || el.matches('[data-fa-en]')) {
                convertAsciiField(el, { caret: 'end' });
            }
        }, true);

        // Scanner suffix Enter must NOT submit the reception form mid-scan.
        // GET search forms still submit on Enter (normal search UX).
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            var el = e.target;
            if (!el || !el.matches || !el.matches('input[data-barcode], textarea[data-barcode]')) return;
            if (el.getAttribute('data-barcode-submit') === '1') return;
            var form = el.form || el.closest('form');
            if (form && String(form.getAttribute('method') || 'get').toLowerCase() === 'get') return;

            e.preventDefault();
            convertAsciiField(el, { caret: 'end' });
            focusNextAfterBarcode(el);
        }, true);
    }

    /* =========================================================
     * 6) App modal helpers
     * ========================================================= */

    function openAppModal(selector, opener) {
        if (!selector) return;
        var modal = document.querySelector(selector);
        if (!modal) return;
        modal.removeAttribute('hidden');
        modal.classList.add('is-open');

        // Lazy-load heavy report HTML only when the modal is opened.
        var url = (opener && opener.getAttribute('data-report-url')) || modal.getAttribute('data-report-url');
        var body = modal.querySelector('[data-report-body]');
        if (url && body && !body.getAttribute('data-loaded')) {
            body.innerHTML = '<p class="muted" style="margin:0;">در حال بارگذاری…</p>';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw new Error('load failed'); return r.text(); })
                .then(function (html) {
                    body.innerHTML = html;
                    body.setAttribute('data-loaded', '1');
                })
                .catch(function () {
                    body.innerHTML = '<p class="muted" style="margin:0;">بارگذاری جزئیات ناموفق بود. صفحه قبض را باز کنید.</p>';
                });
        }
    }

    function closeAppModal(modal) {
        if (!modal) return;
        modal.classList.remove('is-open');
    }

    function initAppModals() {
        document.addEventListener('click', function (e) {
            var opener = e.target.closest('[data-open-modal]');
            if (opener) {
                e.preventDefault();
                openAppModal(opener.getAttribute('data-open-modal'), opener);
                return;
            }

            var closer = e.target.closest('[data-close-modal], .app-modal-close');
            if (closer) {
                e.preventDefault();
                closeAppModal(closer.closest('.app-modal'));
                return;
            }

            // Click on the backdrop itself (not the dialog inside it) closes the modal.
            if (e.target.classList && e.target.classList.contains('app-modal')) {
                closeAppModal(e.target);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var open = document.querySelector('.app-modal.is-open');
            if (open) closeAppModal(open);
        });
    }

    /* =========================================================
     * 4) Reception wizard (#reception-wizard)
     * ========================================================= */

    function initReceptionWizard() {
        var form = document.getElementById('reception-wizard');
        if (!form) return;

        var lookupUrl = form.getAttribute('data-lookup-url') || '';
        var lookupCustomersUrl = form.getAttribute('data-lookup-customers-url') || '';
        var lookupSerialUrl = form.getAttribute('data-lookup-serial-url') || '';
        var ensureCustomerUrl = form.getAttribute('data-ensure-customer-url') || '';
        var skipPhone = form.getAttribute('data-skip-phone') === '1';
        var oldMode = form.getAttribute('data-old-mode') || 'single';
        var csrfToken = getCsrfToken();

        var stepPhone = document.getElementById('step-phone');
        var lookupPhoneInput = document.getElementById('lookup-phone');
        var lookupPhoneBtn = document.getElementById('lookup-phone-btn');
        var lookupNameInput = document.getElementById('lookup-name');
        var lookupNameBtn = document.getElementById('lookup-name-btn');
        var customerPickList = document.getElementById('customer-pick-list');
        var lookupStatus = document.getElementById('lookup-status');
        var startTabs = form.querySelectorAll('[data-start-tab]');
        var nameSearchTimer = null;
        var nameSearchSeq = 0;
        var serialLookupInput = document.getElementById('serial-lookup-input');
        var serialLookupBanner = document.getElementById('serial-lookup-banner');
        var serialSuggestActions = document.getElementById('serial-suggest-actions');
        var serialFillPrevBtn = document.getElementById('serial-fill-prev-btn');
        var lastSerialSuggest = null;

        var modeModal = document.getElementById('mode-modal');
        var chooseModeBtns = form.querySelectorAll('[data-choose-mode]');
        var modeModalCancel = document.getElementById('mode-modal-cancel');

        var stepBody = document.getElementById('step-body');
        var existingCard = document.getElementById('existing-customer-card');
        var existingName = document.getElementById('existing-customer-name');
        var existingMeta = document.getElementById('existing-customer-meta');

        var newCustomerFields = document.getElementById('new-customer-fields');
        var saveCustomerBtn = document.getElementById('save-customer-btn');
        var customerSaveStatus = document.getElementById('customer-save-status');

        var changePhoneBtn = document.getElementById('change-phone-btn');
        var backToPhoneBtn = document.getElementById('back-to-phone-btn');
        var changeModeBtn = document.getElementById('change-mode-btn');
        var changeModeBtn2 = document.getElementById('change-mode-btn-2');

        var customerIdInput = form.querySelector('input[name="customer_id"]');
        var customerPhoneInput = form.querySelector('input[name="customer_phone"]');
        var intakeModeInput = document.getElementById('intake-mode');
        var formActionInput = document.getElementById('form-action');

        var modeBadge = document.getElementById('mode-badge');
        var modeBadgeText = document.getElementById('mode-badge-text');
        var modeSingle = document.getElementById('mode-single');
        var modeGroup = document.getElementById('mode-group');

        var groupDeviceList = document.getElementById('group-device-list');
        var groupDeviceTemplate = document.getElementById('group-device-template');
        var groupAddBtn = document.getElementById('group-add-btn');
        var groupDupBtn = document.getElementById('group-dup-btn');
        var groupExpandLastBtn = document.getElementById('group-expand-last-btn');
        var groupCollapseAllBtn = document.getElementById('group-collapse-all-btn');
        var groupCount = document.getElementById('group-count');
        var groupSummaryDeposit = document.getElementById('group-summary-deposit');
        var groupHint = document.getElementById('group-hint');

        var state = { modeChosen: false };

        function applyNoteMenuValue(field, value) {
            if (!field || !value) return;
            value = String(value).trim();
            if (!value) return;
            var current = (field.value || '').trim();
            if (!current || current === 'ندارد') {
                field.value = value;
            } else if (current.indexOf(value) === -1) {
                field.value = current + '، ' + value;
            }
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function applyNoteMenu(select) {
            if (!select) return;
            var value = (select.value || '').trim();
            if (!value) return;
            var targetName = select.getAttribute('data-note-target');
            if (!targetName) return;
            var scope = select.closest('[data-device-card]')
                || select.closest('#mode-single')
                || select.closest('form')
                || document;
            var field = scope.querySelector('[data-name="' + targetName + '"]')
                || scope.querySelector('[name="' + targetName + '"]')
                || scope.querySelector('[data-note-ctx="' + targetName + '"]');
            if (!field) return;
            applyNoteMenuValue(field, value);
            select.value = '';
        }

        function noteMenuOptions(select) {
            if (!select) return [];
            return Array.prototype.slice.call(select.options || [])
                .map(function (opt) { return (opt.value || '').trim(); })
                .filter(Boolean);
        }

        function closeNoteContextMenu() {
            var existing = document.getElementById('note-ctx-menu');
            if (existing) existing.remove();
        }

        function openNoteContextMenu(e, field, options) {
            closeNoteContextMenu();
            var menu = document.createElement('div');
            menu.id = 'note-ctx-menu';
            menu.className = 'note-ctx-menu';
            menu.setAttribute('role', 'menu');
            if (!options.length) {
                var empty = document.createElement('div');
                empty.className = 'note-ctx-empty';
                empty.textContent = 'تعریفی در تنظیمات lookups ثبت نشده است.';
                menu.appendChild(empty);
            } else {
                options.forEach(function (opt) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'note-ctx-item';
                    btn.textContent = opt;
                    btn.addEventListener('click', function () {
                        applyNoteMenuValue(field, opt);
                        closeNoteContextMenu();
                    });
                    menu.appendChild(btn);
                });
            }
            document.body.appendChild(menu);
            var x = e.clientX;
            var y = e.clientY;
            var rect = menu.getBoundingClientRect();
            if (x + rect.width > window.innerWidth - 8) x = Math.max(8, window.innerWidth - rect.width - 8);
            if (y + rect.height > window.innerHeight - 8) y = Math.max(8, window.innerHeight - rect.height - 8);
            menu.style.left = x + 'px';
            menu.style.top = y + 'px';
        }

        function wireNoteMenus(root) {
            if (!root) return;
            root.querySelectorAll('.note-menu[data-note-target]').forEach(function (select) {
                if (select.getAttribute('data-note-wired') === '1') return;
                select.setAttribute('data-note-wired', '1');
                select.addEventListener('change', function () {
                    applyNoteMenu(select);
                });

                var targetName = select.getAttribute('data-note-target');
                var scope = select.closest('[data-device-card]')
                    || select.closest('#mode-single')
                    || select.closest('form')
                    || root;
                var field = scope.querySelector('[data-note-ctx="' + targetName + '"]')
                    || scope.querySelector('[data-name="' + targetName + '"]')
                    || scope.querySelector('[name="' + targetName + '"]');
                if (!field || field.getAttribute('data-note-ctx-wired') === '1') return;
                field.setAttribute('data-note-ctx-wired', '1');
                field.addEventListener('contextmenu', function (e) {
                    var options = noteMenuOptions(select);
                    if (!options.length) return;
                    e.preventDefault();
                    openNoteContextMenu(e, field, options);
                });
            });
        }

        /* ---------- status helpers ---------- */

        function setLookupStatus(text, type) {
            if (!lookupStatus) return;
            lookupStatus.textContent = text || '';
            lookupStatus.className = 'lookup-status' + (type ? ' is-' + type : '');
        }

        function setCustomerSaveStatus(text, type) {
            if (!customerSaveStatus) return;
            customerSaveStatus.textContent = text || '';
            customerSaveStatus.className = 'lookup-status' + (type ? ' is-' + type : '');
        }

        /* ---------- phone step ---------- */

        function showPhoneStep() {
            if (stepPhone) stepPhone.classList.remove('hidden');
            if (stepBody) stepBody.classList.add('hidden');
        }

        function showBodyStep() {
            if (stepPhone) stepPhone.classList.add('hidden');
            if (stepBody) stepBody.classList.remove('hidden');
        }

        function setStartTab(tab) {
            tab = tab === 'name' ? 'name' : 'phone';
            startTabs.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-start-tab') === tab);
            });
            form.querySelectorAll('[data-start-pane]').forEach(function (pane) {
                pane.classList.toggle('is-active', pane.getAttribute('data-start-pane') === tab);
            });
            if (tab === 'name') {
                if (lookupNameInput) lookupNameInput.focus();
            } else if (lookupPhoneInput) {
                lookupPhoneInput.focus();
            }
        }

        function clearCustomerPickList() {
            if (!customerPickList) return;
            customerPickList.innerHTML = '';
            customerPickList.hidden = true;
        }

        function goBackToPhone(resetCustomer) {
            closeModeModal();
            showPhoneStep();
            if (resetCustomer) {
                if (customerIdInput) customerIdInput.value = '';
                if (customerPhoneInput) customerPhoneInput.value = '';
                if (existingCard) existingCard.classList.add('hidden');
                if (lookupPhoneInput) lookupPhoneInput.value = '';
                if (lookupNameInput) lookupNameInput.value = '';
                clearCustomerPickList();
            }
            var activeTab = form.querySelector('.start-tab.is-active');
            setStartTab(activeTab ? activeTab.getAttribute('data-start-tab') : 'phone');
        }

        function showExistingCustomer(customer) {
            if (!existingCard) return;
            existingCard.classList.remove('hidden');
            if (existingName) existingName.textContent = (customer && (customer.display_name || customer.name)) || 'بدون نام';

            var metaParts = [];
            if (customer && customer.phone) metaParts.push('موبایل: ' + customer.phone);
            if (customer && customer.alias) metaParts.push('مستعار: ' + customer.alias);
            if (customer && customer.gender) metaParts.push('جنسیت: ' + customer.gender);
            if (customer && customer.job) metaParts.push('شغل: ' + customer.job);
            if (customer && customer.address) metaParts.push('آدرس: ' + customer.address);
            if (customer && customer.is_blacklisted) metaParts.push('لیست سیاه');
            if (customer && typeof customer.visits !== 'undefined' && customer.visits !== null) {
                metaParts.push('مراجعات قبلی: ' + toPersianDigits(customer.visits) + ' بار');
            }
            if (existingMeta) existingMeta.textContent = metaParts.join(' · ');
        }

        function clearSerialBanner() {
            lastSerialSuggest = null;
            if (serialLookupBanner) {
                serialLookupBanner.classList.add('hidden');
                serialLookupBanner.textContent = '';
            }
            if (serialSuggestActions) serialSuggestActions.classList.add('hidden');
        }

        function applySerialSuggest(suggest) {
            if (!suggest) return;
            var brandModel = form.querySelector('[name="brand_model"]');
            var brand = form.querySelector('[name="brand"]');
            var model = form.querySelector('[name="model"]');
            var lockCode = form.querySelector('[name="lock_code"]');
            var hdd = form.querySelector('[name="hdd_capacity"]');
            if (brandModel && suggest.brand_model) brandModel.value = suggest.brand_model;
            if (brand && suggest.brand) brand.value = suggest.brand;
            if (model && suggest.model) model.value = suggest.model;
            if (lockCode && suggest.lock_code) lockCode.value = suggest.lock_code;
            if (hdd && suggest.hdd_capacity) hdd.value = suggest.hdd_capacity;
        }

        function doSerialLookup() {
            if (!serialLookupInput || !lookupSerialUrl) return;
            var serial = (serialLookupInput.value || '').trim();
            if (serial.length < 3) {
                clearSerialBanner();
                return;
            }
            fetch(lookupSerialUrl + '?serial=' + encodeURIComponent(serial), {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.found) {
                        clearSerialBanner();
                        return;
                    }
                    lastSerialSuggest = data.suggest || null;
                    var msg = data.block_reason || 'سریال قبلاً ثبت شده است.';
                    if (data.suggest && data.suggest.ticket_no) {
                        msg += ' قبض قبلی: ' + data.suggest.ticket_no;
                        if (data.suggest.status_label) msg += ' (' + data.suggest.status_label + ')';
                        if (data.suggest.customer_name) msg += ' — ' + data.suggest.customer_name;
                    }
                    if (data.can_reuse) {
                        msg += ' — می‌توانید مشخصات قبلی را پر کنید و پذیرش جدید ثبت کنید.';
                    }
                    if (serialLookupBanner) {
                        serialLookupBanner.textContent = msg;
                        serialLookupBanner.classList.remove('hidden');
                        serialLookupBanner.className = 'alert ' + (data.blocked ? 'alert-error' : 'alert-success');
                        serialLookupBanner.style.marginBottom = '8px';
                    }
                    if (serialSuggestActions) {
                        serialSuggestActions.classList.toggle('hidden', !(data.can_reuse && data.suggest));
                    }
                })
                .catch(function () {});
        }

        function doLookup() {
            if (!lookupPhoneInput) return;
            var phone = normalizePhoneDigits(lookupPhoneInput.value);
            lookupPhoneInput.value = phone;

            if (phone.length < 10) {
                setLookupStatus('شماره موبایل را کامل و صحیح وارد کنید.', 'error');
                lookupPhoneInput.focus();
                return;
            }

            if (!lookupUrl) {
                setLookupStatus('آدرس بررسی موبایل تنظیم نشده است.', 'error');
                return;
            }

            setLookupStatus('در حال بررسی...', 'info');
            if (lookupPhoneBtn) lookupPhoneBtn.disabled = true;

            fetch(lookupUrl + '?phone=' + encodeURIComponent(phone), {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (lookupPhoneBtn) lookupPhoneBtn.disabled = false;
                    handleLookupResult(data, phone);
                })
                .catch(function () {
                    if (lookupPhoneBtn) lookupPhoneBtn.disabled = false;
                    setLookupStatus('خطا در ارتباط با سرور. دوباره تلاش کنید.', 'error');
                });
        }

        function selectExistingCustomer(customer, statusText) {
            if (!customer) return;
            if (customer.is_blacklisted) {
                if (customerIdInput) customerIdInput.value = '';
                if (existingCard) existingCard.classList.add('hidden');
                if (newCustomerFields) newCustomerFields.classList.add('hidden');
                setLookupStatus((customer.blacklist_reason || 'این مشتری در لیست سیاه است') + ' — پذیرش مسدود است.', 'error');
                showPhoneStep();
                return;
            }
            if (customerIdInput) customerIdInput.value = customer.id || '';
            if (customerPhoneInput) customerPhoneInput.value = customer.phone || '';
            if (lookupPhoneInput && customer.phone) lookupPhoneInput.value = customer.phone;
            showExistingCustomer(customer);
            if (newCustomerFields) newCustomerFields.classList.add('hidden');
            setLookupStatus(statusText || ('مشتری انتخاب شد: ' + (customer.display_name || customer.name || '—')), 'ok');
            clearCustomerPickList();
            showBodyStep();
            if (!state.modeChosen) {
                openModeModal();
            }
        }

        function renderCustomerPickList(customers, query) {
            if (!customerPickList) return;
            customerPickList.innerHTML = '';
            if (!customers || !customers.length) {
                customerPickList.hidden = false;
                var empty = document.createElement('div');
                empty.className = 'customer-pick-empty';
                empty.textContent = query
                    ? 'مشتری با این نام پیدا نشد. با موبایل ادامه دهید یا مشتری جدید ثبت کنید.'
                    : 'حداقل ۲ حرف از نام را بنویسید.';
                customerPickList.appendChild(empty);
                return;
            }

            customers.forEach(function (customer) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'customer-pick-item';
                var name = document.createElement('span');
                name.className = 'customer-pick-name';
                name.textContent = customer.display_name || customer.name || 'بدون نام';
                var meta = document.createElement('span');
                meta.className = 'customer-pick-meta';
                var bits = [];
                if (customer.phone) bits.push(customer.phone);
                if (customer.job) bits.push(customer.job);
                if (typeof customer.visits !== 'undefined' && customer.visits !== null) {
                    bits.push(toPersianDigits(customer.visits) + ' مراجعه');
                }
                if (customer.is_blacklisted) bits.push('لیست سیاه');
                meta.textContent = bits.join(' · ') || '—';
                btn.appendChild(name);
                btn.appendChild(meta);
                btn.addEventListener('click', function () {
                    selectExistingCustomer(customer, 'مشتری انتخاب شد: ' + (customer.display_name || customer.name || '—'));
                });
                customerPickList.appendChild(btn);
            });
            customerPickList.hidden = false;
        }

        function doNameLookup(immediate) {
            if (!lookupNameInput) return;
            var q = (lookupNameInput.value || '').trim();
            if (nameSearchTimer) {
                clearTimeout(nameSearchTimer);
                nameSearchTimer = null;
            }

            var run = function () {
                q = (lookupNameInput.value || '').trim();
                if (q.length < 2) {
                    clearCustomerPickList();
                    setLookupStatus('حداقل ۲ حرف از نام را بنویسید.', 'info');
                    return;
                }
                if (!lookupCustomersUrl) {
                    setLookupStatus('آدرس جستجوی مشتری تنظیم نشده است.', 'error');
                    return;
                }

                var seq = ++nameSearchSeq;
                setLookupStatus('در حال جستجوی نام...', 'info');
                if (lookupNameBtn) lookupNameBtn.disabled = true;

                fetch(lookupCustomersUrl + '?q=' + encodeURIComponent(q), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (seq !== nameSearchSeq) return;
                        if (lookupNameBtn) lookupNameBtn.disabled = false;
                        var list = (data && data.customers) || [];
                        renderCustomerPickList(list, q);
                        if (list.length) {
                            setLookupStatus(toPersianDigits(list.length) + ' مشتری پیدا شد — یکی را انتخاب کنید.', 'ok');
                        } else {
                            setLookupStatus('مشتری پیدا نشد. با موبایل ادامه دهید یا مشتری جدید ثبت کنید.', 'info');
                        }
                    })
                    .catch(function () {
                        if (seq !== nameSearchSeq) return;
                        if (lookupNameBtn) lookupNameBtn.disabled = false;
                        setLookupStatus('خطا در ارتباط با سرور. دوباره تلاش کنید.', 'error');
                    });
            };

            if (immediate) run();
            else nameSearchTimer = setTimeout(run, 280);
        }

        function handleLookupResult(data, fallbackPhone) {
            var phone = (data && data.phone) || fallbackPhone;
            if (customerPhoneInput) customerPhoneInput.value = phone;

            if (data && data.blocked) {
                if (customerIdInput) customerIdInput.value = '';
                if (existingCard) existingCard.classList.add('hidden');
                if (newCustomerFields) newCustomerFields.classList.add('hidden');
                setLookupStatus((data.block_reason || 'این مشتری در لیست سیاه است') + ' — پذیرش مسدود است.', 'error');
                showPhoneStep();
                return;
            }

            if (data && data.found && data.customer) {
                selectExistingCustomer(data.customer, 'مشتری پیدا شد: ' + (data.customer.display_name || data.customer.name || '—'));
                return;
            }

            if (customerIdInput) customerIdInput.value = '';
            if (existingCard) existingCard.classList.add('hidden');
            if (newCustomerFields) newCustomerFields.classList.remove('hidden');
            setLookupStatus('مشتری جدید است؛ اطلاعات را کامل و ثبت کنید.', 'info');

            showBodyStep();

            if (!state.modeChosen) {
                openModeModal();
            }
        }

        /* ---------- mode modal ---------- */

        function openModeModal() {
            if (modeModal) modeModal.classList.remove('hidden');
        }

        function closeModeModal() {
            if (modeModal) modeModal.classList.add('hidden');
        }

        function setWorkspaceEnabled(root, enabled) {
            if (!root) return;
            root.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                // Keep structural controls clickable when visible; only gate named fields for submit.
                if (el.tagName === 'BUTTON') {
                    el.disabled = !enabled;
                    return;
                }
                if (!el.name) return;
                el.disabled = !enabled;
            });
        }

        function chooseMode(mode) {
            mode = mode === 'group' ? 'group' : 'single';
            state.modeChosen = true;

            if (intakeModeInput) intakeModeInput.value = mode;
            if (modeBadge) modeBadge.classList.remove('hidden');
            if (modeBadgeText) modeBadgeText.textContent = mode === 'group' ? 'گروهی' : 'تکی';

            closeModeModal();
            showBodyStep();

            if (mode === 'group') {
                if (modeSingle) modeSingle.classList.add('hidden');
                if (modeGroup) modeGroup.classList.remove('hidden');
                setWorkspaceEnabled(modeSingle, false);
                setWorkspaceEnabled(modeGroup, true);
                ensureMinimumDeviceCards();
            } else {
                if (modeGroup) modeGroup.classList.add('hidden');
                if (modeSingle) modeSingle.classList.remove('hidden');
                setWorkspaceEnabled(modeGroup, false);
                setWorkspaceEnabled(modeSingle, true);
            }
        }

        /* ---------- customer save ---------- */

        function saveCustomer() {
            if (!saveCustomerBtn) return;
            var scope = newCustomerFields || form;
            var nameInput = scope.querySelector('[name="customer_name"]');
            var aliasInput = scope.querySelector('[name="alias"]');
            var genderInput = scope.querySelector('[name="gender"]');
            var nationalInput = scope.querySelector('[name="national_code"]');
            var jobInput = scope.querySelector('[name="job"]');
            var addressInput = scope.querySelector('[name="address"]');
            var referralInput = scope.querySelector('[name="referral_source_id"]');

            var name = ((nameInput && nameInput.value) || '').trim();
            if (!name) {
                setCustomerSaveStatus('نام مشتری را وارد کنید.', 'error');
                if (nameInput) nameInput.focus();
                return;
            }

            var phone = normalizePhoneDigits((customerPhoneInput && customerPhoneInput.value)
                || (lookupPhoneInput && lookupPhoneInput.value) || '');
            if (phone.length < 10) {
                setCustomerSaveStatus('شماره موبایل نامعتبر است.', 'error');
                return;
            }

            var payload = {
                customer_id: (customerIdInput && customerIdInput.value) || null,
                customer_name: name,
                customer_phone: phone,
                alias: aliasInput ? aliasInput.value : '',
                gender: genderInput ? genderInput.value : '',
                national_code: nationalInput ? nationalInput.value : '',
                job: jobInput ? jobInput.value : '',
                address: addressInput ? addressInput.value : '',
                referral_source_id: referralInput ? referralInput.value : ''
            };

            setCustomerSaveStatus('در حال ذخیره...', 'info');
            saveCustomerBtn.disabled = true;

            fetch(ensureCustomerUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            })
                .then(function (r) {
                    return r.json().then(function (data) {
                        return { ok: r.ok, status: r.status, data: data };
                    });
                })
                .then(function (res) {
                    saveCustomerBtn.disabled = false;
                    if (res.ok && res.data && res.data.ok && res.data.customer) {
                        if (customerIdInput) customerIdInput.value = res.data.customer.id;
                        if (customerPhoneInput) customerPhoneInput.value = res.data.customer.phone;
                        setCustomerSaveStatus('مشتری با موفقیت در لیست ثبت شد.', 'ok');
                    } else {
                        var msg = (res.data && (res.data.message || firstErrorMessage(res.data.errors)))
                            || 'خطا در ثبت مشتری.';
                        setCustomerSaveStatus(msg, 'error');
                    }
                })
                .catch(function () {
                    saveCustomerBtn.disabled = false;
                    setCustomerSaveStatus('خطا در ارتباط با سرور.', 'error');
                });
        }

        /* ---------- group device cards ---------- */

        function fieldValue(card, name) {
            var el = card.querySelector('[data-name="' + name + '"]');
            if (!el) return '';
            return (el.value || '').trim();
        }

        function updateDevicePreview(card) {
            var previewEl = card.querySelector('[data-device-preview]');
            if (!previewEl) return;

            var serial = fieldValue(card, 'serial_number');
            var brandModel = fieldValue(card, 'brand_model')
                || (fieldValue(card, 'brand') + ' ' + fieldValue(card, 'model')).trim();

            var parts = [];
            if (serial) parts.push('سریال ' + serial);
            if (brandModel) parts.push(brandModel);

            previewEl.textContent = parts.length ? parts.join(' — ') : 'در حال تکمیل…';
            previewEl.classList.toggle('muted', parts.length === 0);
        }

        function toggleCollapse(card) {
            card.classList.toggle('is-collapsed');
            card.classList.toggle('is-active', !card.classList.contains('is-collapsed'));
        }

        function collapseAllExcept(activeCard) {
            groupDeviceList.querySelectorAll('[data-device-card]').forEach(function (card) {
                if (card === activeCard) {
                    card.classList.remove('is-collapsed');
                    card.classList.add('is-active');
                } else {
                    card.classList.add('is-collapsed');
                    card.classList.remove('is-active');
                }
            });
        }

        function bumpSequentialCode(base, offset) {
            if (!base) return '';
            var ascii = convertDigits(String(base)).trim();
            var match = ascii.match(/^(.*?)(\d+)(\D*)$/);
            if (!match) return ascii;
            var prefix = match[1];
            var digits = match[2];
            var suffix = match[3] || '';
            var next = String(parseInt(digits, 10) + offset);
            if (next.length < digits.length) {
                next = Array(digits.length - next.length + 1).join('0') + next;
            }
            return prefix + next + suffix;
        }

        function assignGroupReceiptNumbers(cards) {
            var baseReceipt = form ? (form.getAttribute('data-next-receipt') || '') : '';
            var baseTicket = form ? (form.getAttribute('data-next-ticket') || '') : '';
            cards.forEach(function (card, index) {
                var receipt = bumpSequentialCode(baseReceipt, index);
                var ticket = bumpSequentialCode(baseTicket, index);
                var receiptEl = card.querySelector('[data-device-receipt]');
                if (receiptEl) {
                    receiptEl.textContent = receipt || '—';
                    receiptEl.title = ticket
                        ? ('شماره قبض: ' + receipt + ' | کد پذیرش: ' + ticket)
                        : ('شماره قبض: ' + (receipt || '—'));
                }
                card.setAttribute('data-preview-receipt', receipt || '');
                card.setAttribute('data-preview-ticket', ticket || '');
            });
        }

        function reindexDeviceCards() {
            var cards = groupDeviceList.querySelectorAll('[data-device-card]');
            cards.forEach(function (card, index) {
                card.querySelectorAll('[data-name]').forEach(function (field) {
                    var name = field.getAttribute('data-name');
                    field.name = 'items[' + index + '][' + name + ']';
                });
                var indexEl = card.querySelector('.device-index');
                if (indexEl) indexEl.textContent = 'قبض ' + toPersianDigits(index + 1);
            });
            assignGroupReceiptNumbers(cards);
            updateGroupCountText(cards.length);
            return cards;
        }

        function updateGroupCountText(count) {
            if (groupCount) groupCount.textContent = toPersianDigits(count) + ' قبض';
        }

        function updateGroupSummary() {
            var cards = groupDeviceList.querySelectorAll('[data-device-card]');
            var sum = 0;
            cards.forEach(function (card) {
                var depositField = card.querySelector('[data-deposit]');
                if (depositField) sum += parseInt(convertDigits(depositField.value), 10) || 0;
            });
            if (groupSummaryDeposit) groupSummaryDeposit.textContent = formatNumber(sum);
            updateGroupCountText(cards.length);
        }

        function focusFirstField(card) {
            var first = card.querySelector('[data-name="serial_number"]') || card.querySelector('input, select, textarea');
            if (first) {
                try { first.focus(); } catch (err) { /* ignore */ }
            }
        }

        function scrollCardIntoView(card) {
            if (card && typeof card.scrollIntoView === 'function') {
                try { card.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } catch (err) { /* ignore */ }
            }
        }

        function copyDeviceFieldValues(source, target) {
            source.querySelectorAll('[data-name]').forEach(function (sf) {
                var name = sf.getAttribute('data-name');
                var tf = target.querySelector('[data-name="' + name + '"]');
                if (!tf) return;
                if (sf.type === 'checkbox') tf.checked = sf.checked;
                else tf.value = sf.value;
            });
        }

        function wireDeviceCard(card) {
            card.querySelectorAll('[data-name]').forEach(function (field) {
                field.addEventListener('input', function () {
                    updateDevicePreview(card);
                    if (field.hasAttribute('data-deposit')) updateGroupSummary();
                });
                field.addEventListener('change', function () {
                    updateDevicePreview(card);
                    if (field.hasAttribute('data-deposit')) updateGroupSummary();
                });
            });

            wireGroupSerialLookup(card);

            var toggleBtn = card.querySelector('[data-device-toggle]');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () { toggleCollapse(card); });
            }

            var collapseBtn = card.querySelector('[data-device-collapse]');
            if (collapseBtn) {
                collapseBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    toggleCollapse(card);
                });
            }

            var removeBtn = card.querySelector('[data-device-remove]');
            if (removeBtn) {
                removeBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (groupDeviceList.querySelectorAll('[data-device-card]').length <= 1) {
                        window.alert('حداقل یک قبض باید در لیست باقی بماند.');
                        return;
                    }
                    card.remove();
                    reindexDeviceCards();
                    updateGroupSummary();
                    // Re-check remaining cards for intra-batch serial clashes.
                    groupDeviceList.querySelectorAll('[data-device-card]').forEach(function (other) {
                        doGroupSerialLookup(other);
                    });
                });
            }

            var doneBtn = card.querySelector('[data-device-done]');
            if (doneBtn) {
                doneBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    convertAsciiFieldsIn(card);
                    updateDevicePreview(card);
                    card.classList.add('is-collapsed');
                    card.classList.remove('is-active');
                });
            }

            updateDevicePreview(card);
            wireNoteMenus(card);
        }

        function clearGroupSerialBanner(card) {
            var banner = card.querySelector('[data-serial-banner]');
            if (!banner) return;
            banner.classList.add('hidden');
            banner.textContent = '';
            banner.className = 'alert alert-error hidden';
            card.removeAttribute('data-serial-blocked');
        }

        function setGroupSerialBanner(card, msg, blocked) {
            var banner = card.querySelector('[data-serial-banner]');
            if (!banner) return;
            banner.textContent = msg || '';
            banner.classList.toggle('hidden', !msg);
            banner.className = 'alert ' + (blocked ? 'alert-error' : 'alert-success');
            banner.style.marginTop = '6px';
            banner.style.marginBottom = '0';
            if (blocked) card.setAttribute('data-serial-blocked', '1');
            else card.removeAttribute('data-serial-blocked');
        }

        function findIntraBatchSerialClash(card, serial) {
            if (!serial) return null;
            var clash = null;
            var cards = groupDeviceList.querySelectorAll('[data-device-card]');
            cards.forEach(function (other, index) {
                if (other === card || clash) return;
                var otherSerial = (fieldValue(other, 'serial_number') || '').trim().toUpperCase();
                if (otherSerial && otherSerial === serial.toUpperCase()) {
                    clash = index + 1;
                }
            });
            return clash;
        }

        function doGroupSerialLookup(card) {
            if (!card) return Promise.resolve(false);
            var input = card.querySelector('[data-name="serial_number"]');
            if (!input) return Promise.resolve(false);
            var serial = (input.value || '').trim();
            if (serial.length < 3) {
                clearGroupSerialBanner(card);
                return Promise.resolve(false);
            }

            var clash = findIntraBatchSerialClash(card, serial);
            if (clash) {
                setGroupSerialBanner(
                    card,
                    'سریال تکراری در همین پذیرش گروهی است (قبض ' + toPersianDigits(clash) + '). هر سریال فقط یک قبض می‌تواند داشته باشد.',
                    true
                );
                return Promise.resolve(true);
            }

            if (!lookupSerialUrl) {
                clearGroupSerialBanner(card);
                return Promise.resolve(false);
            }

            var seq = (parseInt(card.getAttribute('data-serial-seq') || '0', 10) || 0) + 1;
            card.setAttribute('data-serial-seq', String(seq));

            return fetch(lookupSerialUrl + '?serial=' + encodeURIComponent(serial), {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (String(seq) !== card.getAttribute('data-serial-seq')) return card.getAttribute('data-serial-blocked') === '1';
                    var liveClash = findIntraBatchSerialClash(card, (input.value || '').trim());
                    if (liveClash) {
                        setGroupSerialBanner(
                            card,
                            'سریال تکراری در همین پذیرش گروهی است (قبض ' + toPersianDigits(liveClash) + '). هر سریال فقط یک قبض می‌تواند داشته باشد.',
                            true
                        );
                        return true;
                    }
                    if (!data || !data.found) {
                        clearGroupSerialBanner(card);
                        return false;
                    }
                    var msg = data.block_reason || 'سریال قبلاً ثبت شده است.';
                    if (data.suggest && data.suggest.ticket_no) {
                        msg += ' قبض قبلی: ' + data.suggest.ticket_no;
                        if (data.suggest.status_label) msg += ' (' + data.suggest.status_label + ')';
                        if (data.suggest.customer_name) msg += ' — ' + data.suggest.customer_name;
                    }
                    if (data.can_reuse) {
                        msg += ' — می‌توانید پذیرش جدید ثبت کنید (قبض قبلی تحویل شده).';
                    }
                    setGroupSerialBanner(card, msg, !!data.blocked);
                    return !!data.blocked;
                })
                .catch(function () {
                    return card.getAttribute('data-serial-blocked') === '1';
                });
        }

        function wireGroupSerialLookup(card) {
            var input = card.querySelector('[data-name="serial_number"]');
            if (!input) return;
            var timer = null;
            input.addEventListener('input', function () {
                if (timer) clearTimeout(timer);
                timer = setTimeout(function () { doGroupSerialLookup(card); }, 450);
            });
            input.addEventListener('blur', function () {
                if (timer) clearTimeout(timer);
                doGroupSerialLookup(card);
            });
        }

        function addDeviceCard(options) {
            if (!groupDeviceTemplate || !groupDeviceList) return null;
            options = options || {};

            var frag = groupDeviceTemplate.content.cloneNode(true);
            var card = frag.querySelector('[data-device-card]');
            if (!card) return null;

            if (options.copyFrom) {
                copyDeviceFieldValues(options.copyFrom, card);
            }

            groupDeviceList.appendChild(card);
            wireDeviceCard(card);
            collapseAllExcept(card);
            reindexDeviceCards();
            updateGroupSummary();

            if (options.focus !== false) {
                focusFirstField(card);
                scrollCardIntoView(card);
            }

            return card;
        }

        function lastDeviceCard() {
            var cards = groupDeviceList.querySelectorAll('[data-device-card]');
            return cards.length ? cards[cards.length - 1] : null;
        }

        function ensureMinimumDeviceCards() {
            if (!groupDeviceList) return;
            if (groupDeviceList.querySelectorAll('[data-device-card]').length === 0) {
                addDeviceCard({ focus: false });
            } else {
                reindexDeviceCards();
                updateGroupSummary();
            }
        }

        /* ---------- wire static controls ---------- */

        if (lookupPhoneInput) {
            lookupPhoneInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    doLookup();
                }
            });
        }

        if (lookupPhoneBtn) {
            lookupPhoneBtn.addEventListener('click', function (e) {
                e.preventDefault();
                doLookup();
            });
        }

        startTabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setStartTab(btn.getAttribute('data-start-tab'));
                setLookupStatus('', '');
            });
        });

        if (lookupNameInput) {
            lookupNameInput.addEventListener('input', function () {
                doNameLookup(false);
            });
            lookupNameInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    doNameLookup(true);
                }
            });
        }

        if (lookupNameBtn) {
            lookupNameBtn.addEventListener('click', function (e) {
                e.preventDefault();
                doNameLookup(true);
            });
        }

        chooseModeBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                chooseMode(btn.getAttribute('data-choose-mode'));
            });
        });

        if (modeModalCancel) {
            modeModalCancel.addEventListener('click', function () {
                goBackToPhone(false);
            });
        }

        if (changePhoneBtn) {
            changePhoneBtn.addEventListener('click', function () {
                goBackToPhone(true);
            });
        }

        if (backToPhoneBtn) {
            backToPhoneBtn.addEventListener('click', function () {
                goBackToPhone(true);
            });
        }

        if (changeModeBtn) {
            changeModeBtn.addEventListener('click', function () {
                openModeModal();
            });
        }

        if (changeModeBtn2) {
            changeModeBtn2.addEventListener('click', function () {
                openModeModal();
            });
        }

        if (saveCustomerBtn) {
            saveCustomerBtn.addEventListener('click', function (e) {
                e.preventDefault();
                saveCustomer();
            });
        }

        if (serialLookupInput) {
            var serialTimer = null;
            serialLookupInput.addEventListener('blur', doSerialLookup);
            serialLookupInput.addEventListener('input', function () {
                clearTimeout(serialTimer);
                serialTimer = setTimeout(doSerialLookup, 450);
            });
        }
        if (serialFillPrevBtn) {
            serialFillPrevBtn.addEventListener('click', function () {
                applySerialSuggest(lastSerialSuggest);
            });
        }

        if (groupAddBtn) {
            groupAddBtn.addEventListener('click', function () {
                addDeviceCard();
            });
        }

        if (groupDupBtn) {
            groupDupBtn.addEventListener('click', function () {
                var last = lastDeviceCard();
                addDeviceCard({ copyFrom: last });
            });
        }

        if (groupExpandLastBtn) {
            groupExpandLastBtn.addEventListener('click', function () {
                var last = lastDeviceCard();
                if (!last) return;
                last.classList.remove('is-collapsed');
                last.classList.add('is-active');
                focusFirstField(last);
                scrollCardIntoView(last);
            });
        }

        if (groupCollapseAllBtn) {
            groupCollapseAllBtn.addEventListener('click', function () {
                groupDeviceList.querySelectorAll('[data-device-card]').forEach(function (card) {
                    card.classList.add('is-collapsed');
                    card.classList.remove('is-active');
                });
            });
        }

        form.querySelectorAll('[data-set-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (formActionInput) formActionInput.value = btn.getAttribute('data-set-action') || 'save_close';
                if (btn.getAttribute('type') === 'button') {
                    triggerFormSubmit(form);
                }
            });
        });

        form.addEventListener('submit', function (e) {
            convertAsciiFieldsIn(form);

            var mode = intakeModeInput ? intakeModeInput.value : 'single';
            if (mode === 'group' && groupDeviceList) {
                var cards = reindexDeviceCards();
                if (cards.length < 2) {
                    e.preventDefault();
                    window.alert((groupHint && groupHint.textContent) || 'حداقل ۲ قبض برای پذیرش گروهی لازم است.');
                    if (groupHint) groupHint.classList.add('is-error');
                    return;
                }

                // Wait for live serial checks (same API as single) before allowing submit.
                if (form.getAttribute('data-serial-checked') === '1') {
                    form.removeAttribute('data-serial-checked');
                    updateGroupSummary();
                    return;
                }
                e.preventDefault();
                Promise.all(Array.prototype.map.call(cards, function (card) {
                    return doGroupSerialLookup(card);
                })).then(function (flags) {
                    var blockedIdx = -1;
                    for (var i = 0; i < flags.length; i++) {
                        if (flags[i] || cards[i].getAttribute('data-serial-blocked') === '1') {
                            blockedIdx = i;
                            break;
                        }
                    }
                    if (blockedIdx >= 0) {
                        var blockedCard = cards[blockedIdx];
                        collapseAllExcept(blockedCard);
                        scrollCardIntoView(blockedCard);
                        var banner = blockedCard.querySelector('[data-serial-banner]');
                        window.alert((banner && banner.textContent) || 'سریال تکراری در قبض گروهی وجود دارد.');
                        var serialInput = blockedCard.querySelector('[data-name="serial_number"]');
                        if (serialInput) {
                            try { serialInput.focus(); serialInput.select(); } catch (err) {}
                        }
                        return;
                    }
                    form.setAttribute('data-serial-checked', '1');
                    triggerFormSubmit(form);
                });
                return;
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (modeModal && !modeModal.classList.contains('hidden')) {
                goBackToPhone(false);
            }
        });

        /* ---------- initial state ---------- */

        wireNoteMenus(form);
        document.addEventListener('click', function (e) {
            var menu = document.getElementById('note-ctx-menu');
            if (menu && !menu.contains(e.target)) closeNoteContextMenu();
        });

        if (skipPhone) {
            showBodyStep();
            chooseMode(oldMode);
        } else {
            showPhoneStep();
            if (lookupPhoneInput) lookupPhoneInput.focus();
        }
    }

    /** Note menus + right-click pick on edit/show pages (outside reception wizard). */
    function initNoteMenus(root) {
        root = root || document;
        function applyValue(field, value) {
            if (!field || !value) return;
            value = String(value).trim();
            if (!value) return;
            var current = (field.value || '').trim();
            if (!current || current === 'ندارد') field.value = value;
            else if (current.indexOf(value) === -1) field.value = current + '، ' + value;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }
        function closeMenu() {
            var existing = document.getElementById('note-ctx-menu');
            if (existing) existing.remove();
        }
        root.querySelectorAll('.note-menu[data-note-target]').forEach(function (select) {
            if (select.getAttribute('data-note-wired') === '1') return;
            select.setAttribute('data-note-wired', '1');
            select.addEventListener('change', function () {
                var value = (select.value || '').trim();
                if (!value) return;
                var targetName = select.getAttribute('data-note-target');
                var scope = select.closest('form') || document;
                var field = scope.querySelector('[data-note-ctx="' + targetName + '"]')
                    || scope.querySelector('[name="' + targetName + '"]');
                if (!field) return;
                applyValue(field, value);
                select.value = '';
            });
            var targetName = select.getAttribute('data-note-target');
            var scope = select.closest('form') || document;
            var field = scope.querySelector('[data-note-ctx="' + targetName + '"]')
                || scope.querySelector('[name="' + targetName + '"]');
            if (!field || field.getAttribute('data-note-ctx-wired') === '1') return;
            field.setAttribute('data-note-ctx-wired', '1');
            field.addEventListener('contextmenu', function (e) {
                var options = Array.prototype.slice.call(select.options || [])
                    .map(function (opt) { return (opt.value || '').trim(); })
                    .filter(Boolean);
                if (!options.length) return;
                e.preventDefault();
                closeMenu();
                var menu = document.createElement('div');
                menu.id = 'note-ctx-menu';
                menu.className = 'note-ctx-menu';
                options.forEach(function (opt) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'note-ctx-item';
                    btn.textContent = opt;
                    btn.addEventListener('click', function () {
                        applyValue(field, opt);
                        closeMenu();
                    });
                    menu.appendChild(btn);
                });
                document.body.appendChild(menu);
                var x = e.clientX;
                var y = e.clientY;
                var rect = menu.getBoundingClientRect();
                if (x + rect.width > window.innerWidth - 8) x = Math.max(8, window.innerWidth - rect.width - 8);
                if (y + rect.height > window.innerHeight - 8) y = Math.max(8, window.innerHeight - rect.height - 8);
                menu.style.left = x + 'px';
                menu.style.top = y + 'px';
            });
        });
        if (!document.documentElement.getAttribute('data-note-ctx-close')) {
            document.documentElement.setAttribute('data-note-ctx-close', '1');
            document.addEventListener('click', function (e) {
                var menu = document.getElementById('note-ctx-menu');
                if (menu && !menu.contains(e.target)) closeMenu();
            });
        }
    }

    /* =========================================================
     * Staff UI mode (mobile / desktop) + drawer menus
     * ========================================================= */

    var UI_MODE_KEY = 'staff_ui_mode';

    function detectStaffUiMode() {
        try {
            var forced = localStorage.getItem(UI_MODE_KEY);
            var narrow = false;
            var coarse = false;
            try {
                narrow = window.matchMedia('(max-width: 900px)').matches || window.innerWidth <= 900;
                coarse = window.matchMedia('(pointer: coarse)').matches && window.innerWidth < 1100;
            } catch (e2) {
                narrow = window.innerWidth <= 900;
            }
            // موبایل واقعی: همیشه شل موبایل، حتی اگر قبلاً «کامپیوتر» ذخیره شده باشد.
            if (narrow) {
                if (forced === 'desktop') {
                    try { localStorage.setItem(UI_MODE_KEY, 'auto'); } catch (e3) {}
                }
                return 'mobile';
            }
            if (forced === 'mobile' || forced === 'desktop') {
                return forced;
            }
            return (narrow || coarse) ? 'mobile' : 'desktop';
        } catch (e) {
            return (window.innerWidth <= 900) ? 'mobile' : 'desktop';
        }
    }

    function applyStaffUiMode(mode) {
        var resolved = mode || detectStaffUiMode();
        document.documentElement.setAttribute('data-ui-mode', resolved);
        var label = document.querySelector('[data-ui-mode-label]');
        if (label) {
            var forced = null;
            try { forced = localStorage.getItem(UI_MODE_KEY); } catch (e) {}
            if (forced === 'mobile') label.textContent = 'موبایل (ثابت)';
            else if (forced === 'desktop') label.textContent = 'کامپیوتر (ثابت)';
            else label.textContent = resolved === 'mobile' ? 'خودکار · موبایل' : 'خودکار · کامپیوتر';
        }
        document.querySelectorAll('[data-ui-mode-set]').forEach(function (btn) {
            var v = btn.getAttribute('data-ui-mode-set');
            var forced = null;
            try { forced = localStorage.getItem(UI_MODE_KEY); } catch (e) {}
            var on = (v === 'auto' && !forced) || (forced && forced === v);
            btn.classList.toggle('btn-secondary', on);
            btn.classList.toggle('btn-ghost', !on);
        });
        return resolved;
    }

    function setStaffUiMode(value) {
        try {
            if (value === 'auto' || !value) localStorage.removeItem(UI_MODE_KEY);
            else localStorage.setItem(UI_MODE_KEY, value);
        } catch (e) {}
        applyStaffUiMode();
    }

    function openStaffDrawer() {
        var drawer = document.getElementById('staff-drawer');
        if (!drawer) return;
        drawer.hidden = false;
        document.body.style.overflow = 'hidden';
        var search = document.getElementById('staff-drawer-search');
        if (search) setTimeout(function () { search.focus(); }, 50);
    }

    function closeStaffDrawer() {
        var drawer = document.getElementById('staff-drawer');
        if (!drawer) return;
        drawer.hidden = true;
        document.body.style.overflow = '';
    }

    function initStaffShell() {
        applyStaffUiMode();

        document.querySelectorAll('[data-staff-drawer-open]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openStaffDrawer();
            });
        });
        document.querySelectorAll('[data-staff-drawer-close]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                closeStaffDrawer();
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeStaffDrawer();
        });

        document.querySelectorAll('[data-ui-mode-set]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setStaffUiMode(btn.getAttribute('data-ui-mode-set'));
            });
        });
        document.querySelectorAll('[data-ui-mode-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var current = document.documentElement.getAttribute('data-ui-mode') || 'desktop';
                setStaffUiMode(current === 'mobile' ? 'desktop' : 'mobile');
            });
        });

        var search = document.getElementById('staff-drawer-search');
        if (search) {
            search.addEventListener('input', function () {
                var q = (search.value || '').trim().toLowerCase();
                document.querySelectorAll('[data-drawer-group]').forEach(function (g) {
                    var groupLabel = (g.getAttribute('data-menu-label') || '').toLowerCase();
                    var items = g.querySelectorAll('[data-menu-label]');
                    var any = false;
                    items.forEach(function (item) {
                        var label = (item.getAttribute('data-menu-label') || '').toLowerCase();
                        var show = !q || label.indexOf(q) !== -1 || groupLabel.indexOf(q) !== -1;
                        item.style.display = show ? '' : 'none';
                        if (show) any = true;
                    });
                    g.style.display = (!q || any || groupLabel.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }

        var mq = null;
        try {
            mq = window.matchMedia('(max-width: 900px)');
            var onChange = function () {
                var forced = null;
                try { forced = localStorage.getItem(UI_MODE_KEY); } catch (e) {}
                if (!forced) applyStaffUiMode();
            };
            if (mq.addEventListener) mq.addEventListener('change', onChange);
            else if (mq.addListener) mq.addListener(onChange);
        } catch (e) {}
    }

    function initStaffLoginDeviceHint() {
        var otpTab = document.querySelector('[data-login-tab="otp"]');
        var passTab = document.querySelector('[data-login-tab="pass"]');
        if (!otpTab || !passTab) return;
        if (document.getElementById('tab-otp') && detectStaffUiMode() === 'mobile') {
            // Prefer SMS login on phones unless password pane already active from server
            if (!document.querySelector('.login-tab.is-active[data-login-tab="pass"]') || document.querySelector('.login-tab.is-active[data-login-tab="otp"]')) {
                otpTab.click();
            }
        }
        var hint = document.querySelector('[data-device-login-hint]');
        if (hint) {
            hint.textContent = detectStaffUiMode() === 'mobile'
                ? 'دستگاه شما موبایل تشخیص داده شد — ورود پیامکی پیشنهاد می‌شود.'
                : 'دستگاه شما کامپیوتر تشخیص داده شد — می‌توانید با رمز یا SMS وارد شوید.';
        }
    }

    function initLookupEditors() {
        function setToggle(root, on) {
            if (!root) return;
            var cb = root.querySelector('[data-toggle-input]');
            var btn = root.querySelector('[data-toggle-btn]');
            var state = root.querySelector('.toggle-state');
            if (cb) cb.checked = !!on;
            if (btn) {
                btn.classList.toggle('is-on', !!on);
                btn.classList.toggle('is-off', !on);
                btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                if (state) state.textContent = on
                    ? (btn.getAttribute('data-on-text') || 'روشن')
                    : (btn.getAttribute('data-off-text') || 'خاموش');
            }
        }

        document.querySelectorAll('[data-lookup-editor]').forEach(function (editor) {
            var base = editor.getAttribute('data-base-url') || '';
            var select = editor.querySelector('.lookup-select');
            var createForm = editor.querySelector('.lookup-form-create');
            var editForm = editor.querySelector('.lookup-form-edit');
            var createActions = editor.querySelector('.lookup-actions-create');
            if (!select || !createForm || !editForm) return;

            function modeCreate() {
                createForm.classList.remove('hidden');
                editForm.classList.add('hidden');
                if (createActions) createActions.classList.remove('hidden');
                editForm.setAttribute('action', '#');
                createForm.querySelectorAll('.lookup-name').forEach(function (i) { i.value = ''; });
                createForm.querySelectorAll('.lookup-sort').forEach(function (i) { i.value = '0'; });
                setToggle(createForm.querySelector('[data-toggle-field]'), true);
            }

            function modeEdit(opt) {
                createForm.classList.add('hidden');
                editForm.classList.remove('hidden');
                if (createActions) createActions.classList.add('hidden');
                var id = opt.value;
                editForm.setAttribute('action', base.replace(/\/$/, '') + '/' + id);
                editForm.querySelectorAll('.lookup-name').forEach(function (i) {
                    i.value = opt.getAttribute('data-name') || '';
                });
                editForm.querySelectorAll('.lookup-sort').forEach(function (i) {
                    i.value = opt.getAttribute('data-sort') || '0';
                });
                setToggle(editForm.querySelector('[data-toggle-field]'), opt.getAttribute('data-active') === '1');
                var method = editForm.querySelector('.lookup-method');
                if (method) method.value = 'PUT';
            }

            select.addEventListener('change', function () {
                var opt = select.options[select.selectedIndex];
                if (!opt || !opt.value) modeCreate();
                else modeEdit(opt);
            });

            var delBtn = editForm.querySelector('.lookup-delete-btn');
            if (delBtn) {
                delBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var msg = delBtn.getAttribute('data-confirm') || 'حذف شود؟';
                    if (!window.confirm(msg)) return;
                    var method = editForm.querySelector('.lookup-method');
                    if (method) method.value = 'DELETE';
                    editForm.submit();
                });
            }
            var saveBtn = editForm.querySelector('.lookup-save-btn');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    var method = editForm.querySelector('.lookup-method');
                    if (method) method.value = 'PUT';
                });
            }
        });

        document.querySelectorAll('[data-simple-editor]').forEach(function (editor) {
            var base = editor.getAttribute('data-base-url') || '';
            var select = editor.querySelector('.simple-select');
            var createForm = editor.querySelector('.simple-form-create');
            var editForm = editor.querySelector('.simple-form-edit');
            var createActions = editor.querySelector('.simple-actions-create');
            if (!select || !createForm || !editForm) return;

            function modeCreate() {
                createForm.classList.remove('hidden');
                editForm.classList.add('hidden');
                if (createActions) createActions.classList.remove('hidden');
                editForm.setAttribute('action', '#');
                createForm.querySelectorAll('.simple-name').forEach(function (i) { i.value = ''; });
                setToggle(createForm.querySelector('[data-toggle-field]'), true);
            }

            function modeEdit(opt) {
                createForm.classList.add('hidden');
                editForm.classList.remove('hidden');
                if (createActions) createActions.classList.add('hidden');
                editForm.setAttribute('action', base.replace(/\/$/, '') + '/' + opt.value);
                editForm.querySelectorAll('.simple-name').forEach(function (i) {
                    i.value = opt.getAttribute('data-name') || '';
                });
                setToggle(editForm.querySelector('[data-toggle-field]'), opt.getAttribute('data-active') !== '0');
                var method = editForm.querySelector('.simple-method');
                if (method) method.value = 'PUT';
            }

            select.addEventListener('change', function () {
                var opt = select.options[select.selectedIndex];
                if (!opt || !opt.value) modeCreate();
                else modeEdit(opt);
            });

            var delBtn = editForm.querySelector('.simple-delete-btn');
            if (delBtn) {
                delBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var msg = delBtn.getAttribute('data-confirm') || 'حذف شود؟';
                    if (!window.confirm(msg)) return;
                    var method = editForm.querySelector('.simple-method');
                    if (method) method.value = 'DELETE';
                    editForm.submit();
                });
            }
            var saveBtn = editForm.querySelector('.simple-save-btn');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    var method = editForm.querySelector('.simple-method');
                    if (method) method.value = 'PUT';
                });
            }
        });
    }

    /* =========================================================
     * Boot
     * ========================================================= */

    /* =========================================================
     * Jalali date text inputs (YYYY/MM/DD)
     * ========================================================= */

    function formatJalaliTyped(raw) {
        var digits = convertDigits(raw).replace(/\D+/g, '').slice(0, 8);
        if (digits.length <= 4) return digits;
        if (digits.length <= 6) return digits.slice(0, 4) + '/' + digits.slice(4);
        return digits.slice(0, 4) + '/' + digits.slice(4, 6) + '/' + digits.slice(6);
    }

    function initJalaliDateInputs(root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('input.jalali-date').forEach(function (el) {
            if (el.dataset.jalaliReady === '1') return;
            el.dataset.jalaliReady = '1';
            el.setAttribute('placeholder', el.getAttribute('placeholder') || '1404/05/16');
            el.setAttribute('dir', 'ltr');
            el.style.textAlign = el.style.textAlign || 'left';
            el.addEventListener('input', function () {
                var next = formatJalaliTyped(el.value);
                if (el.value !== next) {
                    var pos = next.length;
                    el.value = next;
                    try { el.setSelectionRange(pos, pos); } catch (e) {}
                }
            });
            el.addEventListener('blur', function () {
                el.value = formatJalaliTyped(el.value);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initToggles();
        initWorkspaceTabs();
        initConfirmButtons();
        initAsciiFields();
        initAppModals();
        initReceptionWizard();
        initNoteMenus(document);
        initStaffShell();
        initStaffLoginDeviceHint();
        initLookupEditors();
        initJalaliDateInputs();
    });

    // Group admission clones device cards dynamically
    document.addEventListener('focusin', function (e) {
        if (e.target && e.target.matches && e.target.matches('input.jalali-date')) {
            initJalaliDateInputs(e.target.parentNode || document);
        }
    });
})();
