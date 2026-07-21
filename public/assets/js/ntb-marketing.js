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

    /* Header transparent sur la home : devient solide (.is-scrolled) une fois
       le hero dépassé. Sans hero transparent (autres pages), no-op. */
    function heroNav() {
        var h = document.getElementById('mkt-header');
        if (!h || !h.hasAttribute('data-hero-nav')) return;
        var hero = document.querySelector('.ntb-home');
        function update() {
            var trigger = hero ? hero.offsetHeight - 70 : window.innerHeight * 0.7;
            h.classList.toggle('is-scrolled', window.scrollY > trigger);
        }
        update();
        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
    }
    if (document.readyState !== 'loading') heroNav();
    else document.addEventListener('DOMContentLoaded', heroNav);
})();
