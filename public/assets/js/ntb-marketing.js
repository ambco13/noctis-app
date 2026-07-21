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

    /* Menu mobile : le burger ouvre/ferme le panneau de navigation. */
    function burger() {
        var h = document.getElementById('mkt-header');
        var b = h && h.querySelector('.mk-burger');
        if (!b) return;
        b.addEventListener('click', function () {
            var open = h.classList.toggle('is-open');
            b.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (!open) closeDrops();     // referme le sous-menu Services
        });
        /* « Services » : ne navigue jamais. Sur mobile (burger), un tap
           ouvre/ferme le sous-menu (accordéon) ; sur desktop il ne fait rien
           (le menu s'ouvre au survol) — le 1er clic ne doit pas l'ouvrir. */
        var drop = h.querySelector('.mk-drop');
        var trigger = drop && drop.querySelector('a');
        if (trigger) {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                if (window.matchMedia('(max-width: 860px)').matches) {
                    drop.classList.toggle('is-open');
                }
            });
        }
        /* Fermer le menu mobile après un clic sur un vrai lien (pas le
           déclencheur Services, géré ci-dessus). */
        h.querySelectorAll('.mk-nav a').forEach(function (a) {
            if (drop && a === trigger) return;
            a.addEventListener('click', function () {
                h.classList.remove('is-open');
                b.setAttribute('aria-expanded', 'false');
                closeDrops();
            });
        });
        function closeDrops() {
            h.querySelectorAll('.mk-drop.is-open').forEach(function (d) { d.classList.remove('is-open'); });
        }
    }
    if (document.readyState !== 'loading') burger();
    else document.addEventListener('DOMContentLoaded', burger);
})();
