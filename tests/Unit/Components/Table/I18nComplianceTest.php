<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test i18n compliance for Table component.
 *
 * Validates Requirements 52.16 - Final i18n Compliance Audit
 */
class I18nComplianceTest extends TestCase
{
    /**
     * Test that all table translations exist in English.
     */
    public function test_all_table_translations_exist_in_english(): void
    {
        $this->app->setLocale('en');

        $requiredKeys = [
            // Search
            'components.table.search',
            'components.table.search_table',
            'components.table.search_in',
            'components.table.clear_search',

            // Sorting
            'components.table.sort_asc',
            'components.table.sort_desc',
            'components.table.unsorted',

            // Pagination
            'components.table.showing',
            'components.table.first',
            'components.table.previous',
            'components.table.next',
            'components.table.last',
            'components.table.page',
            'components.table.page_size',

            // Filtering
            'components.table.filters',
            'components.table.filter_by',
            'components.table.active_filters',
            'components.table.clear_filters',
            'components.table.clear_all',
            'components.table.apply_filters',
            'components.table.all',
            'components.table.select_date_range',
            'components.table.min',
            'components.table.max',

            // Actions
            'components.table.actions',
            'components.table.view',
            'components.table.edit',
            'components.table.delete',

            // States
            'components.table.loading',
            'components.table.no_data',
            'components.table.error',
            'components.table.retry',

            // Locale Switcher
            'components.locale_switcher.toggle',
            'components.locale_switcher.keyboard_hint',
        ];

        foreach ($requiredKeys as $key) {
            $translation = __($key);
            $this->assertNotEquals($key, $translation, "Translation missing for key: {$key}");
            $this->assertIsString($translation);
            $this->assertNotEmpty($translation);
        }
    }

    /**
     * Test that all table translations exist in Indonesian.
     */
    public function test_all_table_translations_exist_in_indonesian(): void
    {
        $this->app->setLocale('id');

        $requiredKeys = [
            'components.table.search',
            'components.table.filters',
            'components.table.filter_by',
            'components.table.clear_all',
            'components.table.all',
            'components.table.min',
            'components.table.max',
            'components.table.actions',
            'components.table.loading',
            'components.table.no_data',
            'components.locale_switcher.toggle',
        ];

        foreach ($requiredKeys as $key) {
            $translation = __($key);
            $this->assertNotEquals($key, $translation, "Indonesian translation missing for key: {$key}");
            $this->assertIsString($translation);
            $this->assertNotEmpty($translation);
        }
    }

    /**
     * Test that all table translations exist in Arabic.
     */
    public function test_all_table_translations_exist_in_arabic(): void
    {
        $this->app->setLocale('ar');

        $requiredKeys = [
            'components.table.search',
            'components.table.filters',
            'components.table.filter_by',
            'components.table.clear_all',
            'components.table.all',
            'components.table.min',
            'components.table.max',
            'components.table.actions',
            'components.table.loading',
            'components.table.no_data',
            'components.locale_switcher.toggle',
        ];

        foreach ($requiredKeys as $key) {
            $translation = __($key);
            $this->assertNotEquals($key, $translation, "Arabic translation missing for key: {$key}");
            $this->assertIsString($translation);
            $this->assertNotEmpty($translation);
        }
    }

    /**
     * Test parameter substitution in translations.
     */
    public function test_parameter_substitution_works(): void
    {
        $this->app->setLocale('en');

        // Test :from, :to, :total substitution
        $result = __('components.table.showing', ['from' => 1, 'to' => 10, 'total' => 100]);
        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('10', $result);
        $this->assertStringContainsString('100', $result);

        // Test :count substitution
        $result = __('components.table.selected_count', ['count' => 5]);
        $this->assertStringContainsString('5', $result);
    }

    /**
     * Test pluralization works correctly.
     */
    public function test_pluralization_works(): void
    {
        $this->app->setLocale('en');

        // Test zero items
        $result = trans_choice('components.table.items_count', 0);
        $this->assertStringContainsString('No items', $result);

        // Test one item
        $result = trans_choice('components.table.items_count', 1, ['count' => 1]);
        $this->assertStringContainsString('1 item', $result);

        // Test multiple items
        $result = trans_choice('components.table.items_count', 5, ['count' => 5]);
        $this->assertStringContainsString('5 items', $result);
    }

    /**
     * Test Arabic pluralization (complex rules).
     */
    public function test_arabic_pluralization(): void
    {
        $this->app->setLocale('ar');

        // Arabic has 6 plural forms
        $counts = [0, 1, 2, 3, 11, 100];

        foreach ($counts as $count) {
            $result = trans_choice('components.table.items_count', $count, ['count' => $count]);
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
            // Should contain the count
            $this->assertStringContainsString((string) $count, $result);
        }
    }

    /**
     * Test that no hardcoded text exists in Blade templates.
     */
    public function test_no_hardcoded_text_in_blade_templates(): void
    {
        $templatePath = base_path('packages/canvastack/canvastack/resources/views/canvastack/components/table/partials');

        if (!is_dir($templatePath)) {
            $this->markTestSkipped('Template directory not found');
        }

        $files = glob($templatePath . '/*.blade.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Check for common hardcoded English words that should be translated
            $hardcodedPatterns = [
                '/>\s*(Search|Filter|Sort|Page|Next|Previous|First|Last|Loading|Error|Delete|Edit|View|Actions)\s*</',
                '/placeholder=["\'](?!{{|@)(Search|Filter|Enter)[^"\']*["\']/',
                '/title=["\'](?!{{|@)(Search|Filter|Sort)[^"\']*["\']/',
            ];

            foreach ($hardcodedPatterns as $pattern) {
                preg_match_all($pattern, $content, $matches);
                if (!empty($matches[0])) {
                    $this->fail(
                        "Found potential hardcoded text in {$file}:\n" .
                        implode("\n", array_slice($matches[0], 0, 3))
                    );
                }
            }
        }

        $this->assertTrue(true, 'No hardcoded text found in Blade templates');
    }

    /**
     * Test RTL support for Arabic locale.
     */
    public function test_rtl_support_for_arabic(): void
    {
        $this->app->setLocale('ar');

        $rtl = app('canvastack.rtl');

        $this->assertTrue($rtl->isRtl('ar'), 'Arabic should be RTL');
        $this->assertEquals('rtl', $rtl->getDirection('ar'));
        $this->assertEquals('rtl', $rtl->getRtlClass('ar'));
    }

    /**
     * Test LTR support for English and Indonesian.
     */
    public function test_ltr_support_for_non_rtl_locales(): void
    {
        $rtl = app('canvastack.rtl');

        $this->assertFalse($rtl->isRtl('en'), 'English should be LTR');
        $this->assertFalse($rtl->isRtl('id'), 'Indonesian should be LTR');

        $this->assertEquals('ltr', $rtl->getDirection('en'));
        $this->assertEquals('ltr', $rtl->getDirection('id'));
    }

    /**
     * Test locale switching without page reload.
     */
    public function test_locale_switching_works(): void
    {
        $localeManager = app('canvastack.locale');

        // Switch to Indonesian
        $localeManager->setLocale('id');
        $this->assertEquals('id', $this->app->getLocale());
        $this->assertEquals('Cari...', __('components.table.search'));

        // Switch to Arabic
        $localeManager->setLocale('ar');
        $this->assertEquals('ar', $this->app->getLocale());
        $this->assertEquals('بحث...', __('components.table.search'));

        // Switch back to English
        $localeManager->setLocale('en');
        $this->assertEquals('en', $this->app->getLocale());
        $this->assertEquals('Search...', __('components.table.search'));
    }

    /**
     * Test that all locales are available.
     */
    public function test_all_required_locales_are_available(): void
    {
        $localeManager = app('canvastack.locale');

        $availableLocales = $localeManager->getAvailableLocales();

        $this->assertArrayHasKey('en', $availableLocales, 'English locale should be available');
        $this->assertArrayHasKey('id', $availableLocales, 'Indonesian locale should be available');
        $this->assertArrayHasKey('ar', $availableLocales, 'Arabic locale should be available');

        // Check locale structure
        foreach (['en', 'id', 'ar'] as $locale) {
            $this->assertArrayHasKey('name', $availableLocales[$locale]);
            $this->assertArrayHasKey('native', $availableLocales[$locale]);
            $this->assertArrayHasKey('flag', $availableLocales[$locale]);
        }
    }

    /**
     * Test date localization with Carbon.
     */
    public function test_date_localization_works(): void
    {
        $date = now();

        // English
        $this->app->setLocale('en');
        \Carbon\Carbon::setLocale('en');
        $englishDate = $date->translatedFormat('l, d F Y');
        $this->assertIsString($englishDate);

        // Indonesian
        $this->app->setLocale('id');
        \Carbon\Carbon::setLocale('id');
        $indonesianDate = $date->translatedFormat('l, d F Y');
        $this->assertIsString($indonesianDate);
        $this->assertNotEquals($englishDate, $indonesianDate);

        // Arabic
        $this->app->setLocale('ar');
        \Carbon\Carbon::setLocale('ar');
        $arabicDate = $date->translatedFormat('l, d F Y');
        $this->assertIsString($arabicDate);
        $this->assertNotEquals($englishDate, $arabicDate);
    }

    /**
     * Test number formatting per locale.
     */
    public function test_number_formatting_per_locale(): void
    {
        $number = 1234567.89;

        // English (US)
        $formatter = new \NumberFormatter('en_US', \NumberFormatter::DECIMAL);
        $englishNumber = $formatter->format($number);
        $this->assertStringContainsString('1,234,567', $englishNumber);

        // Indonesian
        $formatter = new \NumberFormatter('id_ID', \NumberFormatter::DECIMAL);
        $indonesianNumber = $formatter->format($number);
        $this->assertStringContainsString('1.234.567', $indonesianNumber);

        // Arabic
        $formatter = new \NumberFormatter('ar_SA', \NumberFormatter::DECIMAL);
        $arabicNumber = $formatter->format($number);
        $this->assertIsString($arabicNumber);
    }
}

