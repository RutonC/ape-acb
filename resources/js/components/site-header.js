/**
 * Cabeçalho: sombra ao descer a página, menu lateral (telemóvel) e modo escuro.
 */
const THEME_KEY = 'ape-theme';

export default () => ({
    scrolled: false,
    offcanvas: false,
    dark: document.documentElement.dataset.theme === 'dark',

    init() {
        const onScroll = () => {
            this.scrolled = window.scrollY > 8;
        };

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        this.$watch('offcanvas', (open) => {
            document.documentElement.classList.toggle('overflow-hidden', open);
        });
    },

    openOffcanvas() {
        this.offcanvas = true;
    },

    closeOffcanvas() {
        this.offcanvas = false;
    },

    toggleTheme() {
        this.dark = !this.dark;

        if (this.dark) {
            document.documentElement.dataset.theme = 'dark';
        } else {
            delete document.documentElement.dataset.theme;
        }

        try {
            localStorage.setItem(THEME_KEY, this.dark ? 'dark' : 'light');
        } catch {
            // Sem armazenamento disponível, a escolha vale só para esta página.
        }
    },
});
