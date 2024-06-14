<?php

namespace XD\CookieConsent\Forms;

use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\LiteralField;
use XD\CookieConsent\CookieConsent;
use XD\CookieConsent\Model\CookieGroup;
use XD\CookieConsent\Model\CookiePolicyPage;
use XD\CookieConsent\Control\CookiePolicyPageController;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;

/**
 * Class CookieConsentForm
 *
 */
class CookieConsentForm extends Form
{
    protected $extraClasses = array('cookie-consent-form');

    public function __construct(Controller $controller, $name)
    {

        $this->setName($name);
        $this->setController($controller);

        $fields = FieldList::create();
        $cookieGroups = CookieGroup::get()->filter(['Active' => 1]);
        $data = CookieConsent::getConsent();

        /** @var CookieGroup $cookieGroup */
        foreach ($cookieGroups as $cookieGroup) {
            $field = $cookieGroup->createField();
            $fields->add($field);

            if (in_array($cookieGroup->ConfigName, $data)) {
                $field->setValue(1);
            }
        }

        $actions = $this->getFormActions();

        parent::__construct($controller, $name, $fields, $actions);
    }

    public function getFormActions()
    {
        $actions = FieldList::create();
        $faStyle = 'fa-regular';

        if (get_class($this->controller) == CookiePolicyPageController::class) {
            $saveButton = FormAction::create('submitConsent', _t(__CLASS__ . '.Save', 'Save'))
                ->setUseButtonTag(true)
                ->setButtonContent("<i class='{$faStyle} fa-save'></i> " . _t(__CLASS__ . '.Save', 'Save'))
                ->addExtraClass('btn-primary me-2 mb-1');

            $actions->push($saveButton);
        } else {
            $acceptAllButton = LiteralField::create(
                'AcceptAll',
                "<a class='btn btn-success btn-flat btn-sm me-3 mb-1' 
                id='accept-all-cookies' 
                href='{$this->getAcceptAllCookiesLink()}'>
                <i class='{$faStyle} fa-check'></i> " .
                _t(CookieConsent::class . '.AcceptAllCookies', 'Accept all cookies') .
                "</a>"
            );
            $actions->push($acceptAllButton);

            $saveButton = FormAction::create('submitConsent', '')
                ->setUseButtonTag(true)
                ->setButtonContent("<i class='{$faStyle} fa-save'></i> " . _t(__CLASS__ . '.Save', 'Save'))
                ->addExtraClass('btn-link btn-sm text-body me-2 mb-1');

            $actions->push($saveButton);

            $cookiePage = CookiePolicyPage::instance();
            if ($cookiePage) {
                $policyLink = LiteralField::create(
                    'CookiePolicyLink',
                    "<a class='btn btn-link btn-sm text-body me-2 mb-1' 
                    id='cookie-policy-page' 
                    href='{$cookiePage->Link()}'>
                    <i class='{$faStyle} fa-cog'></i> " .
                    _t(CookieConsent::class . '.Settings', 'Settings') .
                    "</a>"
                );
                $actions->push($policyLink);
            }
        }

        return $actions;
    }

    public function getAcceptAllCookiesLink()
    {
        return Controller::curr()->getAcceptAllCookiesLink();
    }

    /**
     * Submit the consent
     *
     * @param $data
     * @param Form $form
     */
    public function submitConsent($data, Form $form)
    {
        CookieConsent::grant(CookieConsent::config()->get('required_groups'));
        foreach (CookieConsent::config()->get('cookie_groups') as $group => $cookies) {
            if (isset($data[$group]) && $data[$group]) {
                CookieConsent::grant($group);
            } elseif ($group !== CookieGroup::REQUIRED_DEFAULT) {
                CookieConsent::remove($group);
            }
        }

        $form->sessionMessage(_t(__CLASS__ . '.FormMessage', 'Your preferences have been saved'), 'good');
        $this->getController()->redirectBack();
    }
}
