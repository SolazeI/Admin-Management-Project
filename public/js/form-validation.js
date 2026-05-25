/**
 * Shared field validation for all subsystems.
 * Speech-bubble (field-bubble) UI removed; validation logic retained.
 * All FV public-API stubs that referenced bubbles are removed.
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

    function prepareForm(form) {
        if (!form || form.dataset.fvReady) return;
        form.setAttribute('novalidate', 'novalidate');
        form.dataset.fvReady = '1';
    }

    /** Strip formatting only — never truncate; length is validated separately. */
    function stripPhoneDigits(value) {
        return (value || '').replace(/\D/g, '');
    }

    // ── Mark / unmark invalid fields ──────────────────────────────────
    function markInvalid(input) {
        input?.setAttribute('data-fv-invalid', '1');
        input?.classList.add('field-invalid');
    }
    function markValid(input) {
        input?.removeAttribute('data-fv-invalid');
        input?.classList.remove('field-invalid');
    }

    function focusFirstInvalid(form) {
        const invalid = form?.querySelector('[data-fv-invalid]');
        invalid?.focus();
    }

    function validateRequiredField(input, { required = false } = {}) {
        if (!input) return true;
        const value = (input.value ?? '').toString().trim();
        if (!value.length) {
            if (required) { markInvalid(input); return false; }
            markValid(input);
            return true;
        }
        markValid(input);
        return true;
    }

    function validatePhoneField(input, { required = false } = {}) {
        if (!input) return true;
        const digits = stripPhoneDigits(input.value);
        if (input.value !== digits) input.value = digits;

        if (!digits.length) {
            if (required) { markInvalid(input); return false; }
            markValid(input);
            return true;
        }
        if (digits.length < PHONE_MIN_DIGITS || digits.length > PHONE_MAX_DIGITS) {
            markInvalid(input);
            return false;
        }
        markValid(input);
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
        const value = input.value.trim();

        if (!value.length) {
            if (required) { markInvalid(input); return false; }
            markValid(input);
            return true;
        }

        const complete = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value);
        if (!complete) {
            if (immediate || value.length >= 8) { markInvalid(input); return false; }
            if (value.length > 0) { markInvalid(input); return false; }
            return true;
        }

        const month = parseInt(complete[1], 10);
        const day   = parseInt(complete[2], 10);
        const year  = parseInt(complete[3], 10);
        if (month < 1 || month > 12 || !isValidCalendarDate(month, day, year)) {
            markInvalid(input);
            return false;
        }

        markValid(input);
        return true;
    }

    function bindFieldValidation(input, validateFn) {
        if (!input || input.dataset.fvBound) return;
        input.dataset.fvBound = '1';

        const run         = () => validateFn(input);
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
        if (input.dataset.validateRequired || input.dataset.validatePhone || input.dataset.validateDateText) return;
        input.dataset.validateRequired = 'true';
        bindFieldValidation(input, validateRequiredField);
    }

    function setupRequiredFields(form) {
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

    function setupPhoneFields(form, selector = '[name="phone_number"], [name="emergency_contact"]') {
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

    function setupDateTextFields(form, selector = '[name="license_expiry_date"]') {
        if (!form) return;
        prepareForm(form);
        form.querySelectorAll(selector).forEach(input => {
            if (input.dataset.validateDateText) return;
            input.dataset.validateDateText = 'true';
            input.dataset.dateFormatBound  = '1';
            input.addEventListener('keypress', restrictDateInputEvent);
            input.addEventListener('input', (e) => {
                formatDateInputEvent(e);
                validateDateTextField(e.target, { immediate: true });
            });
            input.addEventListener('blur', () => validateDateTextField(input, { required: true }));
        });
    }

    function validateNativeDateField(input, { required = false } = {}) {
        if (!input || input.type !== 'date') return true;
        const value = (input.value ?? '').trim();
        if (!value.length) {
            if (required) { markInvalid(input); return false; }
            markValid(input);
            return true;
        }
        if (!input.validity.valid) { markInvalid(input); return false; }
        markValid(input);
        return true;
    }

    function validateDateTimeLocalField(input, { required = false } = {}) {
        if (!input || input.type !== 'datetime-local') return true;
        const value = (input.value ?? '').trim();
        if (!value.length) {
            if (required) { markInvalid(input); return false; }
            markValid(input);
            return true;
        }
        if (!input.validity.valid || Number.isNaN(new Date(value).getTime())) {
            markInvalid(input);
            return false;
        }
        markValid(input);
        return true;
    }

    function validateOptionalNumberField(input, { required = false } = {}) {
        if (!input) return true;
        const value   = (input.value ?? '').toString().trim();
        if (!value.length) {
            if (required) { markInvalid(input); return false; }
            markValid(input);
            return true;
        }
        const cleaned = value.replace(/[^\d.]/g, '');
        if (cleaned === '' || Number.isNaN(Number(cleaned)) || Number(cleaned) < 0) {
            markInvalid(input);
            return false;
        }
        if (input.value !== cleaned) input.value = cleaned;
        markValid(input);
        return true;
    }

    function validateTripDateOrder(form) {
        if (!form) return true;
        const departure = form.querySelector('[name="departure_time"]');
        const arrival   = form.querySelector('[name="arrival_time"]');
        if (!departure?.value || !arrival?.value) return true;
        if (new Date(arrival.value) < new Date(departure.value)) {
            markInvalid(arrival);
            return false;
        }
        markValid(arrival);
        return true;
    }

    function setupNativeDateFields(form, selector = 'input[type="date"]') {
        if (!form) return;
        prepareForm(form);
        form.querySelectorAll(selector).forEach(input => {
            if (input.dataset.validateNativeDate) return;
            input.dataset.validateNativeDate = 'true';
            bindFieldValidation(input, validateNativeDateField);
        });
    }

    function setupDateTimeLocalFields(form, selector = 'input[type="datetime-local"]') {
        if (!form) return;
        prepareForm(form);
        form.querySelectorAll(selector).forEach(input => {
            if (input.dataset.validateDateTime) return;
            input.dataset.validateDateTime = 'true';
            bindFieldValidation(input, validateDateTimeLocalField);
        });
    }

    function setupOptionalNumberFields(form, selector = '[name="distance_km"], [name="amount"]') {
        if (!form) return;
        prepareForm(form);
        form.querySelectorAll(selector).forEach(input => {
            if (input.dataset.validateOptionalNumber) return;
            input.dataset.validateOptionalNumber = 'true';
            bindFieldValidation(input, validateOptionalNumberField);
        });
    }

    function validateForm(form) {
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
        form.querySelectorAll('[data-validate-native-date]').forEach(input => {
            if (!validateNativeDateField(input, { required: true })) valid = false;
        });
        form.querySelectorAll('[data-validate-date-time]').forEach(input => {
            if (!validateDateTimeLocalField(input, { required: true })) valid = false;
        });
        form.querySelectorAll('[data-validate-optional-number]').forEach(input => {
            if (!validateOptionalNumberField(input, { required: true })) valid = false;
        });
        if (!valid) focusFirstInvalid(form);
        return valid;
    }

    function setupTripForm(form) {
        if (!form) return;
        setupRequiredFields(form);
        setupNativeDateFields(form);
        setupDateTimeLocalFields(form);
        setupOptionalNumberFields(form);
        form.querySelectorAll('[name="departure_time"], [name="arrival_time"]').forEach(input => {
            if (input.dataset.tripOrderBound) return;
            input.dataset.tripOrderBound = '1';
            const checkOrder = () => validateTripDateOrder(form);
            input.addEventListener('change', checkOrder);
            input.addEventListener('blur', checkOrder);
        });
    }

    function validateTripForm(form) {
        if (!form) return false;
        let valid = validateForm(form);
        if (!validateTripDateOrder(form)) valid = false;
        if (!valid) focusFirstInvalid(form);
        return valid;
    }

    function setupDriverForm(form) {
        if (!form) return;
        setupRequiredFields(form);
        setupPhoneFields(form);
        setupDateTextFields(form);
    }

    function validateDriverForm(form) {
        return validateForm(form);
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
            if (typeof picker.showPicker === 'function') { picker.showPicker(); return; }
        } catch (_) { /* fallback */ }
        picker.focus();
        picker.click();
    }

    function setupLicenseExpiryField(textInputId, pickerId, calendarBtnId) {
        const textInput   = document.getElementById(textInputId);
        const picker      = document.getElementById(pickerId);
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
        document.querySelectorAll('form[data-validate="standard"]').forEach(setupRequiredFields);
        document.querySelectorAll('form[data-validate="driver"]').forEach(setupDriverForm);
        document.querySelectorAll('form[data-validate="trip"]').forEach(setupTripForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initValidationForms);
    } else {
        initValidationForms();
    }

    global.FormValidation = {
        PHONE_MIN_DIGITS,
        PHONE_MAX_DIGITS,
        prepareForm,
        validateRequiredField,
        validatePhoneField,
        validateDateTextField,
        validateDateField: validateDateTextField,
        setupRequiredFields,
        setupPhoneFields,
        setupDateTextFields,
        validateForm,
        setupDriverForm,
        validateDriverForm,
        validateNativeDateField,
        validateDateTimeLocalField,
        validateOptionalNumberField,
        validateTripDateOrder,
        setupNativeDateFields,
        setupDateTimeLocalFields,
        setupOptionalNumberFields,
        setupTripForm,
        validateTripForm,
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