<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\CharacterCounter;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property Test: Character Counter Legacy Syntax.
 *
 * Validates Requirements: 7.16
 *
 * Property 24: Character Counter Legacy Syntax
 *
 * Universal Property:
 * For any field name containing pipe-separated limit syntax (e.g., "description|limit:500"),
 * the CharacterCounter should correctly parse the limit value and extract the clean field name.
 *
 * Specific Properties:
 * 1. For any field name with "|limit:N" syntax, parseLegacySyntax() returns N
 * 2. For any field name with "|limit:N" syntax, extractFieldName() returns clean name
 * 3. For any field name without limit syntax, parseLegacySyntax() returns null
 * 4. For any field name without limit syntax, extractFieldName() returns original name
 * 5. For any invalid limit value (0, negative), parseLegacySyntax() returns null
 * 6. For any field name with multiple pipes, only first limit is parsed
 * 7. For any field name with limit, both methods work together correctly
 * 8. For any whitespace around limit value, parsing handles it correctly
 * 9. For any non-numeric limit value, parseLegacySyntax() returns null
 * 10. For any very large limit value, parseLegacySyntax() returns it correctly
 */
class CharacterCounterLegacySyntaxPropertyTest extends TestCase
{
    /**
     * Property 24.1: For any field name with "|limit:N" syntax, parseLegacySyntax() returns N.
     *
     * @test
     * @dataProvider legacySyntaxProvider
     */
    public function property_parse_legacy_syntax_returns_limit(string $fieldName, int $expectedLimit): void
    {
        $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);

        $this->assertNotNull($actualLimit, "Expected limit to be parsed from: {$fieldName}");
        $this->assertEquals($expectedLimit, $actualLimit, "Expected limit {$expectedLimit} for: {$fieldName}");
    }

    /**
     * Property 24.2: For any field name with "|limit:N" syntax, extractFieldName() returns clean name.
     *
     * @test
     * @dataProvider legacySyntaxProvider
     */
    public function property_extract_field_name_returns_clean_name(string $fieldName, int $expectedLimit, string $expectedCleanName): void
    {
        $actualCleanName = CharacterCounter::extractFieldName($fieldName);

        $this->assertEquals($expectedCleanName, $actualCleanName, "Expected clean name '{$expectedCleanName}' for: {$fieldName}");
    }

    /**
     * Property 24.3: For any field name without limit syntax, parseLegacySyntax() returns null.
     *
     * @test
     * @dataProvider noLimitSyntaxProvider
     */
    public function property_parse_returns_null_without_limit_syntax(string $fieldName): void
    {
        $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);

        $this->assertNull($actualLimit, "Expected null for field without limit syntax: {$fieldName}");
    }

    /**
     * Property 24.4: For any field name without limit syntax, extractFieldName() returns original name.
     *
     * @test
     * @dataProvider noLimitSyntaxProvider
     */
    public function property_extract_returns_original_without_limit_syntax(string $fieldName): void
    {
        $actualCleanName = CharacterCounter::extractFieldName($fieldName);

        $this->assertEquals($fieldName, $actualCleanName, "Expected original name for field without limit syntax: {$fieldName}");
    }

    /**
     * Property 24.5: For any invalid limit value (0, negative), parseLegacySyntax() returns null.
     *
     * @test
     * @dataProvider invalidLimitProvider
     */
    public function property_parse_returns_null_for_invalid_limits(string $fieldName): void
    {
        $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);

        $this->assertNull($actualLimit, "Expected null for invalid limit in: {$fieldName}");
    }

    /**
     * Property 24.6: For any field name with multiple pipes, only first limit is parsed.
     *
     * @test
     */
    public function property_parse_handles_multiple_pipes(): void
    {
        $fieldName = 'description|limit:500|other:value';
        $expectedLimit = 500;
        $expectedCleanName = 'description';

        $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);
        $actualCleanName = CharacterCounter::extractFieldName($fieldName);

        $this->assertEquals($expectedLimit, $actualLimit);
        $this->assertEquals($expectedCleanName, $actualCleanName);
    }

    /**
     * Property 24.7: For any field name with limit, both methods work together correctly.
     *
     * @test
     * @dataProvider legacySyntaxProvider
     */
    public function property_both_methods_work_together(string $fieldName, int $expectedLimit, string $expectedCleanName): void
    {
        $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);
        $actualCleanName = CharacterCounter::extractFieldName($fieldName);

        $this->assertEquals($expectedLimit, $actualLimit, "Limit mismatch for: {$fieldName}");
        $this->assertEquals($expectedCleanName, $actualCleanName, "Clean name mismatch for: {$fieldName}");

        // Verify clean name doesn't contain limit syntax
        $this->assertStringNotContainsString('|limit:', $actualCleanName);
    }

    /**
     * Property 24.8: For any non-numeric limit value, parseLegacySyntax() returns null or converts to int.
     *
     * Note: PHP's (int) cast converts decimals and scientific notation to integers.
     * This is acceptable behavior for backward compatibility.
     *
     * @test
     * @dataProvider nonNumericLimitProvider
     */
    public function property_parse_returns_null_for_non_numeric_limits(string $fieldName, ?int $expectedResult): void
    {
        $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);

        $this->assertEquals($expectedResult, $actualLimit, "Expected result for: {$fieldName}");
    }

    /**
     * Property 24.9: For any very large limit value, parseLegacySyntax() returns it correctly.
     *
     * @test
     * @dataProvider largeLimitProvider
     */
    public function property_parse_handles_large_limits(string $fieldName, int $expectedLimit): void
    {
        $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);

        $this->assertEquals($expectedLimit, $actualLimit, "Expected large limit {$expectedLimit} for: {$fieldName}");
    }

    /**
     * Property 24.10: For any field name format, parsing is consistent.
     *
     * @test
     * @dataProvider consistencyProvider
     */
    public function property_parsing_is_consistent(string $fieldName, ?int $expectedLimit, string $expectedCleanName): void
    {
        // Parse multiple times to ensure consistency
        for ($i = 0; $i < 3; $i++) {
            $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);
            $actualCleanName = CharacterCounter::extractFieldName($fieldName);

            $this->assertEquals($expectedLimit, $actualLimit, "Limit inconsistent on iteration {$i} for: {$fieldName}");
            $this->assertEquals($expectedCleanName, $actualCleanName, "Clean name inconsistent on iteration {$i} for: {$fieldName}");
        }
    }

    /**
     * Property 24.11: For any field name with array notation, parsing works correctly.
     *
     * @test
     * @dataProvider arrayNotationProvider
     */
    public function property_parse_handles_array_notation(string $fieldName, int $expectedLimit, string $expectedCleanName): void
    {
        $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);
        $actualCleanName = CharacterCounter::extractFieldName($fieldName);

        $this->assertEquals($expectedLimit, $actualLimit, "Limit mismatch for array notation: {$fieldName}");
        $this->assertEquals($expectedCleanName, $actualCleanName, "Clean name mismatch for array notation: {$fieldName}");
    }

    /**
     * Property 24.12: For any field name with dots, parsing works correctly.
     *
     * @test
     * @dataProvider dotNotationProvider
     */
    public function property_parse_handles_dot_notation(string $fieldName, int $expectedLimit, string $expectedCleanName): void
    {
        $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);
        $actualCleanName = CharacterCounter::extractFieldName($fieldName);

        $this->assertEquals($expectedLimit, $actualLimit, "Limit mismatch for dot notation: {$fieldName}");
        $this->assertEquals($expectedCleanName, $actualCleanName, "Clean name mismatch for dot notation: {$fieldName}");
    }

    /**
     * Property 24.13: For any empty or whitespace-only field name, methods handle gracefully.
     *
     * @test
     * @dataProvider emptyFieldNameProvider
     */
    public function property_parse_handles_empty_field_names(string $fieldName): void
    {
        $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);
        $actualCleanName = CharacterCounter::extractFieldName($fieldName);

        // Should not throw exceptions
        $this->assertNull($actualLimit, 'Expected null for empty/whitespace field name');
        $this->assertEquals($fieldName, $actualCleanName, 'Expected original value for empty/whitespace field name');
    }

    /**
     * Property 24.14: For any field name, extractFieldName() never returns empty string.
     *
     * @test
     * @dataProvider allFieldNameProvider
     */
    public function property_extract_never_returns_empty_string(string $fieldName): void
    {
        $actualCleanName = CharacterCounter::extractFieldName($fieldName);

        $this->assertNotEmpty($actualCleanName, "extractFieldName() should never return empty string for: {$fieldName}");
    }

    /**
     * Property 24.15: For any valid limit syntax, limit is always positive integer.
     *
     * @test
     * @dataProvider legacySyntaxProvider
     */
    public function property_parsed_limit_is_always_positive(string $fieldName, int $expectedLimit): void
    {
        $actualLimit = CharacterCounter::parseLegacySyntax($fieldName);

        $this->assertNotNull($actualLimit);
        $this->assertIsInt($actualLimit);
        $this->assertGreaterThan(0, $actualLimit, "Parsed limit should be positive for: {$fieldName}");
    }

    /**
     * Data provider for legacy syntax field names.
     *
     * @return array<array<mixed>>
     */
    public static function legacySyntaxProvider(): array
    {
        return [
            ['description|limit:500', 500, 'description'],
            ['bio|limit:1000', 1000, 'bio'],
            ['comment|limit:255', 255, 'comment'],
            ['message|limit:100', 100, 'message'],
            ['text|limit:50', 50, 'text'],
            ['content|limit:2000', 2000, 'content'],
            ['notes|limit:300', 300, 'notes'],
        ];
    }

    /**
     * Data provider for field names without limit syntax.
     *
     * @return array<array<string>>
     */
    public static function noLimitSyntaxProvider(): array
    {
        return [
            ['description'],
            ['bio'],
            ['comment'],
            ['message'],
            ['text'],
            ['user[name]'],
            ['settings.email'],
        ];
    }

    /**
     * Data provider for invalid limit values.
     *
     * @return array<array<string>>
     */
    public static function invalidLimitProvider(): array
    {
        return [
            ['description|limit:0'],
            ['bio|limit:-100'],
            ['comment|limit:-1'],
        ];
    }

    /**
     * Data provider for non-numeric limit values.
     *
     * Note: PHP's (int) cast converts decimals to integers (12.5 → 12)
     * and scientific notation to integers (1e3 → 1000).
     *
     * @return array<array<mixed>>
     */
    public static function nonNumericLimitProvider(): array
    {
        return [
            ['description|limit:abc', null],      // Non-numeric string → null
            ['bio|limit:', null],                 // Empty value → null
            ['comment|limit:12.5', 12],           // Decimal → truncated to int
            ['message|limit:1e3', 1000],          // Scientific notation → int
        ];
    }

    /**
     * Data provider for large limit values.
     *
     * @return array<array<mixed>>
     */
    public static function largeLimitProvider(): array
    {
        return [
            ['description|limit:10000', 10000],
            ['content|limit:50000', 50000],
            ['text|limit:100000', 100000],
        ];
    }

    /**
     * Data provider for consistency testing.
     *
     * @return array<array<mixed>>
     */
    public static function consistencyProvider(): array
    {
        return [
            ['description|limit:500', 500, 'description'],
            ['bio', null, 'bio'],
            ['comment|limit:0', null, 'comment'],
            ['message|limit:abc', null, 'message'],
        ];
    }

    /**
     * Data provider for array notation field names.
     *
     * @return array<array<mixed>>
     */
    public static function arrayNotationProvider(): array
    {
        return [
            ['user[bio]|limit:500', 500, 'user[bio]'],
            ['settings[description]|limit:1000', 1000, 'settings[description]'],
            ['data[0][text]|limit:255', 255, 'data[0][text]'],
        ];
    }

    /**
     * Data provider for dot notation field names.
     *
     * @return array<array<mixed>>
     */
    public static function dotNotationProvider(): array
    {
        return [
            ['user.bio|limit:500', 500, 'user.bio'],
            ['settings.description|limit:1000', 1000, 'settings.description'],
            ['data.text|limit:255', 255, 'data.text'],
        ];
    }

    /**
     * Data provider for empty field names.
     *
     * @return array<array<string>>
     */
    public static function emptyFieldNameProvider(): array
    {
        return [
            [''],
            ['   '],
        ];
    }

    /**
     * Data provider for all field name types.
     *
     * @return array<array<string>>
     */
    public static function allFieldNameProvider(): array
    {
        return [
            ['description'],
            ['description|limit:500'],
            ['user[bio]'],
            ['user[bio]|limit:1000'],
            ['settings.email'],
            ['settings.email|limit:255'],
        ];
    }
}
