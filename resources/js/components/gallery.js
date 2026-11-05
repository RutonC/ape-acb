/**
 * Galeria (resources/views/components/gallery.blade.php): filtro por
 * categoria e lightbox que percorre só as imagens visíveis no filtro.
 */
export default () => ({
    filter: null,
    current: null,
    categories: [],
    trigger: null,

    init() {
        this.categories = [...this.$el.querySelectorAll('[data-gallery-item]')].map((item) => item.dataset.category);
    },

    isVisible(index) {
        return this.filter === null || this.categories[index] === this.filter;
    },

    visibleIndexes() {
        return this.categories.map((_, index) => index).filter((index) => this.isVisible(index));
    },

    open(index, trigger) {
        this.trigger = trigger;
        this.current = index;
    },

    close() {
        if (this.current === null) {
            return;
        }

        this.current = null;
        this.$nextTick(() => this.trigger?.focus());
    },

    step(direction) {
        if (this.current === null) {
            return;
        }

        const visible = this.visibleIndexes();
        const position = visible.indexOf(this.current);
        this.current = visible[(position + direction + visible.length) % visible.length];
    },

    next() {
        this.step(1);
    },

    previous() {
        this.step(-1);
    },

    position() {
        const visible = this.visibleIndexes();

        return `${visible.indexOf(this.current) + 1} / ${visible.length}`;
    },
});
