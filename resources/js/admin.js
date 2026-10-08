document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('admin-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggle = document.getElementById('sidebar-toggle');

    const closeSidebar = () => {
        sidebar?.classList.add('-translate-x-full', 'pointer-events-none');
        backdrop?.classList.add('hidden');
    };

    toggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('-translate-x-full');
        sidebar?.classList.toggle('pointer-events-none');
        backdrop?.classList.toggle('hidden');
    });
    backdrop?.addEventListener('click', closeSidebar);

    const lightbox = document.getElementById('lightbox');
    const image = document.getElementById('lightbox-image');
    const download = document.getElementById('lightbox-download');
    let scale = 1;

    const openLightbox = (src, href) => {
        if (!lightbox || !image) {
            return;
        }
        scale = 1;
        image.style.transform = 'scale(1)';
        image.src = src;
        if (download) {
            download.href = href || src;
        }
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
    };

    const closeLightbox = () => {
        lightbox?.classList.add('hidden');
        lightbox?.classList.remove('flex');
        if (image) {
            image.src = '';
        }
    };

    document.querySelectorAll('.js-lightbox').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            openLightbox(trigger.dataset.src, trigger.dataset.download);
        });
    });

    document.getElementById('lightbox-close')?.addEventListener('click', closeLightbox);
    lightbox?.addEventListener('click', (event) => {
        if (event.target === lightbox) {
            closeLightbox();
        }
    });
    document.getElementById('lightbox-zoom-in')?.addEventListener('click', () => {
        scale = Math.min(scale + 0.25, 3);
        if (image) {
            image.style.transform = `scale(${scale})`;
        }
    });
    document.getElementById('lightbox-zoom-out')?.addEventListener('click', () => {
        scale = Math.max(scale - 0.25, 0.5);
        if (image) {
            image.style.transform = `scale(${scale})`;
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeLightbox();
        }
    });
});
