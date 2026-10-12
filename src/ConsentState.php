<?php

namespace XD\CookieConsent;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use Throwable;
use XD\CookieConsent\Model\ConsentRecord;

/**
 * ConsentState — the modern, versioned consent-state API that other code (PHP,
 * templates via $HasConsent, JS via window.XDConsent) reads.
 *
 * It speaks the standard taxonomy (necessary/preferences/statistics/marketing)
 * and resolves to the legacy group ConfigNames stored in the primary
 * `CookieConsent` cookie, so it is a pure additive layer on top of the existing
 * {@see CookieConsent} service.
 *
 * Versioning / re-consent lives in a SIDECAR cookie (`CookieConsentMeta`), so the
 * primary consent cookie format stays byte-for-byte backwards compatible with the
 * published 6.0.x JS and any theme code that reads it directly.
 *
 * @package XD
 * @subpackage CookieConsent
 */
class ConsentState
{
    use Extensible;
    use Injectable;
    use Configurable;

    public const METHOD_ALL = 'all';
    public const METHOD_REJECT = 'reject';
    public const METHOD_CUSTOM = 'custom';

    /**
     * Name of the sidecar metadata cookie holding {v,t,m,i}. Kept separate from
     * the primary CookieConsent cookie for backwards compatibility.
     *
     * @var string
     */
    private static $meta_cookie_name = 'CookieConsentMeta';

    /**
     * The current consent/declaration version. Bump this (config or the CMS
     * action) whenever the categories or cookie declaration materially change to
     * force a site-wide re-prompt.
     *
     * @var int
     */
    private static $consent_version = 1;

    /**
     * How long a given consent stays valid before we re-prompt, in days. Defaults
     * to ~6 months (strictest common EU reading, Dutch AP). Configurable up to the
     * French 13-month maximum.
     *
     * @var int
     */
    private static $consent_lifetime_days = 183;

    /**
     * After an explicit reject we suppress re-prompting for this many days to
     * avoid cookie-fatigue (EDPB). Defaults to ~12 months.
     *
     * @var int
     */
    private static $reject_suppress_days = 365;

    /**
     * Whether to write immutable ConsentRecord proof rows on each decision.
     *
     * @var bool
     */
    private static $log_consent = true;

    /**
     * Retention horizon for the consent log, in days. 0 (default) keeps records
     * indefinitely; set a positive value and run the prune task to age them out.
     *
     * @var int
     */
    private static $log_retention_days = 0;

    /**
     * Soft bridge: map a standard category to a session key that sibling modules
     * gate on. Defaults to the silvershop/analytics contract
     * (statistics -> analytics_consent). Harmless when nothing reads the key.
     *
     * @var array
     */
    private static $session_keys = [
        CookieConsent::CATEGORY_STATISTICS => 'analytics_consent',
    ];

    /**
     * Check consent for a standard category or a legacy group name.
     *
     * @param string $category
     * @return bool
     */
    public static function has($category = CookieConsent::CATEGORY_NECESSARY)
    {
        return CookieConsent::hasConsent($category);
    }

    /**
     * The legacy group ConfigNames currently granted (as stored in the cookie).
     *
     * @return array
     */
    public static function grantedGroups()
    {
        return array_values(array_filter(CookieConsent::getConsent()));
    }

    /**
     * The granted groups expressed in the standard taxonomy.
     *
     * @return array
     */
    public static function granted()
    {
        return array_values(array_unique(array_map(
            fn ($group) => CookieConsent::toStandardCategory($group),
            self::grantedGroups()
        )));
    }

    /**
     * Has the visitor made any explicit decision yet? (primary cookie present)
     *
     * @return bool
     */
    public static function hasDecision()
    {
        return (bool) (Cookie::get(CookieConsent::COOKIE_NAME) ?? '');
    }

    /**
     * The decoded sidecar meta payload, or null when absent/invalid.
     *
     * @return array|null
     */
    public static function meta()
    {
        return self::decodeMeta(Cookie::get(self::config()->get('meta_cookie_name')));
    }

    /**
     * The version the visitor last consented under (0 when unknown).
     *
     * @return int
     */
    public static function version()
    {
        $meta = self::meta();
        return (int) ($meta['v'] ?? 0);
    }

    /**
     * The pseudonymous consent id from the meta cookie (no PII), or null.
     *
     * @return string|null
     */
    public static function consentId()
    {
        $meta = self::meta();
        return $meta['i'] ?? null;
    }

    /**
     * Should the banner be (re-)shown? True when there is no decision, the stored
     * version is behind the current one, or the consent has lapsed — except within
     * the post-reject suppression window.
     *
     * @return bool
     */
    public static function needsPrompt()
    {
        return self::evaluateNeedsPrompt(
            self::meta(),
            (int) self::config()->get('consent_version'),
            (int) self::config()->get('consent_lifetime_days'),
            (int) self::config()->get('reject_suppress_days'),
            self::hasDecision(),
            time()
        );
    }

    /**
     * Pure decision logic for needsPrompt(), extracted so it is unit-testable
     * without touching cookies. All inputs are explicit.
     *
     * @param array|null $meta decoded meta payload {v,t,m,i} or null
     * @param int $currentVersion
     * @param int $lifetimeDays
     * @param int $rejectSuppressDays
     * @param bool $hasDecision
     * @param int $now unix timestamp
     * @return bool
     */
    public static function evaluateNeedsPrompt(
        $meta,
        $currentVersion,
        $lifetimeDays,
        $rejectSuppressDays,
        $hasDecision,
        $now
    ) {
        // No decision at all -> prompt.
        if (!$hasDecision) {
            return true;
        }

        // No/!invalid meta but a decision exists (e.g. a cookie from before this
        // upgrade): honour it, but re-prompt so we can record a versioned decision.
        if (!is_array($meta) || !isset($meta['t'])) {
            return true;
        }

        // Policy/declaration version moved on -> re-consent.
        if ((int) ($meta['v'] ?? 0) < (int) $currentVersion) {
            return true;
        }

        $age = $now - (int) $meta['t'];
        $method = $meta['m'] ?? self::METHOD_CUSTOM;

        // After a reject, suppress for the longer fatigue window.
        if ($method === self::METHOD_REJECT) {
            return $age > ($rejectSuppressDays * 86400);
        }

        return $age > ($lifetimeDays * 86400);
    }

    /**
     * Derive the decision method from the granted set, for the meta cookie and the
     * ConsentRecord. Pure/testable.
     *
     * @param array $granted legacy group names granted
     * @param array $all all configured legacy group names
     * @param array $required required group names
     * @return string
     */
    public static function deriveMethod($granted, $all, $required)
    {
        $norm = fn ($a) => array_values(array_unique(array_filter($a)));
        $granted = $norm($granted);
        sort($granted);
        $all = $norm($all);
        sort($all);
        $required = $norm($required);
        sort($required);

        if ($granted === $all) {
            return self::METHOD_ALL;
        }
        if ($granted === $required) {
            return self::METHOD_REJECT;
        }
        return self::METHOD_CUSTOM;
    }

    /**
     * Called from {@see CookieConsent::setConsent()} after the primary cookie is
     * written. Refreshes the sidecar meta cookie and syncs soft bridges. Never
     * throws — a bridge failure must not break a consent write.
     *
     * @param array $grantedGroups
     * @return void
     */
    public static function onConsentPersisted($grantedGroups)
    {
        try {
            self::persistMeta($grantedGroups);
            self::syncSession($grantedGroups);
        } catch (Throwable $e) {
            // best-effort only
        }
    }

    /**
     * Write/refresh the sidecar meta cookie, preserving the pseudonymous id.
     *
     * @param array $grantedGroups
     * @return array the meta payload written
     */
    public static function persistMeta($grantedGroups)
    {
        $existing = self::meta();
        $all = array_keys(CookieConsent::config()->get('cookie_groups') ?: []);
        $required = CookieConsent::config()->get('required_groups') ?: [];

        $payload = [
            'v' => (int) self::config()->get('consent_version'),
            't' => time(),
            'm' => self::deriveMethod($grantedGroups, $all, $required),
            'i' => $existing['i'] ?? self::generateConsentId(),
        ];

        $name = self::config()->get('meta_cookie_name');
        Cookie::set(
            $name,
            json_encode($payload),
            365,
            '/',
            CookieConsent::config()->get('domain') ?: null,
            !Director::isDev(),
            false,
            CookieConsent::config()->get('same_site') ?: Cookie::SAMESITE_LAX
        );

        return $payload;
    }

    /**
     * Soft analytics/marketing bridge: mirror each mapped category's granted state
     * onto its session key, so sibling modules that gate on the session (e.g.
     * silvershop/analytics' `analytics_consent`) react with zero coupling.
     *
     * @param array $grantedGroups
     * @return void
     */
    public static function syncSession($grantedGroups)
    {
        if (Director::is_cli()) {
            return;
        }
        $map = self::config()->get('session_keys') ?: [];
        if (empty($map)) {
            return;
        }

        $controller = Controller::curr();
        if (!$controller) {
            return;
        }
        $session = $controller->getRequest()->getSession();

        foreach ($map as $category => $sessionKey) {
            $group = CookieConsent::resolveGroup($category);
            $session->set($sessionKey, in_array($group, $grantedGroups, true));
        }
    }

    /**
     * Write an immutable ConsentRecord for the current state. Called once per user
     * action by the controller/form (not from the low-level cookie writer, to
     * avoid duplicate rows). Guarded + best-effort.
     *
     * @param string $source banner|preferences|xhr|policy
     * @return ConsentRecord|null
     */
    public static function log($source = 'banner')
    {
        if (!self::config()->get('log_consent')) {
            return null;
        }
        if (Director::is_cli()) {
            return null;
        }

        try {
            return ConsentRecord::record($source);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Decode the meta cookie payload. Pure/testable.
     *
     * @param string|null $raw
     * @return array|null
     */
    public static function decodeMeta($raw)
    {
        if (!$raw) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * A random, non-identifying consent id (no IP, no PII).
     *
     * @return string
     */
    public static function generateConsentId()
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Throwable $e) {
            return substr(md5(uniqid('', true)), 0, 32);
        }
    }

    /**
     * The category -> Consent Mode v2 signal map, derived from the cookie_groups
     * config (each group's `consent_modes`). Keyed by standard category.
     *
     * @return array e.g. ['statistics' => ['analytics_storage'], 'marketing' => [...]]
     */
    public static function consentModeMap()
    {
        $map = [];
        $groups = CookieConsent::config()->get('cookie_groups') ?: [];
        foreach ($groups as $groupName => $settings) {
            $signals = $settings['consent_modes'] ?? [];
            if (empty($signals)) {
                continue;
            }
            $category = CookieConsent::toStandardCategory($groupName);
            $map[$category] = array_values($signals);
        }
        return $map;
    }

    /**
     * Every Consent Mode signal referenced by any group (the set that must be
     * defaulted to 'denied' before any tag fires).
     *
     * @return array
     */
    public static function allConsentModeSignals()
    {
        $signals = [];
        foreach (self::consentModeMap() as $groupSignals) {
            foreach ($groupSignals as $signal) {
                $signals[$signal] = true;
            }
        }
        return array_keys($signals);
    }
}
