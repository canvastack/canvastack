<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\App;

/**
 * Test parameter substitution in table translations.
 *
 * Validates Requirement 52.13: Parameter substitution support
 */
class ParameterSubstitutionTest extends TestCase
{
    /**
     * Test single parameter substitution with :column.
     */
    public function test_single_parameter_substitution_with_column(): void
    {
        $result = __('components.table.search_in', ['column' => 'Name']);
        $this->assertEquals('Search in Name', $result);

        $result = __('components.table.search_in', ['column' => 'Email']);
        $this->assertEquals('Search in Email', $result);
    }

    /**
     * Test multiple parameter substitution with :from, :to, :total.
     */
    public function test_multiple_parameter_substitution(): void
    {
        $result = __('components.table.showing', [
            'from' => 1,
            'to' => 10,
            'total' => 100,
        ]);
        
        $this->assertEquals('Showing 1 to 10 of 100 entries', $result);
    }

    /**
     * Test parameter substitution with :count.
     */
    public function test_parameter_substitution_with_count(): void
    {
        $result = __('components.table.selected_count', ['count' => 5]);
        $this->assertEquals('5 selected', $result);

        $result = __('components.table.selected_count', ['count' => 0]);
        $this->assertEquals('0 selected', $result);
    }

    /**
     * Test parameter substitution in filter context.
     */
    public function test_parameter_substitution_in_filter_context(): void
    {
        $result = __('components.table.filter_by', ['column' => 'Status']);
        $this->assertEquals('Filter by Status', $result);

        $result = __('components.table.filter_by', ['column' => 'Created Date']);
        $this->assertEquals('Filter by Created Date', $result);
    }

    /**
     * Test parameter substitution in confirmation messages.
     */
    public function test_parameter_substitution_in_confirmation_messages(): void
    {
        $result = __('components.table.bulk_delete_confirm', ['count' => 5]);
        $this->assertEquals('Are you sure you want to delete 5 items?', $result);

        $result = __('components.table.bulk_delete_confirm', ['count' => 1]);
        $this->assertEquals('Are you sure you want to delete 1 items?', $result);
    }

    /**
     * Test parameter substitution with Indonesian locale.
     */
    public function test_parameter_substitution_with_indonesian_locale(): void
    {
        App::setLocale('id');

        $result = __('components.table.search_in', ['column' => 'Nama']);
        $this->assertEquals('Cari di Nama', $result);

        $result = __('components.table.showing', [
            'from' => 1,
            'to' => 10,
            'total' => 100,
        ]);
        $this->assertEquals('Menampilkan 1 sampai 10 dari 100 entri', $result);

        $result = __('components.table.selected_count', ['count' => 5]);
        $this->assertEquals('5 dipilih', $result);
    }

    /**
     * Test parameter substitution with Arabic locale (RTL).
     */
    public function test_parameter_substitution_with_arabic_locale(): void
    {
        App::setLocale('ar');

        $result = __('components.table.search_in', ['column' => 'الاسم']);
        $this->assertEquals('بحث في الاسم', $result);

        $result = __('components.table.showing', [
            'from' => 1,
            'to' => 10,
            'total' => 100,
        ]);
        $this->assertEquals('عرض 1 إلى 10 من 100 إدخال', $result);

        $result = __('components.table.selected_count', ['count' => 5]);
        $this->assertEquals('5 محدد', $result);
    }

    /**
     * Test parameter substitution with numeric values.
     */
    public function test_parameter_substitution_with_numeric_values(): void
    {
        $result = __('components.table.showing', [
            'from' => 1,
            'to' => 10,
            'total' => 100,
        ]);
        
        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('10', $result);
        $this->assertStringContainsString('100', $result);
    }

    /**
     * Test parameter substitution with special characters.
     */
    public function test_parameter_substitution_with_special_characters(): void
    {
        $result = __('components.table.search_in', ['column' => 'User\'s Name']);
        $this->assertStringContainsString('User\'s Name', $result);

        $result = __('components.table.search_in', ['column' => 'User "Admin" Name']);
        $this->assertStringContainsString('User "Admin" Name', $result);
    }

    /**
     * Test parameter substitution in form validation messages.
     */
    public function test_parameter_substitution_in_form_validation(): void
    {
        $result = __('components.form.min_length', ['min' => 8]);
        $this->assertEquals('Minimum length is 8 characters', $result);

        $result = __('components.form.max_length', ['max' => 255]);
        $this->assertEquals('Maximum length is 255 characters', $result);
    }

    /**
     * Test parameter substitution in locale switcher messages.
     */
    public function test_parameter_substitution_in_locale_switcher(): void
    {
        $result = __('components.locale_switcher.switch_success', ['locale' => 'English']);
        $this->assertEquals('Language changed to English', $result);

        $result = __('components.locale_switcher.switch_success', ['locale' => 'Bahasa Indonesia']);
        $this->assertEquals('Language changed to Bahasa Indonesia', $result);
    }
}
