/**
 * Breeze Slider — frontend init.
 *
 * Mounts a Splide instance on each .breeze-slider. Options are read by
 * Splide from each element's data-splide attribute (set in template.php).
 *
 * Splide is loaded via the "bricks-splide" handle (or our fallback), so it
 * is not duplicated when Bricks is present. This script is defensive: it
 * waits for window.Splide in case script ordering is ever bypassed.
 */
(function() {
    'use strict';

    function mountAll() {
        var sliders = document.querySelectorAll('.breeze-slider.splide');

        Array.prototype.forEach.call(sliders, function(el) {
            // Guard against double-mount (e.g. re-runs)
            if (el.dataset.breezeMounted === '1') {
                return;
            }

            // Skip empty sliders
            if (!el.querySelector('.splide__slide')) {
                return;
            }

            el.dataset.breezeMounted = '1';

            try {
                new window.Splide(el).mount();
            } catch (e) {
                // Leave the static markup in place if Splide fails
                el.dataset.breezeMounted = '';
                if (window.console) {
                    window.console.warn('Breeze Slider: Splide mount failed', e);
                }
            }
        });
    }

    function whenSplideReady(cb) {
        if (window.Splide) {
            cb();
            return;
        }

        var waited = 0;
        var timer = setInterval(function() {
            if (window.Splide) {
                clearInterval(timer);
                cb();
            } else if ((waited += 50) > 10000) {
                clearInterval(timer);
            }
        }, 50);
    }

    function init() {
        whenSplideReady(mountAll);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
