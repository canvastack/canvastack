<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for setBidirectionalCascade() method.
 *
 * Task 4.1: Add setBidirectionalCascade() Method
 */
class SetBidirectionalCascadeTest extends TestCase
{
    /**
     * Test that setBidirectionalCascade() method exists.
     *
     * @return void
     */
    public function test_set_bidirectional_cascade_method_exists(): void
    {
        $table = app(TableBuilder::class);
        
        $this->assertTrue(
            method_exists($table, 'setBidirectionalCascade'),
            'TableBuilder should have setBidirectionalCascade() method'
        );
    }

    /**
     * Test that setBidirectionalCascade() enables globally.
     *
     * @return void
     */
    public function test_set_bidirectional_cascade_enables_globally(): void
    {
        $table = app(TableBuilder::class);
        $table->setBidirectionalCascade(true);
        
        $config = $table->getConfig();
        
        $this->assertArrayHasKey('bidirectional_cascade', $config);
        $this->assertTrue($config['bidirectional_cascade']);
    }

    /**
     * Test that setBidirectionalCascade() can be disabled.
     *
     * @return void
     */
    public function test_set_bidirectional_cascade_can_be_disabled(): void
    {
        $table = app(TableBuilder::class);
        $table->setBidirectionalCascade(false);
        
        $config = $table->getConfig();
        
        $this->assertArrayHasKey('bidirectional_cascade', $config);
        $this->assertFalse($config['bidirectional_cascade']);
    }

    /**
     * Test that setBidirectionalCascade() returns self for chaining.
     *
     * @return void
     */
    public function test_set_bidirectional_cascade_returns_self(): void
    {
        $table = app(TableBuilder::class);
        $result = $table->setBidirectionalCascade(true);
        
        $this->assertSame($table, $result, 'setBidirectionalCascade() should return self for method chaining');
    }

    /**
     * Test that setBidirectionalCascade() defaults to true when called without parameter.
     *
     * @return void
     */
    public function test_set_bidirectional_cascade_defaults_to_true(): void
    {
        $table = app(TableBuilder::class);
        $table->setBidirectionalCascade();
        
        $config = $table->getConfig();
        
        $this->assertTrue($config['bidirectional_cascade']);
    }

    /**
     * Test that setBidirectionalCascade() is backward compatible.
     *
     * @return void
     */
    public function test_set_bidirectional_cascade_is_backward_compatible(): void
    {
        $table = app(TableBuilder::class);
        
        // Should not throw any errors
        $table->setBidirectionalCascade(true);
        $table->setBidirectionalCascade(false);
        $table->setBidirectionalCascade();
        
        $this->assertTrue(true, 'setBidirectionalCascade() should be backward compatible');
    }
}
