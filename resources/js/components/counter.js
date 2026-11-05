/**
 * Contador animado (blocos "Números"): conta até ao valor quando fica visível.
 */
export default (target = 0, duration = 1600) => ({
    current: target,

    init() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        this.current = 0;

        const observer = new IntersectionObserver((entries) => {
            if (!entries[0].isIntersecting) {
                return;
            }

            observer.disconnect();
            const start = performance.now();

            const step = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                this.current = Math.round(target * (1 - Math.pow(1 - progress, 3)));

                if (progress < 1) {
                    requestAnimationFrame(step);
                }
            };

            requestAnimationFrame(step);
        }, { threshold: 0.4 });

        observer.observe(this.$el);
    },
});
