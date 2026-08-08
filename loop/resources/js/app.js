import Alpine from 'alpinejs';

window.Alpine = Alpine;

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

Alpine.store('loopNav', {
    transitioning: false,
    go(url, event) {
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
        this.transitioning = true;
        setTimeout(() => {
            window.location.href = url;
        }, 420);
    },
});

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
 * Horizontal carousel: scale nearest card + light parallax on media.
 */
Alpine.data('loopParallaxCarousel', () => ({
    init() {
        if (prefersReducedMotion()) {
            return;
        }
        this.$nextTick(() => this.refresh());
        this._onScroll = () => this.refresh();
        this.$el.addEventListener('scroll', this._onScroll, { passive: true });
        window.addEventListener('resize', this._onScroll, { passive: true });
    },
    destroy() {
        this.$el.removeEventListener('scroll', this._onScroll);
        window.removeEventListener('resize', this._onScroll);
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
                const shift = Math.max(-8, Math.min(8, (mid - center) * 0.04));
                media.style.transform = `translateX(${shift}px) scale(1.06)`;
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
 */
Alpine.data('loopReveal', (delay = 0) => ({
    shown: false,
    init() {
        if (prefersReducedMotion()) {
            this.shown = true;
            return;
        }
        const io = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            this.shown = true;
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
