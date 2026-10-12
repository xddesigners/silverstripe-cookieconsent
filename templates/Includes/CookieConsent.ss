<%-- XD Cookie Consent — default banner + preferences centre.
     Framework-neutral and token-driven; override this template in your theme
     (templates/Includes/CookieConsent.ss) to restyle. The ids
     #cookie-consent-popup / #accept-all-cookies / #accept-necessary-cookies and
     the data-consent-* hooks are the JS contract. --%>
<% if $SiteConfig.CookieConsentActive %>
    <% if $PromptCookieConsent %>
        <div class="cookie-consent" id="cookie-consent-popup" role="dialog" aria-modal="false"
             aria-labelledby="cookie-consent-title" aria-describedby="cookie-consent-desc">
            <h2 class="cookie-consent__title" id="cookie-consent-title">$SiteConfig.CookieConsentTitle</h2>
            <div class="cookie-consent__body" id="cookie-consent-desc">$SiteConfig.CookieConsentContent</div>
            <div class="cookie-consent__actions">
                <a class="cookie-consent__btn cookie-consent__btn--primary" id="accept-all-cookies"
                   href="$AcceptAllCookiesLink" rel="nofollow" data-consent-accept-all>
                    <% if $SiteConfig.AcceptAllCookiesTitle %>$SiteConfig.AcceptAllCookiesTitle<% else %><%t XD\\CookieConsent\\CookieConsent.AcceptAllCookies 'Accept all cookies' %><% end_if %>
                </a>
                <a class="cookie-consent__btn cookie-consent__btn--secondary" id="accept-necessary-cookies"
                   href="$AcceptNecessaryCookiesLink" rel="nofollow" data-consent-reject>
                    <% if $SiteConfig.AcceptOnlyNecessaryCookiesTitle %>$SiteConfig.AcceptOnlyNecessaryCookiesTitle<% else %><%t XD\\CookieConsent\\CookieConsent.RejectAllCookies 'Reject non-essential' %><% end_if %>
                </a>
                <button type="button" class="cookie-consent__btn cookie-consent__btn--link" data-consent-open>
                    <% if $SiteConfig.EditCookieSettingsTitle %>$SiteConfig.EditCookieSettingsTitle<% else %><%t XD\\CookieConsent\\CookieConsent.ManageCookies 'Manage preferences' %><% end_if %>
                </button>
            </div>
            <% if $CookiePolicyPage %>
                <noscript>
                    <a class="cookie-consent__policy-link" href="$CookiePolicyPage.Link"><%t XD\\CookieConsent\\CookieConsent.ManageCookies 'Manage preferences' %></a>
                </noscript>
            <% end_if %>
        </div>
    <% end_if %>

    <div class="cookie-consent-prefs" id="cookie-consent-preferences" role="dialog" aria-modal="true"
         aria-labelledby="cookie-consent-prefs-title" hidden>
        <div class="cookie-consent-prefs__backdrop" data-consent-close></div>
        <div class="cookie-consent-prefs__dialog" role="document">
            <button type="button" class="cookie-consent-prefs__close" data-consent-close
                    aria-label="<%t XD\\CookieConsent\\CookieConsent.Close 'Close' %>">&times;</button>
            <h2 class="cookie-consent-prefs__title" id="cookie-consent-prefs-title"><%t XD\\CookieConsent\\CookieConsent.PreferencesTitle 'Cookie preferences' %></h2>
            <div class="cookie-consent-prefs__body">
                <% loop $CookieConsentGroups %>
                    <div class="cookie-consent-prefs__group">
                        <label class="cookie-consent-prefs__toggle">
                            <input type="checkbox" data-consent-group="$ConfigName" data-consent-category="$StandardCategory"<% if $isRequired %> checked disabled<% else_if $isGranted %> checked<% end_if %> />
                            <span class="cookie-consent-prefs__group-title">$Title<% if $isRequired %> <span class="cookie-consent-prefs__required">(<%t XD\\CookieConsent\\CookieConsent.AlwaysOn 'always on' %>)</span><% end_if %></span>
                        </label>
                        <div class="cookie-consent-prefs__group-desc">$Content</div>
                        <% if $ShowCookies %><% if $Cookies %>
                            <div class="table-responsive">
                                <table class="cookie-group-table">
                                    <thead>
                                        <tr>
                                            <th><%t XD\\CookieConsent\\Shortcode\\CookieGroupTable.Title 'Name cookie' %></th>
                                            <th><%t XD\\CookieConsent\\Shortcode\\CookieGroupTable.Provider 'Placed by' %></th>
                                            <th><%t XD\\CookieConsent\\Shortcode\\CookieGroupTable.Purpose 'Purpose' %></th>
                                            <th><%t XD\\CookieConsent\\Shortcode\\CookieGroupTable.Expiry 'Expiry' %></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <% loop $Cookies %>
                                            <tr>
                                                <td>$Title</td>
                                                <td>$Provider</td>
                                                <td>$Purpose</td>
                                                <td>$Expiry</td>
                                            </tr>
                                        <% end_loop %>
                                    </tbody>
                                </table>
                            </div>
                        <% end_if %><% end_if %>
                    </div>
                <% end_loop %>
            </div>
            <div class="cookie-consent-prefs__actions">
                <button type="button" class="cookie-consent__btn cookie-consent__btn--primary" data-consent-accept-all><%t XD\\CookieConsent\\CookieConsent.AcceptAllCookies 'Accept all cookies' %></button>
                <button type="button" class="cookie-consent__btn cookie-consent__btn--secondary" data-consent-reject><%t XD\\CookieConsent\\CookieConsent.RejectAllCookies 'Reject non-essential' %></button>
                <button type="button" class="cookie-consent__btn cookie-consent__btn--save" data-consent-save><%t XD\\CookieConsent\\CookieConsent.SavePreferences 'Save preferences' %></button>
            </div>
        </div>
    </div>
<% end_if %>
