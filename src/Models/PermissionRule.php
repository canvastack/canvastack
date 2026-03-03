<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PermissionRule Model.
 *
 * Represents a fine-grained permission rule in the RBAC system.
 * Supports row-level, column-level, JSON attribute, and conditional permissions.
 *
 * @property int $id
 * @property int $permission_id
 * @property string $rule_type
 * @property array $rule_config
 * @property int $priority
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Permission $permission
 */
class PermissionRule extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permission_rules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'permission_id',
        'rule_type',
        'rule_config',
        'priority',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rule_config' => 'array',
        'priority' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Rule type constants.
     */
    public const TYPE_ROW = 'row';
    public const TYPE_COLUMN = 'column';
    public const TYPE_JSON_ATTRIBUTE = 'json_attribute';
    public const TYPE_CONDITIONAL = 'conditional';

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Only register observers if not in testing environment
        if (!app()->runningUnitTests()) {
            // Clear cache when rule is created, updated, or deleted
            static::created(function ($rule) {
                static::invalidateCache($rule);
            });

            static::updated(function ($rule) {
                static::invalidateCache($rule);
            });

            static::deleted(function ($rule) {
                static::invalidateCache($rule);
            });
        }
    }

    /**
     * Invalidate cache for this rule.
     */
    protected static function invalidateCache(PermissionRule $rule): void
    {
        // Get cache config
        $cacheEnabled = config('canvastack-rbac.cache.enabled', true);
        
        if (!$cacheEnabled) {
            return;
        }

        // Clear cache tags for this permission
        $tags = ['canvastack', 'rbac', 'rules', 'permission'];
        \Illuminate\Support\Facades\Cache::tags($tags)->flush();

        // Log cache invalidation
        \Illuminate\Support\Facades\Log::info('Permission rule cache invalidated', [
            'permission_id' => $rule->permission_id,
            'rule_type' => $rule->rule_type,
            'tags' => $tags,
        ]);
    }

    /**
     * Get the permission that owns this rule.
     *
     * @return BelongsTo
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    /**
     * Scope a query to filter by permission.
     *
     * @param Builder $query
     * @param int $permissionId
     * @return Builder
     */
    public function scopeForPermission(Builder $query, int $permissionId): Builder
    {
        return $query->where('permission_id', $permissionId);
    }

    /**
     * Scope a query to filter by rule type.
     *
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope a query to order by priority.
     *
     * @param Builder $query
     * @param string $direction
     * @return Builder
     */
    public function scopeByPriority(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('priority', $direction);
    }

    /**
     * Evaluate the rule against a model instance.
     *
     * @param object $model
     * @param string|null $field
     * @return bool
     */
    public function evaluate(object $model, ?string $field = null): bool
    {
        return match ($this->rule_type) {
            self::TYPE_ROW => $this->evaluateRowRule($model),
            self::TYPE_COLUMN => $this->evaluateColumnRule($model, $field),
            self::TYPE_JSON_ATTRIBUTE => $this->evaluateJsonAttributeRule($model, $field),
            self::TYPE_CONDITIONAL => $this->evaluateConditionalRule($model),
            default => false,
        };
    }

    /**
     * Evaluate row-level rule.
     *
     * Checks if the model instance matches all conditions defined in the rule.
     * Supports template variables like {{auth.id}}.
     *
     * @param object $model
     * @return bool
     */
    protected function evaluateRowRule(object $model): bool
    {
        $config = $this->rule_config;

        // Get conditions and operator
        $conditions = $config['conditions'] ?? [];
        $operator = strtoupper($config['operator'] ?? 'AND');

        if (empty($conditions)) {
            return true; // No conditions = allow access
        }

        $results = [];
        $hasUnresolvedVariables = false;

        foreach ($conditions as $field => $expectedValue) {
            // Resolve template variables
            $resolvedValue = $this->resolveTemplateVariable($expectedValue);

            // Check if variable was resolved (if it's still a template string, it wasn't resolved)
            if (is_string($resolvedValue) && preg_match('/^\{\{.+?\}\}$/', $resolvedValue)) {
                $hasUnresolvedVariables = true;
                continue; // Skip unresolved variables
            }

            // Get actual value from model
            $actualValue = $this->getModelValue($model, $field);

            // Compare values
            $results[] = $this->compareValues($actualValue, $resolvedValue);
        }

        // If all variables were unresolved, return true (graceful degradation)
        if ($hasUnresolvedVariables && empty($results)) {
            return true;
        }

        // Apply operator logic
        if ($operator === 'OR') {
            return in_array(true, $results, true);
        }

        // Default to AND
        return !in_array(false, $results, true);
    }

    /**
     * Evaluate column-level rule.
     *
     * Checks if the specified field is in the allowed columns list
     * or not in the denied columns list.
     *
     * @param object $model
     * @param string|null $field
     * @return bool
     */
    protected function evaluateColumnRule(object $model, ?string $field): bool
    {
        if ($field === null) {
            return false; // Field is required for column-level checks
        }

        $config = $this->rule_config;
        $mode = $config['mode'] ?? 'whitelist';

        if ($mode === 'whitelist') {
            // Whitelist mode: only allowed columns are accessible
            $allowedColumns = $config['allowed_columns'] ?? [];

            return in_array($field, $allowedColumns, true);
        }

        // Blacklist mode: all columns except denied are accessible
        $deniedColumns = $config['denied_columns'] ?? [];

        return !in_array($field, $deniedColumns, true);
    }

    /**
     * Evaluate JSON attribute rule.
     *
     * Checks if the specified JSON path is in the allowed paths
     * or not in the denied paths. Supports wildcard matching.
     *
     * @param object $model
     * @param string|null $field
     * @return bool
     */
    protected function evaluateJsonAttributeRule(object $model, ?string $field): bool
    {
        if ($field === null) {
            return false; // Field is required for JSON attribute checks
        }

        $config = $this->rule_config;
        $jsonColumn = $config['json_column'] ?? 'metadata';
        $allowedPaths = $config['allowed_paths'] ?? [];
        $deniedPaths = $config['denied_paths'] ?? [];
        $pathSeparator = $config['path_separator'] ?? '.';

        // Extract the path within the JSON column
        // If field is "metadata.seo.title", extract "seo.title"
        $path = $field;
        if (str_starts_with($field, $jsonColumn . $pathSeparator)) {
            $path = substr($field, strlen($jsonColumn) + strlen($pathSeparator));
        }

        // Check denied paths first (deny takes precedence)
        foreach ($deniedPaths as $deniedPath) {
            if ($this->matchesPath($path, $deniedPath, $pathSeparator)) {
                return false;
            }
        }

        // If no allowed paths specified, allow all (except denied)
        if (empty($allowedPaths)) {
            return true;
        }

        // Check allowed paths
        foreach ($allowedPaths as $allowedPath) {
            if ($this->matchesPath($path, $allowedPath, $pathSeparator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate conditional rule.
     *
     * Evaluates a conditional expression against the model instance.
     * Supports comparison operators and logical operators.
     *
     * @param object $model
     * @return bool
     */
    protected function evaluateConditionalRule(object $model): bool
    {
        $config = $this->rule_config;
        $condition = $config['condition'] ?? '';

        if (empty($condition)) {
            return true; // No condition = allow access
        }

        // Resolve template variables in condition
        $resolvedCondition = $this->resolveTemplateVariablesInCondition($condition);

        // Check if there are unresolved variables
        if (str_contains($resolvedCondition, '{{UNRESOLVED}}')) {
            // Unresolved variables - deny access for security
            return false;
        }

        // Parse and evaluate the condition
        return $this->evaluateConditionExpression($resolvedCondition, $model);
    }

    /**
     * Resolve template variable.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function resolveTemplateVariable($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        // Check if value contains template variable pattern {{variable}}
        if (preg_match('/^\{\{(.+?)\}\}$/', $value, $matches)) {
            $varName = trim($matches[1]);

            try {
                // Check if auth is available
                if (!function_exists('auth')) {
                    return $value; // Return original if auth function not available
                }

                $auth = auth();
                if (!$auth->check()) {
                    return $value; // Return original if not authenticated
                }

                $user = $auth->user();

                // Resolve common template variables
                return match ($varName) {
                    'auth.id' => $auth->id(),
                    'auth.role' => $user?->role,
                    'auth.department' => $user?->department_id,
                    'auth.email' => $user?->email,
                    'auth.organization' => $user?->organization_id,
                    'auth.team' => $user?->team_id,
                    default => $value, // Return original if not recognized
                };
            } catch (\Throwable $e) {
                // If auth is not available (e.g., in tests), return original value
                return $value;
            }
        }

        return $value;
    }

    /**
     * Get value from model by field name.
     *
     * @param object $model
     * @param string $field
     * @return mixed
     */
    protected function getModelValue(object $model, string $field)
    {
        // Try property access
        if (property_exists($model, $field)) {
            return $model->$field;
        }

        // Try method access (getter)
        if (method_exists($model, $field)) {
            return $model->$field();
        }

        // Try array access (for arrays or ArrayAccess objects)
        if (is_array($model) || $model instanceof \ArrayAccess) {
            return $model[$field] ?? null;
        }

        return null;
    }

    /**
     * Compare two values for equality.
     *
     * @param mixed $actual
     * @param mixed $expected
     * @return bool
     */
    protected function compareValues($actual, $expected): bool
    {
        // Handle null comparisons
        if ($actual === null || $expected === null) {
            return $actual === $expected;
        }

        // Loose comparison for numeric strings
        if (is_numeric($actual) && is_numeric($expected)) {
            return (float) $actual === (float) $expected;
        }

        // Strict comparison for everything else
        return $actual === $expected;
    }

    /**
     * Check if a path matches a pattern (supports wildcards).
     *
     * @param string $path
     * @param string $pattern
     * @param string $separator
     * @return bool
     */
    protected function matchesPath(string $path, string $pattern, string $separator): bool
    {
        // Exact match
        if ($path === $pattern) {
            return true;
        }

        // Wildcard match (e.g., "seo.*" matches "seo.title", "seo.description")
        if (str_ends_with($pattern, '.*')) {
            $prefix = substr($pattern, 0, -2);

            return str_starts_with($path, $prefix . $separator) || $path === $prefix;
        }

        return false;
    }

    /**
     * Resolve template variables in condition string.
     *
     * @param string $condition
     * @return string
     */
    protected function resolveTemplateVariablesInCondition(string $condition): string
    {
        $result = preg_replace_callback('/\{\{(.+?)\}\}/', function ($matches) {
            $varName = trim($matches[1]);
            $value = $this->resolveTemplateVariable('{{' . $varName . '}}');

            // Check if variable was resolved (if it's still in {{}} format, it wasn't resolved)
            if (is_string($value) && preg_match('/^\{\{.+?\}\}$/', $value)) {
                // Variable not resolved - this is an error condition
                // We'll handle this by throwing an exception or returning a marker
                return '{{UNRESOLVED}}';
            }

            // Convert to string representation for condition evaluation
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

        return $result;
    }

    /**
     * Evaluate a conditional expression.
     *
     * @param string $condition
     * @param object $model
     * @return bool
     */
    protected function evaluateConditionExpression(string $condition, object $model): bool
    {
        // Trim the condition
        $condition = trim($condition);

        // Handle parentheses - evaluate innermost first
        while (preg_match('/\(([^()]+)\)/', $condition, $matches)) {
            $innerCondition = $matches[1];
            $innerResult = $this->evaluateConditionExpression($innerCondition, $model);
            // Replace the parenthesized expression with its boolean result
            $condition = str_replace($matches[0], $innerResult ? 'true' : 'false', $condition);
        }

        // Handle boolean literals
        if ($condition === 'true') {
            return true;
        }
        if ($condition === 'false') {
            return false;
        }

        // Split by logical operators (OR has lower precedence than AND)
        // First check for OR outside of any remaining parentheses
        if (stripos($condition, ' OR ') !== false) {
            $parts = preg_split('/\s+OR\s+/i', $condition);
            foreach ($parts as $part) {
                if ($this->evaluateConditionExpression(trim($part), $model)) {
                    return true;
                }
            }

            return false;
        }

        // Then check for AND
        if (stripos($condition, ' AND ') !== false) {
            $parts = preg_split('/\s+AND\s+/i', $condition);
            foreach ($parts as $part) {
                if (!$this->evaluateConditionExpression(trim($part), $model)) {
                    return false;
                }
            }

            return true;
        }

        // Handle NOT operator
        if (stripos($condition, 'NOT ') === 0) {
            $innerCondition = trim(substr($condition, 4));

            return !$this->evaluateConditionExpression($innerCondition, $model);
        }

        // Evaluate single comparison
        return $this->evaluateSingleComparison($condition, $model);
    }

    /**
     * Evaluate a single comparison expression.
     *
     * @param string $condition
     * @param object $model
     * @return bool
     */
    protected function evaluateSingleComparison(string $condition, object $model): bool
    {
        // Match comparison operators
        $operators = ['===', '!==', '>=', '<=', '>', '<', 'in', 'not_in'];

        foreach ($operators as $operator) {
            $pos = stripos($condition, ' ' . $operator . ' ');
            if ($pos !== false) {
                $field = trim(substr($condition, 0, $pos));
                $value = trim(substr($condition, $pos + strlen($operator) + 2));

                // Get actual value from model
                $actualValue = $this->getModelValue($model, $field);

                // Parse expected value
                $expectedValue = $this->parseValue($value);

                // Perform comparison
                return $this->performComparison($actualValue, $expectedValue, $operator);
            }
        }

        return false;
    }

    /**
     * Parse a value from string representation.
     *
     * @param string $value
     * @return mixed
     */
    protected function parseValue(string $value)
    {
        $value = trim($value);

        // String (quoted)
        if ((str_starts_with($value, "'") && str_ends_with($value, "'")) ||
            (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
            return substr($value, 1, -1);
        }

        // Boolean
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }

        // Null
        if (strtolower($value) === 'null') {
            return null;
        }

        // Number
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // Array (for 'in' operator)
        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            $items = explode(',', substr($value, 1, -1));

            return array_map(fn ($item) => $this->parseValue(trim($item)), $items);
        }

        return $value;
    }

    /**
     * Perform comparison between two values.
     *
     * @param mixed $actual
     * @param mixed $expected
     * @param string $operator
     * @return bool
     */
    protected function performComparison($actual, $expected, string $operator): bool
    {
        return match (strtolower($operator)) {
            '===' => $actual === $expected,
            '!==' => $actual !== $expected,
            '>' => $actual > $expected,
            '<' => $actual < $expected,
            '>=' => $actual >= $expected,
            '<=' => $actual <= $expected,
            'in' => is_array($expected) && in_array($actual, $expected, true),
            'not_in' => is_array($expected) && !in_array($actual, $expected, true),
            default => false,
        };
    }

    /**
     * Get all valid rule types.
     *
     * @return array<int, string>
     */
    public static function getValidTypes(): array
    {
        return [
            self::TYPE_ROW,
            self::TYPE_COLUMN,
            self::TYPE_JSON_ATTRIBUTE,
            self::TYPE_CONDITIONAL,
        ];
    }

    /**
     * Check if a rule type is valid.
     *
     * @param string $type
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getValidTypes(), true);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Canvastack\Canvastack\Tests\Fixtures\Factories\PermissionRuleFactory::new();
    }
}
