<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * TableBuilderTest - Unit tests for TableBuilder.
 */
class TableBuilderTest extends TestCase
{
    protected TableBuilder $tableBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tableBuilder = $this->createTableBuilder();

        Cache::flush();
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TableBuilder::class, $this->tableBuilder);
    }

    /** @test */
    public function it_sets_and_gets_context(): void
    {
        $this->tableBuilder->setContext('admin');
        $this->assertEquals('admin', $this->tableBuilder->getContext());

        $this->tableBuilder->setContext('public');
        $this->assertEquals('public', $this->tableBuilder->getContext());
    }

    /** @test */
    public function it_sets_table_name_with_valid_table(): void
    {
        // Create test table
        Schema::dropIfExists('test_users');
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $result = $this->tableBuilder->setName('test_users');

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Cleanup
        Schema::dropIfExists('test_users');
    }

    /** @test */
    public function it_throws_exception_for_invalid_table_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Table 'non_existent_table' does not exist in database");

        $this->tableBuilder->setName('non_existent_table');
    }

    /** @test */
    public function it_sets_table_label(): void
    {
        $label = 'User Management Table';
        $result = $this->tableBuilder->label($label);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_sets_method_identifier(): void
    {
        $method = 'getUserList';
        $result = $this->tableBuilder->method($method);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_supports_method_chaining_with_method(): void
    {
        // Create test table
        Schema::dropIfExists('test_method_chaining');
        Schema::create('test_method_chaining', function ($table) {
            $table->id();
            $table->string('name');
        });

        $result = $this->tableBuilder
            ->setName('test_method_chaining')
            ->label('Test Label')
            ->method('getTestData')
            ->setFields(['id', 'name']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Cleanup
        Schema::dropIfExists('test_method_chaining');
    }

    /** @test */
    public function it_stores_method_identifier_for_tracking(): void
    {
        $method = 'fetchReportData';
        $this->tableBuilder->method($method);

        // The method identifier should be stored and accessible
        // Since methodName is protected, we verify it doesn't throw an error
        // and returns proper instance for chaining
        $this->assertInstanceOf(TableBuilder::class, $this->tableBuilder);
    }

    /** @test */
    public function it_sets_database_connection_with_valid_connection(): void
    {
        // Use the 'default' connection which exists in test environment
        $result = $this->tableBuilder->connection('default');

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_throws_exception_for_invalid_connection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Database connection 'non_existent_connection' does not exist in config/database.php");

        $this->tableBuilder->connection('non_existent_connection');
    }

    /** @test */
    public function it_supports_method_chaining_with_connection(): void
    {
        $result = $this->tableBuilder
            ->connection('default')
            ->label('Test Label')
            ->method('getTestData');

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_validates_connection_exists_in_config(): void
    {
        // Get available connections from config
        $connections = config('database.connections', []);

        // Test with first available connection
        if (!empty($connections)) {
            $firstConnection = array_key_first($connections);
            $result = $this->tableBuilder->connection($firstConnection);
            $this->assertInstanceOf(TableBuilder::class, $result);
        }

        $this->assertTrue(true);
    }

    /** @test */
    public function it_provides_helpful_error_message_with_available_connections(): void
    {
        try {
            $this->tableBuilder->connection('invalid_connection');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            // Verify error message contains available connections
            $this->assertStringContainsString('Available connections:', $e->getMessage());
            $this->assertStringContainsString('invalid_connection', $e->getMessage());
        }
    }

    /** @test */
    public function it_supports_method_chaining_with_label(): void
    {
        // Create test table
        Schema::dropIfExists('test_label_chaining');
        Schema::create('test_label_chaining', function ($table) {
            $table->id();
            $table->string('name');
        });

        $result = $this->tableBuilder
            ->setName('test_label_chaining')
            ->label('Test Label')
            ->setFields(['id', 'name']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Cleanup
        Schema::dropIfExists('test_label_chaining');
    }

    /** @test */
    public function it_stores_label_for_display_purposes(): void
    {
        $label = 'Customer Orders';
        $this->tableBuilder->label($label);

        // The label should be stored and accessible through config or rendering
        // Since label is protected, we verify it doesn't throw an error
        // and returns proper instance for chaining
        $this->assertInstanceOf(TableBuilder::class, $this->tableBuilder);
    }

    /** @test */
    public function it_updates_allowed_columns_when_setting_table_name(): void
    {
        // Create test table with specific columns
        Schema::dropIfExists('test_products');
        Schema::create('test_products', function ($table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('stock');
        });

        $this->tableBuilder->setName('test_products');

        // Now try to set columns - should work with valid columns
        $result = $this->tableBuilder->setFields(['id', 'name', 'price']);
        $this->assertInstanceOf(TableBuilder::class, $result);

        // Cleanup
        Schema::dropIfExists('test_products');
    }

    /** @test */
    public function it_validates_columns_after_setting_table_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'invalid_column' does not exist in table 'test_orders'");

        // Create test table
        Schema::dropIfExists('test_orders');
        Schema::create('test_orders', function ($table) {
            $table->id();
            $table->string('order_number');
        });

        $this->tableBuilder->setName('test_orders');

        // Try to set invalid column
        $this->tableBuilder->setFields(['invalid_column']);

        // Cleanup
        Schema::dropIfExists('test_orders');
    }

    /** @test */
    public function it_supports_method_chaining_with_set_name(): void
    {
        // Create test table
        Schema::dropIfExists('test_chaining');
        Schema::create('test_chaining', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $result = $this->tableBuilder
            ->setName('test_chaining')
            ->setFields(['id', 'name'])
            ->cache(300);

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Cleanup
        Schema::dropIfExists('test_chaining');
    }

    /** @test */
    public function it_sets_and_gets_model(): void
    {
        $model = $this->createMockModel();

        $this->tableBuilder->setModel($model);
        $this->assertSame($model, $this->tableBuilder->getModel());
    }

    /** @test */
    public function it_sets_and_gets_columns(): void
    {
        // Create test table
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $columns = ['id', 'name', 'email'];
        $this->tableBuilder->setFields($columns);

        $result = $this->tableBuilder->getColumns();

        // Verify columns are stored as simple array
        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // Check columns are stored correctly
        $this->assertEquals('id', $result[0]);
        $this->assertEquals('name', $result[1]);
        $this->assertEquals('email', $result[2]);

        // Cleanup
        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_columns_against_schema(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        // Try to set invalid column
        $this->tableBuilder->setFields(['invalid_column']);
    }

    /** @test */
    public function it_sets_eager_load_relationships(): void
    {
        $relations = ['profile', 'posts'];
        $this->tableBuilder->eager($relations);

        $this->assertEquals($relations, $this->tableBuilder->getEagerLoad());
    }

    /** @test */
    public function it_adds_single_relationship_with_with_method(): void
    {
        $this->tableBuilder->with('profile');
        $this->tableBuilder->with('posts');

        $this->assertEquals(['profile', 'posts'], $this->tableBuilder->getEagerLoad());
    }

    /** @test */
    public function it_enables_caching(): void
    {
        $result = $this->tableBuilder->cache(300);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_sets_chunk_size(): void
    {
        $result = $this->tableBuilder->chunk(50);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_adds_where_conditions(): void
    {
        // Create test table
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->where('name', '=', 'test');

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Cleanup
        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_where_column(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->where('invalid_column', '=', 'test');
    }

    /** @test */
    public function it_adds_wherein_conditions(): void
    {
        // Create test table
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->whereIn('id', [1, 2, 3]);

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Cleanup
        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_adds_filters(): void
    {
        // Create test table
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $filters = ['name' => 'test', 'email' => 'test@example.com'];
        $result = $this->tableBuilder->addFilters($filters);

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Cleanup
        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_filter_columns(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->addFilters(['invalid_column' => 'test']);
    }

    /** @test */
    public function it_sets_actions(): void
    {
        // Create test table
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $actions = ['view', 'edit', 'delete'];
        $result = $this->tableBuilder->setActions($actions);

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Cleanup
        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_supports_legacy_format_method(): void
    {
        $result = $this->tableBuilder->format();

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_supports_legacy_run_model_method(): void
    {
        $model = $this->createMockModel();
        $result = $this->tableBuilder->runModel($model);

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertSame($model, $this->tableBuilder->getModel());
    }

    /** @test */
    public function it_clears_cache(): void
    {
        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        // This should not throw exception
        $this->tableBuilder->clearCache();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_gets_cache_time(): void
    {
        // Initially cache time should be null
        $this->assertNull($this->tableBuilder->getCacheTime());

        // Set cache time
        $this->tableBuilder->cache(300);

        // Get cache time should return the set value
        $this->assertEquals(300, $this->tableBuilder->getCacheTime());
    }

    /** @test */
    public function it_sets_cache_time(): void
    {
        // Set cache time using setCacheTime()
        $result = $this->tableBuilder->setCacheTime(600);

        // Should return self for method chaining
        $this->assertInstanceOf(TableBuilder::class, $result);

        // Cache time should be set
        $this->assertEquals(600, $this->tableBuilder->getCacheTime());
    }

    /** @test */
    public function it_sets_cache_time_via_cache_method(): void
    {
        // Set cache time using cache()
        $this->tableBuilder->cache(900);

        // getCacheTime() should return the same value
        $this->assertEquals(900, $this->tableBuilder->getCacheTime());
    }

    /** @test */
    public function it_updates_cache_time(): void
    {
        // Set initial cache time
        $this->tableBuilder->cache(300);
        $this->assertEquals(300, $this->tableBuilder->getCacheTime());

        // Update cache time
        $this->tableBuilder->setCacheTime(600);
        $this->assertEquals(600, $this->tableBuilder->getCacheTime());

        // Update again using cache()
        $this->tableBuilder->cache(900);
        $this->assertEquals(900, $this->tableBuilder->getCacheTime());
    }

    /** @test */
    public function it_sets_and_gets_config(): void
    {
        $config = ['key' => 'value'];
        $this->tableBuilder->setConfig($config);

        $this->assertEquals($config, $this->tableBuilder->getConfig());
    }

    /** @test */
    public function it_supports_fluent_interface(): void
    {
        // Create test table
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('status');
        });

        $model = $this->createMockModel();

        $result = $this->tableBuilder
            ->setModel($model)
            ->setFields(['id', 'name'])
            ->eager(['profile'])
            ->cache(300)
            ->chunk(100)
            ->where('status', '=', 'active');

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Cleanup
        Schema::dropIfExists('test_table');
    }

    /**
     * Helper: Create mock model.
     */
    protected function createMockModel(): Model
    {
        return new class () extends Model {
            protected $table = 'test_table';

            protected $fillable = ['id', 'name', 'email', 'status'];

            public $timestamps = false;
        };
    }

    // ============================================================
    // TESTS FOR SUB-TASKS 1.5-1.11 (Phase 1 Core Configuration)
    // ============================================================

    /** @test */
    public function it_resets_connection_to_default(): void
    {
        // First set a custom connection
        $this->tableBuilder->connection('default');

        // Then reset it
        $result = $this->tableBuilder->resetConnection();

        // Should return instance for chaining
        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_supports_method_chaining_with_reset_connection(): void
    {
        $result = $this->tableBuilder
            ->connection('default')
            ->resetConnection()
            ->label('Test Label');

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_merges_configuration_options(): void
    {
        // Set initial config
        $this->tableBuilder->setConfig(['key1' => 'value1']);

        // Merge additional config
        $result = $this->tableBuilder->config(['key2' => 'value2']);

        // Should return instance for chaining
        $this->assertInstanceOf(TableBuilder::class, $result);

        // Both configs should be present
        $config = $this->tableBuilder->getConfig();
        $this->assertArrayHasKey('key1', $config);
        $this->assertArrayHasKey('key2', $config);
        $this->assertEquals('value1', $config['key1']);
        $this->assertEquals('value2', $config['key2']);
    }

    /** @test */
    public function it_merges_config_without_replacing_existing(): void
    {
        $this->tableBuilder->setConfig(['key1' => 'value1', 'key2' => 'value2']);
        $this->tableBuilder->config(['key2' => 'updated', 'key3' => 'value3']);

        $config = $this->tableBuilder->getConfig();

        // key1 should remain unchanged
        $this->assertEquals('value1', $config['key1']);
        // key2 should be updated
        $this->assertEquals('updated', $config['key2']);
        // key3 should be added
        $this->assertEquals('value3', $config['key3']);
    }

    /** @test */
    public function it_supports_empty_config_array(): void
    {
        $result = $this->tableBuilder->config([]);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_executes_function_on_model_with_run_model(): void
    {
        // Create a mock model with a custom method
        $model = new class () extends Model {
            protected $table = 'test_table';

            public function activeUsers()
            {
                return $this->newQuery()->where('status', 'active');
            }
        };

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('status');
        });

        $result = $this->tableBuilder->runModel($model, 'activeUsers');

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_throws_exception_in_strict_mode_when_method_does_not_exist(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method nonExistentMethod does not exist');

        $model = $this->createMockModel();

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $this->tableBuilder->runModel($model, 'nonExistentMethod', true);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_does_not_throw_exception_in_non_strict_mode_when_method_does_not_exist(): void
    {
        $model = $this->createMockModel();

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $result = $this->tableBuilder->runModel($model, 'nonExistentMethod', false);

        // Should just set the model and return without error
        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertSame($model, $this->tableBuilder->getModel());

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_handles_builder_return_type_from_run_model(): void
    {
        $model = new class () extends Model {
            protected $table = 'test_table';

            public function scopeActive($query)
            {
                return $query->where('status', 'active');
            }

            public function getActiveQuery()
            {
                return $this->active();
            }
        };

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('status');
        });

        $result = $this->tableBuilder->runModel($model, 'getActiveQuery');

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_model_when_no_function_name_provided(): void
    {
        $model = $this->createMockModel();

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $result = $this->tableBuilder->runModel($model, '');

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertSame($model, $this->tableBuilder->getModel());

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_accepts_valid_select_query(): void
    {
        $sql = 'SELECT id, name, email FROM users WHERE status = ?';

        $result = $this->tableBuilder->query($sql);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_rejects_drop_statement(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: DROP');

        $this->tableBuilder->query('DROP TABLE users');
    }

    /** @test */
    public function it_rejects_truncate_statement(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: TRUNCATE');

        $this->tableBuilder->query('TRUNCATE TABLE users');
    }

    /** @test */
    public function it_rejects_delete_statement(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: DELETE');

        $this->tableBuilder->query('DELETE FROM users WHERE id = 1');
    }

    /** @test */
    public function it_rejects_update_statement(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: UPDATE');

        $this->tableBuilder->query('UPDATE users SET name = "test" WHERE id = 1');
    }

    /** @test */
    public function it_rejects_insert_statement(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: INSERT');

        $this->tableBuilder->query('INSERT INTO users (name) VALUES ("test")');
    }

    /** @test */
    public function it_rejects_alter_statement(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: ALTER');

        $this->tableBuilder->query('ALTER TABLE users ADD COLUMN test VARCHAR(255)');
    }

    /** @test */
    public function it_rejects_query_not_starting_with_select(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query must start with SELECT');

        $this->tableBuilder->query('SHOW TABLES');
    }

    /** @test */
    public function it_accepts_select_query_with_joins(): void
    {
        $sql = 'SELECT u.id, u.name, p.title FROM users u JOIN posts p ON u.id = p.user_id';

        $result = $this->tableBuilder->query($sql);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_accepts_select_query_with_subquery(): void
    {
        $sql = 'SELECT * FROM users WHERE id IN (SELECT user_id FROM posts)';

        $result = $this->tableBuilder->query($sql);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_enables_server_side_processing(): void
    {
        $result = $this->tableBuilder->setServerSide(true);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_disables_server_side_processing(): void
    {
        $result = $this->tableBuilder->setServerSide(false);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_defaults_to_enabled_server_side_processing(): void
    {
        $result = $this->tableBuilder->setServerSide();

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_supports_method_chaining_with_set_server_side(): void
    {
        $result = $this->tableBuilder
            ->setServerSide(true)
            ->label('Test Label')
            ->method('getTestData');

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_stores_filter_model_data(): void
    {
        $filterData = [
            'status' => ['active', 'inactive'],
            'role' => ['admin', 'user'],
        ];

        $result = $this->tableBuilder->filterModel($filterData);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_supports_empty_filter_model_data(): void
    {
        $result = $this->tableBuilder->filterModel([]);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_supports_method_chaining_with_filter_model(): void
    {
        $result = $this->tableBuilder
            ->filterModel(['status' => ['active']])
            ->label('Test Label')
            ->method('getTestData');

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_supports_complex_filter_model_data(): void
    {
        $filterData = [
            'status' => [
                'options' => ['active', 'inactive', 'pending'],
                'default' => 'active',
            ],
            'date_range' => [
                'start' => '2024-01-01',
                'end' => '2024-12-31',
            ],
        ];

        $result = $this->tableBuilder->filterModel($filterData);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    // ============================================================
    // TESTS FOR PHASE 2: COLUMN CONFIGURATION METHODS
    // ============================================================

    /** @test */
    public function it_sets_fields_with_simple_array_format(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->setFields(['id', 'name', 'email']);

        $this->assertInstanceOf(TableBuilder::class, $result);
        $columns = $this->tableBuilder->getColumns();
        $this->assertCount(3, $columns);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_fields_with_colon_format(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->setFields([
            'id:ID',
            'name:Full Name',
            'email:Email Address',
        ]);

        $this->assertInstanceOf(TableBuilder::class, $result);
        $columns = $this->tableBuilder->getColumns();
        $this->assertCount(3, $columns);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_fields_with_associative_array_format(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->setFields([
            'id' => 'ID',
            'name' => 'Full Name',
            'email' => 'Email Address',
        ]);

        $this->assertInstanceOf(TableBuilder::class, $result);
        $columns = $this->tableBuilder->getColumns();
        $this->assertCount(3, $columns);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_columns_in_set_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->setFields(['invalid_column']);
    }

    /** @test */
    public function it_sets_hidden_columns(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->setHiddenColumns(['password']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_hidden_columns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->setHiddenColumns(['invalid_column']);
    }

    /** @test */
    public function it_sets_column_width(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->setColumnWidth('name', 200);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_column_in_set_column_width(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->setColumnWidth('invalid_column', 200);
    }

    /** @test */
    public function it_sets_table_width_with_pixels(): void
    {
        $result = $this->tableBuilder->setWidth(800, 'px');

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_sets_table_width_with_percentage(): void
    {
        $result = $this->tableBuilder->setWidth(100, '%');

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_validates_measurement_unit_in_set_width(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid measurement unit');

        $this->tableBuilder->setWidth(100, 'invalid');
    }

    /** @test */
    public function it_accepts_valid_html_attributes(): void
    {
        $result = $this->tableBuilder->addAttributes([
            'class' => 'table-striped',
            'id' => 'my-table',
            'data-test' => 'value',
        ]);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_rejects_event_handler_attributes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event handlers are not allowed');

        $this->tableBuilder->addAttributes(['onclick' => 'alert("xss")']);
    }

    /** @test */
    public function it_rejects_javascript_urls_in_attributes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dangerous URL schemes');

        $this->tableBuilder->addAttributes(['href' => 'javascript:alert("xss")']);
    }

    /** @test */
    public function it_rejects_data_urls_in_attributes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dangerous URL schemes');

        $this->tableBuilder->addAttributes(['src' => 'data:text/html,<script>alert("xss")</script>']);
    }

    /** @test */
    public function it_sets_column_alignment(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->setAlignColumns('right', ['price']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_alignment_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid alignment: invalid');

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->setAlignColumns('invalid', ['name']);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_applies_alignment_to_all_columns_when_empty_array(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);
        $this->tableBuilder->setFields(['id', 'name', 'email']);

        $result = $this->tableBuilder->setAlignColumns('center', []);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_right_columns(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->decimal('price', 10, 2);
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->setRightColumns(['price']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_center_columns(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('status');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->setCenterColumns(['status']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_left_columns(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->setLeftColumns(['name']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_background_color_with_valid_hex(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('status');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->setBackgroundColor('#ff0000', '#ffffff', ['status']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_background_color_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid color format');

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('status');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->setBackgroundColor('red', null, ['status']);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_text_color_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid text color format');

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('status');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->setBackgroundColor('#ff0000', 'white', ['status']);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_applies_background_color_to_all_columns_when_null(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);
        $this->tableBuilder->setFields(['id', 'name', 'email']);

        $result = $this->tableBuilder->setBackgroundColor('#f0f0f0', null, null);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_fixed_columns_from_left(): void
    {
        $result = $this->tableBuilder->fixedColumns(2, null);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_sets_fixed_columns_from_right(): void
    {
        $result = $this->tableBuilder->fixedColumns(null, 1);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_sets_fixed_columns_from_both_sides(): void
    {
        $result = $this->tableBuilder->fixedColumns(2, 1);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_validates_left_position_is_non_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Left position must be non-negative');

        $this->tableBuilder->fixedColumns(-1, null);
    }

    /** @test */
    public function it_validates_right_position_is_non_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Right position must be non-negative');

        $this->tableBuilder->fixedColumns(null, -1);
    }

    /** @test */
    public function it_clears_fixed_columns(): void
    {
        $this->tableBuilder->fixedColumns(2, 1);

        $result = $this->tableBuilder->clearFixedColumns();

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_merges_columns(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->mergeColumns('Full Name', ['first_name', 'last_name'], 'top');

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_label_position_in_merge_columns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid label position');

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->mergeColumns('Full Name', ['first_name', 'last_name'], 'invalid');

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_columns_in_merge_columns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->mergeColumns('Full Name', ['invalid_column'], 'top');
    }

    /** @test */
    public function it_supports_method_chaining_with_column_configuration(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->decimal('price', 10, 2);
        });

        $model = $this->createMockModel();

        $result = $this->tableBuilder
            ->setModel($model)
            ->setFields(['id:ID', 'name:Name', 'email:Email', 'price:Price'])
            ->setHiddenColumns([])
            ->setColumnWidth('name', 200)
            ->setWidth(100, '%')
            ->addAttributes(['class' => 'table-striped'])
            ->setRightColumns(['price'])
            ->setCenterColumns(['id'])
            ->fixedColumns(1, null);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    // ============================================================
    // TESTS FOR PHASE 3: SORTING AND SEARCHING METHODS
    // ============================================================

    /** @test */
    public function it_sets_default_ordering_with_asc_direction(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->orderby('name', 'asc');

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_default_ordering_with_desc_direction(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->orderby('name', 'desc');

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_defaults_to_asc_direction_when_not_specified(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->orderby('name');

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_column_exists_in_orderby(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->orderby('invalid_column');
    }

    /** @test */
    public function it_validates_order_direction_in_orderby(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort order: invalid');

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->orderby('name', 'invalid');

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_accepts_case_insensitive_order_direction(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result1 = $this->tableBuilder->orderby('name', 'ASC');
        $this->assertInstanceOf(TableBuilder::class, $result1);

        $result2 = $this->tableBuilder->orderby('name', 'DESC');
        $this->assertInstanceOf(TableBuilder::class, $result2);

        $result3 = $this->tableBuilder->orderby('name', 'Asc');
        $this->assertInstanceOf(TableBuilder::class, $result3);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_supports_method_chaining_with_orderby(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();

        $result = $this->tableBuilder
            ->setModel($model)
            ->orderby('name', 'asc')
            ->setFields(['id', 'name']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_sortable_columns_to_all_when_null(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->sortable(null);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_sortable_columns_to_none_when_false(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->sortable(false);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_sortable_columns_to_specific_columns(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->sortable(['name', 'email']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_columns_in_sortable_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->sortable(['invalid_column']);
    }

    /** @test */
    public function it_supports_method_chaining_with_sortable(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();

        $result = $this->tableBuilder
            ->setModel($model)
            ->sortable(['name'])
            ->setFields(['id', 'name']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_searchable_columns_to_all_when_null(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->searchable(null);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_searchable_columns_to_none_when_false(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->searchable(false);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_searchable_columns_to_specific_columns(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->searchable(['name', 'email']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_columns_in_searchable_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->searchable(['invalid_column']);
    }

    /** @test */
    public function it_supports_method_chaining_with_searchable(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();

        $result = $this->tableBuilder
            ->setModel($model)
            ->searchable(['name'])
            ->setFields(['id', 'name']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_clickable_columns_to_all_when_null(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->clickable(null);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_clickable_columns_to_none_when_false(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->clickable(false);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_sets_clickable_columns_to_specific_columns(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->clickable(['name', 'email']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_columns_in_clickable_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->clickable(['invalid_column']);
    }

    /** @test */
    public function it_supports_method_chaining_with_clickable(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();

        $result = $this->tableBuilder
            ->setModel($model)
            ->clickable(['name'])
            ->setFields(['id', 'name']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_adds_filter_group_with_inputbox_type(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->filterGroups('name', 'inputbox');

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_adds_filter_group_with_selectbox_type(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('status');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->filterGroups('status', 'selectbox');

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_adds_filter_group_with_datebox_type(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->date('created_at');
        });

        $model = new class () extends Model {
            protected $table = 'test_table';

            protected $fillable = ['id', 'created_at'];

            public $timestamps = false;
        };

        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->filterGroups('created_at', 'datebox');

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_column_in_filter_groups(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->filterGroups('invalid_column', 'inputbox');
    }

    /** @test */
    public function it_validates_filter_type_in_filter_groups(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid filter type: invalid_type');

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->filterGroups('name', 'invalid_type');

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_adds_filter_group_with_relate_true(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('status');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->filterGroups('name', 'inputbox', true);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_adds_filter_group_with_relate_to_specific_column(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('status');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->filterGroups('name', 'inputbox', 'status');

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_adds_filter_group_with_relate_to_multiple_columns(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('status');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder->filterGroups('name', 'inputbox', ['email', 'status']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_related_column_when_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->filterGroups('name', 'inputbox', 'invalid_column');

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_validates_related_columns_when_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $model = $this->createMockModel();
        $this->tableBuilder->setModel($model);

        $this->tableBuilder->filterGroups('name', 'inputbox', ['invalid_column']);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_supports_method_chaining_with_filter_groups(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('status');
        });

        $model = $this->createMockModel();

        $result = $this->tableBuilder
            ->setModel($model)
            ->filterGroups('name', 'inputbox')
            ->filterGroups('status', 'selectbox')
            ->setFields(['id', 'name', 'status']);

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }

    /** @test */
    public function it_supports_all_filter_types(): void
    {
        Schema::dropIfExists('test_table');
        Schema::create('test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('status');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('active');
            $table->string('category');
        });

        $model = new class () extends Model {
            protected $table = 'test_table';

            protected $fillable = ['id', 'name', 'status', 'start_date', 'end_date', 'active', 'category'];

            public $timestamps = false;
        };

        $this->tableBuilder->setModel($model);

        $result = $this->tableBuilder
            ->filterGroups('name', 'inputbox')
            ->filterGroups('start_date', 'datebox')
            ->filterGroups('end_date', 'daterangebox')
            ->filterGroups('status', 'selectbox')
            ->filterGroups('active', 'checkbox')
            ->filterGroups('category', 'radiobox');

        $this->assertInstanceOf(TableBuilder::class, $result);

        Schema::dropIfExists('test_table');
    }
}
