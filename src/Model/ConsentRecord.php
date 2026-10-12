<?php

namespace XD\CookieConsent\Model;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataObject;
use XD\CookieConsent\ConsentState;
use XD\CookieConsent\CookieConsent;

/**
 * An immutable record of a single consent decision, for GDPR Art. 7(1)
 * accountability ("be able to demonstrate who consented to what, when and under
 * which policy version").
 *
 * Deliberately pseudonymous: it stores a random consent id (from the meta cookie)
 * — never an IP address or any other PII. Rows cannot be edited or hand-created
 * through the CMS; a retention task prunes them.
 *
 * @property string $ConsentID
 * @property string $Categories
 * @property string $Groups
 * @property int $ConsentVersion
 * @property string $Method
 * @property string $Source
 * @property string $Locale
 * @property string $UserAgent
 *
 * @package XD
 * @subpackage CookieConsent
 */
class ConsentRecord extends DataObject
{
    private static $table_name = 'CookieConsent_ConsentRecord';

    private static $db = [
        'ConsentID' => 'Varchar(64)',
        'Categories' => 'Varchar(255)',
        'Groups' => 'Varchar(255)',
        'ConsentVersion' => 'Int',
        'Method' => 'Varchar(32)',
        'Source' => 'Varchar(32)',
        'Locale' => 'Varchar(16)',
        'UserAgent' => 'Varchar(255)',
    ];

    private static $indexes = [
        'ConsentID' => true,
        'Created' => true,
    ];

    private static $default_sort = 'Created DESC';

    private static $summary_fields = [
        'Created.Nice' => 'When',
        'ConsentID' => 'Consent ID',
        'Categories' => 'Categories',
        'ConsentVersion' => 'Version',
        'Method' => 'Method',
        'Source' => 'Source',
        'Locale' => 'Locale',
    ];

    private static $singular_name = 'Consent record';

    private static $plural_name = 'Consent records';

    /**
     * Build and persist a record of the current consent state.
     *
     * @param string $source banner|preferences|xhr|policy
     * @return ConsentRecord
     */
    public static function record($source = 'banner')
    {
        $groups = ConsentState::grantedGroups();
        $all = array_keys(CookieConsent::config()->get('cookie_groups') ?: []);
        $required = CookieConsent::config()->get('required_groups') ?: [];

        $ua = '';
        if ($controller = Controller::curr()) {
            $ua = (string) $controller->getRequest()->getHeader('User-Agent');
        }

        $record = self::create([
            'ConsentID' => ConsentState::consentId() ?: ConsentState::generateConsentId(),
            'Categories' => implode(',', ConsentState::granted()),
            'Groups' => implode(',', $groups),
            'ConsentVersion' => ConsentState::version(),
            'Method' => ConsentState::deriveMethod($groups, $all, $required),
            'Source' => $source,
            'Locale' => i18n::get_locale(),
            'UserAgent' => mb_substr($ua, 0, 255),
        ]);
        $record->write();

        return $record;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        // Everything is read-only proof; make the whole form read-only.
        return $fields->makeReadonly();
    }

    public function canView($member = null)
    {
        return true;
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }

    public function canCreate($member = null, $context = [])
    {
        return false;
    }
}
