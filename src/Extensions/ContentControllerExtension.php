<?php

namespace XD\CookieConsent\Extensions;

use Exception;
use XD\CookieConsent\ConsentState;
use XD\CookieConsent\CookieConsent;
use XD\CookieConsent\Model\CookieGroup;
use XD\CookieConsent\Control\CookiePolicyPageController;
use XD\CookieConsent\Model\CookiePolicyPage;
use XD\CookieConsent\Forms\CookieConsentForm;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Environment;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;

/**
 * Class ContentControllerExtension
 * @package XD\CookieConsent
 * @property ContentController $owner
 */
class ContentControllerExtension extends Extension
{
    private static $allowed_actions = [
        'acceptAllCookies',
        'acceptNecessaryCookies',
        'saveCookieConsent',
    ];

    /**
     * Place the necessary js and css + the client config + the Consent Mode
     * default-denied signal (when a tag manager env is configured).
     *
     * @throws \Exception
     */
    public function onAfterInit()
    {
        // Initialise Google Consent Mode v2: default every referenced signal to
        // 'denied' *before* any tag. The signal set is now derived from config
        // (each cookie group's `consent_modes`) rather than hardcoded. Kept
        // env-gated for backwards compatibility.
        if (Environment::getEnv('GTM_CODE') || Environment::getEnv('GA_CODE')) {
            $defaults = [];
            foreach (ConsentState::allConsentModeSignals() as $signal) {
                $defaults[$signal] = 'denied';
            }
            if (!empty($defaults)) {
                Requirements::insertHeadTags(
                    '<script>'
                    . 'window.dataLayer = window.dataLayer || [];'
                    . 'function gtag(){dataLayer.push(arguments);}'
                    . "gtag('consent', 'default', " . json_encode($defaults) . ');'
                    . '</script>',
                    'CookieConsentDefault'
                );
            }
        }

        // Expose the consent configuration to the client so the JS drives itself
        // from config/CMS rather than hardcoded names.
        Requirements::insertHeadTags(
            '<script>window.XDConsentConfig = ' . json_encode($this->getClientConfig()) . ';</script>',
            'XDConsentConfig'
        );

        $module = ModuleLoader::getModule('xddesigners/silverstripe-cookieconsent');
        if (Config::inst()->get(CookieConsent::class, 'include_css')) {
            Requirements::css($module->getResource('client/dist/styles/cookieconsent.css')->getRelativePath());
        }

        if (Config::inst()->get(CookieConsent::class, 'include_javascript')) {
            Requirements::javascript($module->getResource('client/dist/js/cookieconsent.js')->getRelativePath());
        }
    }

    /**
     * The config object injected as window.XDConsentConfig.
     *
     * @return array
     */
    public function getClientConfig()
    {
        return [
            'cookieName' => CookieConsent::COOKIE_NAME,
            'metaCookieName' => ConsentState::config()->get('meta_cookie_name'),
            'groups' => array_keys(CookieConsent::config()->get('cookie_groups') ?: []),
            'required' => array_values(CookieConsent::config()->get('required_groups') ?: []),
            'aliases' => CookieConsent::config()->get('category_aliases') ?: [],
            'consentModes' => ConsentState::consentModeMap(),
            'version' => (int) ConsentState::config()->get('consent_version'),
            'needsPrompt' => ConsentState::needsPrompt(),
            'acceptAllLink' => $this->getAcceptAllCookiesLink(),
            'acceptNecessaryLink' => $this->getAcceptNecessaryCookiesLink(),
            'saveLink' => $this->getSaveCookieConsentLink(),
        ];
    }

    public function CookieConsentForm()
    {
        return CookieConsentForm::create($this->owner, 'CookieConsentForm');
    }

    /**
     * Method for checking cookie consent in template (legacy, controller-scoped).
     * Prefer the $HasConsent('statistics') global for new templates.
     *
     * @param $group
     * @return bool
     * @throws Exception
     */
    public function CookieConsent($group = CookieConsent::NECESSARY)
    {
        // if de-activated cookieconsent returns true
        $siteConfig = SiteConfig::current_site_config();
        if (!$siteConfig->CookieConsentActive) {
            return true;
        }
        // return actual value
        return CookieConsent::check($group);
    }

    /**
     * Check if we should prompt for consent. Not on a Security or Cookie policy
     * page, consent management is active, and the versioned state says we need a
     * (re-)decision.
     *
     * @return bool
     */
    public function PromptCookieConsent()
    {
        $siteConfig = SiteConfig::current_site_config();
        if (!$siteConfig->CookieConsentActive) {
            return false;
        }
        $controller = Controller::curr();
        $security = $controller ? $controller instanceof Security : false;
        $cookiePolicy = $controller ? $controller instanceof CookiePolicyPageController : false;
        $prompt = !$security && !$cookiePolicy && ConsentState::needsPrompt();
        $this->owner->extend('updatePromptCookieConsent', $prompt);
        return $prompt;
    }

    /**
     * Expose the active cookie groups to the banner/preferences template.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function CookieConsentGroups()
    {
        return CookieGroup::get()->filter(['Active' => 1]);
    }

    /**
     * Get an instance of the cookie policy page
     *
     * @return CookiePolicyPage|DataObject
     */
    public function getCookiePolicyPage()
    {
        return CookiePolicyPage::instance();
    }

    public function acceptAllCookies()
    {
        CookieConsent::grantAll();
        ConsentState::log($this->consentSource());
        return $this->consentResponse();
    }

    public function getAcceptAllCookiesLink()
    {
        return Controller::join_links($this->getOwner()->Link(), 'acceptAllCookies');
    }

    public function acceptNecessaryCookies()
    {
        // Build the granted set once (necessary + any auto-enabled groups) so we
        // write the cookie and log exactly one decision.
        $granted = CookieConsent::config()->get('required_groups') ?: [CookieConsent::NECESSARY];

        /** @var CookieGroup|null $necessaryGroup */
        $necessaryGroup = CookieGroup::get()->filter(['ConfigName' => CookieConsent::NECESSARY])->first();
        if ($necessaryGroup) {
            foreach ($necessaryGroup->AutoEnabledCookieGroups() as $autoEnabledGroup) {
                $granted[] = $autoEnabledGroup->ConfigName;
            }
        }

        CookieConsent::setConsent($granted);
        ConsentState::log($this->consentSource());
        return $this->consentResponse();
    }

    public function getAcceptNecessaryCookiesLink()
    {
        return Controller::join_links($this->getOwner()->Link(), 'acceptNecessaryCookies');
    }

    /**
     * Save a custom set of granted groups from the in-banner preferences centre.
     * Expects a `groups` query param: a comma-separated list of legacy group
     * ConfigNames (category names are not personal data).
     *
     * @return \SilverStripe\Control\HTTPResponse|string
     */
    public function saveCookieConsent()
    {
        $request = $this->owner->getRequest();
        $requested = array_filter(array_map('trim', explode(',', (string) $request->getVar('groups'))));

        $configured = array_keys(CookieConsent::config()->get('cookie_groups') ?: []);
        $granted = array_values(array_intersect($configured, $requested));

        CookieConsent::setConsent($granted);
        ConsentState::log('preferences');
        return $this->consentResponse();
    }

    public function getSaveCookieConsentLink()
    {
        return Controller::join_links($this->getOwner()->Link(), 'saveCookieConsent');
    }

    /**
     * Where did this decision come from, for the log.
     *
     * @return string
     */
    protected function consentSource()
    {
        return Director::is_ajax() ? 'xhr' : 'banner';
    }

    /**
     * Shared response for the consent actions: "ok" for ajax, otherwise a
     * cache-busted redirect back.
     *
     * @return \SilverStripe\Control\HTTPResponse|string
     */
    protected function consentResponse()
    {
        if (Director::is_ajax()) {
            return 'ok';
        }

        $url = $this->owner->getBackURL()
            ?: $this->owner->getReturnReferer()
                ?: Director::baseURL();

        $cachebust = uniqid();
        if (parse_url($url, PHP_URL_QUERY)) {
            $url = Director::absoluteURL("$url&acceptCookies=$cachebust");
        } else {
            $url = Director::absoluteURL("$url?acceptCookies=$cachebust");
        }

        return $this->owner->redirect($url);
    }
}
