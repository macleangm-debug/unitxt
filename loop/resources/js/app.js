import Alpine from 'alpinejs';

window.Alpine = Alpine;

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

Alpine.store('loopNav', {
    transitioning: false,
    go(url, event, options = {}) {
        if (
            event &&
            (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button === 1)
        ) {
            return;
        }
        if (prefersReducedMotion()) {
            return;
        }
        event?.preventDefault();

        const morphEl = options.morph;
        if (morphEl) {
            try {
                const rect = morphEl.getBoundingClientRect();
                const img = morphEl.querySelector('img');
                const initial = morphEl.querySelector('.font-display, [data-initial]');
                sessionStorage.setItem(
                    'loopMorph',
                    JSON.stringify({
                        id: morphEl.getAttribute('data-loop-morph') || '',
                        href: url,
                        src: img?.currentSrc || img?.src || '',
                        initial: initial?.textContent?.trim()?.charAt(0) || '',
                        top: rect.top,
                        left: rect.left,
                        width: rect.width,
                        height: rect.height,
                        radius: getComputedStyle(morphEl).borderRadius || '1.05rem',
                    })
                );
            } catch (_) {
                /* ignore */
            }
        }

        this.transitioning = true;
        setTimeout(() => {
            window.location.href = url;
        }, 280);
    },
});

/**
 * Shared-element settle: flying logo into business profile hero.
 */
function loopSettleMorph() {
    if (prefersReducedMotion()) {
        sessionStorage.removeItem('loopMorph');
        return;
    }

    let payload;
    try {
        payload = JSON.parse(sessionStorage.getItem('loopMorph') || 'null');
    } catch (_) {
        payload = null;
    }
    sessionStorage.removeItem('loopMorph');
    if (! payload?.id) {
        return;
    }

    const target = document.querySelector(`[data-loop-morph-target="${payload.id}"]`);
    if (! target) {
        return;
    }

    const to = target.getBoundingClientRect();
    if (to.width < 8 || to.height < 8) {
        return;
    }

    const flyer = document.createElement('div');
    flyer.className = 'loop-morph-flyer';
    flyer.style.top = `${payload.top}px`;
    flyer.style.left = `${payload.left}px`;
    flyer.style.width = `${payload.width}px`;
    flyer.style.height = `${payload.height}px`;
    flyer.style.borderRadius = payload.radius;

    if (payload.src) {
        const img = document.createElement('img');
        img.src = payload.src;
        img.alt = '';
        img.draggable = false;
        flyer.appendChild(img);
    } else {
        flyer.innerHTML = `<span class="loop-morph-flyer__initial">${payload.initial || ''}</span>`;
    }

    document.body.appendChild(flyer);
    target.classList.add('loop-morph-target--waiting');

    requestAnimationFrame(() => {
        flyer.style.top = `${to.top}px`;
        flyer.style.left = `${to.left}px`;
        flyer.style.width = `${to.width}px`;
        flyer.style.height = `${to.height}px`;
        flyer.style.borderRadius = getComputedStyle(target).borderRadius || payload.radius;
        flyer.classList.add('is-settling');
    });

    const finish = () => {
        flyer.remove();
        target.classList.remove('loop-morph-target--waiting');
        target.classList.add('loop-morph-target--landed');
        setTimeout(() => target.classList.remove('loop-morph-target--landed'), 400);
    };

    flyer.addEventListener('transitionend', finish, { once: true });
    setTimeout(finish, 700);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loopSettleMorph);
} else {
    loopSettleMorph();
}

/**
 * QR expand — FLIP from thumb to centered lightbox.
 */
Alpine.data('loopQrExpand', () => ({
    visible: false,
    expanded: false,
    frameStyle: '',
    _closing: false,
    open(thumb) {
        if (this.visible || ! thumb) {
            return;
        }
        const rect = thumb.getBoundingClientRect();
        const size = Math.min(300, Math.floor(window.innerWidth * 0.78));
        this.visible = true;
        this.expanded = false;
        this.frameStyle = [
            'position:fixed',
            `top:${rect.top}px`,
            `left:${rect.left}px`,
            `width:${rect.width}px`,
            'z-index:96',
            'transition:none',
        ].join(';');

        this._onKey = (e) => {
            if (e.key === 'Escape') {
                this.close();
            }
        };
        window.addEventListener('keydown', this._onKey);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                this.expanded = true;
                this.frameStyle = [
                    'position:fixed',
                    `top:calc(50% - ${size / 2 + 24}px)`,
                    `left:calc(50% - ${size / 2}px)`,
                    `width:${size}px`,
                    'z-index:96',
                    'transition:top 0.42s cubic-bezier(0.22, 1, 0.36, 1), left 0.42s cubic-bezier(0.22, 1, 0.36, 1), width 0.42s cubic-bezier(0.22, 1, 0.36, 1)',
                ].join(';');
            });
        });
    },
    close() {
        if (! this.visible || this._closing) {
            return;
        }
        this._closing = true;
        this.expanded = false;
        if (this._onKey) {
            window.removeEventListener('keydown', this._onKey);
            this._onKey = null;
        }
        const thumb = this.$refs.thumb;
        if (thumb) {
            const rect = thumb.getBoundingClientRect();
            this.frameStyle = [
                'position:fixed',
                `top:${rect.top}px`,
                `left:${rect.left}px`,
                `width:${rect.width}px`,
                'z-index:96',
                'transition:top 0.32s cubic-bezier(0.4, 0, 0.2, 1), left 0.32s cubic-bezier(0.4, 0, 0.2, 1), width 0.32s cubic-bezier(0.4, 0, 0.2, 1)',
            ].join(';');
        }
        setTimeout(() => {
            this.visible = false;
            this.frameStyle = '';
            this._closing = false;
        }, 340);
    },
}));

/**
 * Count points 0 → target with ease-out (~700–900ms).
 * Optional earnedDelta shows a floating +N after settle.
 */
Alpine.data('loopCountUp', (target, duration = 800, earnedDelta = 0) => ({
    display: 0,
    earned: null,
    ripple: false,
    init() {
        const goal = Number(target) || 0;
        const earned = Number(earnedDelta) || 0;

        if (prefersReducedMotion() || goal <= 0) {
            this.display = goal;
            if (earned > 0) {
                this.flashEarned(earned);
            }
            return;
        }

        const start = performance.now();
        const tick = (now) => {
            const t = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - t, 3);
            this.display = Math.round(goal * eased);
            if (t < 1) {
                requestAnimationFrame(tick);
            } else {
                this.display = goal;
                if (earned > 0) {
                    this.flashEarned(earned);
                }
            }
        };
        requestAnimationFrame(tick);
    },
    flashEarned(amount) {
        this.earned = amount;
        this.ripple = true;
        setTimeout(() => {
            this.ripple = false;
        }, 900);
        setTimeout(() => {
            this.earned = null;
        }, 1200);
    },
    formatted() {
        return new Intl.NumberFormat().format(this.display);
    },
}));

/**
 * Living wallet shell — ambient liquid class + optional earn pulse.
 */
Alpine.data('loopLivingWallet', (earnedDelta = 0) => ({
    pulsing: false,
    init() {
        if (Number(earnedDelta) > 0 && ! prefersReducedMotion()) {
            this.pulsing = true;
            setTimeout(() => {
                this.pulsing = false;
            }, 1400);
        }
    },
}));

/**
 * Horizontal carousel: slow swipe + soft snap + light parallax.
 */
Alpine.data('loopParallaxCarousel', () => ({
    _target: 0,
    _current: 0,
    _raf: null,
    init() {
        this._target = this.$el.scrollLeft;
        this._current = this.$el.scrollLeft;
        this.$nextTick(() => this.refresh());

        if (prefersReducedMotion()) {
            this._onScroll = () => this.refresh();
            this.$el.addEventListener('scroll', this._onScroll, { passive: true });
            return;
        }

        this._onScroll = () => {
            if (! this._raf) {
                this._target = this.$el.scrollLeft;
                this._current = this.$el.scrollLeft;
            }
            this.refresh();
        };
        this.$el.addEventListener('scroll', this._onScroll, { passive: true });
        window.addEventListener('resize', this._onScroll, { passive: true });

        this._onWheel = (event) => {
            const horizontal = Math.abs(event.deltaX) > Math.abs(event.deltaY) || event.shiftKey;
            if (! horizontal && Math.abs(event.deltaY) < 2) {
                return;
            }
            if (! horizontal && this.$el.scrollWidth <= this.$el.clientWidth + 4) {
                return;
            }
            event.preventDefault();
            const delta = horizontal ? event.deltaX || event.deltaY : event.deltaY;
            this._target = Math.max(
                0,
                Math.min(this.$el.scrollWidth - this.$el.clientWidth, this._target + delta * 0.35)
            );
            this.lerp();
        };
        this.$el.addEventListener('wheel', this._onWheel, { passive: false });
    },
    destroy() {
        this.$el.removeEventListener('scroll', this._onScroll);
        window.removeEventListener('resize', this._onScroll);
        if (this._onWheel) {
            this.$el.removeEventListener('wheel', this._onWheel);
        }
        if (this._raf) {
            cancelAnimationFrame(this._raf);
        }
    },
    lerp() {
        if (this._raf) {
            return;
        }
        const tick = () => {
            this._current += (this._target - this._current) * 0.06;
            this.$el.scrollLeft = this._current;
            this.refresh();
            if (Math.abs(this._target - this._current) > 0.4) {
                this._raf = requestAnimationFrame(tick);
            } else {
                this.$el.scrollLeft = this._target;
                this._raf = null;
                this.snapSlow();
            }
        };
        this._raf = requestAnimationFrame(tick);
    },
    snapSlow() {
        const cards = [...this.$el.querySelectorAll('[data-loop-card]')];
        if (! cards.length) {
            return;
        }
        const mid = this.$el.scrollLeft + this.$el.clientWidth / 2;
        let best = cards[0];
        let bestDist = Infinity;
        cards.forEach((card) => {
            const center = card.offsetLeft + card.offsetWidth / 2;
            const dist = Math.abs(mid - center);
            if (dist < bestDist) {
                bestDist = dist;
                best = card;
            }
        });
        this._target = Math.max(0, best.offsetLeft - 8);
        if (Math.abs(this._target - this._current) > 1) {
            this.lerp();
        }
    },
    refresh() {
        const root = this.$el;
        const mid = root.scrollLeft + root.clientWidth / 2;
        root.querySelectorAll('[data-loop-card]').forEach((card) => {
            const center = card.offsetLeft + card.offsetWidth / 2;
            const dist = Math.abs(mid - center);
            const active = dist < card.offsetWidth * 0.55;
            card.classList.toggle('is-active', active);
            const media = card.querySelector('[data-loop-parallax]');
            if (media) {
                const shift = Math.max(-4, Math.min(4, (mid - center) * 0.015));
                media.style.transform = `translateX(${shift}px) scale(1.04)`;
                media.style.transition = 'transform 0.7s cubic-bezier(0.22, 1, 0.36, 1)';
            }
        });
    },
}));

/**
 * Reward unlock — one-shot glow when progress is ready.
 */
Alpine.data('loopUnlock', (ready = false) => ({
    unlocked: false,
    init() {
        if (ready && ! prefersReducedMotion()) {
            requestAnimationFrame(() => {
                this.unlocked = true;
            });
        } else if (ready) {
            this.unlocked = true;
        }
    },
}));

/**
 * Redeem ticket sheet — digital ticket with confirmation code.
 */
Alpine.data('loopRedeem', () => ({
    open: false,
    rewardName: '',
    rewardPts: '',
    hotline: '',
    businessName: '',
    code: '',
    show(payload = {}) {
        this.rewardName = payload.name || '';
        this.rewardPts = payload.pts || '';
        this.hotline = payload.hotline || '';
        this.businessName = payload.business || '';
        this.code = this.makeCode(payload.name || 'LOOP');
        this.open = true;
    },
    close() {
        this.open = false;
    },
    makeCode(seed) {
        const base = String(seed).replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 4) || 'LOOP';
        const n = Math.floor(1000 + Math.random() * 9000);
        return `${base}-${n}`;
    },
}));

/**
 * Viewport reveal — one fade/slide when section enters view.
 * Starts visible (no FOUC). Only below-fold sections briefly pending.
 */
Alpine.data('loopReveal', (delay = 0) => ({
    shown: true,
    init() {
        if (prefersReducedMotion()) {
            return;
        }
        const rect = this.$el.getBoundingClientRect();
        const inView = rect.top < window.innerHeight * 0.92 && rect.bottom > 0;
        if (inView) {
            return;
        }
        this.shown = false;
        this.$el.classList.add('is-pending');
        const io = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            this.shown = true;
                            this.$el.classList.remove('is-pending');
                        }, delay);
                        io.disconnect();
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
        );
        io.observe(this.$el);
    },
}));

/**
 * Branded page veil — orb expands, then navigate (uses $store.loopNav).
 */
Alpine.data('loopPageMotion', () => ({
    get transitioning() {
        return this.$store.loopNav.transitioning;
    },
    go(url, event) {
        this.$store.loopNav.go(url, event);
    },
}));

Alpine.start();
