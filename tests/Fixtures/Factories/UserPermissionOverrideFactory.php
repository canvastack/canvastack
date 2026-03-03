<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Fixtures\Factories;

use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\UserPermissionOverride;
use Canvastack\Canvastack\Tests\Fixtures\User;

/**
 * Factory for creating UserPermissionOverride test instances.
 */
class UserPermissionOverrideFactory
{
    /**
     * Create a user permission override with default values.
     *
     * @param array $attributes
     * @return \Canvastack\Canvastack\Models\UserPermissionOverride
     */
    public static function create(array $attributes = []): UserPermissionOverride
    {
        // Create user if not provided
        if (!isset($attributes['user_id'])) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'user' . uniqid() . '@example.com',
                'password' => 'password',
            ]);
            $attributes['user_id'] = $user->id;
        }

        // Create permission if not provided
        if (!isset($attributes['permission_id'])) {
            $permission = Permission::create([
                'name' => 'test.permission.' . uniqid(),
                'display_name' => 'Test Permission',
                'description' => 'Test permission description',
            ]);
            $attributes['permission_id'] = $permission->id;
        }

        // Set default values
        $defaults = [
            'model_type' => 'App\\Models\\Post',
            'model_id' => null,
            'field_name' => null,
            'rule_config' => null,
            'allowed' => true,
        ];

        $attributes = array_merge($defaults, $attributes);

        return UserPermissionOverride::create($attributes);
    }

    /**
     * Create a row-level override.
     *
     * @param int $userId
     * @param int $permissionId
     * @param string $modelType
     * @param int $modelId
     * @param bool $allowed
     * @return \Canvastack\Canvastack\Models\UserPermissionOverride
     */
    public static function createRowOverride(
        int $userId,
        int $permissionId,
        string $modelType,
        int $modelId,
        bool $allowed = true
    ): UserPermissionOverride {
        return self::create([
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'allowed' => $allowed,
        ]);
    }

    /**
     * Create a column-level override.
     *
     * @param int $userId
     * @param int $permissionId
     * @param string $modelType
     * @param string $fieldName
     * @param bool $allowed
     * @return \Canvastack\Canvastack\Models\UserPermissionOverride
     */
    public static function createColumnOverride(
        int $userId,
        int $permissionId,
        string $modelType,
        string $fieldName,
        bool $allowed = true
    ): UserPermissionOverride {
        return self::create([
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'model_type' => $modelType,
            'field_name' => $fieldName,
            'allowed' => $allowed,
        ]);
    }

    /**
     * Create a JSON attribute override.
     *
     * @param int $userId
     * @param int $permissionId
     * @param string $modelType
     * @param string $jsonColumn
     * @param string $path
     * @param bool $allowed
     * @return \Canvastack\Canvastack\Models\UserPermissionOverride
     */
    public static function createJsonAttributeOverride(
        int $userId,
        int $permissionId,
        string $modelType,
        string $jsonColumn,
        string $path,
        bool $allowed = true
    ): UserPermissionOverride {
        return self::create([
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'model_type' => $modelType,
            'field_name' => $jsonColumn,
            'rule_config' => ['path' => $path],
            'allowed' => $allowed,
        ]);
    }

    /**
     * Create a conditional override.
     *
     * @param int $userId
     * @param int $permissionId
     * @param string $modelType
     * @param string $condition
     * @param bool $allowed
     * @return \Canvastack\Canvastack\Models\UserPermissionOverride
     */
    public static function createConditionalOverride(
        int $userId,
        int $permissionId,
        string $modelType,
        string $condition,
        bool $allowed = true
    ): UserPermissionOverride {
        return self::create([
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'model_type' => $modelType,
            'rule_config' => ['condition' => $condition],
            'allowed' => $allowed,
        ]);
    }

    /**
     * Create multiple overrides.
     *
     * @param int $count
     * @param array $attributes
     * @return array
     */
    public static function createMany(int $count, array $attributes = []): array
    {
        $overrides = [];

        for ($i = 0; $i < $count; $i++) {
            $overrides[] = self::create($attributes);
        }

        return $overrides;
    }
}
