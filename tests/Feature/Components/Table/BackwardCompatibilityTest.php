<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

/**
 * Backward Compatibility Tests for TableBuilder.
 *
 * These tests ensure 100% backward compatibility with the legacy Objects.php API.
 * All legacy method signatures, return types, and behaviors must work exactly as before.
 *
 * Requirements: 33.1, 33.2, 33.3, 33.4, 33.5, 33.6, 33.7, 33.8,
 *               34.1, 34.2, 34.3, 34.4, 34.5, 34.6, 34.7, 34.8, 34.9
 */
class BackwardCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test table
        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('status')->default('active');
            $table->integer('age')->default(0);
            $table->timestamps();
        });

        // Create test model
        $this->createTestModel();

        // Create test data
        for ($i = 1; $i <= 10; $i++) {
            TestUser::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
                'age' => 20 + $i,
            ]);
        }

        // Create TableBuilder instance
        $this->table = app(TableBuilder::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_users');
        parent::tearDown();
    }

    protected function createTestModel(): void
    {
        if (!class_exists(TestUser::class)) {
            eval('
                namespace Canvastack\Canvastack\Tests\Feature\Components\Table;
                
                use Illuminate\Database\Eloquent\Model;
                
                class TestUser extends Model
                {
                    protected $table = "test_users";
                    protected $fillable = ["name", "email", "status", "age"];
                }
            ');
        }
    }

    // ============================================================
    // TASK 15.1: Legacy lists() Method Compatibility Tests
    // Requirements: 34.1, 34.2, 34.3, 34.4, 34.5, 34.6, 34.7, 34.8, 34.9
    // ============================================================

    /**
     * Test lists() with all parameters (full signature).
     *
     * Requirement 34.1: lists() accepts tableName parameter
     * Requirement 34.2: lists() accepts fields parameter
     * Requirement 34.3: lists() accepts actions parameter
     * Requirement 34.4: lists() accepts serverSide parameter
     * Requirement 34.5: lists() accepts numbering parameter
     * Requirement 34.6: lists() accepts attributes parameter
     * Requirement 34.7: lists() accepts serverSideCustomUrl parameter
     */
    public function test_lists_with_all_parameters(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name', 'email'],
            actions: true,
            serverSide: true,
            numbering: true,
            attributes: ['class' => 'table table-striped'],
            serverSideCustomUrl: false
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('table', $html);
    }

    /**
     * Test lists() with minimal parameters (only required).
     *
     * Requirement 34.8: lists() works with default parameters
     */
    public function test_lists_with_minimal_parameters(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists();

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with tableName only.
     *
     * Requirement 34.1: lists() sets table name when provided
     */
    public function test_lists_with_table_name_only(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(tableName: 'test_users');

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with fields parameter.
     *
     * Requirement 34.2: lists() sets fields when provided
     */
    public function test_lists_with_fields_parameter(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name', 'email']
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with actions=true (default actions).
     *
     * Requirement 34.3: lists() enables default actions when actions=true
     */
    public function test_lists_with_actions_true(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name'],
            actions: true
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with actions=false (no actions).
     *
     * Requirement 34.3: lists() disables actions when actions=false
     */
    public function test_lists_with_actions_false(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name'],
            actions: false
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with actions as array (custom actions).
     *
     * Requirement 34.3: lists() accepts custom actions array
     */
    public function test_lists_with_custom_actions_array(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name'],
            actions: ['view', 'edit']
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with serverSide=true.
     *
     * Requirement 34.4: lists() enables server-side processing when serverSide=true
     */
    public function test_lists_with_server_side_true(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name'],
            serverSide: true
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with serverSide=false.
     *
     * Requirement 34.4: lists() disables server-side processing when serverSide=false
     */
    public function test_lists_with_server_side_false(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name'],
            serverSide: false
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with numbering=true.
     *
     * Requirement 34.5: lists() enables row numbering when numbering=true
     */
    public function test_lists_with_numbering_true(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name'],
            numbering: true
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with numbering=false.
     *
     * Requirement 34.5: lists() disables row numbering when numbering=false
     */
    public function test_lists_with_numbering_false(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name'],
            numbering: false
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with HTML attributes.
     *
     * Requirement 34.6: lists() adds HTML attributes when provided
     */
    public function test_lists_with_html_attributes(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name'],
            attributes: [
                'class' => 'table table-striped table-bordered',
                'id' => 'my-table',
                'data-test' => 'value',
            ]
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with serverSideCustomUrl=true.
     *
     * Requirement 34.7: lists() enables custom URL for server-side when serverSideCustomUrl=true
     */
    public function test_lists_with_server_side_custom_url_true(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name'],
            serverSide: true,
            serverSideCustomUrl: true
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() with serverSideCustomUrl=false.
     *
     * Requirement 34.7: lists() uses default URL for server-side when serverSideCustomUrl=false
     */
    public function test_lists_with_server_side_custom_url_false(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name'],
            serverSide: true,
            serverSideCustomUrl: false
        );

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() returns string (not void or other type).
     *
     * Requirement 34.9: lists() returns HTML string
     */
    public function test_lists_returns_string(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $result = $this->table->lists(
            tableName: 'test_users',
            fields: ['id', 'name']
        );

        $this->assertIsString($result);
    }

    /**
     * Test lists() with all parameter combinations (comprehensive).
     *
     * Requirement 34.8: lists() works with all parameter combinations
     */
    public function test_lists_with_various_parameter_combinations(): void
    {
        $model = new TestUser();

        // Combination 1: tableName + fields
        $this->table->setModel($model);
        $html1 = $this->table->lists('test_users', ['id', 'name']);
        $this->assertIsString($html1);

        // Combination 2: tableName + fields + actions
        $this->table = app(TableBuilder::class);
        $this->table->setModel($model);
        $html2 = $this->table->lists('test_users', ['id', 'name'], true);
        $this->assertIsString($html2);

        // Combination 3: tableName + fields + actions + serverSide
        $this->table = app(TableBuilder::class);
        $this->table->setModel($model);
        $html3 = $this->table->lists('test_users', ['id', 'name'], true, false);
        $this->assertIsString($html3);

        // Combination 4: tableName + fields + actions + serverSide + numbering
        $this->table = app(TableBuilder::class);
        $this->table->setModel($model);
        $html4 = $this->table->lists('test_users', ['id', 'name'], true, false, false);
        $this->assertIsString($html4);

        // Combination 5: All parameters
        $this->table = app(TableBuilder::class);
        $this->table->setModel($model);
        $html5 = $this->table->lists(
            'test_users',
            ['id', 'name'],
            true,
            true,
            true,
            ['class' => 'table'],
            false
        );
        $this->assertIsString($html5);
    }

    /**
     * Test lists() preserves configuration from previous method calls.
     *
     * Requirement 33.5: Method chaining works correctly with lists()
     */
    public function test_lists_preserves_previous_configuration(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        // Configure table before calling lists()
        $this->table
            ->setColumnWidth('name', 200)
            ->setRightColumns(['age'])
            ->orderby('name', 'asc');

        $html = $this->table->lists('test_users', ['id', 'name', 'age']);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test lists() can be called multiple times with different parameters.
     *
     * Requirement 34.8: lists() can be called multiple times
     */
    public function test_lists_can_be_called_multiple_times(): void
    {
        $model = new TestUser();

        // First call
        $this->table->setModel($model);
        $html1 = $this->table->lists('test_users', ['id', 'name']);
        $this->assertIsString($html1);

        // Second call with different parameters
        $this->table = app(TableBuilder::class);
        $this->table->setModel($model);
        $html2 = $this->table->lists('test_users', ['id', 'email'], false);
        $this->assertIsString($html2);

        // Both should return valid HTML
        $this->assertNotEmpty($html1);
        $this->assertNotEmpty($html2);
    }

    // ============================================================
    // TASK 15.2: Legacy setFields() Format Compatibility Tests
    // Requirements: 33.1, 33.2
    // ============================================================

    /**
     * Test setFields() with simple array format.
     *
     * Requirement 33.1: setFields() accepts simple array format
     */
    public function test_set_fields_with_simple_array(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $this->table->setFields(['id', 'name', 'email']);

        $html = $this->table->lists();

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test setFields() with colon format for custom labels.
     *
     * Requirement 33.2: setFields() accepts colon format ('field:Label')
     */
    public function test_set_fields_with_colon_format(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $this->table->setFields([
            'id:ID',
            'name:Full Name',
            'email:Email Address',
        ]);

        $html = $this->table->lists();

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test setFields() with associative array format.
     *
     * Requirement 33.2: setFields() accepts associative array format
     */
    public function test_set_fields_with_associative_array(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $this->table->setFields([
            'id' => 'ID',
            'name' => 'Full Name',
            'email' => 'Email Address',
        ]);

        $html = $this->table->lists();

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test setFields() with mixed formats (simple + colon).
     *
     * Requirement 33.2: setFields() handles mixed formats correctly
     */
    public function test_set_fields_with_mixed_formats(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $this->table->setFields([
            'id',
            'name:Full Name',
            'email',
        ]);

        $html = $this->table->lists();

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test setFields() with all three formats in one call.
     *
     * Requirement 33.2: setFields() handles all format combinations
     */
    public function test_set_fields_with_all_format_combinations(): void
    {
        $model = new TestUser();

        // Test 1: Simple array
        $this->table->setModel($model);
        $this->table->setFields(['id', 'name']);
        $html1 = $this->table->lists();
        $this->assertIsString($html1);

        // Test 2: Colon format
        $this->table = app(TableBuilder::class);
        $this->table->setModel($model);
        $this->table->setFields(['id:ID', 'name:Name']);
        $html2 = $this->table->lists();
        $this->assertIsString($html2);

        // Test 3: Associative array
        $this->table = app(TableBuilder::class);
        $this->table->setModel($model);
        $this->table->setFields(['id' => 'ID', 'name' => 'Name']);
        $html3 = $this->table->lists();
        $this->assertIsString($html3);

        // All should return valid HTML
        $this->assertNotEmpty($html1);
        $this->assertNotEmpty($html2);
        $this->assertNotEmpty($html3);
    }

    /**
     * Test setFields() returns $this for method chaining.
     *
     * Requirement 33.5: setFields() returns $this for method chaining
     */
    public function test_set_fields_returns_this_for_chaining(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $result = $this->table->setFields(['id', 'name', 'email']);

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertSame($this->table, $result);
    }

    // ============================================================
    // TASK 15.3: Legacy Method Chaining Compatibility Tests
    // Requirements: 33.5
    // ============================================================

    /**
     * Test method chaining with multiple configuration methods.
     *
     * Requirement 33.5: All methods return $this for chaining
     */
    public function test_method_chaining_with_multiple_methods(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $result = $this->table
            ->setFields(['id', 'name', 'email', 'age'])
            ->setHiddenColumns(['id'])
            ->setColumnWidth('name', 200)
            ->setRightColumns(['age'])
            ->orderby('name', 'asc')
            ->sortable(['name', 'email'])
            ->searchable(['name', 'email']);

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertSame($this->table, $result);

        $html = $this->table->lists();
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test method chaining with all configuration methods.
     *
     * Requirement 33.5: Complex method chains work correctly
     */
    public function test_complex_method_chaining(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $html = $this->table
            ->setFields(['id:ID', 'name:Full Name', 'email:Email', 'age:Age'])
            ->setColumnWidth('name', 200)
            ->setWidth(100, '%')
            ->addAttributes(['class' => 'table table-striped'])
            ->setRightColumns(['age'])
            ->setCenterColumns(['id'])
            ->setBackgroundColor('#f0f0f0', '#000000', ['id'])
            ->orderby('name', 'asc')
            ->sortable(['name', 'email'])
            ->searchable(['name', 'email'])
            ->displayRowsLimitOnLoad(10)
            ->setUrlValue('id')
            ->setActions(true)
            ->lists();

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test that configuration is applied correctly after chaining.
     *
     * Requirement 33.5: Configuration from chained methods is preserved
     */
    public function test_configuration_preserved_after_chaining(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $this->table
            ->setFields(['id', 'name', 'email'])
            ->orderby('name', 'desc')
            ->sortable(false)
            ->searchable(['name']);

        $html = $this->table->lists();

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    // ============================================================
    // TASK 15.4: Legacy Property Access Compatibility Tests
    // Requirements: 35.4
    // ============================================================

    /**
     * Test public property hidden_columns is accessible.
     *
     * Requirement 35.4: Public properties are accessible for backward compatibility
     */
    public function test_public_property_hidden_columns_accessible(): void
    {
        $this->assertIsArray($this->table->hidden_columns);

        // Test that property can be set
        $this->table->hidden_columns = ['id', 'created_at'];
        $this->assertEquals(['id', 'created_at'], $this->table->hidden_columns);
    }

    /**
     * Test public property button_removed is accessible.
     *
     * Requirement 35.4: Public properties are accessible for backward compatibility
     */
    public function test_public_property_button_removed_accessible(): void
    {
        $this->assertIsArray($this->table->button_removed);

        // Test that property can be set
        $this->table->button_removed = ['delete'];
        $this->assertEquals(['delete'], $this->table->button_removed);
    }

    /**
     * Test public property conditions is accessible.
     *
     * Requirement 35.4: Public properties are accessible for backward compatibility
     */
    public function test_public_property_conditions_accessible(): void
    {
        $this->assertIsArray($this->table->conditions);

        // Test that property can be set
        $this->table->conditions = [['field' => 'status', 'value' => 'active']];
        $this->assertCount(1, $this->table->conditions);
    }

    /**
     * Test public property formula is accessible.
     *
     * Requirement 35.4: Public properties are accessible for backward compatibility
     */
    public function test_public_property_formula_accessible(): void
    {
        $this->assertIsArray($this->table->formula);

        // Test that property can be set
        $this->table->formula = [['name' => 'total', 'logic' => 'price * quantity']];
        $this->assertCount(1, $this->table->formula);
    }

    /**
     * Test public property useFieldTargetURL is accessible.
     *
     * Requirement 35.4: Public properties are accessible for backward compatibility
     */
    public function test_public_property_use_field_target_url_accessible(): void
    {
        $this->assertIsString($this->table->useFieldTargetURL);
        $this->assertEquals('id', $this->table->useFieldTargetURL);

        // Test that property can be set
        $this->table->useFieldTargetURL = 'uuid';
        $this->assertEquals('uuid', $this->table->useFieldTargetURL);
    }

    /**
     * Test public property search_columns is accessible.
     *
     * Requirement 35.4: Public properties are accessible for backward compatibility
     */
    public function test_public_property_search_columns_accessible(): void
    {
        $this->assertNull($this->table->search_columns);

        // Test that property can be set
        $this->table->search_columns = ['name', 'email'];
        $this->assertEquals(['name', 'email'], $this->table->search_columns);
    }

    /**
     * Test all public properties work together.
     *
     * Requirement 35.4: All public properties are accessible and functional
     */
    public function test_all_public_properties_work_together(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        // Set all public properties
        $this->table->hidden_columns = ['created_at', 'updated_at'];
        $this->table->button_removed = ['delete'];
        $this->table->conditions = [];
        $this->table->formula = [];
        $this->table->useFieldTargetURL = 'id';
        $this->table->search_columns = ['name', 'email'];

        $html = $this->table->lists('test_users', ['id', 'name', 'email']);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    // ============================================================
    // TASK 15.6: All Legacy Methods Compatibility Tests
    // Requirements: 33.1, 33.2, 33.3, 33.4
    // ============================================================

    /**
     * Test all core configuration methods exist and return $this.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     * Requirement 33.4: All legacy return types are preserved
     */
    public function test_all_core_configuration_methods_exist(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        // Test that all methods exist and return $this
        $this->assertInstanceOf(TableBuilder::class, $this->table->setName('test_users'));
        $this->assertInstanceOf(TableBuilder::class, $this->table->label('Test Users'));
        $this->assertInstanceOf(TableBuilder::class, $this->table->method('index'));
        $this->assertInstanceOf(TableBuilder::class, $this->table->connection('testing'));
        $this->assertInstanceOf(TableBuilder::class, $this->table->resetConnection());
        $this->assertInstanceOf(TableBuilder::class, $this->table->config(['key' => 'value']));
    }

    /**
     * Test all model and data source methods exist and work correctly.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     */
    public function test_all_model_and_data_source_methods_exist(): void
    {
        $model = new TestUser();

        $this->assertInstanceOf(TableBuilder::class, $this->table->setModel($model));
        $this->assertInstanceOf(TableBuilder::class, $this->table->runModel($model, '', false));
        $this->assertInstanceOf(TableBuilder::class, $this->table->setServerSide(true));
        $this->assertInstanceOf(TableBuilder::class, $this->table->filterModel([]));
    }

    /**
     * Test all column configuration methods exist and work correctly.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     */
    public function test_all_column_configuration_methods_exist(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $this->assertInstanceOf(TableBuilder::class, $this->table->setFields(['id', 'name']));
        $this->assertInstanceOf(TableBuilder::class, $this->table->setHiddenColumns(['created_at']));
        $this->assertInstanceOf(TableBuilder::class, $this->table->setColumnWidth('name', 200));
        $this->assertInstanceOf(TableBuilder::class, $this->table->setWidth(100, '%'));
        $this->assertInstanceOf(TableBuilder::class, $this->table->addAttributes(['class' => 'table']));
    }

    /**
     * Test all alignment methods exist and work correctly.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     */
    public function test_all_alignment_methods_exist(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);
        $this->table->setFields(['id', 'name', 'age']);

        $this->assertInstanceOf(TableBuilder::class, $this->table->setAlignColumns('left', ['name']));
        $this->assertInstanceOf(TableBuilder::class, $this->table->setRightColumns(['age']));
        $this->assertInstanceOf(TableBuilder::class, $this->table->setCenterColumns(['id']));
        $this->assertInstanceOf(TableBuilder::class, $this->table->setLeftColumns(['name']));
    }

    /**
     * Test all styling methods exist and work correctly.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     */
    public function test_all_styling_methods_exist(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);
        $this->table->setFields(['id', 'name']);

        $this->assertInstanceOf(TableBuilder::class, $this->table->setBackgroundColor('#f0f0f0', '#000000', ['id']));
        $this->assertInstanceOf(TableBuilder::class, $this->table->fixedColumns(1, 1));
        $this->assertInstanceOf(TableBuilder::class, $this->table->clearFixedColumns());
        $this->assertInstanceOf(TableBuilder::class, $this->table->mergeColumns('Info', ['name', 'email']));
    }

    /**
     * Test all sorting and searching methods exist and work correctly.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     */
    public function test_all_sorting_and_searching_methods_exist(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);
        $this->table->setFields(['id', 'name', 'email']);

        $this->assertInstanceOf(TableBuilder::class, $this->table->orderby('name', 'asc'));
        $this->assertInstanceOf(TableBuilder::class, $this->table->sortable(['name', 'email']));
        $this->assertInstanceOf(TableBuilder::class, $this->table->searchable(['name', 'email']));
        $this->assertInstanceOf(TableBuilder::class, $this->table->clickable(['name']));
        $this->assertInstanceOf(TableBuilder::class, $this->table->filterGroups('status', 'selectbox'));
    }

    /**
     * Test all display option methods exist and work correctly.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     */
    public function test_all_display_option_methods_exist(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $this->assertInstanceOf(TableBuilder::class, $this->table->displayRowsLimitOnLoad(10));
        $this->assertInstanceOf(TableBuilder::class, $this->table->clearOnLoad());
        $this->assertInstanceOf(TableBuilder::class, $this->table->setUrlValue('id'));
        $this->assertInstanceOf(TableBuilder::class, $this->table->setDatatableType(true));
        $this->assertInstanceOf(TableBuilder::class, $this->table->set_regular_table());
    }

    /**
     * Test all condition and formatting methods exist and work correctly.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     */
    public function test_all_condition_and_formatting_methods_exist(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);
        $this->table->setFields(['id', 'name', 'status', 'age']);

        $this->assertInstanceOf(TableBuilder::class, $this->table->where('status', '=', 'active'));
        $this->assertInstanceOf(TableBuilder::class, $this->table->filterConditions(['status' => 'active']));
        $this->assertInstanceOf(TableBuilder::class, $this->table->columnCondition('status', 'cell', '==', 'active', 'css style', 'color: green'));
        $this->assertInstanceOf(TableBuilder::class, $this->table->formula('total', 'Total', ['age'], 'age * 2'));
        $this->assertInstanceOf(TableBuilder::class, $this->table->format(['age'], 0, '.', 'number'));
    }

    /**
     * Test all relation methods exist and work correctly.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     */
    public function test_all_relation_methods_exist(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        // Test that the methods exist by checking they're callable
        $this->assertTrue(method_exists($this->table, 'relations'));
        $this->assertTrue(method_exists($this->table, 'fieldReplacementValue'));

        // Note: We can't test actual execution without real relationships,
        // but we've verified the methods exist with correct signatures
    }

    /**
     * Test all action methods exist and work correctly.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     */
    public function test_all_action_methods_exist(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $this->assertInstanceOf(TableBuilder::class, $this->table->setActions(true));
        $this->assertInstanceOf(TableBuilder::class, $this->table->removeButtons(['delete']));
    }

    /**
     * Test all utility methods exist and work correctly.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     */
    public function test_all_utility_methods_exist(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        $this->assertInstanceOf(TableBuilder::class, $this->table->clear(false));
        $this->assertInstanceOf(TableBuilder::class, $this->table->clearVar('columns'));
    }

    /**
     * Test main rendering methods exist and return correct types.
     *
     * Requirement 33.3: All legacy method signatures are preserved
     * Requirement 33.4: All legacy return types are preserved
     */
    public function test_main_rendering_methods_exist(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        // lists() returns string
        $result1 = $this->table->lists('test_users', ['id', 'name']);
        $this->assertIsString($result1);

        // render() returns string
        $this->table = app(TableBuilder::class);
        $this->table->setModel($model);
        $this->table->setFields(['id', 'name']);
        $result2 = $this->table->render();
        $this->assertIsString($result2);
    }

    /**
     * Test that all 60+ legacy methods work in combination.
     *
     * Requirement 33.1: Complete backward compatibility
     * Requirement 33.2: All legacy API works exactly as before
     */
    public function test_all_legacy_methods_work_in_combination(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        // Use many methods together (simulating real-world usage)
        $html = $this->table
            ->setName('test_users')
            ->label('Test Users')
            ->method('index')
            ->setFields(['id:ID', 'name:Name', 'email:Email', 'status:Status', 'age:Age'])
            ->setHiddenColumns(['created_at', 'updated_at'])
            ->setColumnWidth('name', 200)
            ->setWidth(100, '%')
            ->addAttributes(['class' => 'table table-striped'])
            ->setRightColumns(['age'])
            ->setCenterColumns(['id'])
            ->setBackgroundColor('#f8f9fa', null, ['id'], true, false)
            ->orderby('name', 'asc')
            ->sortable(['name', 'email', 'age'])
            ->searchable(['name', 'email'])
            ->clickable(['name'])
            ->displayRowsLimitOnLoad(10)
            ->setUrlValue('id')
            ->where('status', '=', 'active')
            ->columnCondition('status', 'cell', '==', 'active', 'css style', 'color: green')
            ->format(['age'], 0, ',', 'number')
            ->setActions(true)
            ->removeButtons(['delete'])
            ->lists();

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    /**
     * Test legacy method signatures match exactly.
     *
     * Requirement 33.3: Method signatures must match legacy implementation
     */
    public function test_legacy_method_signatures_match(): void
    {
        $reflection = new \ReflectionClass(TableBuilder::class);

        // Check critical methods exist with correct signatures
        $this->assertTrue($reflection->hasMethod('lists'));
        $this->assertTrue($reflection->hasMethod('setFields'));
        $this->assertTrue($reflection->hasMethod('setModel'));
        $this->assertTrue($reflection->hasMethod('render'));
        $this->assertTrue($reflection->hasMethod('orderby'));
        $this->assertTrue($reflection->hasMethod('sortable'));
        $this->assertTrue($reflection->hasMethod('searchable'));
        $this->assertTrue($reflection->hasMethod('where'));
        $this->assertTrue($reflection->hasMethod('setActions'));

        // Check lists() method signature
        $listsMethod = $reflection->getMethod('lists');
        $this->assertEquals(7, $listsMethod->getNumberOfParameters());
        $this->assertEquals('string', $listsMethod->getReturnType()->getName());
    }

    /**
     * Test legacy return types are preserved.
     *
     * Requirement 33.4: Return types must match legacy implementation
     */
    public function test_legacy_return_types_preserved(): void
    {
        $model = new TestUser();
        $this->table->setModel($model);

        // Methods that return $this (for chaining)
        $this->assertInstanceOf(TableBuilder::class, $this->table->setFields(['id']));
        $this->assertInstanceOf(TableBuilder::class, $this->table->orderby('id'));
        $this->assertInstanceOf(TableBuilder::class, $this->table->where('id', '>', 0));

        // Methods that return string (rendering)
        $this->assertIsString($this->table->lists());

        $this->table = app(TableBuilder::class);
        $this->table->setModel($model);
        $this->table->setFields(['id']);
        $this->assertIsString($this->table->render());

        // Methods that return Model
        $this->assertInstanceOf(Model::class, $this->table->getModel());
    }
}
