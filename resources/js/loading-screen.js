function initLoadingScreen() {
    const overlay = document.getElementById('loading-screen');

    if (! overlay) {
        return;
    }

    const label = overlay.querySelector('[data-loading-label]');
    let downloadTimer = null;

    const show = (message) => {
        if (label) {
            label.textContent = message || 'Processing your request';
        }

        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    const hide = () => {
        if (downloadTimer) {
            window.clearTimeout(downloadTimer);
            downloadTimer = null;
        }

        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    const isModifiedClick = (event) => event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;

    const isIgnoredHref = (href) => {
        const value = (href || '').trim();

        return value === ''
            || value === '#'
            || value.startsWith('#')
            || value.startsWith('javascript:')
            || value.startsWith('mailto:')
            || value.startsWith('tel:');
    };

    const isDownload = (link) => {
        if (link.hasAttribute('download') || link.dataset.loading === 'download') {
            return true;
        }

        try {
            const url = new URL(link.href, window.location.href);

            return url.pathname.includes('/export') || /\.(xlsx|xls|csv|pdf|zip)$/i.test(url.pathname);
        } catch {
            return false;
        }
    };

    const isInPageNavigation = (link) => {
        try {
            const url = new URL(link.href, window.location.href);

            return url.origin === window.location.origin
                && url.pathname === window.location.pathname
                && url.search === window.location.search
                && url.hash !== '';
        } catch {
            return false;
        }
    };

    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented) {
            return;
        }

        const form = event.target;

        if (! (form instanceof HTMLFormElement) || form.dataset.loading === 'skip') {
            return;
        }

        show(form.dataset.loadingMessage || 'Processing your request');
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');

        if (! link || event.defaultPrevented || isModifiedClick(event)) {
            return;
        }

        if (link.dataset.loading === 'skip' || link.target === '_blank' || link.hasAttribute('data-no-loading')) {
            return;
        }

        if (isIgnoredHref(link.getAttribute('href')) || isInPageNavigation(link)) {
            return;
        }

        show(link.dataset.loadingMessage || 'Loading');

        if (isDownload(link)) {
            downloadTimer = window.setTimeout(hide, 2500);
        }
    });

    window.addEventListener('pageshow', hide);
}

document.addEventListener('DOMContentLoaded', initLoadingScreen);
