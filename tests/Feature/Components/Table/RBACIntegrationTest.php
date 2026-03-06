<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

/**
 * Feature Test for RBAC Integration.
 * 
 * Tests that TableBuilder properly integrates with RBAC system:
 * - Row filtering based on permissions
 * - Column hiding based on permissions
 * - Action disabling based on permissions
 * 
 * Validates: Requirements 42.1-42.7
 */
class RBACIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->table = app(TableBuilder::class);
        $this->createTestTables();
        $this->seedTestData();
    }

    /**
     * Create test tables for RBAC testing.
     */
    protected function createTestTables(): void
    {
        $this->schema->create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role'); // 'admin', 'editor', 'viewer'
            $table->string('department'); // 'sales', 'marketing', 'it'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Seed test data with different roles and departments.
     */
    protected function seedTestData(): void
    {
        // Admin users
        $this->createUser('Admin User 1', 'admin1@test.com', 'admin', 'it');
        $this->createUser('Admin User 2', 'admin2@test.com', 'admin', 'sales');
        
        // Editor users
        $this->createUser('Editor User 1', 'editor1@test.com', 'editor', 'sales');
        $this->createUser('Editor User 2', 'editor2@test.com', 'editor', 'marketing');
        
        // Viewer users
        $this->createUser('Viewer User 1', 'viewer1@test.com', 'viewer', 'sales');
        $this->createUser('Viewer User 2', 'viewer2@test.com', 'viewer', 'it');
        $this->createUser('Viewer User 3', 'viewer3@test.com', 'viewer', 'marketing');
    }

    /**
     * Helper to create test user.
     */
    protected function createUser(string $name, string $email, string $role, string $department): void
    {
        $this->db->table('test_users')->insert([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'department' => $department,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test 6.2.7.1: Test row filtering based on permissions.
     * 
     * Validates: Requirements 42.1, 42.2
     */
    public function test_row_filtering_based_on_permissions(): void
    {
        // Arrange: Setup table with permission-based row filtering
        $this->table->setContext('admin');
        $this->table->setModel($this->createTestModel('test_users'));
        $this->table->setFields(['name:Name', 'email:Email', 'role:Role', 'department:Department']);
        
        // Set permission filter: only show users from 'sales' department
        $this->table->setPermission('view', function ($query, $user) {
            return $query->where('department', 'sales');
        });
        
        $this->table->format();
        
        // Act: Render table
        $html = $this->table->render();
        
        // Assert: Only sales department users should be visible
        $this->assertStringContainsString('Editor User 1', $html, 'Sales editor should be visible');
        $this->assertStringContainsString('Viewer User 1', $html, 'Sales viewer should be visible');
        $this->assertStringContainsString('Admin User 2', $html, 'Sales admin should be visible');
        
        // Users from other departments should NOT be visible
        $this->assertStringNotContainsString('Admin User 1', $html, 'IT admin should not be visible');
        $this->assertStringNotContainsString('Editor User 2', $html, 'Marketing editor should not be visible');
        $this->assertStringNotContainsString('Viewer User 2', $html, 'IT viewer should not be visible');
        $this->assertStringNotContainsString('Viewer User 3', $html, 'Marketing viewer should not be visible');
    }

    /**
     * Test row filtering with role-based permissions.
     * 
     * Validates: Requirements 42.1, 42.2
     */
    public function test_row_filtering_with_role_based_permissions(): void
    {
        // Arrange: Setup table with role-based filtering
        $this->table->setContext('admin');
        $this->table->setModel($this->createTestModel('test_users'));
        $this->table->setFields(['name:Name', 'email:Email', 'role:Role']);
        
        // Set permission filter: only show admin and editor roles
        $this->table->setPermission('view', function ($query, $user) {
            return $query->whereIn('role', ['admin', 'editor']);
        });
        
        $this->table->format();
        
        // Act: Render table
        $html = $this->table->render();
        
        // Assert: Only admin and editor users should be visible
        $this->assertStringContainsString('Admin User 1', $html);
        $this->assertStringContainsString('Admin User 2', $html);
        $this->assertStringContainsString('Editor User 1', $html);
        $this->assertStringContainsString('Editor User 2', $html);
        
        // Viewer users should NOT be visible
        $this->assertStringNotContainsString('Viewer User 1', $html);
        $this->assertStringNotContainsString('Viewer User 2', $html);
        $this->assertStringNotContainsString('Viewer User 3', $html);
    }

    /**
     * Test 6.2.7.2: Test column hiding based on permissions.
     * 
     * Validates: Requirements 42.3, 42.4
     */
    public function test_column_hiding_based_on_permissions(): void
    {
        // Arrange: Setup table with all columns
        $this->table->setContext('admin');
        $this->table->setModel($this->createTestModel('test_users'));
        $this->table->setFields([
            'name:Name',
            'email:Email',
            'role:Role',
            'department:Department',
            'is_active:Status'
        ]);
        
        // Hide sensitive columns based on permission
        $this->table->setColumnPermission('email', 'view_email');
        $this->table->setColumnPermission('role', 'view_role');
        
        // Simulate user without 'view_email' permission
        Gate::define('view_email', fn() => false);
        Gate::define('view_role', fn() => true);
        
        $this->table->format();
        
        // Act: Render table
        $html = $this->table->render();
        
        // Assert: Email column should be hidden
        $this->assertStringNotContainsString('Email', $html, 'Email column header should be hidden');
        $this->assertStringNotContainsString('@test.com', $html, 'Email values should be hidden');
        
        // Role column should be visible
        $this->assertStringContainsString('Role', $html, 'Role column header should be visible');
        $this->assertStringContainsString('admin', $html, 'Role values should be visible');
        
        // Other columns should be visible
        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('Department', $html);
        $this->assertStringContainsString('Status', $html);
    }

    /**
     * Test column hiding with multiple permission checks.
     * 
     * Validates: Requirements 42.3, 42.4
     */
    public function test_column_hiding_with_multiple_permissions(): void
    {
        // Arrange: Setup table
        $this->table->setContext('admin');
        $this->table->setModel($this->createTestModel('test_users'));
        $this->table->setFields([
            'name:Name',
            'email:Email',
            'role:Role',
            'department:Department'
        ]);
        
        // Set permissions for multiple columns
        $this->table->setColumnPermission('email', 'view_email');
        $this->table->setColumnPermission('role', 'view_role');
        $this->table->setColumnPermission('department', 'view_department');
        
        // Simulate user with limited permissions
        Gate::define('view_email', fn() => false);
        Gate::define('view_role', fn() => false);
        Gate::define('view_department', fn() => true);
        
        $this->table->format();
        
        // Act: Render table
        $html = $this->table->render();
        
        // Assert: Only name and department should be visible
        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('Department', $html);
        
        $this->assertStringNotContainsString('Email', $html);
        $this->assertStringNotContainsString('Role', $html);
    }

    /**
     * Test 6.2.7.3: Test action disabling based on permissions.
     * 
     * Validates: Requirements 42.5, 42.6
     */
    public function test_action_disabling_based_on_permissions(): void
    {
        // Arrange: Setup table with actions
        $this->table->setContext('admin');
        $this->table->setModel($this->createTestModel('test_users'));
        $this->table->setFields(['name:Name', 'email:Email', 'role:Role']);
        
        // Add actions with permissions
        $this->table->addAction('view', '/users/:id', 'eye', 'View', null, 'view_user');
        $this->table->addAction('edit', '/users/:id/edit', 'edit', 'Edit', null, 'edit_user');
        $this->table->addAction('delete', '/users/:id', 'trash', 'Delete', 'DELETE', 'delete_user');
        
        // Simulate user with limited permissions (can view and edit, but not delete)
        Gate::define('view_user', fn() => true);
        Gate::define('edit_user', fn() => true);
        Gate::define('delete_user', fn() => false);
        
        $this->table->format();
        
        // Act: Render table
        $html = $this->table->render();
        
        // Assert: View and Edit actions should be visible
        $this->assertStringContainsString('View', $html, 'View action should be visible');
        $this->assertStringContainsString('Edit', $html, 'Edit action should be visible');
        
        // Delete action should NOT be visible
        $this->assertStringNotContainsString('Delete', $html, 'Delete action should be hidden');
        $this->assertStringNotContainsString('trash', $html, 'Delete icon should be hidden');
    }

    /**
     * Test action disabling with conditional permissions.
     * 
     * Validates: Requirements 42.5, 42.6
     */
    public function test_action_disabling_with_conditional_permissions(): void
    {
        // Arrange: Setup table with conditional actions
        $this->table->setContext('admin');
        $this->table->setModel($this->createTestModel('test_users'));
        $this->table->setFields(['name:Name', 'role:Role']);
        
        // Add actions with row-level permissions
        $this->table->addAction('edit', '/users/:id/edit', 'edit', 'Edit', null, function ($row) {
            // Only allow editing non-admin users
            return $row->role !== 'admin';
        });
        
        $this->table->addAction('delete', '/users/:id', 'trash', 'Delete', 'DELETE', function ($row) {
            // Only allow deleting viewer users
            return $row->role === 'viewer';
        });
        
        $this->table->format();
        
        // Act: Render table
        $html = $this->table->render();
        
        // Assert: Actions should be conditionally visible based on row data
        // This is a simplified check - actual implementation would need to verify per-row
        $this->assertStringContainsString('Edit', $html, 'Edit action should be visible for some rows');
        $this->assertStringContainsString('Delete', $html, 'Delete action should be visible for viewer rows');
    }

    /**
     * Test permission caching for performance.
     * 
     * Validates: Requirements 42.7
     */
    public function test_permission_caching(): void
    {
        // Arrange: Setup table with permission checks
        $this->table->setContext('admin');
        $this->table->setModel($this->createTestModel('test_users'));
        $this->table->setFields(['name:Name', 'email:Email']);
        
        $permissionCheckCount = 0;
        
        // Set permission with counter
        $this->table->setPermission('view', function ($query, $user) use (&$permissionCheckCount) {
            $permissionCheckCount++;
            return $query;
        });
        
        $this->table->format();
        
        // Act: Render table multiple times
        $this->table->render();
        $this->table->render();
        $this->table->render();
        
        // Assert: Permission should be checked only once (cached)
        $this->assertEquals(1, $permissionCheckCount, 'Permission check should be cached');
    }

    /**
     * Test context-aware permissions.
     * 
     * Validates: Requirements 42.7
     */
    public function test_context_aware_permissions(): void
    {
        // Arrange: Setup table with context-aware permissions
        $this->table->setContext('admin');
        $this->table->setModel($this->createTestModel('test_users'));
        $this->table->setFields(['name:Name', 'role:Role']);
        
        // Set different permissions for different contexts
        $this->table->setPermission('view', function ($query, $user, $context) {
            if ($context === 'admin') {
                // Admin context: show all users
                return $query;
            } else {
                // Public context: show only active users
                return $query->where('is_active', true);
            }
        });
        
        $this->table->format();
        
        // Act: Render table in admin context
        $html = $this->table->render();
        
        // Assert: All users should be visible in admin context
        $this->assertStringContainsString('Admin User 1', $html);
        $this->assertStringContainsString('Editor User 1', $html);
        $this->assertStringContainsString('Viewer User 1', $html);
    }

    /**
     * Test RBAC integration with both engines (DataTables and TanStack).
     * 
     * Validates: Requirements 42.1-42.7
     */
    public function test_rbac_works_with_both_engines(): void
    {
        // Test with DataTables engine
        config(['canvastack-table.engine' => 'datatables']);
        $this->table = app(TableBuilder::class);
        $this->table->setContext('admin');
        $this->table->setModel($this->createTestModel('test_users'));
        $this->table->setFields(['name:Name', 'role:Role']);
        $this->table->setPermission('view', function ($query) {
            return $query->where('role', 'admin');
        });
        $this->table->format();
        
        $datatablesHtml = $this->table->render();
        $this->assertStringContainsString('Admin User 1', $datatablesHtml);
        $this->assertStringNotContainsString('Viewer User 1', $datatablesHtml);
        
        // Test with TanStack engine
        config(['canvastack-table.engine' => 'tanstack']);
        $this->table = app(TableBuilder::class);
        $this->table->setContext('admin');
        $this->table->setModel($this->createTestModel('test_users'));
        $this->table->setFields(['name:Name', 'role:Role']);
        $this->table->setPermission('view', function ($query) {
            return $query->where('role', 'admin');
        });
        $this->table->format();
        
        $tanstackHtml = $this->table->render();
        $this->assertStringContainsString('Admin User 1', $tanstackHtml);
        $this->assertStringNotContainsString('Viewer User 1', $tanstackHtml);
    }

    /**
     * Test RBAC with server-side processing.
     * 
     * Validates: Requirements 42.1, 42.2
     */
    public function test_rbac_with_server_side_processing(): void
    {
        // Arrange: Setup table with server-side processing
        $this->table->setContext('admin');
        $this->table->setModel($this->createTestModel('test_users'));
        $this->table->setFields(['name:Name', 'email:Email', 'role:Role']);
        $this->table->serverSide(true);
        
        // Set permission filter
        $this->table->setPermission('view', function ($query) {
            return $query->where('department', 'sales');
        });
        
        $this->table->format();
        
        // Act: Process server-side request
        $request = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];
        
        $response = $this->table->processServerSide($request);
        
        // Assert: Only sales department users should be returned
        $this->assertCount(3, $response['data'], 'Should return 3 sales users');
        
        $names = array_column($response['data'], 'name');
        $this->assertContains('Admin User 2', $names);
        $this->assertContains('Editor User 1', $names);
        $this->assertContains('Viewer User 1', $names);
        
        $this->assertNotContains('Admin User 1', $names);
        $this->assertNotContains('Viewer User 2', $names);
    }

    /**
     * Helper to create test model instance.
     */
    protected function createTestModel(string $table): object
    {
        return new class($table) {
            protected string $table;
            
            public function __construct(string $table)
            {
                $this->table = $table;
            }
            
            public function getTable(): string
            {
                return $this->table;
            }
        };
    }
}
