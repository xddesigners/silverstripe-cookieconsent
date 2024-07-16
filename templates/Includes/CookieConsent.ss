<% if $PromptCookieConsent %>
    <div class="cookie-consent p-3" id="cookie-consent-popup">
        <h4 class="text-body">
            <i class="$Theme.FAS fa-cookie-bite"></i>
            $SiteConfig.CookieConsentTitle
        </h4>
        <div class="small">
            $SiteConfig.CookieConsentContent
        </div>
        <div class="row gx-2">
            <% if not $SiteConfig.HideAcceptAllCookiesButton %>
                <div class="col-md col-sm-12">
                    <a class="btn btn-sm btn-success w-100 mb-md-0 mb-2" href="$AcceptAllCookiesLink" rel="nofollow"
                       id="accept-all-cookies">
                        <i class="fa-regular fa-check"></i>
                        <%t XD\\CookieConsent\\CookieConsent.AcceptAllCookies 'Accept all cookies' %>
                    </a>
                </div>
            <% end_if %>
            <% if not $SiteConfig.HideOnlyNecessaryyButton %>
                <div class="col-md col-sm-12">
                    <a class="btn btn-outline-gray-600 btn-sm w-100 mb-md-0 mb-2" href="$AcceptNecessaryCookiesLink"
                       rel="nofollow" id="accept-necessary-cookies">
                        <i class="fa-regular fa-close"></i>
                        <%t XD\\CookieConsent\\CookieConsent.AcceptNecessaryCookies 'Accept necessary cookies' %>
                    </a>
                </div>
            <% end_if %>
            <% if not $SiteConfig.HideEditCookieSettingsButton %>
                <div class="col-md  col-sm-12">
                    <a class="btn btn-outline-gray-600 btn-sm w-100" href="$CookiePolicyPage.Link">
                        <i class="fa-regular fa-cog"></i>
                        <%t XD\\CookieConsent\\CookieConsent.ManageCookies 'Manage cookie settings' %>
                    </a>
                </div>
            <% end_if %>
        </div>
    </div>
<% end_if %>