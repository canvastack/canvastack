<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\RtlSupport;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * TanStack Table RTL Support Test.
 *
 * Tests RTL (Right-to-Left) support for TanStack Table component.
 *
 * Requirements Validated:
 * - 40.10: RTL layout support for RTL locales
 * - 40.11: Automatic RTL detection
 * - 52.5: RTL support for ar, he, fa, ur locales
 * - 52.6: dir="rtl" attribute for RTL locales
 *
 * @package CanvaStack
 * @subpackage Tests\Feature\Table
 */
class TanStackTableRtlTest extends TestCase
{
    use RefreshDatabase;

    protected TableBuilder $table;
    protected LocaleManager $localeManager;
    protected RtlSupport $rtlSupport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);
        $this->localeManager = app(LocaleManager::class);
        $this->rtlSupport = app('canvastack.rtl');
    }

    /**
     * Test RTL detection for Arabic locale.
     *
     * @return void
     */
    public function test_rtl_detection_for_arabic_locale(): void
    {
        // Set Arabic locale
        $this->localeManager->setLocale('ar');

        // Verify RTL detection
        $this->assertTrue($this->rtlSupport->isRtl());
        $this->assertEquals('rtl', $this->rtlSupport->getDirection());
        $this->assertEquals('rtl', $this->rtlSupport->getDirAttribute());
    }

    /**
     * Test RTL detection for Hebrew locale.
     *
     * @return void
     */
    public function test_rtl_detection_for_hebrew_locale(): void
    {
        // Set Hebrew locale
        $this->localeManager->setLocale('he');

        // Verify RTL detection
        $this->assertTrue($this->rtlSupport->isRtl());
        $this->assertEquals('rtl', $this->rtlSupport->getDirection());
    }

    /**
     * Test RTL detection for Persian locale.
     *
     * @return void
     */
    public function test_rtl_detection_for_persian_locale(): void
    {
        // Set Persian locale
        $this->localeManager->setLocale('fa');

        // Verify RTL detection
        $this->assertTrue($this->rtlSupport->isRtl());
        $this->assertEquals('rtl', $this->rtlSupport->getDirection());
    }

    /**
     * Test RTL detection for Urdu locale.
     *
     * @return void
     */
    public function test_rtl_detection_for_urdu_locale(): void
    {
        // Set Urdu locale
        $this->localeManager->setLocale('ur');

        // Verify RTL detection
        $this->assertTrue($this->rtlSupport->isRtl());
        $this->assertEquals('rtl', $this->rtlSupport->getDirection());
    }

    /**
     * Test LTR detection for English locale.
     *
     * @return void
     */
    public function test_ltr_detection_for_english_locale(): void
    {
        // Set English locale
        $this->localeManager->setLocale('en');

        // Verify LTR detection
        $this->assertFalse($this->rtlSupport->isRtl());
        $this->assertEquals('ltr', $this->rtlSupport->getDirection());
        $this->assertEquals('ltr', $this->rtlSupport->getDirAttribute());
    }

    /**
     * Test table renders with Arabic text for Arabic locale.
     *
     * Validates: Requirements 40.10, 40.11, 52.5, 52.6
     *
     * @return void
     */
    public function test_table_renders_with_arabic_text(): void
    {
        // Set Arabic locale
        $this->localeManager->setLocale('ar');

        // Configure table
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'محمد', 'email' => 'mohamed@example.com'],
            ['id' => 2, 'name' => 'فاطمة', 'email' => 'fatima@example.com'],
        ]);
        $this->table->setFields([
            'name:' . __('components.table.name'),
            'email:' . __('components.table.email'),
        ]);
        $this->table->format();

        // Render table
        $html = $this->table->render();

        // Assert Arabic text is rendered
        $this->assertStringContainsString('محمد', $html, 'Table should render Arabic text');
        $this->assertStringContainsString('فاطمة', $html, 'Table should render Arabic text');
        
        // Assert table is rendered successfully
        $this->assertStringContainsString('<table', $html, 'Table HTML should be present');
    }

    /**
     * Test table renders with English text for English locale.
     *
     * @return void
     */
    public function test_table_renders_with_english_text(): void
    {
        // Set English locale
        $this->localeManager->setLocale('en');

        // Configure table
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
        ]);
        $this->table->setFields([
            'name:' . __('components.table.name'),
            'email:' . __('components.table.email'),
        ]);
        $this->table->format();

        // Render table
        $html = $this->table->render();

        // Assert English text is rendered
        $this->assertStringContainsString('John', $html, 'Table should render English text');
        $this->assertStringContainsString('Jane', $html, 'Table should render English text');
        
        // Assert table is rendered successfully
        $this->assertStringContainsString('<table', $html, 'Table HTML should be present');
    }

    /**
     * Test RTL support provides correct direction for Arabic.
     *
     * @return void
     */
    public function test_rtl_support_provides_correct_direction_for_arabic(): void
    {
        // Set Arabic locale
        $this->localeManager->setLocale('ar');

        // Verify RTL support provides correct direction
        $this->assertTrue($this->rtlSupport->isRtl(), 'Arabic should be detected as RTL');
        $this->assertEquals('rtl', $this->rtlSupport->getDirection(), 'Direction should be rtl for Arabic');
        $this->assertEquals('rtl', $this->rtlSupport->getDirAttribute(), 'Dir attribute should be rtl for Arabic');
    }

    /**
     * Test RTL support provides correct direction for English.
     *
     * @return void
     */
    public function test_rtl_support_provides_correct_direction_for_english(): void
    {
        // Set English locale
        $this->localeManager->setLocale('en');

        // Verify RTL support provides correct direction
        $this->assertFalse($this->rtlSupport->isRtl(), 'English should not be detected as RTL');
        $this->assertEquals('ltr', $this->rtlSupport->getDirection(), 'Direction should be ltr for English');
        $this->assertEquals('ltr', $this->rtlSupport->getDirAttribute(), 'Dir attribute should be ltr for English');
    }

    /**
     * Test table can handle mixed RTL and LTR content.
     *
     * @return void
     */
    public function test_table_handles_mixed_rtl_ltr_content(): void
    {
        // Set Arabic locale
        $this->localeManager->setLocale('ar');

        // Configure table with mixed content
        $this->table->setContext('admin');
        $this->table->setData([
            ['id' => 1, 'name' => 'محمد', 'email' => 'mohamed@example.com'],
            ['id' => 2, 'name' => 'John Smith', 'email' => 'john@example.com'],
        ]);
        $this->table->setFields([
            'name:Name',
            'email:Email',
        ]);
        $this->table->format();

        // Render table
        $html = $this->table->render();

        // Assert both Arabic and English text are rendered
        $this->assertStringContainsString('محمد', $html, 'Table should render Arabic text');
        $this->assertStringContainsString('John Smith', $html, 'Table should render English text');
        $this->assertStringContainsString('mohamed@example.com', $html, 'Table should render email');
    }

    /**
     * Test table renders with pagination for Arabic locale.
     *
     * @return void
     */
    public function test_table_renders_with_pagination_for_arabic(): void
    {
        // Set Arabic locale
        $this->localeManager->setLocale('ar');

        // Configure table with pagination
        $this->table->setContext('admin');
        $this->table->setData(array_map(function ($i) {
            return ['id' => $i, 'name' => "User $i"];
        }, range(1, 50)));
        $this->table->setFields(['name:Name']);
        $this->table->format();

        // Render table
        $html = $this->table->render();

        // Assert table renders successfully with data
        $this->assertStringContainsString('<table', $html, 'Table HTML should be present');
        $this->assertStringContainsString('User 1', $html, 'Table should render first user');
        
        // Assert pagination elements are present (DataTables pagination)
        $this->assertMatchesRegularExpression(
            '/pagination|paginate|page/',
            $html,
            'Table should include pagination elements'
        );
    }
}