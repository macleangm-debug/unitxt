import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Count points 0 → target with ease-out (~700–900ms).
 */
Alpine.data('loopCountUp', (target, duration = 800) => ({
    display: 0,
    init() {
        const goal = Number(target) || 0;
        if (goal <= 0) {
            this.display = 0;
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
            }
        };
        requestAnimationFrame(tick);
    },
    formatted() {
        return new Intl.NumberFormat().format(this.display);
    },
}));

/**
 * Redeem ticket sheet — card expands into a centered “digital ticket”.
 */
Alpine.data('loopRedeem', () => ({
    open: false,
    rewardName: '',
    rewardPts: '',
    hotline: '',
    businessName: '',
    show(payload = {}) {
        this.rewardName = payload.name || '';
        this.rewardPts = payload.pts || '';
        this.hotline = payload.hotline || '';
        this.businessName = payload.business || '';
        this.open = true;
    },
    close() {
        this.open = false;
    },
}));

Alpine.start();
