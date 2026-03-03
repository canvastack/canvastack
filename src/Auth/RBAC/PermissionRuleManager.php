<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\RBAC;

use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Models\UserPermissionOverride;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Permission Rule Manager.
 *
 * Manages fine-grained permission rules including row-level, column-level,
 * JSON attribute, and conditional permissions. Provides caching and
 * user override functionality.
 */
class PermissionRuleManager
{
    /**
     * Cache configuration.
     *
     * @var array<string, mixed>
     */
    protected array $cacheConfig;

    /**
     * Fine-grained permission configuration.
     *
     * @var array<string, mixed>
     */
    protected array $fineGrainedConfig;

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
     * Template variable resolver instance.
     *
     * @var TemplateVariableResolver
     */
    protected TemplateVariableResolver $templateResolver;

    /**
     * Path matching cache to avoid repeated pattern matching.
     *
     * @var array<string, bool>
     */
    protected array $pathMatchCache = [];

    /**
     * Compiled pattern cache for faster matching.
     *
     * @var array<string, array>
     */
    protected array $compiledPatternCache = [];

    /**
     * Global static pattern cache shared across all instances.
     * This significantly improves performance by avoiding repeated pattern compilation.
     *
     * @var array<string, bool>
     */
    protected static array $globalPatternCache = [];

    /**
     * Global static compiled pattern cache shared across all instances.
     *
     * @var array<string, array>
     */
    protected static array $globalCompiledPatternCache = [];

    /**
     * Permission ID cache to avoid repeated database lookups.
     *
     * @var array<string, int>
     */
    protected static array $permissionIdCache = [];

    /**
     * Model class name cache to avoid repeated get_class() calls.
     *
     * @var array<int, string>
     */
    protected static array $modelClassCache = [];

    /**
     * Create a new PermissionRuleManager instance.
     *
     * @param RoleManager $roleManager Role manager instance
     * @param PermissionManager $permissionManager Permission manager instance
     * @param TemplateVariableResolver $templateResolver Template variable resolver instance
     */
    public function __construct(
        RoleManager $roleManager,
        PermissionManager $permissionManager,
        TemplateVariableResolver $templateResolver
    ) {
        $this->roleManager = $roleManager;
        $this->permissionManager = $permissionManager;
        $this->templateResolver = $templateResolver;

        // Load configuration
        $this->cacheConfig = config('canvastack-rbac.cache', []);
        $this->fineGrainedConfig = config('canvastack-rbac.fine_grained', []);
    }

    // =========================================================================
    // Row-Level Permission Methods
    // =========================================================================

    /**
     * Add a row-level permission rule.
     *
     * @param int $permissionId Permission ID
     * @param string $modelClass Model class name
     * @param array<string, mixed> $conditions Conditions for row access
     * @param string $operator Logical operator (AND/OR)
     * @return PermissionRule
     */
    public function addRowRule(
        int $permissionId,
        string $modelClass,
        array $conditions,
        string $operator = 'AND'
    ): PermissionRule {
        // Validate operator
        if (!in_array($operator, ['AND', 'OR'])) {
            throw new \InvalidArgumentException("Invalid operator: {$operator}. Must be AND or OR.");
        }

        // Create rule configuration
        $ruleConfig = [
            'type' => 'row',
            'model' => $modelClass,
            'conditions' => $conditions,
            'operator' => $operator,
        ];

        // Create and save the rule
        $rule = PermissionRule::create([
            'permission_id' => $permissionId,
            'rule_type' => 'row',
            'rule_config' => $ruleConfig,
            'priority' => 0,
        ]);

        // Clear cache for this permission
        $this->clearRuleCacheForPermission($permissionId);

        return $rule;
    }

    /**
     * Check if user can access a specific row.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param object $model Model instance
     * @return bool
     */
    public function canAccessRow(
        int $userId,
        string $permission,
        object $model
    ): bool {
        // Check if fine-grained permissions are enabled
        if (!$this->isEnabled() || !$this->isRuleTypeEnabled('row_level')) {
            return true; // Default to allow if disabled
        }

        // Get model class and ID
        $modelClass = get_class($model);
        $modelId = $model->id ?? null;

        // Check cache first
        if ($this->isCacheEnabled()) {
            $cacheKey = $this->getCacheKey("can_access_row:{$userId}:{$permission}:{$modelClass}:{$modelId}");
            $cached = $this->getCachedEvaluation($cacheKey, [], 'row');

            if ($cached !== null) {
                return $cached;
            }
        }

        // Get permission ID (cached lookup)
        $permissionObj = $this->permissionManager->findByName($permission);
        if (!$permissionObj) {
            return false;
        }

        // Check user overrides first (single query with permission ID)
        $override = $this->checkUserOverrideOptimized($userId, $permissionObj->id, $modelClass, $modelId);
        if ($override !== null) {
            // Cache and return override result
            if ($this->isCacheEnabled()) {
                $this->cacheRuleEvaluation($cacheKey, $override, $this->getCacheTtl('row'));
            }

            return $override;
        }

        // Get row-level AND conditional rules for this permission (eager load permission relationship)
        $rules = PermissionRule::with('permission')
            ->where('permission_id', $permissionObj->id)
            ->whereIn('rule_type', ['row', 'conditional'])
            ->orderBy('priority', 'desc')
            ->get();

        // If no rules, allow access
        if ($rules->isEmpty()) {
            if ($this->isCacheEnabled()) {
                $this->cacheRuleEvaluation($cacheKey, true, $this->getCacheTtl('row'));
            }

            return true;
        }

        // Evaluate all rules (AND logic between rules)
        $result = true;
        foreach ($rules as $rule) {
            $ruleConfig = $rule->rule_config;

            // Check if rule applies to this model
            if ($ruleConfig['model'] !== $modelClass) {
                continue;
            }

            // Evaluate based on rule type
            if ($rule->rule_type === 'row') {
                // Resolve template variables in conditions
                $conditions = $this->templateResolver->resolveConditions($ruleConfig['conditions']);

                // Evaluate conditions
                $ruleResult = $this->evaluateRowConditions($model, $conditions, $ruleConfig['operator'] ?? 'AND');
            } elseif ($rule->rule_type === 'conditional') {
                // Evaluate conditional rule
                $ruleResult = $rule->evaluate($model);
            } else {
                continue;
            }

            // Apply AND logic between rules
            $result = $result && $ruleResult;

            // Short-circuit if any rule fails
            if (!$result) {
                break;
            }
        }

        // Cache result
        if ($this->isCacheEnabled()) {
            $this->cacheRuleEvaluation($cacheKey, $result, $this->getCacheTtl('row'));
        }

        return $result;
    }

    /**
     * Apply row-level permission scope to query.
     *
     * @param Builder $query Query builder instance
     * @param int $userId User ID
     * @param string $permission Permission name
     * @return Builder
     */
    public function scopeByPermission(
        Builder $query,
        int $userId,
        string $permission
    ): Builder {
        // Check if fine-grained permissions are enabled
        if (!$this->isEnabled() || !$this->isRuleTypeEnabled('row_level')) {
            return $query; // Return unmodified query if disabled
        }

        // Get permission ID (cached lookup)
        $permissionObj = $this->permissionManager->findByName($permission);
        if (!$permissionObj) {
            // No permission found, return empty result
            $query->whereRaw('1 = 0');

            return $query;
        }

        // Get model class from query
        $modelClass = get_class($query->getModel());

        // Get row-level AND conditional rules for this permission (eager load permission relationship)
        $rules = PermissionRule::with('permission')
            ->where('permission_id', $permissionObj->id)
            ->whereIn('rule_type', ['row', 'conditional'])
            ->orderBy('priority', 'desc')
            ->get();

        // If no rules, return unmodified query
        if ($rules->isEmpty()) {
            return $query;
        }

        // Apply rules to query
        foreach ($rules as $rule) {
            $ruleConfig = $rule->rule_config;

            // Check if rule applies to this model
            if ($ruleConfig['model'] !== $modelClass) {
                continue;
            }

            if ($rule->rule_type === 'row') {
                // Resolve template variables in conditions
                $conditions = $this->templateResolver->resolveConditions($ruleConfig['conditions']);

                // Apply conditions to query
                $operator = $ruleConfig['operator'] ?? 'AND';

                if ($operator === 'AND') {
                    // Apply AND conditions
                    foreach ($conditions as $field => $value) {
                        $query->where($field, $value);
                    }
                } else {
                    // Apply OR conditions
                    $query->where(function ($q) use ($conditions) {
                        foreach ($conditions as $field => $value) {
                            $q->orWhere($field, $value);
                        }
                    });
                }
            } elseif ($rule->rule_type === 'conditional') {
                // Resolve template variables in condition first
                $condition = $ruleConfig['condition'];
                $resolvedCondition = $this->resolveTemplateVariablesInCondition($condition);

                // Parse and apply conditional rule to query
                $this->applyConditionalToQuery($query, $resolvedCondition);
            }
        }

        return $query;
    }

    // =========================================================================
    // Column-Level Permission Methods
    // =========================================================================

    /**
     * Add a column-level permission rule.
     *
     * @param int $permissionId Permission ID
     * @param string $modelClass Model class name
     * @param array<string> $allowedColumns Allowed columns
     * @param array<string> $deniedColumns Denied columns
     * @return PermissionRule
     */
    public function addColumnRule(
        int $permissionId,
        string $modelClass,
        array $allowedColumns,
        array $deniedColumns = []
    ): PermissionRule {
        // Validate that we have either allowed or denied columns
        if (empty($allowedColumns) && empty($deniedColumns)) {
            throw new \InvalidArgumentException('Must specify either allowed or denied columns');
        }

        // Determine mode based on what's provided
        $mode = !empty($allowedColumns) ? 'whitelist' : 'blacklist';

        // Create rule configuration
        $ruleConfig = [
            'type' => 'column',
            'model' => $modelClass,
            'allowed_columns' => $allowedColumns,
            'denied_columns' => $deniedColumns,
            'mode' => $mode,
        ];

        // Create and save the rule
        $rule = PermissionRule::create([
            'permission_id' => $permissionId,
            'rule_type' => 'column',
            'rule_config' => $ruleConfig,
            'priority' => 0,
        ]);

        // Clear cache for this permission
        $this->clearRuleCacheForPermission($permissionId);

        return $rule;
    }

    /**
     * Check if user can access a specific column.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param object $model Model instance
     * @param string $column Column name
     * @return bool
     */
    public function canAccessColumn(
        int $userId,
        string $permission,
        object $model,
        string $column
    ): bool {
        // Check if fine-grained permissions are enabled
        if (!$this->isEnabled() || !$this->isRuleTypeEnabled('column_level')) {
            return true; // Default to allow if disabled
        }

        // Get model class (OPTIMIZED - cached)
        $modelClass = $this->getModelClass($model);
        $modelId = $model->id ?? null;

        // Check cache first - use a single cache key for the result
        if ($this->isCacheEnabled()) {
            $cacheKey = $this->getCacheKey("can_access_column:{$userId}:{$permission}:{$modelClass}:{$column}");
            $cached = $this->getCachedEvaluation($cacheKey, [], 'column');

            if ($cached !== null) {
                return $cached;
            }
        }

        // Check user overrides first (column-specific) - this is already cached
        $override = $this->checkColumnOverride($userId, $permission, $modelClass, $modelId, $column);
        if ($override !== null) {
            // Cache and return override result
            if ($this->isCacheEnabled()) {
                $this->cacheRuleEvaluation($cacheKey, $override, $this->getCacheTtl('column'));
            }

            return $override;
        }

        // Get accessible columns (this method is already cached and now optimized)
        $accessibleColumns = $this->getAccessibleColumns($userId, $permission, $modelClass);

        // Determine result based on accessible columns
        $result = $this->evaluateColumnAccess($column, $accessibleColumns);

        // Cache result
        if ($this->isCacheEnabled()) {
            $this->cacheRuleEvaluation($cacheKey, $result, $this->getCacheTtl('column'));
        }

        return $result;
    }

    /**
     * Evaluate column access based on accessible columns list.
     *
     * @param string $column Column name to check
     * @param array $accessibleColumns List of accessible columns (may include negations with !)
     * @return bool True if column is accessible
     */
    protected function evaluateColumnAccess(string $column, array $accessibleColumns): bool
    {
        // If empty array, allow by default (no rules defined)
        if (empty($accessibleColumns)) {
            return true;
        }

        // Check for negated columns (blacklist mode)
        $hasNegations = false;
        foreach ($accessibleColumns as $col) {
            if (is_string($col) && str_starts_with($col, '!')) {
                $hasNegations = true;
                $deniedColumn = substr($col, 1);
                if ($deniedColumn === $column) {
                    return false; // Column is explicitly denied
                }
            }
        }

        // If we have negations (blacklist mode), allow if not denied
        if ($hasNegations) {
            return true;
        }

        // Whitelist mode: check if column is in the list
        return in_array($column, $accessibleColumns, true);
    }

    /**
     * Get accessible columns for user and permission.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @return array<string>
     */
    public function getAccessibleColumns(
        int $userId,
        string $permission,
        string $modelClass
    ): array {
        // Check if fine-grained permissions are enabled
        if (!$this->isEnabled() || !$this->isRuleTypeEnabled('column_level')) {
            // If disabled, return empty array (allow all by default)
            return [];
        }

        // Check cache first with optimized key
        if ($this->isCacheEnabled()) {
            $cacheKey = $this->generateAccessibleColumnsCacheKey($userId, $permission, $modelClass);
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }
        }

        // Get permission ID - use cached permission lookup (OPTIMIZATION)
        $permissionId = $this->getPermissionId($permission);
        if (!$permissionId) {
            // Cache empty result for non-existent permissions
            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, [], $this->getCacheTtl('column'));
            }

            return [];
        }

        // Get column-level rules for this permission (eager load permission relationship)
        $rules = PermissionRule::with('permission')
            ->where('permission_id', $permissionId)
            ->where('rule_type', 'column')
            ->orderBy('priority', 'desc')
            ->get();

        // If no rules, return empty array (allow all by default)
        if ($rules->isEmpty()) {
            $result = [];

            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $result, $this->getCacheTtl('column'));
            }

            return $result;
        }

        // Process rules to determine accessible columns (OPTIMIZED)
        $accessibleColumns = []; // Use array keys for O(1) lookup
        $deniedColumns = [];
        $hasWhitelist = false;
        $hasMatchingRule = false;

        foreach ($rules as $rule) {
            $ruleConfig = $rule->rule_config;

            // Filter by model class in PHP for better compatibility
            if ($ruleConfig['model'] !== $modelClass) {
                continue;
            }

            $hasMatchingRule = true;
            $mode = $ruleConfig['mode'] ?? 'whitelist';

            if ($mode === 'whitelist') {
                // Whitelist mode: only allowed columns are accessible
                $hasWhitelist = true;
                $allowedColumns = $ruleConfig['allowed_columns'] ?? [];
                // Use array keys instead of array_merge (OPTIMIZATION)
                foreach ($allowedColumns as $col) {
                    $accessibleColumns[$col] = true;
                }
            } else {
                // Blacklist mode: all columns except denied are accessible
                $deniedCols = $ruleConfig['denied_columns'] ?? [];
                foreach ($deniedCols as $col) {
                    $deniedColumns[$col] = true;
                }
            }
        }

        // If no rules match this model, return empty array (allow all)
        if (!$hasMatchingRule) {
            $result = [];

            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $result, $this->getCacheTtl('column'));
            }

            return $result;
        }

        // Determine final result (OPTIMIZED)
        if ($hasWhitelist) {
            // Whitelist mode: return unique allowed columns
            $result = array_keys($accessibleColumns);
        } else {
            // Blacklist mode: return denied columns with ! prefix
            $result = array_map(fn ($col) => "!{$col}", array_keys($deniedColumns));
        }

        // Cache result with longer TTL since rules don't change often
        if ($this->isCacheEnabled()) {
            Cache::put($cacheKey, $result, $this->getCacheTtl('column'));
        }

        return $result;
    }

    // =========================================================================
    // JSON Attribute Permission Methods
    // =========================================================================

    /**
     * Add a JSON attribute permission rule.
     *
     * @param int $permissionId Permission ID
     * @param string $modelClass Model class name
     * @param string $jsonColumn JSON column name
     * @param array<string> $allowedPaths Allowed JSON paths
     * @param array<string> $deniedPaths Denied JSON paths
     * @return PermissionRule
     */
    public function addJsonAttributeRule(
        int $permissionId,
        string $modelClass,
        string $jsonColumn,
        array $allowedPaths,
        array $deniedPaths = []
    ): PermissionRule {
        // Validate that we have either allowed or denied paths
        if (empty($allowedPaths) && empty($deniedPaths)) {
            throw new \InvalidArgumentException('Must specify either allowed or denied JSON paths');
        }

        // Get path separator from config
        $pathSeparator = $this->fineGrainedConfig['json_attribute']['path_separator'] ?? '.';

        // Create rule configuration
        $ruleConfig = [
            'type' => 'json_attribute',
            'model' => $modelClass,
            'json_column' => $jsonColumn,
            'allowed_paths' => $allowedPaths,
            'denied_paths' => $deniedPaths,
            'path_separator' => $pathSeparator,
        ];

        // Create and save the rule
        $rule = PermissionRule::create([
            'permission_id' => $permissionId,
            'rule_type' => 'json_attribute',
            'rule_config' => $ruleConfig,
            'priority' => 0,
        ]);

        // Clear cache for this permission
        $this->clearRuleCacheForPermission($permissionId);

        return $rule;
    }

    /**
     * Check if user can access a JSON attribute.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param object $model Model instance
     * @param string $jsonColumn JSON column name
     * @param string $path JSON path
     * @return bool
     */
    public function canAccessJsonAttribute(
        int $userId,
        string $permission,
        object $model,
        string $jsonColumn,
        string $path
    ): bool {
        // Check if fine-grained permissions are enabled
        if (!$this->isEnabled() || !$this->isRuleTypeEnabled('json_attribute')) {
            return true; // Default to allow if disabled
        }

        // Get model class
        $modelClass = get_class($model);
        $modelId = $model->id ?? null;

        // Check cache first
        if ($this->isCacheEnabled()) {
            $cacheKey = $this->getCacheKey("can_access_json:{$userId}:{$permission}:{$modelClass}:{$jsonColumn}:{$path}");
            $cached = $this->getCachedEvaluation($cacheKey, [], 'json_attribute');

            if ($cached !== null) {
                return $cached;
            }
        }

        // Check user overrides first (JSON attribute-specific)
        $override = $this->checkJsonAttributeOverride($userId, $permission, $modelClass, $modelId, $jsonColumn, $path);
        if ($override !== null) {
            // Cache and return override result
            if ($this->isCacheEnabled()) {
                $this->cacheRuleEvaluation($cacheKey, $override, $this->getCacheTtl('json_attribute'));
            }

            return $override;
        }

        // Get accessible JSON paths
        $accessiblePaths = $this->getAccessibleJsonPaths($userId, $permission, $modelClass, $jsonColumn);

        // Check if path is in accessible list
        $result = $this->isPathAccessible($path, $accessiblePaths);

        // Cache result
        if ($this->isCacheEnabled()) {
            $this->cacheRuleEvaluation($cacheKey, $result, $this->getCacheTtl('json_attribute'));
        }

        return $result;
    }

    /**
     * Get accessible JSON paths for user and permission.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @param string $jsonColumn JSON column name
     * @return array<string, array<string>>
     */
    public function getAccessibleJsonPaths(
        int $userId,
        string $permission,
        string $modelClass,
        string $jsonColumn
    ): array {
        // Check if fine-grained permissions are enabled
        if (!$this->isEnabled() || !$this->isRuleTypeEnabled('json_attribute')) {
            // If disabled, return empty array (allow all)
            return [];
        }

        // Check cache first
        if ($this->isCacheEnabled()) {
            $cacheKey = $this->generateAccessibleJsonPathsCacheKey($userId, $permission, $modelClass, $jsonColumn);
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }
        }

        // Get permission ID using cached lookup (OPTIMIZATION)
        $permissionId = $this->getPermissionId($permission);
        if (!$permissionId) {
            return [];
        }

        // Get JSON attribute rules for this permission
        $rules = PermissionRule::where('permission_id', $permissionId)
            ->where('rule_type', 'json_attribute')
            ->orderBy('priority', 'desc')
            ->get();

        // If no rules, return empty array (allow all)
        if ($rules->isEmpty()) {
            $result = [];

            if ($this->isCacheEnabled()) {
                $cacheKey = $this->generateAccessibleJsonPathsCacheKey($userId, $permission, $modelClass, $jsonColumn);
                Cache::put($cacheKey, $result, $this->getCacheTtl('json_attribute'));
            }

            return $result;
        }

        // Process rules to determine accessible paths
        $allowedPaths = [];
        $deniedPaths = [];

        foreach ($rules as $rule) {
            $ruleConfig = $rule->rule_config;

            // Check if rule applies to this model and JSON column
            if ($ruleConfig['model'] !== $modelClass || $ruleConfig['json_column'] !== $jsonColumn) {
                continue;
            }

            // Collect allowed and denied paths
            $allowedPaths = array_merge($allowedPaths, $ruleConfig['allowed_paths'] ?? []);
            $deniedPaths = array_merge($deniedPaths, $ruleConfig['denied_paths'] ?? []);
        }

        // Build result array
        $result = [
            'allowed' => array_unique($allowedPaths),
            'denied' => array_unique($deniedPaths),
        ];

        // Cache result
        if ($this->isCacheEnabled()) {
            $cacheKey = $this->generateAccessibleJsonPathsCacheKey($userId, $permission, $modelClass, $jsonColumn);
            Cache::put($cacheKey, $result, $this->getCacheTtl('json_attribute'));
        }

        return $result;
    }

    // =========================================================================
    // Conditional Permission Methods
    // =========================================================================

    /**
     * Add a conditional permission rule.
     *
     * @param int $permissionId Permission ID
     * @param string $modelClass Model class name
     * @param string $condition Condition expression
     * @return PermissionRule
     * @throws \InvalidArgumentException If condition syntax is invalid
     */
    public function addConditionalRule(
        int $permissionId,
        string $modelClass,
        string $condition
    ): PermissionRule {
        // Validate condition syntax
        $this->validateConditionSyntax($condition);

        // Get allowed operators from config
        $allowedOperators = $this->fineGrainedConfig['conditional']['allowed_operators'] ?? [
            '===', '!==', '>', '<', '>=', '<=',
            'in', 'not_in', 'AND', 'OR', 'NOT',
        ];

        // Create rule configuration
        $ruleConfig = [
            'type' => 'conditional',
            'model' => $modelClass,
            'condition' => $condition,
            'allowed_operators' => $allowedOperators,
        ];

        // Create and save the rule
        $rule = PermissionRule::create([
            'permission_id' => $permissionId,
            'rule_type' => 'conditional',
            'rule_config' => $ruleConfig,
            'priority' => 0,
        ]);

        // Clear cache for this permission
        $this->clearRuleCacheForPermission($permissionId);

        return $rule;
    }

    /**
     * Evaluate a condition expression.
     *
     * @param string $condition Condition expression
     * @param object $model Model instance
     * @return bool
     * @throws \RuntimeException If condition evaluation fails
     */
    protected function evaluateCondition(
        string $condition,
        object $model
    ): bool {
        // Resolve template variables in condition
        $condition = $this->templateResolver->resolve($condition);

        // Parse and evaluate the condition
        try {
            return $this->parseAndEvaluateCondition($condition, $model);
        } catch (\Exception $e) {
            // Log error for debugging
            \Log::error('Conditional rule evaluation failed', [
                'condition' => $condition,
                'model' => get_class($model),
                'error' => $e->getMessage(),
            ]);

            // Return false on evaluation error (fail-safe)
            return false;
        }
    }

    /**
     * Validate condition syntax.
     *
     * @param string $condition Condition expression
     * @return void
     * @throws \InvalidArgumentException If syntax is invalid
     */
    protected function validateConditionSyntax(string $condition): void
    {
        // Check for empty condition
        if (empty(trim($condition))) {
            throw new \InvalidArgumentException('Condition cannot be empty');
        }

        // Get allowed operators
        $allowedOperators = $this->fineGrainedConfig['conditional']['allowed_operators'] ?? [
            '===', '!==', '>', '<', '>=', '<=',
            'in', 'not_in', 'AND', 'OR', 'NOT',
        ];

        // Check for code injection attempts
        $dangerousPatterns = [
            '/\$\w+\s*=/',           // Variable assignment
            '/eval\s*\(/i',          // eval() function
            '/exec\s*\(/i',          // exec() function
            '/system\s*\(/i',        // system() function
            '/passthru\s*\(/i',      // passthru() function
            '/shell_exec\s*\(/i',    // shell_exec() function
            '/`[^`]*`/',             // Backtick execution
            '/\binclude\b/i',        // include statement
            '/\brequire\b/i',        // require statement
            '/__halt_compiler/i',    // __halt_compiler
            '/\bdie\b/i',            // die statement
            '/\bexit\b/i',           // exit statement
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $condition)) {
                throw new \InvalidArgumentException('Condition contains potentially dangerous code');
            }
        }

        // Validate that condition uses only allowed operators
        $operatorPattern = implode('|', array_map('preg_quote', $allowedOperators));

        // Extract all operators from condition
        preg_match_all('/\b(' . $operatorPattern . ')\b/i', $condition, $matches);

        // Check if condition has at least one operator (unless it's a simple boolean check)
        if (empty($matches[0]) && !preg_match('/^\w+(\.\w+)*$/', trim($condition))) {
            throw new \InvalidArgumentException('Condition must contain at least one valid operator');
        }
    }

    /**
     * Parse and evaluate condition expression.
     *
     * @param string $condition Condition expression
     * @param object $model Model instance
     * @return bool
     */
    protected function parseAndEvaluateCondition(string $condition, object $model): bool
    {
        // Handle logical operators (AND, OR, NOT)
        if (preg_match('/\b(AND|OR)\b/i', $condition)) {
            return $this->evaluateLogicalCondition($condition, $model);
        }

        // Handle NOT operator
        if (preg_match('/^\s*NOT\s+(.+)$/i', $condition, $matches)) {
            return !$this->parseAndEvaluateCondition($matches[1], $model);
        }

        // Handle comparison operators
        return $this->evaluateComparisonCondition($condition, $model);
    }

    /**
     * Evaluate logical condition (AND/OR).
     *
     * @param string $condition Condition expression
     * @param object $model Model instance
     * @return bool
     */
    protected function evaluateLogicalCondition(string $condition, object $model): bool
    {
        // Split by OR first (lower precedence)
        if (preg_match('/\bOR\b/i', $condition)) {
            $parts = preg_split('/\bOR\b/i', $condition);
            if ($parts === false) {
                return false;
            }
            foreach ($parts as $part) {
                if ($this->parseAndEvaluateCondition(trim($part), $model)) {
                    return true; // Short-circuit on first true
                }
            }

            return false;
        }

        // Split by AND (higher precedence)
        if (preg_match('/\bAND\b/i', $condition)) {
            $parts = preg_split('/\bAND\b/i', $condition);
            if ($parts === false) {
                return false;
            }
            foreach ($parts as $part) {
                if (!$this->parseAndEvaluateCondition(trim($part), $model)) {
                    return false; // Short-circuit on first false
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Evaluate comparison condition.
     *
     * @param string $condition Condition expression
     * @param object $model Model instance
     * @return bool
     */
    protected function evaluateComparisonCondition(string $condition, object $model): bool
    {
        // Match comparison patterns
        $patterns = [
            '/^(.+?)\s+(===)\s+(.+)$/' => 'strict_equal',
            '/^(.+?)\s+(!\==)\s+(.+)$/' => 'strict_not_equal',
            '/^(.+?)\s+(>=)\s+(.+)$/' => 'greater_equal',
            '/^(.+?)\s+(<=)\s+(.+)$/' => 'less_equal',
            '/^(.+?)\s+(>)\s+(.+)$/' => 'greater',
            '/^(.+?)\s+(<)\s+(.+)$/' => 'less',
            '/^(.+?)\s+in\s+(\[.+?\])$/i' => 'in',
            '/^(.+?)\s+not_in\s+(\[.+?\])$/i' => 'not_in',
        ];

        foreach ($patterns as $pattern => $type) {
            if (preg_match($pattern, $condition, $matches)) {
                $left = trim($matches[1]);
                $right = isset($matches[3]) ? trim($matches[3]) : trim($matches[2]);

                return $this->evaluateComparison($left, $right, $type, $model);
            }
        }

        // If no operator found, treat as boolean field check
        $field = trim($condition);
        $value = $this->getModelValue($model, $field);

        return (bool) $value;
    }

    /**
     * Evaluate comparison between two values.
     *
     * @param string $left Left operand
     * @param string $right Right operand
     * @param string $operator Comparison operator
     * @param object $model Model instance
     * @return bool
     */
    protected function evaluateComparison(
        string $left,
        string $right,
        string $operator,
        object $model
    ): bool {
        // Get left value (from model)
        $leftValue = $this->getModelValue($model, $left);

        // Get right value (literal or from model)
        $rightValue = $this->parseValue($right, $model);

        // Perform comparison
        return match ($operator) {
            'strict_equal' => $leftValue === $rightValue,
            'strict_not_equal' => $leftValue !== $rightValue,
            'greater' => $leftValue > $rightValue,
            'less' => $leftValue < $rightValue,
            'greater_equal' => $leftValue >= $rightValue,
            'less_equal' => $leftValue <= $rightValue,
            'in' => $this->evaluateInOperator($leftValue, $rightValue),
            'not_in' => !$this->evaluateInOperator($leftValue, $rightValue),
            default => false,
        };
    }

    /**
     * Evaluate IN operator.
     *
     * @param mixed $value Value to check
     * @param mixed $array Array or comma-separated string
     * @return bool
     */
    protected function evaluateInOperator(mixed $value, mixed $array): bool
    {
        // If array is a string, parse it
        if (is_string($array)) {
            $array = array_map('trim', explode(',', $array));
        }

        // Convert to array if not already
        if (!is_array($array)) {
            $array = [$array];
        }

        return in_array($value, $array, true);
    }

    /**
     * Parse value from string (literal or model field).
     *
     * @param string $value Value string
     * @param object $model Model instance
     * @return mixed
     */
    protected function parseValue(string $value, object $model): mixed
    {
        $value = trim($value);

        // Check if it's an array (for IN/NOT_IN operators)
        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            $arrayContent = substr($value, 1, -1); // Remove brackets
            $items = array_map('trim', explode(',', $arrayContent));

            // Parse each item
            $parsedItems = [];
            foreach ($items as $item) {
                // Remove quotes if present
                if ((str_starts_with($item, "'") && str_ends_with($item, "'")) ||
                    (str_starts_with($item, '"') && str_ends_with($item, '"'))) {
                    $parsedItems[] = substr($item, 1, -1);
                } else {
                    $parsedItems[] = $item;
                }
            }

            return $parsedItems;
        }

        // Check if it's a quoted string
        if ((str_starts_with($value, "'") && str_ends_with($value, "'")) ||
            (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
            return substr($value, 1, -1); // Remove quotes
        }

        // Check if it's a number
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // Check if it's a boolean
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }

        // Check if it's null
        if (strtolower($value) === 'null') {
            return null;
        }

        // Otherwise, treat as model field
        return $this->getModelValue($model, $value);
    }

    // =========================================================================
    // User Override Methods
    // =========================================================================

    /**
     * Add a user permission override.
     *
     * @param int $userId User ID
     * @param int $permissionId Permission ID
     * @param string $modelType Model type
     * @param int|null $modelId Model ID (null for all instances)
     * @param string|null $fieldName Field name (null for row-level)
     * @param bool $allowed Whether access is allowed
     * @return UserPermissionOverride
     */
    public function addUserOverride(
        int $userId,
        int $permissionId,
        string $modelType,
        ?int $modelId = null,
        ?string $fieldName = null,
        bool $allowed = true
    ): UserPermissionOverride {
        // Validate that permission exists
        $permission = $this->permissionManager->find($permissionId);
        if (!$permission) {
            throw new \InvalidArgumentException("Permission with ID {$permissionId} not found");
        }

        // Check if override already exists
        $query = UserPermissionOverride::where('user_id', $userId)
            ->where('permission_id', $permissionId)
            ->where('model_type', $modelType);

        if ($modelId !== null) {
            $query->where('model_id', $modelId);
        } else {
            $query->whereNull('model_id');
        }

        if ($fieldName !== null) {
            $query->where('field_name', $fieldName);
        } else {
            $query->whereNull('field_name');
        }

        $existingOverride = $query->first();

        if ($existingOverride) {
            // Update existing override
            $existingOverride->update([
                'allowed' => $allowed,
                'rule_config' => null, // Clear any custom rule config
            ]);

            // Clear cache for this user and permission
            $this->clearUserOverrideCache($userId, $permissionId);

            return $existingOverride;
        }

        // Create new override
        $override = UserPermissionOverride::create([
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'field_name' => $fieldName,
            'rule_config' => null,
            'allowed' => $allowed,
        ]);

        // Clear cache for this user and permission
        $this->clearUserOverrideCache($userId, $permissionId);

        return $override;
    }

    /**
     * Remove a user permission override.
     *
     * @param int $userId User ID
     * @param int $permissionId Permission ID
     * @param string $modelType Model type
     * @param int|null $modelId Model ID (null for all instances)
     * @return bool
     */
    public function removeUserOverride(
        int $userId,
        int $permissionId,
        string $modelType,
        ?int $modelId = null
    ): bool {
        // Build query to find override(s)
        $query = UserPermissionOverride::where('user_id', $userId)
            ->where('permission_id', $permissionId)
            ->where('model_type', $modelType);

        if ($modelId !== null) {
            $query->where('model_id', $modelId);
        } else {
            $query->whereNull('model_id');
        }

        // Delete all matching overrides (row-level and field-level)
        $deleted = $query->delete();

        // Clear cache for this user and permission
        if ($deleted > 0) {
            $this->clearUserOverrideCache($userId, $permissionId);
        }

        return $deleted > 0;
    }

    /**
     * Get user permission overrides.
     *
     * @param int $userId User ID
     * @param int $permissionId Permission ID
     * @return Collection
     */
    public function getUserOverrides(
        int $userId,
        int $permissionId
    ): Collection {
        // Get all overrides for this user and permission
        return UserPermissionOverride::where('user_id', $userId)
            ->where('permission_id', $permissionId)
            ->orderBy('model_type')
            ->orderBy('model_id')
            ->orderBy('field_name')
            ->get();
    }

    // =========================================================================
    // Cache Methods
    // =========================================================================

    /**
     * Cache a rule evaluation result.
     *
     * @param string $key Cache key
     * @param bool $result Evaluation result
     * @param int $ttl Time to live in seconds
     * @return void
     */
    /**
     * Cache rule evaluation result.
     *
     * Stores the result of a permission rule evaluation in cache with
     * appropriate tags for efficient invalidation.
     *
     * @param string $key Cache key
     * @param bool $result Evaluation result
     * @param int $ttl Time to live in seconds
     * @param array<string> $tags Cache tags
     * @return void
     */
    protected function cacheRuleEvaluation(
        string $key,
        bool $result,
        int $ttl,
        array $tags = []
    ): void {
        if (!$this->isCacheEnabled()) {
            return;
        }

        // Use provided tags or default to base tag
        if (empty($tags)) {
            $tags = ['rbac:rules'];
        }

        // Store with tags for efficient invalidation (Redis/Memcached)
        try {
            Cache::tags($tags)->put($key, $result, $ttl);

            // Log cache write for monitoring (only in debug mode)
            if (config('app.debug')) {
                Log::debug('Permission rule cached', [
                    'key' => $key,
                    'result' => $result,
                    'ttl' => $ttl,
                    'tags' => $tags,
                ]);
            }
        } catch (\BadMethodCallException $e) {
            // Fallback for cache drivers that don't support tags (e.g., file, database)
            Cache::put($key, $result, $ttl);

            Log::warning('Cache driver does not support tags, using basic cache', [
                'key' => $key,
                'driver' => config('cache.default'),
            ]);
        }
    }

    /**
     * Get cached rule evaluation result.
     *
     * Retrieves a previously cached permission rule evaluation result.
     * Returns null if cache is disabled or no cached value exists.
     * Logs cache hit/miss for monitoring.
     *
     * @param string $key Cache key
     * @param array<string> $tags Cache tags
     * @param string $ruleType Rule type for statistics (row, column, json_attribute, conditional)
     * @return bool|null Cached result or null if not found
     */
    protected function getCachedEvaluation(string $key, array $tags = [], string $ruleType = 'row'): ?bool
    {
        if (!$this->isCacheEnabled()) {
            return null;
        }

        // Use provided tags or default to base tag
        if (empty($tags)) {
            $tags = ['rbac:rules'];
        }

        // Retrieve from tagged cache (Redis/Memcached)
        try {
            $cached = Cache::tags($tags)->get($key);

            // Log cache hit/miss for monitoring
            $hit = $cached !== null;
            $this->logCacheOperation($hit, $ruleType);

            // Log cache hit/miss for debugging (only in debug mode)
            if (config('app.debug')) {
                Log::debug('Permission rule cache lookup', [
                    'key' => $key,
                    'hit' => $hit,
                    'tags' => $tags,
                    'type' => $ruleType,
                ]);
            }
        } catch (\BadMethodCallException $e) {
            // Fallback for cache drivers that don't support tags
            $cached = Cache::get($key);

            // Log cache hit/miss for monitoring
            $hit = $cached !== null;
            $this->logCacheOperation($hit, $ruleType);
        }

        return is_bool($cached) ? $cached : null;
    }

    /**
     * Clear rule cache.
     *
     * Clears cached permission rule evaluations. Can be scoped to specific
     * user and/or permission for targeted cache invalidation.
     *
     * @param int|null $userId User ID (null for all users)
     * @param string|null $permission Permission name (null for all permissions)
     * @return bool True if cache was cleared successfully
     */
    public function clearRuleCache(
        ?int $userId = null,
        ?string $permission = null
    ): bool {
        if (!$this->isCacheEnabled()) {
            // Still clear in-memory caches even if Redis cache is disabled
            $this->clearPathMatchCache();

            return true; // Consider it successful if cache is disabled
        }

        try {
            // Build tags array based on parameters
            $tags = ['rbac:rules'];

            if ($userId !== null) {
                $tags[] = "rbac:user:{$userId}";
            }

            if ($permission !== null) {
                $tags[] = "rbac:permission:{$permission}";
            }

            // Flush cache by tags
            Cache::tags($tags)->flush();

            // Clear in-memory path matching cache
            $this->clearPathMatchCache();

            // Log cache clear for monitoring
            Log::info('Permission rule cache cleared', [
                'user_id' => $userId,
                'permission' => $permission,
                'tags' => $tags,
            ]);

            return true;
        } catch (\Exception $e) {
            // Log error but don't throw - cache clearing should be non-blocking
            Log::error('Failed to clear permission rule cache', [
                'user_id' => $userId,
                'permission' => $permission,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Warm up cache for user permissions.
     *
     * @param int $userId User ID
     * @param array<string> $permissions Permission names
     * @return void
     */
    /**
     * Warm up cache for frequently used permissions.
     *
     * This method pre-caches permission evaluations for a user and their permissions
     * to improve performance on subsequent checks.
     *
     * @param int $userId User ID
     * @param array $permissions Array of permission names to warm up
     * @return void
     */
    /**
     * Warm up cache for specific permissions.
     *
     * This method pre-caches permission evaluations for a user and their permissions.
     * It intelligently warms up the cache with actual data to improve hit rates.
     *
     * Optimizations:
     * - Batches database queries to reduce overhead
     * - Only warms up enabled rule types
     * - Deduplicates cache warming operations
     * - Uses actual model instances for realistic cache keys
     *
     * @param int $userId User ID
     * @param array<string> $permissions Array of permission names
     * @return void
     */
    public function warmUpCache(
        int $userId,
        array $permissions
    ): void {
        if (!$this->isCacheEnabled()) {
            return;
        }

        // Track warmed cache keys to avoid duplicates
        $warmedKeys = [];

        foreach ($permissions as $permission) {
            // Get permission ID using cached lookup (OPTIMIZATION)
            $permissionId = $this->getPermissionId($permission);
            if (!$permissionId) {
                continue;
            }

            // Get all rules for this permission (batch query)
            $rules = PermissionRule::where('permission_id', $permissionId)
                ->orderBy('priority', 'desc')
                ->get();

            // Group rules by type for efficient processing
            $rulesByType = $rules->groupBy('rule_type');

            // Warm up row-level rules
            if ($this->isRuleTypeEnabled('row') && isset($rulesByType['row'])) {
                $this->warmUpRowRules($userId, $permission, $rulesByType['row'], $warmedKeys);
            }

            // Warm up column-level rules
            if ($this->isRuleTypeEnabled('column') && isset($rulesByType['column'])) {
                $this->warmUpColumnRules($userId, $permission, $rulesByType['column'], $warmedKeys);
            }

            // Warm up JSON attribute rules
            if ($this->isRuleTypeEnabled('json_attribute') && isset($rulesByType['json_attribute'])) {
                $this->warmUpJsonAttributeRules($userId, $permission, $rulesByType['json_attribute'], $warmedKeys);
            }

            // Warm up conditional rules
            if ($this->isRuleTypeEnabled('conditional') && isset($rulesByType['conditional'])) {
                $this->warmUpConditionalRules($userId, $permission, $rulesByType['conditional'], $warmedKeys);
            }
        }
    }

    /**
     * Warm up cache for a specific user.
     *
     * This method pre-caches all permissions for a user based on their roles.
     *
     * @param int $userId User ID
     * @return void
     */
    public function warmUpUserCache(int $userId): void
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        // Get all permissions for user
        $permissions = $this->permissionManager->getUserPermissions($userId);

        // Extract permission names
        $permissionNames = $permissions->pluck('name')->toArray();

        // Warm up cache for all permissions
        $this->warmUpCache($userId, $permissionNames);
    }

    /**
     * Warm up cache for a specific rule.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param PermissionRule $rule Permission rule
     * @return void
     */
    protected function warmUpRuleCache(
        int $userId,
        string $permission,
        PermissionRule $rule
    ): void {
        $ruleType = $rule->rule_type;
        $modelClass = $rule->rule_config['model'] ?? null;

        if (!$modelClass) {
            return;
        }

        // Create a dummy model instance for cache key generation
        // We don't evaluate the actual rule, just generate cache keys
        try {
            $dummyModel = new $modelClass();
            $dummyModel->id = 0; // Dummy ID for cache key

            switch ($ruleType) {
                case 'row':
                    // Generate cache key for row-level check
                    $cacheKey = $this->generateRowCacheKey($userId, $permission, $dummyModel);
                    // We don't actually cache a value here, just ensure the key structure is valid
                    break;

                case 'column':
                    // Accessible columns are cached by getAccessibleColumns()
                    // which is called in warmUpCache()
                    break;

                case 'json_attribute':
                    // Accessible JSON paths are cached by getAccessibleJsonPaths()
                    // which is called in warmUpCache()
                    break;

                case 'conditional':
                    // Generate cache key for conditional check
                    $cacheKey = $this->generateConditionalCacheKey($userId, $permission, $dummyModel);
                    // We don't actually cache a value here, just ensure the key structure is valid
                    break;
            }
        } catch (\Throwable $e) {
            // If model instantiation fails, skip this rule
            // This can happen if the model class doesn't exist or requires constructor params
            return;
        }
    }

    /**
     * Warm up row-level rules cache.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param \Illuminate\Support\Collection $rules Collection of row rules
     * @param array<string> &$warmedKeys Reference to warmed keys array
     * @return void
     */
    protected function warmUpRowRules(
        int $userId,
        string $permission,
        $rules,
        array &$warmedKeys
    ): void {
        foreach ($rules as $rule) {
            $modelClass = $rule->rule_config['model'] ?? null;
            if (!$modelClass || !class_exists($modelClass)) {
                continue;
            }

            try {
                // Get a sample of actual model instances to warm up cache with real data
                $models = $modelClass::take(10)->get();

                foreach ($models as $model) {
                    $cacheKey = $this->generateRowCacheKey(
                        $userId,
                        $permission,
                        get_class($model),
                        $model->id
                    );

                    // Skip if already warmed
                    if (isset($warmedKeys[$cacheKey])) {
                        continue;
                    }

                    // Actually evaluate and cache the result
                    $this->canAccessRow($userId, $permission, $model);
                    $warmedKeys[$cacheKey] = true;
                }
            } catch (\Throwable $e) {
                // Skip if model query fails
                continue;
            }
        }
    }

    /**
     * Warm up column-level rules cache.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param \Illuminate\Support\Collection $rules Collection of column rules
     * @param array<string> &$warmedKeys Reference to warmed keys array
     * @return void
     */
    protected function warmUpColumnRules(
        int $userId,
        string $permission,
        $rules,
        array &$warmedKeys
    ): void {
        foreach ($rules as $rule) {
            $modelClass = $rule->rule_config['model'] ?? null;
            if (!$modelClass) {
                continue;
            }

            $cacheKey = $this->generateAccessibleColumnsCacheKey(
                $userId,
                $permission,
                $modelClass
            );

            // Skip if already warmed
            if (isset($warmedKeys[$cacheKey])) {
                continue;
            }

            // Warm up accessible columns cache
            $this->getAccessibleColumns($userId, $permission, $modelClass);
            $warmedKeys[$cacheKey] = true;
        }
    }

    /**
     * Warm up JSON attribute rules cache.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param \Illuminate\Support\Collection $rules Collection of JSON attribute rules
     * @param array<string> &$warmedKeys Reference to warmed keys array
     * @return void
     */
    protected function warmUpJsonAttributeRules(
        int $userId,
        string $permission,
        $rules,
        array &$warmedKeys
    ): void {
        // Group by model and column to avoid duplicates
        $uniqueCombinations = [];

        foreach ($rules as $rule) {
            $modelClass = $rule->rule_config['model'] ?? null;
            $jsonColumn = $rule->rule_config['json_column'] ?? null;

            if ($modelClass && $jsonColumn) {
                $key = "{$modelClass}::{$jsonColumn}";
                $uniqueCombinations[$key] = [
                    'model' => $modelClass,
                    'column' => $jsonColumn,
                ];
            }
        }

        // Warm up cache for each unique combination
        foreach ($uniqueCombinations as $combo) {
            $cacheKey = $this->generateAccessibleJsonPathsCacheKey(
                $userId,
                $permission,
                $combo['model'],
                $combo['column']
            );

            // Skip if already warmed
            if (isset($warmedKeys[$cacheKey])) {
                continue;
            }

            // Warm up accessible JSON paths cache
            $this->getAccessibleJsonPaths(
                $userId,
                $permission,
                $combo['model'],
                $combo['column']
            );
            $warmedKeys[$cacheKey] = true;
        }
    }

    /**
     * Warm up conditional rules cache.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param \Illuminate\Support\Collection $rules Collection of conditional rules
     * @param array<string> &$warmedKeys Reference to warmed keys array
     * @return void
     */
    protected function warmUpConditionalRules(
        int $userId,
        string $permission,
        $rules,
        array &$warmedKeys
    ): void {
        foreach ($rules as $rule) {
            $modelClass = $rule->rule_config['model'] ?? null;
            if (!$modelClass || !class_exists($modelClass)) {
                continue;
            }

            try {
                // Get a sample of actual model instances
                $models = $modelClass::take(10)->get();

                foreach ($models as $model) {
                    $cacheKey = $this->generateConditionalCacheKey(
                        $userId,
                        $permission,
                        get_class($model),
                        $model->id
                    );

                    // Skip if already warmed
                    if (isset($warmedKeys[$cacheKey])) {
                        continue;
                    }

                    // Actually evaluate and cache the result
                    // This is done through canAccessRow which checks conditional rules
                    $this->canAccessRow($userId, $permission, $model);
                    $warmedKeys[$cacheKey] = true;
                }
            } catch (\Throwable $e) {
                // Skip if model query fails
                continue;
            }
        }
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Check if cache is enabled.
     *
     * @return bool
     */
    protected function isCacheEnabled(): bool
    {
        return $this->cacheConfig['enabled'] ?? false;
    }

    /**
     * Get cache TTL for rule type.
     *
     * @param string $ruleType Rule type (row, column, json_attribute, conditional)
     * @return int
     */
    protected function getCacheTtl(string $ruleType): int
    {
        $ttl = $this->fineGrainedConfig['cache']['ttl'][$ruleType] ?? 3600;

        return (int) $ttl;
    }

    /**
     * Get cache key with prefix.
     *
     * @param string $key Cache key
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        $prefix = $this->fineGrainedConfig['cache']['key_prefix'] ?? 'canvastack:rbac:rules:';

        return $prefix . $key;
    }

    /**
     * Generate cache key for row-level rule evaluation.
     *
     * Optimized to use class basename instead of full MD5 hash for better readability
     * and slightly better performance.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @param int|string $modelId Model ID
     * @return string
     */
    protected function generateRowCacheKey(
        int $userId,
        string $permission,
        string $modelClass,
        int|string $modelId
    ): string {
        // Use class basename for shorter, more readable keys
        // This also reduces memory usage and improves cache lookup performance
        $modelShort = class_basename($modelClass);

        return $this->getCacheKey("row:{$userId}:{$permission}:{$modelShort}:{$modelId}");
    }

    /**
     * Generate cache key for column-level rule evaluation.
     *
     * Optimized to use class basename instead of full MD5 hash.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @param string $column Column name
     * @return string
     */
    protected function generateColumnCacheKey(
        int $userId,
        string $permission,
        string $modelClass,
        string $column
    ): string {
        $modelShort = class_basename($modelClass);

        return $this->getCacheKey("column:{$userId}:{$permission}:{$modelShort}:{$column}");
    }

    /**
     * Generate cache key for accessible columns list.
     *
     * Optimized to use class basename instead of full MD5 hash.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @return string
     */
    protected function generateAccessibleColumnsCacheKey(
        int $userId,
        string $permission,
        string $modelClass
    ): string {
        $modelShort = class_basename($modelClass);

        return $this->getCacheKey("columns:{$userId}:{$permission}:{$modelShort}");
    }

    /**
     * Generate cache key for JSON attribute rule evaluation.
     *
     * Optimized to use class basename and shorter path hash.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @param string $jsonColumn JSON column name
     * @param string $path JSON path
     * @return string
     */
    protected function generateJsonAttributeCacheKey(
        int $userId,
        string $permission,
        string $modelClass,
        string $jsonColumn,
        string $path
    ): string {
        $modelShort = class_basename($modelClass);
        // Use crc32 for shorter hash (8 chars vs 32 chars for MD5)
        $pathHash = dechex(crc32($path));

        return $this->getCacheKey("json:{$userId}:{$permission}:{$modelShort}:{$jsonColumn}:{$pathHash}");
    }

    /**
     * Generate cache key for accessible JSON paths list.
     *
     * Optimized to use class basename instead of full MD5 hash.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @param string $jsonColumn JSON column name
     * @return string
     */
    protected function generateAccessibleJsonPathsCacheKey(
        int $userId,
        string $permission,
        string $modelClass,
        string $jsonColumn
    ): string {
        $modelShort = class_basename($modelClass);

        return $this->getCacheKey("json_paths:{$userId}:{$permission}:{$modelShort}:{$jsonColumn}");
    }

    /**
     * Generate cache key for conditional rule evaluation.
     *
     * Optimized to use class basename instead of full MD5 hash.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @param int|string $modelId Model ID
     * @return string
     */
    protected function generateConditionalCacheKey(
        int $userId,
        string $permission,
        string $modelClass,
        int|string $modelId
    ): string {
        $modelShort = class_basename($modelClass);

        return $this->getCacheKey("conditional:{$userId}:{$permission}:{$modelShort}:{$modelId}");
    }

    /**
     * Generate cache key for user override.
     *
     * Optimized to use class basename instead of full MD5 hash.
     *
     * @param int $userId User ID
     * @param int $permissionId Permission ID
     * @param string $modelType Model type
     * @param int|string|null $modelId Model ID
     * @return string
     */
    protected function generateUserOverrideCacheKey(
        int $userId,
        int $permissionId,
        string $modelType,
        int|string|null $modelId = null
    ): string {
        $modelShort = class_basename($modelType);
        $modelIdPart = $modelId !== null ? ":{$modelId}" : '';

        return $this->getCacheKey("override:{$userId}:{$permissionId}:{$modelShort}{$modelIdPart}");
    }

    /**
     * Get cache tags.
     *
     * @param string $tag Tag name
     * @return array<string>
     */
    protected function getCacheTags(string $tag): array
    {
        $tags = $this->fineGrainedConfig['cache']['tags'] ?? [];

        return isset($tags[$tag]) ? [$tags[$tag]] : ['rbac:rules'];
    }

    /**
     * Generate cache tags for rule evaluation.
     *
     * Creates a comprehensive set of cache tags for efficient invalidation.
     * Tags include: base tag, user tag, permission tag, model tag, and rule type tag.
     *
     * Optimized to use class basename for model tags.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @param string $ruleType Rule type (row, column, json_attribute, conditional)
     * @return array<string>
     */
    protected function generateCacheTags(
        int $userId,
        string $permission,
        string $modelClass,
        string $ruleType
    ): array {
        $modelShort = class_basename($modelClass);

        $tags = [
            'rbac:rules',                           // Base tag for all rules
            "rbac:user:{$userId}",                  // User-specific tag
            "rbac:permission:{$permission}",        // Permission-specific tag
            "rbac:model:{$modelShort}",             // Model-specific tag (optimized)
            "rbac:type:{$ruleType}",                // Rule type tag
        ];

        return $tags;
    }

    /**
     * Generate cache tags for user override.
     *
     * Optimized to use class basename for model tags.
     *
     * @param int $userId User ID
     * @param int $permissionId Permission ID
     * @param string $modelType Model type
     * @return array<string>
     */
    protected function generateUserOverrideCacheTags(
        int $userId,
        int $permissionId,
        string $modelType
    ): array {
        $modelShort = class_basename($modelType);

        $tags = [
            'rbac:rules',                           // Base tag
            "rbac:user:{$userId}",                  // User-specific tag
            "rbac:permission_id:{$permissionId}",   // Permission ID tag
            "rbac:model:{$modelShort}",             // Model-specific tag (optimized)
            'rbac:type:override',                   // Override type tag
        ];

        return $tags;
    }

    /**
     * Flush cache by model class.
     *
     * Clears all cached rules for a specific model class.
     * Useful when model structure changes or bulk updates occur.
     *
     * @param string $modelClass Model class name
     * @return bool
     */
    public function clearCacheByModel(string $modelClass): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        try {
            $modelShort = class_basename($modelClass);
            $modelTag = "rbac:model:{$modelShort}";
            Cache::tags([$modelTag])->flush();

            Log::info('Permission rule cache cleared by model', [
                'model_class' => $modelClass,
                'tag' => $modelTag,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear permission rule cache by model', [
                'model_class' => $modelClass,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Flush cache by rule type.
     *
     * Clears all cached rules of a specific type (row, column, json_attribute, conditional).
     * Useful when rule configuration changes for a specific type.
     *
     * @param string $ruleType Rule type
     * @return bool
     */
    public function clearCacheByType(string $ruleType): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        try {
            $typeTag = "rbac:type:{$ruleType}";
            Cache::tags([$typeTag])->flush();

            Log::info('Permission rule cache cleared by type', [
                'rule_type' => $ruleType,
                'tag' => $typeTag,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear permission rule cache by type', [
                'rule_type' => $ruleType,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Flush all permission rule cache.
     *
     * Clears all cached permission rules across all users, permissions, and models.
     * Use with caution as this affects all cached evaluations.
     *
     * @return bool
     */
    public function clearAllCache(): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        try {
            Cache::tags(['rbac:rules'])->flush();

            Log::info('All permission rule cache cleared');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear all permission rule cache', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // =========================================================================
    // Cache Monitoring & Statistics Methods
    // =========================================================================

    /**
     * Get cache statistics.
     *
     * Returns comprehensive cache statistics including hit/miss rates,
     * total operations, and per-rule-type breakdown.
     *
     * @return array<string, mixed>
     */
    public function getCacheStatistics(): array
    {
        if (!$this->isCacheEnabled()) {
            return [
                'enabled' => false,
                'message' => 'Cache is disabled',
            ];
        }

        // Get statistics from cache (stored by logCacheOperation)
        $stats = Cache::get($this->getCacheKey('statistics'), [
            'total_hits' => 0,
            'total_misses' => 0,
            'total_operations' => 0,
            'by_type' => [
                'row' => ['hits' => 0, 'misses' => 0],
                'column' => ['hits' => 0, 'misses' => 0],
                'json_attribute' => ['hits' => 0, 'misses' => 0],
                'conditional' => ['hits' => 0, 'misses' => 0],
            ],
            'last_reset' => now()->toDateTimeString(),
        ]);

        // Calculate hit rate
        $hitRate = $stats['total_operations'] > 0
            ? ($stats['total_hits'] / $stats['total_operations']) * 100
            : 0;

        return [
            'enabled' => true,
            'total_hits' => $stats['total_hits'],
            'total_misses' => $stats['total_misses'],
            'total_operations' => $stats['total_operations'],
            'hit_rate' => round($hitRate, 2),
            'by_type' => $stats['by_type'],
            'last_reset' => $stats['last_reset'],
        ];
    }

    /**
     * Get cache hit rate.
     *
     * Returns the overall cache hit rate as a percentage.
     *
     * @return float Cache hit rate (0-100)
     */
    public function getCacheHitRate(): float
    {
        $stats = $this->getCacheStatistics();

        return $stats['hit_rate'] ?? 0.0;
    }

    /**
     * Get cache statistics for specific rule type.
     *
     * @param string $ruleType Rule type (row, column, json_attribute, conditional)
     * @return array<string, mixed>
     */
    public function getCacheStatisticsByType(string $ruleType): array
    {
        $stats = $this->getCacheStatistics();

        if (!isset($stats['by_type'][$ruleType])) {
            return [
                'hits' => 0,
                'misses' => 0,
                'operations' => 0,
                'hit_rate' => 0.0,
            ];
        }

        $typeStats = $stats['by_type'][$ruleType];
        $operations = $typeStats['hits'] + $typeStats['misses'];
        $hitRate = $operations > 0
            ? ($typeStats['hits'] / $operations) * 100
            : 0;

        return [
            'hits' => $typeStats['hits'],
            'misses' => $typeStats['misses'],
            'operations' => $operations,
            'hit_rate' => round($hitRate, 2),
        ];
    }

    /**
     * Reset cache statistics.
     *
     * Clears all cache statistics and resets counters to zero.
     *
     * @return bool True if statistics were reset successfully
     */
    public function resetCacheStatistics(): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        try {
            $stats = [
                'total_hits' => 0,
                'total_misses' => 0,
                'total_operations' => 0,
                'by_type' => [
                    'row' => ['hits' => 0, 'misses' => 0],
                    'column' => ['hits' => 0, 'misses' => 0],
                    'json_attribute' => ['hits' => 0, 'misses' => 0],
                    'conditional' => ['hits' => 0, 'misses' => 0],
                ],
                'last_reset' => now()->toDateTimeString(),
            ];

            // Use put with very long TTL instead of forever (works better with array cache)
            Cache::put($this->getCacheKey('statistics'), $stats, 86400 * 365); // 1 year

            Log::info('Permission rule cache statistics reset');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to reset cache statistics', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Log cache operation (hit or miss).
     *
     * Records cache hit/miss for monitoring and statistics.
     * This method is called internally by getCachedEvaluation.
     *
     * @param bool $hit True for cache hit, false for cache miss
     * @param string $ruleType Rule type (row, column, json_attribute, conditional)
     * @return void
     */
    protected function logCacheOperation(bool $hit, string $ruleType): void
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        try {
            // Get current statistics
            $statsKey = $this->getCacheKey('statistics');
            $stats = Cache::get($statsKey, [
                'total_hits' => 0,
                'total_misses' => 0,
                'total_operations' => 0,
                'by_type' => [
                    'row' => ['hits' => 0, 'misses' => 0],
                    'column' => ['hits' => 0, 'misses' => 0],
                    'json_attribute' => ['hits' => 0, 'misses' => 0],
                    'conditional' => ['hits' => 0, 'misses' => 0],
                ],
                'last_reset' => now()->toDateTimeString(),
            ]);

            // Update statistics
            if ($hit) {
                $stats['total_hits']++;
                if (isset($stats['by_type'][$ruleType])) {
                    $stats['by_type'][$ruleType]['hits']++;
                }
            } else {
                $stats['total_misses']++;
                if (isset($stats['by_type'][$ruleType])) {
                    $stats['by_type'][$ruleType]['misses']++;
                }
            }

            $stats['total_operations']++;

            // Store updated statistics (use put with very long TTL instead of forever)
            // This works better with array cache driver in tests
            Cache::put($statsKey, $stats, 86400 * 365); // 1 year

            // Log to application log (only in debug mode to avoid log spam)
            if (config('app.debug')) {
                Log::debug('Permission rule cache operation', [
                    'hit' => $hit,
                    'type' => $ruleType,
                    'total_operations' => $stats['total_operations'],
                    'hit_rate' => $stats['total_operations'] > 0
                        ? round(($stats['total_hits'] / $stats['total_operations']) * 100, 2)
                        : 0,
                ]);
            }
        } catch (\Exception $e) {
            // Don't throw - logging should be non-blocking
            // Just log the error
            Log::error('Failed to log cache operation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get cache size estimate.
     *
     * Returns an estimate of the cache size (number of cached items).
     * Note: This is an approximation and may not be 100% accurate.
     *
     * @return int Estimated number of cached items
     */
    public function getCacheSize(): int
    {
        if (!$this->isCacheEnabled()) {
            return 0;
        }

        // This is a simple implementation that counts cache keys
        // In production, you might want to use Redis INFO or similar
        // to get more accurate cache size information

        try {
            // Get all cache keys with our prefix
            // Note: This implementation depends on cache driver
            // For Redis, you could use SCAN command
            // For now, we'll return the total operations as a proxy
            $stats = $this->getCacheStatistics();

            return $stats['total_operations'] ?? 0;
        } catch (\Exception $e) {
            Log::error('Failed to get cache size', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Log cache hit/miss rate periodically.
     *
     * This method can be called periodically (e.g., via scheduled task)
     * to log cache performance metrics for monitoring.
     *
     * @return void
     */
    public function logCachePerformance(): void
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        $stats = $this->getCacheStatistics();

        Log::info('Permission rule cache performance', [
            'total_hits' => $stats['total_hits'],
            'total_misses' => $stats['total_misses'],
            'total_operations' => $stats['total_operations'],
            'hit_rate' => $stats['hit_rate'] . '%',
            'by_type' => array_map(function ($typeStats) {
                $operations = $typeStats['hits'] + $typeStats['misses'];
                $hitRate = $operations > 0
                    ? round(($typeStats['hits'] / $operations) * 100, 2)
                    : 0;

                return [
                    'hits' => $typeStats['hits'],
                    'misses' => $typeStats['misses'],
                    'operations' => $operations,
                    'hit_rate' => $hitRate . '%',
                ];
            }, $stats['by_type']),
            'last_reset' => $stats['last_reset'],
        ]);
    }

    // =========================================================================
    // Configuration & State Methods
    // =========================================================================

    /**
     * Check if fine-grained permissions are enabled.
     *
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return $this->fineGrainedConfig['enabled'] ?? false;
    }

    /**
     * Check if specific rule type is enabled.
     *
     * @param string $ruleType Rule type (row_level, column_level, json_attribute, conditional)
     * @return bool
     */
    protected function isRuleTypeEnabled(string $ruleType): bool
    {
        return $this->fineGrainedConfig[$ruleType]['enabled'] ?? false;
    }

    /**
     * Check user override for specific model.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @param int|null $modelId Model ID
     * @return bool|null True if allowed, false if denied, null if no override
     */
    protected function checkUserOverride(
        int $userId,
        string $permission,
        string $modelClass,
        ?int $modelId
    ): ?bool {
        // Get permission ID
        $permissionObj = $this->permissionManager->findByName($permission);
        if (!$permissionObj) {
            return null;
        }

        return $this->checkUserOverrideOptimized($userId, $permissionObj->id, $modelClass, $modelId);
    }

    /**
     * Check user override using permission ID (optimized version).
     *
     * @param int $userId User ID
     * @param int $permissionId Permission ID
     * @param string $modelClass Model class name
     * @param int|null $modelId Model ID
     * @return bool|null True if allowed, false if denied, null if no override
     */
    protected function checkUserOverrideOptimized(
        int $userId,
        int $permissionId,
        string $modelClass,
        ?int $modelId
    ): ?bool {
        // Single query to check both specific and general overrides
        // Order by model_id DESC NULLS LAST to prioritize specific overrides
        $override = UserPermissionOverride::where('user_id', $userId)
            ->where('permission_id', $permissionId)
            ->where('model_type', $modelClass)
            ->where(function ($query) use ($modelId) {
                if ($modelId !== null) {
                    $query->where('model_id', $modelId)
                        ->orWhereNull('model_id');
                } else {
                    $query->whereNull('model_id');
                }
            })
            ->whereNull('field_name') // Row-level override
            ->orderByRaw('model_id IS NULL ASC') // Specific overrides first
            ->first();

        if ($override) {
            return $override->allowed;
        }

        return null;
    }

    /**
     * Check user override for specific column.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @param int|null $modelId Model ID
     * @param string $column Column name
     * @return bool|null True if allowed, false if denied, null if no override
     */
    protected function checkColumnOverride(
        int $userId,
        string $permission,
        string $modelClass,
        ?int $modelId,
        string $column
    ): ?bool {
        // Get permission ID
        $permissionObj = $this->permissionManager->findByName($permission);
        if (!$permissionObj) {
            return null;
        }

        return $this->checkColumnOverrideOptimized($userId, $permissionObj->id, $modelClass, $modelId, $column);
    }

    /**
     * Check column override using permission ID (optimized version).
     *
     * @param int $userId User ID
     * @param int $permissionId Permission ID
     * @param string $modelClass Model class name
     * @param int|null $modelId Model ID
     * @param string $column Column name
     * @return bool|null True if allowed, false if denied, null if no override
     */
    protected function checkColumnOverrideOptimized(
        int $userId,
        int $permissionId,
        string $modelClass,
        ?int $modelId,
        string $column
    ): ?bool {
        // Single query to check both specific and general overrides
        $override = UserPermissionOverride::where('user_id', $userId)
            ->where('permission_id', $permissionId)
            ->where('model_type', $modelClass)
            ->where(function ($query) use ($modelId) {
                if ($modelId !== null) {
                    $query->where('model_id', $modelId)
                        ->orWhereNull('model_id');
                } else {
                    $query->whereNull('model_id');
                }
            })
            ->where('field_name', $column)
            ->orderByRaw('model_id IS NULL ASC') // Specific overrides first
            ->first();

        if ($override) {
            return $override->allowed;
        }

        return null;
    }

    /**
     * Check user override for specific JSON attribute.
     *
     * @param int $userId User ID
     * @param string $permission Permission name
     * @param string $modelClass Model class name
     * @param int|null $modelId Model ID
     * @param string $jsonColumn JSON column name
     * @param string $path JSON path
     * @return bool|null True if allowed, false if denied, null if no override
     */
    protected function checkJsonAttributeOverride(
        int $userId,
        string $permission,
        string $modelClass,
        ?int $modelId,
        string $jsonColumn,
        string $path
    ): ?bool {
        // Get permission ID
        $permissionObj = $this->permissionManager->findByName($permission);
        if (!$permissionObj) {
            return null;
        }

        return $this->checkJsonAttributeOverrideOptimized($userId, $permissionObj->id, $modelClass, $modelId, $jsonColumn, $path);
    }

    /**
     * Check JSON attribute override using permission ID (optimized version).
     *
     * @param int $userId User ID
     * @param int $permissionId Permission ID
     * @param string $modelClass Model class name
     * @param int|null $modelId Model ID
     * @param string $jsonColumn JSON column name
     * @param string $path JSON path
     * @return bool|null True if allowed, false if denied, null if no override
     */
    protected function checkJsonAttributeOverrideOptimized(
        int $userId,
        int $permissionId,
        string $modelClass,
        ?int $modelId,
        string $jsonColumn,
        string $path
    ): ?bool {
        // Build field name for JSON attribute (e.g., "metadata.seo.title")
        $fieldName = $jsonColumn . '.' . $path;

        // Single query to check both specific and general overrides
        $override = UserPermissionOverride::where('user_id', $userId)
            ->where('permission_id', $permissionId)
            ->where('model_type', $modelClass)
            ->where(function ($query) use ($modelId) {
                if ($modelId !== null) {
                    $query->where('model_id', $modelId)
                        ->orWhereNull('model_id');
                } else {
                    $query->whereNull('model_id');
                }
            })
            ->where('field_name', $fieldName)
            ->orderByRaw('model_id IS NULL ASC') // Specific overrides first
            ->first();

        if ($override) {
            return $override->allowed;
        }

        return null;
    }

    /**
     * Check if a JSON path is accessible based on allowed/denied paths.
     *
     * Supports wildcard matching (e.g., "seo.*" matches "seo.title", "seo.description").
     *
     * @param string $path JSON path to check
     * @param array<string, array<string>> $accessiblePaths Array with 'allowed' and 'denied' keys
     * @return bool
     */
    protected function isPathAccessible(string $path, array $accessiblePaths): bool
    {
        // If no rules defined, allow all
        if (empty($accessiblePaths)) {
            return true;
        }

        $allowedPaths = $accessiblePaths['allowed'] ?? [];
        $deniedPaths = $accessiblePaths['denied'] ?? [];

        // If path is explicitly denied, deny access
        if ($this->matchesAnyPattern($path, $deniedPaths)) {
            return false;
        }

        // If we have allowed paths, check if path matches any
        if (!empty($allowedPaths)) {
            return $this->matchesAnyPattern($path, $allowedPaths);
        }

        // If no allowed paths specified but we have denied paths, allow by default
        return true;
    }

    /**
     * Check if a path matches any pattern in the list.
     *
     * Supports wildcard matching (e.g., "seo.*" matches "seo.title").
     * Optimized with global static caching to avoid repeated pattern matching.
     *
     * @param string $path Path to check
     * @param array<string> $patterns Patterns to match against
     * @return bool
     */
    protected function matchesAnyPattern(string $path, array $patterns): bool
    {
        // Generate cache key for this path + patterns combination
        $cacheKey = md5($path . '|' . implode(',', $patterns));

        // Check global static cache first (OPTIMIZATION)
        if (isset(self::$globalPatternCache[$cacheKey])) {
            return self::$globalPatternCache[$cacheKey];
        }

        // Compile patterns once for this set
        $compiledPatterns = $this->compilePatterns($patterns);

        // Fast path: check exact matches first (no regex needed)
        if (isset($compiledPatterns['exact'][$path])) {
            self::$globalPatternCache[$cacheKey] = true;

            return true;
        }

        // Check wildcard patterns
        foreach ($compiledPatterns['wildcards'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '.')) {
                self::$globalPatternCache[$cacheKey] = true;

                return true;
            }
        }

        // No match found
        self::$globalPatternCache[$cacheKey] = false;

        return false;
    }

    /**
     * Compile patterns into optimized data structures for faster matching.
     *
     * @param array<string> $patterns Patterns to compile
     * @return array{exact: array<string, true>, wildcards: array<int, string>}
     */
    protected function compilePatterns(array $patterns): array
    {
        // Generate cache key for this pattern set
        $cacheKey = md5(implode(',', $patterns));

        // Check global static cache first (OPTIMIZATION)
        if (isset(self::$globalCompiledPatternCache[$cacheKey])) {
            return self::$globalCompiledPatternCache[$cacheKey];
        }

        $exact = [];
        $wildcards = [];

        foreach ($patterns as $pattern) {
            if (str_ends_with($pattern, '.*')) {
                // Wildcard pattern - store prefix without ".*"
                $wildcards[] = substr($pattern, 0, -2);
            } else {
                // Exact match - use array key for O(1) lookup
                $exact[$pattern] = true;
            }
        }

        $compiled = [
            'exact' => $exact,
            'wildcards' => $wildcards,
        ];

        // Cache compiled patterns in global static cache (OPTIMIZATION)
        self::$globalCompiledPatternCache[$cacheKey] = $compiled;

        return $compiled;
    }

    /**
     * Check if a path matches a pattern.
     *
     * Supports wildcard matching (e.g., "seo.*" matches "seo.title").
     *
     * @param string $path Path to check
     * @param string $pattern Pattern to match against
     * @return bool
     */
    protected function matchesPattern(string $path, string $pattern): bool
    {
        // Exact match
        if ($path === $pattern) {
            return true;
        }

        // Wildcard match (e.g., "seo.*" matches "seo.title", "seo.description")
        if (str_ends_with($pattern, '.*')) {
            $prefix = substr($pattern, 0, -2); // Remove ".*"

            // Check if path starts with prefix followed by separator
            if ($path === $prefix || str_starts_with($path, $prefix . '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear path matching cache.
     *
     * This should be called when permission rules are modified.
     *
     * @return void
     */
    public function clearPathMatchCache(): void
    {
        $this->pathMatchCache = [];
        $this->compiledPatternCache = [];
    }

    /**
     * Evaluate row conditions against model.
     *
     * @param object $model Model instance
     * @param array<string, mixed> $conditions Conditions to evaluate
     * @param string $operator Logical operator (AND/OR)
     * @return bool
     */
    protected function evaluateRowConditions(
        object $model,
        array $conditions,
        string $operator
    ): bool {
        if (empty($conditions)) {
            return true;
        }

        // Resolve template variables in conditions
        $resolvedConditions = $this->templateResolver->resolveConditions($conditions);

        $results = [];

        foreach ($resolvedConditions as $field => $expectedValue) {
            // Get actual value from model
            $actualValue = $this->getModelValue($model, $field);

            // Compare values
            $results[] = $actualValue === $expectedValue;
        }

        // Apply operator
        if ($operator === 'AND') {
            return !in_array(false, $results, true);
        } else {
            return in_array(true, $results, true);
        }
    }

    /**
     * Get value from model by field name.
     *
     * @param object $model Model instance
     * @param string $field Field name (supports dot notation for relationships)
     * @return mixed
     */
    protected function getModelValue(object $model, string $field): mixed
    {
        // Support dot notation for relationships
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $value = $model;

            foreach ($parts as $part) {
                if (is_object($value)) {
                    $value = $value->{$part} ?? null;
                } else {
                    return null;
                }
            }

            return $value;
        }

        // Direct property access
        return $model->{$field} ?? null;
    }

    /**
     * Apply conditional rule to query.
     *
     * Parses conditional expressions and converts them to SQL WHERE clauses.
     * Supports: ===, !==, >, <, >=, <=, AND, OR, and parentheses grouping
     *
     * @param Builder $query Query builder
     * @param string $condition Conditional expression
     * @return void
     */
    protected function applyConditionalToQuery(Builder $query, string $condition): void
    {
        // Trim whitespace
        $condition = trim($condition);

        // Only remove outer parentheses if they wrap the ENTIRE expression
        // Check if first paren's matching closing paren is at the end
        if (str_starts_with($condition, '(') && str_ends_with($condition, ')')) {
            $depth = 0;
            $firstParenClosesAt = -1;

            for ($i = 0; $i < strlen($condition); $i++) {
                if ($condition[$i] === '(') {
                    $depth++;
                } elseif ($condition[$i] === ')') {
                    $depth--;
                    if ($depth === 0 && $firstParenClosesAt === -1) {
                        $firstParenClosesAt = $i;
                        break; // Found where the first paren closes
                    }
                }
            }

            // Only remove if the first paren closes at the very end
            if ($firstParenClosesAt === strlen($condition) - 1) {
                $condition = substr($condition, 1, -1);
            }
        }

        // Check if condition contains OR at the top level (not inside parentheses)
        $orParts = $this->splitByTopLevelOperator($condition, 'OR');

        if (count($orParts) > 1) {
            // Multiple OR conditions - wrap in OR group
            $query->where(function ($q) use ($orParts) {
                foreach ($orParts as $orPart) {
                    $this->applyConditionPart($q, trim($orPart), 'or');
                }
            });
        } else {
            // No top-level OR, check for AND
            $andParts = $this->splitByTopLevelOperator($condition, 'AND');

            if (count($andParts) > 1) {
                // Multiple AND conditions
                foreach ($andParts as $andPart) {
                    $this->applyConditionPart($query, trim($andPart), 'and');
                }
            } else {
                // Single condition
                $this->applySimpleCondition($query, $condition, 'and');
            }
        }
    }

    /**
     * Split condition by top-level operator (not inside parentheses).
     *
     * @param string $condition Condition string
     * @param string $operator Operator to split by ('AND' or 'OR')
     * @return array Parts of the condition
     */
    protected function splitByTopLevelOperator(string $condition, string $operator): array
    {
        $parts = [];
        $currentPart = '';
        $depth = 0;
        $length = strlen($condition);
        $operatorLength = strlen($operator);

        for ($i = 0; $i < $length; $i++) {
            $char = $condition[$i];

            if ($char === '(') {
                $depth++;
                $currentPart .= $char;
            } elseif ($char === ')') {
                $depth--;
                $currentPart .= $char;
            } elseif ($depth === 0) {
                // Check if we're at the operator
                $remaining = substr($condition, $i);
                if (preg_match('/^\s+' . preg_quote($operator, '/') . '\s+/i', $remaining, $matches)) {
                    // Found operator at top level
                    $parts[] = $currentPart;
                    $currentPart = '';
                    $i += strlen($matches[0]) - 1; // Skip the operator
                } else {
                    $currentPart .= $char;
                }
            } else {
                $currentPart .= $char;
            }
        }

        // Add the last part
        if ($currentPart !== '') {
            $parts[] = $currentPart;
        }

        return array_filter(array_map('trim', $parts));
    }

    /**
     * Apply a condition part (which may be grouped or simple).
     *
     * @param Builder $query Query builder
     * @param string $part Condition part
     * @param string $boolean Boolean operator ('and' or 'or')
     * @return void
     */
    protected function applyConditionPart(Builder $query, string $part, string $boolean = 'and'): void
    {
        // Remove outer parentheses if present
        if (str_starts_with($part, '(') && str_ends_with($part, ')')) {
            $part = substr($part, 1, -1);
        }

        // Check if this part contains AND
        $andParts = $this->splitByTopLevelOperator($part, 'AND');

        if (count($andParts) > 1) {
            // Multiple AND conditions - wrap in closure
            if ($boolean === 'or') {
                $query->orWhere(function ($q) use ($andParts) {
                    foreach ($andParts as $andPart) {
                        $this->applySimpleCondition($q, trim($andPart), 'and');
                    }
                });
            } else {
                $query->where(function ($q) use ($andParts) {
                    foreach ($andParts as $andPart) {
                        $this->applySimpleCondition($q, trim($andPart), 'and');
                    }
                });
            }
        } else {
            // Single condition
            $this->applySimpleCondition($query, $part, $boolean);
        }
    }

    /**
     * Apply simple condition to query.
     *
     * @param Builder $query Query builder
     * @param string $condition Simple condition (e.g., "status === 'pending'")
     * @param string $boolean Boolean operator ('and' or 'or')
     * @return void
     */
    protected function applySimpleCondition(Builder $query, string $condition, string $boolean = 'and'): void
    {
        // Parse condition: field operator value
        // Supported operators: ===, !==, >, <, >=, <=

        $operators = ['===', '!==', '>=', '<=', '>', '<'];
        $operator = null;
        $field = null;
        $value = null;

        foreach ($operators as $op) {
            if (strpos($condition, $op) !== false) {
                [$field, $value] = explode($op, $condition, 2);
                $operator = $op;
                break;
            }
        }

        if (!$operator || !$field || $value === null) {
            // Can't parse condition, skip it
            return;
        }

        $field = trim($field);
        $value = trim($value);

        // Remove quotes from value
        $value = trim($value, "'\"");

        // Convert operator to SQL
        $sqlOperator = match ($operator) {
            '===' => '=',
            '!==' => '!=',
            '>=' => '>=',
            '<=' => '<=',
            '>' => '>',
            '<' => '<',
            default => '=',
        };

        // Apply to query
        if ($boolean === 'or') {
            $query->orWhere($field, $sqlOperator, $value);
        } else {
            $query->where($field, $sqlOperator, $value);
        }
    }

    /**
     * Clear rule cache for specific permission.
     *
     * @param int $permissionId Permission ID
     * @return void
     */
    protected function clearRuleCacheForPermission(int $permissionId): void
    {
        if (!$this->isCacheEnabled()) {
            // Still clear in-memory caches even if Redis cache is disabled
            $this->clearPathMatchCache();

            return;
        }

        // Clear all caches tagged with this permission
        $tags = $this->getCacheTags('permission');
        Cache::tags($tags)->flush();

        // Clear in-memory path matching cache
        $this->clearPathMatchCache();

        // Log cache invalidation
        Log::info('Permission rule cache invalidated', [
            'permission_id' => $permissionId,
            'tags' => $tags,
        ]);
    }

    /**
     * Clear user override cache for specific user and permission.
     *
     * @param int $userId User ID
     * @param int $permissionId Permission ID
     * @return void
     */
    protected function clearUserOverrideCache(int $userId, int $permissionId): void
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        // Get permission name
        $permission = $this->permissionManager->find($permissionId);
        if (!$permission) {
            return;
        }

        // Clear all caches for this user and permission
        // This includes row-level, column-level, and JSON attribute caches
        $patterns = [
            "can_access_row:{$userId}:{$permission->name}:*",
            "can_access_column:{$userId}:{$permission->name}:*",
            "can_access_json:{$userId}:{$permission->name}:*",
            "accessible_columns:{$userId}:{$permission->name}:*",
            "accessible_json_paths:{$userId}:{$permission->name}:*",
        ];

        foreach ($patterns as $pattern) {
            // Note: This is a simple implementation
            // In production, you might want to use Redis SCAN or similar
            // to find and delete keys matching the pattern
            $cacheKey = $this->getCacheKey($pattern);
            Cache::forget($cacheKey);
        }

        // Also clear tagged cache
        $tags = $this->getCacheTags('user');
        Cache::tags($tags)->flush();
    }

    /**
     * Resolve template variables in a condition string.
     *
     * @param string $condition Condition string with template variables
     * @return string Resolved condition string
     */
    protected function resolveTemplateVariablesInCondition(string $condition): string
    {
        return preg_replace_callback('/\{\{(.+?)\}\}/', function ($matches) {
            $varName = trim($matches[1]);
            $value = $this->templateResolver->resolve('{{' . $varName . '}}');

            // If value is still in template format, it wasn't resolved
            if (is_string($value) && preg_match('/^\{\{.+?\}\}$/', $value)) {
                return $matches[0]; // Return original
            }

            // Convert to string representation for SQL
            if (is_string($value)) {
                return "'" . addslashes($value) . "'";
            }

            if (is_numeric($value)) {
                return (string) $value;
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if ($value === null) {
                return 'null';
            }

            return $matches[0]; // Return original if can't resolve
        }, $condition);
    }

    /**
     * Get permission ID from cache or database.
     *
     * This method significantly improves performance by caching permission name → ID mappings.
     *
     * @param string $permission Permission name
     * @return int|null Permission ID or null if not found
     */
    protected function getPermissionId(string $permission): ?int
    {
        // Check static cache first (OPTIMIZATION)
        if (isset(self::$permissionIdCache[$permission])) {
            return self::$permissionIdCache[$permission];
        }

        // Lookup permission
        $permissionObj = $this->permissionManager->findByName($permission);

        if ($permissionObj) {
            // Cache the ID for future lookups (OPTIMIZATION)
            self::$permissionIdCache[$permission] = $permissionObj->id;

            return $permissionObj->id;
        }

        return null;
    }

    /**
     * Get cached model class name to avoid repeated get_class() calls.
     *
     * @param object $model Model instance
     * @return string Model class name
     */
    protected function getModelClass(object $model): string
    {
        $objectId = spl_object_id($model);

        // Check static cache first (OPTIMIZATION)
        if (isset(self::$modelClassCache[$objectId])) {
            return self::$modelClassCache[$objectId];
        }

        $className = get_class($model);

        // Cache for future lookups (OPTIMIZATION)
        self::$modelClassCache[$objectId] = $className;

        return $className;
    }

    /**
     * Clear all static caches.
     *
     * This should be called when permission rules are modified or during testing.
     *
     * @return void
     */
    public static function clearStaticCaches(): void
    {
        self::$globalPatternCache = [];
        self::$globalCompiledPatternCache = [];
        self::$permissionIdCache = [];
        self::$modelClassCache = [];
    }
}
