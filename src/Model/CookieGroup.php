<?php

namespace XD\CookieConsent\Model;

use SilverStripe\Forms\CheckboxSetField;
use XD\CookieConsent\CookieConsent;
use XD\CookieConsent\Forms\CookieConsentCheckBoxField;
use XD\CookieConsent\Gridfield\GridFieldConfigCookies;
use Exception;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;


/**
 * CookieGroup that holds type of cookies
 * You can add these groups trough the yml config
 *
 * @package XD
 * @subpackage CookieConsent
 *
 * @property string ConfigName
 * @property string Title
 * @property string Content
 * @property boolean Active
 * @method HasManyList Cookies()
 */
class CookieGroup extends DataObject
{
    const REQUIRED_DEFAULT = 'Necessary';
    const LOCAL_PROVIDER = 'local';

    private static $table_name = 'CookieConsent_CookieGroup';

    private static $db = [
        'ConfigName' => 'Varchar(255)',
        'Active' => 'Boolean',
        'Title' => 'Varchar(255)',
        'Content' => 'HTMLText',
    ];

    private static $indexes = [
        'ConfigName' => true
    ];

    private static $has_many = [
        'Cookies' => CookieDescription::class . '.Group'
    ];

    private static $many_many = [
        'ConsentModes' => ConsentMode::class
    ];

    private static $summary_fields = [
        'ConfigName' => 'ConfigName',
        'Title' => 'Title',
        'Active.Nice' => 'Active',
        'ConsentModeList' => 'ConsentModes'
    ];

    private static $translate = [
        'Title',
        'Content'
    ];

    /**
     * @return FieldList|mixed
     */
    public function getCMSFields()
    {
        $fields = FieldList::create(TabSet::create('Root', $mainTab = Tab::create('Main')));
        $fields->addFieldsToTab('Root.Main', array(
            CheckboxField::create('Active', _t(__CLASS__ . '.Active', 'Active')),
            TextField::create('Title', _t(__CLASS__ . '.Title', 'Title')),
            HtmlEditorField::create('Content', _t(__CLASS__ . '.Content', 'Content'))->setRows(5),
            CheckboxSetField::create('ConsentModes', _t(__CLASS__ . '.ConsentModes', 'ConsentModes'), ConsentMode::get(), $this->ConsentModes()),
            GridField::create('Cookies', _t(__CLASS__ . '.Cookies', 'Cookies'), $this->Cookies(), GridFieldConfigCookies::create())
        ));

        $this->extend('updateCMSFields', $fields);
        return $fields;
    }

    public function getConsentModeList()
    {
        return implode(',', $this->ConsentModes()->column('Title')) ?: '-';
    }

    /**
     * Check if this group is the required default
     *
     * @return bool
     */
    public function isRequired()
    {
        return CookieConsent::isRequired($this->ConfigName);
    }

    /**
     * Create a Cookie Consent checkbox based on the current cookie group
     *
     * @return CookieConsentCheckBoxField
     */
    public function createField()
    {
        return new CookieConsentCheckBoxField($this);
    }

    /**
     * @throws Exception
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $cookiesConfig = CookieConsent::config()->get('cookie_groups');
        $necessaryGroups = CookieConsent::config()->get('required_groups');
        if ($cookiesConfig && $necessaryGroups) {
//            foreach (array_unique($necessaryGroups) as $necessary) {
//                if (!isset($cookiesConfig[$necessary])) {
//                    throw new Exception("The required default cookie set is missing, make sure to set the '{$necessary}' group");
//                }
//            }

            foreach ($cookiesConfig as $groupName => $groupSettings) {
                if (!$group = self::get()->find('ConfigName', $groupName)) {
                    $group = self::create(array(
                        'ConfigName' => $groupName,
                        'Active' => (bool) $groupSettings['active'],
                        'Title' => _t(__CLASS__ . ".$groupName", $groupName),
                        'Content' => _t(__CLASS__ . ".{$groupName}_Content", $groupName)
                    ));

                    $group->write();
                    DB::alteration_message(sprintf('Cookie group "%s" created', $groupName), 'created');

                    $consentModes = !empty($groupSettings['consent_modes']) ? $groupSettings['consent_modes'] : [];
                    if (!empty($consentModes)) {
                        foreach ($consentModes as $consentMode) {
                            $mode = ConsentMode::get()->filter(['Title' => $consentMode])->first();
                            $group->ConsentModes()->add($mode);
                            DB::alteration_message(sprintf('Cookie group enabled consentmode "%s"', $consentMode), 'created');
                        }
                    }

                }

//                foreach ($providers as $providerName => $cookies) {
//                    if ($providerName === self::LOCAL_PROVIDER && Director::is_cli() && $url = Environment::getEnv('SS_BASE_URL')) {
//                        $providerLabel = parse_url($url, PHP_URL_HOST);
//                    } elseif ($providerName === self::LOCAL_PROVIDER) {
//                        $providerLabel = Director::host();
//                    } else {
//                        $providerLabel = str_replace('_', '.', $providerName);
//                    }
//
//                    foreach ($cookies as $cookieName) {
//                        $cookie = CookieDescription::get()->filter(array(
//                            'ConfigName' => $cookieName,
//                            'Provider' => $providerLabel
//                        ))->first();
//
//                        if (!$cookie) {
//                            $cookie = CookieDescription::create(array(
//                                'ConfigName' => $cookieName,
//                                'Title' => $cookieName,
//                                'Provider' => $providerLabel,
//                                'Purpose' => _t("CookieConsent_{$providerName}.{$cookieName}_Purpose", "$cookieName"),
//                                'Expiry' => _t("CookieConsent_{$providerName}.{$cookieName}_Expiry", 'Session')
//                            ));
//
//                            $group->Cookies()->add($cookie);
//                            $cookie->flushCache();
//                            DB::alteration_message(sprintf('Cookie "%s" created and added to group "%s"', $cookieName, $groupName), 'created');
//                        }
//                    }
//                }

                $group->flushCache();
            }
        }
    }

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * Make deletable if not defined in config
     *
     * @param null $member
     * @return bool
     */
    public function canDelete($member = null)
    {
        $cookieConfig = CookieConsent::config()->get('cookies');
        return !isset($cookieConfig[$this->ConfigName]);
    }
}
