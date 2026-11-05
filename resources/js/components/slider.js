/**
 * Slider (testemunhos, logótipos) com Swiper, configurado a partir do Blade.
 *
 * O Swiper (JS e CSS) só é descarregado nas páginas que têm um slider,
 * para não pesar nas restantes (RNF01).
 */
let swiperModules = null;

const loadSwiper = () => {
    swiperModules ??= import('./swiper-bundle');

    return swiperModules;
};

export default (options = {}) => ({
    swiper: null,

    async init() {
        const { Swiper, Autoplay, Pagination, A11y } = await loadSwiper();
        const pagination = this.$el.querySelector('.swiper-pagination');

        this.swiper = new Swiper(this.$el, {
            modules: [Autoplay, Pagination, A11y],
            pagination: pagination ? { el: pagination, clickable: true } : false,
            ...options,
            autoplay: options.autoplay ? { disableOnInteraction: false, pauseOnMouseEnter: true, ...options.autoplay } : false,
        });
    },

    destroy() {
        this.swiper?.destroy();
    },
});
