<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\Filter\FilterManager;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * Test FilterManager integration with TableBuilder.
 */
class FilterManagerIntegrationTest extends TestCase
{
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = $this->app->make(TableBuilder::class);

        // Create test table
        $this->createTestTable();
    }

    protected function createTestTable(): void
    {
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();

        if (!$schema->hasTable('test_users')) {
            $schema->create('test_users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('status');
                $table->string('category');
                $table->date('created_date');
                $table->timestamps();
            });
        }

        // Insert test data
        $capsule->table('test_users')->insert([
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'status' => 'active',
                'category' => 'premium',
                'created_date' => '2024-01-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'status' => 'inactive',
                'category' => 'basic',
                'created_date' => '2024-01-15',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'status' => 'active',
                'category' => 'premium',
                'created_date' => '2024-02-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
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
     * Test that TableBuilder has FilterManager property.
     */
    public function test_table_builder_has_filter_manager(): void
    {
        $filterManager = $this->table->getFilterManager();

        $this->assertInstanceOf(FilterManager::class, $filterManager);
    }

    /**
     * Test that filterGroups() adds filter to FilterManager.
     */
    public function test_filter_groups_adds_filter_to_manager(): void
    {
        $this->table->setName('test_users');
        $this->table->filterGroups('status', 'selectbox');

        $filterManager = $this->table->getFilterManager();
        $filters = $filterManager->getFilters();

        $this->assertCount(1, $filters);
        $this->assertArrayHasKey('status', $filters);
        $this->assertEquals('selectbox', $filters['status']->getType());
    }

    /**
     * Test that multiple filterGroups() calls add multiple filters.
     */
    public function test_multiple_filter_groups_add_multiple_filters(): void
    {
        $this->table->setName('test_users');
        $this->table->filterGroups('status', 'selectbox');
        $this->table->filterGroups('category', 'selectbox');
        $this->table->filterGroups('name', 'inputbox');

        $filterManager = $this->table->getFilterManager();
        $filters = $filterManager->getFilters();

        $this->assertCount(3, $filters);
        $this->assertArrayHasKey('status', $filters);
        $this->assertArrayHasKey('category', $filters);
        $this->assertArrayHasKey('name', $filters);
    }

    /**
     * Test that setActiveFilters() sets filter values.
     */
    public function test_set_active_filters_sets_values(): void
    {
        $this->table->setName('test_users');
        $this->table->filterGroups('status', 'selectbox');
        $this->table->filterGroups('category', 'selectbox');

        $this->table->setActiveFilters([
            'status' => 'active',
            'category' => 'premium',
        ], false); // Don't save to session in test

        $activeFilters = $this->table->getActiveFilters();

        $this->assertEquals('active', $activeFilters['status']);
        $this->assertEquals('premium', $activeFilters['category']);
    }

    /**
     * Test that active filters are applied to query.
     */
    public function test_active_filters_applied_to_query(): void
    {
        // Create test model
        $model = new class extends Model
        {
            protected $table = 'test_users';
            public $timestamps = true;
        };

        $this->table->setName('test_users');
        $this->table->setModel($model);
        $this->table->setFields(['name:Name', 'email:Email', 'status:Status']);

        // Add filters
        $this->table->filterGroups('status', 'selectbox');
        $this->table->setActiveFilters(['status' => 'active'], false);

        // Get data
        $data = $this->table->getData();

        // Should only return active users
        $this->assertCount(2, $data['data']);
        foreach ($data['data'] as $row) {
            $this->assertEquals('active', $row['status']);
        }
    }

    /**
     * Test that inputbox filter uses LIKE search.
     */
    public function test_inputbox_filter_uses_like_search(): void
    {
        $model = new class extends Model
        {
            protected $table = 'test_users';
            public $timestamps = true;
        };

        $this->table->setName('test_users');
        $this->table->setModel($model);
        $this->table->setFields(['name:Name', 'email:Email']);

        // Add inputbox filter
        $this->table->filterGroups('name', 'inputbox');
        $this->table->setActiveFilters(['name' => 'John'], false);

        // Get data
        $data = $this->table->getData();

        // Should return users with 'John' in name
        $this->assertCount(2, $data['data']); // John Doe and Bob Johnson
    }

    /**
     * Test that datebox filter filters by date.
     */
    public function test_datebox_filter_filters_by_date(): void
    {
        $model = new class extends Model
        {
            protected $table = 'test_users';
            public $timestamps = true;
        };

        $this->table->setName('test_users');
        $this->table->setModel($model);
        $this->table->setFields(['name:Name', 'created_date:Created']);

        // Add datebox filter
        $this->table->filterGroups('created_date', 'datebox');
        $this->table->setActiveFilters(['created_date' => '2024-01-01'], false);

        // Get data
        $data = $this->table->getData();

        // Should return only user created on 2024-01-01
        $this->assertCount(1, $data['data']);
        $this->assertEquals('John Doe', $data['data'][0]['name']);
    }

    /**
     * Test that clearActiveFilters() clears filter values.
     */
    public function test_clear_active_filters_clears_values(): void
    {
        $this->table->setName('test_users');
        $this->table->filterGroups('status', 'selectbox');
        $this->table->setActiveFilters(['status' => 'active'], false);

        // Verify filter is set
        $this->assertEquals('active', $this->table->getActiveFilters()['status']);

        // Clear filters
        $this->table->clearActiveFilters(false);

        // Verify filters are cleared
        $this->assertEmpty($this->table->getActiveFilters());
    }

    /**
     * Test that multiple filters work together.
     */
    public function test_multiple_filters_work_together(): void
    {
        $model = new class extends Model
        {
            protected $table = 'test_users';
            public $timestamps = true;
        };

        $this->table->setName('test_users');
        $this->table->setModel($model);
        $this->table->setFields(['name:Name', 'status:Status', 'category:Category']);

        // Add multiple filters
        $this->table->filterGroups('status', 'selectbox');
        $this->table->filterGroups('category', 'selectbox');

        // Set both filters
        $this->table->setActiveFilters([
            'status' => 'active',
            'category' => 'premium',
        ], false);

        // Get data
        $data = $this->table->getData();

        // Should return only active premium users
        $this->assertCount(2, $data['data']);
        foreach ($data['data'] as $row) {
            $this->assertEquals('active', $row['status']);
            $this->assertEquals('premium', $row['category']);
        }
    }

    /**
     * Test that empty filter values are ignored.
     */
    public function test_empty_filter_values_ignored(): void
    {
        $model = new class extends Model
        {
            protected $table = 'test_users';
            public $timestamps = true;
        };

        $this->table->setName('test_users');
        $this->table->setModel($model);
        $this->table->setFields(['name:Name', 'status:Status']);

        // Add filter
        $this->table->filterGroups('status', 'selectbox');

        // Set empty filter value
        $this->table->setActiveFilters(['status' => ''], false);

        // Get data
        $data = $this->table->getData();

        // Should return all users (filter ignored)
        $this->assertCount(3, $data['data']);
    }

    /**
     * Test that sessionFilters() sets session key for FilterManager.
     */
    public function test_session_filters_sets_session_key(): void
    {
        $this->table->setName('test_users');
        $this->table->sessionFilters();

        $filterManager = $this->table->getFilterManager();
        $sessionKey = $filterManager->getSessionKey();

        $this->assertNotNull($sessionKey);
        $this->assertStringContainsString('table_filters_', $sessionKey);
    }

    /**
     * Test that filters can be saved to and loaded from session.
     */
    public function test_filters_saved_and_loaded_from_session(): void
    {
        $this->table->setName('test_users');
        $this->table->filterGroups('status', 'selectbox');
        $this->table->sessionFilters();

        // Set and save filters
        $this->table->setActiveFilters(['status' => 'active'], true);

        // Create new table instance
        $newTable = $this->app->make(TableBuilder::class);
        $newTable->setName('test_users');
        $newTable->filterGroups('status', 'selectbox');
        $newTable->sessionFilters();

        // Load filters from session
        $newTable->loadFiltersFromSession();

        // Verify filters were loaded
        $activeFilters = $newTable->getActiveFilters();
        $this->assertEquals('active', $activeFilters['status']);
    }

    /**
     * Test that getFilterManager() returns the same instance.
     */
    public function test_get_filter_manager_returns_same_instance(): void
    {
        $manager1 = $this->table->getFilterManager();
        $manager2 = $this->table->getFilterManager();

        $this->assertSame($manager1, $manager2);
    }
}
