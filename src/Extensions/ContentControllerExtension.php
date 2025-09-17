<?php

namespace XD\CookieConsent\Extensions;

use Exception;
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
 * @property ContentController owner
 */
class ContentControllerExtension extends Extension
{
    private static $allowed_actions = ['acceptAllCookies', 'acceptNecessaryCookies'];

    /**
     * Place the necessary js and css
     *
     * @throws \Exception
     */
    public function onAfterInit()
    {
        // if( CookieConsent::check() ) return;
        // Initialize Google Consent Mode
        if (Environment::getEnv('GTM_CODE') || Environment::getEnv('GA_CODE')) {
            Requirements::insertHeadTags("
                <script>
                    // Define dataLayer and the gtag function.
                    window.dataLayer = window.dataLayer || [];
                    function gtag(){dataLayer.push(arguments);}

                    // Set default consent to 'denied' as a placeholder
                    // Determine actual values based on your own requirements
                    gtag('consent', 'default', {
                      'ad_storage': 'denied', // Marketing
                      'ad_user_data': 'denied', // Marketing
                      'ad_personalization': 'denied', // Marketing
                      'analytics_storage': 'denied', // Analytics
                    });
                </script>"
            );
        }


        $module = ModuleLoader::getModule('xddesigners/silverstripe-cookieconsent');
        if (Config::inst()->get(CookieConsent::class, 'include_css')) {
            Requirements::css($module->getResource('client/dist/styles/cookieconsent.css')->getRelativePath());
        }

        if (Config::inst()->get(CookieConsent::class, 'include_javascript')) {
            Requirements::javascript($module->getResource('client/dist/js/cookieconsent.js')->getRelativePath());
        }

    }


    public function CookieConsentForm()
    {
        return CookieConsentForm::create($this->owner, 'CookieConsentForm');
    }

    /**
     * Method for checking cookie consent in template
     *
     * @param $group
     * @return bool
     * @throws Exception
     */
    public function CookieConsent($group = CookieConsent::NECESSARY)
    {
        // if de-activated cookieconsent returns true
        $siteConfig = SiteConfig::current_site_config();
        if (!$siteConfig->CookieConsentActive) return true;
        // return actual value
        return CookieConsent::check($group);
    }

    /**
     * Check if we can promt for concent
     * We're not on a Securty or Cooky policy page and have no concent set
     *
     * @return bool
     */
    public function PromptCookieConsent()
    {
        $siteConfig = SiteConfig::current_site_config();
        if (!$siteConfig->CookieConsentActive) return false;
        $controller = Controller::curr();
        $securiy = $controller ? $controller instanceof Security : false;
        $cookiePolicy = $controller ? $controller instanceof CookiePolicyPageController : false;
        $hasConsent = CookieConsent::check();
        $prompt = !$securiy && !$cookiePolicy && !$hasConsent;
        $this->owner->extend('updatePromptCookieConsent', $prompt);
        return $prompt;
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

        if (Director::is_ajax()) {
            return "ok";
        }

        // Get the url the same as the redirect back method gets it
        $url = $this->owner->getBackURL()
            ?: $this->owner->getReturnReferer()
                ?: Director::baseURL();

        $cachebust = uniqid();
        if (parse_url($url, PHP_URL_QUERY)) {
            $url = Director::absoluteURL("$url&acceptCookies=$cachebust");
        } else {
            $url = Director::absoluteURL("$url?acceptCookies=$cachebust");
        }

        $this->owner->redirect($url);
    }

    public function getAcceptAllCookiesLink()
    {
        return Controller::join_links($this->getOwner()->Link(), 'acceptAllCookies');
    }

    public function acceptNecessaryCookies()
    {

        CookieConsent::grant(CookieConsent::NECESSARY);

        /** @var CookieGroup $necessaryGroup */
        $necessaryGroup = CookieGroup::get()->filter(['ConfigName' => CookieConsent::NECESSARY])->first();
        if ($necessaryGroup) {
            $autoEnabledGroups = $necessaryGroup->AutoEnabledCookieGroups();
            if ($autoEnabledGroups->exists()) {
                /** @var CookieGroup $autoEnabledGroup */
                foreach ($autoEnabledGroups as $autoEnabledGroup) {
                    CookieConsent::grant($autoEnabledGroup->ConfigName);
                }
            }
        }

        if (Director::is_ajax()) {
            return "ok";
        }

        // Get the url the same as the redirect back method gets it
        $url = $this->owner->getBackURL()
            ?: $this->owner->getReturnReferer()
                ?: Director::baseURL();

        $cachebust = uniqid();
        if (parse_url($url, PHP_URL_QUERY)) {
            $url = Director::absoluteURL("$url&acceptCookies=$cachebust");
        } else {
            $url = Director::absoluteURL("$url?acceptCookies=$cachebust");
        }

        $this->owner->redirect($url);

    }

    public function getAcceptNecessaryCookiesLink()
    {
        return Controller::join_links($this->getOwner()->Link(), 'acceptNecessaryCookies');
    }

}
