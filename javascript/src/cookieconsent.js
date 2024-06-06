export default class CookieConsent {
    constructor() {

        console.log('init cookie consent');
        // Define dataLayer and the gtag function.
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}

        // Set default consent to 'denied' as a placeholder
        // Determine actual values based on your own requirements
        gtag('consent', 'default', {
            'ad_storage': 'denied', // Marketing
            'ad_user_data': 'denied', // Marketing
            'ad_personalization': 'denied', // Marketing
            'analytics_storage': 'denied', // Analytics
        });

        this.cookieName = 'CookieConsent';
        this.cookieJar = [];
        this.consent = [];

        let cookies = document.cookie ? document.cookie.split('; ') : [];
        for (let i = 0; i < cookies.length; i++) {
            let parts = cookies[i].split('=');
            let key = parts[0];
            this.cookieJar[key] = parts.slice(1).join('=');
        }

        this.consent = this.isSet()
            ? decodeURIComponent(this.cookieJar[this.cookieName]).split(',')
            : [];


        this.pushToDataLayer();

    };

    isSet() {
        return this.cookieJar[this.cookieName] !== undefined;
    };

    check(group) {
        return this.consent.indexOf(group) !== -1;
    };

    pushToDataLayer() {
        console.log('pushToDataLayer');
        console.log('consent', this.consent);

        let dataLayer = window.dataLayer ? window.dataLayer : [];


        /**
         ad_storage: Opslag voor advertentiegerelateerde gegevens.
         analytics_storage: Opslag voor analysegegevens.
         functionality_storage: Opslag voor functionaliteitsondersteuning, zoals taalinstellingen.
         personalization_storage: Opslag voor persoonlijke instellingen.
         security_storage: Opslag voor beveiligingsfuncties.
         */

        if (typeof dataLayer !== 'undefined') {
            if (this.check('Necessary')) {
                console.log('grant: functionality_storage');
                gtag('consent', 'update', {
                    'functionality_storage': 'granted'
                });
                dataLayer.push({'event': 'cookieconsent_preferences'});

            }
            if (this.check('Analytics')) {
                console.log('grant: analytics_storage');
                gtag('consent', 'update', {
                    'analytics_storage': 'granted'
                });
                dataLayer.push({'event': 'cookieconsent_analytics'});
            }
            if (this.check('Marketing')) {
                console.log('grant: ad_storage');
                console.log('grant: personalization_storage');
                gtag('consent', 'update', {
                    'ad_storage': 'granted',
                    'personalization_storage': 'granted',
                });
                dataLayer.push({'event': 'cookieconsent_marketing'});
            }
        } else {
            console.log('dataLayer undefined');
        }
    };

    enableXHRMode() {
        const acceptAllLink = document.getElementById('accept-all-cookies');
        const cookiePopup = document.getElementById('cookie-consent-popup');

        if (cookiePopup) {
            if (this.isSet()) {
                cookiePopup.remove();
                return;
            }

            // show popup
            cookiePopup.classList.remove('cookie-consent-background--hidden');
            acceptAllLink.addEventListener('click', (e) => {
                e.preventDefault();

                let _this = this;
                const xhr = new XMLHttpRequest();
                xhr.open('GET', acceptAllLink.href);

                // Add event listener for load event
                xhr.addEventListener('load', function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        console.log('XHRRequest was successful!');
                        // console.log('Response:', xhr.responseText);
                        _this.pushToDataLayer();
                    } else {
                        console.log('XHRRequest completed but was not successful. Status:', xhr.status);
                    }
                });

                // Add event listener for error event
                xhr.addEventListener('error', function () {
                    console.error('XHRRequest failed.');
                });

                xhr.send();

                cookiePopup.remove();
                console.log('accept all');
            })
        }
    }
}

window.CookieConsent = CookieConsent;

const consent = new CookieConsent();
consent.enableXHRMode();