<?php

namespace XD\CookieConsent\Tests;

use SilverStripe\Dev\SapphireTest;
use XD\CookieConsent\View\ConsentScript;

class ConsentScriptTest extends SapphireTest
{
    public function testInlineEmitsInertScript()
    {
        $html = (string) ConsentScript::inline('console.log(1)', 'statistics');
        $this->assertStringContainsString('type="text/plain"', $html);
        $this->assertStringContainsString('data-consent-category="statistics"', $html);
        $this->assertStringContainsString('console.log(1)', $html);
    }

    public function testSrcParksUrlInDataSrc()
    {
        $html = (string) ConsentScript::src('https://example.com/t.js', 'marketing', ['async' => true, 'id' => 'x']);
        $this->assertStringContainsString('type="text/plain"', $html);
        $this->assertStringContainsString('data-consent-category="marketing"', $html);
        // Real URL must NOT be in a src attribute (preload scanners would fetch it).
        $this->assertStringContainsString('data-src="https://example.com/t.js"', $html);
        $this->assertStringNotContainsString(' src="https://example.com/t.js"', $html);
        $this->assertStringContainsString(' async', $html);
        $this->assertStringContainsString('id="x"', $html);
    }

    public function testSanitiseCategory()
    {
        $this->assertSame('statistics', ConsentScript::sanitiseCategory('stati$stics'));
        $this->assertSame('statistics', ConsentScript::sanitiseCategory(''));
        $this->assertSame('marketing', ConsentScript::sanitiseCategory('marketing'));
    }
}
