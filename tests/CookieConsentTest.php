<?php

namespace XD\CookieConsent\Tests;

use SilverStripe\Control\Cookie;
use SilverStripe\Dev\SapphireTest;
use XD\CookieConsent\ConsentState;
use XD\CookieConsent\CookieConsent;

class CookieConsentTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Cookie::force_expiry(CookieConsent::COOKIE_NAME);
        Cookie::force_expiry('CookieConsentMeta');
    }

    public function testResolveGroupMapsStandardToLegacy()
    {
        $this->assertSame('Analytics', CookieConsent::resolveGroup('statistics'));
        $this->assertSame('Marketing', CookieConsent::resolveGroup('marketing'));
        // Legacy names and unknown names pass through unchanged (BC).
        $this->assertSame('Analytics', CookieConsent::resolveGroup('Analytics'));
        $this->assertSame('whatever', CookieConsent::resolveGroup('whatever'));
    }

    public function testToStandardCategory()
    {
        $this->assertSame('statistics', CookieConsent::toStandardCategory('Analytics'));
        $this->assertSame('necessary', CookieConsent::toStandardCategory('Necessary'));
        $this->assertSame('marketing', CookieConsent::toStandardCategory('Marketing'));
    }

    public function testStandardCategories()
    {
        $cats = CookieConsent::standardCategories();
        $this->assertContains('statistics', $cats);
        $this->assertContains('marketing', $cats);
        $this->assertContains('necessary', $cats);
    }

    public function testHasConsentReadsCookie()
    {
        Cookie::set(CookieConsent::COOKIE_NAME, 'Necessary,Analytics');

        $this->assertTrue(CookieConsent::hasConsent('statistics'));
        $this->assertTrue(CookieConsent::hasConsent('Analytics'));
        $this->assertFalse(CookieConsent::hasConsent('marketing'));
        // No Preferences group is configured by default -> graceful false (no throw).
        $this->assertFalse(CookieConsent::hasConsent('preferences'));
        // BC: the throwing legacy check() still works.
        $this->assertTrue(CookieConsent::check('Analytics'));
    }

    public function testSetConsentRoundTripAndMeta()
    {
        CookieConsent::setConsent(['Analytics']);

        $granted = CookieConsent::getConsent();
        $this->assertContains('Necessary', $granted, 'required group auto-added');
        $this->assertContains('Analytics', $granted);

        $this->assertTrue(ConsentState::has('statistics'));
        $this->assertFalse(ConsentState::has('marketing'));

        $meta = ConsentState::meta();
        $this->assertIsArray($meta);
        $this->assertSame('custom', $meta['m']);
        $this->assertNotEmpty($meta['i']);
        $this->assertSame(1, (int) $meta['v']);
    }

    public function testRejectMethodDerivedWhenOnlyNecessary()
    {
        CookieConsent::setConsent([]); // only the required group survives
        $meta = ConsentState::meta();
        $this->assertSame('reject', $meta['m']);
        $this->assertSame(['necessary'], ConsentState::granted());
    }
}
