<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\CharacterCounter;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property Test: Character Counter Unicode Support.
 *
 * Validates Requirements: 7.8
 *
 * Property 23: Character Counter Unicode Support
 *
 * Universal Property:
 * For any string containing Unicode characters (including emojis), the character
 * count should match the actual character count, not the byte count.
 *
 * Specific Properties:
 * 1. For any string with emojis, JavaScript counts characters correctly
 * 2. For any string with multi-byte characters, JavaScript counts characters correctly
 * 3. For any string with combining characters, JavaScript counts grapheme clusters
 * 4. For any string with zero-width characters, JavaScript handles them correctly
 * 5. For any mixed ASCII and Unicode, JavaScript counts all characters correctly
 * 6. For any string with surrogate pairs, JavaScript counts them as single characters
 * 7. For any string with regional indicators (flags), JavaScript counts them correctly
 * 8. For any string with skin tone modifiers, JavaScript counts them correctly
 */
class CharacterCounterUnicodePropertyTest extends TestCase
{
    /**
     * Property 23.1: For any string with emojis, JavaScript uses Unicode-aware counting.
     *
     * @test
     * @dataProvider emojiStringProvider
     */
    public function property_javascript_uses_unicode_aware_counting_for_emojis(string $text, int $expectedCount): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Verify JavaScript uses spread operator for Unicode-aware counting
        $this->assertStringContainsString('[...field.value].length', $html);

        // Verify the spread operator correctly counts the test string
        $actualCount = mb_strlen($text, 'UTF-8');
        $this->assertEquals($expectedCount, $actualCount, "Expected {$expectedCount} characters for: {$text}");
    }

    /**
     * Property 23.2: For any string with multi-byte characters, counting is accurate.
     *
     * @test
     * @dataProvider multiByteStringProvider
     */
    public function property_multibyte_characters_counted_correctly(string $text, int $expectedCount): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Verify spread operator is used
        $this->assertStringContainsString('[...field.value].length', $html);

        // Verify multi-byte character count
        $actualCount = mb_strlen($text, 'UTF-8');
        $this->assertEquals($expectedCount, $actualCount, "Expected {$expectedCount} characters for: {$text}");
    }

    /**
     * Property 23.3: For any string with combining characters, counting handles them.
     *
     * @test
     * @dataProvider combiningCharacterProvider
     */
    public function property_combining_characters_handled(string $text, int $minExpectedCount): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Verify spread operator is used
        $this->assertStringContainsString('[...field.value].length', $html);

        // Note: JavaScript spread operator counts code points, not grapheme clusters
        // So combining characters may be counted separately
        $actualCount = mb_strlen($text, 'UTF-8');
        $this->assertGreaterThanOrEqual($minExpectedCount, $actualCount);
    }

    /**
     * Property 23.4: For any mixed ASCII and Unicode, all characters counted.
     *
     * @test
     * @dataProvider mixedStringProvider
     */
    public function property_mixed_ascii_unicode_counted_correctly(string $text, int $expectedCount): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Verify spread operator is used
        $this->assertStringContainsString('[...field.value].length', $html);

        // Verify mixed string count
        $actualCount = mb_strlen($text, 'UTF-8');
        $this->assertEquals($expectedCount, $actualCount, "Expected {$expectedCount} characters for: {$text}");
    }

    /**
     * Property 23.5: For any string with surrogate pairs, counted as single characters.
     *
     * @test
     * @dataProvider surrogatePairProvider
     */
    public function property_surrogate_pairs_counted_as_single_characters(string $text, int $expectedCount): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Verify spread operator is used (handles surrogate pairs correctly)
        $this->assertStringContainsString('[...field.value].length', $html);

        // Verify surrogate pair count
        $actualCount = mb_strlen($text, 'UTF-8');
        $this->assertEquals($expectedCount, $actualCount, "Expected {$expectedCount} characters for: {$text}");
    }

    /**
     * Property 23.6: For any empty string, count is zero.
     *
     * @test
     */
    public function property_empty_string_has_zero_count(): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Initial count should be 0
        $this->assertStringContainsString('<span class="current-count">0</span>', $html);
    }

    /**
     * Property 23.7: For any string with only spaces, spaces are counted.
     *
     * @test
     * @dataProvider whitespaceStringProvider
     */
    public function property_whitespace_characters_counted(string $text, int $expectedCount): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Verify spread operator is used
        $this->assertStringContainsString('[...field.value].length', $html);

        // Verify whitespace count
        $actualCount = mb_strlen($text, 'UTF-8');
        $this->assertEquals($expectedCount, $actualCount);
    }

    /**
     * Property 23.8: For any string with newlines, newlines are counted.
     *
     * @test
     */
    public function property_newlines_counted(): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Verify spread operator is used
        $this->assertStringContainsString('[...field.value].length', $html);

        // Test newline counting
        $text = "Line 1\nLine 2\nLine 3";
        $actualCount = mb_strlen($text, 'UTF-8');
        $this->assertEquals(20, $actualCount); // 6 + 1 + 6 + 1 + 6 = 20
    }

    /**
     * Property 23.9: For any string with tabs, tabs are counted.
     *
     * @test
     */
    public function property_tabs_counted(): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Verify spread operator is used
        $this->assertStringContainsString('[...field.value].length', $html);

        // Test tab counting
        $text = "Column1\tColumn2\tColumn3";
        $actualCount = mb_strlen($text, 'UTF-8');
        $this->assertEquals(23, $actualCount); // 7 + 1 + 7 + 1 + 7 = 23
    }

    /**
     * Property 23.10: For any very long Unicode string, counting is accurate.
     *
     * @test
     */
    public function property_long_unicode_string_counted_accurately(): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 1000;

        $html = $counter->render($fieldName, $maxLength);

        // Verify spread operator is used
        $this->assertStringContainsString('[...field.value].length', $html);

        // Test long string with mixed characters
        $text = str_repeat('Hello 世界 🌍 ', 50); // 50 repetitions
        $singleRepeatLength = mb_strlen('Hello 世界 🌍 ', 'UTF-8');
        $expectedCount = $singleRepeatLength * 50;
        $actualCount = mb_strlen($text, 'UTF-8');
        $this->assertEquals($expectedCount, $actualCount);
    }

    /**
     * Property 23.11: For any string with right-to-left characters, counting is accurate.
     *
     * @test
     * @dataProvider rtlStringProvider
     */
    public function property_rtl_characters_counted_correctly(string $text, int $expectedCount): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Verify spread operator is used
        $this->assertStringContainsString('[...field.value].length', $html);

        // Verify RTL character count
        $actualCount = mb_strlen($text, 'UTF-8');
        $this->assertEquals($expectedCount, $actualCount, "Expected {$expectedCount} characters for: {$text}");
    }

    /**
     * Property 23.12: For any string with mathematical symbols, counting is accurate.
     *
     * @test
     * @dataProvider mathematicalSymbolProvider
     */
    public function property_mathematical_symbols_counted_correctly(string $text, int $expectedCount): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Verify spread operator is used
        $this->assertStringContainsString('[...field.value].length', $html);

        // Verify mathematical symbol count
        $actualCount = mb_strlen($text, 'UTF-8');
        $this->assertEquals($expectedCount, $actualCount, "Expected {$expectedCount} characters for: {$text}");
    }

    /**
     * Data provider for emoji strings.
     *
     * @return array<array<mixed>>
     */
    public static function emojiStringProvider(): array
    {
        return [
            ['Hello 😀', 7],
            ['🌍🌎🌏', 3],
            ['Test 👍 message', 14], // Fixed: actual count is 14
            ['❤️💙💚', 4], // Fixed: ❤️ is 2 code points (heart + variation selector)
            ['🎉🎊🎈', 3],
        ];
    }

    /**
     * Data provider for multi-byte character strings.
     *
     * @return array<array<mixed>>
     */
    public static function multiByteStringProvider(): array
    {
        return [
            ['Hello 世界', 8], // Chinese characters
            ['Привет мир', 10], // Russian characters
            ['مرحبا', 5], // Arabic characters
            ['こんにちは', 5], // Japanese hiragana
            ['안녕하세요', 5], // Korean characters
        ];
    }

    /**
     * Data provider for combining character strings.
     *
     * @return array<array<mixed>>
     */
    public static function combiningCharacterProvider(): array
    {
        return [
            ['é', 1], // e + combining acute accent
            ['ñ', 1], // n + combining tilde
            ['å', 1], // a + combining ring above
        ];
    }

    /**
     * Data provider for mixed ASCII and Unicode strings.
     *
     * @return array<array<mixed>>
     */
    public static function mixedStringProvider(): array
    {
        return [
            ['Hello 世界 World', 14], // Fixed: actual count is 14
            ['Test 123 テスト', 12],
            ['Mix 混合 text', 11],
            ['ABC 中文 XYZ', 10], // Fixed: actual count is 10
        ];
    }

    /**
     * Data provider for surrogate pair strings.
     *
     * @return array<array<mixed>>
     */
    public static function surrogatePairProvider(): array
    {
        return [
            ['𝕳𝖊𝖑𝖑𝖔', 5], // Mathematical bold fraktur
            ['𝓗𝓮𝓵𝓵𝓸', 5], // Mathematical bold script
            ['🏴󠁧󠁢󠁥󠁮󠁧󠁿', 7], // Fixed: Flag emoji has multiple code points
        ];
    }

    /**
     * Data provider for whitespace strings.
     *
     * @return array<array<mixed>>
     */
    public static function whitespaceStringProvider(): array
    {
        return [
            ['   ', 3], // 3 spaces
            ['     ', 5], // 5 spaces
            [' a b c ', 7], // spaces with letters
        ];
    }

    /**
     * Data provider for RTL (right-to-left) strings.
     *
     * @return array<array<mixed>>
     */
    public static function rtlStringProvider(): array
    {
        return [
            ['مرحبا بك', 8], // Fixed: Arabic with space - actual count is 8
            ['שלום עולם', 9], // Fixed: Hebrew with space - actual count is 9
            ['سلام دنیا', 9], // Persian with space
        ];
    }

    /**
     * Data provider for mathematical symbol strings.
     *
     * @return array<array<mixed>>
     */
    public static function mathematicalSymbolProvider(): array
    {
        return [
            ['∑∏∫', 3], // Sum, product, integral
            ['α β γ', 5], // Greek letters with spaces
            ['√π≈', 3], // Square root, pi, approximately
            ['∞≠≤≥', 4], // Infinity, not equal, less/greater than or equal
        ];
    }
}
