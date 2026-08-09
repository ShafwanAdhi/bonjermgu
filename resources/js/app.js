import './bootstrap';

const revealElements = () => {
    const targets = document.querySelectorAll('[data-reveal]');

    if (! targets.length) {
        return;
    }

    document.documentElement.classList.add('reveal-ready');

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || ! ('IntersectionObserver' in window)) {
        targets.forEach((target) => target.classList.add('is-visible'));

        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, {
        rootMargin: '0px 0px -12% 0px',
        threshold: 0.16,
    });

    targets.forEach((target) => observer.observe(target));
};

window.addEventListener('DOMContentLoaded', revealElements);
