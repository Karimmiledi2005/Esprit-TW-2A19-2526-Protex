/**
 * PROTEX — Micro-animations JS
 * helpers/micro-animations.js
 * Activates card-reveal, ripple buttons, and counter animation hooks.
 */
(function () {
    'use strict';

    /* ── Card Reveal via IntersectionObserver ─────────────── */
    function initCardReveal() {
        const cards = document.querySelectorAll('.card-reveal');
        if (!cards.length || !('IntersectionObserver' in window)) {
            cards.forEach(c => c.classList.add('visible'));
            return;
        }
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    obs.unobserve(e.target);
                }
            });
        }, { threshold: 0.12 });
        cards.forEach(c => obs.observe(c));
    }

    /* ── Ripple Effect ────────────────────────────────────── */
    function initRipple() {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.ripple-btn');
            if (!btn) return;
            const rect = btn.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height) * 2;
            const x    = e.clientX - rect.left - size / 2;
            const y    = e.clientY - rect.top  - size / 2;
            const wave = document.createElement('span');
            wave.className = 'ripple-wave';
            wave.style.cssText = `width:${size}px;height:${size}px;left:${x}px;top:${y}px`;
            btn.appendChild(wave);
            wave.addEventListener('animationend', () => wave.remove());
        });
    }

    /* ── Stagger children of .stagger-parent ─────────────── */
    function initStagger() {
        document.querySelectorAll('.stagger-parent').forEach(parent => {
            Array.from(parent.children).forEach((child, i) => {
                child.style.animationDelay = (i * 0.08) + 's';
                child.classList.add('anim-up');
            });
        });
    }

    /* ── Hover lift for .auto-lift ────────────────────────── */
    function initAutoLift() {
        document.querySelectorAll('.auto-lift').forEach(el => {
            el.classList.add('hover-lift');
        });
    }

    /* ── Number count-up ──────────────────────────────────── */
    function countUp(el, target, duration) {
        const start = performance.now();
        const from  = 0;
        function frame(now) {
            const progress = Math.min((now - start) / duration, 1);
            const ease     = 1 - Math.pow(1 - progress, 3); // easeOutCubic
            el.textContent = Math.round(from + (target - from) * ease).toLocaleString('fr-FR');
            if (progress < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }

    function initCounters() {
        const counters = document.querySelectorAll('[data-count-up]');
        if (!counters.length) return;
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    const target = parseFloat(e.target.dataset.countUp) || 0;
                    const dur    = parseInt(e.target.dataset.countDur, 10) || 1500;
                    countUp(e.target, target, dur);
                    obs.unobserve(e.target);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(c => obs.observe(c));
    }

    /* ── Init all on DOM ready ────────────────────────────── */
    function init() {
        initCardReveal();
        initRipple();
        initStagger();
        initAutoLift();
        initCounters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
