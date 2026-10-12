import '../styles/cookieconsent.scss';

/**
 * CookieConsent — the client runtime for the XD cookie-consent module.
 *
 * Responsibilities:
 *   - read the server-written consent state (the `CookieConsent` cookie);
 *   - drive Google Consent Mode v2 from a config-supplied category→signal map;
 *   - enforce consent by activating `type="text/plain" data-consent-category`
 *     scripts and click-to-load embeds only once their category is granted;
 *   - power the accessible banner + in-banner preferences centre;
 *   - expose a small public API (`window.XDConsent`) and a `consentchange` event.
 *
 * Backwards compatibility: the default export + `window.CookieConsent` class, and
 * the `check()` / `pushToDataLayer()` / `enableXHRMode()` methods, are preserved.
 */

const DEFAULT_CONFIG = {
    cookieName: 'CookieConsent',
    metaCookieName: 'CookieConsentMeta',
    groups: ['Necessary', 'Analytics', 'Marketing'],
    required: ['Necessary'],
    aliases: {
        necessary: 'Necessary',
        preferences: 'Preferences',
        statistics: 'Analytics',
        marketing: 'Marketing',
    },
    consentModes: {
        statistics: ['analytics_storage'],
        marketing: ['ad_storage', 'ad_user_data', 'ad_personalization'],
    },
    version: 1,
    needsPrompt: true,
    acceptAllLink: '',
    acceptNecessaryLink: '',
    saveLink: '',
};

export default class CookieConsent {
    constructor(config) {
        this.config = Object.assign({}, DEFAULT_CONFIG, config || window.XDConsentConfig || {});
        this.cookieName = this.config.cookieName;
        this.consent = [];

        // Define dataLayer + gtag and set the Consent Mode v2 default to denied.
        window.dataLayer = window.dataLayer || [];
        if (typeof window.gtag !== 'function') {
            window.gtag = function gtag() {
                window.dataLayer.push(arguments);
            };
        }
        this.setConsentModeDefault();

        this.updateConsent();
        this.pushToDataLayer();

        // Enforcement + UI.
        this.activateScripts();
        this.bindEmbeds();
        this.bindBanner();
        this.bindPreferences();
        this.bindReopen();
        this.enableXHRMode();
    }

    /* ------------------------------------------------------------------ state */

    /** Legacy group name for a standard category (pass-through for unknown names). */
    resolveGroup(category) {
        return this.config.aliases[category] || category;
    }

    /** Read the raw cookie value by name. */
    readCookie(name) {
        const cookies = document.cookie ? document.cookie.split('; ') : [];
        for (let i = 0; i < cookies.length; i++) {
            const parts = cookies[i].split('=');
            if (parts[0] === name) {
                return parts.slice(1).join('=');
            }
        }
        return undefined;
    }

    isSet() {
        return this.readCookie(this.cookieName) !== undefined;
    }

    updateConsent() {
        const raw = this.readCookie(this.cookieName);
        this.consent = raw ? decodeURIComponent(raw).split(',').filter(Boolean) : [];
    }

    /** BC: check a group or standard category against current consent. */
    check(group) {
        return this.consent.indexOf(this.resolveGroup(group)) !== -1
            || this.consent.indexOf(group) !== -1;
    }

    /** Standard-taxonomy consent check. */
    has(category) {
        return this.check(category);
    }

    /* ------------------------------------------------------- consent mode v2 */

    allSignals() {
        const set = {};
        Object.keys(this.config.consentModes || {}).forEach((cat) => {
            (this.config.consentModes[cat] || []).forEach((sig) => { set[sig] = true; });
        });
        return Object.keys(set);
    }

    setConsentModeDefault() {
        const signals = this.allSignals();
        if (!signals.length) {
            return;
        }
        const data = {};
        signals.forEach((sig) => { data[sig] = 'denied'; });
        window.gtag('consent', 'default', data);
    }

    pushToDataLayer() {
        if (typeof window.gtag !== 'function' || typeof window.dataLayer === 'undefined') {
            return;
        }
        const modes = this.config.consentModes || {};
        Object.keys(modes).forEach((category) => {
            if (!this.has(category)) {
                return;
            }
            const update = {};
            (modes[category] || []).forEach((signal) => { update[signal] = 'granted'; });
            if (Object.keys(update).length) {
                window.gtag('consent', 'update', update);
            }
            window.dataLayer.push({ event: `cookieconsent_${category}` });
        });
    }

    /* ----------------------------------------------------- script enforcement */

    activateScripts() {
        const blocked = document.querySelectorAll(
            'script[type="text/plain"][data-consent-category]'
        );
        blocked.forEach((node) => {
            const category = node.getAttribute('data-consent-category');
            if (!this.has(category)) {
                return;
            }
            const script = document.createElement('script');
            // Copy non-type attributes; promote data-src to src.
            for (let i = 0; i < node.attributes.length; i++) {
                const attr = node.attributes[i];
                if (attr.name === 'type') {
                    continue;
                }
                if (attr.name === 'data-src') {
                    script.setAttribute('src', attr.value);
                } else {
                    script.setAttribute(attr.name, attr.value);
                }
            }
            script.text = node.text || node.textContent || '';
            node.parentNode.replaceChild(script, node);
        });
    }

    /* ------------------------------------------------------ click-to-load */

    bindEmbeds() {
        const embeds = document.querySelectorAll('[data-consent-embed]');
        embeds.forEach((embed) => {
            const category = embed.getAttribute('data-consent-category');
            if (this.has(category)) {
                this.loadEmbed(embed);
                return;
            }
            const loadBtn = embed.querySelector('[data-consent-embed-load]');
            if (loadBtn) {
                loadBtn.addEventListener('click', () => this.loadEmbed(embed));
            }
            const alwaysBtn = embed.querySelector('[data-consent-embed-always]');
            if (alwaysBtn) {
                alwaysBtn.addEventListener('click', () => {
                    this.grant(this.resolveGroup(category));
                });
            }
        });
    }

    loadEmbed(embed) {
        if (embed.getAttribute('data-loaded') === 'true') {
            return;
        }
        const src = embed.getAttribute('data-embed-src');
        if (!src) {
            return;
        }
        const iframe = document.createElement('iframe');
        iframe.setAttribute('src', src);
        iframe.setAttribute('title', embed.getAttribute('data-embed-title') || '');
        iframe.setAttribute('loading', 'lazy');
        iframe.setAttribute('allowfullscreen', 'allowfullscreen');
        iframe.className = 'consent-embed__frame';
        embed.innerHTML = '';
        embed.appendChild(iframe);
        embed.setAttribute('data-loaded', 'true');
    }

    /* ---------------------------------------------------------- server sync */

    /** Re-read state, re-apply enforcement, notify subscribers. */
    refresh() {
        this.updateConsent();
        this.pushToDataLayer();
        this.activateScripts();
        this.bindEmbeds();
        this.dispatchChange();
    }

    dispatchChange() {
        document.dispatchEvent(new CustomEvent('consentchange', {
            detail: {
                groups: this.consent.slice(),
                categories: this.consent.map((g) => this.toCategory(g)),
            },
        }));
    }

    toCategory(group) {
        const aliases = this.config.aliases || {};
        const found = Object.keys(aliases).find((cat) => aliases[cat] === group);
        return found || group;
    }

    /** Send a consent action to the server (which writes the cookie), then sync. */
    sendAction(url, onDone) {
        if (!url) {
            return;
        }
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                this.refresh();
                if (typeof onDone === 'function') {
                    onDone();
                }
            }
        });
        xhr.send();
    }

    /* --------------------------------------------------------- public API */

    grantAll(onDone) {
        this.sendAction(this.config.acceptAllLink, onDone || (() => this.hideBanner()));
    }

    rejectAll(onDone) {
        this.sendAction(this.config.acceptNecessaryLink, onDone || (() => this.hideBanner()));
    }

    /** Grant a single group/category (keeps the rest of the current selection). */
    grant(category) {
        const group = this.resolveGroup(category);
        const groups = this.consent.slice();
        if (groups.indexOf(group) === -1) {
            groups.push(group);
        }
        this.save(groups);
    }

    /** Save an explicit set of granted groups (legacy names). */
    save(groups, onDone) {
        const url = this.config.saveLink
            + (this.config.saveLink.indexOf('?') === -1 ? '?' : '&')
            + 'groups=' + encodeURIComponent(groups.join(','));
        this.sendAction(url, onDone || (() => { this.hideBanner(); this.close(); }));
    }

    /* --------------------------------------------------------------- banner */

    hideBanner() {
        const banner = document.getElementById('cookie-consent-popup');
        if (banner) {
            banner.parentNode.removeChild(banner);
        }
    }

    bindBanner() {
        const banner = document.getElementById('cookie-consent-popup');
        if (!banner) {
            return;
        }
        banner.querySelectorAll('[data-consent-accept-all], #accept-all-cookies').forEach((el) => {
            el.addEventListener('click', (e) => { e.preventDefault(); this.grantAll(); });
        });
        banner.querySelectorAll('[data-consent-reject], #accept-necessary-cookies').forEach((el) => {
            el.addEventListener('click', (e) => { e.preventDefault(); this.rejectAll(); });
        });
        banner.querySelectorAll('[data-consent-open]').forEach((el) => {
            el.addEventListener('click', (e) => { e.preventDefault(); this.open(); });
        });
    }

    /* --------------------------------------------------- preferences centre */

    bindPreferences() {
        const centre = document.getElementById('cookie-consent-preferences');
        if (!centre) {
            return;
        }
        this.centre = centre;

        centre.querySelectorAll('[data-consent-save]').forEach((el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const groups = [];
                centre.querySelectorAll('[data-consent-group]').forEach((input) => {
                    if (input.checked || input.disabled) {
                        groups.push(input.getAttribute('data-consent-group'));
                    }
                });
                this.save(groups);
            });
        });
        centre.querySelectorAll('[data-consent-accept-all]').forEach((el) => {
            el.addEventListener('click', (e) => { e.preventDefault(); this.grantAll(() => { this.hideBanner(); this.close(); }); });
        });
        centre.querySelectorAll('[data-consent-reject]').forEach((el) => {
            el.addEventListener('click', (e) => { e.preventDefault(); this.rejectAll(() => { this.hideBanner(); this.close(); }); });
        });
        centre.querySelectorAll('[data-consent-close]').forEach((el) => {
            el.addEventListener('click', (e) => { e.preventDefault(); this.close(); });
        });

        this._keyHandler = (e) => {
            if (e.key === 'Escape') {
                this.close();
            } else if (e.key === 'Tab') {
                this.trapFocus(e);
            }
        };
    }

    open() {
        const centre = this.centre || document.getElementById('cookie-consent-preferences');
        if (!centre) {
            return;
        }
        this.lastFocus = document.activeElement;
        centre.hidden = false;
        centre.classList.add('is-open');
        document.addEventListener('keydown', this._keyHandler);
        const focusables = this.focusable(centre);
        if (focusables.length) {
            focusables[0].focus();
        }
    }

    close() {
        const centre = this.centre || document.getElementById('cookie-consent-preferences');
        if (!centre) {
            return;
        }
        centre.hidden = true;
        centre.classList.remove('is-open');
        document.removeEventListener('keydown', this._keyHandler);
        if (this.lastFocus && typeof this.lastFocus.focus === 'function') {
            this.lastFocus.focus();
        }
    }

    focusable(container) {
        return Array.prototype.slice.call(container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )).filter((el) => el.offsetParent !== null);
    }

    trapFocus(e) {
        const centre = this.centre;
        if (!centre || centre.hidden) {
            return;
        }
        const focusables = this.focusable(centre);
        if (!focusables.length) {
            return;
        }
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    bindReopen() {
        document.querySelectorAll('[data-consent-reopen]').forEach((el) => {
            el.addEventListener('click', (e) => { e.preventDefault(); this.open(); });
        });
    }

    /* ----------------------------------------------------------- BC: XHR mode */

    /**
     * Legacy no-reload behaviour. The banner handlers above already intercept the
     * accept/reject links; this keeps the old public method working and removes
     * the banner once a decision exists.
     */
    enableXHRMode() {
        const banner = document.getElementById('cookie-consent-popup');
        if (banner && this.isSet() && !this.config.needsPrompt) {
            banner.parentNode.removeChild(banner);
        }
    }
}

window.CookieConsent = CookieConsent;

const consent = new CookieConsent();

// Modern public API.
window.XDConsent = {
    has: (category) => consent.has(category),
    granted: () => consent.consent.slice(),
    grantAll: (cb) => consent.grantAll(cb),
    rejectAll: (cb) => consent.rejectAll(cb),
    save: (groups, cb) => consent.save(groups, cb),
    open: () => consent.open(),
    close: () => consent.close(),
    instance: consent,
};
