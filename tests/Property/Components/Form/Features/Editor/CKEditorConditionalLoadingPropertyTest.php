<?php

namespace Canvastack\Canvastack\Tests\Property\Components\Form\Features\Editor;

use Canvastack\Canvastack\Components\Form\Features\Editor\CKEditorIntegration;
use Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig;
use Canvastack\Canvastack\Components\Form\Support\AssetManager;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property Test: CKEditor Conditional Loading.
 *
 * Validates Requirement 4.3: CKEditor assets should only be loaded when
 * at least one editor instance is registered.
 *
 * Property: For any CKEditor integration instance, assets are loaded if and only if
 * at least one editor instance has been registered.
 *
 * Requirements: 4.3
 */
class CKEditorConditionalLoadingPropertyTest extends TestCase
{
    /**
     * Property 15: CKEditor Conditional Loading.
     *
     * GIVEN a CKEditor integration instance
     * WHEN no editor instances are registered
     * THEN renderAssets() should return empty string
     * AND renderScript() should return empty string
     * AND render() should return empty string
     *
     * Requirements: 4.3
     */
    public function test_property_no_assets_loaded_when_no_instances_registered(): void
    {
        // Arrange
        $config = new EditorConfig();
        $assetManager = new AssetManager();
        $integration = new CKEditorIntegration($config, null, $assetManager);

        // Act
        $assets = $integration->renderAssets();
        $scripts = $integration->renderScript();
        $complete = $integration->render();

        // Assert - No assets should be loaded
        $this->assertEmpty($assets, 'Assets should not be loaded when no instances are registered');
        $this->assertEmpty($scripts, 'Scripts should not be rendered when no instances are registered');
        $this->assertEmpty($complete, 'Complete render should be empty when no instances are registered');

        // Assert - Asset manager should not have marked CKEditor as loaded
        $this->assertFalse(
            $assetManager->isLoaded('ckeditor'),
            'CKEditor should not be marked as loaded when no instances exist'
        );
    }

    /**
     * Property 15: CKEditor Conditional Loading.
     *
     * GIVEN a CKEditor integration instance
     * WHEN at least one editor instance is registered
     * THEN renderAssets() should return CKEditor script tag
     * AND renderScript() should return initialization code
     * AND render() should return both assets and scripts
     * AND assets should only be loaded once even with multiple instances
     *
     * Requirements: 4.3
     */
    public function test_property_assets_loaded_when_instances_registered(): void
    {
        // Arrange
        $config = new EditorConfig();
        $assetManager = new AssetManager();
        $integration = new CKEditorIntegration($config, null, $assetManager);

        // Register one instance
        $integration->register('content', []);

        // Act
        $assets = $integration->renderAssets();
        $scripts = $integration->renderScript();
        $complete = $integration->render();

        // Assert - Assets should be loaded
        $this->assertNotEmpty($assets, 'Assets should be loaded when instances are registered');
        $this->assertStringContainsString('ckeditor', $assets, 'Assets should contain CKEditor script tag');
        $this->assertStringContainsString('<script', $assets, 'Assets should contain script tag');

        // Assert - Scripts should be rendered
        $this->assertNotEmpty($scripts, 'Scripts should be rendered when instances are registered');
        $this->assertStringContainsString('CKEDITOR.replace', $scripts, 'Scripts should contain CKEditor initialization');
        $this->assertStringContainsString('content', $scripts, 'Scripts should reference the field name');

        // Assert - Complete render should contain both
        $this->assertNotEmpty($complete, 'Complete render should not be empty');
        $this->assertStringContainsString('ckeditor', $complete, 'Complete render should contain assets');
        $this->assertStringContainsString('CKEDITOR.replace', $complete, 'Complete render should contain scripts');

        // Assert - Asset manager should have marked CKEditor as loaded
        $this->assertTrue(
            $assetManager->isLoaded('ckeditor'),
            'CKEditor should be marked as loaded after rendering assets'
        );
    }

    /**
     * Property 15: CKEditor Conditional Loading.
     *
     * GIVEN a CKEditor integration instance with multiple registered instances
     * WHEN renderAssets() is called multiple times
     * THEN CKEditor assets should only be included once
     * AND subsequent calls should return empty string
     *
     * Requirements: 4.3
     */
    public function test_property_assets_loaded_only_once_for_multiple_instances(): void
    {
        // Arrange
        $config = new EditorConfig();
        $assetManager = new AssetManager();
        $integration = new CKEditorIntegration($config, null, $assetManager);

        // Register multiple instances
        $integration->register('content', []);
        $integration->register('description', []);
        $integration->register('notes', []);

        // Act - First render
        $firstAssets = $integration->renderAssets();

        // Act - Second render (should be empty because already loaded)
        $secondAssets = $integration->renderAssets();

        // Assert - First render should contain assets
        $this->assertNotEmpty($firstAssets, 'First render should contain assets');
        $this->assertStringContainsString('ckeditor', $firstAssets, 'First render should contain CKEditor');

        // Assert - Second render should be empty (already loaded)
        $this->assertEmpty($secondAssets, 'Second render should be empty (assets already loaded)');

        // Assert - Scripts should contain all three instances
        $scripts = $integration->renderScript();
        $this->assertStringContainsString('content', $scripts, 'Scripts should contain first instance');
        $this->assertStringContainsString('description', $scripts, 'Scripts should contain second instance');
        $this->assertStringContainsString('notes', $scripts, 'Scripts should contain third instance');
    }

    /**
     * Property 15: CKEditor Conditional Loading.
     *
     * GIVEN a CKEditor integration instance
     * WHEN lazy loading is enabled (default)
     * THEN script tag should include defer attribute
     *
     * WHEN lazy loading is disabled
     * THEN script tag should not include defer attribute
     *
     * Requirements: 4.3, 4.4
     */
    public function test_property_lazy_loading_controls_defer_attribute(): void
    {
        // Arrange
        $config = new EditorConfig();
        $assetManager = new AssetManager();
        $integration = new CKEditorIntegration($config, null, $assetManager);
        $integration->register('content', []);

        // Act - With lazy loading (default)
        $lazyAssets = $integration->renderAssets(true);

        // Assert - Should include defer
        $this->assertStringContainsString('defer', $lazyAssets, 'Lazy loading should include defer attribute');

        // Arrange - New instance for non-lazy test
        $assetManager2 = new AssetManager();
        $integration2 = new CKEditorIntegration($config, null, $assetManager2);
        $integration2->register('content', []);

        // Act - Without lazy loading
        $nonLazyAssets = $integration2->renderAssets(false);

        // Assert - Should not include defer
        $this->assertStringNotContainsString('defer', $nonLazyAssets, 'Non-lazy loading should not include defer attribute');
    }

    /**
     * Property 15: CKEditor Conditional Loading.
     *
     * GIVEN multiple CKEditor integration instances sharing the same AssetManager
     * WHEN assets are rendered from the first instance
     * THEN the second instance should not load assets again
     *
     * Requirements: 4.3
     */
    public function test_property_shared_asset_manager_prevents_duplicate_loading(): void
    {
        // Arrange - Shared asset manager
        $config = new EditorConfig();
        $sharedAssetManager = new AssetManager();

        $integration1 = new CKEditorIntegration($config, null, $sharedAssetManager);
        $integration2 = new CKEditorIntegration($config, null, $sharedAssetManager);

        $integration1->register('content1', []);
        $integration2->register('content2', []);

        // Act - Render assets from first instance
        $assets1 = $integration1->renderAssets();

        // Act - Try to render assets from second instance
        $assets2 = $integration2->renderAssets();

        // Assert - First should have assets
        $this->assertNotEmpty($assets1, 'First instance should render assets');
        $this->assertStringContainsString('ckeditor', $assets1, 'First instance should contain CKEditor');

        // Assert - Second should be empty (already loaded by first)
        $this->assertEmpty($assets2, 'Second instance should not render assets (already loaded)');

        // Assert - Both should render their own scripts
        $scripts1 = $integration1->renderScript();
        $scripts2 = $integration2->renderScript();

        $this->assertStringContainsString('content1', $scripts1, 'First instance should render its script');
        $this->assertStringContainsString('content2', $scripts2, 'Second instance should render its script');
    }

    /**
     * Property 15: CKEditor Conditional Loading.
     *
     * GIVEN a CKEditor integration instance
     * WHEN hasInstances() returns false
     * THEN renderAssets(), renderScript(), and render() should all return empty strings
     *
     * WHEN hasInstances() returns true
     * THEN renderAssets(), renderScript(), and render() should all return non-empty strings
     *
     * Requirements: 4.3
     */
    public function test_property_rendering_depends_on_has_instances(): void
    {
        // Arrange
        $config = new EditorConfig();
        $assetManager = new AssetManager();
        $integration = new CKEditorIntegration($config, null, $assetManager);

        // Assert - Initially no instances
        $this->assertFalse($integration->hasInstances(), 'Should have no instances initially');
        $this->assertEmpty($integration->renderAssets(), 'renderAssets should be empty');
        $this->assertEmpty($integration->renderScript(), 'renderScript should be empty');
        $this->assertEmpty($integration->render(), 'render should be empty');

        // Act - Register an instance
        $integration->register('content', []);

        // Assert - Now has instances
        $this->assertTrue($integration->hasInstances(), 'Should have instances after registration');
        $this->assertNotEmpty($integration->renderAssets(), 'renderAssets should not be empty');
        $this->assertNotEmpty($integration->renderScript(), 'renderScript should not be empty');
        $this->assertNotEmpty($integration->render(), 'render should not be empty');
    }

    /**
     * Property 15: CKEditor Conditional Loading.
     *
     * GIVEN a CKEditor integration instance with N registered instances
     * WHEN renderScript() is called
     * THEN the output should contain exactly N CKEDITOR.replace() calls
     *
     * Requirements: 4.3
     */
    public function test_property_script_contains_correct_number_of_initializations(): void
    {
        // Test with different numbers of instances
        $testCases = [1, 2, 3, 5, 10];

        foreach ($testCases as $instanceCount) {
            // Arrange
            $config = new EditorConfig();
            $assetManager = new AssetManager();
            $integration = new CKEditorIntegration($config, null, $assetManager);

            // Register N instances
            for ($i = 0; $i < $instanceCount; $i++) {
                $integration->register("field_{$i}", []);
            }

            // Act
            $scripts = $integration->renderScript();

            // Assert - Count CKEDITOR.replace occurrences
            $replaceCount = substr_count($scripts, 'CKEDITOR.replace');

            $this->assertEquals(
                $instanceCount,
                $replaceCount,
                "Script should contain exactly {$instanceCount} CKEDITOR.replace() calls"
            );
        }
    }

    /**
     * Property 15: CKEditor Conditional Loading.
     *
     * GIVEN a CKEditor integration instance
     * WHEN render() is called with lazy=true
     * THEN the output should contain assets before scripts
     * AND assets should have defer attribute
     *
     * Requirements: 4.3, 4.4
     */
    public function test_property_render_order_and_lazy_loading(): void
    {
        // Arrange
        $config = new EditorConfig();
        $assetManager = new AssetManager();
        $integration = new CKEditorIntegration($config, null, $assetManager);
        $integration->register('content', []);

        // Act
        $output = $integration->render(true);

        // Assert - Should contain both assets and scripts
        $this->assertStringContainsString('<script src=', $output, 'Should contain asset script tag');
        $this->assertStringContainsString('CKEDITOR.replace', $output, 'Should contain initialization script');

        // Assert - Assets should come before initialization
        $assetPos = strpos($output, '<script src=');
        $initPos = strpos($output, 'CKEDITOR.replace');

        $this->assertLessThan(
            $initPos,
            $assetPos,
            'Asset loading should come before initialization script'
        );

        // Assert - Should have defer attribute
        $this->assertStringContainsString('defer', $output, 'Should include defer attribute for lazy loading');
    }
}
