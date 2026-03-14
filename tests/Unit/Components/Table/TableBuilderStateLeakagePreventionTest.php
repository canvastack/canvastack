<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

// Use TestUser from TableBuilderStateIntegrationTest
require_once __DIR__ . '/TableBuilderStateIntegrationTest.php';

/**
 * Test TableBuilder state leakage prevention mechanisms.
 *
 * This test suite validates that TableBuilder properly prevents state leakage
 * between multiple instances through proper StateManager integration.
 *
 * VALIDATES: Requirement 5.6 - Separate state for each table instance
 *
 * @version 1.0.0
 */
class TableBuilderStateLeakagePreventionTest extends TestCase
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
     * Test that configuration changes in one table don't leak to another.
     *
     * @return void
     */
    public function test_configuration_changes_do_not_leak_between_tables(): void
    {
        $table1 = $this->createTableBuilder();
        $table1->setModel(new TestUser());
        $table1->setFields(['name:Name', 'email:Email']);

        $table2 = $this->createTableBuilder();
        $table2->setModel(new TestUser());
        $table2->setFields(['name:Name', 'status:Status']);

        // Trigger unique ID generation (initializes StateManager table ID)
        $table1->getUniqueId();
        $table2->getUniqueId();

        // Configure table 1
        $table1->setHiddenColumns(['email']);
        $table1->fixedColumns(1, 0);

        // Configure table 2 differently
        $table2->setHiddenColumns(['status']);
        $table2->fixedColumns(0, 1);

        // Get StateManagers
        $stateManager1 = $this->getProtectedProperty($table1, 'stateManager');
        $stateManager2 = $this->getProtectedProperty($table2, 'stateManager');

        // Verify each table has unique state manager with different table IDs
        $this->assertNotEquals(
            $stateManager1->getTableId(),
            $stateManager2->getTableId()
        );

        // Verify configuration is isolated (stored in TableBuilder properties)
        $hiddenColumns1 = $this->getProtectedProperty($table1, 'hiddenColumns');
        $hiddenColumns2 = $this->getProtectedProperty($table2, 'hiddenColumns');
        $this->assertEquals(['email'], $hiddenColumns1);
        $this->assertEquals(['status'], $hiddenColumns2);

        $fixedLeft1 = $this->getProtectedProperty($table1, 'fixedLeft');
        $fixedLeft2 = $this->getProtectedProperty($table2, 'fixedLeft');
        $this->assertEquals(1, $fixedLeft1);
        $this->assertEquals(0, $fixedLeft2);
    }

    /**
     * Test that clearing configuration in one table doesn't affect another.
     *
     * @return void
     */
    public function test_clearing_configuration_does_not_affect_other_tables(): void
    {
        $table1 = $this->createTableBuilder();
        $table1->setModel(new TestUser());
        $table1->setFields(['name:Name', 'email:Email']);
        $table1->setHiddenColumns(['email']);
        $table1->fixedColumns(1, 0);

        $table2 = $this->createTableBuilder();
        $table2->setModel(new TestUser());
        $table2->setFields(['name:Name', 'status:Status']);
        $table2->setHiddenColumns(['status']);
        $table2->fixedColumns(0, 1);

        // Get StateManagers
        $stateManager1 = $this->getProtectedProperty($table1, 'stateManager');
        $stateManager2 = $this->getProtectedProperty($table2, 'stateManager');

        // Save initial configuration to StateManager
        $stateManager2->saveState('test_config', ['status' => 'active']);

        // Clear table 1 configuration
        $table1->clearFixedColumns();

        // Verify table 2 StateManager is unchanged
        $this->assertEquals(['status' => 'active'], $stateManager2->getState('test_config'));

        // Verify table 2 configuration is unchanged
        $hiddenColumns2 = $this->getProtectedProperty($table2, 'hiddenColumns');
        $fixedLeft2 = $this->getProtectedProperty($table2, 'fixedLeft');
        $this->assertEquals(['status'], $hiddenColumns2);
        $this->assertEquals(0, $fixedLeft2);
    }

    /**
     * Test that resetting one table doesn't affect another.
     *
     * @return void
     */
    public function test_resetting_one_table_does_not_affect_another(): void
    {
        $table1 = $this->createTableBuilder();
        $table1->setModel(new TestUser());
        $table1->setFields(['name:Name', 'email:Email']);
        $table1->setHiddenColumns(['email']);
        $table1->fixedColumns(1, 0);

        $table2 = $this->createTableBuilder();
        $table2->setModel(new TestUser());
        $table2->setFields(['name:Name', 'status:Status']);
        $table2->setHiddenColumns(['status']);
        $table2->fixedColumns(0, 1);

        // Get StateManagers
        $stateManager1 = $this->getProtectedProperty($table1, 'stateManager');
        $stateManager2 = $this->getProtectedProperty($table2, 'stateManager');

        // Save state to both tables
        $stateManager2->saveState('test_key', 'test_value');

        // Reset table 1
        $table1->resetConfiguration();

        // Verify table 2 StateManager is unchanged
        $this->assertEquals('test_value', $stateManager2->getState('test_key'));

        // Verify table 2 configuration is unchanged
        $hiddenColumns2 = $this->getProtectedProperty($table2, 'hiddenColumns');
        $this->assertEquals(['status'], $hiddenColumns2);
    }

    /**
     * Test that multiple tables can have same configuration without interference.
     *
     * @return void
     */
    public function test_multiple_tables_with_same_configuration_are_isolated(): void
    {
        $table1 = $this->createTableBuilder();
        $table1->setModel(new TestUser());
        $table1->setFields(['name:Name', 'email:Email']);
        $table1->setHiddenColumns(['email']);

        $table2 = $this->createTableBuilder();
        $table2->setModel(new TestUser());
        $table2->setFields(['name:Name', 'email:Email']); // Same fields
        $table2->setHiddenColumns(['email']); // Same hidden columns

        // Initialize tables (triggers unique ID generation)
        $this->initializeTable($table1);
        $this->initializeTable($table2);

        // Get StateManagers
        $stateManager1 = $this->getProtectedProperty($table1, 'stateManager');
        $stateManager2 = $this->getProtectedProperty($table2, 'stateManager');

        // Verify they have different table IDs
        $this->assertNotEquals(
            $stateManager1->getTableId(),
            $stateManager2->getTableId()
        );

        // Modify table 1
        $table1->setHiddenColumns(['name']);

        // Verify table 2 is unchanged
        $hiddenColumns2 = $this->getProtectedProperty($table2, 'hiddenColumns');
        $this->assertEquals(['email'], $hiddenColumns2);
    }

    /**
     * Test state isolation when tables are created and destroyed rapidly.
     *
     * @return void
     */
    public function test_state_isolation_with_rapid_table_creation(): void
    {
        $tables = [];
        $stateManagers = [];

        // Create 10 tables rapidly
        for ($i = 0; $i < 10; $i++) {
            $table = $this->createTableBuilder();
            $table->setModel(new TestUser());
            $table->setFields(['name:Name', 'email:Email']);

            // Save state to StateManager instead of using setHiddenColumns
            $stateManager = $this->getProtectedProperty($table, 'stateManager');
            $stateManager->saveState('test_index', $i);

            $tables[] = $table;
            $stateManagers[] = $stateManager;
        }

        // Verify each table has unique state
        for ($i = 0; $i < 10; $i++) {
            $testIndex = $stateManagers[$i]->getState('test_index');
            $this->assertEquals($i, $testIndex);

            // Verify no other table has this state
            for ($j = 0; $j < 10; $j++) {
                if ($i !== $j) {
                    $otherTestIndex = $stateManagers[$j]->getState('test_index');
                    $this->assertNotEquals($testIndex, $otherTestIndex);
                }
            }
        }
    }

    /**
     * Test that state isolation works with complex configuration chains.
     *
     * @return void
     */
    public function test_state_isolation_with_complex_configuration_chains(): void
    {
        $table1 = $this->createTableBuilder();
        $table1->setModel(new TestUser())
            ->setFields(['name:Name', 'email:Email'])
            ->setHiddenColumns(['email'])
            ->fixedColumns(1, 0)
            ->setRightColumns(['email'])
            ->setCenterColumns(['name']);

        $table2 = $this->createTableBuilder();
        $table2->setModel(new TestUser())
            ->setFields(['name:Name', 'status:Status'])
            ->setHiddenColumns(['status'])
            ->fixedColumns(0, 1)
            ->setRightColumns(['status'])
            ->setCenterColumns(['name']);

        // Initialize tables
        $this->initializeTable($table1);
        $this->initializeTable($table2);

        // Get StateManagers
        $stateManager1 = $this->getProtectedProperty($table1, 'stateManager');
        $stateManager2 = $this->getProtectedProperty($table2, 'stateManager');

        // Save state to each
        $stateManager1->saveState('config_chain', 'chain1');
        $stateManager2->saveState('config_chain', 'chain2');

        // Verify complete isolation
        $this->assertEquals('chain1', $stateManager1->getState('config_chain'));
        $this->assertEquals('chain2', $stateManager2->getState('config_chain'));
        $this->assertNotEquals(
            $stateManager1->getTableId(),
            $stateManager2->getTableId()
        );
    }

    /**
     * Test that state isolation persists after multiple operations.
     *
     * @return void
     */
    public function test_state_isolation_persists_after_multiple_operations(): void
    {
        $table1 = $this->createTableBuilder();
        $table1->setModel(new TestUser());
        $table1->setFields(['name:Name', 'email:Email']);

        $table2 = $this->createTableBuilder();
        $table2->setModel(new TestUser());
        $table2->setFields(['name:Name', 'status:Status']);

        // Get StateManagers
        $stateManager1 = $this->getProtectedProperty($table1, 'stateManager');
        $stateManager2 = $this->getProtectedProperty($table2, 'stateManager');

        // Perform multiple operations on table 1
        $table1->setHiddenColumns(['email']);
        $table1->fixedColumns(1, 0);
        $table1->clearFixedColumns();
        $table1->setHiddenColumns(['name']);
        $stateManager1->saveState('operations', 4);

        // Perform different operations on table 2
        $table2->setHiddenColumns(['status']);
        $table2->fixedColumns(0, 1);
        $stateManager2->saveState('operations', 2);

        // Verify final state is isolated
        $this->assertEquals(4, $stateManager1->getState('operations'));
        $this->assertEquals(2, $stateManager2->getState('operations'));

        // Verify configuration is isolated
        $hiddenColumns1 = $this->getProtectedProperty($table1, 'hiddenColumns');
        $hiddenColumns2 = $this->getProtectedProperty($table2, 'hiddenColumns');
        $this->assertEquals(['name'], $hiddenColumns1);
        $this->assertEquals(['status'], $hiddenColumns2);
    }

    /**
     * Test that getStateManager returns isolated instance.
     *
     * @return void
     */
    public function test_get_state_manager_returns_isolated_instance(): void
    {
        $table1 = $this->createTableBuilder();
        $table1->setModel(new TestUser());
        $table1->setFields(['name:Name', 'email:Email']);

        $table2 = $this->createTableBuilder();
        $table2->setModel(new TestUser());
        $table2->setFields(['name:Name', 'status:Status']);

        // Initialize tables
        $this->initializeTable($table1);
        $this->initializeTable($table2);

        // Get StateManagers via public method
        $stateManager1 = $table1->getStateManager();
        $stateManager2 = $table2->getStateManager();

        // Verify they are the same instance (singleton per TableBuilder)
        $this->assertSame($stateManager1, $table1->getStateManager());
        $this->assertSame($stateManager2, $table2->getStateManager());

        // But different table IDs
        $this->assertNotEquals(
            $stateManager1->getTableId(),
            $stateManager2->getTableId()
        );

        // Save state to each
        $stateManager1->saveState('test_key', 'value1');
        $stateManager2->saveState('test_key', 'value2');

        // Verify isolation
        $this->assertEquals('value1', $stateManager1->getState('test_key'));
        $this->assertEquals('value2', $stateManager2->getState('test_key'));
    }

    /**
     * Test that state isolation works with collection-based tables.
     *
     * @return void
     */
    public function test_state_isolation_with_collection_based_tables(): void
    {
        $collection1 = collect([
            ['name' => 'User 1', 'email' => 'user1@example.com'],
            ['name' => 'User 2', 'email' => 'user2@example.com'],
        ]);

        $collection2 = collect([
            ['name' => 'User 3', 'status' => 'active'],
            ['name' => 'User 4', 'status' => 'inactive'],
        ]);

        $table1 = $this->createTableBuilder();
        $table1->setCollection($collection1);
        $table1->setFields(['name:Name', 'email:Email']);
        $table1->setHiddenColumns(['email']);

        $table2 = $this->createTableBuilder();
        $table2->setCollection($collection2);
        $table2->setFields(['name:Name', 'status:Status']);
        $table2->setHiddenColumns(['status']);

        // Initialize tables
        $this->initializeTable($table1);
        $this->initializeTable($table2);

        // Get StateManagers
        $stateManager1 = $this->getProtectedProperty($table1, 'stateManager');
        $stateManager2 = $this->getProtectedProperty($table2, 'stateManager');

        // Save state to each
        $stateManager1->saveState('collection_type', 'users');
        $stateManager2->saveState('collection_type', 'statuses');

        // Verify isolation
        $this->assertEquals('users', $stateManager1->getState('collection_type'));
        $this->assertEquals('statuses', $stateManager2->getState('collection_type'));
        $this->assertNotEquals(
            $stateManager1->getTableId(),
            $stateManager2->getTableId()
        );
    }

    /**
     * Test that state isolation works when tables share the same model class.
     *
     * @return void
     */
    public function test_state_isolation_with_shared_model_class(): void
    {
        $model = new TestUser();

        $table1 = $this->createTableBuilder();
        $table1->setModel($model);
        $table1->setFields(['name:Name', 'email:Email']);
        $table1->setHiddenColumns(['email']);

        $table2 = $this->createTableBuilder();
        $table2->setModel($model); // Same model instance
        $table2->setFields(['name:Name', 'status:Status']);
        $table2->setHiddenColumns(['status']);

        // Initialize tables
        $this->initializeTable($table1);
        $this->initializeTable($table2);

        // Get StateManagers
        $stateManager1 = $this->getProtectedProperty($table1, 'stateManager');
        $stateManager2 = $this->getProtectedProperty($table2, 'stateManager');

        // Save state to each
        $stateManager1->saveState('model_usage', 'table1');
        $stateManager2->saveState('model_usage', 'table2');

        // Verify isolation despite shared model
        $this->assertEquals('table1', $stateManager1->getState('model_usage'));
        $this->assertEquals('table2', $stateManager2->getState('model_usage'));
        $this->assertNotEquals(
            $stateManager1->getTableId(),
            $stateManager2->getTableId()
        );
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
     * Helper method to initialize table with unique ID.
     *
     * Triggers unique ID generation which initializes the StateManager table ID.
     *
     * @param TableBuilder $table
     * @return void
     */
    protected function initializeTable(TableBuilder $table): void
    {
        $table->getUniqueId();
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
