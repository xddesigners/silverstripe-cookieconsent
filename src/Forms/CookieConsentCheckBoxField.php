<?php

namespace XD\CookieConsent\Forms;

use XD\CookieConsent\Model\CookieGroup;
use SilverStripe\Forms\CheckboxField;

/**
 * Class CookieConsentCheckBoxField
 */
class CookieConsentCheckBoxField extends CheckboxField
{
    /**
     * @var CookieGroup
     */
    protected $cookieGroup;

    public function __construct(CookieGroup $cookieGroup)
    {
        $this->cookieGroup = $cookieGroup;
        parent::__construct(
            $cookieGroup->ConfigName,
            $cookieGroup->Title,
            $cookieGroup->isRequired()
        );

        $this->setDisabled($cookieGroup->isRequired());
    }

    public function getContent()
    {
        return $this->cookieGroup->dbObject('Content');
    }

    public function getCookieGroup()
    {
        return $this->cookieGroup;
    }
}
