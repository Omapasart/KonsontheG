function initCarousel(root) {
    const slides = [...root.querySelectorAll('[data-slide]')];
    const dots = [...root.querySelectorAll('[data-dot]')];
    if (slides.length === 0) {
        return;
    }

    let index = 0;
    let timer = null;
    const interval = Number(root.dataset.interval || 6500);

    const show = (next) => {
        index = (next + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            const active = i === index;
            slide.classList.toggle('opacity-100', active);
            slide.classList.toggle('opacity-0', !active);
            slide.classList.toggle('pointer-events-none', !active);
            slide.toggleAttribute('data-active', active);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('bg-ktg-lime', i === index);
            dot.classList.toggle('bg-white/30', i !== index);
        });
    };

    const start = () => {
        stop();
        timer = window.setInterval(() => show(index + 1), interval);
    };

    const stop = () => {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    };

    root.querySelector('.carousel-prev')?.addEventListener('click', () => {
        show(index - 1);
        start();
    });
    root.querySelector('.carousel-next')?.addEventListener('click', () => {
        show(index + 1);
        start();
    });
    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            show(Number(dot.dataset.dot));
            start();
        });
    });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    show(0);
    start();
}

function initFilePreviews() {
    document.querySelectorAll('input[type="file"][data-preview-target]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const preview = document.getElementById(input.dataset.previewTarget);
            const filename = document.getElementById(input.dataset.filenameTarget);

            if (filename) {
                filename.textContent = file ? `Selected: ${file.name}` : '';
            }

            if (!preview) {
                return;
            }

            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = () => {
                    preview.src = String(reader.result);
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                preview.src = '';
                preview.classList.add('hidden');
            }
        });
    });
}

function formHasPaymentProof(form) {
    if (form.dataset.hasProof === '1') {
        return true;
    }

    const proof = form.querySelector('#payment_proof');

    return Boolean(proof?.files?.length);
}

function initPrivacyConsent() {
    document.querySelectorAll('[data-submit-form]').forEach((form) => {
        const checkbox = form.querySelector('[data-privacy-checkbox]');
        const button = form.querySelector('[data-submit-button]');
        const error = form.querySelector('[data-privacy-error]');
        const proof = form.querySelector('#payment_proof');

        if (! checkbox || ! button) {
            return;
        }

        const sync = () => {
            const ready = checkbox.checked && formHasPaymentProof(form);
            button.disabled = ! ready;

            if (checkbox.checked && error) {
                error.classList.add('hidden');
            }
        };

        checkbox.addEventListener('change', sync);
        proof?.addEventListener('change', sync);
        sync();

        form.addEventListener('submit', (event) => {
            if (checkbox.checked) {
                return;
            }

            event.preventDefault();
            error?.classList.remove('hidden');
            checkbox.focus();
        });
    });
}

function initSubmitGuard() {
    document.querySelectorAll('[data-submit-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const checkbox = form.querySelector('[data-privacy-checkbox]');

            if (checkbox && ! checkbox.checked) {
                event.preventDefault();
                return;
            }

            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';
            const button = form.querySelector('[data-submit-button]');
            if (button) {
                button.disabled = true;
                button.textContent = 'SUBMITTING...';
            }
        });
    });
}

function initWaitingNotice() {
    const form = document.querySelector('[data-level-form]');
    const notice = document.getElementById('capacity-notice');

    if (! form || ! notice) {
        return;
    }

    const sync = () => {
        const selected = form.querySelector('input[name="entry_level"]:checked:not(:disabled)');
        const state = selected?.dataset.capacity ?? 'open';
        notice.classList.toggle('hidden', state !== 'waiting');
    };

    form.querySelectorAll('input[name="entry_level"]').forEach((input) => {
        input.addEventListener('change', sync);
    });
    sync();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-carousel]').forEach(initCarousel);
    initFilePreviews();
    initPrivacyConsent();
    initSubmitGuard();
    initWaitingNotice();
});
