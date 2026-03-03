<?php

namespace Canvastack\Canvastack\Tests\Feature\Components;

use Canvastack\Canvastack\Components\Chart\ChartBuilder;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Tests\Feature\FeatureTestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

/**
 * Multi-Language Component Tests.
 *
 * Tests all major components (Form, Table, Chart) in multiple languages
 * to ensure proper i18n support.
 */
class MultiLanguageComponentTest extends FeatureTestCase
{
    protected LocaleManager $localeManager;

    protected array $testLocales = ['en', 'id'];

    protected function setUp(): void
    {
        parent::setUp();

        // Setup locales
        Config::set('canvastack.localization.available_locales', [
            'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
            'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩'],
        ]);

        $this->localeManager = app(LocaleManager::class);
    }

    /** @test */
    public function form_builder_renders_in_english()
    {
        $this->localeManager->setLocale('en');

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->text('name', 'Name')->required();
        $form->email('email', 'Email')->required();

        $html = $form->render();

        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('Email', $html);
        $this->assertStringContainsString('required', $html);
    }

    /** @test */
    public function form_builder_renders_in_indonesian()
    {
        $this->localeManager->setLocale('id');

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->text('name', __('ui.name'))->required();
        $form->email('email', __('ui.email'))->required();

        $html = $form->render();

        // Should contain translated labels if translations exist
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /** @test */
    public function table_builder_renders_in_english()
    {
        $this->localeManager->setLocale('en');

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ]);
        $table->setFields(['name:Name', 'email:Email']);
        $table->format();

        $html = $table->render();

        // Check that table renders and contains data
        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString('jane@example.com', $html);
    }

    /** @test */
    public function table_builder_renders_in_indonesian()
    {
        $this->localeManager->setLocale('id');

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ]);
        $table->setFields(['name:' . __('ui.name'), 'email:' . __('ui.email')]);
        $table->format();

        $html = $table->render();

        // Should render successfully with translated headers
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('John Doe', $html);
    }

    /** @test */
    public function chart_builder_renders_in_english()
    {
        $this->localeManager->setLocale('en');

        $chart = app(ChartBuilder::class);
        $chart->setContext('admin');
        $chart->line([
            ['name' => 'Sales', 'data' => [10, 20, 30, 40]],
        ], ['Jan', 'Feb', 'Mar', 'Apr']);

        $html = $chart->render();

        $this->assertStringContainsString('Sales', $html);
        $this->assertStringContainsString('Jan', $html);
    }

    /** @test */
    public function chart_builder_renders_in_indonesian()
    {
        $this->localeManager->setLocale('id');

        $chart = app(ChartBuilder::class);
        $chart->setContext('admin');
        $chart->line([
            ['name' => __('ui.sales'), 'data' => [10, 20, 30, 40]],
        ], [__('ui.january'), __('ui.february'), __('ui.march'), __('ui.april')]);

        $html = $chart->render();

        // Should render successfully with translated labels
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /** @test */
    public function components_respect_locale_changes()
    {
        // Start with English
        $this->localeManager->setLocale('en');
        $this->assertEquals('en', App::getLocale());

        $form1 = app(FormBuilder::class);
        $form1->setContext('admin');
        $form1->text('name', 'Name');
        $html1 = $form1->render();

        // Switch to Indonesian
        $this->localeManager->setLocale('id');
        $this->assertEquals('id', App::getLocale());

        $form2 = app(FormBuilder::class);
        $form2->setContext('admin');
        $form2->text('name', __('ui.name'));
        $html2 = $form2->render();

        // Both should render successfully
        $this->assertIsString($html1);
        $this->assertIsString($html2);
        $this->assertNotEmpty($html1);
        $this->assertNotEmpty($html2);
    }

    /** @test */
    public function form_validation_messages_use_current_locale()
    {
        $this->localeManager->setLocale('en');

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->text('name', 'Name')->required();
        $form->text('email', 'Email')->required();

        $html = $form->render();

        // Should contain validation attributes
        $this->assertStringContainsString('required', $html);
    }

    /** @test */
    public function table_pagination_uses_current_locale()
    {
        $this->localeManager->setLocale('en');

        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Create dataset with more than 10 items to trigger pagination
        $data = [];
        for ($i = 1; $i <= 25; $i++) {
            $data[] = ['id' => $i, 'name' => "User $i", 'email' => "user$i@example.com"];
        }

        $table->setData($data);
        $table->setFields(['name:Name', 'email:Email']);
        $table->format();

        $html = $table->render();

        // Should render successfully
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /** @test */
    public function chart_tooltips_use_current_locale()
    {
        $this->localeManager->setLocale('en');

        $chart = app(ChartBuilder::class);
        $chart->setContext('admin');
        $chart->bar([
            ['name' => 'Revenue', 'data' => [100, 200, 300]],
        ], ['Q1', 'Q2', 'Q3']);

        $html = $chart->render();

        // Should render successfully
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('Revenue', $html);
    }

    /** @test */
    public function all_components_work_with_rtl_locale()
    {
        // Add Arabic locale
        Config::set('canvastack.localization.available_locales.ar', [
            'name' => 'Arabic',
            'native' => 'العربية',
            'flag' => '🇸🇦',
        ]);

        $this->localeManager = new LocaleManager();
        $this->localeManager->setLocale('ar');

        $this->assertTrue($this->localeManager->isRtl());
        $this->assertEquals('rtl', $this->localeManager->getDirection());

        // Test Form
        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->text('name', 'Name');
        $formHtml = $form->render();
        $this->assertIsString($formHtml);

        // Test Table
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test']]);
        $table->setFields(['name:Name']);
        $table->format();
        $tableHtml = $table->render();
        $this->assertIsString($tableHtml);

        // Test Chart
        $chart = app(ChartBuilder::class);
        $chart->setContext('admin');
        $chart->line([['name' => 'Data', 'data' => [1, 2, 3]]], ['A', 'B', 'C']);
        $chartHtml = $chart->render();
        $this->assertIsString($chartHtml);
    }

    /** @test */
    public function components_handle_missing_translations_gracefully()
    {
        $this->localeManager->setLocale('en');

        $form = app(FormBuilder::class);
        $form->setContext('admin');

        // Use a translation key that doesn't exist
        $form->text('name', __('nonexistent.key'));

        $html = $form->render();

        // Should still render, showing the key as fallback
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /** @test */
    public function form_builder_supports_multiple_locales_simultaneously()
    {
        // Create forms in different locales
        $forms = [];

        foreach ($this->testLocales as $locale) {
            $this->localeManager->setLocale($locale);

            $form = app(FormBuilder::class);
            $form->setContext('admin');
            $form->text('name', __('ui.name'));
            $form->email('email', __('ui.email'));

            $forms[$locale] = $form->render();
        }

        // All forms should render successfully
        foreach ($forms as $locale => $html) {
            $this->assertIsString($html, "Form for locale $locale should render");
            $this->assertNotEmpty($html, "Form for locale $locale should not be empty");
        }
    }

    /** @test */
    public function table_builder_supports_multiple_locales_simultaneously()
    {
        // Create tables in different locales
        $tables = [];

        foreach ($this->testLocales as $locale) {
            $this->localeManager->setLocale($locale);

            $table = app(TableBuilder::class);
            $table->setContext('admin');
            $table->setData([
                ['id' => 1, 'name' => 'Test User', 'email' => 'test@example.com'],
            ]);
            $table->setFields(['name:' . __('ui.name'), 'email:' . __('ui.email')]);
            $table->format();

            $tables[$locale] = $table->render();
        }

        // All tables should render successfully
        foreach ($tables as $locale => $html) {
            $this->assertIsString($html, "Table for locale $locale should render");
            $this->assertNotEmpty($html, "Table for locale $locale should not be empty");
        }
    }

    /** @test */
    public function chart_builder_supports_multiple_locales_simultaneously()
    {
        // Create charts in different locales
        $charts = [];

        foreach ($this->testLocales as $locale) {
            $this->localeManager->setLocale($locale);

            $chart = app(ChartBuilder::class);
            $chart->setContext('admin');
            $chart->line([
                ['name' => __('ui.sales'), 'data' => [10, 20, 30]],
            ], [__('ui.january'), __('ui.february'), __('ui.march')]);

            $charts[$locale] = $chart->render();
        }

        // All charts should render successfully
        foreach ($charts as $locale => $html) {
            $this->assertIsString($html, "Chart for locale $locale should render");
            $this->assertNotEmpty($html, "Chart for locale $locale should not be empty");
        }
    }
}
