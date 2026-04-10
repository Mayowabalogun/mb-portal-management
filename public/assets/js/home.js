/**
 * Landing page interactions.
 * - Typewriter headline for hero messaging rotation.
 */
document.addEventListener('DOMContentLoaded', function () {
  // Guard against missing third-party library in restricted environments.
  if (typeof TypeIt !== 'undefined') {
    new TypeIt('#typewriter', {
      strings: ['Find Your Dream Home', 'Premium Properties', 'Trusted Agency'],
      speed: 100,
      breakLines: false,
      loop: true,
      nextStringDelay: 2000,
      deleteSpeed: 50
    }).go();
  }
});
