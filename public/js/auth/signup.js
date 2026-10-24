document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('business-registration-form');
    if (!form) return;

    let summary = document.getElementById('signup-error-summary');
    if (!summary) {
        summary = document.createElement('div');
        summary.id = 'signup-error-summary';
        summary.className = 'error-summary';
        summary.hidden = true;
        summary.tabIndex = -1;
        summary.setAttribute('role', 'alert');
        form.parentElement.insertBefore(summary, form);
    }

    const controls = Array.from(form.querySelectorAll('input:not([type="hidden"]), select, textarea'));
    const getContainer = (input) => input.closest('.form-field, .upload-field, .terms-field') || input.parentElement;

    const syncDescription = (input) => {
        const container = getContainer(input);
        if (!container) return;
        const existingError = container.querySelector('.field-error');
        if (existingError) {
            if (!existingError.id) existingError.id = `${input.id}-error`;
            input.setAttribute('aria-describedby', existingError.id);
        } else {
            input.removeAttribute('aria-describedby');
        }
    };

    const setInlineError = (input, message) => {
        const container = getContainer(input);
        if (!container) return;
        const messageHost = input.type === 'file' ? container.querySelector('div') : container;
        let error = messageHost.querySelector('.client-field-error');

        if (message) {
            if (!error) {
                error = document.createElement('small');
                error.className = 'field-error client-field-error';
                error.id = `${input.id}-client-error`;
                messageHost.appendChild(error);
            }
            error.textContent = message;
            input.setAttribute('aria-describedby', error.id);
            input.setAttribute('aria-invalid', 'true');
            container.dataset.invalid = 'true';
            return;
        }

        error?.remove();
        const serverError = messageHost.querySelector('.field-error:not(.client-field-error)');
        if (serverError) {
            serverError.remove();
        }
        input.setAttribute('aria-invalid', 'false');
        delete container.dataset.invalid;
        syncDescription(input);
    };

    const setCustomValidation = () => {
        const password = form.querySelector('#password');
        const confirmation = form.querySelector('#password_confirmation');
        const certificate = form.querySelector('#ssm_certificate');
        const profile = form.querySelector('#ssm_profile');

        if (password) {
            password.setCustomValidity('');
            if (password.value && password.value.length < 8) {
                password.setCustomValidity('Use at least 8 characters.');
            } else if (password.value && !/\d/.test(password.value)) {
                password.setCustomValidity('Include at least one number in your password.');
            }
        }
        if (confirmation) {
            confirmation.setCustomValidity(
                confirmation.value && password?.value !== confirmation.value
                    ? 'The passwords do not match.'
                    : '',
            );
        }

        [certificate, profile].forEach((input) => {
            if (!input) return;
            input.setCustomValidity('');
            const file = input.files?.[0];
            if (!file) return;
            if (file.size > 10 * 1024 * 1024) {
                input.setCustomValidity('Choose a file smaller than 10 MB.');
                return;
            }
            if (!/\.(pdf|jpe?g|png)$/i.test(file.name)) {
                input.setCustomValidity('Choose a PDF, JPG, or PNG file.');
            }
        });
    };

    const collectInvalid = () => {
        setCustomValidation();
        return controls.filter((input) => !input.checkValidity());
    };

    const updateSummary = (invalidControls) => {
        if (!invalidControls.length) {
            summary.hidden = true;
            summary.replaceChildren();
            return;
        }

        const heading = document.createElement('strong');
        heading.textContent = `Please fix ${invalidControls.length} ${invalidControls.length === 1 ? 'item' : 'items'} before submitting:`;
        const list = document.createElement('ul');

        invalidControls.forEach((input) => {
            const item = document.createElement('li');
            const link = document.createElement('a');
            const label = form.querySelector(`label[for="${CSS.escape(input.id)}"]`);
            link.href = `#${input.id}`;
            link.textContent = label?.textContent?.replace(/\s*\*\s*$/, '') || input.name;
            item.append(link, document.createTextNode(`: ${input.validationMessage}`));
            list.appendChild(item);
        });

        summary.replaceChildren(heading, list);
        summary.hidden = false;
    };

    const refreshControl = (input) => {
        setCustomValidation();
        const isInvalid = !input.checkValidity();
        if (form.dataset.attempted === 'true') {
            setInlineError(input, isInvalid ? input.validationMessage : '');
            updateSummary(collectInvalid());
        }
    };

    form.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            button.textContent = showPassword ? 'Hide' : 'Show';
            button.setAttribute('aria-pressed', String(showPassword));
            button.setAttribute('aria-label', `${showPassword ? 'Hide' : 'Show'} ${input.id === 'password' ? 'password' : 'password confirmation'}`);
        });
    });

    controls.forEach((input) => {
        syncDescription(input);
        input.addEventListener('input', () => refreshControl(input));
        input.addEventListener('change', () => refreshControl(input));
    });

    form.querySelectorAll('input[type="file"]').forEach((input) => {
        input.addEventListener('change', () => {
            const output = form.querySelector(`[data-file-name="${input.id}"]`);
            const file = input.files?.[0];
            if (output) {
                output.textContent = file
                    ? `${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`
                    : 'Choose a PDF, JPG or PNG file (up to 10 MB).';
            }
            refreshControl(input);
        });
    });

    if (!summary.hidden) {
        window.requestAnimationFrame(() => summary.focus({ preventScroll: true }));
    }

    form.addEventListener('submit', (event) => {
        form.dataset.attempted = 'true';
        const invalidControls = collectInvalid();
        invalidControls.forEach((input) => setInlineError(input, input.validationMessage));
        updateSummary(invalidControls);

        if (invalidControls.length) {
            event.preventDefault();
            summary.focus();
            summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
            invalidControls[0].reportValidity();
            return;
        }

        const button = form.querySelector('[data-submit-button]');
        const label = button?.querySelector('[data-submit-label]');
        if (!button || !label) return;

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        label.textContent = 'Submitting application...';
    });
});