/**
 * Shared field validation bubbles for all subsystems.
 */
(function (global) {
    const PHONE_MIN_DIGITS = 7;
    const PHONE_MAX_DIGITS = 11;

    const SPECIAL_FIELD_SELECTOR = '[name="phone_number"], [name="emergency_contact"], [name="license_expiry_date"]';

    function labelIndicatesRequired(label) {
        if (!label) return false;
        if (label.querySelector('.required')) return true;
        return (label.textContent || '').includes('*');
    }

    function getValidationGroup(el) {
        if (!el) return null;
        if (el.classList?.contains('form-group') || el.classList?.contains('field-validate-group')) {
            return el;
        }

        const closest = el.closest('.form-group, .field-validate-group');
        if (closest) return closest;

        const parent = el.parentElement;
        if (parent?.querySelector('label') && parent.querySelector('input, select, textarea')) {
            parent.classList.add('field-validate-group');
            return parent;
        }

        return null;
    }

    function getFieldLabel(input) {
        const group = getValidationGroup(input);
        if (!group) return 'This field';
        const label = group.querySelector('label');
        if (!label) return 'This field';
        return label.textContent.replace(/\*/g, '').trim();
    }

    function getFieldAnchor(group) {
        if (!group) return null;
        return group.querySelector(
            '.date-input-wrapper, .file-upload-area, input:not([type="hidden"]), select, textarea'
        );
    }

    function getFieldBubbleHost(group) {
        const anchor = getFieldAnchor(group);
        if (!anchor) return group;

        if (anchor.parentElement?.classList.contains('field-bubble-host')) {
            return anchor.parentElement;
        }

        if (anchor.classList.contains('date-input-wrapper') || anchor.classList.contains('file-upload-area')) {
            anchor.classList.add('field-bubble-host');
            return anchor;
        }

        const host = document.createElement('div');
        host.className = 'field-bubble-host';
        anchor.parentNode.insertBefore(host, anchor);
        host.appendChild(anchor);
        return host;
    }

    function getOrCreateFieldBubble(group) {
        if (!group) return null;
        const host = getFieldBubbleHost(group);
        let bubble = host.querySelector(':scope > .field-bubble');
        if (!bubble) {
            bubble = document.createElement('div');
            bubble.className = 'field-bubble';
            bubble.setAttribute('role', 'alert');
            bubble.innerHTML = '<span class="field-bubble-text"></span>';
            host.insertBefore(bubble, host.firstChild);
        }
        return bubble;
    }

    function showFieldNotice(inputOrGroup, message) {
        const group = getValidationGroup(inputOrGroup);
        const bubble = getOrCreateFieldBubble(group);
        const text = bubble?.querySelector('.field-bubble-text');
        if (text) text.textContent = message;
        bubble?.classList.add('show');
    }

    function clearFieldNotice(inputOrGroup) {
        const group = getValidationGroup(inputOrGroup);
        if (!group) return;
        const host = group.querySelector('.field-bubble-host') || group;
        const bubble = host.querySelector(':scope > .field-bubble');
        if (!bubble) return;
        bubble.classList.remove('show');
        const text = bubble.querySelector('.field-bubble-text');
        if (text) text.textContent = '';
    }

    function clearFormFieldNotices(form) {
        form?.querySelectorAll('.form-group, .field-validate-group').forEach(clearFieldNotice);
    }

    function focusFirstBubble(form) {
        const firstNotice = form?.querySelector('.field-bubble.show');
        const group = firstNotice?.closest('.form-group, .field-validate-group');
        const focusTarget = group?.querySelector(
            'input:not([type="hidden"]), select, textarea'
        );
        firstNotice?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        focusTarget?.focus();
    }

    function prepareForm(form) {
        if (!form || form.dataset.bubblesReady) return;
        form.setAttribute('novalidate', 'novalidate');
        form.dataset.bubblesReady = '1';
    }

    /** Strip formatting only — never truncate; length is validated separately. */
    function stripPhoneDigits(value) {
        return (value || '').replace(/\D/g, '');
    }

    function validateRequiredField(input, { required = false } = {}) {
        if (!input) return true;

        const label = getFieldLabel(input);
        const value = (input.value ?? '').toString().trim();

        if (!value.length) {
            if (required) {
                showFieldNotice(input, `${label} is required.`);
                return false;
            }
            clearFieldNotice(input);
            return true;
        }

        clearFieldNotice(input);
        return true;
    }

    function validatePhoneField(input, { required = false } = {}) {
        if (!input) return true;

        const digits = stripPhoneDigits(input.value);
        // Only remove formatting characters — do not truncate digit count.
        if (input.value !== digits) {
            input.value = digits;
        }

        if (!digits.length) {
            if (required) {
                showFieldNotice(input, `${getFieldLabel(input)} is required.`);
                return false;
            }
            clearFieldNotice(input);
            return true;
        }

        if (digits.length < PHONE_MIN_DIGITS || digits.length > PHONE_MAX_DIGITS) {
            const message = input.name === 'emergency_contact'
                ? 'Emergency contact must be 7 to 11 digits.'
                : 'Phone number must be 7 to 11 digits.';
            showFieldNotice(input, message);
            return false;
        }

        clearFieldNotice(input);
        return true;
    }

    function isValidCalendarDate(month, day, year) {
        const date = new Date(year, month - 1, day);
        return date.getFullYear() === year
            && date.getMonth() === month - 1
            && date.getDate() === day;
    }

    function validateDateTextField(input, { required = false, immediate = false } = {}) {
        if (!input) return true;

        const label = getFieldLabel(input);
        const value = input.value.trim();

        if (!value.length) {
            if (required) {
                showFieldNotice(input, `${label} is required.`);
                return false;
            }
            clearFieldNotice(input);
            return true;
        }

        const complete = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value);
        if (!complete) {
            if (immediate || value.length >= 8) {
                showFieldNotice(input, 'Enter a valid date (mm/dd/yyyy).');
                return false;
            }
            if (value.length > 0) {
                showFieldNotice(input, 'Enter a complete date (mm/dd/yyyy).');
                return false;
            }
            return true;
        }

        const month = parseInt(complete[1], 10);
        const day   = parseInt(complete[2], 10);
        const year  = parseInt(complete[3], 10);

        if (month < 1 || month > 12 || !isValidCalendarDate(month, day, year)) {
            showFieldNotice(input, 'Enter a valid calendar date.');
            return false;
        }

        clearFieldNotice(input);
        return true;
    }

    function bindFieldValidation(input, validateFn) {
        if (!input || input.dataset.bubbleBound) return;
        input.dataset.bubbleBound = '1';

        const run = () => validateFn(input);
        const runRequired = () => validateFn(input, { required: true });

        if (input.tagName === 'SELECT') {
            input.addEventListener('change', run);
            input.addEventListener('blur', runRequired);
            return;
        }

        input.addEventListener('input', run);
        input.addEventListener('blur', runRequired);
    }

    function registerRequiredField(input) {
        if (!input || input.matches(SPECIAL_FIELD_SELECTOR)) return;
        if (input.dataset.validateRequired || input.dataset.validatePhone || input.dataset.validateDateText) {
            return;
        }

        input.dataset.validateRequired = 'true';
        bindFieldValidation(input, validateRequiredField);
    }

    function setupRequiredBubbles(form) {
        if (!form) return;
        prepareForm(form);

        form.querySelectorAll('[required]').forEach(input => {
            input.removeAttribute('required');
            registerRequiredField(input);
        });

        form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(input => {
            const group = getValidationGroup(input);
            if (!group) return;
            if (!labelIndicatesRequired(group.querySelector('label'))) return;
            registerRequiredField(input);
        });
    }

    function setupPhoneBubbles(form, selector = '[name="phone_number"], [name="emergency_contact"]') {
        if (!form) return;
        prepareForm(form);
        form.querySelectorAll(selector).forEach(input => {
            if (input.dataset.validatePhone) return;
            input.dataset.validatePhone = 'true';
            bindFieldValidation(input, validatePhoneField);
        });
    }

    function formatDateInputEvent(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) value = value.substring(0, 2) + '/' + value.substring(2);
        if (value.length >= 5) value = value.substring(0, 5) + '/' + value.substring(5, 9);
        e.target.value = value;
    }

    function restrictDateInputEvent(e) {
        if (!/[0-9]/.test(String.fromCharCode(e.which))) e.preventDefault();
    }

    function setupDateTextBubbles(form, selector = '[name="license_expiry_date"]') {
        if (!form) return;
        prepareForm(form);
        form.querySelectorAll(selector).forEach(input => {
            if (input.dataset.validateDateText) return;
            input.dataset.validateDateText = 'true';
            input.dataset.dateFormatBound = '1';
            input.addEventListener('keypress', restrictDateInputEvent);
            input.addEventListener('input', (e) => {
                formatDateInputEvent(e);
                validateDateTextField(e.target, { immediate: true });
            });
            input.addEventListener('blur', () => validateDateTextField(input, { required: true }));
        });
    }

    function validateFormBubbles(form) {
        if (!form) return false;

        let valid = true;

        form.querySelectorAll('[data-validate-required]').forEach(input => {
            if (!validateRequiredField(input, { required: true })) valid = false;
        });
        form.querySelectorAll('[data-validate-phone]').forEach(input => {
            if (!validatePhoneField(input, { required: true })) valid = false;
        });
        form.querySelectorAll('[data-validate-date-text]').forEach(input => {
            if (!validateDateTextField(input, { required: true, immediate: true })) valid = false;
        });

        if (!valid) focusFirstBubble(form);
        return valid;
    }

    function setupDriverForm(form) {
        if (!form) return;
        setupRequiredBubbles(form);
        setupPhoneBubbles(form);
        setupDateTextBubbles(form);
    }

    function validateDriverForm(form) {
        return validateFormBubbles(form);
    }

    function displayDateToIso(value) {
        const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec((value || '').trim());
        if (!match) return '';

        const month = parseInt(match[1], 10);
        const day   = parseInt(match[2], 10);
        const year  = parseInt(match[3], 10);
        if (month < 1 || month > 12 || !isValidCalendarDate(month, day, year)) return '';

        return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    }

    function isoDateToDisplay(iso) {
        if (!iso) return '';
        const [year, month, day] = iso.split('-');
        if (!year || !month || !day) return '';
        return `${month}/${day}/${year}`;
    }

    function openDatePicker(textInput, picker) {
        if (!textInput || !picker) return;
        picker.value = displayDateToIso(textInput.value) || '';
        try {
            if (typeof picker.showPicker === 'function') {
                picker.showPicker();
                return;
            }
        } catch (_) { /* fallback */ }
        picker.focus();
        picker.click();
    }

    function setupLicenseExpiryField(textInputId, pickerId, calendarBtnId) {
        const textInput = document.getElementById(textInputId);
        const picker    = document.getElementById(pickerId);
        const calendarBtn = document.getElementById(calendarBtnId);
        if (!textInput || !picker || picker.dataset.calendarBound) return;
        picker.dataset.calendarBound = '1';

        textInput.addEventListener('blur', () => {
            picker.value = displayDateToIso(textInput.value) || '';
        });

        calendarBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openDatePicker(textInput, picker);
        });

        picker.addEventListener('change', () => {
            textInput.value = isoDateToDisplay(picker.value);
            validateDateTextField(textInput, { immediate: true });
        });
    }

    function initValidationForms() {
        document.querySelectorAll('form[data-validate="standard"]').forEach(setupRequiredBubbles);
        document.querySelectorAll('form[data-validate="driver"]').forEach(setupDriverForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initValidationForms);
    } else {
        initValidationForms();
    }

    global.FormValidation = {
        PHONE_MIN_DIGITS,
        PHONE_MAX_DIGITS,
        showFieldNotice,
        clearFieldNotice,
        clearFormFieldNotices,
        focusFirstBubble,
        prepareForm,
        validateRequiredField,
        validatePhoneField,
        validateDateTextField,
        validateDateField: validateDateTextField,
        setupRequiredBubbles,
        setupPhoneBubbles,
        setupDateTextBubbles,
        setupFormBubbles: setupRequiredBubbles,
        validateFormBubbles,
        setupDriverForm,
        validateDriverForm,
        displayDateToIso,
        isoDateToDisplay,
        openDatePicker,
        setupLicenseExpiryField,
        formatDateInputEvent,
        restrictDateInputEvent,
        initValidationForms,
        registerRequiredField,
    };
})(window);
