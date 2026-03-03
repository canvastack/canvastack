<?php

namespace Tests\Unit\Components\Form\Features\Editor;

use Canvastack\Canvastack\Components\Form\Features\Editor\CKEditorIntegration;
use Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CKEditorIntegration class.
 *
 * Tests editor instance registration, management, and configuration.
 * Requirements: 4.1, 4.2, 4.22
 */
class CKEditorIntegrationTest extends TestCase
{
    protected CKEditorIntegration $integration;

    protected EditorConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new EditorConfig();
        $this->integration = new CKEditorIntegration($this->config);
    }

    /**
     * Test that integration can be instantiated.
     *
     * @test
     * Requirements: 4.1
     */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CKEditorIntegration::class, $this->integration);
    }

    /**
     * Test that register method stores editor instance.
     *
     * @test
     * Requirements: 4.1, 4.2
     */
    public function it_registers_editor_instance(): void
    {
        $fieldName = 'description';
        $options = ['height' => 400];

        $this->integration->register($fieldName, $options);

        $this->assertTrue($this->integration->isRegistered($fieldName));
    }

    /**
     * Test that register method merges options with defaults.
     *
     * @test
     * Requirements: 4.2
     */
    public function it_merges_options_with_defaults_on_register(): void
    {
        $fieldName = 'content';
        $options = ['height' => 500, 'language' => 'id'];

        $this->integration->register($fieldName, $options);

        $instance = $this->integration->getInstance($fieldName);

        $this->assertNotNull($instance);
        $this->assertArrayHasKey('config', $instance);
        $this->assertEquals(500, $instance['config']['height']);
        $this->assertEquals('id', $instance['config']['language']);

        // Should still have default values
        $this->assertArrayHasKey('toolbar', $instance['config']);
    }

    /**
     * Test that hasInstances returns false when no instances registered.
     *
     * @test
     * Requirements: 4.22
     */
    public function it_returns_false_when_no_instances_registered(): void
    {
        $this->assertFalse($this->integration->hasInstances());
    }

    /**
     * Test that hasInstances returns true when instances are registered.
     *
     * @test
     * Requirements: 4.22
     */
    public function it_returns_true_when_instances_are_registered(): void
    {
        $this->integration->register('description');

        $this->assertTrue($this->integration->hasInstances());
    }

    /**
     * Test that multiple instances can be registered.
     *
     * @test
     * Requirements: 4.1, 4.22
     */
    public function it_can_register_multiple_instances(): void
    {
        $this->integration->register('description');
        $this->integration->register('content');
        $this->integration->register('notes');

        $this->assertTrue($this->integration->hasInstances());
        $this->assertEquals(3, $this->integration->count());
    }

    /**
     * Test that getInstances returns all registered instances.
     *
     * @test
     * Requirements: 4.1
     */
    public function it_returns_all_registered_instances(): void
    {
        $this->integration->register('description');
        $this->integration->register('content');

        $instances = $this->integration->getInstances();

        $this->assertIsArray($instances);
        $this->assertCount(2, $instances);
        $this->assertArrayHasKey('description', $instances);
        $this->assertArrayHasKey('content', $instances);
    }

    /**
     * Test that getInstance returns specific instance configuration.
     *
     * @test
     * Requirements: 4.1
     */
    public function it_returns_specific_instance_configuration(): void
    {
        $fieldName = 'description';
        $options = ['height' => 400];

        $this->integration->register($fieldName, $options);

        $instance = $this->integration->getInstance($fieldName);

        $this->assertNotNull($instance);
        $this->assertArrayHasKey('fieldName', $instance);
        $this->assertArrayHasKey('config', $instance);
        $this->assertArrayHasKey('registered_at', $instance);
        $this->assertEquals($fieldName, $instance['fieldName']);
    }

    /**
     * Test that getInstance returns null for unregistered field.
     *
     * @test
     * Requirements: 4.1
     */
    public function it_returns_null_for_unregistered_field(): void
    {
        $instance = $this->integration->getInstance('nonexistent');

        $this->assertNull($instance);
    }

    /**
     * Test that isRegistered checks field registration correctly.
     *
     * @test
     * Requirements: 4.1
     */
    public function it_checks_field_registration_correctly(): void
    {
        $this->integration->register('description');

        $this->assertTrue($this->integration->isRegistered('description'));
        $this->assertFalse($this->integration->isRegistered('content'));
    }

    /**
     * Test that clear removes all instances.
     *
     * @test
     * Requirements: 4.1
     */
    public function it_clears_all_instances(): void
    {
        $this->integration->register('description');
        $this->integration->register('content');

        $this->assertTrue($this->integration->hasInstances());

        $this->integration->clear();

        $this->assertFalse($this->integration->hasInstances());
        $this->assertEquals(0, $this->integration->count());
    }

    /**
     * Test that unregister removes specific instance.
     *
     * @test
     * Requirements: 4.1
     */
    public function it_unregisters_specific_instance(): void
    {
        $this->integration->register('description');
        $this->integration->register('content');

        $result = $this->integration->unregister('description');

        $this->assertTrue($result);
        $this->assertFalse($this->integration->isRegistered('description'));
        $this->assertTrue($this->integration->isRegistered('content'));
        $this->assertEquals(1, $this->integration->count());
    }

    /**
     * Test that unregister returns false for unregistered field.
     *
     * @test
     * Requirements: 4.1
     */
    public function it_returns_false_when_unregistering_nonexistent_field(): void
    {
        $result = $this->integration->unregister('nonexistent');

        $this->assertFalse($result);
    }

    /**
     * Test that count returns correct number of instances.
     *
     * @test
     * Requirements: 4.22
     */
    public function it_returns_correct_instance_count(): void
    {
        $this->assertEquals(0, $this->integration->count());

        $this->integration->register('description');
        $this->assertEquals(1, $this->integration->count());

        $this->integration->register('content');
        $this->assertEquals(2, $this->integration->count());

        $this->integration->unregister('description');
        $this->assertEquals(1, $this->integration->count());
    }

    /**
     * Test that instance includes registration timestamp.
     *
     * @test
     * Requirements: 4.1
     */
    public function it_includes_registration_timestamp(): void
    {
        $beforeTime = microtime(true);

        $this->integration->register('description');

        $afterTime = microtime(true);

        $instance = $this->integration->getInstance('description');

        $this->assertArrayHasKey('registered_at', $instance);
        $this->assertGreaterThanOrEqual($beforeTime, $instance['registered_at']);
        $this->assertLessThanOrEqual($afterTime, $instance['registered_at']);
    }

    /**
     * Test that registering same field twice updates configuration.
     *
     * @test
     * Requirements: 4.1, 4.2
     */
    public function it_updates_configuration_when_registering_same_field_twice(): void
    {
        $this->integration->register('description', ['height' => 300]);
        $firstInstance = $this->integration->getInstance('description');

        $this->integration->register('description', ['height' => 500]);
        $secondInstance = $this->integration->getInstance('description');

        $this->assertEquals(300, $firstInstance['config']['height']);
        $this->assertEquals(500, $secondInstance['config']['height']);
    }

    /**
     * Test that empty options array uses defaults.
     *
     * @test
     * Requirements: 4.2
     */
    public function it_uses_defaults_when_options_are_empty(): void
    {
        $this->integration->register('description', []);

        $instance = $this->integration->getInstance('description');
        $defaults = $this->config->getDefaults();

        $this->assertEquals($defaults, $instance['config']);
    }

    /**
     * Test that instance configuration is independent.
     *
     * @test
     * Requirements: 4.1, 4.2
     */
    public function it_maintains_independent_instance_configurations(): void
    {
        $this->integration->register('description', ['height' => 300]);
        $this->integration->register('content', ['height' => 500]);

        $descInstance = $this->integration->getInstance('description');
        $contentInstance = $this->integration->getInstance('content');

        $this->assertEquals(300, $descInstance['config']['height']);
        $this->assertEquals(500, $contentInstance['config']['height']);
    }

    /**
     * Test that field names are case-sensitive.
     *
     * @test
     * Requirements: 4.1
     */
    public function it_treats_field_names_as_case_sensitive(): void
    {
        $this->integration->register('Description');
        $this->integration->register('description');

        $this->assertTrue($this->integration->isRegistered('Description'));
        $this->assertTrue($this->integration->isRegistered('description'));
        $this->assertEquals(2, $this->integration->count());
    }

    /**
     * Test that instance structure is consistent.
     *
     * @test
     * Requirements: 4.1
     */
    public function it_maintains_consistent_instance_structure(): void
    {
        $this->integration->register('description');
        $this->integration->register('content', ['height' => 400]);

        $instances = $this->integration->getInstances();

        foreach ($instances as $fieldName => $instance) {
            $this->assertArrayHasKey('fieldName', $instance);
            $this->assertArrayHasKey('config', $instance);
            $this->assertArrayHasKey('registered_at', $instance);
            $this->assertEquals($fieldName, $instance['fieldName']);
            $this->assertIsArray($instance['config']);
            $this->assertIsFloat($instance['registered_at']);
        }
    }
}
