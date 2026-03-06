<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\App;

/**
 * Test pluralization support in table component translations.
 *
 * Validates Requirement 52.12: Support trans_choice() for pluralization
 */
class PluralizationTest extends TestCase
{
    /**
     * Test English pluralization for items_count.
     *
     * @return void
     */
    public function test_english_items_count_pluralization(): void
    {
        App::setLocale('en');

        // Zero items
        $this->assertEquals(
            'No items',
            trans_choice('components.table.items_count', 0)
        );

        // One item
        $this->assertEquals(
            '1 item',
            trans_choice('components.table.items_count', 1)
        );

        // Multiple items
        $this->assertEquals(
            '5 items',
            trans_choice('components.table.items_count', 5)
        );

        $this->assertEquals(
            '100 items',
            trans_choice('components.table.items_count', 100)
        );
    }

    /**
     * Test English pluralization for rows_count.
     *
     * @return void
     */
    public function test_english_rows_count_pluralization(): void
    {
        App::setLocale('en');

        $this->assertEquals('No rows', trans_choice('components.table.rows_count', 0));
        $this->assertEquals('1 row', trans_choice('components.table.rows_count', 1));
        $this->assertEquals('10 rows', trans_choice('components.table.rows_count', 10));
    }

    /**
     * Test English pluralization for entries_count.
     *
     * @return void
     */
    public function test_english_entries_count_pluralization(): void
    {
        App::setLocale('en');

        $this->assertEquals('No entries', trans_choice('components.table.entries_count', 0));
        $this->assertEquals('1 entry', trans_choice('components.table.entries_count', 1));
        $this->assertEquals('25 entries', trans_choice('components.table.entries_count', 25));
    }

    /**
     * Test English pluralization for selected_items.
     *
     * @return void
     */
    public function test_english_selected_items_pluralization(): void
    {
        App::setLocale('en');

        $this->assertEquals('No items selected', trans_choice('components.table.selected_items', 0));
        $this->assertEquals('1 item selected', trans_choice('components.table.selected_items', 1));
        $this->assertEquals('3 items selected', trans_choice('components.table.selected_items', 3));
    }

    /**
     * Test English pluralization for filters_active.
     *
     * @return void
     */
    public function test_english_filters_active_pluralization(): void
    {
        App::setLocale('en');

        $this->assertEquals('No filters active', trans_choice('components.table.filters_active', 0));
        $this->assertEquals('1 filter active', trans_choice('components.table.filters_active', 1));
        $this->assertEquals('4 filters active', trans_choice('components.table.filters_active', 4));
    }

    /**
     * Test English pluralization for columns_hidden.
     *
     * @return void
     */
    public function test_english_columns_hidden_pluralization(): void
    {
        App::setLocale('en');

        $this->assertEquals('No columns hidden', trans_choice('components.table.columns_hidden', 0));
        $this->assertEquals('1 column hidden', trans_choice('components.table.columns_hidden', 1));
        $this->assertEquals('5 columns hidden', trans_choice('components.table.columns_hidden', 5));
    }

    /**
     * Test English pluralization for results_found.
     *
     * @return void
     */
    public function test_english_results_found_pluralization(): void
    {
        App::setLocale('en');

        $this->assertEquals('No results found', trans_choice('components.table.results_found', 0));
        $this->assertEquals('1 result found', trans_choice('components.table.results_found', 1));
        $this->assertEquals('50 results found', trans_choice('components.table.results_found', 50));
    }

    /**
     * Test Indonesian pluralization for items_count.
     *
     * Indonesian doesn't have plural forms, so all counts use the same form.
     *
     * @return void
     */
    public function test_indonesian_items_count_pluralization(): void
    {
        App::setLocale('id');

        $this->assertEquals('Tidak ada item', trans_choice('components.table.items_count', 0));
        $this->assertEquals('1 item', trans_choice('components.table.items_count', 1));
        $this->assertEquals('5 item', trans_choice('components.table.items_count', 5));
        $this->assertEquals('100 item', trans_choice('components.table.items_count', 100));
    }

    /**
     * Test Indonesian pluralization for selected_items.
     *
     * @return void
     */
    public function test_indonesian_selected_items_pluralization(): void
    {
        App::setLocale('id');

        $this->assertEquals('Tidak ada item dipilih', trans_choice('components.table.selected_items', 0));
        $this->assertEquals('1 item dipilih', trans_choice('components.table.selected_items', 1));
        $this->assertEquals('10 item dipilih', trans_choice('components.table.selected_items', 10));
    }

    /**
     * Test Arabic pluralization for items_count.
     *
     * Arabic has complex plural rules: zero, one, two, few (3-10), many (11+).
     *
     * @return void
     */
    public function test_arabic_items_count_pluralization(): void
    {
        App::setLocale('ar');

        // Zero
        $this->assertEquals('لا توجد عناصر', trans_choice('components.table.items_count', 0));

        // One
        $this->assertEquals('عنصر واحد', trans_choice('components.table.items_count', 1));

        // Two
        $this->assertEquals('عنصران', trans_choice('components.table.items_count', 2));

        // Few (3-10)
        $this->assertEquals('3 عناصر', trans_choice('components.table.items_count', 3));
        $this->assertEquals('5 عناصر', trans_choice('components.table.items_count', 5));
        $this->assertEquals('10 عناصر', trans_choice('components.table.items_count', 10));

        // Many (11+)
        $this->assertEquals('11 عنصر', trans_choice('components.table.items_count', 11));
        $this->assertEquals('100 عنصر', trans_choice('components.table.items_count', 100));
    }

    /**
     * Test Arabic pluralization for rows_count.
     *
     * @return void
     */
    public function test_arabic_rows_count_pluralization(): void
    {
        App::setLocale('ar');

        $this->assertEquals('لا توجد صفوف', trans_choice('components.table.rows_count', 0));
        $this->assertEquals('صف واحد', trans_choice('components.table.rows_count', 1));
        $this->assertEquals('صفان', trans_choice('components.table.rows_count', 2));
        $this->assertEquals('5 صفوف', trans_choice('components.table.rows_count', 5));
        $this->assertEquals('20 صف', trans_choice('components.table.rows_count', 20));
    }

    /**
     * Test Arabic pluralization for selected_items.
     *
     * @return void
     */
    public function test_arabic_selected_items_pluralization(): void
    {
        App::setLocale('ar');

        $this->assertEquals('لا توجد عناصر محددة', trans_choice('components.table.selected_items', 0));
        $this->assertEquals('عنصر واحد محدد', trans_choice('components.table.selected_items', 1));
        $this->assertEquals('عنصران محددان', trans_choice('components.table.selected_items', 2));
        $this->assertEquals('4 عناصر محددة', trans_choice('components.table.selected_items', 4));
        $this->assertEquals('15 عنصر محدد', trans_choice('components.table.selected_items', 15));
    }

    /**
     * Test pluralization with parameter substitution.
     *
     * @return void
     */
    public function test_pluralization_with_parameters(): void
    {
        App::setLocale('en');

        // Test that :count parameter is properly substituted
        $result = trans_choice('components.table.items_count', 5);
        $this->assertStringContainsString('5', $result);
        $this->assertStringContainsString('items', $result);

        $result = trans_choice('components.table.selected_items', 3);
        $this->assertStringContainsString('3', $result);
        $this->assertStringContainsString('items selected', $result);
    }

    /**
     * Test all pluralization keys exist in all locales.
     *
     * @return void
     */
    public function test_all_pluralization_keys_exist(): void
    {
        $keys = [
            'items_count',
            'rows_count',
            'entries_count',
            'selected_items',
            'filters_active',
            'columns_hidden',
            'results_found',
        ];

        $locales = ['en', 'id', 'ar'];

        foreach ($locales as $locale) {
            App::setLocale($locale);

            foreach ($keys as $key) {
                $translation = trans_choice("components.table.{$key}", 0);

                $this->assertNotEquals(
                    "components.table.{$key}",
                    $translation,
                    "Translation key 'components.table.{$key}' not found for locale '{$locale}'"
                );
            }
        }
    }

    /**
     * Test edge cases for pluralization.
     *
     * @return void
     */
    public function test_pluralization_edge_cases(): void
    {
        App::setLocale('en');

        // Large numbers
        $this->assertEquals('1000 items', trans_choice('components.table.items_count', 1000));
        $this->assertEquals('999999 items', trans_choice('components.table.items_count', 999999));

        // Negative numbers (Laravel treats -1 as singular)
        $result = trans_choice('components.table.items_count', -1);
        $this->assertStringContainsString('item', $result);
    }
}

