class TourGuide {
    constructor(steps, options = {}) {
        this.steps = steps;
        this.currentStep = 0;
        this.options = {
            overlayColor: 'rgba(0,0,0,0.55)',
            highlightPadding: 10,
            zIndex: 9999,
            storageKey: 'ptx_onboarding_done',
            completeUrl: null,
            ...options
        };
        this._elements = { overlay: null, tooltip: null, holes: [] };
        this._active = false;
    }

    start() {
        if (this._active) return;
        if (localStorage.getItem(this.options.storageKey)) return;
        this._active = true;
        this.currentStep = 0;
        this._render();
    }

    next() {
        if (this.currentStep < this.steps.length - 1) {
            this.currentStep++;
            this._render();
        } else {
            this._complete();
        }
    }

    prev() {
        if (this.currentStep > 0) {
            this.currentStep--;
            this._render();
        }
    }

    goTo(index) {
        if (index >= 0 && index < this.steps.length) {
            this.currentStep = index;
            this._render();
        }
    }

    skip() {
        this._complete();
    }

    destroy() {
        this._active = false;
        if (this._elements.overlay) {
            this._elements.overlay.remove();
            this._elements.overlay = null;
        }
        if (this._elements.tooltip) {
            this._elements.tooltip.remove();
            this._elements.tooltip = null;
        }
        this._elements.holes.forEach(el => el.remove());
        this._elements.holes = [];
        document.body.style.overflow = '';
    }

    _complete() {
        localStorage.setItem(this.options.storageKey, '1');
        if (this.options.completeUrl) {
            navigator.sendBeacon(this.options.completeUrl, 'onboarding_done=1');
        }
        this.destroy();
        if (this.options.onComplete) this.options.onComplete();
    }

    _render() {
        this._cleanOverlay();
        const step = this.steps[this.currentStep];
        const target = document.querySelector(step.selector);
        if (!target) {
            console.warn(`TourGuide: selector "${step.selector}" not found, skipping.`);
            this.next();
            return;
        }
        this._scrollToElement(target);
        this._createOverlay(target);
        this._createTooltip(step, target);
    }

    _cleanOverlay() {
        if (this._elements.overlay) this._elements.overlay.remove();
        if (this._elements.tooltip) this._elements.tooltip.remove();
        this._elements.holes.forEach(el => el.remove());
        this._elements.holes = [];
    }

    _scrollToElement(el) {
        const rect = el.getBoundingClientRect();
        const isVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;
        if (!isVisible) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    _createOverlay(target) {
        const rect = target.getBoundingClientRect();
        const p = this.options.highlightPadding;

        const overlay = document.createElement('div');
        overlay.style.cssText = `position:fixed;inset:0;z-index:${this.options.zIndex};background:${this.options.overlayColor};`;
        overlay.style.pointerEvents = 'none';
        document.body.appendChild(overlay);
        this._elements.overlay = overlay;

        const createHole = (t, l, w, h) => {
            const div = document.createElement('div');
            div.style.cssText = `position:fixed;top:${t}px;left:${l}px;width:${w}px;height:${h}px;background:transparent;pointer-events:auto;z-index:${this.options.zIndex + 1};`;
            overlay.appendChild(div);
            this._elements.holes.push(div);
            return div;
        };

        // Four panels around the target (with padding)
        const top = rect.top - p;
        const left = rect.left - p;
        const bottom = rect.bottom + p;
        const right = rect.right + p;

        // Top panel
        createHole(0, 0, Math.max(document.documentElement.clientWidth, window.innerWidth), Math.max(0, top));
        // Bottom panel
        createHole(Math.max(0, bottom), 0, Math.max(document.documentElement.clientWidth, window.innerWidth), Math.max(0, document.documentElement.clientHeight - bottom));
        // Left panel
        createHole(Math.max(0, top), 0, Math.max(0, left), Math.max(0, rect.height + p * 2));
        // Right panel
        createHole(Math.max(0, top), Math.max(0, right), Math.max(0, document.documentElement.clientWidth - right), Math.max(0, rect.height + p * 2));

        // Click to advance on overlay
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) this.next();
        });
    }

    _createTooltip(step, target) {
        const rect = target.getBoundingClientRect();
        const pos = step.position || 'bottom';
        const tooltip = document.createElement('div');
        tooltip.style.cssText = `
            position:fixed;z-index:${this.options.zIndex + 2};
            background:#fff;color:#1a1a2e;
            border-radius:16px;padding:20px 24px;
            max-width:380px;width:max-content;
            box-shadow:0 12px 40px rgba(0,0,0,0.25);
            font-family:'Sora','DM Sans',-apple-system,sans-serif;
            pointer-events:auto;
        `;

        const arrow = document.createElement('div');
        arrow.style.cssText = 'position:absolute;width:12px;height:12px;background:#fff;transform:rotate(45deg);';

        let tooltipTop, tooltipLeft;
        const gap = 14;

        switch (pos) {
            case 'top':
                tooltipTop = rect.top - gap - 10;
                tooltipLeft = rect.left + rect.width / 2;
                arrow.style.top = '100%';
                arrow.style.left = '50%';
                arrow.style.marginLeft = '-6px';
                arrow.style.marginTop = '-6px';
                break;
            case 'bottom':
                tooltipTop = rect.bottom + gap;
                tooltipLeft = rect.left + rect.width / 2;
                arrow.style.top = '-6px';
                arrow.style.left = '50%';
                arrow.style.marginLeft = '-6px';
                break;
            case 'left':
                tooltipTop = rect.top + rect.height / 2;
                tooltipLeft = rect.left - gap - 10;
                arrow.style.top = '50%';
                arrow.style.left = '100%';
                arrow.style.marginTop = '-6px';
                arrow.style.marginLeft = '-6px';
                break;
            case 'right':
                tooltipTop = rect.top + rect.height / 2;
                tooltipLeft = rect.right + gap;
                arrow.style.top = '50%';
                arrow.style.left = '-6px';
                arrow.style.marginTop = '-6px';
                break;
            default:
                tooltipTop = rect.bottom + gap;
                tooltipLeft = rect.left + rect.width / 2;
                arrow.style.top = '-6px';
                arrow.style.left = '50%';
                arrow.style.marginLeft = '-6px';
        }

        // Clamp to viewport
        tooltip.style.transform = 'translateX(-50%)';
        tooltip.style.top = Math.max(12, Math.min(tooltipTop, window.innerHeight - 200)) + 'px';
        tooltip.style.left = Math.max(12, Math.min(tooltipLeft, window.innerWidth - 400)) + 'px';

        tooltip.appendChild(arrow);

        // Header with step indicator
        const header = document.createElement('div');
        header.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;';

        const stepBadge = document.createElement('span');
        stepBadge.style.cssText = 'background:#FF6B1A;color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;';
        stepBadge.textContent = `${this.currentStep + 1}/${this.steps.length}`;
        header.appendChild(stepBadge);

        const skipBtn = document.createElement('button');
        skipBtn.textContent = 'Ignorer ✕';
        skipBtn.style.cssText = 'background:none;border:none;color:#94a3b8;font-size:12px;cursor:pointer;padding:4px 8px;border-radius:6px;';
        skipBtn.onmouseover = () => skipBtn.style.background = '#f1f5f9';
        skipBtn.onmouseout = () => skipBtn.style.background = 'none';
        skipBtn.onclick = () => this.skip();
        header.appendChild(skipBtn);
        tooltip.appendChild(header);

        // Icon
        if (step.icon) {
            const iconEl = document.createElement('div');
            iconEl.style.cssText = 'font-size:28px;margin-bottom:8px;';
            iconEl.textContent = step.icon;
            tooltip.appendChild(iconEl);
        }

        // Title
        const title = document.createElement('div');
        title.style.cssText = 'font-size:17px;font-weight:700;margin-bottom:6px;color:#0f172a;';
        title.textContent = step.title;
        tooltip.appendChild(title);

        // Description
        const desc = document.createElement('div');
        desc.style.cssText = 'font-size:14px;line-height:1.6;color:#475569;margin-bottom:16px;';
        desc.textContent = step.description;
        tooltip.appendChild(desc);

        // Footer buttons
        const footer = document.createElement('div');
        footer.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:10px;';

        const navLeft = document.createElement('div');
        navLeft.style.cssText = 'display:flex;gap:8px;';

        if (this.currentStep > 0) {
            const prevBtn = document.createElement('button');
            prevBtn.textContent = '← Précédent';
            prevBtn.style.cssText = 'background:#f1f5f9;border:none;color:#334155;padding:8px 16px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;';
            prevBtn.onmouseover = () => prevBtn.style.background = '#e2e8f0';
            prevBtn.onmouseout = () => prevBtn.style.background = '#f1f5f9';
            prevBtn.onclick = () => this.prev();
            navLeft.appendChild(prevBtn);
        }

        footer.appendChild(navLeft);

        const nextBtn = document.createElement('button');
        const isLast = this.currentStep === this.steps.length - 1;
        nextBtn.textContent = isLast ? '✅ Terminer' : 'Suivant →';
        nextBtn.style.cssText = `background:#FF6B1A;border:none;color:#fff;padding:8px 20px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;margin-left:auto;`;
        nextBtn.onmouseover = () => nextBtn.style.background = '#e55d12';
        nextBtn.onmouseout = () => nextBtn.style.background = '#FF6B1A';
        nextBtn.onclick = () => this.next();
        footer.appendChild(nextBtn);

        tooltip.appendChild(footer);
        document.body.appendChild(tooltip);
        this._elements.tooltip = tooltip;
    }
}
