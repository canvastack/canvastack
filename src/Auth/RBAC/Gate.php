<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\RBAC;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Gate.
 *
 * Provides a unified interface for authorization checks.
 * Integrates RoleManager, PermissionManager, and PolicyManager for comprehensive access control.
 */
class Gate
{
    /**
     * Role manager instance.
     *
     * @var RoleManager
     */
    protected RoleManager $roleManager;

    /**
     * Permission manager instance.
     *
     * @var PermissionManager
     */
    protected PermissionManager $permissionManager;

    /**
     * Policy manager instance.
     *
     * @var PolicyManager
     */
    protected PolicyManager $policyManager;

    /**
     * Permission rule manager instance.
     *
     * @var PermissionRuleManager
     */
    protected PermissionRuleManager $ruleManager;

    /**
     * Authorization configuration.
     *
     * @var array<string, mixed>
     */
    protected array $authConfig;

    /**
     * Current context (admin, public, api).
     *
     * @var string|null
     */
    protected ?string $context = null;

    /**
     * Create a new Gate instance.
     */
    public function __construct(
        RoleManager $roleManager,
        PermissionManager $permissionManager,
        PolicyManager $policyManager,
        PermissionRuleManager $ruleManager
    ) {
        $this->roleManager = $roleManager;
        $this->permissionManager = $permissionManager;
        $this->policyManager = $policyManager;
        $this->ruleManager = $ruleManager;
        $this->authConfig = config('canvastack-rbac.authorization', []);
    }

    /**
     * Set the current context.
     *
     * @param string $context Context name (admin, public, api)
     * @return self
     */
    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get the current context.
     *
     * @return string|null
     */
    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * Check if user can perform an ability.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param string $ability Ability name
     * @param mixed $arguments Arguments to pass to the policy
     * @return bool
     */
    public function allows(?Authenticatable $user, string $ability, mixed $arguments = null): bool
    {
        // Don't call auth() if user is explicitly null
        if ($user === null && func_num_args() >= 1) {
            return false;
        }

        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Try policy check first
        if ($this->policyManager->hasAbility($ability)) {
            return $this->policyManager->can($ability, $arguments, $this->context);
        }

        // Fallback to permission check
        return $this->permissionManager->userHasPermission($user->id, $ability);
    }

    /**
     * Check if user cannot perform an ability.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param string $ability Ability name
     * @param mixed $arguments Arguments to pass to the policy
     * @return bool
     */
    public function denies(?Authenticatable $user, string $ability, mixed $arguments = null): bool
    {
        return !$this->allows($user, $ability, $arguments);
    }

    /**
     * Authorize an ability or throw an exception.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param string $ability Ability name
     * @param mixed $arguments Arguments to pass to the policy
     * @param string|null $message Custom error message
     * @return void
     * @throws AuthorizationException
     */
    public function authorize(
        ?Authenticatable $user,
        string $ability,
        mixed $arguments = null,
        ?string $message = null
    ): void {
        if (!$this->allows($user, $ability, $arguments)) {
            throw new AuthorizationException(
                $message ?? "You are not authorized to {$ability}"
            );
        }
    }

    /**
     * Check if user has a role.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param int|string $role Role ID or name
     * @return bool
     */
    public function hasRole(?Authenticatable $user, int|string $role): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        return $this->roleManager->userHasRole($user->id, $role);
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param array<int|string> $roles Role IDs or names
     * @return bool
     */
    public function hasAnyRole(?Authenticatable $user, array $roles): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        foreach ($roles as $role) {
            if ($this->roleManager->userHasRole($user->id, $role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given roles.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param array<int|string> $roles Role IDs or names
     * @return bool
     */
    public function hasAllRoles(?Authenticatable $user, array $roles): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        foreach ($roles as $role) {
            if (!$this->roleManager->userHasRole($user->id, $role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has a permission.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param int|string $permission Permission ID or name
     * @return bool
     */
    public function hasPermission(?Authenticatable $user, int|string $permission): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->permissionManager->userHasPermission($user->id, $permission);
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param array<int|string> $permissions Permission IDs or names
     * @return bool
     */
    public function hasAnyPermission(?Authenticatable $user, array $permissions): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->permissionManager->userHasAnyPermission($user->id, $permissions);
    }

    /**
     * Check if user has all of the given permissions.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param array<int|string> $permissions Permission IDs or names
     * @return bool
     */
    public function hasAllPermissions(?Authenticatable $user, array $permissions): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->permissionManager->userHasAllPermissions($user->id, $permissions);
    }

    /**
     * Check if user is super admin.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @return bool
     */
    public function isSuperAdmin(?Authenticatable $user): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // Read config directly to support runtime changes in tests
        $authConfig = config('canvastack-rbac.authorization', []);

        if (!($authConfig['super_admin_bypass'] ?? false)) {
            return false;
        }

        $superAdminRole = $authConfig['super_admin_role'] ?? 'super_admin';

        return $this->roleManager->userHasRole($user->id, $superAdminRole);
    }

    /**
     * Check if user has higher role level than another user.
     *
     * @param Authenticatable $user User instance
     * @param Authenticatable $targetUser Target user instance
     * @return bool
     */
    public function hasHigherLevel(Authenticatable $user, Authenticatable $targetUser): bool
    {
        $userRoles = $this->roleManager->getUserRoles($user->id);
        $targetRoles = $this->roleManager->getUserRoles($targetUser->id);

        if ($userRoles->isEmpty() || $targetRoles->isEmpty()) {
            return false;
        }

        // Get lowest level (highest privilege) for each user
        $userLevel = $userRoles->min('level');
        $targetLevel = $targetRoles->min('level');

        // Lower level number = higher privilege
        return $userLevel < $targetLevel;
    }

    /**
     * Check if user can perform an ability in a specific context.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param string $ability Ability name
     * @param string $context Context name (admin, public, api)
     * @param mixed $arguments Arguments to pass to the policy
     * @return bool
     */
    public function allowsInContext(
        ?Authenticatable $user,
        string $ability,
        string $context,
        mixed $arguments = null
    ): bool {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check if context is enabled
        $contextConfig = config("canvastack-rbac.contexts.{$context}");

        if (!($contextConfig['enabled'] ?? false)) {
            return false;
        }

        // Build context-aware ability name
        $contextAbility = "{$context}.{$ability}";

        // Try policy check first
        if ($this->policyManager->hasAbility($contextAbility)) {
            return $this->policyManager->can($contextAbility, $arguments, $context);
        }

        // Fallback to permission check
        return $this->permissionManager->userHasPermission($user->id, $contextAbility);
    }

    /**
     * Check if user can perform any of the given abilities.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param array<string> $abilities Ability names
     * @param mixed $arguments Arguments to pass to the policy
     * @return bool
     */
    public function allowsAny(?Authenticatable $user, array $abilities, mixed $arguments = null): bool
    {
        foreach ($abilities as $ability) {
            if ($this->allows($user, $ability, $arguments)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can perform all of the given abilities.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param array<string> $abilities Ability names
     * @param mixed $arguments Arguments to pass to the policy
     * @return bool
     */
    public function allowsAll(?Authenticatable $user, array $abilities, mixed $arguments = null): bool
    {
        foreach ($abilities as $ability) {
            if (!$this->allows($user, $ability, $arguments)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get user's roles.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRoles(?Authenticatable $user): \Illuminate\Database\Eloquent\Collection
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return $this->roleManager->getUserRoles($user->id);
    }

    /**
     * Get user's permissions.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPermissions(?Authenticatable $user): \Illuminate\Database\Eloquent\Collection
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return $this->permissionManager->getUserPermissions($user->id);
    }

    /**
     * Get user's abilities.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @return array<string>
     */
    public function getAbilities(?Authenticatable $user): array
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return [];
        }

        return $this->policyManager->getUserAbilities($user);
    }

    /**
     * Check authorization for a resource action.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param string $resource Resource name (e.g., 'users', 'posts')
     * @param string $action Action name (e.g., 'view', 'create', 'update', 'delete')
     * @param mixed $model Model instance (optional)
     * @return bool
     */
    public function allowsResource(
        ?Authenticatable $user,
        string $resource,
        string $action,
        mixed $model = null
    ): bool {
        $ability = "{$resource}.{$action}";

        return $this->allows($user, $ability, $model);
    }

    /**
     * Authorize a resource action or throw an exception.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param string $resource Resource name (e.g., 'users', 'posts')
     * @param string $action Action name (e.g., 'view', 'create', 'update', 'delete')
     * @param mixed $model Model instance (optional)
     * @param string|null $message Custom error message
     * @return void
     * @throws AuthorizationException
     */
    public function authorizeResource(
        ?Authenticatable $user,
        string $resource,
        string $action,
        mixed $model = null,
        ?string $message = null
    ): void {
        if (!$this->allowsResource($user, $resource, $action, $model)) {
            throw new AuthorizationException(
                $message ?? "You are not authorized to {$action} {$resource}"
            );
        }
    }

    /**
     * Get the role manager instance.
     *
     * @return RoleManager
     */
    public function roles(): RoleManager
    {
        return $this->roleManager;
    }

    /**
     * Get the permission manager instance.
     *
     * @return PermissionManager
     */
    public function permissions(): PermissionManager
    {
        return $this->permissionManager;
    }

    /**
     * Get the policy manager instance.
     *
     * @return PolicyManager
     */
    public function policies(): PolicyManager
    {
        return $this->policyManager;
    }

    /**
     * Create a fluent authorization check.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @return GateCheck
     */
    public function forUser(?Authenticatable $user): GateCheck
    {
        return new GateCheck($this, $user ?? auth()->user());
    }

    /**
     * Check if context is enabled.
     *
     * @param string $context Context name
     * @return bool
     */
    public function isContextEnabled(string $context): bool
    {
        $contextConfig = config("canvastack-rbac.contexts.{$context}");

        return $contextConfig['enabled'] ?? false;
    }

    /**
     * Get enabled contexts.
     *
     * @return array<string>
     */
    public function getEnabledContexts(): array
    {
        $contexts = config('canvastack-rbac.contexts', []);
        $enabled = [];

        foreach ($contexts as $name => $config) {
            if ($config['enabled'] ?? false) {
                $enabled[] = $name;
            }
        }

        return $enabled;
    }

    /**
     * Check if user can access specific row.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param string $permission Permission name
     * @param object $model Model instance
     * @return bool
     */
    public function canAccessRow(?Authenticatable $user, string $permission, object $model): bool
    {
        // Only try to get current user if user is not explicitly provided
        if ($user === null && func_num_args() >= 1) {
            // User was explicitly passed as null, don't try auth()
            $this->logDenial(null, $permission, 'row_level_denied', [
                'reason' => 'user_not_authenticated',
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
            ]);

            return false;
        }

        if (!$user) {
            $this->logDenial(null, $permission, 'row_level_denied', [
                'reason' => 'user_not_authenticated',
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
            ]);

            return false;
        }

        // Check basic permission first
        if (!$this->hasPermission($user, $permission)) {
            $this->logDenial($user->id, $permission, 'basic_permission_denied', [
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
            ]);

            return false;
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check row-level rules
        $canAccess = $this->ruleManager->canAccessRow($user->id, $permission, $model);

        if (!$canAccess) {
            $this->logDenial($user->id, $permission, 'row_level_denied', [
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
            ]);
        }

        return $canAccess;
    }

    /**
     * Check if user can access specific column.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param string $permission Permission name
     * @param object $model Model instance
     * @param string $column Column name
     * @return bool
     */
    public function canAccessColumn(
        ?Authenticatable $user,
        string $permission,
        object $model,
        string $column
    ): bool {
        // Only try to get current user if user is not explicitly provided
        if ($user === null && func_num_args() >= 1) {
            // User was explicitly passed as null, don't try auth()
            $this->logDenial(null, $permission, 'column_level_denied', [
                'reason' => 'user_not_authenticated',
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
                'column' => $column,
            ]);

            return false;
        }

        if (!$user) {
            $this->logDenial(null, $permission, 'column_level_denied', [
                'reason' => 'user_not_authenticated',
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
                'column' => $column,
            ]);

            return false;
        }

        // Check basic permission first
        if (!$this->hasPermission($user, $permission)) {
            $this->logDenial($user->id, $permission, 'basic_permission_denied', [
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
                'column' => $column,
            ]);

            return false;
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check column-level rules
        $canAccess = $this->ruleManager->canAccessColumn($user->id, $permission, $model, $column);

        if (!$canAccess) {
            $this->logDenial($user->id, $permission, 'column_level_denied', [
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
                'column' => $column,
            ]);
        }

        return $canAccess;
    }

    /**
     * Check if user can access JSON attribute.
     *
     * @param Authenticatable|null $user User instance (null = current user)
     * @param string $permission Permission name
     * @param object $model Model instance
     * @param string $jsonColumn JSON column name
     * @param string $path JSON path (dot notation)
     * @return bool
     */
    public function canAccessJsonAttribute(
        ?Authenticatable $user,
        string $permission,
        object $model,
        string $jsonColumn,
        string $path
    ): bool {
        // Only try to get current user if user is not explicitly provided
        if ($user === null && func_num_args() >= 1) {
            // User was explicitly passed as null, don't try auth()
            $this->logDenial(null, $permission, 'json_attribute_denied', [
                'reason' => 'user_not_authenticated',
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
                'json_column' => $jsonColumn,
                'path' => $path,
            ]);

            return false;
        }

        if (!$user) {
            $this->logDenial(null, $permission, 'json_attribute_denied', [
                'reason' => 'user_not_authenticated',
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
                'json_column' => $jsonColumn,
                'path' => $path,
            ]);

            return false;
        }

        // Check basic permission first
        if (!$this->hasPermission($user, $permission)) {
            $this->logDenial($user->id, $permission, 'basic_permission_denied', [
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
                'json_column' => $jsonColumn,
                'path' => $path,
            ]);

            return false;
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check JSON attribute rules
        $canAccess = $this->ruleManager->canAccessJsonAttribute(
            $user->id,
            $permission,
            $model,
            $jsonColumn,
            $path
        );

        if (!$canAccess) {
            $this->logDenial($user->id, $permission, 'json_attribute_denied', [
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
                'json_column' => $jsonColumn,
                'path' => $path,
            ]);
        }

        return $canAccess;
    }

    /**
     * Log permission denial for audit.
     *
     * @param int|null $userId User ID
     * @param string $permission Permission name
     * @param string $reason Denial reason
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    protected function logDenial(?int $userId, string $permission, string $reason, array $context = []): void
    {
        \Illuminate\Support\Facades\Log::warning('Permission denied', [
            'user_id' => $userId,
            'permission' => $permission,
            'reason' => $reason,
            'context' => $context,
            'timestamp' => now(),
        ]);
    }
}

/**
 * Fluent Gate Check.
 *
 * Provides a fluent interface for authorization checks.
 */
class GateCheck
{
    /**
     * Gate instance.
     *
     * @var Gate
     */
    protected Gate $gate;

    /**
     * User instance.
     *
     * @var Authenticatable|null
     */
    protected ?Authenticatable $user;

    /**
     * Create a new GateCheck instance.
     */
    public function __construct(Gate $gate, ?Authenticatable $user)
    {
        $this->gate = $gate;
        $this->user = $user;
    }

    /**
     * Check if user can perform an ability.
     *
     * @param string $ability Ability name
     * @param mixed $arguments Arguments to pass to the policy
     * @return bool
     */
    public function can(string $ability, mixed $arguments = null): bool
    {
        return $this->gate->allows($this->user, $ability, $arguments);
    }

    /**
     * Check if user cannot perform an ability.
     *
     * @param string $ability Ability name
     * @param mixed $arguments Arguments to pass to the policy
     * @return bool
     */
    public function cannot(string $ability, mixed $arguments = null): bool
    {
        return $this->gate->denies($this->user, $ability, $arguments);
    }

    /**
     * Check if user has a role.
     *
     * @param int|string $role Role ID or name
     * @return bool
     */
    public function hasRole(int|string $role): bool
    {
        return $this->gate->hasRole($this->user, $role);
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param array<int|string> $roles Role IDs or names
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->gate->hasAnyRole($this->user, $roles);
    }

    /**
     * Check if user has all of the given roles.
     *
     * @param array<int|string> $roles Role IDs or names
     * @return bool
     */
    public function hasAllRoles(array $roles): bool
    {
        return $this->gate->hasAllRoles($this->user, $roles);
    }

    /**
     * Check if user has a permission.
     *
     * @param int|string $permission Permission ID or name
     * @return bool
     */
    public function hasPermission(int|string $permission): bool
    {
        return $this->gate->hasPermission($this->user, $permission);
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param array<int|string> $permissions Permission IDs or names
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->gate->hasAnyPermission($this->user, $permissions);
    }

    /**
     * Check if user has all of the given permissions.
     *
     * @param array<int|string> $permissions Permission IDs or names
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return $this->gate->hasAllPermissions($this->user, $permissions);
    }

    /**
     * Check if user is super admin.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->gate->isSuperAdmin($this->user);
    }
}
