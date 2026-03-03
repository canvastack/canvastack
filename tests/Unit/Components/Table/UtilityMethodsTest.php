<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Mockery;

/**
 * Unit tests for TableBuilder utility methods.
 *
 * Tests Phase 8: Utility Methods (Requirements 35.1, 35.2, 35.4)
 * - clear() method resets all configuration
 * - clearVar() method resets specific properties
 * - Public properties for backward compatibility
 */
class UtilityMethodsTest extends TestCase
{
    protected TableBuilder $table;

    protected Model $mockModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Drop table if exists BEFORE creating (fix for "table already exists" error)
        Schema::dropIfExists('users');

        // Create users table in SQLite memory database
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('token')->nullable();
            $table->string('uuid')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        // Create TableBuilder instance
        $this->table = app(TableBuilder::class);

        // Mock model
        $this->mockModel = Mockery::mock(Model::class);
        $this->mockModel->shouldReceive('getTable')->andReturn('users')->byDefault();
        $this->mockModel->shouldReceive('getConnectionName')->andReturn('testing')->byDefault();
        $this->mockModel->shouldReceive('newQuery')->andReturn(Mockery::mock(\Illuminate\Database\Eloquent\Builder::class))->byDefault();
    }

    protected function tearDown(): void
    {
        // Drop test table (ensure cleanup)
        Schema::dropIfExists('users');

        Mockery::close();
        parent::tearDown();
    }

    // ============================================================
    // TEST: clear() METHOD (Requirement 35.1)
    // ============================================================

    /**
     * @test
     * Test clear() resets all configuration properties.
     */
    public function test_clear_resets_all_properties(): void
    {
        // Configure table with various settings
        $this->table
            ->setModel($this->mockModel)
            ->setName('users')
            ->label('User List')
            ->method('index')
            ->setFields(['id', 'name', 'email'])
            ->setHiddenColumns(['password'])
            ->setColumnWidth('name', 200)
            ->setWidth(100, '%')
            ->addAttributes(['class' => 'table-striped'])
            ->setAlignColumns('center', ['id'])
            ->setBackgroundColor('#f0f0f0', '#000000', ['name'])
            ->fixedColumns(1, 1)
            ->mergeColumns('Full Info', ['name', 'email'])
            ->orderby('created_at', 'desc')
            ->sortable(['id', 'name'])
            ->searchable(['name', 'email'])
            ->clickable(['name'])
            ->displayRowsLimitOnLoad(25)
            ->setUrlValue('uuid')
            ->setDatatableType(false)
            ->cache(300)
            ->chunk(50);

        // Clear all configuration
        $result = $this->table->clear();

        // Assert method chaining
        $this->assertInstanceOf(TableBuilder::class, $result);

        // Assert all properties are reset to defaults
        $this->assertNull($this->getPrivateProperty($this->table, 'model'));
        $this->assertNull($this->getPrivateProperty($this->table, 'tableName'));
        $this->assertNull($this->getPrivateProperty($this->table, 'tableLabel'));
        $this->assertNull($this->getPrivateProperty($this->table, 'methodName'));
        $this->assertEmpty($this->getPrivateProperty($this->table, 'columns'));
        $this->assertEmpty($this->getPrivateProperty($this->table, 'columnLabels'));
        $this->assertEmpty($this->getPrivateProperty($this->table, 'hiddenColumns'));
        $this->assertEmpty($this->getPrivateProperty($this->table, 'columnWidths'));
        $this->assertNull($this->getPrivateProperty($this->table, 'tableWidth'));
        $this->assertEmpty($this->getPrivateProperty($this->table, 'attributes'));
        $this->assertEmpty($this->getPrivateProperty($this->table, 'columnAlignments'));
        $this->assertEmpty($this->getPrivateProperty($this->table, 'columnColors'));
        $this->assertNull($this->getPrivateProperty($this->table, 'fixedLeft'));
        $this->assertNull($this->getPrivateProperty($this->table, 'fixedRight'));
        $this->assertEmpty($this->getPrivateProperty($this->table, 'mergedColumns'));
        $this->assertNull($this->getPrivateProperty($this->table, 'orderColumn'));
        $this->assertEquals('asc', $this->getPrivateProperty($this->table, 'orderDirection'));
        $this->assertNull($this->getPrivateProperty($this->table, 'sortableColumns'));
        $this->assertNull($this->getPrivateProperty($this->table, 'searchableColumns'));
        $this->assertNull($this->getPrivateProperty($this->table, 'clickableColumns'));
        $this->assertEquals(10, $this->getPrivateProperty($this->table, 'displayLimit'));
        $this->assertEquals('id', $this->getPrivateProperty($this->table, 'urlValueField'));
        $this->assertTrue($this->getPrivateProperty($this->table, 'isDatatable'));
        $this->assertNull($this->getPrivateProperty($this->table, 'cacheTime'));
        $this->assertEquals(100, $this->getPrivateProperty($this->table, 'chunkSize'));
    }

    /**
     * @test
     * Test clear(false) preserves columns and model.
     */
    public function test_clear_with_false_preserves_columns_and_model(): void
    {
        // Configure table
        $this->table
            ->setModel($this->mockModel)
            ->setName('users')
            ->setFields(['id', 'name', 'email'])
            ->setHiddenColumns(['password'])
            ->orderby('created_at', 'desc');

        // Clear with clearSet = false
        $result = $this->table->clear(false);

        // Assert method chaining
        $this->assertInstanceOf(TableBuilder::class, $result);

        // Assert model and columns are preserved
        $this->assertNotNull($this->getPrivateProperty($this->table, 'model'));
        $this->assertEquals('users', $this->getPrivateProperty($this->table, 'tableName'));
        $this->assertNotEmpty($this->getPrivateProperty($this->table, 'columns'));

        // Assert other properties are reset
        $this->assertEmpty($this->getPrivateProperty($this->table, 'hiddenColumns'));
        $this->assertNull($this->getPrivateProperty($this->table, 'orderColumn'));
    }

    // ============================================================
    // TEST: clearVar() METHOD (Requirement 35.2)
    // ============================================================

    /**
     * @test
     * Test clearVar() resets specific properties.
     */
    public function test_clear_var_resets_specific_properties(): void
    {
        // Set table name first
        $this->table->setName('users');

        // Configure multiple properties
        $this->table
            ->setHiddenColumns(['password'])
            ->setColumnWidth('name', 200)
            ->orderby('created_at', 'desc')
            ->sortable(['id', 'name'])
            ->displayRowsLimitOnLoad(25);

        // Clear specific property
        $result = $this->table->clearVar('hiddenColumns');

        // Assert method chaining
        $this->assertInstanceOf(TableBuilder::class, $result);

        // Assert only hiddenColumns is reset
        $this->assertEmpty($this->getPrivateProperty($this->table, 'hiddenColumns'));

        // Assert other properties are preserved
        $this->assertNotEmpty($this->getPrivateProperty($this->table, 'columnWidths'));
        $this->assertEquals('created_at', $this->getPrivateProperty($this->table, 'orderColumn'));
        $this->assertNotNull($this->getPrivateProperty($this->table, 'sortableColumns'));
        $this->assertEquals(25, $this->getPrivateProperty($this->table, 'displayLimit'));
    }

    /**
     * @test
     * Test clearVar() with legacy property names.
     */
    public function test_clear_var_supports_legacy_property_names(): void
    {
        // Set table name first
        $this->table->setName('users');

        // Configure properties
        $this->table
            ->setHiddenColumns(['password'])
            ->searchable(['name', 'email'])
            ->setUrlValue('uuid');

        // Clear using legacy names
        $this->table->clearVar('hidden_columns');
        $this->table->clearVar('search_columns');
        $this->table->clearVar('useFieldTargetURL');

        // Assert properties are reset
        $this->assertEmpty($this->getPrivateProperty($this->table, 'hiddenColumns'));
        $this->assertNull($this->getPrivateProperty($this->table, 'searchableColumns'));
        $this->assertEquals('id', $this->getPrivateProperty($this->table, 'urlValueField'));
    }

    /**
     * @test
     * Test clearVar() throws exception for invalid property name.
     */
    public function test_clear_var_throws_exception_for_invalid_property(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid property name: "invalidProperty"');

        $this->table->clearVar('invalidProperty');
    }

    /**
     * @test
     * Test clearVar() resets array properties to empty arrays.
     */
    public function test_clear_var_resets_array_properties_to_empty_arrays(): void
    {
        // Set table name first
        $this->table->setName('users');

        // Configure array properties
        $this->table
            ->setHiddenColumns(['password'])
            ->sortable(['id', 'name']);

        // Clear array properties
        $this->table->clearVar('hiddenColumns');
        $this->table->clearVar('sortableColumns');

        // Assert arrays are empty
        $this->assertEmpty($this->getPrivateProperty($this->table, 'hiddenColumns'));
        $this->assertNull($this->getPrivateProperty($this->table, 'sortableColumns'));
    }

    /**
     * @test
     * Test clearVar() resets nullable properties to null.
     */
    public function test_clear_var_resets_nullable_properties_to_null(): void
    {
        // Configure nullable properties
        $this->table
            ->setModel($this->mockModel)
            ->setName('users')
            ->orderby('created_at', 'desc')
            ->fixedColumns(1, 1);

        // Clear nullable properties
        $this->table->clearVar('model');
        $this->table->clearVar('orderColumn');
        $this->table->clearVar('fixedLeft');

        // Assert properties are null
        $this->assertNull($this->getPrivateProperty($this->table, 'model'));
        $this->assertNull($this->getPrivateProperty($this->table, 'orderColumn'));
        $this->assertNull($this->getPrivateProperty($this->table, 'fixedLeft'));
    }

    // ============================================================
    // TEST: PUBLIC PROPERTIES (Requirement 35.4)
    // ============================================================

    /**
     * @test
     * Test public properties are accessible for backward compatibility.
     */
    public function test_public_properties_are_accessible(): void
    {
        // Assert public properties exist and are accessible
        $this->assertIsArray($this->table->hidden_columns);
        $this->assertIsArray($this->table->button_removed);
        $this->assertIsArray($this->table->conditions);
        $this->assertIsArray($this->table->formula);
        $this->assertIsString($this->table->useFieldTargetURL);
        $this->assertNull($this->table->search_columns);
    }

    /**
     * @test
     * Test public properties sync with protected properties.
     */
    public function test_public_properties_sync_with_protected_properties(): void
    {
        // Set table name first
        $this->table->setName('users');

        // Set hidden columns
        $this->table->setHiddenColumns(['password', 'token']);

        // Assert public property is synced
        $this->assertEquals(['password', 'token'], $this->table->hidden_columns);
        $this->assertEquals(
            $this->getPrivateProperty($this->table, 'hiddenColumns'),
            $this->table->hidden_columns
        );
    }

    /**
     * @test
     * Test button_removed property syncs with removedButtons.
     */
    public function test_button_removed_property_syncs(): void
    {
        // Remove buttons
        $this->table->removeButtons(['edit', 'delete']);

        // Assert public property is synced
        $this->assertEquals(['edit', 'delete'], $this->table->button_removed);
        $this->assertEquals(
            $this->getPrivateProperty($this->table, 'removedButtons'),
            $this->table->button_removed
        );
    }

    /**
     * @test
     * Test useFieldTargetURL property syncs with urlValueField.
     */
    public function test_use_field_target_url_property_syncs(): void
    {
        // Set table name first
        $this->table->setName('users');

        // Set URL value field
        $this->table->setUrlValue('uuid');

        // Assert public property is synced
        $this->assertEquals('uuid', $this->table->useFieldTargetURL);
        $this->assertEquals(
            $this->getPrivateProperty($this->table, 'urlValueField'),
            $this->table->useFieldTargetURL
        );
    }

    /**
     * @test
     * Test search_columns property syncs with searchableColumns.
     */
    public function test_search_columns_property_syncs(): void
    {
        // Set table name first
        $this->table->setName('users');

        // Set searchable columns
        $this->table->searchable(['name', 'email']);

        // Assert public property is synced
        $this->assertEquals(['name', 'email'], $this->table->search_columns);
        $this->assertEquals(
            $this->getPrivateProperty($this->table, 'searchableColumns'),
            $this->table->search_columns
        );
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Get private property value using reflection.
     */
    protected function getPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * Set private property value using reflection.
     */
    protected function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
