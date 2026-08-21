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

    const numericKind = (el) => {
        if (! (el instanceof HTMLInputElement) || el.disabled || el.readOnly || el.type === 'hidden') {
            return null;
        }
        if (el.dataset.numeric === 'off') {
            return null;
        }
        const name = (el.name || '').toLowerCase();
        const mode = (el.getAttribute('inputmode') || '').toLowerCase();
        if (name === 'pin' || name === 'pin_confirmation') {
            return 'digits';
        }
        if (name === 'phone' || name === 'payout_phone' || mode === 'tel') {
            return 'phone';
        }
        if (mode === 'decimal' || (el.step && String(el.step).includes('.'))) {
            return 'decimal';
        }
        if (el.type === 'number') {
            return 'digits';
        }
        if (mode === 'numeric' || el.dataset.numeric) {
            return el.dataset.numeric === 'decimal' ? 'decimal' : 'amount';
        }

        return null;
    };

    const allowedRe = {
        digits: /[0-9]/,
        phone: /[0-9+]/,
        decimal: /[0-9.]/,
        amount: /[0-9.,]/,
    };

    const sanitizeNumeric = (value, kind) => {
        const raw = String(value || '');
        if (kind === 'phone') {
            return raw.replace(/[^\d+]/g, '');
        }
        if (kind === 'decimal') {
            const next = raw.replace(/[^\d.]/g, '');
            const parts = next.split('.');

            return parts.shift() + (parts.length ? '.' + parts.join('') : '');
        }
        if (kind === 'amount') {
            return raw.replace(/[^\d,]/g, '');
        }

        return raw.replace(/\D+/g, '');
    };

    const lockNumericEntry = () => {
        document.querySelectorAll('input[name="phone"], input[name="payout_phone"]').forEach((el) => {
            if (! el.getAttribute('inputmode')) {
                el.setAttribute('inputmode', 'numeric');
            }
            if (! el.getAttribute('pattern')) {
                el.setAttribute('pattern', '[0-9]*');
            }
        });
        document.querySelectorAll('input[name="pin"], input[name="pin_confirmation"]').forEach((el) => {
            el.setAttribute('inputmode', 'numeric');
            el.setAttribute('pattern', '[0-9]*');
            if (el.type === 'password') {
                el.type = 'text';
                el.classList.add('loop-secret');
            }
        });
        document.querySelectorAll('input[type="number"]').forEach((el) => {
            if (! el.getAttribute('inputmode')) {
                const decimal = el.step && String(el.step).includes('.');
                el.setAttribute('inputmode', decimal ? 'decimal' : 'numeric');
            }
        });
    };

    const onNumericBeforeInput = (event) => {
        const el = event.target;
        const kind = numericKind(el);
        if (! kind || ! event.data) {
            return;
        }
        const allowed = [...event.data].every((ch) => allowedRe[kind].test(ch));
        if (! allowed) {
            event.preventDefault();
        }
    };

    const onNumericInput = (event) => {
        const el = event.target;
        const kind = numericKind(el);
        if (! kind) {
            return;
        }
        const next = sanitizeNumeric(el.value, kind);
        if (next !== el.value) {
            el.value = next;
        }
    };

    const onNumericPaste = (event) => {
        const el = event.target;
        const kind = numericKind(el);
        if (! kind) {
            return;
        }
        const text = event.clipboardData?.getData('text') || '';
        const cleaned = sanitizeNumeric(text, kind);
        if (cleaned === text) {
            return;
        }
        event.preventDefault();
        const start = el.selectionStart ?? el.value.length;
        const end = el.selectionEnd ?? el.value.length;
        el.value = sanitizeNumeric(el.value.slice(0, start) + cleaned + el.value.slice(end), kind);
        el.dispatchEvent(new Event('input', { bubbles: true }));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            hardenCredentials();
            lockNumericEntry();
        });
    } else {
        hardenCredentials();
        lockNumericEntry();
    }
    document.addEventListener('beforeinput', onNumericBeforeInput);
    document.addEventListener('input', onNumericInput);
    document.addEventListener('paste', onNumericPaste);
})();

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const supportsViewTransitions = () => 'startViewTransition' in document;

const sameOriginUrl = (url) => {
    try {
        const next = new URL(url, window.location.href);
        if (next.protocol !== 'http:' && next.protocol !== 'https:') {
            return null;
        }
        return next.origin === window.location.origin ? next : null;
    } catch (_) {
        return null;
    }
};

const showLoopSkeleton = () => {
    const root = document.documentElement;
    root.classList.add('loop-js');
    root.classList.remove('loop-ready');
    document.body?.setAttribute('aria-busy', 'true');
    try {
        sessionStorage.setItem('loopNavPending', '1');
    } catch (_) {
        /* ignore */
    }
};

const markLoopReady = () => {
    const root = document.documentElement;
    if (root.classList.contains('loop-ready')) {
        return;
    }
    root.classList.add('loop-ready');
    root.classList.remove('loop-nav-pending');
    document.body?.removeAttribute('aria-busy');
    try {
        sessionStorage.removeItem('loopNavPending');
    } catch (_) {
        /* ignore */
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
    navigating: false,
    kind: 'fade',
    go(url, event, options = {}) {
        if (this.navigating) {
            event?.preventDefault();
            return;
        }

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

        // Public/guest pages opt out: native navigation, no skeleton flash.
        if (document.documentElement.classList.contains('loop-no-skeleton')) {
            return;
        }

        if (prefersReducedMotion()) {
            showLoopSkeleton();
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
        this.navigating = true;
        document.documentElement.dataset.loopNav = kind;
        sessionStorage.setItem('loopNavKind', kind);

        showLoopSkeleton();
        this.transitioning = false;
        window.location.href = next.href;
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
 * Loop-branded camera scanner for member wallet QR on Sale.
 */
Alpine.data('loopQrScanner', (cfg = {}) => ({
    scanning: false,
    status: '',
    error: '',
    stream: null,
    raf: null,
    detector: null,
    scanningLabel: cfg.scanningLabel || 'Scanning…',
    secureError: cfg.secureError || '',
    cameraError: cfg.cameraError || '',
    unrecognized: cfg.unrecognized || '',
    async open() {
        this.error = '';
        this.status = '';
        this.scanning = true;
        await this.$nextTick();
        try {
            if (! window.isSecureContext && location.hostname !== 'localhost') {
                throw new Error('secure');
            }
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } },
                audio: false,
            });
            const video = this.$refs.video;
            video.srcObject = this.stream;
            await video.play();
            this.status = this.scanningLabel;
            if ('BarcodeDetector' in window) {
                this.detector = new BarcodeDetector({ formats: ['qr_code'] });
                this.tick();
            }
        } catch (err) {
            this.error = err?.message === 'secure' ? this.secureError : this.cameraError;
        }
    },
    async tick() {
        if (! this.scanning || ! this.detector) {
            return;
        }
        try {
            const codes = await this.detector.detect(this.$refs.video);
            if (codes?.length) {
                this.handlePayload(codes[0].rawValue || '');
                return;
            }
        } catch (_) {
            /* keep scanning */
        }
        this.raf = requestAnimationFrame(() => this.tick());
    },
    handlePayload(raw) {
        const text = String(raw || '').trim();
        if (! text) {
            return;
        }
        let dial = '';
        let phone = '';
        try {
            const url = new URL(text, window.location.origin);
            const scan = url.searchParams.get('scan') || '';
            if (scan.includes('|')) {
                [dial, phone] = scan.split('|');
            }
        } catch (_) {
            /* not a URL */
        }
        if (! phone && text.includes('|')) {
            [dial, phone] = text.split('|');
        }
        if (! phone && /^\+?\d{8,15}$/.test(text.replace(/\s+/g, ''))) {
            phone = text.replace(/\D+/g, '').slice(-9);
        }
        phone = String(phone || '').replace(/\D+/g, '');
        if (! phone) {
            this.error = this.unrecognized;
            this.raf = requestAnimationFrame(() => this.tick());
            return;
        }
        const form = this.$root?.closest?.('form') || this.$el.closest('form');
        const phoneInput = form?.querySelector('input[name="phone"]');
        const dialInput = form?.querySelector('input[name="country_code"]');
        if (phoneInput) {
            phoneInput.value = phone;
            phoneInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (dial && dialInput) {
            dialInput.value = dial.startsWith('+') ? dial : `+${dial}`;
        }
        this.close();
        form?.requestSubmit?.();
    },
    close() {
        this.scanning = false;
        if (this.raf) {
            cancelAnimationFrame(this.raf);
            this.raf = null;
        }
        if (this.stream) {
            this.stream.getTracks().forEach((t) => t.stop());
            this.stream = null;
        }
        const video = this.$refs.video;
        if (video) {
            video.srcObject = null;
        }
    },
    destroy() {
        this.close();
    },
}));

/**
 * Count from → target with ease-out (~700–900ms).
 * Default from is 0 (heroes). Pass startFrom for 340 → 364 till moments.
 * Optional earnedDelta shows a floating +N after settle.
 */
Alpine.data('loopCountUp', (target, duration = 800, earnedDelta = 0, startFrom = 0) => ({
    display: Number(startFrom) || 0,
    earned: null,
    ripple: false,
    init() {
        const goal = Number(target) || 0;
        const from = Number(startFrom) || 0;
        const earned = Number(earnedDelta) || 0;

        if (prefersReducedMotion() || goal === from || (from === 0 && goal <= 0)) {
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
            this.display = Math.round(from + (goal - from) * eased);
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
 * Horizontal carousel: native swipe + light parallax.
 * Never hijack the wheel — that turned page-scroll into a sideways snap and hid copy.
 */
Alpine.data('loopParallaxCarousel', () => ({
    init() {
        this._onScroll = () => this.refresh();
        this.$nextTick(() => this.refresh());
        this.$el.addEventListener('scroll', this._onScroll, { passive: true });
        window.addEventListener('resize', this._onScroll, { passive: true });
    },
    destroy() {
        this.$el.removeEventListener('scroll', this._onScroll);
        window.removeEventListener('resize', this._onScroll);
    },
    refresh() {
        if (prefersReducedMotion()) {
            return;
        }
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
    persistKey: cfg.persistKey ?? 'loop.campaignWizard',
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
    init() {
        if (!this.persistKey) {
            return;
        }
        let saved = null;
        try {
            saved = JSON.parse(sessionStorage.getItem(this.persistKey) || 'null');
        } catch (e) {
            saved = null;
        }
        const params = new URLSearchParams(window.location.search);
        const urlStep = parseInt(params.get('step') || '', 10);
        if (saved && typeof saved === 'object') {
            if (saved.step) this.step = saved.step;
            if (saved.type) this.type = saved.type;
            if (saved.templateKey) this.templateKey = saved.templateKey;
        }
        if (urlStep >= 1) this.step = urlStep;
        this.syncCampaignUrl();
        this.persistCampaign();
    },
    persistCampaign() {
        if (!this.persistKey) {
            return;
        }
        try {
            sessionStorage.setItem(this.persistKey, JSON.stringify({
                step: this.step,
                type: this.type,
                templateKey: this.templateKey,
            }));
        } catch (e) {}
    },
    syncCampaignUrl() {
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('step', String(this.step));
            window.history.replaceState({}, '', url);
        } catch (e) {}
    },
    go(n) {
        this.step = n;
        this.syncCampaignUrl();
        this.persistCampaign();
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
        try {
            sessionStorage.removeItem(this.persistKey);
        } catch (e) {}
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
    persistKey: cfg.persistKey ?? '',
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
    productRequired: cfg.productRequired ?? '',
    spendPerPoint: cfg.spendPerPoint ?? 0,
    currency: cfg.currency ?? '',
    businessName: cfg.businessName ?? '',
    pickRequired: cfg.pickRequired ?? '',
    valueRequired: cfg.valueRequired ?? '',
    pointsRequired: cfg.pointsRequired ?? '',
    saving: false,
    init() {
        if (!this.persistKey) {
            return;
        }
        let saved = null;
        try {
            saved = JSON.parse(sessionStorage.getItem(this.persistKey) || 'null');
        } catch (e) {
            saved = null;
        }
        const params = new URLSearchParams(window.location.search);
        const urlStep = parseInt(params.get('step') || '', 10);
        const urlType = params.get('reward_type') || '';
        if (saved && typeof saved === 'object') {
            if (saved.type) this.type = saved.type;
            if (saved.typeLabel) this.typeLabel = saved.typeLabel;
            if (saved.name) this.name = saved.name;
            if (saved.points != null) this.points = saved.points;
            if (saved.valueDisplay) this.valueDisplay = saved.valueDisplay;
            if (saved.product) this.product = saved.product;
            if (saved.step) this.step = saved.step;
        }
        if (urlType) this.type = urlType;
        if (urlStep >= 1) this.step = urlStep;
        this.step = Math.max(1, Math.min(this.step, this.totalSteps()));
        this.syncUrl();
        this.persist();
    },
    persist() {
        if (!this.persistKey) {
            return;
        }
        try {
            sessionStorage.setItem(this.persistKey, JSON.stringify({
                step: this.step,
                type: this.type,
                typeLabel: this.typeLabel,
                name: this.name,
                points: this.points,
                valueDisplay: this.valueDisplay,
                product: this.product,
            }));
        } catch (e) {}
    },
    clearPersist() {
        if (!this.persistKey) {
            return;
        }
        try {
            sessionStorage.removeItem(this.persistKey);
        } catch (e) {}
    },
    isFreeItem() {
        return this.type === 'free_item';
    },
    hideLimits(n) {
        return this.isFreeItem() && Number(n) === this.limitsStep();
    },
    limitsStep() {
        return this.hasPick ? 5 : 4;
    },
    totalSteps() {
        return this.isFreeItem() ? this.costStep() : (this.hasPick ? 5 : 4);
    },
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
        this.persist();
    },
    syncUrl() {
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('step', String(this.step));
            if (this.type) {
                url.searchParams.set('reward_type', this.type);
            }
            window.history.replaceState({}, '', url);
        } catch (e) {}
    },
    go(n) {
        this.step = n;
        this.syncUrl();
        this.persist();
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
        if (stepNum === this.rewardStep() && this.isFreeItem()) {
            const el = root.querySelector('[name="product_name"]');
            if (!el || !String(this.product || el.value || '').trim()) {
                return this.fail(stepNum, el, this.productRequired);
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
        this.go(Math.min(this.totalSteps(), this.step + 1));
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
        if (this.step !== this.totalSteps()) {
            event.preventDefault();
            this.next();
            return;
        }
        for (let s = 1; s <= this.totalSteps(); s++) {
            if (!this.validateStep(s)) {
                event.preventDefault();
                return;
            }
        }
        this.clearPersist();
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
        return true;
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

Alpine.data('billingPayConfirm', (cfg = {}) => ({
    months: Number(cfg.months || 1),
    open: false,
    form: null,
    title: '',
    body: '',
    discounts: cfg.discounts || { 1: 0, 3: 8, 6: 15, 12: 25 },
    priceLabel(monthly, currency) {
        const months = Number(this.months) || 1;
        const discount = Number(this.discounts[months] || 0);
        const amount = Math.round(monthly * months * (100 - discount) / 100);
        return `${currency} ${amount.toLocaleString()} / ${months} ${cfg.monthsLabel || 'mo'}`;
    },
    ask(event, planName, monthly, currency) {
        event.preventDefault();
        this.form = event.target;
        this.title = cfg.confirmTitle || planName;
        this.body = `${planName} · ${this.priceLabel(monthly, currency)}`;
        this.open = true;
    },
    confirm() {
        this.open = false;
        if (this.form) {
            this.form.submit();
        }
    },
}));

Alpine.data('memberMessageWizard', (cfg = {}) => ({
    audience: 'all',
    body: '',
    price: cfg.price || 30,
    currency: cfg.currency || 'TZS',
    memberCount: cfg.memberCount || 0,
}));

Alpine.data('affiliateApplyWizard', (cfg = {}) => ({
    step: Number(cfg.step) || 1,
    country: cfg.country || 'TZ',
    dials: cfg.dials || {},
    go(n) {
        this.step = Math.max(1, Math.min(3, Number(n) || 1));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    next() {
        if (!this.validateStep(this.step)) {
            return;
        }
        this.go(this.step + 1);
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
        const fields = root.querySelectorAll('input, select, textarea');
        for (const el of fields) {
            if (el.disabled) {
                continue;
            }
            if (el.type === 'hidden' && ! el.hasAttribute('required')) {
                continue;
            }
            if (typeof el.reportValidity === 'function' && !el.reportValidity()) {
                el.focus();
                return false;
            }
        }
        return true;
    },
}));

Alpine.data('memberRegisterWizard', (cfg = {}) => ({
    step: cfg.step ?? 1,
    persistKey: 'loop.memberRegister',
    maxStep: cfg.total ?? 3,
    init() {
        let saved = null;
        try {
            saved = JSON.parse(sessionStorage.getItem(this.persistKey) || 'null');
        } catch (e) {
            saved = null;
        }
        const params = new URLSearchParams(window.location.search);
        const urlStep = parseInt(params.get('step') || '', 10);
        if (cfg.force) {
            this.step = cfg.step ?? 1;
        } else {
            if (saved && saved.step) {
                this.step = saved.step;
            }
            if (urlStep >= 1 && urlStep <= this.maxStep) {
                this.step = urlStep;
            }
        }
        this.syncUrl();
        this.persist();
    },
    persist() {
        try {
            sessionStorage.setItem(this.persistKey, JSON.stringify({ step: this.step }));
        } catch (e) {}
    },
    syncUrl() {
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('step', String(this.step));
            window.history.replaceState({}, '', url);
        } catch (e) {}
    },
    totalSteps() {
        return this.maxStep;
    },
    go(n) {
        this.step = Math.max(1, Math.min(this.maxStep, parseInt(n, 10) || 1));
        this.syncUrl();
        this.persist();
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
    next() {
        if (!this.validateStep(this.step)) {
            return;
        }
        this.go(Math.min(this.maxStep, this.step + 1));
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
        const fields = root.querySelectorAll('input, select, textarea');
        for (const el of fields) {
            if (typeof el.reportValidity === 'function' && !el.reportValidity()) {
                el.focus();
                return false;
            }
        }
        const pin = root.querySelector('[name="pin"]');
        const confirm = root.querySelector('[name="pin_confirmation"]');
        if (pin && confirm && pin.value !== confirm.value) {
            confirm.setCustomValidity(confirm.validationMessage || 'PIN');
            confirm.reportValidity();
            confirm.setCustomValidity('');
            return false;
        }
        return true;
    },
    submitForm(event) {
        if (this.step !== this.maxStep) {
            event.preventDefault();
            this.next();
            return;
        }
        if (!this.validateStep(this.step)) {
            event.preventDefault();
        }
        try {
            sessionStorage.removeItem(this.persistKey);
        } catch (e) {}
    },
}));

Alpine.data('articlePreview', (cfg = {}) => ({
    lang: 'en',
    title_en: cfg.title_en || '',
    title_sw: cfg.title_sw || '',
    excerpt_en: cfg.excerpt_en || '',
    excerpt_sw: cfg.excerpt_sw || '',
    body_en: cfg.body_en || '',
    body_sw: cfg.body_sw || '',
    image: cfg.image || '',
    title() {
        const primary = this.lang === 'sw' ? this.title_sw : this.title_en;
        const fallback = this.lang === 'sw' ? this.title_en : this.title_sw;
        return primary || fallback || '';
    },
    excerpt() {
        const primary = this.lang === 'sw' ? this.excerpt_sw : this.excerpt_en;
        const fallback = this.lang === 'sw' ? this.excerpt_en : this.excerpt_sw;
        return primary || fallback || '';
    },
    bodyHtml() {
        const primary = this.lang === 'sw' ? this.body_sw : this.body_en;
        const fallback = this.lang === 'sw' ? this.body_en : this.body_sw;
        const raw = String(primary || fallback || '').trim();
        if (! raw) {
            return '';
        }
        return raw.split(/\n{2,}/).map((block) => {
            const safe = block
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br>');
            return `<p class="mb-3 last:mb-0">${safe}</p>`;
        }).join('');
    },
}));

document.addEventListener('click', (event) => {
    const anchor = event.target.closest?.('a[href]');
    if (! anchor || event.defaultPrevented) {
        return;
    }
    if (anchor.hasAttribute('download') || (anchor.target && anchor.target !== '_self')) {
        return;
    }
    Alpine.store('loopNav').go(anchor.href, event, {
        kind: anchor.dataset.loopNavKind || 'fade',
    });
});

document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) {
        return;
    }
    const form = event.target;
    if (! (form instanceof HTMLFormElement) || (form.target && form.target !== '_self')) {
        return;
    }
    if (form.hasAttribute('data-loop-no-skeleton') || form.closest('[data-loop-no-skeleton]')) {
        return;
    }
    if (document.documentElement.classList.contains('loop-no-skeleton')) {
        return;
    }
    showLoopSkeleton();
});

Alpine.data('phoneCountryField', (cfg = {}) => ({
    country: cfg.country || 'TZ',
    countries: cfg.countries || {},
    get dial() {
        return this.countries[this.country] || '+255';
    },
}));

Alpine.data('logoPlaceholder', (cfg = {}) => ({
    preview: cfg.preview || '',
    posX: 50,
    posY: 50,
    zoom: 100,
    dragging: false,
    startX: 0,
    startY: 0,
    startPosX: 50,
    startPosY: 50,
    baked: false,
    openPicker() {
        this.$refs.input.value = '';
        this.$refs.input.click();
    },
    pick(event) {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }
        this.baked = false;
        const reader = new FileReader();
        reader.onload = () => {
            this.preview = String(reader.result || '');
            this.posX = 50;
            this.posY = 50;
            this.zoom = 100;
            this.$dispatch('logo-picked');
        };
        reader.readAsDataURL(file);
    },
    imgStyle() {
        const z = Math.max(1, Number(this.zoom) / 100);
        return `object-position: ${this.posX}% ${this.posY}%; transform: scale(${z}); transform-origin: ${this.posX}% ${this.posY}%;`;
    },
    onDown(event) {
        if (!this.preview) {
            return;
        }
        event.preventDefault();
        this.dragging = true;
        this.startX = event.clientX;
        this.startY = event.clientY;
        this.startPosX = this.posX;
        this.startPosY = this.posY;
        event.currentTarget.setPointerCapture?.(event.pointerId);
    },
    onMove(event) {
        if (!this.dragging) {
            return;
        }
        const box = this.$refs.frame.getBoundingClientRect();
        if (!box.width || !box.height) {
            return;
        }
        this.posX = Math.min(100, Math.max(0, this.startPosX - ((event.clientX - this.startX) / box.width) * 100));
        this.posY = Math.min(100, Math.max(0, this.startPosY - ((event.clientY - this.startY) / box.height) * 100));
    },
    onUp() {
        this.dragging = false;
    },
    init() {
        const form = this.$el.closest('form');
        form?.addEventListener('submit', async (event) => {
            if (this.baked || !this.$refs.input?.files?.[0]) {
                return;
            }
            event.preventDefault();
            event.stopImmediatePropagation();
            await this.exportCrop();
            this.baked = true;
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }, true);
    },
    loadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    },
    async exportCrop() {
        if (!this.preview) {
            return;
        }
        const img = await this.loadImage(this.preview);
        const size = 800;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');
        const zoom = Math.max(1, Number(this.zoom) / 100);
        const scale = Math.max(size / img.width, size / img.height) * zoom;
        const dw = img.width * scale;
        const dh = img.height * scale;
        const dx = (size - dw) * ((Number(this.posX) || 50) / 100);
        const dy = (size - dh) * ((Number(this.posY) || 50) / 100);
        ctx.drawImage(img, dx, dy, dw, dh);
        const blob = await new Promise((resolve) => canvas.toBlob((b) => resolve(b), 'image/png'));
        if (!blob) {
            return;
        }
        const file = new File([blob], 'logo.png', { type: 'image/png' });
        const transfer = new DataTransfer();
        transfer.items.add(file);
        this.$refs.input.files = transfer.files;
    },
}));

Alpine.data('contentStudio', (cfg = {}) => ({
    topic: cfg.topic || 'loop',
    copyKey: cfg.copyKey || 'now_on_loop',
    lang: cfg.lang || 'en',
    copiesByTopic: cfg.copiesByTopic || {},
    designs: cfg.designs || [],
    design: cfg.design || 'mint_card',
    look: 'plain',
    photoUrl: '',
    photoX: 50,
    photoY: 50,
    editOpen: false,
    saving: false,
    businessName: cfg.businessName || '',
    hotline: cfg.hotline || '',
    logoUrl: cfg.logoUrl || '',
    emptyHints: cfg.emptyHints || {},
    setTopic(key) {
        this.topic = key;
        const copies = this.topicCopies();
        this.copyKey = copies[0]?.key || this.copyKey;
    },
    topicCopies() {
        return this.copiesByTopic[this.topic] || [];
    },
    selectedCopy() {
        return this.topicCopies().find((row) => row.key === this.copyKey) || this.topicCopies()[0] || null;
    },
    copyPosition() {
        const copies = this.topicCopies();
        if (!copies.length) {
            return '';
        }
        const index = Math.max(0, copies.findIndex((row) => row.key === this.copyKey));

        return (index + 1) + ' / ' + copies.length;
    },
    headline() {
        const copy = this.selectedCopy();
        if (!copy) {
            return this.businessName;
        }
        return copy[this.lang] || copy.en || '';
    },
    supportLine() {
        const copy = this.selectedCopy();
        if (!copy) {
            return '';
        }
        return copy['support_' + this.lang] || copy.support_en || '';
    },
    currentDesign() {
        return this.designs.find((row) => row.key === this.design) || this.designs[0] || {};
    },
    cardToneClass() {
        if (this.look === 'photo' && this.photoUrl) {
            return 'bg-ink text-white';
        }
        const map = {
            mint_card: 'bg-gradient-to-br from-mint to-mint-deep text-ink',
            ink_bold: 'bg-ink text-white',
            coral_pop: 'bg-gradient-to-br from-coral to-[#ff8f75] text-ink',
            cream_soft: 'bg-[#F7F3EA] text-ink ring-1 ring-ink/10',
        };
        return map[this.design] || map.mint_card;
    },
    onPhoto(event) {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            this.photoUrl = String(reader.result || '');
            this.look = 'photo';
            this.photoX = 50;
            this.photoY = 50;
        };
        reader.readAsDataURL(file);
    },
    onPhotoDown(event) {
        if (this.look !== 'photo' || !this.photoUrl) {
            return;
        }
        event.preventDefault();
        this._drag = {
            x: event.clientX,
            y: event.clientY,
            posX: this.photoX,
            posY: this.photoY,
        };
        event.currentTarget.setPointerCapture?.(event.pointerId);
    },
    onPhotoMove(event) {
        if (!this._drag) {
            return;
        }
        const box = event.currentTarget.getBoundingClientRect();
        this.photoX = Math.min(100, Math.max(0, this._drag.posX - ((event.clientX - this._drag.x) / box.width) * 100));
        this.photoY = Math.min(100, Math.max(0, this._drag.posY - ((event.clientY - this._drag.y) / box.height) * 100));
    },
    onPhotoUp() {
        this._drag = null;
    },
    tryAnother() {
        const copies = this.topicCopies();
        if (copies.length > 1) {
            const index = copies.findIndex((row) => row.key === this.copyKey);
            this.copyKey = copies[(index + 1) % copies.length].key;
            return;
        }
        const di = this.designs.findIndex((row) => row.key === this.design);
        if (this.designs.length) {
            this.design = this.designs[(di + 1) % this.designs.length].key;
        }
    },
    async saveImage() {
        this.saving = true;
        try {
            const blob = await this.renderPng();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = (this.businessName || 'loop') + '-loop.png';
            a.click();
            URL.revokeObjectURL(url);
        } catch (e) {}
        this.saving = false;
    },
    async shareCard() {
        const text = [this.headline(), this.supportLine(), this.businessName + ' on Loop'].filter(Boolean).join(' — ');
        try {
            const blob = await this.renderPng();
            const file = new File([blob], 'loop.png', { type: 'image/png' });
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({ title: this.businessName, text, files: [file] });
                return;
            }
            if (navigator.share) {
                await navigator.share({ title: this.businessName, text });
                return;
            }
        } catch (e) {}
        try {
            await navigator.clipboard.writeText(text);
            alert(cfg.copiedLabel);
        } catch (e) {}
    },
    loadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    },
    coverDraw(ctx, img, size, focusY, focusX = 50) {
        const scale = Math.max(size / img.width, size / img.height);
        const dw = img.width * scale;
        const dh = img.height * scale;
        const dx = (size - dw) * ((Number(focusX) || 50) / 100);
        const dy = (size - dh) * ((Number(focusY) || 50) / 100);
        ctx.drawImage(img, dx, dy, dw, dh);
    },
    wrapLines(ctx, text, maxWidth) {
        const words = String(text || '').split(/\s+/);
        const lines = [];
        let line = '';
        words.forEach((word) => {
            const next = line ? line + ' ' + word : word;
            if (ctx.measureText(next).width > maxWidth && line) {
                lines.push(line);
                line = word;
            } else {
                line = next;
            }
        });
        if (line) {
            lines.push(line);
        }
        return lines;
    },
    async renderPng() {
        const size = 1080;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');
        const design = this.currentDesign();
        const photoMode = this.look === 'photo' && this.photoUrl;
        if (photoMode) {
            ctx.fillStyle = '#0B1F2A';
            ctx.fillRect(0, 0, size, size);
            try {
                const img = await this.loadImage(this.photoUrl);
                ctx.save();
                ctx.filter = 'grayscale(0.42) contrast(1.08) brightness(0.62) saturate(0.55)';
                this.coverDraw(ctx, img, size, this.photoY, this.photoX);
                ctx.restore();
            } catch (e) {}
            const overlay = ctx.createLinearGradient(0, 0, 0, size);
            overlay.addColorStop(0, 'rgba(11,31,42,0.28)');
            overlay.addColorStop(0.4, 'rgba(11,31,42,0.18)');
            overlay.addColorStop(1, 'rgba(11,31,42,0.78)');
            ctx.fillStyle = overlay;
            ctx.fillRect(0, 0, size, size);
        } else {
            const grad = ctx.createLinearGradient(0, 0, size, size);
            grad.addColorStop(0, design.from || '#2DD4A8');
            grad.addColorStop(1, design.to || '#0F6B56');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, size, size);
        }
        const ink = photoMode ? '#FFFFFF' : (design.ink || '#0B1F2A');
        ctx.fillStyle = ink;
        ctx.textBaseline = 'top';
        let x = 72;
        let y = 72;
        if (this.logoUrl) {
            try {
                const logo = await this.loadImage(this.logoUrl);
                ctx.save();
                ctx.beginPath();
                ctx.roundRect(x, y, 88, 88, 24);
                ctx.clip();
                this.coverDrawLogo(ctx, logo, x, y, 88);
                ctx.restore();
            } catch (e) {}
            ctx.font = '700 36px Sora, sans-serif';
            ctx.fillText(this.businessName, x + 108, y + 24);
        } else {
            ctx.font = '700 40px Sora, sans-serif';
            ctx.fillText(this.businessName, x, y + 20);
        }
        ctx.font = '700 28px Sora, sans-serif';
        ctx.textAlign = 'right';
        ctx.globalAlpha = 0.85;
        ctx.fillText('LOOP', size - 72, y + 32);
        ctx.globalAlpha = 1;
        ctx.textAlign = 'left';
        const headline = this.headline();
        ctx.font = '700 72px Sora, sans-serif';
        const lines = this.wrapLines(ctx, headline, size - 144);
        let hy = 390;
        lines.slice(0, 5).forEach((line) => {
            ctx.fillText(line, x, hy);
            hy += 86;
        });
        const support = this.supportLine();
        if (support) {
            ctx.globalAlpha = 0.82;
            ctx.font = '500 32px DM Sans, sans-serif';
            ctx.fillText(support, x, hy + 12);
            ctx.globalAlpha = 1;
        }
        if (this.hotline) {
            ctx.font = '600 32px DM Sans, sans-serif';
            ctx.fillText(this.hotline, x, size - 110);
        }
        return await new Promise((resolve) => canvas.toBlob((blob) => resolve(blob), 'image/png'));
    },
    coverDrawLogo(ctx, img, x, y, size) {
        const scale = Math.max(size / img.width, size / img.height);
        const dw = img.width * scale;
        const dh = img.height * scale;
        ctx.drawImage(img, x + (size - dw) / 2, y + (size - dh) / 2, dw, dh);
    },
}));

Alpine.data('raffleControl', (cfg = {}) => ({
    winnerId: cfg.winnerId || 0,
    spinning: false,
    shown: '',
    timer: null,
    init() {
        if (cfg.playReveal && this.winnerId) {
            this.theatre();
        }
    },
    theatre() {
        this.spinning = true;
        let i = 0;
        const max = Math.max(10, Number(cfg.eligible) || 10);
        this.timer = setInterval(() => {
            this.shown = String(1 + Math.floor(Math.random() * max)).padStart(3, '0');
            i += 1;
            if (i > 22) {
                clearInterval(this.timer);
                this.spinning = false;
            }
        }, 70);
    },
    signalCalling() {
        try {
            new BroadcastChannel('loop-raffle').postMessage({ type: 'calling' });
        } catch (e) {}
    },
}));

Alpine.data('raffleStage', (cfg = {}) => ({
    boardUrl: cfg.boardUrl,
    name: cfg.name || '',
    prize: cfg.prize || '',
    business: cfg.business || '',
    eligible: cfg.eligible || 0,
    winnersCount: cfg.winnersCount || 1,
    drawn: 0,
    callingLabel: cfg.callingLabel || '',
    phase: 'idle',
    shown: '000',
    winnerName: '',
    winnerTag: '',
    seenId: 0,
    ready: false,
    timer: null,
    init() {
        this.poll();
        setInterval(() => this.poll(), 1400);
        try {
            const channel = new BroadcastChannel('loop-raffle');
            channel.addEventListener('message', (event) => {
                if (event.data?.type === 'calling' && this.phase === 'winner') {
                    this.phase = 'calling';
                }
            });
        } catch (e) {}
    },
    membersLine() {
        return (cfg.membersLabel || '').replace(String(cfg.eligible ?? ''), String(this.eligible));
    },
    winnerSlot() {
        const n = Math.min(this.drawn + (this.drawn < this.winnersCount ? 1 : 0), this.winnersCount) || 1;
        return n + ' / ' + this.winnersCount;
    },
    async poll() {
        if (!this.boardUrl) {
            return;
        }
        try {
            const response = await fetch(this.boardUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                return;
            }
            const data = await response.json();
            this.name = data.name;
            this.prize = data.prize;
            this.business = data.business;
            this.eligible = data.eligible;
            this.winnersCount = data.winners_count;
            this.drawn = data.drawn;
            const latest = data.latest;
            if (!this.ready) {
                this.ready = true;
                if (latest) {
                    this.seenId = latest.id;
                    this.showWinner(latest);
                }
                return;
            }
            if (latest && latest.id !== this.seenId) {
                this.seenId = latest.id;
                this.animateTo(latest);
            }
        } catch (e) {}
    },
    showWinner(latest) {
        this.winnerName = latest.name;
        this.winnerTag = latest.tag;
        this.prize = latest.prize || this.prize;
        this.phase = 'winner';
    },
    animateTo(latest) {
        this.phase = 'spin';
        let i = 0;
        const max = Math.max(10, Number(this.eligible) || 10);
        clearInterval(this.timer);
        this.timer = setInterval(() => {
            this.shown = String(1 + Math.floor(Math.random() * max)).padStart(3, '0');
            i += 1;
            const hold = i > 26;
            if (hold) {
                clearInterval(this.timer);
                setTimeout(() => this.showWinner(latest), 420);
            }
        }, 65);
    },
}));

Alpine.start();

document.body?.setAttribute('aria-busy', 'true');
requestAnimationFrame(() => {
    requestAnimationFrame(markLoopReady);
});
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        Alpine.store('loopNav').navigating = false;
        markLoopReady();
    }
});
setTimeout(markLoopReady, 700);
