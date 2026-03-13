(function () {
  'use strict';

  var revealTargets = document.querySelectorAll('.ibadah-minggu-section.reveal-on-scroll, .ibadah-minggu-section .reveal-on-scroll, .gembala-section.reveal-on-scroll, .gembala-section .reveal-on-scroll, .cta-section.reveal-on-scroll, .cta-section .reveal-on-scroll');

  if (!revealTargets.length) {
    return;
  }

  var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function showAllImmediately() {
    revealTargets.forEach(function (el) {
      el.classList.add('is-visible');
    });
  }

  if (reducedMotion || !('IntersectionObserver' in window)) {
    showAllImmediately();
    return;
  }

  var observer = new IntersectionObserver(function (entries, obs) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) {
        return;
      }

      var el = entry.target;
      var delay = parseInt(el.getAttribute('data-delay') || '0', 10);

      window.setTimeout(function () {
        el.classList.add('is-visible');
      }, Math.max(0, delay));

      obs.unobserve(el);
    });
  }, {
    root: null,
    rootMargin: '0px 0px -10% 0px',
    threshold: 0.18
  });

  revealTargets.forEach(function (el) {
    observer.observe(el);
  });
})();
