import '../styles/cookieconsent.scss';

export default class CookieConsent {
    constructor() {
        this.cookieName = 'CookieConsent';
        this.cookieJar = {}; // Changed from [] to {}

        // Define dataLayer and the gtag function.
        window.dataLayer = window.dataLayer || [];
        function gtag() {
            dataLayer.push(arguments);
        }

        // Set default consent to 'denied' as a placeholder
        let data = {
            'ad_storage': 'denied', // Marketing
            'ad_user_data': 'denied', // Marketing
            'ad_personalization': 'denied', // Marketing
            'analytics_storage': 'denied', // Analytics
            'personalization_storage': 'denied',
        };
        gtag('consent', 'default', data);

        this.updateConsent();
        this.pushToDataLayer();

        this.enableXHRMode();
    }

    isSet() {
        return this.cookieJar[this.cookieName] !== undefined;
    }

    check(group) {
        return this.consent.indexOf(group) !== -1;
    }

    updateConsent() {
        let cookies = document.cookie ? document.cookie.split('; ') : [];
        for (let i = 0; i < cookies.length; i++) {
            let parts = cookies[i].split('=');
            let key = parts[0];
            this.cookieJar[key] = parts.slice(1).join('=');
        }

        this.consent = this.isSet()
            ? decodeURIComponent(this.cookieJar[this.cookieName]).split(',')
            : [];
    }

    pushToDataLayer() {
        // check if gtag is defined
        if (typeof gtag !== 'function') {
            console.info('[CookieConsent] gtag not configured');
            return;
        }

        console.info('[CookieConsent] consent:', this.consent);

        // Simplified direct reference to window.dataLayer
        if (typeof window.dataLayer !== 'undefined') {
            if (this.check('Necessary')) {
                console.info('[CookieConsent] grant: functionality_storage');
                gtag('consent', 'update', {
                    'functionality_storage': 'granted'
                });
                window.dataLayer.push({ 'event': 'cookieconsent_preferences' });
            }
            if (this.check('Analytics')) {
                console.info('[CookieConsent] grant: analytics_storage');
                gtag('consent', 'update', {
                    'analytics_storage': 'granted'
                });
                window.dataLayer.push({ 'event': 'cookieconsent_analytics' });
            }
            if (this.check('Marketing')) {
                console.info('[CookieConsent] grant: ad_storage, personalization_storage, ad_user_data, ad_personalization');
                gtag('consent', 'update', {
                    'ad_user_data': 'granted',
                    'ad_personalization': 'granted',
                    'ad_storage': 'granted',
                    'personalization_storage': 'granted',
                });
                window.dataLayer.push({ 'event': 'cookieconsent_marketing' });
            }
        } else {
            console.info('[CookieConsent] dataLayer undefined');
        }
    }

    enableXHRMode() {
        const acceptAllLink = document.getElementById('accept-all-cookies');
        const acceptNecessaryLink = document.getElementById('accept-necessary-cookies');
        const cookiePopup = document.getElementById('cookie-consent-popup');

        if (cookiePopup) {
            if (this.isSet()) {
                cookiePopup.remove();
                return;
            }

            // show popup
            cookiePopup.classList.remove('cookie-consent-background--hidden');
            if (acceptAllLink) {
                acceptAllLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.sendXHRRequest(acceptAllLink.href);
                });
            }
            if (acceptNecessaryLink) {
                acceptNecessaryLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.sendXHRRequest(acceptNecessaryLink.href);
                });
            }
        }
    }

    sendXHRRequest(url) {
        let _this = this;
        const cookiePopup = document.getElementById('cookie-consent-popup');
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.addEventListener('load', function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                console.info('[CookieConsent] consent saved');
                _this.updateConsent();
                _this.pushToDataLayer();
            } else {
                console.warn('[CookieConsent] request not successful. Status:', xhr.status);
            }
        });
        xhr.addEventListener('error', function () {
            console.error('[CookieConsent] request failed.');
        });
        xhr.send();
        cookiePopup.remove();
    }

}

window.CookieConsent = CookieConsent;
const consent = new CookieConsent();
