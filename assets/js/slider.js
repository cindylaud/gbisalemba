let currentSlide = 0;
let autoplayTimer = null;
const sliderSpeed = 2000;

function initSlider() {
  const slider = document.getElementById("sliderSection");
  if (!slider) return;

  const slides = slider.querySelectorAll(".hero-slide");
  if (slides.length <= 1) return;

  showSlide(0);
  startAutoplay();

  slider.addEventListener("mouseenter", stopAutoplay);
  slider.addEventListener("mouseleave", startAutoplay);
}

function showSlide(index) {
  const slider = document.getElementById("sliderSection");
  const slides = slider.querySelectorAll(".hero-slide");
  const dots = slider.querySelectorAll(".hero-dot");

  slides.forEach(s => s.classList.remove("active"));
  dots.forEach(d => d.classList.remove("active"));

  if (slides[index]) slides[index].classList.add("active");
  if (dots[index]) dots[index].classList.add("active");

  currentSlide = index;
}

function sliderNext() {
  const slides = document.querySelectorAll("#sliderSection .hero-slide");
  currentSlide = (currentSlide + 1) % slides.length;
  showSlide(currentSlide);
  stopAutoplay(); startAutoplay();
}

function sliderPrev() {
  const slides = document.querySelectorAll("#sliderSection .hero-slide");
  currentSlide = (currentSlide - 1 + slides.length) % slides.length;
  showSlide(currentSlide);
  stopAutoplay(); startAutoplay();
}

function sliderGoto(i) {
  showSlide(i);
  stopAutoplay(); startAutoplay();
}

function startAutoplay() {
  const slides = document.querySelectorAll("#sliderSection .hero-slide");
  if (slides.length <= 1) return;

  stopAutoplay();
  autoplayTimer = setInterval(sliderNext, sliderSpeed);
}

function stopAutoplay() {
  if (autoplayTimer) {
    clearInterval(autoplayTimer);
    autoplayTimer = null;
  }
}

document.addEventListener("DOMContentLoaded", initSlider);