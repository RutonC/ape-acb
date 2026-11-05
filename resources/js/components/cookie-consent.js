/**
 * Aviso de cookies (RF08).
 *
 * Guarda a escolha no cookie "acb_consent" (accepted | rejected) durante
 * 180 dias e emite o evento "consent-updated". Os cookies analíticos (GA4,
 * Fase 9) só são activados quando window.acbConsent() === 'accepted'.
 */
const COOKIE = 'acb_consent';
const MAX_AGE = 60 * 60 * 24 * 180;

export const readConsent = () => {
    const match = document.cookie.match(new RegExp(`(?:^|; )${COOKIE}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
};

window.acbConsent = readConsent;

export default () => ({
    open: false,

    init() {
        this.open = readConsent() === null;

        window.addEventListener('open-cookie-consent', () => {
            this.open = true;
        });
    },

    choose(value) {
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = `${COOKIE}=${value}; Max-Age=${MAX_AGE}; Path=/; SameSite=Lax${secure}`;
        this.open = false;
        window.dispatchEvent(new CustomEvent('consent-updated', { detail: value }));
    },

    accept() {
        this.choose('accepted');
    },

    reject() {
        this.choose('rejected');
    },
});
