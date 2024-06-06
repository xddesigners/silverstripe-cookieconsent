<?php

namespace XD\CookieConsent\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

class ConsentMode extends DataObject
{
    private static $table_name = 'CookieConsent_ConsentMode';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $belongs_many_many = [
        'CookieGroups' => CookieGroup::class
    ];

    private static $default_values = [
        'ad_storage',
        'ad_user_data',
        'ad_personalization',
        'analytics_storage',
    ];

    public function canDelete($member = null)
    {
        return false;
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $default_values = self::config()->get('default_values');
        if (empty($default_values)) return;

        foreach ($default_values as $default_value) {
            $value = ConsentMode::get()->filter(['Title' => $default_value])->first();
            if (!$value) {
                $value = ConsentMode::create();
                $value->Title = $default_value;
                $value->write();
                DB::alteration_message(sprintf('Cookie Consent Mode "%s" created', $value), 'created');
            }
        }

    }

}