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
    const getContainer = (input) => input.closest('.form-field, .upload-field, .profile-upload-row, .terms-field') || input.parentElement;

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
        const messageHost = input.type === 'file' ? (container.querySelector('.profile-upload-meta') || container.querySelector('div') || container) : container;
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
            if (password.value && (password.value.length < 8 || !/\d/.test(password.value))) {
                password.setCustomValidity('At least 8 characters, including a number.');
            }
        }
        if (confirmation) {
            confirmation.setCustomValidity('');
            if (confirmation.value && (confirmation.value.length < 8 || !/\d/.test(confirmation.value))) {
                confirmation.setCustomValidity('At least 8 characters, including a number.');
            } else if (confirmation.value && password?.value && password.value !== confirmation.value) {
                confirmation.setCustomValidity('The passwords do not match.');
            }
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

        const profileImage = form.querySelector('#profile_image');
        if (profileImage) {
            profileImage.setCustomValidity('');
            const imgFile = profileImage.files?.[0];
            if (!imgFile) {
                profileImage.setCustomValidity('Upload your company profile image or business logo.');
            } else if (imgFile.size > 5 * 1024 * 1024) {
                profileImage.setCustomValidity('Choose an image smaller than 5 MB.');
            } else if (!/\.(jpe?g|png|webp)$/i.test(imgFile.name)) {
                profileImage.setCustomValidity('Choose a JPG, PNG, or WEBP image.');
            }
        }
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

    let availabilityDebounce;
    const checkAvailability = async (input) => {
        const field = input.id;
        const val = input.value.trim();
        const alertSlot = document.getElementById(`${field}-availability-alert`);
        if (!alertSlot) return;

        if (!val) {
            alertSlot.replaceChildren();
            return;
        }

        try {
            const res = await fetch(`/register/check-availability?field=${encodeURIComponent(field)}&value=${encodeURIComponent(val)}`);
            if (!res.ok) return;
            const data = await res.json();
            if (!data.available) {
                input.setCustomValidity(data.message);
                const alertEl = document.createElement('div');
                alertEl.className = 'field-inline-alert';
                alertEl.setAttribute('role', 'alert');
                const badge = document.createElement('span');
                badge.className = 'alert-badge';
                badge.textContent = 'Cannot use';
                alertEl.append(badge, document.createTextNode(` ${data.message}`));
                alertSlot.replaceChildren(alertEl);
                input.setAttribute('aria-invalid', 'true');
            } else {
                alertSlot.replaceChildren();
                if (input.validationMessage && (input.validationMessage.includes('already in use') || input.validationMessage.includes('Cannot use'))) {
                    input.setCustomValidity('');
                }
                syncDescription(input);
            }
        } catch (e) {
            // Ignore offline fetch errors
        }
    };

    ['name', 'email'].forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('blur', () => checkAvailability(el));
        el.addEventListener('input', () => {
            clearTimeout(availabilityDebounce);
            availabilityDebounce = setTimeout(() => checkAvailability(el), 450);
        });
    });

    const filePreviewUrls = {};

    window.openDocumentPreview = (inputId) => {
        const input = document.getElementById(inputId);
        const file = input?.files?.[0];
        const url = filePreviewUrls[inputId];
        if (!file || !url) return;

        const modal = document.getElementById('doc-preview-modal');
        const modalTitle = document.getElementById('modal-doc-title');
        const modalBody = document.getElementById('modal-doc-body');
        const modalFileInfo = document.getElementById('modal-file-info');
        const openNewTab = document.getElementById('modal-open-newtab');
        if (!modal || !modalBody) return;

        const isCert = inputId === 'ssm_certificate';
        modalTitle.textContent = isCert ? 'SSM Registration Certificate — Preview' : 'SSM Company Profile — Preview';
        if (modalFileInfo) {
            modalFileInfo.textContent = `${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`;
        }

        modalBody.replaceChildren();
        if (openNewTab) {
            openNewTab.href = url;
            openNewTab.style.display = 'inline-flex';
        }

        const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
        if (isPdf) {
            const iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.className = 'modal-pdf-frame';
            iframe.title = file.name;
            modalBody.appendChild(iframe);
        } else {
            const img = document.createElement('img');
            img.src = url;
            img.className = 'modal-img-preview';
            img.alt = file.name;
            modalBody.appendChild(img);
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closeDocumentPreview = () => {
        const modal = document.getElementById('doc-preview-modal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    };

    form.querySelectorAll('input[type="file"]').forEach((input) => {
        input.addEventListener('change', () => {
            const output = form.querySelector(`[data-file-name="${input.id}"]`);
            const file = input.files?.[0];
            const confirmCard = document.getElementById(`confirm-card-${input.id}`);
            const filenameEl = document.getElementById(`filename-${input.id}`);
            const filesizeEl = document.getElementById(`filesize-${input.id}`);

            if (filePreviewUrls[input.id]) {
                URL.revokeObjectURL(filePreviewUrls[input.id]);
                delete filePreviewUrls[input.id];
            }

            if (file) {
                filePreviewUrls[input.id] = URL.createObjectURL(file);
                if (output) {
                    output.textContent = `${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`;
                }
                if (confirmCard && filenameEl && filesizeEl) {
                    filenameEl.textContent = file.name;
                    filesizeEl.textContent = `${(file.size / (1024 * 1024)).toFixed(2)} MB · ${file.type || 'Document'}`;
                    confirmCard.style.display = 'flex';
                }
            } else {
                if (output) {
                    output.textContent = 'Choose a PDF, JPG or PNG file (up to 10 MB).';
                }
                if (confirmCard) {
                    confirmCard.style.display = 'none';
                }
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