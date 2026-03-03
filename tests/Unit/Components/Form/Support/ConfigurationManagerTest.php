<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Support;

use Canvastack\Canvastack\Components\Form\Support\ConfigurationManager;
use Canvastack\Canvastack\Tests\TestCase;

class ConfigurationManagerTest extends TestCase
{
    protected ConfigurationManager $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new ConfigurationManager();
    }

    /** @test */
    public function it_returns_default_configuration()
    {
        $defaults = $this->config->all();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('tabs', $defaults);
        $this->assertArrayHasKey('ajax_sync', $defaults);
        $this->assertArrayHasKey('file_upload', $defaults);
        $this->assertArrayHasKey('ckeditor', $defaults);
        $this->assertArrayHasKey('character_counter', $defaults);
    }

    /** @test */
    public function it_gets_configuration_value_with_dot_notation()
    {
        $value = $this->config->get('tabs.default_class');

        $this->assertEquals('', $value);
    }

    /** @test */
    public function it_gets_nested_configuration_value()
    {
        $value = $this->config->get('ajax_sync.cache_ttl');

        $this->assertEquals(300, $value);
    }

    /** @test */
    public function it_returns_default_value_for_missing_key()
    {
        $value = $this->config->get('nonexistent.key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    /** @test */
    public function it_returns_null_for_missing_key_without_default()
    {
        $value = $this->config->get('nonexistent.key');

        $this->assertNull($value);
    }

    /** @test */
    public function it_sets_configuration_value_with_dot_notation()
    {
        $this->config->set('tabs.default_class', 'custom-class');

        $value = $this->config->get('tabs.default_class');
        $this->assertEquals('custom-class', $value);
    }

    /** @test */
    public function it_sets_nested_configuration_value()
    {
        $this->config->set('ajax_sync.cache_ttl', 600);

        $value = $this->config->get('ajax_sync.cache_ttl');
        $this->assertEquals(600, $value);
    }

    /** @test */
    public function it_creates_new_configuration_keys()
    {
        $this->config->set('custom.new_key', 'new_value');

        $value = $this->config->get('custom.new_key');
        $this->assertEquals('new_value', $value);
    }

    /** @test */
    public function it_merges_custom_configuration_with_defaults()
    {
        $custom = [
            'tabs' => [
                'default_class' => 'custom-tab',
            ],
            'custom_feature' => [
                'enabled' => true,
            ],
        ];

        $config = new ConfigurationManager($custom);

        // Custom value should override default
        $this->assertEquals('custom-tab', $config->get('tabs.default_class'));

        // Default value should still exist
        $this->assertEquals('fade', $config->get('tabs.animation'));

        // Custom feature should be added
        $this->assertTrue($config->get('custom_feature.enabled'));
    }

    /** @test */
    public function it_returns_all_configuration()
    {
        $this->config->set('custom.key', 'value');

        $all = $this->config->all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('tabs', $all);
        $this->assertArrayHasKey('custom', $all);
        $this->assertEquals('value', $all['custom']['key']);
    }

    /** @test */
    public function it_has_correct_default_tab_configuration()
    {
        $this->assertEquals('', $this->config->get('tabs.default_class'));
        $this->assertEquals('fade', $this->config->get('tabs.animation'));
    }

    /** @test */
    public function it_has_correct_default_ajax_sync_configuration()
    {
        $this->assertEquals(300, $this->config->get('ajax_sync.cache_ttl'));
        $this->assertEquals('/ajax/sync', $this->config->get('ajax_sync.endpoint'));
    }

    /** @test */
    public function it_has_correct_default_file_upload_configuration()
    {
        $this->assertEquals('public', $this->config->get('file_upload.disk'));
        $this->assertEquals(5120, $this->config->get('file_upload.max_size'));
        $this->assertEquals(['jpg', 'jpeg', 'png', 'gif', 'pdf'], $this->config->get('file_upload.allowed_types'));
        $this->assertTrue($this->config->get('file_upload.thumbnail'));
        $this->assertEquals(150, $this->config->get('file_upload.thumbnail_width'));
        $this->assertEquals(150, $this->config->get('file_upload.thumbnail_height'));
        $this->assertEquals(80, $this->config->get('file_upload.thumbnail_quality'));
    }

    /** @test */
    public function it_has_correct_default_ckeditor_configuration()
    {
        $this->assertEquals(5, $this->config->get('ckeditor.version'));
        $this->assertEquals(300, $this->config->get('ckeditor.height'));
        $this->assertEquals('default', $this->config->get('ckeditor.toolbar'));
        $this->assertTrue($this->config->get('ckeditor.image_upload'));
    }

    /** @test */
    public function it_has_correct_default_character_counter_configuration()
    {
        $this->assertEquals(90, $this->config->get('character_counter.warning_threshold'));
        $this->assertEquals(100, $this->config->get('character_counter.danger_threshold'));
    }

    /** @test */
    public function it_handles_array_values()
    {
        $allowedTypes = $this->config->get('file_upload.allowed_types');

        $this->assertIsArray($allowedTypes);
        $this->assertContains('jpg', $allowedTypes);
        $this->assertContains('pdf', $allowedTypes);
    }

    /** @test */
    public function it_handles_boolean_values()
    {
        $thumbnail = $this->config->get('file_upload.thumbnail');

        $this->assertIsBool($thumbnail);
        $this->assertTrue($thumbnail);
    }

    /** @test */
    public function it_handles_integer_values()
    {
        $maxSize = $this->config->get('file_upload.max_size');

        $this->assertIsInt($maxSize);
        $this->assertEquals(5120, $maxSize);
    }

    /** @test */
    public function it_handles_string_values()
    {
        $disk = $this->config->get('file_upload.disk');

        $this->assertIsString($disk);
        $this->assertEquals('public', $disk);
    }

    /** @test */
    public function it_supports_deep_nesting()
    {
        $this->config->set('level1.level2.level3.value', 'deep_value');

        $value = $this->config->get('level1.level2.level3.value');
        $this->assertEquals('deep_value', $value);
    }

    /** @test */
    public function it_overwrites_existing_values()
    {
        $this->config->set('tabs.animation', 'slide');
        $this->assertEquals('slide', $this->config->get('tabs.animation'));

        $this->config->set('tabs.animation', 'fade');
        $this->assertEquals('fade', $this->config->get('tabs.animation'));
    }

    /** @test */
    public function it_handles_null_values()
    {
        $this->config->set('nullable.value', null);

        $value = $this->config->get('nullable.value', 'default');
        $this->assertNull($value);
    }

    /** @test */
    public function it_preserves_data_types()
    {
        $this->config->set('test.string', 'text');
        $this->config->set('test.integer', 123);
        $this->config->set('test.float', 12.34);
        $this->config->set('test.boolean', true);
        $this->config->set('test.array', [1, 2, 3]);

        $this->assertIsString($this->config->get('test.string'));
        $this->assertIsInt($this->config->get('test.integer'));
        $this->assertIsFloat($this->config->get('test.float'));
        $this->assertIsBool($this->config->get('test.boolean'));
        $this->assertIsArray($this->config->get('test.array'));
    }
}
