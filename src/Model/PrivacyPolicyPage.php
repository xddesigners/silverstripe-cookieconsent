<?php

namespace XD\CookieConsent\Model;

use \Page;
use XD\CookieConsent\CookieConsent;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DB;

/**
 * Model for creating a default privacy policy page
 *
 * @package XD
 * @subpackage CookieConsent
 */
class PrivacyPolicyPage extends Page
{
    private static $table_name = 'CookieConsent_PrivacyPolicyPage';

    private static $defaults = array(
        'ShowInMenus' => 0
    );

    public function populateDefaults()
    {
        $this->Title = _t(__CLASS__ . '.Title', 'Privacy Policy');
        $this->Content = _t(__CLASS__ .'.Content', '<p>Default privacy policy</p>');
        parent::populateDefaults();
    }

    /**
     * @throws Exception
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        if (Config::inst()->get(CookieConsent::class, 'create_default_pages') && !self::get()->exists()) {
            $page = self::create();
            $page->write();
            $page->flushCache();
            DB::alteration_message('Privacy Policy page created', 'created');
        }
    }
}
