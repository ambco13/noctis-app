/**
 * Site vitrine — révélation au défilement des éléments [data-reveal].
 * Repli dur : au bout de 1.6 s tout est révélé, le contenu ne peut jamais
 * rester caché (même sans IntersectionObserver).
 */
(function () {
    'use strict';
    function revealAll() {
        document.querySelectorAll('[data-reveal]:not(.revealed)').forEach(function (el) {
            el.classList.add('revealed');
        });
    }
    var io = 'IntersectionObserver' in window
        ? new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { e.target.classList.add('revealed'); io.unobserve(e.target); }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' })
        : null;

    function scan() {
        document.querySelectorAll('[data-reveal]:not(.revealed)').forEach(function (el) {
            io ? io.observe(el) : el.classList.add('revealed');
        });
    }

    if (document.readyState !== 'loading') scan();
    else document.addEventListener('DOMContentLoaded', scan);
    setTimeout(revealAll, 1600);
})();
