/**
 * toast.js — Protex Global Toast Notification System
 * Usage: showToast('Contrat créé avec succès !', 'success', 4000)
 * Auto-detects ?success= and ?error= URL params on load
 */
(function () {
  'use strict';

  const ICONS = {
    success: '✅',
    error:   '❌',
    warning: '⚠️',
    info:    'ℹ️'
  };

  // Create container if it doesn't exist
  function getContainer() {
    let c = document.getElementById('protex-toast-container');
    if (!c) {
      c = document.createElement('div');
      c.id = 'protex-toast-container';
      document.body.appendChild(c);
    }
    return c;
  }

  /**
   * Show a toast notification
   * @param {string} message - The message to display
   * @param {string} type - 'success' | 'error' | 'warning' | 'info'
   * @param {number} duration - Auto-dismiss in ms (default 4000, 0 = persistent)
   */
  function showToast(message, type, duration) {
    type = type || 'info';
    duration = duration !== undefined ? duration : 4000;

    const container = getContainer();

    const toast = document.createElement('div');
    toast.className = 'protex-toast ' + type;

    const icon = document.createElement('span');
    icon.className = 'protex-toast-icon';
    icon.textContent = ICONS[type] || ICONS.info;

    const body = document.createElement('span');
    body.className = 'protex-toast-body';
    body.textContent = message;

    const closeBtn = document.createElement('button');
    closeBtn.className = 'protex-toast-close';
    closeBtn.innerHTML = '×';
    closeBtn.onclick = function () { dismiss(toast); };

    toast.appendChild(icon);
    toast.appendChild(body);
    toast.appendChild(closeBtn);

    // Progress bar
    if (duration > 0) {
      const progress = document.createElement('div');
      progress.className = 'protex-toast-progress';
      progress.style.animationDuration = duration + 'ms';
      toast.appendChild(progress);
    }

    container.appendChild(toast);

    // Auto dismiss
    if (duration > 0) {
      setTimeout(function () { dismiss(toast); }, duration);
    }

    return toast;
  }

  function dismiss(toast) {
    if (!toast || toast.classList.contains('removing')) return;
    toast.classList.add('removing');
    setTimeout(function () {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 300);
  }

  // Auto-detect URL parameters
  document.addEventListener('DOMContentLoaded', function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('success')) {
      showToast(decodeURIComponent(params.get('success')), 'success');
    }
    if (params.get('error')) {
      showToast(decodeURIComponent(params.get('error')), 'error');
    }
    if (params.get('warning')) {
      showToast(decodeURIComponent(params.get('warning')), 'warning');
    }
    if (params.get('info')) {
      showToast(decodeURIComponent(params.get('info')), 'info');
    }
  });

  // Expose globally
  window.showToast = showToast;
})();
