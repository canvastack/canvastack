<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Database\Migrations;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Test for user_permission_overrides table migration.
 */
class UserPermissionOverridesTableMigrationTest extends TestCase
{
    /**
     * Test that user_permission_overrides table exists.
     *
     * @return void
     */
    public function test_user_permission_overrides_table_exists(): void
    {
        $capsule = Capsule::connection();

        $this->assertTrue(
            $capsule->getSchemaBuilder()->hasTable('user_permission_overrides'),
            'user_permission_overrides table should exist'
        );
    }

    /**
     * Test that user_permission_overrides table has required columns.
     *
     * @return void
     */
    public function test_user_permission_overrides_table_has_required_columns(): void
    {
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();

        $columns = [
            'id',
            'user_id',
            'permission_id',
            'model_type',
            'model_id',
            'field_name',
            'rule_config',
            'allowed',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                $schema->hasColumn('user_permission_overrides', $column),
                "user_permission_overrides table should have {$column} column"
            );
        }
    }

    /**
     * Test that user_permission_overrides table has correct column types.
     *
     * @return void
     */
    public function test_user_permission_overrides_table_has_correct_column_types(): void
    {
        $capsule = Capsule::connection();
        $columns = $capsule->select('PRAGMA table_info(user_permission_overrides)');

        $columnTypes = [];
        $columnNullable = [];
        foreach ($columns as $column) {
            $columnTypes[$column->name] = strtolower($column->type);
            $columnNullable[$column->name] = $column->notnull == 0;
        }

        // Verify column types
        $this->assertStringContainsString('integer', $columnTypes['id']);
        $this->assertStringContainsString('integer', $columnTypes['user_id']);
        $this->assertStringContainsString('integer', $columnTypes['permission_id']);

        // Verify nullable columns
        $this->assertTrue(
            $columnNullable['model_id'],
            'model_id should be nullable'
        );
        $this->assertTrue(
            $columnNullable['field_name'],
            'field_name should be nullable'
        );
        $this->assertTrue(
            $columnNullable['rule_config'],
            'rule_config should be nullable'
        );
    }

    /**
     * Test that user_permission_overrides table has correct indexes.
     *
     * @return void
     */
    public function test_user_permission_overrides_table_has_indexes(): void
    {
        $indexes = $this->getTableIndexes('user_permission_overrides');

        // Check for composite index
        $this->assertContains(
            'user_perm_model_idx',
            $indexes,
            'user_permission_overrides table should have user_perm_model_idx index'
        );

        // Check for model_type index
        $hasModelTypeIndex = false;
        foreach ($indexes as $index) {
            if (str_contains($index, 'model_type')) {
                $hasModelTypeIndex = true;
                break;
            }
        }

        $this->assertTrue(
            $hasModelTypeIndex,
            'user_permission_overrides table should have index on model_type'
        );
    }

    /**
     * Test that user_permission_overrides table has foreign key constraints.
     *
     * @return void
     */
    public function test_user_permission_overrides_table_has_foreign_key_constraints(): void
    {
        $capsule = Capsule::connection();

        // Enable foreign keys for SQLite
        $capsule->statement('PRAGMA foreign_keys = ON');

        $foreignKeys = $capsule->select('PRAGMA foreign_key_list(user_permission_overrides)');

        $this->assertNotEmpty(
            $foreignKeys,
            'user_permission_overrides table should have foreign key constraints'
        );

        // Find foreign keys
        $userFk = null;
        $permissionFk = null;

        foreach ($foreignKeys as $fk) {
            if ($fk->from === 'user_id' && $fk->table === 'users') {
                $userFk = $fk;
            }
            if ($fk->from === 'permission_id' && $fk->table === 'permissions') {
                $permissionFk = $fk;
            }
        }

        $this->assertNotNull(
            $userFk,
            'user_permission_overrides table should have foreign key on user_id referencing users table'
        );

        $this->assertNotNull(
            $permissionFk,
            'user_permission_overrides table should have foreign key on permission_id referencing permissions table'
        );

        // Verify CASCADE delete on both
        $this->assertEquals(
            'CASCADE',
            strtoupper($userFk->on_delete),
            'user_id foreign key should have CASCADE on delete'
        );

        $this->assertEquals(
            'CASCADE',
            strtoupper($permissionFk->on_delete),
            'permission_id foreign key should have CASCADE on delete'
        );
    }

    /**
     * Test that foreign key constraint works for user_id (CASCADE delete).
     *
     * @return void
     */
    public function test_user_id_foreign_key_cascade_delete_works(): void
    {
        $capsule = Capsule::connection();

        // Enable foreign keys for SQLite
        $capsule->statement('PRAGMA foreign_keys = ON');

        // Create test user
        $userId = $capsule->table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test.override@example.com',
            'password' => 'password_hash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create test permission
        $permissionId = $capsule->table('permissions')->insertGetId([
            'name' => 'test.override.permission',
            'display_name' => 'Test Override Permission',
            'description' => 'Test permission for override test',
            'module' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create override
        $overrideId = $capsule->table('user_permission_overrides')->insertGetId([
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 1,
            'allowed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify override was created
        $override = $capsule->table('user_permission_overrides')->find($overrideId);
        $this->assertNotNull($override, 'Override should be created');

        // Delete the user
        $capsule->table('users')->where('id', $userId)->delete();

        // Verify override was cascade deleted
        $override = $capsule->table('user_permission_overrides')->find($overrideId);
        $this->assertNull($override, 'Override should be cascade deleted when user is deleted');

        // Clean up permission
        $capsule->table('permissions')->where('id', $permissionId)->delete();
    }

    /**
     * Test that foreign key constraint works for permission_id (CASCADE delete).
     *
     * @return void
     */
    public function test_permission_id_foreign_key_cascade_delete_works(): void
    {
        $capsule = Capsule::connection();

        // Enable foreign keys for SQLite
        $capsule->statement('PRAGMA foreign_keys = ON');

        // Create test user
        $userId = $capsule->table('users')->insertGetId([
            'name' => 'Test User 2',
            'email' => 'test.override2@example.com',
            'password' => 'password_hash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create test permission
        $permissionId = $capsule->table('permissions')->insertGetId([
            'name' => 'test.override2.permission',
            'display_name' => 'Test Override Permission 2',
            'description' => 'Test permission for override test 2',
            'module' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create override
        $overrideId = $capsule->table('user_permission_overrides')->insertGetId([
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 1,
            'allowed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify override was created
        $override = $capsule->table('user_permission_overrides')->find($overrideId);
        $this->assertNotNull($override, 'Override should be created');

        // Delete the permission
        $capsule->table('permissions')->where('id', $permissionId)->delete();

        // Verify override was cascade deleted
        $override = $capsule->table('user_permission_overrides')->find($overrideId);
        $this->assertNull($override, 'Override should be cascade deleted when permission is deleted');

        // Clean up user
        $capsule->table('users')->where('id', $userId)->delete();
    }

    /**
     * Test that allowed default value works.
     *
     * @return void
     */
    public function test_allowed_default_value(): void
    {
        $capsule = Capsule::connection();

        // Create test user
        $userId = $capsule->table('users')->insertGetId([
            'name' => 'Test User 3',
            'email' => 'test.override3@example.com',
            'password' => 'password_hash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create test permission
        $permissionId = $capsule->table('permissions')->insertGetId([
            'name' => 'test.override3.permission',
            'display_name' => 'Test Override Permission 3',
            'description' => 'Test permission for override test 3',
            'module' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert override without allowed field
        $overrideId = $capsule->table('user_permission_overrides')->insertGetId([
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'model_type' => 'App\\Models\\Post',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify default allowed is true
        $override = $capsule->table('user_permission_overrides')->find($overrideId);
        $this->assertEquals(
            1,
            $override->allowed,
            'allowed should default to true (1)'
        );

        // Clean up
        $capsule->table('users')->where('id', $userId)->delete();
        $capsule->table('permissions')->where('id', $permissionId)->delete();
    }

    /**
     * Test that nullable fields work correctly.
     *
     * @return void
     */
    public function test_nullable_fields_work(): void
    {
        $capsule = Capsule::connection();

        // Create test user
        $userId = $capsule->table('users')->insertGetId([
            'name' => 'Test User 4',
            'email' => 'test.override4@example.com',
            'password' => 'password_hash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create test permission
        $permissionId = $capsule->table('permissions')->insertGetId([
            'name' => 'test.override4.permission',
            'display_name' => 'Test Override Permission 4',
            'description' => 'Test permission for override test 4',
            'module' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert override with null values
        $overrideId = $capsule->table('user_permission_overrides')->insertGetId([
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'model_type' => 'App\\Models\\Post',
            'model_id' => null,
            'field_name' => null,
            'rule_config' => null,
            'allowed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify override was created with null values
        $override = $capsule->table('user_permission_overrides')->find($overrideId);
        $this->assertNotNull($override, 'Override should be created');
        $this->assertNull($override->model_id, 'model_id should be null');
        $this->assertNull($override->field_name, 'field_name should be null');
        $this->assertNull($override->rule_config, 'rule_config should be null');

        // Clean up
        $capsule->table('users')->where('id', $userId)->delete();
        $capsule->table('permissions')->where('id', $permissionId)->delete();
    }

    /**
     * Get table indexes.
     *
     * @param string $table
     * @return array
     */
    private function getTableIndexes(string $table): array
    {
        $indexes = [];
        $capsule = Capsule::connection();

        $results = $capsule->select("PRAGMA index_list({$table})");

        foreach ($results as $result) {
            $indexes[] = $result->name;
        }

        return $indexes;
    }
}
