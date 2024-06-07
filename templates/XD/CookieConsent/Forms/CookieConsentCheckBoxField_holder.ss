<div class="cookie-consent-field $Class mb-4">
    <div class="d-flex">
        <div class="cookie-consent-field__label w-100">
            <label for="$ID">$Title</label>
        </div>
        <div class="cookie-consent-field__field">
            $Field
        </div>
    </div>
    <div class="cookie-consent-field__description">
        $Content
    </div>

    <% with $CookieGroup %>
        <% if $Cookies && $ShowCookies %>
            <div class="accordion cookie-accordion" id="cookieAccordion{$ID}">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="cookieGroup{$ID}">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{$ID}" aria-expanded="true" aria-controls="collapse{$ID}">
                            Cookie details
                        </button>
                    </h2>
                    <div id="collapse{$ID}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#cookieAccordion{$ID}">
                        <div class="accordion-body">
                            <% include XD\CookieConsent\Shortcode\CookieGroupTable %>
                        </div>
                    </div>
                </div>
            </div>
        <% end_if %>
    <% end_with %>

</div>
<% if $Message %>
    <div class="cookie-consent-field__message cookie-consent-field__message--$MessageType">
        $Message
    </div>
<% end_if %>
