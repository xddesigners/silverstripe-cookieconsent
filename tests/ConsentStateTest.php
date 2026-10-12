<?php

namespace XD\CookieConsent\Tests;

use SilverStripe\Dev\SapphireTest;
use XD\CookieConsent\ConsentState;

class ConsentStateTest extends SapphireTest
{
    public function testNeedsPromptWhenNoDecision()
    {
        $this->assertTrue(ConsentState::evaluateNeedsPrompt(null, 1, 183, 365, false, 1000));
    }

    public function testNeedsPromptWhenDecisionButNoMeta()
    {
        // A pre-upgrade cookie with no meta sidecar: re-prompt once to record a
        // versioned decision.
        $this->assertTrue(ConsentState::evaluateNeedsPrompt(null, 1, 183, 365, true, 1000));
    }

    public function testNoPromptWhenFresh()
    {
        $now = 1_000_000_000;
        $meta = ['v' => 1, 't' => $now - 10, 'm' => 'all', 'i' => 'x'];
        $this->assertFalse(ConsentState::evaluateNeedsPrompt($meta, 1, 183, 365, true, $now));
    }

    public function testPromptWhenVersionBehind()
    {
        $now = 1_000_000_000;
        $meta = ['v' => 1, 't' => $now, 'm' => 'all', 'i' => 'x'];
        $this->assertTrue(ConsentState::evaluateNeedsPrompt($meta, 2, 183, 365, true, $now));
    }

    public function testPromptWhenExpired()
    {
        $now = 1_000_000_000;
        $meta = ['v' => 1, 't' => $now - (200 * 86400), 'm' => 'all', 'i' => 'x'];
        $this->assertTrue(ConsentState::evaluateNeedsPrompt($meta, 1, 183, 365, true, $now));
    }

    public function testRejectSuppressedWithinWindow()
    {
        $now = 1_000_000_000;
        // 200 days old: past the 183-day lifetime, but inside the 365-day
        // post-reject suppression window -> do not re-prompt.
        $meta = ['v' => 1, 't' => $now - (200 * 86400), 'm' => 'reject', 'i' => 'x'];
        $this->assertFalse(ConsentState::evaluateNeedsPrompt($meta, 1, 183, 365, true, $now));
    }

    public function testRejectRePromptsBeyondWindow()
    {
        $now = 1_000_000_000;
        $meta = ['v' => 1, 't' => $now - (400 * 86400), 'm' => 'reject', 'i' => 'x'];
        $this->assertTrue(ConsentState::evaluateNeedsPrompt($meta, 1, 183, 365, true, $now));
    }

    public function testDeriveMethod()
    {
        $all = ['Necessary', 'Analytics', 'Marketing'];
        $required = ['Necessary'];
        $this->assertSame('all', ConsentState::deriveMethod($all, $all, $required));
        $this->assertSame('reject', ConsentState::deriveMethod(['Necessary'], $all, $required));
        $this->assertSame('custom', ConsentState::deriveMethod(['Necessary', 'Analytics'], $all, $required));
        // order-independent
        $this->assertSame('all', ConsentState::deriveMethod(['Marketing', 'Necessary', 'Analytics'], $all, $required));
    }

    public function testDecodeMeta()
    {
        $this->assertNull(ConsentState::decodeMeta(null));
        $this->assertNull(ConsentState::decodeMeta(''));
        $this->assertNull(ConsentState::decodeMeta('not json'));
        $decoded = ConsentState::decodeMeta('{"v":2,"m":"all"}');
        $this->assertSame(2, $decoded['v']);
        $this->assertSame('all', $decoded['m']);
    }

    public function testConsentModeMapFromConfig()
    {
        $map = ConsentState::consentModeMap();
        $this->assertArrayHasKey('statistics', $map);
        $this->assertContains('analytics_storage', $map['statistics']);
        $this->assertArrayHasKey('marketing', $map);
        $this->assertContains('ad_user_data', $map['marketing']);

        $signals = ConsentState::allConsentModeSignals();
        $this->assertContains('analytics_storage', $signals);
        $this->assertContains('ad_storage', $signals);
        $this->assertContains('ad_personalization', $signals);
    }
}
