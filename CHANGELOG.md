# Changelog

All notable changes to `xddesigners/silverstripe-cookieconsent` are documented here.

## 6.1.0 (unreleased) — consent-management layer

A backwards-compatible feature release that turns the cookie bar into a small
consent-management platform (CMP): enforcement, accountability and a modern,
accessible UX on top of the existing Consent Mode v2 wiring. No breaking changes
to the `XD\CookieConsent\…` API; existing consumers keep working unchanged.

> **Versioning note.** The module's major tracks the Silverstripe major
> (4 = CMS 4, 5 = CMS 5, 6 = CMS 6), so this backwards-compatible addition on the
> CMS 6 line is a **minor** (6.1.0), not a new major. If you prefer the version
> number to signal the scope of the change you can tag it as a major instead —
> but nothing in the public API breaks.

### Added
- **Standard category taxonomy** `necessary / preferences / statistics / marketing`,
  mapped onto the legacy `Necessary / Analytics / Marketing / Preferences` group
  names via the `category_aliases` config (fully backwards compatible).
- **`ConsentState` service** — the modern, versioned consent-state API:
  `ConsentState::has('statistics')`, `::granted()`, `::needsPrompt()`,
  `::version()`, `::consentId()`.
- **`$HasConsent('statistics')` template global** (plus `$CookieConsentNeedsPrompt`,
  `$CookieConsentVersion`) usable in any template, including header/footer shells.
- **`window.XDConsent` JS API** — `.has()`, `.grantAll()`, `.rejectAll()`,
  `.save()`, `.open()`, `.close()`, `.granted()` — and a `consentchange` DOM event.
- **In-banner preferences centre** — an accessible modal (`role="dialog"`,
  focus trap, `Esc`, visible focus, `prefers-reduced-motion`, no pre-ticked
  non-essential toggles) with per-category toggles and the cookie declaration.
- **True script/tag blocking** — `ConsentScript::inline()` / `::src()` emit
  `<script type="text/plain" data-consent-category="…">`; the module JS activates
  them on consent and on every `consentchange`. Any script carrying those
  attributes is handled, including vendor snippets marked by hand.
- **Click-to-load embeds** — `[consent_embed src="…" category="marketing"]`
  shortcode renders a placeholder until consent (or a click).
- **Config-driven Consent Mode v2** — the `gtag('consent','default',{…denied})`
  signal set and the per-category `update` map are now derived from each group's
  `consent_modes` config instead of a hardcoded string.
- **Immutable consent log** — a `ConsentRecord` DataObject (categories, version,
  method, source, locale, user-agent and a pseudonymous consent id — **no IP, no
  PII**), written on each decision for GDPR Art. 7(1) accountability.
  `canEdit()` / `canDelete()` / `canCreate()` are all `false`.
  A `CookieConsent-PruneConsentRecords` task ages records out past a configurable
  retention horizon (disabled by default).
- **Versioning / re-consent** — a sidecar `CookieConsentMeta` cookie stores the
  consent version, timestamp and method; `needsPrompt()` re-prompts on a version
  bump or after the consent lifetime (default 6 months), while suppressing
  re-prompts for ~12 months after an explicit reject (anti cookie-fatigue).
- **`ConsentAdmin` ModelAdmin** — manages the cookie categories, cookies and the
  consent log in one CMS section (the declaration also stays in Settings).
- **Soft analytics bridge** — the granted state of a mapped category is mirrored
  onto a session key (default `statistics → analytics_consent`), so modules that
  gate on the session react with zero coupling. Guarded; harmless when unused.
- **Release engineering** — PSR-12 (`phpcs`), `phpstan` (level 4 + `cambis/silverstan`),
  `phpunit` test suite, a `gha-ci` workflow, and translations for
  `en / nl / de / fr / es / it`.

### Changed
- The default banner template is now framework-neutral and token-driven
  (CSS custom properties, `em`-based), still overridable at
  `templates/Includes/CookieConsent.ss`. The JS contract ids
  (`#cookie-consent-popup`, `#accept-all-cookies`, `#accept-necessary-cookies`)
  are preserved.
- `CookieConsent::setConsent()` now writes through the framework `Cookie` API
  (per-cookie SameSite in CMS 6) instead of raw `setcookie()`.
- The consent cookie write path also refreshes the sidecar meta cookie and the
  analytics-session bridge (best-effort, never fatal).

### Removed
- The **hide-reject dark-pattern** options are gone from the CMS: reject and
  manage controls are always shown, as prominent as accept (EDPB). The underlying
  `Hide…Button` DB columns are kept for backwards compatibility but are no longer
  read by the default template.

### Deprecated / superseded
- The dormant `ConsentMode` DataObject is superseded by the config-driven signal
  map (`consent_modes`); it is left in place but no longer required.

### Backwards compatibility
- The primary `CookieConsent` cookie format (comma-separated legacy group names)
  is unchanged — existing PHP (`CookieConsent::check()`), JS (`window.CookieConsent`,
  `check()`, `pushToDataLayer()`, `enableXHRMode()`) and theme code that reads it
  directly keep working. Versioning lives in a separate sidecar cookie.
- The `XD\CookieConsent\…` namespace, the `<% include CookieConsent %>` include,
  the `[cookiegrouptable]` shortcode, the default policy pages and all existing
  SiteConfig fields are retained.
