# Silverstripe Cookie Consent

GDPR/ePrivacy cookie consent for Silverstripe — a configurable consent framework,
not just a cookie bar: a category taxonomy, a CMS-managed cookie declaration, a
server + JS **consent-state API** other code reads, true **script/tag blocking**
until consent, **Google Consent Mode v2** signalling, an **immutable consent log**
for accountability, re-consent/versioning, and an accessible, theme-overridable
banner + preferences centre.

Core-independent: it runs on any Silverstripe 6 site.

## Installation
```bash
composer require xddesigners/silverstripe-cookieconsent
```

Include the banner + preferences centre in your base `Page.ss`:
```html
<% include CookieConsent %>
```

Add a revoke link anywhere (e.g. the footer) to reopen the preferences centre:
```html
<a href="#" data-consent-reopen>Cookie preferences</a>
```

## Category taxonomy
The module speaks the industry-standard categories **`necessary` / `preferences` /
`statistics` / `marketing`**, mapped onto the stored group names via config. The
defaults keep full backwards compatibility with the legacy
`Necessary / Analytics / Marketing / Preferences` groups:

```yaml
XD\CookieConsent\CookieConsent:
  category_aliases:
    necessary: Necessary
    preferences: Preferences
    statistics: Analytics
    marketing: Marketing
```

So `ConsentState::has('statistics')` resolves to the legacy `Analytics` group —
you can adopt the new names without migrating existing cookies or config.

## The consent-state API (the integration contract)
Source of truth is the server-readable `CookieConsent` cookie (unchanged format),
with versioning in a sidecar `CookieConsentMeta` cookie.

**PHP**
```php
use XD\CookieConsent\ConsentState;

if (ConsentState::has('statistics')) {
    // load analytics
}
ConsentState::granted();     // ['necessary', 'statistics']
ConsentState::needsPrompt(); // show the banner?
ConsentState::version();     // the version the visitor consented under
```
`CookieConsent::check('Analytics')` is kept as a backwards-compatible alias.

**Templates** — usable in any template, including header/footer shells:
```html
<% if $HasConsent('statistics') %>
    <!-- analytics markup -->
<% end_if %>
```

**JavaScript**
```js
if (window.XDConsent.has('marketing')) { /* … */ }
window.XDConsent.grantAll();
window.XDConsent.open();        // open the preferences centre
document.addEventListener('consentchange', (e) => {
    console.log(e.detail.categories); // ['necessary', 'statistics']
});
```

## Script / tag blocking (prior blocking)
Signalling consent is not the same as *not loading* a tag. Emit third-party tags
as inert scripts that the module activates once their category is granted (on load
and on every `consentchange`):

```php
use XD\CookieConsent\View\ConsentScript;

$MyTag       = ConsentScript::inline("gtag('event', 'x');", 'statistics');
$MyExternal  = ConsentScript::src('https://example.com/pixel.js', 'marketing', ['async' => true]);
```
Any script carrying `type="text/plain" data-consent-category="…"` is handled,
including vendor snippets a CMS editor marks by hand. External scripts park their
URL in `data-src` (promoted to `src` on activation) so preload scanners don't fetch
them early.

**Click-to-load embeds** for iframes (YouTube, Maps, …):
```
[consent_embed src="https://www.youtube.com/embed/xyz" category="marketing" title="Video"]
```

## Google Consent Mode v2
The four signals (`ad_storage`, `analytics_storage`, `ad_user_data`,
`ad_personalization`) are defaulted to `denied` before any tag and updated on
consent. The signal map is driven from each group's `consent_modes` config:

```yaml
XD\CookieConsent\CookieConsent:
  cookie_groups:
    Analytics:
      active: true
      consent_modes: [analytics_storage]
    Marketing:
      active: true
      consent_modes: [ad_storage, ad_user_data, ad_personalization]
```
The server emits the default-denied block when a `GTM_CODE` or `GA_CODE` env var is
set; the JS emits the `update` + dataLayer events (`cookieconsent_statistics`, …).

## Consent log (accountability)
Every decision writes an immutable `ConsentRecord` (granted categories, consent
version, method, source, locale, user-agent and a **pseudonymous** consent id — no
IP, no PII), viewable under the **Cookie consent** CMS section. Records cannot be
edited, deleted or created by hand.

Retention is off by default; opt in and run the prune task:
```yaml
XD\CookieConsent\ConsentState:
  log_retention_days: 730
```
```bash
vendor/bin/sake tasks:CookieConsent-PruneConsentRecords
```
Disable logging entirely with `log_consent: false`.

## Re-consent / versioning
```yaml
XD\CookieConsent\ConsentState:
  consent_version: 1          # bump to force a site-wide re-prompt
  consent_lifetime_days: 183  # ~6 months (Dutch AP); up to 13 months (FR max)
  reject_suppress_days: 365   # don't re-ask for ~a year after an explicit reject
```

## Accessibility
The banner and preferences centre are a proper `role="dialog"` with a focus trap,
`Esc` to close, labelled controls, visible focus and `prefers-reduced-motion`
support. Non-essential toggles are never pre-ticked, reject is as prominent as
accept, and content is never hidden behind the banner (no cookie wall).

## Theming / overriding
The default banner is framework-neutral and token-driven. Override any `--cc-*`
custom property from your theme, or override the template entirely at
`templates/Includes/CookieConsent.ss`. The JS contract ids
(`#cookie-consent-popup`, `#accept-all-cookies`, `#accept-necessary-cookies`) and
the `data-consent-*` hooks must be preserved in a custom template.

```yaml
XD\CookieConsent\CookieConsent:
  include_javascript: true
  include_css: true
  create_default_pages: true
```

## Cookie declaration content
Cookie purposes/expiry are editable in the CMS and translatable. You can also set
defaults through the lang files; see `lang/en.yml`. Translations managed through
[Transifex](https://www.transifex.com/xd/cookie-consent). Ships `en`, `nl`, `de`,
`fr`, `es`, `it`.

```yaml
en:
  CookieConsent_ads_marketingcompany_com:
    _track_Purpose: 'Cookie description'
    _track_Expiry: 'Cookie expire time'
```

## Soft integrations
When `statistics` is granted the module mirrors the state onto a session key
(default `analytics_consent`) so analytics/marketing modules can gate on it with no
coupling. Configure with:
```yaml
XD\CookieConsent\ConsentState:
  session_keys:
    statistics: analytics_consent
```

## Upgrading from 6.0.x
No code changes required. The `XD\CookieConsent\…` API, the `CookieConsent` cookie,
the `<% include CookieConsent %>` include and the `[cookiegrouptable]` shortcode are
all preserved. Notable changes:
- The default banner is restyled and gains an in-banner preferences centre. If your
  theme overrides `templates/Includes/CookieConsent.ss`, keep the JS contract ids
  above. Add a `data-consent-reopen` link to let visitors revoke consent.
- The hide-reject button options are no longer applied (reject is always shown).
- Prefer `$HasConsent('statistics')` / `ConsentState::has()` over the legacy
  `$CookieConsent('Analytics')` helper in new templates.

## Static publishing (XHR)
The banner enhances progressively: with JS, accept/reject/save happen over XHR
without a reload; without JS they fall back to the server actions and the policy
page. The `window.CookieConsent` class and its `enableXHRMode()` method are retained.

## Default pages
On `dev/build` the module can create a CookiePolicyPage, PrivacyPolicyPage and
TermsAndConditionsPage. Disable with `create_default_pages: false`.

## Roadmap
Geo-gating (EU/EEA-only banner), CCPA/CPRA "Do Not Sell or Share" + Global Privacy
Control (`Sec-GPC`) honouring, and automatic cookie scanning are planned but not yet
shipped. Full IAB TCF v2.2 is out of scope.

## License
BSD-3-Clause.
