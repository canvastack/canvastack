<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\RBAC;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Policy Manager.
 *
 * Manages policy-based authorization, policy registration, and context-aware checks.
 * Provides methods for defining, checking, and managing authorization policies.
 */
class PolicyManager
{
    /**
     * Policy configuration.
     *
     * @var array<string, mixed>
     */
    protected array $policyConfig;

    /**
     * Authorization configuration.
     *
     * @var array<string, mixed>
     */
    protected array $authConfig;

    /**
     * Registered policies.
     *
     * @var array<string, string>
     */
    protected array $policies = [];

    /**
     * Gate instance.
     *
     * @var GateContract
     */
    protected GateContract $gate;

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
     * Create a new PolicyManager instance.
     */
    public function __construct(
        RoleManager $roleManager,
        PermissionManager $permissionManager
    ) {
        $this->policyConfig = config('canvastack-rbac.policies', []);
        $this->authConfig = config('canvastack-rbac.authorization', []);
        $this->gate = Gate::getFacadeRoot();
        $this->roleManager = $roleManager;
        $this->permissionManager = $permissionManager;
    }

    /**
     * Register a policy for a model.
     *
     * @param string $model Model class name
     * @param string $policy Policy class name
     * @return void
     */
    public function register(string $model, string $policy): void
    {
        if (!class_exists($model)) {
            throw new InvalidArgumentException("Model class {$model} does not exist");
        }

        if (!class_exists($policy)) {
            throw new InvalidArgumentException("Policy class {$policy} does not exist");
        }

        $this->policies[$model] = $policy;
        $this->gate->policy($model, $policy);
    }

    /**
     * Register multiple policies.
     *
     * @param array<string, string> $policies Model => Policy mappings
     * @return void
     */
    public function registerMany(array $policies): void
    {
        foreach ($policies as $model => $policy) {
            $this->register($model, $policy);
        }
    }

    /**
     * Auto-discover and register policies.
     *
     * @return int Number of policies registered
     */
    public function autoDiscover(): int
    {
        if (!($this->policyConfig['auto_discover'] ?? false)) {
            return 0;
        }

        $namespace = $this->policyConfig['namespace'] ?? 'App\\Policies';
        $policyPath = app_path('Policies');

        if (!is_dir($policyPath)) {
            return 0;
        }

        $count = 0;
        $files = glob($policyPath . '/*.php');

        foreach ($files as $file) {
            $className = basename($file, '.php');
            $policyClass = $namespace . '\\' . $className;

            // Derive model name from policy name (e.g., UserPolicy => User)
            $modelName = str_replace('Policy', '', $className);
            $modelClass = 'App\\Models\\' . $modelName;

            if (class_exists($modelClass) && class_exists($policyClass)) {
                $this->register($modelClass, $policyClass);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get registered policy for a model.
     *
     * @param string $model Model class name
     * @return string|null Policy class name
     */
    public function getPolicy(string $model): ?string
    {
        return $this->policies[$model] ?? null;
    }

    /**
     * Get all registered policies.
     *
     * @return array<string, string> Model => Policy mappings
     */
    public function getPolicies(): array
    {
        return $this->policies;
    }

    /**
     * Check if a policy is registered for a model.
     *
     * @param string $model Model class name
     * @return bool
     */
    public function hasPolicy(string $model): bool
    {
        return isset($this->policies[$model]);
    }

    /**
     * Define a gate ability.
     *
     * @param string $ability Ability name
     * @param callable|string $callback Callback or policy method
     * @return void
     */
    public function define(string $ability, callable|string $callback): void
    {
        $this->gate->define($ability, $callback);
    }

    /**
     * Check if user can perform an ability.
     *
     * @param string $ability Ability name
     * @param mixed $arguments Arguments to pass to the policy
     * @param string|null $context Context (admin, public, api)
     * @return bool
     */
    public function can(string $ability, mixed $arguments = null, ?string $context = null): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Context-aware check
        if ($context && $this->authConfig['context_aware'] ?? false) {
            return $this->canInContext($user, $ability, $arguments, $context);
        }

        // Standard gate check
        return $this->gate->forUser($user)->allows($ability, $arguments);
    }

    /**
     * Check if user cannot perform an ability.
     *
     * @param string $ability Ability name
     * @param mixed $arguments Arguments to pass to the policy
     * @param string|null $context Context (admin, public, api)
     * @return bool
     */
    public function cannot(string $ability, mixed $arguments = null, ?string $context = null): bool
    {
        return !$this->can($ability, $arguments, $context);
    }

    /**
     * Authorize an ability or throw an exception.
     *
     * @param string $ability Ability name
     * @param mixed $arguments Arguments to pass to the policy
     * @param string|null $context Context (admin, public, api)
     * @param string|null $message Custom error message
     * @return void
     * @throws AuthorizationException
     */
    public function authorize(
        string $ability,
        mixed $arguments = null,
        ?string $context = null,
        ?string $message = null
    ): void {
        if (!$this->can($ability, $arguments, $context)) {
            throw new AuthorizationException(
                $message ?? "You are not authorized to {$ability}"
            );
        }
    }

    /**
     * Check if user can perform an ability in a specific context.
     *
     * @param mixed $user User instance
     * @param string $ability Ability name
     * @param mixed $arguments Arguments to pass to the policy
     * @param string $context Context (admin, public, api)
     * @return bool
     */
    protected function canInContext(mixed $user, string $ability, mixed $arguments, string $context): bool
    {
        // Check if context is enabled
        $contextConfig = config("canvastack-rbac.contexts.{$context}");

        if (!($contextConfig['enabled'] ?? false)) {
            return false;
        }

        // Build context-aware ability name
        $contextAbility = "{$context}.{$ability}";

        // Check if context-specific ability exists
        if ($this->gate->has($contextAbility)) {
            return $this->gate->forUser($user)->allows($contextAbility, $arguments);
        }

        // Fallback to standard ability
        return $this->gate->forUser($user)->allows($ability, $arguments);
    }

    /**
     * Check if user is super admin.
     *
     * @param mixed $user User instance
     * @return bool
     */
    protected function isSuperAdmin(mixed $user): bool
    {
        if (!($this->authConfig['super_admin_bypass'] ?? false)) {
            return false;
        }

        $superAdminRole = $this->authConfig['super_admin_role'] ?? 'super_admin';

        return $this->roleManager->userHasRole($user->id, $superAdminRole);
    }

    /**
     * Check if user has any of the given abilities.
     *
     * @param array<string> $abilities Ability names
     * @param mixed $arguments Arguments to pass to the policy
     * @param string|null $context Context (admin, public, api)
     * @return bool
     */
    public function canAny(array $abilities, mixed $arguments = null, ?string $context = null): bool
    {
        foreach ($abilities as $ability) {
            if ($this->can($ability, $arguments, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given abilities.
     *
     * @param array<string> $abilities Ability names
     * @param mixed $arguments Arguments to pass to the policy
     * @param string|null $context Context (admin, public, api)
     * @return bool
     */
    public function canAll(array $abilities, mixed $arguments = null, ?string $context = null): bool
    {
        foreach ($abilities as $ability) {
            if (!$this->can($ability, $arguments, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Define a resource policy (CRUD abilities).
     *
     * @param string $resource Resource name (e.g., 'users', 'posts')
     * @param string $model Model class name
     * @param string|null $policy Policy class name (optional)
     * @return void
     */
    public function resource(string $resource, string $model, ?string $policy = null): void
    {
        // Register policy if provided
        if ($policy) {
            $this->register($model, $policy);
        }

        // Define standard CRUD abilities
        $abilities = ['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'];

        foreach ($abilities as $ability) {
            $abilityName = "{$resource}.{$ability}";

            $this->define($abilityName, function ($user, $modelInstance = null) use ($ability, $model, $abilityName) {
                // If policy exists, use it
                if ($this->hasPolicy($model)) {
                    return $this->gate->forUser($user)->allows($ability, $modelInstance ?? $model);
                }

                // Fallback to permission check
                $permission = str_replace('viewAny', 'view', $abilityName);

                return $this->permissionManager->userHasPermission($user->id, $permission);
            });
        }
    }

    /**
     * Define abilities from permissions.
     *
     * @param string|null $module Module name (null for all modules)
     * @return int Number of abilities defined
     */
    public function defineFromPermissions(?string $module = null): int
    {
        $permissions = $module
            ? $this->permissionManager->getByModule($module)
            : $this->permissionManager->all();

        $count = 0;

        foreach ($permissions as $permission) {
            $this->define($permission->name, function ($user) use ($permission) {
                return $this->permissionManager->userHasPermission($user->id, $permission->name);
            });

            $count++;
        }

        return $count;
    }

    /**
     * Get gate instance.
     *
     * @return GateContract
     */
    public function getGate(): GateContract
    {
        return $this->gate;
    }

    /**
     * Before callback - runs before all gate checks.
     *
     * @param callable $callback Callback function
     * @return void
     */
    public function before(callable $callback): void
    {
        $this->gate->before($callback);
    }

    /**
     * After callback - runs after all gate checks.
     *
     * @param callable $callback Callback function
     * @return void
     */
    public function after(callable $callback): void
    {
        $this->gate->after($callback);
    }

    /**
     * Register super admin bypass callback.
     *
     * @return void
     */
    public function registerSuperAdminBypass(): void
    {
        if (!($this->authConfig['super_admin_bypass'] ?? false)) {
            return;
        }

        $this->before(function ($user, $ability) {
            if ($this->isSuperAdmin($user)) {
                return true;
            }
        });
    }

    /**
     * Register default permissions callback.
     *
     * @return void
     */
    public function registerDefaultPermissions(): void
    {
        $defaultPermissions = $this->authConfig['default_permissions'] ?? [];

        if (empty($defaultPermissions)) {
            return;
        }

        $this->after(function ($user, $ability, $result) use ($defaultPermissions) {
            // If already authorized, return result
            if ($result === true) {
                return $result;
            }

            // Check if ability is in default permissions
            if (in_array($ability, $defaultPermissions)) {
                return true;
            }

            return $result;
        });
    }

    /**
     * Check ability for a specific user.
     *
     * @param mixed $user User instance
     * @param string $ability Ability name
     * @param mixed $arguments Arguments to pass to the policy
     * @return bool
     */
    public function forUser(mixed $user, string $ability, mixed $arguments = null): bool
    {
        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->gate->forUser($user)->allows($ability, $arguments);
    }

    /**
     * Get abilities for a user.
     *
     * @param mixed $user User instance
     * @return array<string>
     */
    public function getUserAbilities(mixed $user): array
    {
        $abilities = [];

        // Get permissions
        $permissions = $this->permissionManager->getUserPermissions($user->id);

        foreach ($permissions as $permission) {
            $abilities[] = $permission->name;
        }

        return $abilities;
    }

    /**
     * Check if ability is defined.
     *
     * @param string $ability Ability name
     * @return bool
     */
    public function hasAbility(string $ability): bool
    {
        return $this->gate->has($ability);
    }

    /**
     * Get all defined abilities.
     *
     * @return array<string>
     */
    public function getAbilities(): array
    {
        return $this->gate->abilities();
    }
}
