<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test setFilterRelationships() method.
 *
 * Task 4.2: Add setFilterRelationships() Method
 */
class SetFilterRelationshipsTest extends TestCase
{
    /**
     * Test that setFilterRelationships() method exists.
     *
     * @return void
     */
    public function test_set_filter_relationships_method_exists(): void
    {
        $table = app(TableBuilder::class);
        
        $this->assertTrue(
            method_exists($table, 'setFilterRelationships'),
            'TableBuilder should have setFilterRelationships() method'
        );
    }

    /**
     * Test that setFilterRelationships() defines complex relationships.
     *
     * @return void
     */
    public function test_set_filter_relationships_defines_complex_relationships(): void
    {
        $table = app(TableBuilder::class);
        
        $relationships = [
            'province' => ['city', 'district'],
            'city' => ['province', 'district'],
            'district' => ['province', 'city'],
        ];
        
        $table->setFilterRelationships($relationships);
        
        $config = $table->getConfig();
        
        $this->assertArrayHasKey('filter_relationships', $config);
        $this->assertEquals($relationships, $config['filter_relationships']);
    }

    /**
     * Test that setFilterRelationships() returns self for chaining.
     *
     * @return void
     */
    public function test_set_filter_relationships_returns_self(): void
    {
        $table = app(TableBuilder::class);
        
        $result = $table->setFilterRelationships([
            'category' => ['subcategory', 'product'],
        ]);
        
        $this->assertSame($table, $result);
    }

    /**
     * Test that setFilterRelationships() validates column names are strings.
     *
     * @return void
     */
    public function test_set_filter_relationships_validates_column_names(): void
    {
        $table = app(TableBuilder::class);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter relationship keys must be column names (strings)');
        
        $table->setFilterRelationships([
            123 => ['city'], // Invalid: numeric key
        ]);
    }

    /**
     * Test that setFilterRelationships() validates related columns is array.
     *
     * @return void
     */
    public function test_set_filter_relationships_validates_related_columns_is_array(): void
    {
        $table = app(TableBuilder::class);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Filter relationships for column 'province' must be an array");
        
        $table->setFilterRelationships([
            'province' => 'city', // Invalid: string instead of array
        ]);
    }

    /**
     * Test that setFilterRelationships() validates each related column is string.
     *
     * @return void
     */
    public function test_set_filter_relationships_validates_each_related_column_is_string(): void
    {
        $table = app(TableBuilder::class);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Related column names must be strings");
        
        $table->setFilterRelationships([
            'province' => ['city', 123], // Invalid: numeric value in array
        ]);
    }

    /**
     * Test that setFilterRelationships() accepts empty array.
     *
     * @return void
     */
    public function test_set_filter_relationships_accepts_empty_array(): void
    {
        $table = app(TableBuilder::class);
        
        $table->setFilterRelationships([]);
        
        $config = $table->getConfig();
        
        $this->assertArrayHasKey('filter_relationships', $config);
        $this->assertEquals([], $config['filter_relationships']);
    }

    /**
     * Test that setFilterRelationships() accepts simple parent-child relationships.
     *
     * @return void
     */
    public function test_set_filter_relationships_accepts_simple_relationships(): void
    {
        $table = app(TableBuilder::class);
        
        $relationships = [
            'category' => ['subcategory', 'product'],
            'subcategory' => ['product'],
        ];
        
        $table->setFilterRelationships($relationships);
        
        $config = $table->getConfig();
        
        $this->assertEquals($relationships, $config['filter_relationships']);
    }

    /**
     * Test that setFilterRelationships() can be called multiple times.
     *
     * @return void
     */
    public function test_set_filter_relationships_can_be_called_multiple_times(): void
    {
        $table = app(TableBuilder::class);
        
        // First call
        $table->setFilterRelationships([
            'province' => ['city'],
        ]);
        
        $config1 = $table->getConfig();
        $this->assertEquals(['province' => ['city']], $config1['filter_relationships']);
        
        // Second call (should override)
        $table->setFilterRelationships([
            'category' => ['subcategory'],
        ]);
        
        $config2 = $table->getConfig();
        $this->assertEquals(['category' => ['subcategory']], $config2['filter_relationships']);
    }

    /**
     * Test that setFilterRelationships() works with method chaining.
     *
     * @return void
     */
    public function test_set_filter_relationships_works_with_method_chaining(): void
    {
        $table = app(TableBuilder::class);
        
        $result = $table
            ->setBidirectionalCascade(true)
            ->setFilterRelationships([
                'province' => ['city', 'district'],
                'city' => ['province', 'district'],
            ]);
        
        $this->assertSame($table, $result);
        
        $config = $table->getConfig();
        $this->assertTrue($config['bidirectional_cascade']);
        $this->assertArrayHasKey('filter_relationships', $config);
    }

    /**
     * Test that setFilterRelationships() config is passed to frontend.
     *
     * @return void
     */
    public function test_set_filter_relationships_config_passed_to_frontend(): void
    {
        $table = app(TableBuilder::class);
        
        $relationships = [
            'province' => ['city', 'district'],
        ];
        
        $table->setFilterRelationships($relationships);
        
        $config = $table->getConfig();
        
        // Verify config contains filter_relationships
        $this->assertArrayHasKey('filter_relationships', $config);
        $this->assertEquals($relationships, $config['filter_relationships']);
        
        // Config is automatically passed to renderer via render() method
        // which includes 'config' => $this->config in renderConfig array
    }
}
