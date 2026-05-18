function initWhatsNewSlider() {
  try {
  const slider = document.getElementById('whatsNewSlider');
  const track = document.getElementById('whatsNewTrack');
  const prevBtn = document.getElementById('whatsNewPrev');
  const nextBtn = document.getElementById('whatsNewNext');
  const dotsWrap = document.getElementById('whatsNewDots');
  const modal = document.getElementById('comingSoonModal');
  const modalImage = document.getElementById('comingSoonModalImage');
  const modalTitle = document.getElementById('comingSoonModalTitle');
  const modalDatetime = document.getElementById('comingSoonModalDatetime');
  const modalLocation = document.getElementById('comingSoonModalLocation');
  const modalRegistration = document.getElementById('comingSoonModalRegistration');
  const modalDescription = document.getElementById('comingSoonModalDescription');
  const debug = /(?:\?|&)debug_whatsnew=1/.test(window.location.search || '');
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

  function getModalText(value) {
    const text = (value || '').trim();
    return text === '' ? 'Belum diisi' : text;
  }

  function openModalFromCard(card) {
    if (!modal || !modalImage || !modalTitle || !modalDatetime || !modalLocation || !modalRegistration || !modalDescription) {
      if (debug) console.warn('WhatsNew: modal elements missing', { modal, modalImage, modalTitle, modalDatetime, modalLocation, modalRegistration, modalDescription });
      return;
    }

    const imageSrc = card.getAttribute('data-event-image') || '';
    const title = getModalText(card.getAttribute('data-event-title'));
    const datetime = getModalText(card.getAttribute('data-event-datetime'));
    const location = getModalText(card.getAttribute('data-event-location'));
    const registration = getModalText(card.getAttribute('data-event-registration'));
    const description = getModalText(card.getAttribute('data-event-description'));

    modalImage.src = imageSrc;
    modalImage.alt = title;
    modalTitle.textContent = title;
    modalDatetime.textContent = datetime;
    modalLocation.textContent = location;
    modalRegistration.textContent = registration;
    modalDescription.textContent = description;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('coming-soon-modal-open');
    if (debug) console.log('WhatsNew: opened modal for', { title, datetime, location, registration, description, imageSrc });
  }

  function closeModal() {
    if (!modal) return;

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('coming-soon-modal-open');
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

  track.addEventListener('click', function (event) {
    const card = event.target.closest('.whats-new-card');
    if (!card || !track.contains(card)) return;
    openModalFromCard(card);
  });

  track.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    const card = event.target.closest('.whats-new-card');
    if (!card || !track.contains(card)) return;
    event.preventDefault();
    openModalFromCard(card);
  });

  // Fallback: attach per-card handlers in case event delegation is blocked on hosting (overlay/z-index issues)
  function attachPerCardHandlers() {
    try {
      getAllItemsInTrack().forEach(function (card) {
        if (card._wn_hasHandler) return;
        card._wn_hasHandler = true;
        card.addEventListener('click', function (e) {
          if (debug) console.log('WhatsNew: card click (direct)', card);
          openModalFromCard(card);
        });
        card.addEventListener('keydown', function (ev) {
          if (ev.key === 'Enter' || ev.key === ' ') {
            ev.preventDefault();
            openModalFromCard(card);
          }
        });
      });
    } catch (err) {
      if (debug) console.error('WhatsNew: attachPerCardHandlers failed', err);
    }
  }

  // call once after setup, and again after resize (when cards may be recreated)
  requestAnimationFrame(function () {
    attachPerCardHandlers();
  });
  window.addEventListener('resize', function () { attachPerCardHandlers(); });

  // Extra global fallback: detect clicks inside the slider using elementFromPoint.
  // This helps on hosted setups where an invisible overlay or stacking context prevents normal events.
  document.addEventListener('click', function (e) {
    try {
      if (!slider) return;
      const rect = slider.getBoundingClientRect();
      if (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom) return;
      const topEl = document.elementFromPoint(e.clientX, e.clientY);
      const card = topEl && topEl.closest ? topEl.closest('.whats-new-card') : null;
      if (card && track && track.contains(card)) {
        if (debug) console.log('WhatsNew: document click fallback found card', card);
        openModalFromCard(card);
      }
    } catch (err) {
      if (debug) console.error('WhatsNew: document fallback error', err);
    }
  }, true);

  if (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target.closest('[data-modal-close]')) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    });
  }

  requestAnimationFrame(function () {
    setup();
    startAutoplay();
  });
  } catch (err) {
    try { console.error('WhatsNew:init error', err); } catch (e) { /* ignore console errors */ }
    if (typeof debug !== 'undefined' && debug) {
      try { console.trace(err); } catch (e) { /* ignore */ }
    }
  }
}

document.addEventListener('DOMContentLoaded', initWhatsNewSlider);