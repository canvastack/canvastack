<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Localization;

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Tests\Fixtures\Models\TranslatableProduct;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\App;

class HelpersTest extends TestCase
{
    protected TranslatableProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Load helper functions
        require_once __DIR__ . '/../../../../src/Support/Localization/helpers.php';

        // Create a test product
        $this->product = TranslatableProduct::create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 100.00,
        ]);

        $this->product->setTranslation('name', 'Produk Test', 'id');
        $this->product->setTranslation('description', 'Deskripsi Test', 'id');
    }

    protected function tearDown(): void
    {
        // Truncate tables for next test
        try {
            \Canvastack\Canvastack\Models\Translation::query()->delete();
            TranslatableProduct::query()->delete();
        } catch (\Exception $e) {
            // Ignore errors during cleanup
        }

        unset($this->product);

        parent::tearDown();
    }

    /** @test */
    public function translate_model_helper_works()
    {
        $translation = translate_model($this->product, 'name', 'id');

        $this->assertEquals('Produk Test', $translation);
    }

    /** @test */
    public function translate_model_returns_null_for_invalid_model()
    {
        $translation = translate_model(null, 'name', 'id');

        $this->assertNull($translation);
    }

    /** @test */
    public function set_model_translation_helper_works()
    {
        $result = set_model_translation($this->product, 'name', 'Producto de Prueba', 'es');

        $this->assertTrue($result);
        $this->assertEquals('Producto de Prueba', $this->product->getTranslation('name', 'es'));
    }

    /** @test */
    public function set_model_translation_returns_false_for_invalid_model()
    {
        $result = set_model_translation(null, 'name', 'Test', 'es');

        $this->assertFalse($result);
    }

    /** @test */
    public function locale_manager_helper_returns_instance()
    {
        $manager = locale_manager();

        $this->assertInstanceOf(LocaleManager::class, $manager);
    }

    /** @test */
    public function current_locale_helper_returns_current_locale()
    {
        App::setLocale('id');

        $locale = current_locale();

        $this->assertEquals('id', $locale);
    }

    /** @test */
    public function available_locales_helper_returns_array()
    {
        $locales = available_locales();

        $this->assertIsArray($locales);
        $this->assertArrayHasKey('en', $locales);
        $this->assertArrayHasKey('id', $locales);
    }

    /** @test */
    public function is_rtl_helper_works()
    {
        $this->assertFalse(is_rtl('en'));
        $this->assertFalse(is_rtl('id'));
    }

    /** @test */
    public function text_direction_helper_works()
    {
        $this->assertEquals('ltr', text_direction('en'));
        $this->assertEquals('ltr', text_direction('id'));
    }

    /** @test */
    public function translate_collection_helper_works()
    {
        $product2 = TranslatableProduct::create([
            'name' => 'Product 2',
            'description' => 'Description 2',
            'price' => 200.00,
        ]);
        $product2->setTranslation('name', 'Produk 2', 'id');

        $collection = collect([$this->product, $product2]);

        $translated = translate_collection($collection, ['name'], 'id');

        $this->assertEquals('Produk Test', $translated->first()->name);
        $this->assertEquals('Produk 2', $translated->last()->name);
    }

    /** @test */
    public function translate_array_helper_works()
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $translated = translate_array($data, 'test.prefix', 'en');

        $this->assertIsArray($translated);
        $this->assertArrayHasKey('key1', $translated);
        $this->assertArrayHasKey('key2', $translated);
    }

    /** @test */
    public function has_translation_helper_works()
    {
        $this->assertTrue(has_translation($this->product, 'name', 'id'));
        $this->assertFalse(has_translation($this->product, 'name', 'es'));
    }

    /** @test */
    public function has_translation_returns_false_for_invalid_model()
    {
        $this->assertFalse(has_translation(null, 'name', 'id'));
    }

    /** @test */
    public function get_translations_helper_works()
    {
        $this->product->setTranslation('name', 'Producto de Prueba', 'es');

        $translations = get_translations($this->product, 'name');

        $this->assertIsArray($translations);
        $this->assertArrayHasKey('id', $translations);
        $this->assertArrayHasKey('es', $translations);
        $this->assertEquals('Produk Test', $translations['id']);
        $this->assertEquals('Producto de Prueba', $translations['es']);
    }

    /** @test */
    public function get_translations_returns_empty_array_for_invalid_model()
    {
        $translations = get_translations(null, 'name');

        $this->assertIsArray($translations);
        $this->assertEmpty($translations);
    }
}
