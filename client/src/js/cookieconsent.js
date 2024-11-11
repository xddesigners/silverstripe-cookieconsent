export default class CookieConsent {
    constructor() {
        // console.log('init cookie consent');
        this.cookieName = 'CookieConsent';
        this.cookieJar = {}; // Changed from [] to {}

        // Define dataLayer and the gtag function.
        window.dataLayer = window.dataLayer || [];

        // Check if gtag is defined by GTM and only redefine if it's not.
        if (typeof gtag !== 'function') {
            function gtag() {
                dataLayer.push(arguments);
            }
        }

        // Ensure GTM is initialized and gtag is available
        const checkGTM = () => {
            if (typeof gtag === 'function') {
                // Proceed with setting default consent
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
            } else {
                console.log('Waiting for GTM to load...');
                setTimeout(checkGTM, 100); // Retry after 100ms
            }
        };

        checkGTM(); // Start the check

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
        console.log('consent', this.consent);

        // Add

        // Simplified direct reference to window.dataLayer
        if (typeof gtag === 'function') {
            if (this.check('Necessary')) {
                console.log('grant: functionality_storage');
                gtag('consent', 'update', {
                    'functionality_storage': 'granted'
                });
                window.dataLayer.push({ 'event': 'cookieconsent_preferences' });
            }
            if (this.check('Analytics')) {
                console.log('grant: analytics_storage');
                gtag('consent', 'update', {
                    'analytics_storage': 'granted'
                });
                window.dataLayer.push({ 'event': 'cookieconsent_analytics' });
            }
            if (this.check('Marketing')) {
                console.log('grant: ad_storage');
                console.log('grant: personalization_storage');
                console.log('grant: ad_user_data');
                console.log('grant: ad_personalization');
                gtag('consent', 'update', {
                    'ad_user_data': 'granted',
                    'ad_personalization': 'granted',
                    'ad_storage': 'granted',
                    'personalization_storage': 'granted',
                });
                window.dataLayer.push({ 'event': 'cookieconsent_marketing' });
            }
        } else {
            console.log('dataLayer undefined');
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
                // console.log('XHRRequest success');
                _this.updateConsent();
                _this.pushToDataLayer();
            } else {
                console.log('XHRRequest completed but was not successful. Status:', xhr.status);
            }
        });
        xhr.addEventListener('error', function () {
            console.error('XHRRequest failed.');
        });
        xhr.send();
        cookiePopup.remove();
    }

}

window.CookieConsent = CookieConsent;
const consent = new CookieConsent();
