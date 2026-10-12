<?php

namespace XD\CookieConsent\Tests;

use SilverStripe\Control\Cookie;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\SiteConfig\SiteConfig;
use XD\CookieConsent\CookieConsent;
use XD\CookieConsent\Shortcode\ConsentEmbed;
use XD\CookieConsent\View\ConsentTemplateProvider;

class ConsentViewTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        Cookie::force_expiry(CookieConsent::COOKIE_NAME);
    }

    public function testHasConsentGlobalWhenActive()
    {
        $config = SiteConfig::current_site_config();
        $config->CookieConsentActive = true;
        $config->write();

        Cookie::set(CookieConsent::COOKIE_NAME, 'Necessary,Analytics');

        $this->assertTrue(ConsentTemplateProvider::HasConsent('statistics'));
        $this->assertFalse(ConsentTemplateProvider::HasConsent('marketing'));
    }

    public function testHasConsentGlobalWhenInactiveGrantsAll()
    {
        $config = SiteConfig::current_site_config();
        $config->CookieConsentActive = false;
        $config->write();

        // With consent management off, gated regions still render.
        $this->assertTrue(ConsentTemplateProvider::HasConsent('marketing'));
    }

    public function testEmbedShortcodeRenders()
    {
        $html = ConsentEmbed::parse(
            ['src' => 'https://www.youtube.com/embed/xyz', 'category' => 'marketing', 'title' => 'Video'],
            null,
            null,
            'consent_embed'
        );

        $this->assertStringContainsString('data-consent-embed', $html);
        $this->assertStringContainsString('data-consent-category="marketing"', $html);
        $this->assertStringContainsString('data-embed-src="https://www.youtube.com/embed/xyz"', $html);
        $this->assertStringContainsString('consent-embed__placeholder', $html);
    }

    public function testEmbedShortcodeWithoutSrcIsEmpty()
    {
        $this->assertSame('', ConsentEmbed::parse([], null, null, 'consent_embed'));
    }
}
