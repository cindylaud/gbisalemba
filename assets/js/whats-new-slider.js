function initWhatsNewSlider() {
  const slider = document.getElementById('whatsNewSlider');
  const track = document.getElementById('whatsNewTrack');
  const prevBtn = document.getElementById('whatsNewPrev');
  const nextBtn = document.getElementById('whatsNewNext');
  const dotsWrap = document.getElementById('whatsNewDots');
  if (!slider || !track) return;

  const AUTOPLAY_DELAY = 2000;
  const TRANSITION = 'transform 700ms cubic-bezier(0.22, 1, 0.36, 1)';

  let currentIndex = 0;
  let autoplayTimer = null;
  let isTransitioning = false;
  let dots = [];

  function getOriginalItems() {
    return Array.from(track.querySelectorAll('.whats-new-card:not([data-clone])'));
  }

  function getCardStep() {
    return slider.clientWidth;
  }

  function clearClones() {
    track.querySelectorAll('[data-clone]').forEach(function (el) { el.remove(); });
  }

  function addClones() {
    const originals = getOriginalItems();
    if (originals.length === 0) return;
    const clone = originals[0].cloneNode(true);
    clone.setAttribute('data-clone', 'true');
    track.appendChild(clone);
  }

  function applyTranslate(animate) {
    const offset = currentIndex * getCardStep();
    track.style.transition = animate ? TRANSITION : 'none';
    track.style.transform = 'translateX(-' + offset + 'px)';
    void track.offsetWidth;
  }

  function updateDots() {
    const originals = getOriginalItems();
    if (!dots.length || originals.length <= 1) return;
    const activeIndex = currentIndex >= originals.length ? 0 : currentIndex;
    dots.forEach(function (dot, index) {
      dot.classList.toggle('active', index === activeIndex);
    });
  }

  function buildDots() {
    if (!dotsWrap) return;
    dotsWrap.innerHTML = '';
    dots = [];
    const originals = getOriginalItems();
    if (originals.length <= 1) return;

    originals.forEach(function (_, index) {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'whats-new-dot' + (index === 0 ? ' active' : '');
      dot.setAttribute('aria-label', 'Pindah ke slide ' + (index + 1));
      dot.addEventListener('click', function () {
        if (isTransitioning) return;
        currentIndex = index;
        applyTranslate(true);
        updateDots();
        restartAutoplay();
      });
      dotsWrap.appendChild(dot);
      dots.push(dot);
    });
  }

  function setup() {
    if (slider.clientWidth === 0) {
      requestAnimationFrame(setup);
      return;
    }
    clearClones();
    addClones();
    buildDots();
    currentIndex = 0;
    isTransitioning = false;
    applyTranslate(false);
    updateDots();
  }

  function next() {
    if (isTransitioning) return;
    const originals = getOriginalItems();
    if (originals.length <= 1) return;
    currentIndex++;
    isTransitioning = true;
    applyTranslate(true);
    updateDots();
  }

  function prev() {
    if (isTransitioning) return;
    const originals = getOriginalItems();
    if (originals.length <= 1) return;

    if (currentIndex === 0) {
      currentIndex = originals.length;
      applyTranslate(false);
      void track.offsetWidth;
    }
    currentIndex--;
    isTransitioning = true;
    applyTranslate(true);
    updateDots();
  }

  track.addEventListener('transitionend', function () {
    isTransitioning = false;
    const originals = getOriginalItems();
    if (currentIndex >= originals.length) {
      currentIndex = 0;
      applyTranslate(false);
      void track.offsetWidth;
    }
    updateDots();
  });

  function startAutoplay() {
    if (getOriginalItems().length <= 1) return;
    stopAutoplay();
    autoplayTimer = setInterval(next, AUTOPLAY_DELAY);
  }

  function stopAutoplay() {
    if (!autoplayTimer) return;
    clearInterval(autoplayTimer);
    autoplayTimer = null;
  }

  function restartAutoplay() {
    stopAutoplay();
    startAutoplay();
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      next();
      restartAutoplay();
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      prev();
      restartAutoplay();
    });
  }

  slider.addEventListener('mouseenter', stopAutoplay);
  slider.addEventListener('mouseleave', startAutoplay);

  let resizeTimer = null;
  window.addEventListener('resize', function () {
    if (resizeTimer) clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      stopAutoplay();
      setup();
      startAutoplay();
    }, 150);
  });

  requestAnimationFrame(function () {
    setup();
    startAutoplay();
  });
}

document.addEventListener('DOMContentLoaded', initWhatsNewSlider);