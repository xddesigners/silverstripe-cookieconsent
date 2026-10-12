<?php

namespace XD\CookieConsent\Admin;

use SilverStripe\Admin\ModelAdmin;
use XD\CookieConsent\Model\ConsentRecord;
use XD\CookieConsent\Model\CookieDescription;
use XD\CookieConsent\Model\CookieGroup;

/**
 * Dedicated CMS section for the cookie declaration (categories + cookies) and the
 * immutable consent log. The declaration also stays editable from Settings
 * (SiteConfig) for backwards compatibility.
 *
 * @package XD
 * @subpackage CookieConsent
 */
class ConsentAdmin extends ModelAdmin
{
    private static $url_segment = 'cookie-consent';

    private static $menu_title = 'Cookie consent';

    private static $menu_icon_class = 'font-icon-block';

    private static $managed_models = [
        CookieGroup::class,
        CookieDescription::class,
        ConsentRecord::class,
    ];
}
