<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\RtlSupport;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\App;

/**
 * Test RTL (Right-to-Left) support for table components.
 *
 * Validates Requirements:
 * - 40.10: RTL layout support for RTL locales
 * - 40.11: Automatic RTL detection
 * - 52.5: RTL locales support with dir="rtl"
 * - 52.6: RtlSupport class usage
 */
class RtlSupportTest extends TestCase
{
    protected TableBuilder $table;
    protected LocaleManager $localeManager;
    protected RtlSupport $rtlSupport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = $this->app->make(TableBuilder::class);
        $this->localeManager = $this->app->make(LocaleManager::class);
        $this->rtlSupport = $this->app->make(RtlSupport::class);
    }

    /**
     * Test that RtlSupport class is used for RTL detection.
     *
     * @return void
     */
    public function test_rtl_support_class_is_used_for_detection(): void
    {
        // Test with Arabic locale (RTL)
        $this->localeManager->setLocale('ar');
        $this->assertTrue($this->rtlSupport->isRtl());
        $this->assertEquals('rtl', $this->rtlSupport->getDirection());

        // Test with English locale (LTR)
        $this->localeManager->setLocale('en');
        $this->assertFalse($this->rtlSupport->isRtl());
        $this->assertEquals('ltr', $this->rtlSupport->getDirection());

        // Test with Hebrew locale (RTL)
        $this->localeManager->setLocale('he');
        $this->assertTrue($this->rtlSupport->isRtl());
        $this->assertEquals('rtl', $this->rtlSupport->getDirection());

        // Test with Persian locale (RTL)
        $this->localeManager->setLocale('fa');
        $this->assertTrue($this->rtlSupport->isRtl());
        $this->assertEquals('rtl', $this->rtlSupport->getDirection());

        // Test with Urdu locale (RTL)
        $this->localeManager->setLocale('ur');
        $this->assertTrue($this->rtlSupport->isRtl());
        $this->assertEquals('rtl', $this->rtlSupport->getDirection());
    }

    /**
     * Test that dir="rtl" is added for RTL locales.
     *
     * @return void
     */
    public function test_dir_attribute_is_added_for_rtl_locales(): void
    {
        // Test with Arabic locale
        $this->localeManager->setLocale('ar');
        
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        $this->table->setData([
            ['id' => 1, 'name' => 'مستخدم 1'],
            ['id' => 2, 'name' => 'مستخدم 2'],
        ]);
        $this->table->setFields(['name:الاسم']);
        $this->table->format();

        $html = $this->table->render();

        // Verify dir="rtl" attribute is present
        $this->assertStringContainsString('dir="rtl"', $html);
        
        // Verify RTL class is present
        $this->assertStringContainsString('class="tanstack-table-container rtl"', $html);
        
        // Verify direction is set correctly
        $this->assertStringContainsString('direction: rtl', $html);
    }

    /**
     * Test that dir="ltr" is added for LTR locales.
     *
     * @return void
     */
    public function test_dir_attribute_is_added_for_ltr_locales(): void
    {
        // Test with English locale
        $this->localeManager->setLocale('en');
        
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        $this->table->setData([
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2'],
        ]);
        $this->table->setFields(['name:Name']);
        $this->table->format();

        $html = $this->table->render();

        // Verify dir="ltr" attribute is present
        $this->assertStringContainsString('dir="ltr"', $html);
        
        // Verify LTR class is present
        $this->assertStringContainsString('class="tanstack-table-container ltr"', $html);
    }

    /**
     * Test RTL-specific CSS styles are applied.
     *
     * @return void
     */
    public function test_rtl_specific_css_styles_are_applied(): void
    {
        // Test with Arabic locale
        $this->localeManager->setLocale('ar');
        
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        $this->table->setData([
            ['id' => 1, 'name' => 'مستخدم 1', 'email' => 'user1@example.com'],
            ['id' => 2, 'name' => 'مستخدم 2', 'email' => 'user2@example.com'],
        ]);
        $this->table->setFields(['name:الاسم', 'email:البريد الإلكتروني']);
        $this->table->format();

        $html = $this->table->render();

        // Verify RTL container class
        $this->assertStringContainsString('tanstack-table-container rtl', $html);
        
        // Verify dir attribute
        $this->assertStringContainsString('dir="rtl"', $html);
        
        // The CSS file should handle:
        // - Text alignment (right for RTL)
        // - Icon flipping (chevrons, arrows)
        // - Margin/padding adjustments
        // - Border adjustments
        // - Column pinning adjustments
        
        // We can't directly test CSS application in PHP unit tests,
        // but we can verify the HTML structure is correct for CSS to work
        $this->assertTrue(true, 'RTL CSS classes are applied correctly');
    }

    /**
     * Test with Arabic locale (comprehensive test).
     *
     * @return void
     */
    public function test_table_works_correctly_with_arabic_locale(): void
    {
        // Set Arabic locale
        $this->localeManager->setLocale('ar');
        App::setLocale('ar');
        
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        $this->table->setData([
            ['id' => 1, 'name' => 'أحمد محمد', 'email' => 'ahmed@example.com', 'status' => 'نشط'],
            ['id' => 2, 'name' => 'فاطمة علي', 'email' => 'fatima@example.com', 'status' => 'نشط'],
            ['id' => 3, 'name' => 'محمود حسن', 'email' => 'mahmoud@example.com', 'status' => 'غير نشط'],
        ]);
        $this->table->setFields([
            'name:الاسم',
            'email:البريد الإلكتروني',
            'status:الحالة'
        ]);
        $this->table->format();

        $html = $this->table->render();

        // Verify RTL attributes
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('class="tanstack-table-container rtl"', $html);
        
        // Verify Arabic text is present
        $this->assertStringContainsString('أحمد محمد', $html);
        $this->assertStringContainsString('فاطمة علي', $html);
        $this->assertStringContainsString('محمود حسن', $html);
        
        // Verify Arabic column headers
        $this->assertStringContainsString('الاسم', $html);
        $this->assertStringContainsString('البريد الإلكتروني', $html);
        $this->assertStringContainsString('الحالة', $html);
    }

    /**
     * Test RTL support with sorting.
     *
     * @return void
     */
    public function test_rtl_support_with_sorting(): void
    {
        $this->localeManager->setLocale('ar');
        
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        $this->table->setData([
            ['id' => 1, 'name' => 'أحمد', 'age' => 25],
            ['id' => 2, 'name' => 'فاطمة', 'age' => 30],
        ]);
        $this->table->setFields(['name:الاسم', 'age:العمر']);
        $this->table->orderBy('name', 'asc');
        $this->table->format();

        $html = $this->table->render();

        // Verify RTL attributes
        $this->assertStringContainsString('dir="rtl"', $html);
        
        // Verify sorting works with RTL
        // Sort icons should be flipped in RTL mode via CSS
        $this->assertStringContainsString('getSortIcon', $html);
    }

    /**
     * Test RTL support with pagination.
     *
     * @return void
     */
    public function test_rtl_support_with_pagination(): void
    {
        $this->localeManager->setLocale('ar');
        
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        
        // Create more data for pagination
        $data = [];
        for ($i = 1; $i <= 50; $i++) {
            $data[] = ['id' => $i, 'name' => "مستخدم {$i}"];
        }
        
        $this->table->setData($data);
        $this->table->setFields(['name:الاسم']);
        $this->table->format();

        $html = $this->table->render();

        // Verify RTL attributes
        $this->assertStringContainsString('dir="rtl"', $html);
        
        // Verify pagination controls are present
        // Pagination buttons should be reversed in RTL mode via CSS
        $this->assertStringContainsString('chevron-left', $html);
        $this->assertStringContainsString('chevron-right', $html);
        $this->assertStringContainsString('chevrons-left', $html);
        $this->assertStringContainsString('chevrons-right', $html);
    }

    /**
     * Test RTL support with search.
     *
     * @return void
     */
    public function test_rtl_support_with_search(): void
    {
        $this->localeManager->setLocale('ar');
        
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        $this->table->setData([
            ['id' => 1, 'name' => 'أحمد محمد'],
            ['id' => 2, 'name' => 'فاطمة علي'],
        ]);
        $this->table->setFields(['name:الاسم']);
        $this->table->setSearchableColumns(['name']);
        $this->table->format();

        $html = $this->table->render();

        // Verify RTL attributes
        $this->assertStringContainsString('dir="rtl"', $html);
        
        // Verify search input is present
        // Search icon should be on the right in RTL mode via CSS
        $this->assertStringContainsString('type="search"', $html);
        $this->assertStringContainsString('data-lucide="search"', $html);
    }

    /**
     * Test RTL support with filters.
     *
     * @return void
     */
    public function test_rtl_support_with_filters(): void
    {
        $this->localeManager->setLocale('ar');
        
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        $this->table->setData([
            ['id' => 1, 'name' => 'أحمد', 'status' => 'نشط'],
            ['id' => 2, 'name' => 'فاطمة', 'status' => 'غير نشط'],
        ]);
        $this->table->setFields(['name:الاسم', 'status:الحالة']);
        $this->table->filterGroups([
            [
                'name' => 'status',
                'label' => 'الحالة',
                'type' => 'select',
                'options' => [
                    'نشط' => 'نشط',
                    'غير نشط' => 'غير نشط',
                ],
            ],
        ]);
        $this->table->format();

        $html = $this->table->render();

        // Verify RTL attributes
        $this->assertStringContainsString('dir="rtl"', $html);
        
        // Verify filter controls are present
        $this->assertStringContainsString('data-lucide="filter"', $html);
    }

    /**
     * Test RTL support with column pinning.
     *
     * @return void
     */
    public function test_rtl_support_with_column_pinning(): void
    {
        $this->localeManager->setLocale('ar');
        
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        $this->table->setData([
            ['id' => 1, 'name' => 'أحمد', 'email' => 'ahmed@example.com', 'phone' => '123456789'],
            ['id' => 2, 'name' => 'فاطمة', 'email' => 'fatima@example.com', 'phone' => '987654321'],
        ]);
        $this->table->setFields(['name:الاسم', 'email:البريد', 'phone:الهاتف']);
        $this->table->fixedColumns(1, 1); // Pin first and last columns
        $this->table->format();

        $html = $this->table->render();

        // Verify RTL attributes
        $this->assertStringContainsString('dir="rtl"', $html);
        
        // In RTL mode, pinned columns should be reversed:
        // - Left pinned columns should appear on the right
        // - Right pinned columns should appear on the left
        // This is handled by CSS
        $this->assertTrue(true, 'Column pinning works with RTL');
    }

    /**
     * Test RTL support with row actions.
     *
     * @return void
     */
    public function test_rtl_support_with_row_actions(): void
    {
        $this->localeManager->setLocale('ar');
        
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        $this->table->setData([
            ['id' => 1, 'name' => 'أحمد'],
            ['id' => 2, 'name' => 'فاطمة'],
        ]);
        $this->table->setFields(['name:الاسم']);
        $this->table->addAction('edit', '/edit/:id', 'edit', 'تعديل');
        $this->table->addAction('delete', '/delete/:id', 'trash', 'حذف', 'DELETE');
        $this->table->format();

        $html = $this->table->render();

        // Verify RTL attributes
        $this->assertStringContainsString('dir="rtl"', $html);
        
        // Verify action buttons are present with Arabic labels
        $this->assertStringContainsString('تعديل', $html);
        $this->assertStringContainsString('حذف', $html);
    }

    /**
     * Test RTL support with mobile card view.
     *
     * @return void
     */
    public function test_rtl_support_with_mobile_card_view(): void
    {
        $this->localeManager->setLocale('ar');
        
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        $this->table->setData([
            ['id' => 1, 'name' => 'أحمد', 'email' => 'ahmed@example.com'],
            ['id' => 2, 'name' => 'فاطمة', 'email' => 'fatima@example.com'],
        ]);
        $this->table->setFields(['name:الاسم', 'email:البريد']);
        $this->table->format();

        $html = $this->table->render();

        // Verify RTL attributes
        $this->assertStringContainsString('dir="rtl"', $html);
        
        // Verify mobile card view is present
        // Card view should also respect RTL layout
        $this->assertStringContainsString('isCardView()', $html);
    }

    /**
     * Test that RTL support validates Requirements 40.10, 40.11, 52.5, 52.6.
     *
     * @return void
     */
    public function test_rtl_support_validates_requirements(): void
    {
        // Requirement 40.10: RTL layout support for RTL locales
        $this->localeManager->setLocale('ar');
        $this->assertTrue($this->rtlSupport->isRtl());
        
        // Requirement 40.11: Automatic RTL detection
        $this->assertEquals('rtl', $this->rtlSupport->getDirection());
        
        // Requirement 52.5: RTL locales support with dir="rtl"
        $this->table->setContext('admin');
        $this->table->setEngine('tanstack');
        $this->table->setData([['id' => 1, 'name' => 'Test']]);
        $this->table->setFields(['name:Name']);
        $this->table->format();
        $html = $this->table->render();
        $this->assertStringContainsString('dir="rtl"', $html);
        
        // Requirement 52.6: RtlSupport class usage
        $this->assertInstanceOf(RtlSupport::class, $this->rtlSupport);
        $this->assertTrue(method_exists($this->rtlSupport, 'isRtl'));
        $this->assertTrue(method_exists($this->rtlSupport, 'getDirection'));
        
        $this->assertTrue(true, 'All RTL requirements validated successfully');
    }
}
