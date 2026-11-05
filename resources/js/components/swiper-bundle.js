/**
 * Parte do Swiper usada pelo site. Os imports estáticos permitem ao Vite
 * incluir só estes módulos; o ficheiro é carregado a pedido por slider.js.
 */
import Swiper from 'swiper';
import { Autoplay, Pagination, A11y } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';

export { Swiper, Autoplay, Pagination, A11y };
