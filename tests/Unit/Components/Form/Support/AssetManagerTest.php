<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Support;

use Canvastack\Canvastack\Components\Form\Support\AssetManager;
use Canvastack\Canvastack\Tests\TestCase;

class AssetManagerTest extends TestCase
{
    protected AssetManager $assetManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assetManager = new AssetManager();
    }

    /** @test */
    public function it_tracks_ckeditor_loading()
    {
        $this->assertFalse($this->assetManager->isLoaded('ckeditor'));

        $this->assetManager->loadCKEditor();

        $this->assertTrue($this->assetManager->isLoaded('ckeditor'));
    }

    /** @test */
    public function it_tracks_choices_loading()
    {
        $this->assertFalse($this->assetManager->isLoaded('choices'));

        $this->assetManager->loadChoices();

        $this->assertTrue($this->assetManager->isLoaded('choices'));
    }

    /** @test */
    public function it_tracks_flatpickr_loading()
    {
        $this->assertFalse($this->assetManager->isLoaded('flatpickr'));

        $this->assetManager->loadFlatpickr();

        $this->assertTrue($this->assetManager->isLoaded('flatpickr'));
    }

    /** @test */
    public function it_tracks_tagify_loading()
    {
        $this->assertFalse($this->assetManager->isLoaded('tagify'));

        $this->assetManager->loadTagify();

        $this->assertTrue($this->assetManager->isLoaded('tagify'));
    }

    /** @test */
    public function it_prevents_duplicate_ckeditor_loading()
    {
        $this->assetManager->loadCKEditor();
        $this->assetManager->loadCKEditor();
        $this->assetManager->loadCKEditor();

        $loadedAssets = $this->assetManager->getLoadedAssets();

        // Should only appear once
        $this->assertCount(1, $loadedAssets);
        $this->assertEquals(['ckeditor'], $loadedAssets);
    }

    /** @test */
    public function it_prevents_duplicate_choices_loading()
    {
        $this->assetManager->loadChoices();
        $this->assetManager->loadChoices();

        $loadedAssets = $this->assetManager->getLoadedAssets();

        $this->assertCount(1, $loadedAssets);
        $this->assertEquals(['choices'], $loadedAssets);
    }

    /** @test */
    public function it_prevents_duplicate_flatpickr_loading()
    {
        $this->assetManager->loadFlatpickr();
        $this->assetManager->loadFlatpickr();

        $loadedAssets = $this->assetManager->getLoadedAssets();

        $this->assertCount(1, $loadedAssets);
        $this->assertEquals(['flatpickr'], $loadedAssets);
    }

    /** @test */
    public function it_prevents_duplicate_tagify_loading()
    {
        $this->assetManager->loadTagify();
        $this->assetManager->loadTagify();

        $loadedAssets = $this->assetManager->getLoadedAssets();

        $this->assertCount(1, $loadedAssets);
        $this->assertEquals(['tagify'], $loadedAssets);
    }

    /** @test */
    public function it_tracks_multiple_assets()
    {
        $this->assetManager->loadCKEditor();
        $this->assetManager->loadChoices();
        $this->assetManager->loadFlatpickr();

        $loadedAssets = $this->assetManager->getLoadedAssets();

        $this->assertCount(3, $loadedAssets);
        $this->assertContains('ckeditor', $loadedAssets);
        $this->assertContains('choices', $loadedAssets);
        $this->assertContains('flatpickr', $loadedAssets);
    }

    /** @test */
    public function it_returns_empty_array_when_no_assets_loaded()
    {
        $loadedAssets = $this->assetManager->getLoadedAssets();

        $this->assertIsArray($loadedAssets);
        $this->assertEmpty($loadedAssets);
    }

    /** @test */
    public function it_returns_empty_string_when_no_assets_to_render()
    {
        $script = $this->assetManager->renderScript();

        $this->assertEquals('', $script);
    }

    /** @test */
    public function it_renders_script_for_single_asset()
    {
        $this->assetManager->loadCKEditor();

        $script = $this->assetManager->renderScript();

        $this->assertStringContainsString('<script type="module">', $script);
        $this->assertStringContainsString('window.CanvastackForm.loadCkeditor()', $script);
        $this->assertStringContainsString('</script>', $script);
    }

    /** @test */
    public function it_renders_script_for_multiple_assets()
    {
        $this->assetManager->loadCKEditor();
        $this->assetManager->loadChoices();
        $this->assetManager->loadFlatpickr();

        $script = $this->assetManager->renderScript();

        $this->assertStringContainsString('window.CanvastackForm.loadCkeditor()', $script);
        $this->assertStringContainsString('window.CanvastackForm.loadChoices()', $script);
        $this->assertStringContainsString('window.CanvastackForm.loadFlatpickr()', $script);
    }

    /** @test */
    public function it_uses_async_await_in_rendered_script()
    {
        $this->assetManager->loadCKEditor();

        $script = $this->assetManager->renderScript();

        $this->assertStringContainsString('async function', $script);
        $this->assertStringContainsString('await', $script);
    }

    /** @test */
    public function it_includes_error_handling_in_rendered_script()
    {
        $this->assetManager->loadCKEditor();

        $script = $this->assetManager->renderScript();

        $this->assertStringContainsString('try {', $script);
        $this->assertStringContainsString('} catch (error) {', $script);
        $this->assertStringContainsString('console.error', $script);
    }

    /** @test */
    public function it_capitalizes_asset_names_in_method_calls()
    {
        $this->assetManager->loadCKEditor();
        $this->assetManager->loadChoices();
        $this->assetManager->loadFlatpickr();
        $this->assetManager->loadTagify();

        $script = $this->assetManager->renderScript();

        // Check capitalization
        $this->assertStringContainsString('loadCkeditor', $script);
        $this->assertStringContainsString('loadChoices', $script);
        $this->assertStringContainsString('loadFlatpickr', $script);
        $this->assertStringContainsString('loadTagify', $script);
    }

    /** @test */
    public function it_renders_all_loaded_assets_in_script()
    {
        $this->assetManager->loadCKEditor();
        $this->assetManager->loadChoices();
        $this->assetManager->loadFlatpickr();
        $this->assetManager->loadTagify();

        $script = $this->assetManager->renderScript();

        // Count the number of load calls
        $loadCount = substr_count($script, 'await window.CanvastackForm.load');
        $this->assertEquals(4, $loadCount);
    }

    /** @test */
    public function it_checks_asset_loaded_status_correctly()
    {
        $this->assertFalse($this->assetManager->isLoaded('ckeditor'));
        $this->assertFalse($this->assetManager->isLoaded('choices'));

        $this->assetManager->loadCKEditor();

        $this->assertTrue($this->assetManager->isLoaded('ckeditor'));
        $this->assertFalse($this->assetManager->isLoaded('choices'));
    }

    /** @test */
    public function it_handles_case_sensitive_asset_names()
    {
        $this->assetManager->loadCKEditor();

        $this->assertTrue($this->assetManager->isLoaded('ckeditor'));
        $this->assertFalse($this->assetManager->isLoaded('CKEditor'));
        $this->assertFalse($this->assetManager->isLoaded('CKEDITOR'));
    }

    /** @test */
    public function it_returns_asset_names_as_keys()
    {
        $this->assetManager->loadCKEditor();
        $this->assetManager->loadChoices();

        $loadedAssets = $this->assetManager->getLoadedAssets();

        $this->assertEquals(['ckeditor', 'choices'], $loadedAssets);
        $this->assertNotContains(true, $loadedAssets); // Should not contain boolean values
    }

    /** @test */
    public function it_supports_lazy_loading_strategy()
    {
        // Assets should only be marked as loaded when explicitly called
        $this->assertEmpty($this->assetManager->getLoadedAssets());

        // Load only when needed
        $this->assetManager->loadCKEditor();
        $this->assertEquals(['ckeditor'], $this->assetManager->getLoadedAssets());

        // Load another when needed
        $this->assetManager->loadChoices();
        $this->assertEquals(['ckeditor', 'choices'], $this->assetManager->getLoadedAssets());
    }

    /** @test */
    public function it_generates_valid_javascript_syntax()
    {
        $this->assetManager->loadCKEditor();

        $script = $this->assetManager->renderScript();

        // Check for valid JavaScript structure
        $this->assertStringContainsString('<script type="module">', $script);
        $this->assertStringContainsString('(async function() {', $script);
        $this->assertStringContainsString('})();', $script);
        $this->assertStringContainsString('</script>', $script);
    }

    /** @test */
    public function it_loads_assets_independently()
    {
        // Each asset should be loadable independently
        $manager1 = new AssetManager();
        $manager1->loadCKEditor();

        $manager2 = new AssetManager();
        $manager2->loadChoices();

        $this->assertEquals(['ckeditor'], $manager1->getLoadedAssets());
        $this->assertEquals(['choices'], $manager2->getLoadedAssets());
    }

    /** @test */
    public function it_maintains_load_order()
    {
        $this->assetManager->loadFlatpickr();
        $this->assetManager->loadCKEditor();
        $this->assetManager->loadChoices();

        $loadedAssets = $this->assetManager->getLoadedAssets();

        // Order should be preserved
        $this->assertEquals(['flatpickr', 'ckeditor', 'choices'], $loadedAssets);
    }
}
