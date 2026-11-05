/**
 * Botão "voltar ao topo", visível depois de descer 300px.
 */
export default () => ({
    visible: false,

    init() {
        const onScroll = () => {
            this.visible = window.scrollY > 300;
        };

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    },

    toTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
});
