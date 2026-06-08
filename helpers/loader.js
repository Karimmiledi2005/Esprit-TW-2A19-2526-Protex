/**
 * loader.js — Protex Global Loading Overlay
 * Usage: showLoader('Analyse en cours…') / hideLoader()
 */
(function () {
  'use strict';

  var overlay = null;
  var textEl  = null;

  function ensureOverlay() {
    if (overlay) return;
    overlay = document.createElement('div');
    overlay.className = 'protex-loader-overlay';
    overlay.id = 'protex-loader';

    var dots = document.createElement('div');
    dots.className = 'protex-loader-dots';
    dots.innerHTML = '<span></span><span></span><span></span>';

    textEl = document.createElement('div');
    textEl.className = 'protex-loader-text';
    textEl.textContent = 'Chargement…';

    overlay.appendChild(dots);
    overlay.appendChild(textEl);
    document.body.appendChild(overlay);
  }

  /**
   * Show the loading overlay
   * @param {string} [message] - Optional loading message
   */
  function showLoader(message) {
    ensureOverlay();
    textEl.textContent = message || 'Chargement…';
    // Force reflow before adding active class
    overlay.offsetHeight;
    overlay.classList.add('active');
  }

  /**
   * Hide the loading overlay
   */
  function hideLoader() {
    if (overlay) {
      overlay.classList.remove('active');
    }
  }

  window.showLoader = showLoader;
  window.hideLoader = hideLoader;
})();
