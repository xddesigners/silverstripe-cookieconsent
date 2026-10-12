<?php

namespace XD\CookieConsent\Tests;

use SilverStripe\Control\Cookie;
use SilverStripe\Dev\SapphireTest;
use XD\CookieConsent\CookieConsent;
use XD\CookieConsent\Model\ConsentRecord;

class ConsentRecordTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        Cookie::force_expiry(CookieConsent::COOKIE_NAME);
    }

    public function testRecordWritesImmutableRow()
    {
        Cookie::set(CookieConsent::COOKIE_NAME, 'Necessary,Analytics');

        $record = ConsentRecord::record('banner');

        $this->assertTrue($record->isInDB());
        $this->assertSame('banner', $record->Source);
        $this->assertStringContainsString('statistics', $record->Categories);
        $this->assertStringContainsString('Analytics', $record->Groups);
        $this->assertSame('custom', $record->Method);
        $this->assertNotEmpty($record->ConsentID);
        // Pseudonymous: never store an IP.
        $this->assertStringNotContainsString('.', (string) $record->ConsentID);
    }

    public function testImmutability()
    {
        $record = ConsentRecord::record('banner');
        $this->assertFalse($record->canEdit());
        $this->assertFalse($record->canDelete());
        $this->assertFalse($record->canCreate());
        $this->assertTrue($record->canView());
    }
}
