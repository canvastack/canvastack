<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * Test TableBuilder integration with StateManager for unique ID-based state tracking.
 *
 * VALIDATES: Requirements 5.1, 5.2, 5.6
 */
class TableBuilderStateIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create test table
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();

        if (!$schema->hasTable('test_users')) {
            $schema->create('test_users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();

        if ($schema->hasTable('test_users')) {
            $schema->drop('test_users');
        }

        parent::tearDown();
    }

    /**
     * Test that TableBuilder generates unique ID and sets it in StateManager.
     *
     * VALIDATES: Requirement 5.6 - Separate state for each table instance
     *
     * @return void
     */
    public function test_table_builder_sets_unique_id_in_state_manager(): void
    {
        $table = $this->createTableBuilder();
        $table->setModel(new TestUser());
        $table->setFields(['name:Name', 'email:Email']);

        // Get unique ID (triggers generation)
        $uniqueId = $table->getUniqueId();

        // Verify unique ID is set
        $this->assertNotNull($uniqueId);
        $this->assertStringStartsWith('canvastable_', $uniqueId);

        // Verify StateManager has the table ID set
        $stateManager = $this->getProtectedProperty($table, 'stateManager');
        $this->assertEquals($uniqueId, $stateManager->getTableId());
    }

    /**
     * Test state isolation between multiple TableBuilder instances.
     *
     * VALIDATES: Requirement 5.1 - Support multiple instances on same page
     * VALIDATES: Requirement 5.2 - Unique IDs for each instance
     * VALIDATES: Requirement 5.6 - Separate state for each table instance
     *
     * @return void
     */
    public function test_state_isolation_between_multiple_table_instances(): void
    {
        // Create three table instances
        $table1 = $this->createTableBuilder();
        $table1->setModel(new TestUser());
        $table1->setFields(['name:Name', 'email:Email']);

        $table2 = $this->createTableBuilder();
        $table2->setModel(new TestUser());
        $table2->setFields(['name:Name', 'status:Status']);

        $table3 = $this->createTableBuilder();
        $table3->setModel(new TestUser());
        $table3->setFields(['email:Email', 'status:Status']);

        // Get unique IDs (triggers generation and StateManager setup)
        $id1 = $table1->getUniqueId();
        $id2 = $table2->getUniqueId();
        $id3 = $table3->getUniqueId();

        // Verify all IDs are different
        $this->assertNotEquals($id1, $id2);
        $this->assertNotEquals($id2, $id3);
        $this->assertNotEquals($id1, $id3);

        // Get StateManagers
        $stateManager1 = $this->getProtectedProperty($table1, 'stateManager');
        $stateManager2 = $this->getProtectedProperty($table2, 'stateManager');
        $stateManager3 = $this->getProtectedProperty($table3, 'stateManager');

        // Save state for each table
        $stateManager1->saveState('filters', ['status' => 'active']);
        $stateManager1->saveState('page', 1);

        $stateManager2->saveState('filters', ['status' => 'inactive']);
        $stateManager2->saveState('page', 2);

        $stateManager3->saveState('filters', ['status' => 'archived']);
        $stateManager3->saveState('page', 3);

        // Verify state isolation
        $this->assertEquals(['status' => 'active'], $stateManager1->getState('filters'));
        $this->assertEquals(1, $stateManager1->getState('page'));

        $this->assertEquals(['status' => 'inactive'], $stateManager2->getState('filters'));
        $this->assertEquals(2, $stateManager2->getState('page'));

        $this->assertEquals(['status' => 'archived'], $stateManager3->getState('filters'));
        $this->assertEquals(3, $stateManager3->getState('page'));
    }

    /**
     * Test that each table instance maintains separate state even with same model.
     *
     * VALIDATES: Requirement 5.6 - Separate state for each table instance
     *
     * @return void
     */
    public function test_separate_state_with_same_model(): void
    {
        // Create two tables with same model but different fields
        $table1 = $this->createTableBuilder();
        $table1->setModel(new TestUser());
        $table1->setFields(['name:Name', 'email:Email']);

        $table2 = $this->createTableBuilder();
        $table2->setModel(new TestUser());
        $table2->setFields(['name:Name', 'status:Status']); // Different fields

        // Get unique IDs
        $id1 = $table1->getUniqueId();
        $id2 = $table2->getUniqueId();

        // IDs should be different even with same model (different fields)
        $this->assertNotEquals($id1, $id2);

        // Get StateManagers
        $stateManager1 = $this->getProtectedProperty($table1, 'stateManager');
        $stateManager2 = $this->getProtectedProperty($table2, 'stateManager');

        // Save different state for each
        $stateManager1->saveState('sorting', ['column' => 'name', 'direction' => 'asc']);
        $stateManager2->saveState('sorting', ['column' => 'status', 'direction' => 'desc']);

        // Verify isolation
        $this->assertEquals(
            ['column' => 'name', 'direction' => 'asc'],
            $stateManager1->getState('sorting')
        );
        $this->assertEquals(
            ['column' => 'status', 'direction' => 'desc'],
            $stateManager2->getState('sorting')
        );
    }

    /**
     * Test state persistence across multiple operations on same table.
     *
     * @return void
     */
    public function test_state_persistence_across_operations(): void
    {
        $table = $this->createTableBuilder();
        $table->setModel(new TestUser());
        $table->setFields(['name:Name', 'email:Email']);

        // Get unique ID
        $uniqueId = $table->getUniqueId();

        // Get StateManager
        $stateManager = $this->getProtectedProperty($table, 'stateManager');

        // Save state
        $stateManager->saveState('filters', ['status' => 'active']);
        $stateManager->saveState('sorting', ['column' => 'name', 'direction' => 'asc']);
        $stateManager->saveState('pagination', ['page' => 1, 'pageSize' => 10]);

        // Verify state persists
        $this->assertEquals(['status' => 'active'], $stateManager->getState('filters'));
        $this->assertEquals(['column' => 'name', 'direction' => 'asc'], $stateManager->getState('sorting'));
        $this->assertEquals(['page' => 1, 'pageSize' => 10], $stateManager->getState('pagination'));

        // Update state
        $stateManager->saveState('filters', ['status' => 'inactive']);
        $stateManager->saveState('pagination', ['page' => 2, 'pageSize' => 20]);

        // Verify updates
        $this->assertEquals(['status' => 'inactive'], $stateManager->getState('filters'));
        $this->assertEquals(['page' => 2, 'pageSize' => 20], $stateManager->getState('pagination'));
        $this->assertEquals(['column' => 'name', 'direction' => 'asc'], $stateManager->getState('sorting'));
    }

    /**
     * Test that unique ID is generated only once per instance.
     *
     * @return void
     */
    public function test_unique_id_generated_once_per_instance(): void
    {
        $table = $this->createTableBuilder();
        $table->setModel(new TestUser());
        $table->setFields(['name:Name', 'email:Email']);

        // Get unique ID multiple times
        $id1 = $table->getUniqueId();
        $id2 = $table->getUniqueId();
        $id3 = $table->getUniqueId();

        // All should be the same (cached)
        $this->assertEquals($id1, $id2);
        $this->assertEquals($id2, $id3);
    }

    /**
     * Test state history tracking per table instance.
     *
     * @return void
     */
    public function test_state_history_per_table_instance(): void
    {
        $table1 = $this->createTableBuilder();
        $table1->setModel(new TestUser());
        $table1->setFields(['name:Name']);

        $table2 = $this->createTableBuilder();
        $table2->setModel(new TestUser());
        $table2->setFields(['email:Email']);

        // Get unique IDs
        $id1 = $table1->getUniqueId();
        $id2 = $table2->getUniqueId();

        // Get StateManagers
        $stateManager1 = $this->getProtectedProperty($table1, 'stateManager');
        $stateManager2 = $this->getProtectedProperty($table2, 'stateManager');

        // Make changes to table 1
        $stateManager1->saveState('key1', 'value1');
        $stateManager1->saveState('key1', 'value1_updated');

        // Make changes to table 2
        $stateManager2->saveState('key2', 'value2');

        // Get history for each table
        $history1 = $stateManager1->getStateHistory($id1);
        $history2 = $stateManager2->getStateHistory($id2);

        // Verify history is separate
        $this->assertCount(2, $history1); // 2 changes to table 1
        $this->assertCount(1, $history2); // 1 change to table 2
    }

    /**
     * Helper method to create TableBuilder instance.
     *
     * @return TableBuilder
     */
    protected function createTableBuilder(): TableBuilder
    {
        return $this->app->make(TableBuilder::class);
    }

    /**
     * Helper method to get protected property value.
     *
     * @param object $object
     * @param string $property
     * @return mixed
     */
    protected function getProtectedProperty(object $object, string $property)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}

/**
 * Test model for TableBuilder tests.
 */
class TestUser extends Model
{
    protected $table = 'test_users';

    protected $fillable = ['name', 'email', 'status'];
}

