<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\App;

/**
 * Translation API Unit Tests.
 *
 * Tests for the Translation API helper functions and manager.
 */
class TranslationAPITest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set default locale
        App::setLocale('en');
    }

    /** @test */
    public function it_can_translate_with_trans_choice_with_count()
    {
        $result = trans_choice_with_count('messages.items', 5);

        $this->assertIsString($result);
    }

    /** @test */
    public function it_can_translate_with_fallback()
    {
        $result = trans_fallback('nonexistent.key', 'Default Value');

        $this->assertEquals('Default Value', $result);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_translation_with_trans_if_exists()
    {
        $result = trans_if_exists('nonexistent.key');

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_translate_with_context()
    {
        $result = trans_with_context('button.save', 'admin');

        $this->assertIsString($result);
    }

    /** @test */
    public function it_can_translate_component_keys()
    {
        $result = trans_component('form', 'submit');

        $this->assertIsString($result);
    }

    /** @test */
    public function it_can_translate_ui_keys()
    {
        $result = trans_ui('buttons.save');

        $this->assertIsString($result);
    }

    /** @test */
    public function it_can_translate_validation_keys()
    {
        $result = trans_validation('required', ['attribute' => 'email']);

        $this->assertIsString($result);
    }

    /** @test */
    public function it_can_translate_error_keys()
    {
        $result = trans_error('not_found');

        $this->assertIsString($result);
    }

    /** @test */
    public function translation_manager_exists()
    {
        $manager = app('canvastack.translation');

        $this->assertInstanceOf(\Canvastack\Canvastack\Support\Localization\TranslationManager::class, $manager);
    }

    /** @test */
    public function translation_manager_can_get_current_locale()
    {
        $manager = app('canvastack.translation');

        $locale = $manager->getLocale();

        $this->assertEquals('en', $locale);
    }

    /** @test */
    public function translation_manager_can_set_locale()
    {
        $manager = app('canvastack.translation');

        $manager->setLocale('id');

        $this->assertEquals('id', $manager->getLocale());
        $this->assertEquals('id', App::getLocale());
    }

    /** @test */
    public function translation_manager_can_get_fallback_locale()
    {
        $manager = app('canvastack.translation');

        $fallback = $manager->getFallback();

        $this->assertIsString($fallback);
    }

    /** @test */
    public function translation_manager_can_set_fallback_locale()
    {
        $manager = app('canvastack.translation');

        $manager->setFallback('id');

        $this->assertEquals('id', $manager->getFallback());
    }

    /** @test */
    public function translation_manager_can_get_locale_manager()
    {
        $manager = app('canvastack.translation');

        $localeManager = $manager->getLocaleManager();

        $this->assertInstanceOf(\Canvastack\Canvastack\Support\Localization\LocaleManager::class, $localeManager);
    }

    /** @test */
    public function translation_manager_can_get_cache()
    {
        $manager = app('canvastack.translation');

        $cache = $manager->getCache();

        $this->assertInstanceOf(\Canvastack\Canvastack\Support\Localization\TranslationCache::class, $cache);
    }

    /** @test */
    public function translation_manager_can_get_fallback_manager()
    {
        $manager = app('canvastack.translation');

        $fallbackManager = $manager->getFallbackManager();

        $this->assertInstanceOf(\Canvastack\Canvastack\Support\Localization\TranslationFallback::class, $fallbackManager);
    }

    /** @test */
    public function helper_translate_model_returns_null_for_non_translatable()
    {
        $result = translate_model(null, 'name');

        $this->assertNull($result);
    }

    /** @test */
    public function helper_set_model_translation_returns_false_for_non_translatable()
    {
        $result = set_model_translation(null, 'name', 'value');

        $this->assertFalse($result);
    }

    /** @test */
    public function helper_locale_manager_returns_instance()
    {
        $manager = locale_manager();

        $this->assertInstanceOf(\Canvastack\Canvastack\Support\Localization\LocaleManager::class, $manager);
    }

    /** @test */
    public function helper_current_locale_returns_string()
    {
        $locale = current_locale();

        $this->assertIsString($locale);
        $this->assertEquals('en', $locale);
    }

    /** @test */
    public function helper_available_locales_returns_array()
    {
        $locales = available_locales();

        $this->assertIsArray($locales);
    }

    /** @test */
    public function helper_is_rtl_returns_boolean()
    {
        $isRtl = is_rtl('en');

        $this->assertIsBool($isRtl);
        $this->assertFalse($isRtl);

        $isRtl = is_rtl('ar');
        $this->assertTrue($isRtl);
    }

    /** @test */
    public function helper_text_direction_returns_string()
    {
        $direction = text_direction('en');

        $this->assertEquals('ltr', $direction);

        $direction = text_direction('ar');
        $this->assertEquals('rtl', $direction);
    }

    /** @test */
    public function helper_has_translation_returns_false_for_non_translatable()
    {
        $result = has_translation(null, 'name');

        $this->assertFalse($result);
    }

    /** @test */
    public function helper_get_translations_returns_empty_array_for_non_translatable()
    {
        $result = get_translations(null, 'name');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
