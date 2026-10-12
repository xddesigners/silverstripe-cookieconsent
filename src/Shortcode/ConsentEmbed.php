<?php

namespace XD\CookieConsent\Shortcode;

use SilverStripe\Model\ArrayData;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\View\SSViewer;
use XD\CookieConsent\CookieConsent;
use XD\CookieConsent\View\ConsentScript;

/**
 * Click-to-load placeholder for third-party embeds (YouTube, Maps, …). Renders a
 * neutral placeholder until the required consent category is granted; the module
 * JS then swaps in the real iframe (automatically on consent, or when the visitor
 * clicks "load" / "always allow").
 *
 * Usage in content:
 *   [consent_embed src="https://www.youtube.com/embed/xyz" category="marketing" title="Video"]
 *
 * @package XD
 * @subpackage CookieConsent
 */
class ConsentEmbed
{
    public static function register()
    {
        ShortcodeParser::get('default')->register(
            'consent_embed',
            [self::class, 'parse']
        );
    }

    /**
     * @param array $arguments
     * @param string|null $content
     * @param ShortcodeParser $parser
     * @param string $shortcode
     * @return string
     */
    public static function parse($arguments, $content, $parser, $shortcode)
    {
        $src = isset($arguments['src']) ? trim((string) $arguments['src']) : '';
        if ($src === '') {
            return '';
        }

        $category = !empty($arguments['category'])
            ? ConsentScript::sanitiseCategory($arguments['category'])
            : CookieConsent::CATEGORY_MARKETING;

        $data = ArrayData::create([
            'EmbedSrc' => $src,
            'Category' => $category,
            'EmbedTitle' => $arguments['title'] ?? '',
            'Height' => isset($arguments['height']) ? (int) $arguments['height'] : 0,
            'Ratio' => !empty($arguments['ratio']) ? $arguments['ratio'] : '16x9',
        ]);

        return (string) SSViewer::create('XD\\CookieConsent\\Shortcode\\ConsentEmbed')
            ->process($data);
    }
}
