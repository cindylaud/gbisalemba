function initWhatsNewSlider() {
  const slider = document.getElementById('whatsNewSlider');
  const track = document.getElementById('whatsNewTrack');
  const prevBtn = document.getElementById('whatsNewPrev');
  const nextBtn = document.getElementById('whatsNewNext');
  const dotsWrap = document.getElementById('whatsNewDots');
  if (!slider || !track) return;

  const AUTOPLAY_DELAY = 2800;
  const TRANSITION = 'transform 750ms cubic-bezier(0.2, 0.9, 0.25, 1)';

  let currentIndex = 0;
  let originalCount = 0;
  let cardStep = 0;
  let cardWidth = 0;
  let autoplayTimer = null;
  let isTransitioning = false;
  let dots = [];

  function getOriginalItemsInTrack() {
    return Array.from(track.querySelectorAll('.whats-new-card:not([data-clone="true"])'));
  }

  function getAllItemsInTrack() {
    return Array.from(track.querySelectorAll('.whats-new-card'));
  }

  function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
  }

  function calculateSizing() {
    const containerWidth = slider.clientWidth;
    const isMobile = window.matchMedia('(max-width: 576px)').matches;
    const isTablet = window.matchMedia('(max-width: 900px)').matches;

    if (isMobile) {
      cardWidth = containerWidth * 0.84;
    } else if (isTablet) {
      cardWidth = containerWidth * 0.74;
    } else {
      cardWidth = containerWidth * 0.64;
    }

    cardWidth = clamp(cardWidth, 250, 760);
    const gap = isMobile ? 12 : isTablet ? 18 : 24;
    cardStep = cardWidth + gap;

    track.style.gap = gap + 'px';
    getAllItemsInTrack().forEach(function (card) {
      card.style.width = cardWidth + 'px';
    });
  }

  function clearClones() {
    track.querySelectorAll('[data-clone]').forEach(function (el) { el.remove(); });
  }

  function addLoopClones() {
    const originals = getOriginalItemsInTrack();
    if (originals.length <= 1) return;

    const firstClone = originals[0].cloneNode(true);
    const lastClone = originals[originals.length - 1].cloneNode(true);

    firstClone.setAttribute('data-clone', 'true');
    lastClone.setAttribute('data-clone', 'true');

    track.insertBefore(lastClone, originals[0]);
    track.appendChild(firstClone);
  }

  function applyTranslate(animate) {
    const viewportOffset = (slider.clientWidth - cardWidth) / 2;
    const offset = (currentIndex * cardStep) - viewportOffset;
    track.style.transition = animate ? TRANSITION : 'none';
    track.style.transform = 'translate3d(-' + offset + 'px, 0, 0)';
    void track.offsetWidth;
  }

  function getActiveOriginalIndex() {
    if (originalCount <= 1) return 0;
    if (currentIndex <= 0) return originalCount - 1;
    if (currentIndex > originalCount) return 0;
    return currentIndex - 1;
  }

  function updateDots() {
    if (!dots.length || originalCount <= 1) return;
    const activeIndex = getActiveOriginalIndex();
    dots.forEach(function (dot, index) {
      dot.classList.toggle('active', index === activeIndex);
    });
  }

  function updateCardStates() {
    const cards = getAllItemsInTrack();
    cards.forEach(function (card) {
      card.classList.remove('is-active', 'is-prev', 'is-next');
    });

    const activeCard = cards[currentIndex];
    const prevCard = cards[currentIndex - 1];
    const nextCard = cards[currentIndex + 1];

    if (activeCard) activeCard.classList.add('is-active');
    if (prevCard) prevCard.classList.add('is-prev');
    if (nextCard) nextCard.classList.add('is-next');
  }

  function buildDots() {
    if (!dotsWrap) return;
    dotsWrap.innerHTML = '';
    dots = [];
    if (originalCount <= 1) return;

    for (let index = 0; index < originalCount; index++) {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'whats-new-dot' + (index === 0 ? ' active' : '');
      dot.setAttribute('aria-label', 'Pindah ke slide ' + (index + 1));
      dot.addEventListener('click', function () {
        if (isTransitioning) return;
        currentIndex = originalCount <= 1 ? 0 : index + 1;
        applyTranslate(true);
        updateCardStates();
        updateDots();
        restartAutoplay();
      });
      dotsWrap.appendChild(dot);
      dots.push(dot);
    }
  }

  function setup() {
    if (slider.clientWidth === 0) {
      requestAnimationFrame(setup);
      return;
    }

    clearClones();
    originalCount = getOriginalItemsInTrack().length;
    addLoopClones();
    calculateSizing();
    buildDots();
    currentIndex = originalCount <= 1 ? 0 : 1;
    isTransitioning = false;
    applyTranslate(false);
    updateCardStates();
    updateDots();

    if (prevBtn) prevBtn.style.display = originalCount <= 1 ? 'none' : '';
    if (nextBtn) nextBtn.style.display = originalCount <= 1 ? 'none' : '';
    if (dotsWrap) dotsWrap.style.display = originalCount <= 1 ? 'none' : '';
  }

  function next() {
    if (isTransitioning) return;
    if (originalCount <= 1) return;

    currentIndex++;
    isTransitioning = true;
    applyTranslate(true);
    updateCardStates();
    updateDots();
  }

  function prev() {
    if (isTransitioning) return;
    if (originalCount <= 1) return;

    currentIndex--;
    isTransitioning = true;
    applyTranslate(true);
    updateCardStates();
    updateDots();
  }

  track.addEventListener('transitionend', function () {
    isTransitioning = false;

    if (originalCount <= 1) return;

    if (currentIndex === 0) {
      currentIndex = originalCount;
      applyTranslate(false);
      void track.offsetWidth;
    }

    if (currentIndex === originalCount + 1) {
      currentIndex = 1;
      applyTranslate(false);
      void track.offsetWidth;
    }

    updateCardStates();
    updateDots();
  });

  function startAutoplay() {
    if (originalCount <= 1) return;
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

  slider.addEventListener('focusin', stopAutoplay);
  slider.addEventListener('focusout', startAutoplay);

  slider.addEventListener('keydown', function (event) {
    if (event.key === 'ArrowRight') {
      next();
      restartAutoplay();
    }

    if (event.key === 'ArrowLeft') {
      prev();
      restartAutoplay();
    }
  });

  requestAnimationFrame(function () {
    setup();
    startAutoplay();
  });
}

document.addEventListener('DOMContentLoaded', initWhatsNewSlider);