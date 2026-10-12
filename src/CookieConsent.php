<?php

namespace XD\CookieConsent;

use XD\CookieConsent\Model\CookieGroup;
use Exception;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Cookie;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * Class CookieConsent
 *
 * @package XD
 * @subpackage CookieConsent
 */
class CookieConsent
{
    use Extensible;
    use Injectable;
    use Configurable;

    public const COOKIE_NAME = 'CookieConsent';
    public const NECESSARY = 'Necessary';
    public const ANALYTICS = 'Analytics';
    public const MARKETING = 'Marketing';
    public const PREFERENCES = 'Preferences';

    // Standard industry category taxonomy (Cookiebot/Klaro style). These are the
    // names the modern API (ConsentState / $HasConsent / window.XDConsent) speaks;
    // they are mapped onto the legacy group ConfigNames via `category_aliases` so
    // existing configs and consumers keep working unchanged.
    public const CATEGORY_NECESSARY = 'necessary';
    public const CATEGORY_PREFERENCES = 'preferences';
    public const CATEGORY_STATISTICS = 'statistics';
    public const CATEGORY_MARKETING = 'marketing';

    private static $required_groups = [
        self::NECESSARY,
    ];

    /**
     * Maps the standard category taxonomy onto the legacy group ConfigNames that
     * are actually stored in the consent cookie and the CookieGroup records. This
     * is the backwards-compatibility seam: `ConsentState::has('statistics')`
     * resolves to the legacy `Analytics` group. Override per-site if your groups
     * differ.
     *
     * @var array
     */
    private static $category_aliases = [
        self::CATEGORY_NECESSARY => self::NECESSARY,
        self::CATEGORY_PREFERENCES => self::PREFERENCES,
        self::CATEGORY_STATISTICS => self::ANALYTICS,
        self::CATEGORY_MARKETING => self::MARKETING,
    ];

    private static $cookies = [];

    private static $same_site = Cookie::SAMESITE_LAX;

    private static $include_javascript = true;

    private static $include_css = true;

    private static $create_default_pages = true;

    /**
     * Check if there is consent for the given cookie
     *
     * @param $group
     * @return bool
     * @throws Exception
     */
    public static function check($group = CookieConsent::NECESSARY)
    {
        $cookies = self::config()->get('cookie_groups');
        if (!isset($cookies[$group])) {
            throw new Exception(sprintf(
                "The cookie group '%s' is not configured. You need to add it to the cookies config on %s",
                $group,
                self::class
            ));
        }

        $consent = self::getConsent();
        return array_search($group, $consent) !== false;
    }

    /**
     * Resolve a standard taxonomy category (necessary/preferences/statistics/marketing)
     * to the legacy group ConfigName it is aliased to. Legacy names (and any
     * unmapped name) are passed through unchanged, so both taxonomies work.
     *
     * @param string $category
     * @return string
     */
    public static function resolveGroup($category)
    {
        $aliases = self::config()->get('category_aliases') ?: [];
        return $aliases[$category] ?? $category;
    }

    /**
     * The reverse of resolveGroup(): map a legacy group ConfigName back to its
     * standard taxonomy category, or return it unchanged when not aliased.
     *
     * @param string $group
     * @return string
     */
    public static function toStandardCategory($group)
    {
        $aliases = self::config()->get('category_aliases') ?: [];
        $flipped = array_flip($aliases);
        return $flipped[$group] ?? $group;
    }

    /**
     * The configured standard categories (the alias keys).
     *
     * @return array
     */
    public static function standardCategories()
    {
        return array_keys(self::config()->get('category_aliases') ?: []);
    }

    /**
     * A non-throwing consent check that accepts either a standard category or a
     * legacy group name. Unlike check(), unknown groups return false instead of
     * throwing — the safe entry point for the modern ConsentState API.
     *
     * @param string $category
     * @return bool
     */
    public static function hasConsent($category)
    {
        $group = self::resolveGroup($category);
        $groups = self::config()->get('cookie_groups') ?: [];
        if (!isset($groups[$group])) {
            return false;
        }
        return in_array($group, self::getConsent(), true);
    }

    /**
     * Grant consent for the given cookie group
     *
     * @param $group
     */
    public static function grant($group)
    {
        $consent = self::getConsent();
        if (is_array($group)) {
            $consent = array_merge($consent, $group);
        } else {
            array_push($consent, $group);
        }
        self::setConsent($consent);
    }

    /**
     * Grant consent for all the configured cookie groups
     */
    public static function grantAll()
    {
        $consent = array_keys(Config::inst()->get(CookieConsent::class, 'cookie_groups'));
        self::setConsent($consent);
    }

    /**
     * Remove consent for the given cookie group
     *
     * @param $group
     */
    public static function remove($group)
    {
        $consent = self::getConsent();
        $key = array_search($group, $consent);
        $cookieGroups = Config::inst()->get(CookieConsent::class, 'cookie_groups');
        if (isset($cookieGroups[$group]) && isset($cookieGroups[$group]['cookies'])) {
            foreach ($cookieGroups[$group]['cookies'] as $host => $cookies) {
                $host = ($host === CookieGroup::LOCAL_PROVIDER)
                    ? Director::host()
                    : str_replace('_', '.', $host);
                foreach ($cookies as $cookie) {
                    Cookie::force_expiry($cookie, null, $host);
                }
            }
        }

        unset($consent[$key]);
        self::setConsent($consent);
    }

    /**
     * Get the current configured consent
     *
     * @return array
     */
    public static function getConsent()
    {
        return explode(',', Cookie::get(CookieConsent::COOKIE_NAME) ?? '');
    }

    /**
     * Save the consent
     *
     * @param $consent
     */
    public static function setConsent($consent)
    {
        $consent = array_values(array_filter(array_unique(array_merge($consent, self::config()->get('required_groups')))));
        $domain = self::config()->get('domain') ?: null;
        $secure = !Director::isDev(); // only secure in live to allow cross domain cookies
        $httpOnly = false; // allow js access
        $sameSite = self::config()->get('same_site') ?: Cookie::SAMESITE_LAX;

        // Use the framework Cookie API (SS6 supports per-cookie SameSite): it
        // sends the proper Set-Cookie header at response time, keeps same-request
        // reads consistent, and is unit-testable via the CookieJar.
        Cookie::set(self::COOKIE_NAME, implode(',', $consent), 365, '/', $domain, $secure, $httpOnly, $sameSite);

        // Refresh the sidecar meta cookie (version/timestamp/method/id) and sync
        // any soft integration bridges. Best-effort — never break a consent write.
        ConsentState::onConsentPersisted($consent);
    }

    /**
     * Check if the group is required
     *
     * @param $group
     * @return bool
     */
    public static function isRequired($group)
    {
        return in_array($group, self::config()->get('required_groups'));
    }
}
