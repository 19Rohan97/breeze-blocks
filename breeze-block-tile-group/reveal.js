/**
 * Tile Group — progressive reveal.
 *
 * Staggered fade-in with a gentle upward lift when a Tile Group scrolls
 * into view. Loaded on the frontend only when the global setting is
 * enabled (Settings → Tile Group).
 *
 * The hidden state is applied from JS (not CSS), so tiles stay visible
 * if JavaScript fails to load.
 */
(function() {
    'use strict';

    var STAGGER_MS = 80;   // delay between one tile and the next
    var MAX_DELAY_MS = 800; // cap so long grids don't take forever

    function init() {
        var groups = document.querySelectorAll('.breeze-tile-group--reveal');

        if (!groups.length) {
            return;
        }

        var reducedMotion = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (reducedMotion || !('IntersectionObserver' in window)) {
            return;
        }

        Array.prototype.forEach.call(groups, function(group) {
            Array.prototype.forEach.call(group.children, function(tile, index) {
                tile.classList.add('breeze-tile-reveal');
                tile.style.transitionDelay = Math.min(index * STAGGER_MS, MAX_DELAY_MS) + 'ms';
            });
        });

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.15,
            rootMargin: '0px 0px -40px 0px'
        });

        Array.prototype.forEach.call(groups, function(group) {
            observer.observe(group);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
