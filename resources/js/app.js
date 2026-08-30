document.documentElement.classList.add('js');

function initReveal(root) {
    const els = (root ?? document).querySelectorAll('.reveal:not(.is-visible)');
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        els.forEach((el) => el.classList.add('is-visible'));
        return;
    }
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1, rootMargin: '0px 0px -6% 0px' },
    );
    els.forEach((el) => observer.observe(el));
}

window.initReveal = initReveal;

document.addEventListener('DOMContentLoaded', () => initReveal());

document.addEventListener('livewire:navigated', () => initReveal());
document.addEventListener('livewire:init', () => {
    if (window.Livewire) {
        window.Livewire.hook('morph.updated', () => initReveal());
    }
});
