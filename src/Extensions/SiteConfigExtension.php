<?php

namespace XD\CookieConsent\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\LiteralField;
use XD\CookieConsent\Model\CookieGroup;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\FieldList;
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
        'HideOnlyNecessaryyButton' => 'Boolean',
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
                TextField::create('AcceptAllCookiesTitle', _t(__CLASS__ . '.AcceptAllCookiesTitle', 'Accept All Cookies Title')),
                TextField::create('AcceptOnlyNecessaryCookiesTitle', _t(__CLASS__ . '.AcceptOnlyNecessaryCookiesTitle', 'Reject / only necessary button title')),
                TextField::create('EditCookieSettingsTitle', _t(__CLASS__ . '.EditCookieSettingsTitle', 'Manage preferences button title')),
                LiteralField::create('RejectParityNote', '<p class="message notice">' . _t(
                    __CLASS__ . '.RejectParityNote',
                    'Rejecting is kept as prominent as accepting (EDPB requirement). The legacy "hide button" options have been removed; the reject and manage controls are always shown.'
                ) . '</p>'),
            ])->setTitle(_t(__CLASS__ . '.PopupSettings', 'Button labels')),
        ]);
    }

    /**
     * Set the defaults this way because the SiteConfig is probably already created
     */
    public function requireDefaultRecords()
    {
        $config = SiteConfig::current_site_config();

        if (empty($config->CookieConsentTitle)) {
            $config->CookieConsentTitle = _t(__CLASS__ . '.CookieConsentTitle', 'This website uses cookies');
        }

        if (empty($config->CookieConsentContent)) {
            $config->CookieConsentContent = _t(__CLASS__ . '.CookieConsentContent', '<p>We use cookies to personalise content, to provide social media features and to analyse our traffic. We also share information about your use of our site with our social media and analytics partners who may combine it with other information that you’ve provided to them or that they’ve collected from your use of their services. You consent to our cookies if you continue to use our website.</p>');
        }

        $config->write();
    }
}
