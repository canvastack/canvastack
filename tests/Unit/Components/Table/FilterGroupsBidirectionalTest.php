<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;

/**
 * Test for filterGroups() method with bidirectional parameter.
 */
class FilterGroupsBidirectionalTest extends TestCase
{
    /**
     * Test that filterGroups accepts bidirectional parameter.
     *
     * @return void
     */
    public function test_filter_groups_accepts_bidirectional_parameter(): void
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        // Add filter with bidirectional = true
        $table->filterGroups('name', 'selectbox', true, true);
        
        $filters = $table->getFilterManager()->toArray();
        
        $this->assertCount(1, $filters);
        $this->assertEquals('name', $filters[0]['column']);
        $this->assertEquals('selectbox', $filters[0]['type']);
        $this->assertTrue($filters[0]['relate']);
        $this->assertTrue($filters[0]['bidirectional']);
    }

    /**
     * Test that filterGroups is backward compatible (default false).
     *
     * @return void
     */
    public function test_filter_groups_backward_compatible(): void
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        // Add filter without bidirectional parameter
        $table->filterGroups('name', 'selectbox', true);
        
        $filters = $table->getFilterManager()->toArray();
        
        $this->assertCount(1, $filters);
        $this->assertEquals('name', $filters[0]['column']);
        $this->assertEquals('selectbox', $filters[0]['type']);
        $this->assertTrue($filters[0]['relate']);
        $this->assertFalse($filters[0]['bidirectional']); // Default is false
    }

    /**
     * Test that bidirectional flag is passed to frontend.
     *
     * @return void
     */
    public function test_bidirectional_flag_passed_to_frontend(): void
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        // Add filters with different bidirectional settings
        $table->filterGroups('name', 'selectbox', true, true);
        $table->filterGroups('email', 'selectbox', true, false);
        
        $filters = $table->getFilterManager()->toArray();
        
        $this->assertCount(2, $filters);
        $this->assertTrue($filters[0]['bidirectional']);
        $this->assertFalse($filters[1]['bidirectional']);
    }

    /**
     * Test that global bidirectional cascade overrides per-filter setting.
     *
     * @return void
     */
    public function test_global_bidirectional_cascade_overrides_per_filter(): void
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        // Enable global bidirectional cascade
        $table->setBidirectionalCascade(true);
        
        // Add filter with bidirectional = false (should be overridden)
        $table->filterGroups('name', 'selectbox', true, false);
        
        $filters = $table->getFilterManager()->toArray();
        
        $this->assertCount(1, $filters);
        $this->assertTrue($filters[0]['bidirectional']); // Overridden by global setting
    }

    /**
     * Test that explicit bidirectional = true works without global setting.
     *
     * @return void
     */
    public function test_explicit_bidirectional_without_global_setting(): void
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        // Don't enable global bidirectional cascade
        // Add filter with explicit bidirectional = true
        $table->filterGroups('name', 'selectbox', true, true);
        
        $filters = $table->getFilterManager()->toArray();
        
        $this->assertCount(1, $filters);
        $this->assertTrue($filters[0]['bidirectional']);
    }

    /**
     * Test multiple filters with mixed bidirectional settings.
     *
     * @return void
     */
    public function test_multiple_filters_with_mixed_bidirectional(): void
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        // Add filters with different settings (using columns that exist in User model)
        $table->filterGroups('name', 'selectbox', true, true);      // Bidirectional
        $table->filterGroups('email', 'selectbox', true, false);    // Forward only
        $table->filterGroups('active', 'selectbox', true, true);    // Bidirectional (using 'active' instead of 'status')
        $table->filterGroups('created_at', 'datebox', true, false); // Forward only
        
        $filters = $table->getFilterManager()->toArray();
        
        $this->assertCount(4, $filters);
        $this->assertTrue($filters[0]['bidirectional']);   // name
        $this->assertFalse($filters[1]['bidirectional']);  // email
        $this->assertTrue($filters[2]['bidirectional']);   // active
        $this->assertFalse($filters[3]['bidirectional']);  // created_at
    }

    /**
     * Test that method returns self for chaining.
     *
     * @return void
     */
    public function test_method_returns_self_for_chaining(): void
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        $result = $table->filterGroups('name', 'selectbox', true, true);
        
        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertSame($table, $result);
    }

    /**
     * Test that method validates column exists.
     *
     * @return void
     */
    public function test_method_validates_column_exists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Column.*nonexistent.*does not exist/');
        
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        $table->filterGroups('nonexistent', 'selectbox', true, true);
    }

    /**
     * Test that method validates filter type.
     *
     * @return void
     */
    public function test_method_validates_filter_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid filter type');
        
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        $table->filterGroups('name', 'invalidtype', true, true);
    }

    /**
     * Test with all valid filter types.
     *
     * @return void
     */
    public function test_all_valid_filter_types(): void
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        $validTypes = ['inputbox', 'datebox', 'daterangebox', 'selectbox', 'checkbox', 'radiobox'];
        
        foreach ($validTypes as $type) {
            $table->filterGroups('name', $type, true, true);
        }
        
        $filters = $table->getFilterManager()->toArray();
        
        // Note: Each call to filterGroups with the same column overwrites the previous one
        // So we should only have 1 filter (the last one)
        $this->assertCount(1, $filters);
        $this->assertEquals('radiobox', $filters[0]['type']); // Last type added
        $this->assertTrue($filters[0]['bidirectional']);
    }

    /**
     * Test with specific column cascade.
     *
     * @return void
     */
    public function test_with_specific_column_cascade(): void
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        // Cascade to specific column
        $table->filterGroups('name', 'selectbox', 'email', true);
        
        $filters = $table->getFilterManager()->toArray();
        
        $this->assertCount(1, $filters);
        $this->assertEquals('email', $filters[0]['relate']);
        $this->assertTrue($filters[0]['bidirectional']);
    }

    /**
     * Test with multiple column cascade.
     *
     * @return void
     */
    public function test_with_multiple_column_cascade(): void
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        // Cascade to multiple columns
        $table->filterGroups('name', 'selectbox', ['email', 'created_at'], true);
        
        $filters = $table->getFilterManager()->toArray();
        
        $this->assertCount(1, $filters);
        $this->assertIsArray($filters[0]['relate']);
        $this->assertContains('email', $filters[0]['relate']);
        $this->assertContains('created_at', $filters[0]['relate']);
        $this->assertTrue($filters[0]['bidirectional']);
    }

    /**
     * Test that FilterManager receives bidirectional flag.
     *
     * @return void
     */
    public function test_filter_manager_receives_bidirectional_flag(): void
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());
        
        // Add filter with bidirectional = true
        $table->filterGroups('name', 'selectbox', true, true);
        
        $filterManager = $table->getFilterManager();
        $filters = $filterManager->toArray();
        
        $this->assertCount(1, $filters);
        $this->assertTrue($filters[0]['bidirectional']);
    }

    /**
     * Test documentation examples work correctly.
     *
     * @return void
     */
    public function test_documentation_examples(): void
    {
        // Example 1: Simple filter (no cascade)
        $table1 = app(TableBuilder::class);
        $table1->setModel(new User());
        $table1->filterGroups('active', 'selectbox'); // Using 'active' instead of 'status'
        $this->assertCount(1, $table1->getFilterManager()->toArray());
        
        // Example 2: Forward cascade only
        $table2 = app(TableBuilder::class);
        $table2->setModel(new User());
        $table2->filterGroups('name', 'selectbox', true);
        $table2->filterGroups('email', 'selectbox', true);
        $this->assertCount(2, $table2->getFilterManager()->toArray());
        
        // Example 3: Bi-directional per filter
        $table3 = app(TableBuilder::class);
        $table3->setModel(new User());
        $table3->filterGroups('name', 'selectbox', true, true);
        $table3->filterGroups('email', 'selectbox', true, true);
        $filters3 = $table3->getFilterManager()->toArray();
        $this->assertTrue($filters3[0]['bidirectional']);
        $this->assertTrue($filters3[1]['bidirectional']);
        
        // Example 4: Bi-directional globally
        $table4 = app(TableBuilder::class);
        $table4->setModel(new User());
        $table4->setBidirectionalCascade(true);
        $table4->filterGroups('name', 'selectbox', true);
        $table4->filterGroups('email', 'selectbox', true);
        $filters4 = $table4->getFilterManager()->toArray();
        $this->assertTrue($filters4[0]['bidirectional']);
        $this->assertTrue($filters4[1]['bidirectional']);
    }
}
