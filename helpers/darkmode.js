/**
 * darkmode.js — Protex Dark Mode Toggle
 * Persists preference to localStorage('protex_theme')
 * Injects toggle button into every page
 */
(function () {
  'use strict';

  const STORAGE_KEY = 'protex_theme';
  const html = document.documentElement;

  // Restore saved theme or respect system preference
  function getPreferredTheme() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function applyTheme(theme) {
    html.setAttribute('data-theme', theme);
    localStorage.setItem(STORAGE_KEY, theme);
  }

  // Apply immediately (before DOM ready) to prevent flash
  applyTheme(getPreferredTheme());

  // Inject toggle button when DOM is ready
  document.addEventListener('DOMContentLoaded', function () {
    // Don't inject if already present
    if (document.getElementById('protex-darkmode-toggle')) return;

    const btn = document.createElement('button');
    btn.id = 'protex-darkmode-toggle';
    btn.className = 'darkmode-toggle';
    btn.setAttribute('aria-label', 'Basculer le thème sombre');
    btn.setAttribute('title', 'Mode sombre / clair');
    btn.innerHTML = '<span class="icon-moon">🌙</span><span class="icon-sun">☀️</span>';

    btn.addEventListener('click', function () {
      const current = html.getAttribute('data-theme') || 'light';
      const next = current === 'dark' ? 'light' : 'dark';
      applyTheme(next);
    });

    document.body.appendChild(btn);
  });

  // Listen for system theme changes
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
    if (!localStorage.getItem(STORAGE_KEY)) {
      applyTheme(e.matches ? 'dark' : 'light');
    }
  });
})();
