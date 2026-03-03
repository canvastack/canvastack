<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Tab;

use Canvastack\Canvastack\Components\Table\Tab\TableInstance;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for TableInstance class
 * 
 * @package Canvastack\Canvastack\Tests\Unit\Components\Table\Tab
 */
class TableInstanceTest extends TestCase
{
    /**
     * Test that table instance can be created
     * 
     * @return void
     */
    public function test_table_instance_can_be_created(): void
    {
        $tableName = 'users';
        $fields = ['id', 'name', 'email'];
        $config = ['sortable' => ['name', 'email']];
        
        $instance = new TableInstance($tableName, $fields, $config);
        
        $this->assertInstanceOf(TableInstance::class, $instance);
        $this->assertEquals($tableName, $instance->getTableName());
        $this->assertEquals($fields, $instance->getFields());
        $this->assertEquals($config, $instance->getConfig());
    }
    
    /**
     * Test that unique ID is generated
     * 
     * @return void
     */
    public function test_unique_id_is_generated(): void
    {
        $instance1 = new TableInstance('users', ['id', 'name'], []);
        $instance2 = new TableInstance('users', ['id', 'name'], []);
        
        $this->assertNotEmpty($instance1->getUniqueId());
        $this->assertNotEmpty($instance2->getUniqueId());
        $this->assertNotEquals($instance1->getUniqueId(), $instance2->getUniqueId());
    }
    
    /**
     * Test that unique ID contains table prefix
     * 
     * @return void
     */
    public function test_unique_id_contains_table_prefix(): void
    {
        $instance = new TableInstance('users', ['id', 'name'], []);
        
        $this->assertStringStartsWith('table_', $instance->getUniqueId());
    }
    
    /**
     * Test that render method returns HTML
     * 
     * @return void
     */
    public function test_render_returns_html(): void
    {
        $instance = new TableInstance('users', ['id', 'name'], []);
        
        $html = $instance->render();
        
        $this->assertIsString($html);
        $this->assertStringContainsString('<div', $html);
        $this->assertStringContainsString('table-instance', $html);
        $this->assertStringContainsString($instance->getUniqueId(), $html);
    }
    
    /**
     * Test that render includes table name in data attribute
     * 
     * @return void
     */
    public function test_render_includes_table_name(): void
    {
        $tableName = 'users';
        $instance = new TableInstance($tableName, ['id', 'name'], []);
        
        $html = $instance->render();
        
        $this->assertStringContainsString('data-table="' . $tableName . '"', $html);
    }
    
    /**
     * Test that render includes configuration data attributes
     * 
     * @return void
     */
    public function test_render_includes_config_data_attributes(): void
    {
        $config = [
            'connection' => 'mysql',
            'displayLimit' => 25,
            'sortable' => ['name', 'email'],
        ];
        
        $instance = new TableInstance('users', ['id', 'name'], $config);
        
        $html = $instance->render();
        
        $this->assertStringContainsString('data-connection="mysql"', $html);
        $this->assertStringContainsString('data-display-limit="25"', $html);
        $this->assertStringContainsString('data-sortable=', $html);
    }
    
    /**
     * Test that render includes loading placeholder
     * 
     * @return void
     */
    public function test_render_includes_loading_placeholder(): void
    {
        $instance = new TableInstance('users', ['id', 'name'], []);
        
        $html = $instance->render();
        
        $this->assertStringContainsString('table-loading', $html);
        $this->assertStringContainsString('loading-spinner', $html);
    }
    
    /**
     * Test that toArray returns correct structure
     * 
     * @return void
     */
    public function test_to_array_returns_correct_structure(): void
    {
        $tableName = 'users';
        $fields = ['id', 'name', 'email'];
        $config = ['sortable' => ['name']];
        
        $instance = new TableInstance($tableName, $fields, $config);
        
        $array = $instance->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('tableName', $array);
        $this->assertArrayHasKey('fields', $array);
        $this->assertArrayHasKey('config', $array);
        $this->assertEquals($tableName, $array['tableName']);
        $this->assertEquals($fields, $array['fields']);
        $this->assertEquals($config, $array['config']);
    }
    
    /**
     * Test that configuration can be retrieved by key
     * 
     * @return void
     */
    public function test_get_config_value_returns_correct_value(): void
    {
        $config = [
            'sortable' => ['name', 'email'],
            'displayLimit' => 25,
        ];
        
        $instance = new TableInstance('users', ['id', 'name'], $config);
        
        $this->assertEquals(['name', 'email'], $instance->getConfigValue('sortable'));
        $this->assertEquals(25, $instance->getConfigValue('displayLimit'));
        $this->assertNull($instance->getConfigValue('nonexistent'));
        $this->assertEquals('default', $instance->getConfigValue('nonexistent', 'default'));
    }
    
    /**
     * Test that configuration can be set
     * 
     * @return void
     */
    public function test_set_config_value_updates_configuration(): void
    {
        $instance = new TableInstance('users', ['id', 'name'], []);
        
        $instance->setConfigValue('sortable', ['name']);
        
        $this->assertEquals(['name'], $instance->getConfigValue('sortable'));
    }
    
    /**
     * Test that configuration can be merged
     * 
     * @return void
     */
    public function test_merge_config_merges_configuration(): void
    {
        $initialConfig = ['sortable' => ['name']];
        $instance = new TableInstance('users', ['id', 'name'], $initialConfig);
        
        $additionalConfig = ['searchable' => ['email'], 'displayLimit' => 50];
        $instance->mergeConfig($additionalConfig);
        
        $config = $instance->getConfig();
        
        $this->assertEquals(['name'], $config['sortable']);
        $this->assertEquals(['email'], $config['searchable']);
        $this->assertEquals(50, $config['displayLimit']);
    }
    
    /**
     * Test that configuration is isolated
     * 
     * @return void
     */
    public function test_configuration_is_isolated(): void
    {
        $config = ['sortable' => ['name']];
        $instance = new TableInstance('users', ['id', 'name'], $config);
        
        $this->assertTrue($instance->isConfigIsolated());
    }
    
    /**
     * Test that configuration can be cloned
     * 
     * @return void
     */
    public function test_clone_config_returns_deep_copy(): void
    {
        $config = ['sortable' => ['name'], 'nested' => ['key' => 'value']];
        $instance = new TableInstance('users', ['id', 'name'], $config);
        
        $clonedConfig = $instance->cloneConfig();
        
        $this->assertEquals($config, $clonedConfig);
        
        // Modify cloned config
        $clonedConfig['sortable'][] = 'email';
        
        // Original should not be affected
        $this->assertEquals(['name'], $instance->getConfigValue('sortable'));
    }
    
    /**
     * Test that validate config passes with valid configuration
     * 
     * @return void
     */
    public function test_validate_config_passes_with_valid_configuration(): void
    {
        $instance = new TableInstance('users', ['id', 'name'], ['sortable' => ['name']]);
        
        $this->assertTrue($instance->validateConfig());
    }
    
    /**
     * Test that validate config throws exception with empty table name
     * 
     * @return void
     */
    public function test_validate_config_throws_exception_with_empty_table_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name cannot be empty');
        
        $instance = new TableInstance('', ['id', 'name'], []);
        $instance->validateConfig();
    }
    
    /**
     * Test that validate config throws exception with empty fields
     * 
     * @return void
     */
    public function test_validate_config_throws_exception_with_empty_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Table fields cannot be empty');
        
        $instance = new TableInstance('users', [], []);
        $instance->validateConfig();
    }
    
    /**
     * Test that metadata returns correct information
     * 
     * @return void
     */
    public function test_get_metadata_returns_correct_information(): void
    {
        $tableName = 'users';
        $fields = ['id', 'name', 'email'];
        $config = ['sortable' => ['name'], 'displayLimit' => 25];
        
        $instance = new TableInstance($tableName, $fields, $config);
        
        $metadata = $instance->getMetadata();
        
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('id', $metadata);
        $this->assertArrayHasKey('tableName', $metadata);
        $this->assertArrayHasKey('fieldCount', $metadata);
        $this->assertArrayHasKey('hasConfig', $metadata);
        $this->assertArrayHasKey('configKeys', $metadata);
        
        $this->assertEquals($tableName, $metadata['tableName']);
        $this->assertEquals(3, $metadata['fieldCount']);
        $this->assertTrue($metadata['hasConfig']);
        $this->assertEquals(['sortable', 'displayLimit'], $metadata['configKeys']);
    }
    
    /**
     * Test that multiple instances have isolated configurations
     * 
     * @return void
     */
    public function test_multiple_instances_have_isolated_configurations(): void
    {
        $config1 = ['sortable' => ['name']];
        $config2 = ['sortable' => ['email']];
        
        $instance1 = new TableInstance('users', ['id', 'name'], $config1);
        $instance2 = new TableInstance('posts', ['id', 'title'], $config2);
        
        // Modify instance1 config
        $instance1->setConfigValue('displayLimit', 25);
        
        // instance2 should not be affected
        $this->assertNull($instance2->getConfigValue('displayLimit'));
        $this->assertEquals(['email'], $instance2->getConfigValue('sortable'));
    }
    
    /**
     * Test that render escapes HTML in table name
     * 
     * @return void
     */
    public function test_render_escapes_html_in_table_name(): void
    {
        $tableName = '<script>alert("xss")</script>';
        $instance = new TableInstance($tableName, ['id', 'name'], []);
        
        $html = $instance->render();
        
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
    
    /**
     * Test that fixed columns configuration is included in render
     * 
     * @return void
     */
    public function test_render_includes_fixed_columns_configuration(): void
    {
        $config = [
            'fixedColumns' => ['left' => 2, 'right' => 1],
        ];
        
        $instance = new TableInstance('users', ['id', 'name'], $config);
        
        $html = $instance->render();
        
        $this->assertStringContainsString('data-fixed-columns=', $html);
    }
}
