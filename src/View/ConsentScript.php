<?php

namespace XD\CookieConsent\View;

use SilverStripe\ORM\FieldType\DBHTMLText;
use XD\CookieConsent\CookieConsent;

/**
 * Helper for emitting third-party tags as *inert* scripts that only run once the
 * matching consent category is granted. This is true prior blocking (ePrivacy
 * "prior opt-in"), not just Consent Mode signalling.
 *
 * The emitted markup follows the widely-used convention
 * `<script type="text/plain" data-consent-category="statistics">`; the module JS
 * ({@see client/src/js/cookieconsent.js}) activates it on load and on every
 * `consentchange`. Any script carrying these attributes is handled, however it
 * was produced — including vendor snippets a CMS editor marked by hand.
 *
 * @package XD
 * @subpackage CookieConsent
 */
class ConsentScript
{
    /**
     * Emit an inline script gated on a category.
     *
     * NOTE: the JS body must not contain the literal `</script>` sequence.
     *
     * @param string $js inline javascript body
     * @param string $category standard category or legacy group name
     * @return DBHTMLText
     */
    public static function inline($js, $category = CookieConsent::CATEGORY_STATISTICS)
    {
        $cat = self::sanitiseCategory($category);
        $html = sprintf(
            '<script type="text/plain" data-consent-category="%s">%s</script>',
            $cat,
            $js
        );
        $field = DBHTMLText::create();
        $field->setValue($html);
        return $field;
    }

    /**
     * Emit an inert external script gated on a category. The real URL is parked in
     * `data-src` and promoted to `src` on activation so preload scanners do not
     * fetch it early.
     *
     * @param string $url script src
     * @param string $category standard category or legacy group name
     * @param array $attrs extra attributes, e.g. ['async' => true, 'id' => 'x']
     * @return DBHTMLText
     */
    public static function src($url, $category = CookieConsent::CATEGORY_STATISTICS, array $attrs = [])
    {
        $cat = self::sanitiseCategory($category);
        $extra = '';
        foreach ($attrs as $name => $value) {
            $name = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $name);
            if ($value === true) {
                $extra .= ' ' . $name;
            } elseif ($value !== false && $value !== null) {
                $extra .= sprintf(' %s="%s"', $name, htmlspecialchars((string) $value, ENT_QUOTES));
            }
        }
        $html = sprintf(
            '<script type="text/plain" data-consent-category="%s" data-src="%s"%s></script>',
            $cat,
            htmlspecialchars((string) $url, ENT_QUOTES),
            $extra
        );
        $field = DBHTMLText::create();
        $field->setValue($html);
        return $field;
    }

    /**
     * @param string $category
     * @return string
     */
    public static function sanitiseCategory($category)
    {
        $category = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $category);
        return $category !== '' ? $category : CookieConsent::CATEGORY_STATISTICS;
    }
}
