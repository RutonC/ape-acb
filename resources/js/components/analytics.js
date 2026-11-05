/**
 * Google Analytics 4.
 *
 * - Só carrega o gtag.js depois de o visitante aceitar os cookies (RF08);
 *   sem ID configurado nas Definições SEO não faz nada.
 * - Eventos: chamadas (click_to_call), WhatsApp (whatsapp_click), e-mail
 *   (email_click), partilhas (share) e formulários enviados com sucesso
 *   (generate_lead / job_application, marcados com data-analytics-event).
 */
import { readConsent } from './cookie-consent';

const measurementId = document.querySelector('meta[name="ga4-id"]')?.content;
let loaded = false;
let scriptAdded = false;

// O gtag.js só reconhece comandos empurrados como objecto "arguments" (não arrays).
function gtag() {
    window.dataLayer = window.dataLayer || [];
    // eslint-disable-next-line prefer-rest-params
    window.dataLayer.push(arguments);
}

export const track = (event, params = {}) => {
    if (loaded) {
        gtag('event', event, params);
    }
};

const load = () => {
    if (loaded || !measurementId) {
        return;
    }

    loaded = true;
    window[`ga-disable-${measurementId}`] = false;
    window.gtag = gtag;

    if (!scriptAdded) {
        scriptAdded = true;
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;
        document.head.appendChild(script);
    }

    gtag('js', new Date());
    gtag('config', measurementId);

    // Formulários enviados: a página de sucesso traz o evento a registar.
    document.querySelectorAll('[data-analytics-event]').forEach((element) => {
        track(element.dataset.analyticsEvent, { form_name: element.dataset.analyticsForm });
    });
};

// Recusa depois de ter aceitado: pára a recolha e apaga os cookies _ga.
const disable = () => {
    if (!measurementId) {
        return;
    }

    window[`ga-disable-${measurementId}`] = true;
    loaded = false;

    const domain = window.location.hostname.replace(/^www\./, '');
    document.cookie.split('; ').forEach((cookie) => {
        const name = cookie.split('=')[0];
        if (name.startsWith('_ga')) {
            [domain, `.${domain}`, ''].forEach((scope) => {
                document.cookie = `${name}=; Max-Age=0; Path=/${scope ? `; Domain=${scope}` : ''}`;
            });
        }
    });
};

/**
 * Evento correspondente a um link: data-track explícito ou pelo endereço.
 */
const eventFor = (link) => {
    const href = link.getAttribute('href') || '';
    const explicit = link.dataset.track || '';

    if (explicit.startsWith('share-')) {
        return ['share', { method: explicit.slice(6), content_type: 'article', item_id: window.location.pathname }];
    }
    if (explicit === 'phone-click' || href.startsWith('tel:')) {
        return ['click_to_call', { link_url: href }];
    }
    if (explicit === 'whatsapp-click' || /^https:\/\/wa\.me\/\d/.test(href)) {
        return ['whatsapp_click', { link_url: href.split('?')[0] }];
    }
    if (href.startsWith('mailto:')) {
        return ['email_click', { link_url: href.split('?')[0] }];
    }

    return null;
};

export default function initAnalytics() {
    if (readConsent() === 'accepted') {
        load();
    }

    window.addEventListener('consent-updated', (event) => {
        if (event.detail === 'accepted') {
            load();
        } else {
            disable();
        }
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        const match = link && eventFor(link);

        if (match) {
            track(...match);
        }
    });
}
