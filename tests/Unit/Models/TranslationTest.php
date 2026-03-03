<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Models;

use Canvastack\Canvastack\Exceptions\DuplicateTranslationException;
use Canvastack\Canvastack\Models\Translation;
use Canvastack\Canvastack\Tests\Fixtures\Models\TranslatableProduct;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    protected TranslatableProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = TranslatableProduct::create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 100.00,
        ]);
    }

    /** @test */
    public function it_can_create_translation()
    {
        $translation = Translation::create([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test',
        ]);

        $this->assertInstanceOf(Translation::class, $translation);
        $this->assertEquals('name', $translation->attribute);
        $this->assertEquals('id', $translation->locale);
        $this->assertEquals('Produk Test', $translation->value);
    }

    /** @test */
    public function it_has_translatable_relationship()
    {
        $translation = Translation::create([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test',
        ]);

        $translatable = $translation->translatable;

        $this->assertInstanceOf(TranslatableProduct::class, $translatable);
        $this->assertEquals($this->product->id, $translatable->id);
    }

    /** @test */
    public function it_can_scope_by_locale()
    {
        Translation::create([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test',
        ]);

        Translation::create([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'es',
            'value' => 'Producto de Prueba',
        ]);

        $idTranslations = Translation::forLocale('id')->get();
        $esTranslations = Translation::forLocale('es')->get();

        $this->assertCount(1, $idTranslations);
        $this->assertCount(1, $esTranslations);
        $this->assertEquals('Produk Test', $idTranslations->first()->value);
        $this->assertEquals('Producto de Prueba', $esTranslations->first()->value);
    }

    /** @test */
    public function it_can_scope_by_attribute()
    {
        Translation::create([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test',
        ]);

        Translation::create([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'description',
            'locale' => 'id',
            'value' => 'Deskripsi Test',
        ]);

        $nameTranslations = Translation::forAttribute('name')->get();
        $descriptionTranslations = Translation::forAttribute('description')->get();

        $this->assertCount(1, $nameTranslations);
        $this->assertCount(1, $descriptionTranslations);
        $this->assertEquals('Produk Test', $nameTranslations->first()->value);
        $this->assertEquals('Deskripsi Test', $descriptionTranslations->first()->value);
    }

    /** @test */
    public function it_can_scope_by_model()
    {
        $product2 = TranslatableProduct::create([
            'name' => 'Product 2',
            'description' => 'Description 2',
            'price' => 200.00,
        ]);

        Translation::create([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test',
        ]);

        Translation::create([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $product2->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk 2',
        ]);

        $product1Translations = Translation::forModel(TranslatableProduct::class, $this->product->id)->get();
        $product2Translations = Translation::forModel(TranslatableProduct::class, $product2->id)->get();

        $this->assertCount(1, $product1Translations);
        $this->assertCount(1, $product2Translations);
        $this->assertEquals('Produk Test', $product1Translations->first()->value);
        $this->assertEquals('Produk 2', $product2Translations->first()->value);
    }

    /** @test */
    public function it_enforces_unique_constraint()
    {
        Translation::create([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Translation::create([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test 2',
        ]);
    }

    /** @test */
    public function it_throws_duplicate_translation_exception_when_using_create_translation()
    {
        Translation::createTranslation([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test',
        ]);

        $this->expectException(DuplicateTranslationException::class);
        $this->expectExceptionMessage("A translation for TranslatableProduct#{$this->product->id} attribute 'name' in locale 'id' already exists");

        Translation::createTranslation([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test 2',
        ]);
    }

    /** @test */
    public function it_allows_same_attribute_for_different_locales()
    {
        Translation::createTranslation([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test',
        ]);

        $translation = Translation::createTranslation([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'es',
            'value' => 'Producto de Prueba',
        ]);

        $this->assertInstanceOf(Translation::class, $translation);
        $this->assertEquals('es', $translation->locale);
        $this->assertEquals('Producto de Prueba', $translation->value);
    }

    /** @test */
    public function it_allows_same_locale_for_different_attributes()
    {
        Translation::createTranslation([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test',
        ]);

        $translation = Translation::createTranslation([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'description',
            'locale' => 'id',
            'value' => 'Deskripsi Test',
        ]);

        $this->assertInstanceOf(Translation::class, $translation);
        $this->assertEquals('description', $translation->attribute);
        $this->assertEquals('Deskripsi Test', $translation->value);
    }

    /** @test */
    public function it_can_create_or_update_translation()
    {
        // Create new translation
        $translation = Translation::createOrUpdateTranslation([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
        ], [
            'value' => 'Produk Test',
        ]);

        $this->assertEquals('Produk Test', $translation->value);

        // Update existing translation
        $updated = Translation::createOrUpdateTranslation([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
        ], [
            'value' => 'Produk Test Updated',
        ]);

        $this->assertEquals($translation->id, $updated->id);
        $this->assertEquals('Produk Test Updated', $updated->value);

        // Verify only one translation exists
        $count = Translation::forModel(TranslatableProduct::class, $this->product->id)
            ->forAttribute('name')
            ->forLocale('id')
            ->count();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $translation = Translation::create([
            'translatable_type' => TranslatableProduct::class,
            'translatable_id' => $this->product->id,
            'attribute' => 'name',
            'locale' => 'id',
            'value' => 'Produk Test',
        ]);

        $this->assertNotNull($translation->created_at);
        $this->assertNotNull($translation->updated_at);
    }
}
