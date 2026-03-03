<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;

/**
 * Integration test for displayRowsLimitOnLoad() session persistence.
 *
 * Tests Requirements:
 * - 3.1.1: Enhanced displayRowsLimitOnLoad() method with session persistence
 */
class DisplayLimitSessionTest extends TestCase
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

            protected $fillable = ['name', 'email'];

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
     * Test complete session persistence workflow for display limit.
     *
     * Requirement 3.1.1: Session persistence for display limit
     */
    public function test_display_limit_session_persistence_workflow(): void
    {
        // Step 1: Enable session filters to initialize session manager
        $this->tableBuilder->sessionFilters();
        
        // Step 2: Set display limit - should save to session
        $this->tableBuilder->displayRowsLimitOnLoad(50);
        
        // Step 3: Verify it was saved to session
        $sessionManager = $this->getPrivateProperty($this->tableBuilder, 'sessionManager');
        $this->assertNotNull($sessionManager);
        $this->assertEquals(50, $sessionManager->get('display_limit'));
        
        // Step 4: Create new TableBuilder instance (simulating new request)
        $newTableBuilder = $this->createTableBuilder();
        $newTableBuilder->setModel($this->testModel);
        $newTableBuilder->setName('test_users');
        
        // Step 5: Enable session filters on new instance
        $newTableBuilder->sessionFilters();
        
        // Step 6: Verify display limit is restored from session
        $this->assertEquals(50, $newTableBuilder->getDisplayLimit());
    }

    /**
     * Test session persistence with 'all' value.
     *
     * Requirement 3.1.1: Session persistence for 'all' limit
     */
    public function test_display_limit_all_session_persistence(): void
    {
        // Enable session filters
        $this->tableBuilder->sessionFilters();
        
        // Set display limit to 'all'
        $this->tableBuilder->displayRowsLimitOnLoad('all');
        
        // Verify it was saved to session
        $sessionManager = $this->getPrivateProperty($this->tableBuilder, 'sessionManager');
        $this->assertEquals('all', $sessionManager->get('display_limit'));
        
        // Create new instance and verify restoration
        $newTableBuilder = $this->createTableBuilder();
        $newTableBuilder->setModel($this->testModel);
        $newTableBuilder->setName('test_users');
        $newTableBuilder->sessionFilters();
        
        $this->assertEquals('all', $newTableBuilder->getDisplayLimit());
    }

    /**
     * Test session persistence with '*' value (converted to 'all').
     *
     * Requirement 3.1.1: Session persistence for '*' limit
     */
    public function test_display_limit_asterisk_session_persistence(): void
    {
        // Enable session filters
        $this->tableBuilder->sessionFilters();
        
        // Set display limit to '*' (should be converted to 'all')
        $this->tableBuilder->displayRowsLimitOnLoad('*');
        
        // Verify it was saved as 'all' to session
        $sessionManager = $this->getPrivateProperty($this->tableBuilder, 'sessionManager');
        $this->assertEquals('all', $sessionManager->get('display_limit'));
        
        // Create new instance and verify restoration
        $newTableBuilder = $this->createTableBuilder();
        $newTableBuilder->setModel($this->testModel);
        $newTableBuilder->setName('test_users');
        $newTableBuilder->sessionFilters();
        
        $this->assertEquals('all', $newTableBuilder->getDisplayLimit());
    }

    /**
     * Test that display limit works without session manager.
     *
     * Requirement 3.1.1: Graceful handling when session manager not active
     */
    public function test_display_limit_without_session_manager(): void
    {
        // Set display limit without enabling session filters
        $this->tableBuilder->displayRowsLimitOnLoad(25);
        
        // Should work normally but not save to session
        $this->assertEquals(25, $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));
        $this->assertEquals(25, $this->tableBuilder->getDisplayLimit());
        
        // Session manager should be null
        $this->assertNull($this->getPrivateProperty($this->tableBuilder, 'sessionManager'));
    }

    /**
     * Test session override of default limit.
     *
     * Requirement 3.1.1: Session takes precedence over property
     */
    public function test_session_overrides_property_limit(): void
    {
        // Enable session filters
        $this->tableBuilder->sessionFilters();
        
        // Set property limit directly
        $this->setPrivateProperty($this->tableBuilder, 'displayLimit', 10);
        
        // Set session limit to different value
        $sessionManager = $this->getPrivateProperty($this->tableBuilder, 'sessionManager');
        $sessionManager->save(['display_limit' => 100]);
        
        // getDisplayLimit should return session value, not property value
        $this->assertEquals(100, $this->tableBuilder->getDisplayLimit());
        $this->assertEquals(10, $this->getPrivateProperty($this->tableBuilder, 'displayLimit'));
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