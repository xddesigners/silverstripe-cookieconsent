<div class="consent-embed consent-embed--$Ratio" data-consent-embed data-consent-category="$Category.ATT"
     data-embed-src="$EmbedSrc.ATT" data-embed-title="$EmbedTitle.ATT"<% if $Height %> style="--consent-embed-height: {$Height}px;"<% end_if %>>
    <div class="consent-embed__placeholder">
        <p class="consent-embed__text"><%t XD\\CookieConsent\\Shortcode\\ConsentEmbed.Blocked 'This content is blocked until you accept {category} cookies.' category=$Category %></p>
        <button type="button" class="cookie-consent__btn cookie-consent__btn--primary" data-consent-embed-load><%t XD\\CookieConsent\\Shortcode\\ConsentEmbed.Load 'Load content' %></button>
        <button type="button" class="cookie-consent__btn cookie-consent__btn--link" data-consent-embed-always><%t XD\\CookieConsent\\Shortcode\\ConsentEmbed.Always 'Always allow' %></button>
    </div>
</div>
