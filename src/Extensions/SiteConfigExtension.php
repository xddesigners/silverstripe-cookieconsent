<?php

namespace XD\CookieConsent\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;
use XD\CookieConsent\Model\CookieGroup;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class SiteConfigExtension
 * @package XD\CookieConsent
 */
class SiteConfigExtension extends Extension
{
    private static $db = [
        'CookieConsentActive' => 'Boolean',
        'CookieConsentTitle' => 'Varchar',
        'CookieConsentContent' => 'HTMLText',
        'HideAcceptAllCookiesButton' => 'Boolean',
        'HideOnlyNecessaryButton' => 'Boolean',
        'HideEditCookieSettingsButton' => 'Boolean',
        'AcceptAllCookiesTitle' => 'Varchar',
        'AcceptOnlyNecessaryCookiesTitle' => 'Varchar',
        'EditCookieSettingsTitle' => 'Varchar',
    ];

    private static $translate = [
        'CookieConsentTitle',
        'CookieConsentContent',
        'AcceptAllCookiesTitle',
        'AcceptOnlyNecessaryCookiesTitle',
        'EditCookieSettingsTitle',
    ];

    /**
     * @param FieldList $fields
     * @return FieldList|void
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab('Root.CookieConsent', [
            CheckboxField::create('CookieConsentActive', _t(__CLASS__ . '.CookieConsentActive', 'Cookie consent active')),
            TextField::create('CookieConsentTitle', _t(__CLASS__ . '.CookieConsentTitle', 'Cookie consent title')),
            HtmlEditorField::create('CookieConsentContent', _t(__CLASS__ . '.CookieConsentContent', 'Cookie consent content'))->setRows(6),
            GridField::create('Cookies', 'Cookies', CookieGroup::get(), GridFieldConfig_RecordEditor::create()),
            CompositeField::create([
                CheckboxField::create('HideAcceptAllCookiesButton', _t(__CLASS__ . '.HideAcceptAllCookiesButton', 'Hide accept all cookies button')),
                CheckboxField::create('HideOnlyNecessaryButton', _t(__CLASS__ . '.HideOnlyNecessaryButton', 'Hide necessary only cookies button')),
                CheckboxField::create('HideEditCookieSettingsButton', _t(__CLASS__ . '.HideEditCookieSettingsButton', 'Hide edit cookies settings button')),
                TextField::create('AcceptAllCookiesTitle', _t(__CLASS__ . '.AcceptAllCookiesTitle', 'Accept All Cookies Title')),
                TextField::create('AcceptOnlyNecessaryCookiesTitle', _t(__CLASS__ . '.AcceptOnlyNecessaryCookiesTitle', 'Accept only necessary cookies title')),
                TextField::create('EditCookieSettingsTitle', _t(__CLASS__ . '.EditCookieSettingsTitle', 'Edit cookiesettings title')),
            ])->setTitle(_t(__CLASS__ . '.PopupSettings', 'Popup settings')),
        ]);
    }

    /**
     * Set the defaults this way beacause the SiteConfig is probably already created
     *
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function requireDefaultRecords()
    {
        $config = SiteConfig::current_site_config();
        if (!$config) {
            return;
        }

        $changed = false;

        // Initial value populated once at install (empty field only). Uses dedicated .DefaultTitle/.DefaultContent
        // lang keys (separate from the .CookieConsentTitle/.CookieConsentContent field labels, so the default is
        // not tied to the CMS label text). No new DB fields — only the existing columns get their initial value;
        // per-locale values are handled by Fluent/AutoTranslate on those columns afterwards.
        if (empty($config->CookieConsentTitle)) {
            $config->CookieConsentTitle = _t(__CLASS__ . '.DefaultTitle', 'Cookies...');
            $changed = true;
        }

        if (empty($config->CookieConsentContent)) {
            $config->CookieConsentContent = _t(__CLASS__ . '.DefaultContent', '<p>We use cookies to personalise content, to provide social media features and to analyse our traffic. We also share information about your use of our site with our social media and analytics partners, who may combine it with other information that you have provided to them or that they have collected from your use of their services.</p>');
            $changed = true;
        }

        // One-off migration: this field used to be the misspelled column `HideOnlyNecessaryyButton` (double y).
        // dev/build does not drop removed columns, so copy the old value into the corrected
        // `HideOnlyNecessaryButton` field once, then retire the old column (SS renames it to _obsolete_…, so the
        // data is kept and this does not run again). Backward compatible: no data loss, no manual migration.
        $table = DataObject::getSchema()->tableName(SiteConfig::class);
        if (DB::get_schema()->hasField($table, 'HideOnlyNecessaryyButton')) {
            $legacy = SQLSelect::create('"HideOnlyNecessaryyButton"', "\"$table\"", ['"ID"' => $config->ID])
                ->execute()
                ->value();
            if ($legacy !== null && !$config->HideOnlyNecessaryButton) {
                $config->HideOnlyNecessaryButton = (bool) $legacy;
                $changed = true;
            }
            DB::get_schema()->dontRequireField($table, 'HideOnlyNecessaryyButton');
        }

        // Only persist when we actually populated a default (avoids a write on every dev/build).
        if ($changed) {
            $config->write();
        }
    }
}
