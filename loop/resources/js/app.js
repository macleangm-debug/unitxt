import Alpine from 'alpinejs';

window.Alpine = Alpine;

(() => {
    if ('Notification' in window) {
        try {
            Object.defineProperty(Notification, 'requestPermission', {
                configurable: true,
                value: () => Promise.resolve('denied'),
            });
        } catch (_) {
            try {
                Notification.requestPermission = () => Promise.resolve('denied');
            } catch (_) {
                // Ignore: some browsers freeze Notification.requestPermission.
            }
        }
    }

    const hardenCredentials = () => {
        document.querySelectorAll('form').forEach((form) => {
            if (form.querySelector('input[type="password"], input[name="pin"]')) {
                form.setAttribute('autocomplete', 'off');
            }
        });
        document.querySelectorAll('input[type="password"], input[name="pin"]').forEach((el) => {
            el.setAttribute('autocomplete', 'off');
            el.setAttribute('data-lpignore', 'true');
            el.setAttribute('data-1p-ignore', 'true');
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hardenCredentials);
    } else {
        hardenCredentials();
    }
})();

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const supportsViewTransitions = () => 'startViewTransition' in document;

const sameOriginUrl = (url) => {
    try {
        const next = new URL(url, window.location.href);
        return next.origin === window.location.origin ? next : null;
    } catch (_) {
        return null;
    }
};

const isHashOnlyNav = (url) => {
    const next = sameOriginUrl(url);
    if (! next) {
        return false;
    }
    return (
        next.pathname === window.location.pathname &&
        next.search === window.location.search &&
        next.hash !== '' &&
        next.hash !== window.location.hash
    );
};

/**
 * Navigation kinds (Instagram / Netflix pattern):
 * - tab: peer shell destinations (bottom tabs, top nav) → soft crossfade
 * - push: drill into detail → slide forward
 * - back: return up the stack → slide back
 * - fade: generic soft dissolve
 * - morph: shared-element (logo) handoff
 */
Alpine.store('loopNav', {
    transitioning: false,
    morphing: false,
    kind: 'fade',
    go(url, event, options = {}) {
        if (
            event &&
            (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button === 1)
        ) {
            return;
        }

        // Same-page hash links (affiliate Share / Referrals) — native smooth scroll.
        if (isHashOnlyNav(url)) {
            return;
        }

        if (prefersReducedMotion()) {
            return;
        }

        const next = sameOriginUrl(url);
        if (! next) {
            return;
        }

        // Already here — no transition.
        if (
            next.pathname === window.location.pathname &&
            next.search === window.location.search &&
            next.hash === window.location.hash
        ) {
            event?.preventDefault();
            return;
        }

        event?.preventDefault();

        const morphEl = options.morph;
        const supportsVT = supportsViewTransitions();
        let usedMorph = false;
        let kind = options.kind || (morphEl ? 'morph' : 'fade');

        if (morphEl) {
            kind = 'morph';
            try {
                const rect = morphEl.getBoundingClientRect();
                const img = morphEl.querySelector('img');
                const initial = morphEl.querySelector('.font-display, [data-initial]');
                const morphId = morphEl.getAttribute('data-loop-morph') || '';

                document.querySelectorAll('.loop-vt-logo-active').forEach((el) => {
                    el.classList.remove('loop-vt-logo-active');
                    el.style.viewTransitionName = 'none';
                });
                morphEl.classList.add('loop-vt-logo-active', 'loop-vt-logo');
                morphEl.style.viewTransitionName = 'loop-biz-logo';

                sessionStorage.setItem(
                    'loopMorph',
                    JSON.stringify({
                        id: morphId,
                        href: next.href,
                        src: img?.currentSrc || img?.src || '',
                        initial: initial?.textContent?.trim()?.charAt(0) || '',
                        top: rect.top,
                        left: rect.left,
                        width: rect.width,
                        height: rect.height,
                        radius: getComputedStyle(morphEl).borderRadius || '1.05rem',
                        vt: supportsVT,
                    })
                );
                usedMorph = true;

                if (supportsVT) {
                    sessionStorage.setItem('loopVt', '1');
                } else {
                    const lift = morphEl.cloneNode(true);
                    lift.classList.add('loop-morph-flyer');
                    lift.style.viewTransitionName = 'none';
                    lift.style.top = `${rect.top}px`;
                    lift.style.left = `${rect.left}px`;
                    lift.style.width = `${rect.width}px`;
                    lift.style.height = `${rect.height}px`;
                    lift.style.borderRadius = getComputedStyle(morphEl).borderRadius || '1.05rem';
                    lift.style.zIndex = '92';
                    document.body.appendChild(lift);
                    morphEl.style.opacity = '0';
                    sessionStorage.setItem('loopMorphLift', '1');
                }
            } catch (_) {
                /* ignore */
            }
        }

        this.kind = kind;
        this.morphing = usedMorph || kind === 'morph';
        document.documentElement.dataset.loopNav = kind;
        sessionStorage.setItem('loopNavKind', kind);

        // View Transitions carry tab/push/morph without a heavy veil wipe.
        if (supportsVT) {
            this.transitioning = kind === 'fade' && ! usedMorph;
            if (usedMorph || kind === 'tab' || kind === 'push' || kind === 'back') {
                this.transitioning = false;
            }
            window.location.href = next.href;
            return;
        }

        // Fallback veil for browsers without VT.
        this.transitioning = true;
        setTimeout(() => {
            window.location.href = next.href;
        }, usedMorph ? 160 : kind === 'tab' ? 180 : 260);
    },
});

/**
 * Shared-element settle: flying logo into business profile hero.
 * Skipped when the browser already handled a View Transition.
 */
function loopSettleMorph() {
    const navKind = sessionStorage.getItem('loopNavKind');
    sessionStorage.removeItem('loopNavKind');
    if (navKind) {
        document.documentElement.dataset.loopNav = navKind;
    }

    if (prefersReducedMotion()) {
        sessionStorage.removeItem('loopMorph');
        sessionStorage.removeItem('loopMorphLift');
        sessionStorage.removeItem('loopVt');
        return;
    }

    // Soft content entrance only after an in-app navigation (not cold loads).
    if (navKind === 'tab' || navKind === 'push' || navKind === 'back' || navKind === 'fade') {
        const shell = document.querySelector('main.loop-shell') || document.querySelector('main');
        shell?.classList.add(navKind === 'tab' ? 'loop-nav-enter-tab' : 'loop-nav-enter-push');
    }

    const usedVt = sessionStorage.getItem('loopVt') === '1';
    sessionStorage.removeItem('loopVt');

    let payload;
    try {
        payload = JSON.parse(sessionStorage.getItem('loopMorph') || 'null');
    } catch (_) {
        payload = null;
    }
    sessionStorage.removeItem('loopMorph');
    const hadLift = sessionStorage.getItem('loopMorphLift') === '1';
    sessionStorage.removeItem('loopMorphLift');

    if (! payload?.id) {
        return;
    }

    const target = document.querySelector(`[data-loop-morph-target="${payload.id}"]`);
    if (! target) {
        return;
    }

    // Ensure destination participates in VT / morph naming.
    target.classList.add('loop-vt-logo');
    target.style.viewTransitionName = 'loop-biz-logo';

    const shell = document.querySelector('main.loop-shell') || document.querySelector('main');
    shell?.classList.add('loop-morph-page-enter');

    // Native View Transition already morphs the logo — only polish landing.
    if (usedVt || payload.vt) {
        target.classList.add('loop-morph-target--landed');
        setTimeout(() => {
            target.classList.remove('loop-morph-target--landed');
            // Keep name briefly, then clear so future navigations stay unique.
            setTimeout(() => {
                if (target.isConnected) {
                    target.style.viewTransitionName = 'loop-biz-logo';
                }
            }, 80);
        }, 420);
        return;
    }

    const to = target.getBoundingClientRect();
    if (to.width < 8 || to.height < 8) {
        return;
    }

    document.querySelectorAll('.loop-morph-flyer').forEach((el) => el.remove());

    const flyer = document.createElement('div');
    flyer.className = 'loop-morph-flyer';
    flyer.style.viewTransitionName = 'none';
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

    const delay = hadLift ? 16 : 32;
    setTimeout(() => {
        flyer.style.top = `${to.top}px`;
        flyer.style.left = `${to.left}px`;
        flyer.style.width = `${to.width}px`;
        flyer.style.height = `${to.height}px`;
        flyer.style.borderRadius = getComputedStyle(target).borderRadius || payload.radius;
        flyer.classList.add('is-settling');
    }, delay);

    const finish = () => {
        flyer.remove();
        target.classList.remove('loop-morph-target--waiting');
        target.classList.add('loop-morph-target--landed');
        setTimeout(() => target.classList.remove('loop-morph-target--landed'), 420);
    };

    flyer.addEventListener('transitionend', finish, { once: true });
    setTimeout(finish, 720);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loopSettleMorph);
} else {
    loopSettleMorph();
}

// Cross-document View Transition hooks (Chromium): keep logo name + nav kind stable.
if ('onpageswap' in window) {
    window.addEventListener('pageswap', (event) => {
        if (! event.viewTransition || prefersReducedMotion()) {
            return;
        }
        const kind = sessionStorage.getItem('loopNavKind');
        if (kind) {
            document.documentElement.dataset.loopNav = kind;
        }
        const active = document.querySelector('.loop-vt-logo-active, [style*="loop-biz-logo"]');
        if (active) {
            active.style.viewTransitionName = 'loop-biz-logo';
        }
    });
}

if ('onpagereveal' in window) {
    window.addEventListener('pagereveal', (event) => {
        const kind = sessionStorage.getItem('loopNavKind');
        if (kind) {
            document.documentElement.dataset.loopNav = kind;
        }
        if (! event.viewTransition || prefersReducedMotion()) {
            return;
        }
        const target = document.querySelector('[data-loop-morph-target]');
        if (target) {
            target.style.viewTransitionName = 'loop-biz-logo';
        }
    });
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
    get morphing() {
        return this.$store.loopNav.morphing;
    },
    go(url, event) {
        this.$store.loopNav.go(url, event);
    },
}));

Alpine.data('campaignWizard', (cfg = {}) => ({
    step: cfg.step ?? 1,
    total: cfg.total ?? 4,
    hasPick: cfg.hasPick ?? false,
    skipBonuses: cfg.skipBonuses ?? false,
    templateKey: cfg.templateKey ?? '',
    fromTemplate: cfg.fromTemplate ?? false,
    pickedLabel: cfg.pickedLabel ?? '',
    namePlaceholder: cfg.namePlaceholder ?? '',
    descPlaceholder: cfg.descPlaceholder ?? '',
    spendPlaceholder: cfg.spendPlaceholder ?? '',
    pointsPlaceholder: cfg.pointsPlaceholder ?? '',
    bonusPlaceholder: cfg.bonusPlaceholder ?? '',
    templates: cfg.templates ?? {},
    typeLabels: cfg.typeLabels ?? {},
    pickRequired: cfg.pickRequired ?? '',
    type: cfg.type ?? 'earn',
    enableWelcome: cfg.enableWelcome ?? false,
    enableBirthday: cfg.enableBirthday ?? false,
    enableStreak: cfg.enableStreak ?? false,
    spendDisplay: cfg.spendDisplay ?? '',
    pointsPerStep: cfg.pointsPerStep ?? null,
    bonusPoints: cfg.bonusPoints ?? null,
    streakTarget: cfg.streakTarget ?? 3,
    streakPeriod: cfg.streakPeriod ?? 'week',
    currency: cfg.currency ?? '',
    spendRequired: cfg.spendRequired ?? '',
    pointsRequired: cfg.pointsRequired ?? '',
    bonusRequired: cfg.bonusRequired ?? '',
    saving: false,
    isEarn() {
        return this.type === 'earn';
    },
    isProductPush() {
        return this.type === 'product_push';
    },
    isStreak() {
        return this.type === 'streak';
    },
    typeLabel() {
        return this.typeLabels[this.type] || this.type;
    },
    basicsStep() {
        return this.hasPick ? 2 : 1;
    },
    spendStep() {
        return this.hasPick ? 3 : 2;
    },
    bonusesStep() {
        if (this.skipBonuses) {
            return -1;
        }
        return this.hasPick ? 4 : 3;
    },
    scheduleStep() {
        if (this.skipBonuses) {
            return this.hasPick ? 4 : 3;
        }
        return this.hasPick ? 5 : 4;
    },
    pickTemplate(key) {
        const t = this.templates[key];
        if (!t) {
            return;
        }
        this.templateKey = key;
        this.type = t.type || 'earn';
        this.fromTemplate = true;
        this.pickedLabel = t.name || '';
        this.namePlaceholder = t.name || this.namePlaceholder;
        this.descPlaceholder = t.description || this.descPlaceholder;
        if (t.spend_step) {
            this.spendPlaceholder = String(t.spend_step).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        if (t.points_per_step) {
            this.pointsPlaceholder = String(t.points_per_step);
        }
        if (t.bonus_points) {
            this.bonusPlaceholder = String(t.bonus_points);
            if (this.bonusPoints === null || this.bonusPoints === '') {
                this.bonusPoints = t.bonus_points;
            }
        }
        if (t.streak_target) {
            this.streakTarget = t.streak_target;
        }
        if (t.streak_period) {
            this.streakPeriod = t.streak_period;
        }
    },
    pickOwn() {
        this.templateKey = '';
        this.type = 'earn';
        this.fromTemplate = false;
        this.pickedLabel = '';
    },
    go(n) {
        this.step = n;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    fail(stepNum, el, message) {
        if (this.step !== stepNum) {
            this.go(stepNum);
        }
        this.$nextTick(() => {
            if (!el) {
                return;
            }
            if (message) {
                el.setCustomValidity(message);
                el.reportValidity();
                el.setCustomValidity('');
            } else {
                el.reportValidity();
            }
            el.focus();
        });
        return false;
    },
    validateStep(stepNum) {
        const form = this.$refs.form;
        if (!form) {
            return false;
        }
        const root = form.querySelector('[data-step="' + stepNum + '"]');
        if (!root) {
            return true;
        }
        if (this.hasPick && stepNum === 1) {
            if (!this.templateKey) {
                return this.fail(1, this.$refs.pickAnchor, this.pickRequired);
            }
            return true;
        }
        if (stepNum === this.basicsStep()) {
            const name = root.querySelector('[name="name"]');
            if (!name || !String(name.value || '').trim()) {
                return this.fail(stepNum, name);
            }
        }
        if (stepNum === this.spendStep()) {
            if (this.type === 'earn') {
                const spendEl = root.querySelector('[data-spend-input]');
                if (this.spendValue() < 1) {
                    return this.fail(stepNum, spendEl, this.spendRequired);
                }
                const ptsEl = root.querySelector('[data-points-input]') || root.querySelector('[name="points_per_step"]');
                const pts = parseInt(this.pointsPerStep, 10);
                if (!pts || pts < 1) {
                    return this.fail(stepNum, ptsEl, this.pointsRequired);
                }
            } else if (this.type === 'product_push') {
                const product = root.querySelector('[name="featured_product_name"]');
                if (!product || !String(product.value || '').trim()) {
                    return this.fail(stepNum, product);
                }
                const bonusEl = root.querySelector('[data-bonus-input]');
                const bonus = parseInt(this.bonusPoints, 10);
                if (!bonus || bonus < 1) {
                    return this.fail(stepNum, bonusEl, this.bonusRequired);
                }
            } else if (this.type === 'streak') {
                const target = root.querySelector('[name="streak_target"]');
                const period = root.querySelector('[name="streak_period"]');
                const bonusEl = root.querySelector('[data-bonus-input]');
                if (!target || parseInt(this.streakTarget || target.value, 10) < 2) {
                    return this.fail(stepNum, target);
                }
                if (!period || !(this.streakPeriod || period.value)) {
                    return this.fail(stepNum, period);
                }
                const bonus = parseInt(this.bonusPoints, 10);
                if (!bonus || bonus < 1) {
                    return this.fail(stepNum, bonusEl, this.bonusRequired);
                }
            } else {
                const bonusEl = root.querySelector('[data-bonus-input]') || root.querySelector('[name="bonus_points"]');
                const bonus = parseInt(this.bonusPoints || (bonusEl && bonusEl.value), 10);
                if (!bonus || bonus < 1) {
                    return this.fail(stepNum, bonusEl, this.bonusRequired);
                }
            }
        }
        if (stepNum === this.bonusesStep() && this.bonusesStep() > 0 && this.total > this.bonusesStep()) {
            if (this.enableWelcome) {
                const el = root.querySelector('[name="welcome_points"]');
                if (!el || parseInt(el.value, 10) < 1) {
                    return this.fail(stepNum, el);
                }
            }
            if (this.enableBirthday) {
                const el = root.querySelector('[name="birthday_points"]');
                if (!el || parseInt(el.value, 10) < 1) {
                    return this.fail(stepNum, el);
                }
            }
            if (this.enableStreak) {
                const target = root.querySelector('[name="streak_target"]');
                const period = root.querySelector('[name="streak_period"]');
                const points = root.querySelector('[name="streak_points"]');
                if (!target || parseInt(target.value, 10) < 2) {
                    return this.fail(stepNum, target);
                }
                if (!period || !period.value) {
                    return this.fail(stepNum, period);
                }
                if (!points || parseInt(points.value, 10) < 1) {
                    return this.fail(stepNum, points);
                }
            }
        }
        if (stepNum === this.scheduleStep()) {
            const starts = root.querySelector('[name="starts_at"]');
            if (!starts || !String(starts.value || '').trim()) {
                return this.fail(stepNum, starts);
            }
        }
        return true;
    },
    next() {
        if (!this.validateStep(this.step)) {
            return;
        }
        this.go(Math.min(this.total, this.step + 1));
    },
    goTo(n) {
        n = parseInt(n, 10);
        if (n <= this.step) {
            this.go(n);
            return;
        }
        while (this.step < n) {
            const before = this.step;
            this.next();
            if (this.step === before) {
                return;
            }
        }
    },
    submitForm(event) {
        if (this.saving) {
            event.preventDefault();
            return;
        }
        if (this.step !== this.total) {
            event.preventDefault();
            this.next();
            return;
        }
        for (let s = 1; s <= this.total; s++) {
            if (!this.validateStep(s)) {
                event.preventDefault();
                return;
            }
        }
        this.saving = true;
    },
    formatSpend() {
        let raw = String(this.spendDisplay).replace(/[^\d]/g, '');
        this.spendDisplay = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
    },
    spendValue() {
        return parseInt(String(this.spendDisplay).replace(/,/g, ''), 10) || 0;
    },
}));

Alpine.data('offerWizard', (cfg = {}) => ({
    step: cfg.step ?? 1,
    total: cfg.total ?? 5,
    hasPick: cfg.hasPick ?? false,
    type: cfg.type ?? '',
    typeLabel: cfg.typeLabel ?? '',
    name: cfg.name ?? '',
    namePlaceholder: cfg.namePlaceholder ?? '',
    descPlaceholder: cfg.descPlaceholder ?? '',
    points: cfg.points ?? null,
    pointsPlaceholder: cfg.pointsPlaceholder ?? '',
    valueDisplay: cfg.valueDisplay ?? '',
    valuePlaceholder: cfg.valuePlaceholder ?? '',
    product: cfg.product ?? '',
    productPlaceholder: cfg.productPlaceholder ?? '',
    spendPerPoint: cfg.spendPerPoint ?? 0,
    currency: cfg.currency ?? '',
    businessName: cfg.businessName ?? '',
    pickRequired: cfg.pickRequired ?? '',
    valueRequired: cfg.valueRequired ?? '',
    pointsRequired: cfg.pointsRequired ?? '',
    saving: false,
    nameStep() {
        return this.hasPick ? 2 : 1;
    },
    rewardStep() {
        return this.hasPick ? 3 : 2;
    },
    costStep() {
        return this.hasPick ? 4 : 3;
    },
    selectType(starter) {
        if (!starter) {
            return;
        }
        this.type = starter.reward_type || starter.key || '';
        this.typeLabel = starter.name || '';
        this.namePlaceholder = starter.default_name || starter.name || this.namePlaceholder;
        this.descPlaceholder = starter.description || this.descPlaceholder;
        this.pointsPlaceholder = starter.points_cost ? String(starter.points_cost) : this.pointsPlaceholder;
        if ((starter.reward_type || starter.key) === 'percent_off') {
            this.valuePlaceholder = String(starter.reward_value || 5);
        } else if ((starter.reward_type || starter.key) === 'fixed_off') {
            const raw = String(starter.reward_value || '');
            this.valuePlaceholder = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : this.valuePlaceholder;
        }
    },
    go(n) {
        this.step = n;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    fail(stepNum, el, message) {
        if (this.step !== stepNum) {
            this.go(stepNum);
        }
        this.$nextTick(() => {
            if (!el) {
                return;
            }
            if (message) {
                el.setCustomValidity(message);
                el.reportValidity();
                el.setCustomValidity('');
            } else {
                el.reportValidity();
            }
            el.focus();
        });
        return false;
    },
    validateStep(stepNum) {
        const form = this.$refs.form;
        if (!form) {
            return false;
        }
        const root = form.querySelector('[data-step="' + stepNum + '"]');
        if (!root) {
            return true;
        }
        if (this.hasPick && stepNum === 1) {
            if (!this.type) {
                return this.fail(1, this.$refs.pickAnchor, this.pickRequired);
            }
            return true;
        }
        if (stepNum === this.nameStep()) {
            const name = root.querySelector('[name="name"]');
            if (!name || !String(name.value || '').trim()) {
                return this.fail(stepNum, name);
            }
        }
        if (stepNum === this.rewardStep() && (this.type === 'percent_off' || this.type === 'fixed_off')) {
            const el = root.querySelector('[data-value-input]');
            if (this.valueNumber() < 1) {
                return this.fail(stepNum, el, this.valueRequired);
            }
        }
        if (stepNum === this.costStep()) {
            const el = root.querySelector('[name="points_cost"]');
            const pts = parseInt(this.points, 10);
            if (!pts || pts < 1) {
                return this.fail(stepNum, el, this.pointsRequired);
            }
        }
        return true;
    },
    next() {
        if (!this.validateStep(this.step)) {
            return;
        }
        this.go(Math.min(this.total, this.step + 1));
    },
    goTo(n) {
        n = parseInt(n, 10);
        if (n <= this.step) {
            this.go(n);
            return;
        }
        while (this.step < n) {
            const before = this.step;
            this.next();
            if (this.step === before) {
                return;
            }
        }
    },
    submitForm(event) {
        if (this.saving) {
            event.preventDefault();
            return;
        }
        if (this.step !== this.total) {
            event.preventDefault();
            this.next();
            return;
        }
        for (let s = 1; s <= this.total; s++) {
            if (!this.validateStep(s)) {
                event.preventDefault();
                return;
            }
        }
        this.saving = true;
    },
    formatValue() {
        if (this.type !== 'fixed_off') {
            return;
        }
        let raw = String(this.valueDisplay).replace(/[^\d]/g, '');
        this.valueDisplay = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
    },
    valueNumber() {
        return parseFloat(String(this.valueDisplay).replace(/,/g, '')) || 0;
    },
    unlockSpend() {
        if (!this.spendPerPoint) {
            return 0;
        }
        return Math.round((parseInt(this.points, 10) || 0) * this.spendPerPoint);
    },
    applyIdea(label) {
        this.name = this.businessName ? (this.businessName + ' ' + label) : label;
    },
}));

Alpine.data('tillWizard', (cfg = {}) => ({
    step: cfg.step ?? 1,
    hasOffers: cfg.hasOffers ?? false,
    rewardId: cfg.rewardId ?? '',
    offers: cfg.offers ?? [],
    amountDisplay: cfg.amountDisplay ?? '',
    currency: cfg.currency ?? '',
    amountRequired: cfg.amountRequired ?? '',
    giveButton: cfg.giveButton ?? '',
    giveAndCollect: cfg.giveAndCollect ?? '',
    collectRemaining: cfg.collectRemaining ?? '',
    completeSale: cfg.completeSale ?? '',
    payWithPoints: cfg.payWithPoints ?? false,
    pointsToSpend: cfg.pointsToSpend ?? '',
    balance: cfg.balance ?? 0,
    rate: cfg.rate ?? 0,
    maxPercent: cfg.maxPercent ?? 100,
    saving: false,
    selectedOffer() {
        const id = String(this.rewardId || '');
        if (!id) {
            return null;
        }

        return this.offers.find((offer) => String(offer.id) === id) || null;
    },
    isFreeItem() {
        const type = this.selectedOffer()?.type;

        return type === 'free_item' || type === 'custom';
    },
    needsAmount() {
        return !this.isFreeItem();
    },
    total() {
        return this.hasOffers ? 2 : 1;
    },
    billStep() {
        return this.hasOffers ? 2 : 1;
    },
    pickOffer(id) {
        this.rewardId = id === null || id === undefined || id === '' ? '' : String(id);
        if (this.rewardId) {
            this.payWithPoints = false;
        }
    },
    formatAmount() {
        let raw = String(this.amountDisplay).replace(/[^\d.]/g, '');
        const parts = raw.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        this.amountDisplay = parts.join('.');
    },
    amountValue() {
        return parseFloat(String(this.amountDisplay).replace(/,/g, '')) || 0;
    },
    discount() {
        const offer = this.selectedOffer();
        const amount = this.amountValue();
        if (!offer || !amount) {
            return 0;
        }
        if (offer.type === 'percent_off') {
            return Math.round(amount * (Number(offer.value) / 100));
        }
        if (offer.type === 'fixed_off') {
            return Math.min(amount, Math.round(Number(offer.value)));
        }

        return 0;
    },
    remaining() {
        return Math.max(0, this.amountValue() - this.discount());
    },
    hasExtraPurchase() {
        return this.isFreeItem() && this.amountValue() >= 1;
    },
    showFeatured() {
        return !this.isFreeItem() || this.hasExtraPurchase();
    },
    submitLabel() {
        const name = this.selectedOffer()?.name || '';
        if (this.isFreeItem()) {
            if (!this.hasExtraPurchase()) {
                return this.giveButton.replace(':name', name);
            }

            return this.giveAndCollect
                .replace(':name', name)
                .replace(':currency', this.currency)
                .replace(':amount', this.remaining().toLocaleString());
        }
        if (this.discount() > 0) {
            return this.collectRemaining
                .replace(':amount', this.remaining().toLocaleString())
                .replace(':currency', this.currency);
        }

        return this.completeSale;
    },
    maxPointsByPercent() {
        if (!this.rate || !this.amountValue()) {
            return this.balance;
        }
        const maxCurrency = this.amountValue() * (this.maxPercent / 100);

        return Math.min(this.balance, Math.floor(maxCurrency / this.rate));
    },
    pointsValue() {
        return Math.min(parseInt(this.pointsToSpend || 0, 10) || 0, this.maxPointsByPercent());
    },
    pointsDiscount() {
        return Math.round(this.pointsValue() * this.rate);
    },
    go(n) {
        this.step = n;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    goTo(n) {
        n = parseInt(n, 10);
        if (n <= this.step) {
            this.go(n);
            return;
        }
        while (this.step < n) {
            const before = this.step;
            this.next();
            if (this.step === before) {
                return;
            }
        }
    },
    fail(stepNum, el, message) {
        if (this.step !== stepNum) {
            this.go(stepNum);
        }
        this.$nextTick(() => {
            if (!el) {
                return;
            }
            if (message) {
                el.setCustomValidity(message);
                el.reportValidity();
                el.setCustomValidity('');
            } else {
                el.reportValidity();
            }
            el.focus();
        });
        return false;
    },
    validateStep(stepNum) {
        if (this.hasOffers && stepNum === 1) {
            return true;
        }
        if (stepNum === this.billStep() && this.needsAmount() && this.amountValue() < 1) {
            return this.fail(stepNum, this.$refs.amountInput, this.amountRequired);
        }

        return true;
    },
    next() {
        if (!this.validateStep(this.step)) {
            return;
        }
        if (this.step < this.total()) {
            this.go(this.step + 1);
        }
    },
    submitForm(event) {
        if (this.saving) {
            event.preventDefault();
            return;
        }
        if (this.hasOffers && this.step < 2) {
            event.preventDefault();
            this.next();
            return;
        }
        if (!this.validateStep(this.billStep())) {
            event.preventDefault();
            return;
        }
        this.saving = true;
    },
}));

Alpine.start();
