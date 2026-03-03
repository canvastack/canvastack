<?php

namespace Canvastack\Canvastack\Tests\Unit;

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\TranslationFallback;
use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Canvastack\Canvastack\Tests\TestCase;

class TranslationFallbackTest extends TestCase
{
    protected TranslationFallback $fallback;

    protected TranslationLoader $loader;

    protected LocaleManager $localeManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = $this->app->make(TranslationLoader::class);
        $this->localeManager = $this->app->make(LocaleManager::class);
        $this->fallback = new TranslationFallback($this->loader, $this->localeManager);
    }

    public function test_gets_translation_from_primary_locale(): void
    {
        $value = $this->fallback->get('ui.buttons.save', [], 'en');

        $this->assertIsString($value);
        $this->assertEquals('Save', $value);
    }

    public function test_falls_back_to_default_locale(): void
    {
        $value = $this->fallback->get('ui.buttons.save', [], 'nonexistent');

        $this->assertIsString($value);
        $this->assertEquals('Save', $value);
    }

    public function test_gets_fallback_chain(): void
    {
        $chain = $this->fallback->getFallbackChain('id');

        $this->assertIsArray($chain);
    }

    public function test_sets_fallback_chain(): void
    {
        $this->fallback->setFallbackChain('id', ['en', 'es']);

        $chain = $this->fallback->getFallbackChain('id');

        $this->assertEquals(['en', 'es'], $chain);
    }

    public function test_adds_fallback_locale(): void
    {
        $this->fallback->addFallback('id', 'es');

        $chain = $this->fallback->getFallbackChain('id');

        $this->assertContains('es', $chain);
    }

    public function test_removes_fallback_locale(): void
    {
        $this->fallback->setFallbackChain('id', ['en', 'es']);
        $this->fallback->removeFallback('id', 'es');

        $chain = $this->fallback->getFallbackChain('id');

        $this->assertNotContains('es', $chain);
    }

    public function test_checks_translation_exists_with_fallback(): void
    {
        $exists = $this->fallback->has('ui.buttons.save', 'en');

        $this->assertTrue($exists);
    }

    public function test_gets_translation_source(): void
    {
        $source = $this->fallback->getSource('ui.buttons.save', 'en');

        $this->assertEquals('en', $source);
    }

    public function test_gets_translation_with_specific_fallback(): void
    {
        // When primary locale (id) doesn't have the key, it should fallback to 'en'
        $value = $this->fallback->getWithFallback('ui.buttons.save', 'en', [], 'nonexistent');

        $this->assertIsString($value);
        $this->assertEquals('Save', $value);
    }

    public function test_gets_and_sets_strategy(): void
    {
        $this->fallback->setStrategy('key');

        $this->assertEquals('key', $this->fallback->getStrategy());
    }
}
