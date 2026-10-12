<?php

namespace XD\CookieConsent\View;

use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\TemplateGlobalProvider;
use XD\CookieConsent\ConsentState;
use XD\CookieConsent\CookieConsent;

/**
 * Exposes the modern consent-state API to every template (header/footer shells
 * included, where there is no page controller):
 *
 *   <% if $HasConsent('statistics') %> ... <% end_if %>
 *   <% if $CookieConsentNeedsPrompt %> ... <% end_if %>
 *
 * This is the clean replacement for the controller-scoped $CookieConsent('Analytics')
 * helper, which only exists on ContentController templates.
 *
 * @package XD
 * @subpackage CookieConsent
 */
class ConsentTemplateProvider implements TemplateGlobalProvider
{
    public static function get_template_global_variables()
    {
        return [
            'HasConsent' => 'HasConsent',
            'CookieConsentNeedsPrompt' => 'NeedsPrompt',
            'CookieConsentVersion' => 'Version',
        ];
    }

    /**
     * @param string $category standard category or legacy group name
     * @return bool
     */
    public static function HasConsent($category = CookieConsent::CATEGORY_NECESSARY)
    {
        // When consent management is switched off, treat everything as granted so
        // gated template regions still render.
        $config = SiteConfig::current_site_config();
        if (!$config->CookieConsentActive) {
            return true;
        }
        return ConsentState::has($category);
    }

    /**
     * @return bool
     */
    public static function NeedsPrompt()
    {
        $config = SiteConfig::current_site_config();
        if (!$config->CookieConsentActive) {
            return false;
        }
        return ConsentState::needsPrompt();
    }

    /**
     * @return int
     */
    public static function Version()
    {
        return ConsentState::version();
    }
}
