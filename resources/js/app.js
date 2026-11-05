import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import AOS from 'aos';
import 'aos/dist/aos.css';

import siteHeader from './components/site-header';
import cookieConsent from './components/cookie-consent';
import scrollUp from './components/scroll-up';
import counter from './components/counter';
import slider from './components/slider';
import gallery from './components/gallery';
import initAnalytics from './components/analytics';

Alpine.plugin(collapse);
Alpine.plugin(focus);

Alpine.data('siteHeader', siteHeader);
Alpine.data('cookieConsent', cookieConsent);
Alpine.data('scrollUp', scrollUp);
Alpine.data('counter', counter);
Alpine.data('slider', slider);
Alpine.data('gallery', gallery);

initAnalytics();

window.Alpine = Alpine;
Alpine.start();

// Animações de entrada (equivalente às animações WOW do tema).
AOS.init({
    once: true,
    duration: 800,
    easing: 'ease-out-cubic',
    offset: 60,
    disable: () => window.matchMedia('(prefers-reduced-motion: reduce)').matches,
});
