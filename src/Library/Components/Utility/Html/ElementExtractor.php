<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Html;

class ElementExtractor
{
    /**
     * Extract attribute value or the whole opening tag from provided HTML.
     *
     * BC note: Mirrors legacy canvastack_script_html_element_value behavior using regex:
     * - When $asHTML is false: return the attribute value (first capture group)
     * - When $asHTML is true: return the entire opening tag match
     * - Returns null when no match found
     */
    public static function elementValue(string $html, string $tag, string $attr, bool $asHTML = true): ?string
    {
        $pattern = "/<{$tag}\\s.*?\\b{$attr}=\"(.*?)\".*?>/si";
        $match = [];
        if (preg_match($pattern, $html, $match)) {
            return $asHTML ? ($match[0] ?? null) : ($match[1] ?? null);
        }
        return null;
    }
}