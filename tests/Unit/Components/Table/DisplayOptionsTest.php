<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Unit tests for TableBuilder display options methods.
 *
 * Tests Requirements:
 * - 13.1, 13.2, 13.3: displayRowsLimitOnLoad() with integer and 'all'
 * - 13.4: clearOnLoad() resets limit
 * - 14.1, 14.2, 14.3, 14.4: setUrlValue() validates field
 * - 15.1, 15.2: setDatatableType() toggles flag
 * - 15.3: set_regular_table() disables DataTables
 * - 36.1, 36.2: Unit testing requirements
 */
class DisplayOptionsTest extends TestCase
{
    protected TableBuilder $tableBuilder;

    protected Model $testModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test_users table in SQLite memory database
        \Illuminate\Support\Facades\Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('uuid')->nullable();
            $table->timestamps();
        });

        // Create mock model with test columns
        $this->testModel = $this->createMockModel();

        // Initialize TableBuilder with dependencies using helper
        $this->tableBuilder = $this->createTableBuilder();

        // Set model and table name
        $this->tableBuilder->setModel($this->testModel);
        $this->tableBuilder->setName('test_users');
    }

    /**
     * Create a mock model for testing.
     */
    protected function createMockModel(): Model
    {
        return new class () extends Model {
            protected $table = 'test_users';

            protected $fillable = ['name', 'email', 'uuid'];

            public $timestamps = false;
        };
    }

    protected function tearDown(): void
    {
        // Drop test table
        \Illuminate\Support\Facades\Schema::dropIfExists('test_users');

        parent::tearDown();
    }

    /**
     * Test displayRowsLimitOnLoad() with integer value.
     *
     * Requirement 13.1: Accept integer for display limit
     */
    public function test_display_rows_limit_on_load_with_integer(): void
    {
        $result = $this->tableBuilder->displayRowsLimitOnLoad(25);

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals(25, $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));
    }

    /**
     * Test displayRowsLimitOnLoad() with 'all' string.
     *
     * Requirement 13.2: Accept 'all' for all rows
     */
    public function test_display_rows_limit_on_load_with_all_string(): void
    {
        $result = $this->tableBuilder->displayRowsLimitOnLoad('all');

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals('all', $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));
    }

    /**
     * Test displayRowsLimitOnLoad() with '*' string.
     *
     * Requirement 13.2: Accept '*' for all rows
     */
    public function test_display_rows_limit_on_load_with_asterisk(): void
    {
        $result = $this->tableBuilder->displayRowsLimitOnLoad('*');

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals('all', $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));
    }

    /**
     * Test displayRowsLimitOnLoad() validates positive integers.
     *
     * Requirement 13.3: Validate integer is positive
     */
    public function test_display_rows_limit_on_load_rejects_negative_integer(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid display limit: -10');

        $this->tableBuilder->displayRowsLimitOnLoad(-10);
    }

    /**
     * Test displayRowsLimitOnLoad() rejects zero.
     *
     * Requirement 13.3: Validate integer is positive
     */
    public function test_display_rows_limit_on_load_rejects_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid display limit: 0');

        $this->tableBuilder->displayRowsLimitOnLoad(0);
    }

    /**
     * Test displayRowsLimitOnLoad() rejects invalid string.
     *
     * Requirement 13.3: Validate limit value
     */
    public function test_display_rows_limit_on_load_rejects_invalid_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid display limit: invalid');

        $this->tableBuilder->displayRowsLimitOnLoad('invalid');
    }

    /**
     * Test displayRowsLimitOnLoad() with default value.
     *
     * Requirement 13.1: Default value is 10
     */
    public function test_display_rows_limit_on_load_with_default(): void
    {
        $result = $this->tableBuilder->displayRowsLimitOnLoad();

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals(10, $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));
    }

    /**
     * Test clearOnLoad() resets display limit.
     *
     * Requirement 13.4: Reset display limit to default (10)
     */
    public function test_clear_on_load_resets_limit(): void
    {
        // Set custom limit
        $this->tableBuilder->displayRowsLimitOnLoad(50);
        $this->assertEquals(50, $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));

        // Clear limit
        $result = $this->tableBuilder->clearOnLoad();

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals(10, $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));
    }

    /**
     * Test clearOnLoad() resets 'all' limit.
     *
     * Requirement 13.4: Reset display limit to default
     */
    public function test_clear_on_load_resets_all_limit(): void
    {
        // Set 'all' limit
        $this->tableBuilder->displayRowsLimitOnLoad('all');
        $this->assertEquals('all', $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));

        // Clear limit
        $result = $this->tableBuilder->clearOnLoad();

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals(10, $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));
    }

    /**
     * Test setUrlValue() with valid field.
     *
     * Requirement 14.1, 14.2: Set URL value field
     */
    public function test_set_url_value_with_valid_field(): void
    {
        $result = $this->tableBuilder->setUrlValue('uuid');

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals('uuid', $this->getPrivateProperty($this->tableBuilder, 'urlValueField'));
    }

    /**
     * Test setUrlValue() with default 'id' field.
     *
     * Requirement 14.4: Default to 'id' field
     */
    public function test_set_url_value_with_default_id(): void
    {
        $result = $this->tableBuilder->setUrlValue();

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals('id', $this->getPrivateProperty($this->tableBuilder, 'urlValueField'));
    }

    /**
     * Test setUrlValue() validates field exists.
     *
     * Requirement 14.3: Validate field exists in table schema
     */
    public function test_set_url_value_validates_field_exists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'nonexistent_field' does not exist in table 'test_users'");

        $this->tableBuilder->setUrlValue('nonexistent_field');
    }

    /**
     * Test setDatatableType() enables DataTables.
     *
     * Requirement 15.1: Enable DataTables
     */
    public function test_set_datatable_type_enables_datatables(): void
    {
        $result = $this->tableBuilder->setDatatableType(true);

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertTrue($this->getPrivateProperty($this->tableBuilder, 'isDatatable'));
    }

    /**
     * Test setDatatableType() disables DataTables.
     *
     * Requirement 15.2: Disable DataTables
     */
    public function test_set_datatable_type_disables_datatables(): void
    {
        $result = $this->tableBuilder->setDatatableType(false);

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertFalse($this->getPrivateProperty($this->tableBuilder, 'isDatatable'));
    }

    /**
     * Test setDatatableType() with default value.
     *
     * Requirement 15.1: Default is true (enabled)
     */
    public function test_set_datatable_type_with_default(): void
    {
        // First disable it
        $this->tableBuilder->setDatatableType(false);
        $this->assertFalse($this->getPrivateProperty($this->tableBuilder, 'isDatatable'));

        // Call with default (should enable)
        $result = $this->tableBuilder->setDatatableType();

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertTrue($this->getPrivateProperty($this->tableBuilder, 'isDatatable'));
    }

    /**
     * Test set_regular_table() disables DataTables.
     *
     * Requirement 15.3: Convenience method to disable DataTables
     */
    public function test_set_regular_table_disables_datatables(): void
    {
        // Ensure DataTables is enabled first
        $this->tableBuilder->setDatatableType(true);
        $this->assertTrue($this->getPrivateProperty($this->tableBuilder, 'isDatatable'));

        // Call set_regular_table()
        $result = $this->tableBuilder->set_regular_table();

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertFalse($this->getPrivateProperty($this->tableBuilder, 'isDatatable'));
    }

    /**
     * Test method chaining with display options.
     *
     * Requirement 36.2: Fluent interface support
     */
    public function test_method_chaining_with_display_options(): void
    {
        $result = $this->tableBuilder
            ->displayRowsLimitOnLoad(25)
            ->setUrlValue('uuid')
            ->setDatatableType(true);

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals(25, $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));
        $this->assertEquals('uuid', $this->getPrivateProperty($this->tableBuilder, 'urlValueField'));
        $this->assertTrue($this->getPrivateProperty($this->tableBuilder, 'isDatatable'));
    }

    /**
     * Test display options with complex configuration.
     *
     * Requirement 36.1: Comprehensive testing
     */
    public function test_display_options_complex_configuration(): void
    {
        $this->tableBuilder
            ->setFields(['id', 'name', 'email'])
            ->displayRowsLimitOnLoad(50)
            ->setUrlValue('uuid')
            ->set_regular_table();

        $this->assertEquals(50, $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));
        $this->assertEquals('uuid', $this->getPrivateProperty($this->tableBuilder, 'urlValueField'));
        $this->assertFalse($this->getPrivateProperty($this->tableBuilder, 'isDatatable'));
    }

    /**
     * Test displayRowsLimitOnLoad() saves to session when session manager is active.
     *
     * Requirement 3.1.1: Session persistence for display limit
     */
    public function test_display_rows_limit_on_load_saves_to_session(): void
    {
        // Enable session filters to initialize session manager
        $this->tableBuilder->sessionFilters();
        
        // Set display limit
        $this->tableBuilder->displayRowsLimitOnLoad(25);
        
        // Verify it was saved to session
        $sessionManager = $this->getPrivateProperty($this->tableBuilder, 'sessionManager');
        $this->assertNotNull($sessionManager);
        $this->assertEquals(25, $sessionManager->get('display_limit'));
    }

    /**
     * Test displayRowsLimitOnLoad() saves 'all' to session.
     *
     * Requirement 3.1.1: Session persistence for 'all' limit
     */
    public function test_display_rows_limit_on_load_saves_all_to_session(): void
    {
        // Enable session filters to initialize session manager
        $this->tableBuilder->sessionFilters();
        
        // Set display limit to 'all'
        $this->tableBuilder->displayRowsLimitOnLoad('all');
        
        // Verify it was saved to session
        $sessionManager = $this->getPrivateProperty($this->tableBuilder, 'sessionManager');
        $this->assertNotNull($sessionManager);
        $this->assertEquals('all', $sessionManager->get('display_limit'));
    }

    /**
     * Test getDisplayLimit() retrieves from session when available.
     *
     * Requirement 3.1.1: Session persistence retrieval
     */
    public function test_get_display_limit_retrieves_from_session(): void
    {
        // Enable session filters to initialize session manager
        $this->tableBuilder->sessionFilters();
        
        // Set display limit via session manager directly
        $sessionManager = $this->getPrivateProperty($this->tableBuilder, 'sessionManager');
        $sessionManager->save(['display_limit' => 75]);
        
        // Verify getDisplayLimit returns session value
        $this->assertEquals(75, $this->tableBuilder->getDisplayLimit());
    }

    /**
     * Test getDisplayLimit() falls back to property when no session.
     *
     * Requirement 3.1.1: Fallback to property value
     */
    public function test_get_display_limit_falls_back_to_property(): void
    {
        // Set display limit without session manager
        $this->tableBuilder->displayRowsLimitOnLoad(30);
        
        // Verify getDisplayLimit returns property value
        $this->assertEquals(30, $this->tableBuilder->getDisplayLimit());
    }

    /**
     * Test displayRowsLimitOnLoad() works without session manager.
     *
     * Requirement 3.1.1: Graceful handling when session manager not active
     */
    public function test_display_rows_limit_on_load_without_session_manager(): void
    {
        // Set display limit without initializing session manager
        $result = $this->tableBuilder->displayRowsLimitOnLoad(40);
        
        // Should work normally
        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals(40, $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));
        
        // Session manager should still be null
        $this->assertNull($this->getPrivateProperty($this->tableBuilder, 'sessionManager'));
    }

    /**
     * Helper method to access private properties for testing.
     */
    protected function getPrivateProperty(object $object, string $property)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * Helper method to set private properties for testing.
     */
    protected function setPrivateProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
