<?php

namespace Tests\Unit\Components\Form\Features\Editor;

use Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EditorConfig class.
 *
 * Tests default configuration, toolbar configurations, and custom configuration merging.
 * Requirements: 4.5, 4.6
 */
class EditorConfigTest extends TestCase
{
    protected EditorConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new EditorConfig();
    }

    /**
     * Test that getDefaults returns a valid configuration array.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_returns_default_configuration(): void
    {
        $defaults = $this->config->getDefaults();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('toolbar', $defaults);
        $this->assertArrayHasKey('language', $defaults);
        $this->assertArrayHasKey('height', $defaults);
        $this->assertEquals('en', $defaults['language']);
        $this->assertEquals(300, $defaults['height']);
    }

    /**
     * Test that default configuration includes security settings.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_includes_security_settings_in_defaults(): void
    {
        $defaults = $this->config->getDefaults();

        $this->assertArrayHasKey('disallowedContent', $defaults);
        $this->assertStringContainsString('script', $defaults['disallowedContent']);
        $this->assertStringContainsString('on*', $defaults['disallowedContent']);
    }

    /**
     * Test that default configuration includes paste settings.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_includes_paste_settings_in_defaults(): void
    {
        $defaults = $this->config->getDefaults();

        $this->assertArrayHasKey('pasteFromWordRemoveFontStyles', $defaults);
        $this->assertArrayHasKey('pasteFromWordRemoveStyles', $defaults);
        $this->assertTrue($defaults['pasteFromWordRemoveFontStyles']);
        $this->assertTrue($defaults['pasteFromWordRemoveStyles']);
    }

    /**
     * Test that default configuration includes resize settings.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_includes_resize_settings_in_defaults(): void
    {
        $defaults = $this->config->getDefaults();

        $this->assertArrayHasKey('resize_enabled', $defaults);
        $this->assertArrayHasKey('resize_dir', $defaults);
        $this->assertArrayHasKey('resize_minWidth', $defaults);
        $this->assertArrayHasKey('resize_minHeight', $defaults);
        $this->assertTrue($defaults['resize_enabled']);
        $this->assertEquals('both', $defaults['resize_dir']);
    }

    /**
     * Test that getDefaultToolbar returns a valid toolbar configuration.
     *
     * @test
     * Requirements: 4.6
     */
    public function it_returns_default_toolbar_configuration(): void
    {
        $toolbar = $this->config->getDefaultToolbar();

        $this->assertIsArray($toolbar);
        $this->assertNotEmpty($toolbar);

        // Check that toolbar contains expected groups
        $toolbarGroups = array_column($toolbar, 'name');
        $this->assertContains('basicstyles', $toolbarGroups);
        $this->assertContains('paragraph', $toolbarGroups);
        $this->assertContains('links', $toolbarGroups);
    }

    /**
     * Test that default toolbar includes essential formatting options.
     *
     * @test
     * Requirements: 4.6
     */
    public function it_includes_essential_formatting_in_default_toolbar(): void
    {
        $toolbar = $this->config->getDefaultToolbar();

        // Find basicstyles group
        $basicStyles = null;
        foreach ($toolbar as $group) {
            if (is_array($group) && isset($group['name']) && $group['name'] === 'basicstyles') {
                $basicStyles = $group['items'];
                break;
            }
        }

        $this->assertNotNull($basicStyles);
        $this->assertContains('Bold', $basicStyles);
        $this->assertContains('Italic', $basicStyles);
        $this->assertContains('Underline', $basicStyles);
    }

    /**
     * Test that getMinimalToolbar returns a simplified configuration.
     *
     * @test
     * Requirements: 4.6
     */
    public function it_returns_minimal_toolbar_configuration(): void
    {
        $toolbar = $this->config->getMinimalToolbar();

        $this->assertIsArray($toolbar);
        $this->assertNotEmpty($toolbar);

        // Minimal toolbar should have fewer groups than default
        $defaultToolbar = $this->config->getDefaultToolbar();
        $this->assertLessThan(count($defaultToolbar), count($toolbar));
    }

    /**
     * Test that minimal toolbar includes only essential options.
     *
     * @test
     * Requirements: 4.6
     */
    public function it_includes_only_essential_options_in_minimal_toolbar(): void
    {
        $toolbar = $this->config->getMinimalToolbar();

        $toolbarGroups = array_column($toolbar, 'name');

        // Should include basic formatting
        $this->assertContains('basicstyles', $toolbarGroups);
        $this->assertContains('paragraph', $toolbarGroups);
        $this->assertContains('links', $toolbarGroups);

        // Should NOT include advanced features
        $this->assertNotContains('styles', $toolbarGroups);
        $this->assertNotContains('colors', $toolbarGroups);
    }

    /**
     * Test that getFullToolbar returns a comprehensive configuration.
     *
     * @test
     * Requirements: 4.6
     */
    public function it_returns_full_toolbar_configuration(): void
    {
        $toolbar = $this->config->getFullToolbar();

        $this->assertIsArray($toolbar);
        $this->assertNotEmpty($toolbar);

        // Full toolbar should have more groups than default
        $defaultToolbar = $this->config->getDefaultToolbar();
        $this->assertGreaterThanOrEqual(count($defaultToolbar), count($toolbar));
    }

    /**
     * Test that full toolbar includes advanced features.
     *
     * @test
     * Requirements: 4.6
     */
    public function it_includes_advanced_features_in_full_toolbar(): void
    {
        $toolbar = $this->config->getFullToolbar();

        $toolbarGroups = array_column($toolbar, 'name');

        // Should include all feature groups
        $this->assertContains('forms', $toolbarGroups);
        $this->assertContains('styles', $toolbarGroups);
        $this->assertContains('colors', $toolbarGroups);
        $this->assertContains('tools', $toolbarGroups);
    }

    /**
     * Test that getToolbar returns correct toolbar by name.
     *
     * @test
     * Requirements: 4.6
     */
    public function it_returns_correct_toolbar_by_name(): void
    {
        $defaultToolbar = $this->config->getToolbar('default');
        $minimalToolbar = $this->config->getToolbar('minimal');
        $fullToolbar = $this->config->getToolbar('full');

        $this->assertEquals($this->config->getDefaultToolbar(), $defaultToolbar);
        $this->assertEquals($this->config->getMinimalToolbar(), $minimalToolbar);
        $this->assertEquals($this->config->getFullToolbar(), $fullToolbar);
    }

    /**
     * Test that getToolbar defaults to default toolbar for unknown names.
     *
     * @test
     * Requirements: 4.6
     */
    public function it_returns_default_toolbar_for_unknown_names(): void
    {
        $toolbar = $this->config->getToolbar('unknown');

        $this->assertEquals($this->config->getDefaultToolbar(), $toolbar);
    }

    /**
     * Test that custom configuration can be passed to constructor.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_accepts_custom_configuration_in_constructor(): void
    {
        $customConfig = [
            'language' => 'id',
            'height' => 500,
        ];

        $config = new EditorConfig($customConfig);
        $defaults = $config->getDefaults();

        $this->assertEquals('id', $defaults['language']);
        $this->assertEquals(500, $defaults['height']);
    }

    /**
     * Test that custom configuration merges with defaults.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_merges_custom_configuration_with_defaults(): void
    {
        $customConfig = [
            'language' => 'id',
            'customOption' => 'customValue',
        ];

        $config = new EditorConfig($customConfig);
        $defaults = $config->getDefaults();

        // Custom values should override defaults
        $this->assertEquals('id', $defaults['language']);
        $this->assertEquals('customValue', $defaults['customOption']);

        // Default values should still be present
        $this->assertArrayHasKey('height', $defaults);
        $this->assertArrayHasKey('toolbar', $defaults);
    }

    /**
     * Test that merge method combines configurations correctly.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_merges_configurations_correctly(): void
    {
        $custom = [
            'language' => 'fr',
            'height' => 400,
            'newOption' => 'value',
        ];

        $merged = $this->config->merge($custom);

        $this->assertEquals('fr', $merged['language']);
        $this->assertEquals(400, $merged['height']);
        $this->assertEquals('value', $merged['newOption']);

        // Default values should still be present
        $this->assertArrayHasKey('toolbar', $merged);
        $this->assertArrayHasKey('resize_enabled', $merged);
    }

    /**
     * Test that getContextConfig returns admin configuration.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_returns_admin_context_configuration(): void
    {
        $config = $this->config->getContextConfig('admin');

        $this->assertIsArray($config);
        $this->assertEquals($this->config->getDefaultToolbar(), $config['toolbar']);
        $this->assertEquals(300, $config['height']);
    }

    /**
     * Test that getContextConfig returns simplified public configuration.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_returns_simplified_public_context_configuration(): void
    {
        $config = $this->config->getContextConfig('public');

        $this->assertIsArray($config);
        $this->assertEquals($this->config->getMinimalToolbar(), $config['toolbar']);
        $this->assertEquals(250, $config['height']);
        $this->assertContains('elementspath', $config['removePlugins']);
    }

    /**
     * Test that withImageUpload adds upload configuration.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_adds_image_upload_configuration(): void
    {
        $uploadUrl = '/admin/upload';
        $csrfToken = 'test-token';

        $config = $this->config->withImageUpload($uploadUrl, $csrfToken);

        $this->assertArrayHasKey('filebrowserUploadUrl', $config);
        $this->assertArrayHasKey('filebrowserUploadMethod', $config);
        $this->assertArrayHasKey('fileTools_requestHeaders', $config);

        $this->assertEquals($uploadUrl, $config['filebrowserUploadUrl']);
        $this->assertEquals('form', $config['filebrowserUploadMethod']);
        $this->assertEquals($csrfToken, $config['fileTools_requestHeaders']['X-CSRF-TOKEN']);
    }

    /**
     * Test that getDarkModeConfig adds dark mode styling.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_adds_dark_mode_configuration(): void
    {
        $config = $this->config->getDarkModeConfig();

        $this->assertArrayHasKey('contentsCss', $config);
        $this->assertArrayHasKey('bodyClass', $config);

        $this->assertContains('/css/ckeditor-dark.css', $config['contentsCss']);
        $this->assertStringContainsString('dark-mode', $config['bodyClass']);
    }

    /**
     * Test that toolbar configurations are properly structured.
     *
     * @test
     * Requirements: 4.6
     */
    public function it_returns_properly_structured_toolbar_configurations(): void
    {
        $toolbars = [
            'default' => $this->config->getDefaultToolbar(),
            'minimal' => $this->config->getMinimalToolbar(),
            'full' => $this->config->getFullToolbar(),
        ];

        foreach ($toolbars as $name => $toolbar) {
            foreach ($toolbar as $item) {
                // Each item should be either a separator or a group
                if (is_array($item)) {
                    $this->assertArrayHasKey('name', $item, "Toolbar group in {$name} should have 'name' key");
                    $this->assertArrayHasKey('items', $item, "Toolbar group in {$name} should have 'items' key");
                    $this->assertIsArray($item['items'], "Toolbar items in {$name} should be an array");
                } else {
                    // Should be a separator
                    $this->assertEquals('/', $item, "Non-array toolbar item in {$name} should be '/'");
                }
            }
        }
    }

    /**
     * Test that configuration preserves all required keys.
     *
     * @test
     * Requirements: 4.5
     */
    public function it_preserves_all_required_configuration_keys(): void
    {
        $requiredKeys = [
            'toolbar',
            'language',
            'height',
            'removePlugins',
            'extraPlugins',
            'contentsCss',
            'allowedContent',
            'disallowedContent',
            'resize_enabled',
        ];

        $config = $this->config->getDefaults();

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $config, "Configuration should include '{$key}' key");
        }
    }
}
