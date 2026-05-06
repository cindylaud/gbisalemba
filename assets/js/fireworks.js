// Global function: launchFireworks
// Simple, direct assignment to window - NO IIFE wrapper
// Available immediately in browser console as: launchFireworks()

window.launchFireworks = function() {
  // Validate canvas exists
  var canvas = document.getElementById('fireworksCanvas');
  if (!canvas) {
    console.warn('[Fireworks] Canvas #fireworksCanvas not found');
    return;
  }

  // Validate confetti library is loaded
  if (typeof confetti === 'undefined') {
    console.warn('[Fireworks] canvas-confetti library not loaded');
    return;
  }

  console.log('[Fireworks] Starting animation...');

  // Create confetti instance with optimization settings
  var confettiInstance = confetti.create(canvas, {
    resize: true,
    useWorker: true
  });

  var animationDuration = 3500; // 3.5 seconds
  var startTime = Date.now();

  // Animation loop
  var loop = function() {
    var elapsed = Date.now() - startTime;

    if (elapsed < animationDuration) {
      // Fire particles every ~300ms for continuous effect
      if (Math.floor(elapsed / 300) % 2 === 0) {
        confettiInstance({
          particleCount: 60,
          angle: 90,
          spread: 70,
          origin: {
            x: Math.random(),
            y: Math.random() * 0.6
          },
          startVelocity: 20,
          decay: 0.95
        });
      }

      requestAnimationFrame(loop);
    } else {
      // Final burst
      confettiInstance({
        particleCount: 100,
        angle: 90,
        spread: 100,
        origin: { x: 0.5, y: 0.6 },
        startVelocity: 30
      });

      console.log('[Fireworks] Animation complete');
    }
  };

  // Start animation
  loop();
};
