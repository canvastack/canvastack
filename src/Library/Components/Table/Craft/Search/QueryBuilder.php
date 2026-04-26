<?php
namespace Canvastack\Canvastack\Library\Components\Table\Craft\Search;

use Canvastack\Canvastack\Library\Components\Table\Craft\Search\Config\SearchConfig;
use Canvastack\Canvastack\Library\Constants\TableConstants;
use Canvastack\Canvastack\Library\Exceptions\Table\SQLInjectionAttemptException;
use Canvastack\Canvastack\Library\Exceptions\Table\TableValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * QueryBuilder - SQL query building and execution for Search component
 *
 * SECURITY FEATURES (1.7.6):
 * - Uses Laravel query builder with parameter binding instead of raw SQL
 * - Validates table and column names via sanitizeIdentifier()
 * - Logs suspicious query attempts
 * - Prevents SQL injection via identifier sanitization + value binding
 *
 * PERFORMANCE FEATURES (2.4):
 * - In-memory request-level cache for repeated queries within same request
 * - Laravel Cache integration for persistent cross-request caching
 * - Batch query execution to reduce N+1 query problems
 * - Optimized column selection (only required columns)
 * - Query result deduplication via groupBy
 *
 * @filesource QueryBuilder.php
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 */
class QueryBuilder {
	
	private SearchConfig $config;

	/**
	 * @var array In-memory request-level cache for query results
	 */
	private array $queryCache = [];

	/**
	 * @var array Accumulated selection results
	 */
	private array $selections = [];

	/**
	 * @var int Cache TTL in seconds for persistent cache (default: 5 minutes)
	 */
	private int $cacheTtl = 300;

	/**
	 * @var bool Whether to use persistent Laravel Cache (disabled by default for safety)
	 */
	private bool $usePersistentCache = false;
	
	/**
	 * Constructor
	 *
	 * @param SearchConfig $config Configuration object
	 * @param int $cacheTtl Cache TTL in seconds (default: 300)
	 * @param bool $usePersistentCache Whether to use Laravel Cache for cross-request caching
	 */
	public function __construct(SearchConfig $config, int $cacheTtl = 300, bool $usePersistentCache = false) {
		$this->config = $config;
		$this->cacheTtl = $cacheTtl;
		$this->usePersistentCache = $usePersistentCache;
	}

	/**
	 * Enable persistent cross-request caching via Laravel Cache
	 *
	 * @performance 2.4.2 - Enables persistent caching for search query results
	 *
	 * @param int $ttl Cache TTL in seconds
	 * @return $this
	 */
	public function enablePersistentCache(int $ttl = 300): self {
		$this->usePersistentCache = true;
		$this->cacheTtl = $ttl;
		return $this;
	}

	/**
	 * Disable persistent caching (use in-memory only)
	 *
	 * @return $this
	 */
	public function disablePersistentCache(): self {
		$this->usePersistentCache = false;
		return $this;
	}
	
	/**
	 * Execute SQL SELECT query with error handling
	 *
	 * @param string $sql SQL query
	 * @param string|null $connection Database connection
	 * @return array Query results
	 */
	public function select(string $sql, ?string $connection = null): array {
		try {
			return canvastack_query($sql, 'SELECT', $connection);
		} catch (\Exception $e) {
			Log::error('Search query failed', [
				'sql' => $sql,
				'connection' => $connection,
				'error' => $e->getMessage()
			]);
			
			return [];
		}
	}
	
	/**
	 * Get selections for fields with caching
	 *
	 * @performance 2.4.1 - Uses in-memory + optional persistent cache to avoid
	 *              repeated database queries for the same field selections.
	 *
	 * @param string $table Table name
	 * @param array $fields Field names
	 * @param mixed $condition WHERE conditions
	 * @return $this
	 */
	public function selections(string $table, array $fields = [], $condition = null): self {
		// Generate cache key
		$cacheKey = $this->buildCacheKey($table, $fields, $condition);
		
		// Check in-memory cache first (fastest)
		if (isset($this->queryCache[$cacheKey])) {
			$this->selections = array_merge($this->selections, $this->queryCache[$cacheKey]);
			return $this;
		}

		// Check persistent cache if enabled
		if ($this->usePersistentCache) {
			$cached = Cache::get('search_qb_' . $cacheKey);
			if ($cached !== null) {
				$this->queryCache[$cacheKey] = $cached;
				$this->selections = array_merge($this->selections, $cached);
				return $this;
			}
		}
		
		// Check relations first
		$strfields = implode(',', $fields);
		if ($this->hasRelationData($strfields)) {
			return $this;
		}
		
		// Build query with security - FIX: Sanitize field names
		if (!empty($fields)) {
			$where = $this->buildWhereClause($table, $condition);
			$query = $this->executeSecureQuery($table, $fields, $where);
			$this->processQueryResults($query, $fields);
			
			// Store in in-memory cache
			$this->queryCache[$cacheKey] = $this->selections;

			// Store in persistent cache if enabled
			if ($this->usePersistentCache) {
				Cache::put('search_qb_' . $cacheKey, $this->selections, $this->cacheTtl);
			}
		}
		
		return $this;
	}

	/**
	 * Batch selections for multiple fields - OPTIMIZED
	 * Executes single query for all fields instead of N queries
	 *
	 * @performance 2.4.1 - Single query for all fields prevents N+1 query problem.
	 *              Results are cached in-memory and optionally in persistent cache.
	 *
	 * @param string $table Table name
	 * @param array $fields Field names
	 * @param mixed $condition WHERE conditions
	 * @return $this
	 */
	public function batchSelections(string $table, array $fields = [], $condition = null): self {
		if (empty($fields)) {
			return $this;
		}

		// Generate cache key for batch
		$cacheKey = $this->buildCacheKey($table, $fields, $condition);

		// Check in-memory cache first
		if (isset($this->queryCache[$cacheKey])) {
			$this->selections = array_merge($this->selections, $this->queryCache[$cacheKey]);
			return $this;
		}

		// Check persistent cache if enabled
		if ($this->usePersistentCache) {
			$cached = Cache::get('search_qb_batch_' . $cacheKey);
			if ($cached !== null) {
				$this->queryCache[$cacheKey] = $cached;
				$this->selections = array_merge($this->selections, $cached);
				return $this;
			}
		}

		// Build single query for all fields
		$where = $this->buildWhereClause($table, $condition);
		$query = $this->executeSecureQuery($table, $fields, $where);
		$this->processQueryResults($query, $fields);

		// Store in in-memory cache
		$this->queryCache[$cacheKey] = $this->selections;

		// Store in persistent cache if enabled
		if ($this->usePersistentCache) {
			Cache::put('search_qb_batch_' . $cacheKey, $this->selections, $this->cacheTtl);
		}

		return $this;
	}

	
	/**
	 * Check if relation data exists
	 *
	 * @param string $strfields
	 * @return bool
	 */
	public function hasRelationData(string $strfields): bool {
		$relations = $this->config->getRelations();
		
		if (empty($relations)) {
			return false;
		}
		
		if (empty($relations[$strfields]['relation_data'])) {
			return false;
		}
		
		foreach ($relations[$strfields]['relation_data'] as $relationData) {
			$fieldValue = $relationData['field_value'] ?? null;
			if ($fieldValue !== null) {
				$this->selections[$strfields][$fieldValue] = $fieldValue;
			}
		}
		
		return true;
	}
	
	/**
	 * Build WHERE clause dengan security (prevent SQL injection)
	 *
	 * @deprecated Use applyModelFiltersToQuery() and applyFilterQueryToQuery() instead.
	 *             This method is kept for backward compatibility with the selections() method.
	 *             The executeSecureQuery() method now uses query builder directly.
	 *
	 * @param string $table
	 * @param mixed $condition
	 * @return string
	 */
	public function buildWhereClause(string $table, $condition): string {
		$where = '';
		
		if (!empty($condition)) {
			$table = $this->sanitizeIdentifier($table);
			$where = "WHERE `{$table}`.id IS NOT NULL ";
		}
		
		// Note: Model filters and filter queries are now applied directly via query builder
		// in executeSecureQuery() for better security (parameter binding)
		
		return $where;
	}
	
	/**
	 * Build WHERE clause dari model filters (LEGACY - kept for backward compatibility)
	 *
	 * @deprecated Use applyModelFiltersToQuery() instead for proper parameter binding.
	 *             This method uses string escaping which is less secure than query builder bindings.
	 *
	 * @param array $modelFilters Model filters
	 * @return string
	 */
	public function buildModelFiltersWhere(array $modelFilters): string {
		$mf_where = [];
		$n = 0;
		
		foreach ($modelFilters as $mf_field => $mf_values) {
			$n++;
			$mf_cond = ($n <= 1) ? 'WHERE ' : 'AND ';
			
			// Sanitize field name
			$mf_field = $this->sanitizeIdentifier($mf_field);
			
			if (!is_array($mf_values)) {
				// Single value - use prepared statement style
				$mf_values = $this->escapeValue($mf_values);
				$mf_where[] = "{$mf_cond}`{$mf_field}` = '{$mf_values}'";
			} else {
				// Multiple values - use IN clause
				$escaped_values = array_map([$this, 'escapeValue'], $mf_values);
				$mf_value = implode("', '", $escaped_values);
				$mf_where[] = "{$mf_cond}`{$mf_field}` IN ('{$mf_value}')";
			}
		}
		
		return implode(' ', $mf_where);
	}
	
	/**
	 * Build WHERE clause dari filter query (LEGACY - kept for backward compatibility)
	 *
	 * @deprecated Use applyFilterQueryToQuery() instead for proper parameter binding.
	 *             This method uses string escaping which is less secure than query builder bindings.
	 *
	 * @param array $filterQuery Filter query
	 * @return string
	 */
	public function buildFilterQueryWhere(array $filterQuery): string {
		$filterQueries = [];
		
		foreach ($filterQuery as $i => $fqData) {
			$fqFieldName = $fqData['field_name'] ?? '';
			$fqDataValue = $fqData['value'] ?? '';
			
			// Sanitize field name
			$fqFieldName = $this->sanitizeIdentifier($fqFieldName);
			
			if (is_array($fqDataValue)) {
				// Escape all values in array
				$escaped_values = array_map([$this, 'escapeValue'], $fqDataValue);
				$fQdataValue = implode("', '", $escaped_values);
				
				if (count($fqDataValue) >= 2) {
					$filterQueries[$i] = "`{$fqFieldName}` IN ('{$fQdataValue}')";
				} else {
					$filterQueries[$i] = "`{$fqFieldName}` = '{$fQdataValue}'";
				}
			} else {
				// Escape single value
				$fqDataValue = $this->escapeValue($fqDataValue);
				$filterQueries[$i] = "`{$fqFieldName}` = '{$fqDataValue}'";
			}
		}
		
		$filterQuery = implode(' AND ', $filterQueries);
		return "WHERE {$filterQuery}";
	}
	
	/**
	 * Execute secure query using Laravel query builder - prevents SQL injection
	 *
	 * @security 1.7.6 - Uses DB::table() query builder with parameter binding instead of
	 *           raw SQL string concatenation. Identifiers (table/column names) are sanitized
	 *           via sanitizeIdentifier(). Values are bound automatically by the query builder.
	 *
	 * @param string $table Table name
	 * @param array $fields Field names (changed from string to array)
	 * @param string $where WHERE clause (legacy parameter, kept for backward compatibility)
	 * @return array Query results
	 */
	public function executeSecureQuery(string $table, array $fields, string $where): array {
		$connection = $this->config->getConnection();
		
		// Check if any fields are relational fields
		$relations = $this->config->getRelations();
		$relationalFields = [];
		$regularFields = [];
		
		foreach ($fields as $field) {
			$isRelational = false;
			if (!empty($relations)) {
				foreach ($relations as $relationKey => $relationData) {
					if ($relationKey === $field || (isset($relationData['field_name']) && $relationData['field_name'] === $field)) {
						$relationalFields[$field] = $relationData;
						$isRelational = true;
						break;
					}
				}
			}
			if (!$isRelational) {
				$regularFields[] = $field;
			}
		}
		
		$results = [];
		
		// Process regular fields with database query
		if (!empty($regularFields)) {
			$results = $this->queryRegularFields($table, $regularFields, $where, $connection);
		}
		
		// Process relational fields from relation_data
		if (!empty($relationalFields)) {
			$relationalResults = $this->extractRelationalFieldValues($relationalFields);
			// Merge results
			$results = array_merge($results, $relationalResults);
		}
		
		return $results;
	}
	
	/**
	 * Query regular (non-relational) fields from database
	 *
	 * @param string $table Table name
	 * @param array $fields Field names
	 * @param string $where WHERE clause
	 * @param string|null $connection Database connection
	 * @return array Query results
	 */
	private function queryRegularFields(string $table, array $fields, string $where, ?string $connection): array {
		// Sanitize table name
		$safeTable = $this->sanitizeIdentifier($table);
		if ($safeTable !== $table) {
			Log::warning('[SECURITY] QueryBuilder::queryRegularFields() - Table name sanitized', [
				'original'  => $table,
				'sanitized' => $safeTable,
				'context'   => 'SQL injection prevention - identifier sanitization'
			]);
		}
		
		// Sanitize each field name
		$safeFields = [];
		foreach ($fields as $field) {
			$safeField = $this->sanitizeIdentifier($field);
			if ($safeField !== $field) {
				Log::warning('[SECURITY] QueryBuilder::queryRegularFields() - Field name sanitized', [
					'original'  => $field,
					'sanitized' => $safeField,
					'context'   => 'SQL injection prevention - identifier sanitization'
				]);
			}
			if (!empty($safeField)) {
				$safeFields[] = $safeField;
			}
		}
		
		if (empty($safeFields)) {
			return [];
		}
		
		try {
			// Use Laravel query builder for parameter binding
			$dbConnection = $connection ? DB::connection($connection) : DB::connection();
			$query = $dbConnection->table($safeTable);
			
			// Apply model filters using query builder bindings
			$modelFilters = $this->config->getModelFilters();
			if (!empty($modelFilters)) {
				$query = $this->applyModelFiltersToQuery($query, $modelFilters);
			}
			
			// Apply filter query using query builder bindings
			$filterQuery = $this->config->getFilterQuery();
			if (!empty($filterQuery)) {
				$query = $this->applyFilterQueryToQuery($query, $filterQuery);
			}
			
			// Select only the required fields (sanitized identifiers)
			$selectColumns = array_map(fn($f) => $safeTable . '.' . $f, $safeFields);
			$query->select($selectColumns)->groupBy($selectColumns);
			
			return $query->get()->toArray();
			
		} catch (\Exception $e) {
			Log::error('[SECURITY] QueryBuilder::queryRegularFields() - Query failed', [
				'table'  => $safeTable,
				'fields' => $safeFields,
				'error'  => $e->getMessage()
			]);
			return [];
		}
	}
	
	/**
	 * Extract field values from relational data
	 *
	 * @param array $relationalFields Relational fields with their data
	 * @return array Extracted values in query result format
	 */
	private function extractRelationalFieldValues(array $relationalFields): array {
		$results = [];
		
		foreach ($relationalFields as $fieldName => $relationData) {
			if (empty($relationData['relation_data'])) {
				Log::warning('QueryBuilder: Relational field has no relation_data', [
					'field' => $fieldName
				]);
				continue;
			}
			
			// Extract unique values from relation_data
			$uniqueValues = [];
			foreach ($relationData['relation_data'] as $record) {
				if (isset($record['field_value'])) {
					$value = $record['field_value'];
					if (!in_array($value, $uniqueValues)) {
						$uniqueValues[] = $value;
						// Create result object matching database query format
						$results[] = (object)[$fieldName => $value];
					}
				}
			}
			
			Log::debug('QueryBuilder: Extracted relational field values', [
				'field' => $fieldName,
				'values_count' => count($uniqueValues)
			]);
		}
		
		return $results;
	}
	
	/**
	 * Apply model filters to query builder (SECURE - uses parameter binding)
	 *
	 * @security 1.7.6 - Values are bound via query builder, not concatenated into SQL
	 *
	 * @param \Illuminate\Database\Query\Builder $query
	 * @param array $modelFilters
	 * @return \Illuminate\Database\Query\Builder
	 */
	private function applyModelFiltersToQuery($query, array $modelFilters) {
		foreach ($modelFilters as $mf_field => $mf_values) {
			// Sanitize field name (identifier)
			$safeField = $this->sanitizeIdentifier($mf_field);
			if ($safeField !== $mf_field) {
				Log::warning('[SECURITY] QueryBuilder::applyModelFiltersToQuery() - Field sanitized', [
					'original'  => $mf_field,
					'sanitized' => $safeField,
					'context'   => 'SQL injection prevention'
				]);
			}
			
			if (!is_array($mf_values)) {
				// Single value - query builder binds $mf_values as parameter automatically
				$query->where($safeField, '=', $mf_values);
			} else {
				// Multiple values - query builder binds all values as parameters automatically
				$query->whereIn($safeField, $mf_values);
			}
		}
		
		return $query;
	}
	
	/**
	 * Apply filter query to query builder (SECURE - uses parameter binding)
	 *
	 * @security 1.7.6 - Values are bound via query builder, not concatenated into SQL
	 *
	 * @param \Illuminate\Database\Query\Builder $query
	 * @param array $filterQuery
	 * @return \Illuminate\Database\Query\Builder
	 */
	private function applyFilterQueryToQuery($query, array $filterQuery) {
		foreach ($filterQuery as $fqData) {
			$fqFieldName = $fqData['field_name'] ?? '';
			$fqDataValue = $fqData['value'] ?? '';
			
			// Sanitize field name (identifier)
			$safeField = $this->sanitizeIdentifier($fqFieldName);
			if ($safeField !== $fqFieldName) {
				Log::warning('[SECURITY] QueryBuilder::applyFilterQueryToQuery() - Field sanitized', [
					'original'  => $fqFieldName,
					'sanitized' => $safeField,
					'context'   => 'SQL injection prevention'
				]);
			}
			
			if (is_array($fqDataValue)) {
				// Query builder binds all values as parameters automatically
				if (count($fqDataValue) >= 2) {
					$query->whereIn($safeField, $fqDataValue);
				} else {
					$query->where($safeField, '=', $fqDataValue[0] ?? '');
				}
			} else {
				// Query builder binds $fqDataValue as parameter automatically
				$query->where($safeField, '=', $fqDataValue);
			}
		}
		
		return $query;
	}
	
	/**
	 * Process query results
	 *
	 * @param array $query
	 * @param array $fields
	 * @return void
	 */
	public function processQueryResults(array $query, array $fields): void {
		if (empty($query)) {
			return;
		}
		
		$selections = [];
		foreach ($query as $rows) {
			foreach ($rows as $fieldname => $fieldvalue) {
				$selections[$fieldname][$fieldvalue] = $fieldvalue;
			}
		}
		
		foreach ($fields as $field) {
			if (isset($selections[$field])) {
				$this->selections[$field] = array_unique($selections[$field]);
			}
		}
	}
	
	/**
	 * Sanitize identifier (table/column name) - prevent SQL injection
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function sanitizeIdentifier(string $identifier): string {
		// Only allow alphanumeric, underscore, and dot
		return preg_replace('/[^a-zA-Z0-9_.]/', '', $identifier);
	}
	
	/**
	 * Escape value for SQL - prevent SQL injection
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function escapeValue($value): string {
		if ($value === null) {
			return '';
		}
		
		// Use addslashes for basic escaping
		// In production, use DB::connection()->getPdo()->quote() for better security
		return addslashes((string)$value);
	}
	
	/**
	 * Build a consistent cache key for query parameters
	 *
	 * @performance 2.4.2 - Generates deterministic cache keys for query result caching
	 *
	 * @param string $table Table name
	 * @param array $fields Field names
	 * @param mixed $condition WHERE conditions
	 * @return string Cache key (MD5 hash)
	 */
	private function buildCacheKey(string $table, array $fields, $condition): string {
		// Sort fields for consistent key regardless of order
		$sortedFields = $fields;
		sort($sortedFields);
		return md5($table . '|' . implode(',', $sortedFields) . '|' . serialize($condition));
	}

	/**
	 * Get selections
	 *
	 * @return array
	 */
	public function getSelections(): array {
		return $this->selections;
	}
	
	/**
	 * Clear in-memory query cache
	 *
	 * @return void
	 */
	public function clearCache(): void {
		$this->queryCache = [];
	}

	/**
	 * Clear persistent cache entries for a specific table
	 *
	 * @performance 2.4.2 - Cache invalidation for persistent search query cache
	 *
	 * @param string $table Table name to clear cache for
	 * @return void
	 */
	public function clearPersistentCache(string $table = ''): void {
		$this->queryCache = [];
		// Note: Laravel Cache doesn't support pattern-based deletion in all drivers.
		// For targeted invalidation, callers should use Cache::forget() with specific keys.
		// This method clears in-memory cache; persistent cache expires via TTL.
	}
	
	/**
	 * Apply multiple filters with AND/OR combination
	 *
	 * @security 1.7.6 - Uses query builder with parameter binding
	 *
	 * @param \Illuminate\Database\Query\Builder $query Query builder instance
	 * @param array $filters Array of filter definitions
	 * @param string $combination Combination type ('AND' or 'OR')
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function applyMultipleFilters($query, array $filters, string $combination = 'AND') {
		if (empty($filters)) {
			return $query;
		}
		
		// Validate combination type
		$combination = strtoupper($combination);
		if (!in_array($combination, ['AND', 'OR'], true)) {
			Log::warning('[SECURITY] QueryBuilder::applyMultipleFilters() - Invalid combination type', [
				'combination' => $combination,
				'defaulting_to' => 'AND'
			]);
			$combination = 'AND';
		}
		
		// Apply filters based on combination type
		if ($combination === 'OR') {
			$query->where(function($q) use ($filters) {
				foreach ($filters as $filter) {
					$this->applySingleFilter($q, $filter, 'OR');
				}
			});
		} else {
			foreach ($filters as $filter) {
				$this->applySingleFilter($query, $filter, 'AND');
			}
		}
		
		return $query;
	}
	
	/**
	 * Apply a single filter to query
	 *
	 * @security 1.7.6 - Uses query builder with parameter binding
	 *
	 * @param \Illuminate\Database\Query\Builder $query Query builder instance
	 * @param array $filter Filter definition [field, operator, value]
	 * @param string $boolean Boolean operator ('AND' or 'OR')
	 * @return void
	 */
	private function applySingleFilter($query, array $filter, string $boolean = 'AND'): void {
		$field = $filter['field'] ?? '';
		$operator = $filter['operator'] ?? '=';
		$value = $filter['value'] ?? null;
		
		// Sanitize field name
		$safeField = $this->sanitizeIdentifier($field);
		if ($safeField !== $field) {
			Log::warning('[SECURITY] QueryBuilder::applySingleFilter() - Field sanitized', [
				'original' => $field,
				'sanitized' => $safeField
			]);
		}
		
		// Validate operator
		$operator = $this->validateOperator($operator);
		
		// Apply filter based on operator
		$method = ($boolean === 'OR') ? 'orWhere' : 'where';
		
		switch ($operator) {
			case 'IN':
				if (is_array($value)) {
					$query->$method(function($q) use ($safeField, $value) {
						$q->whereIn($safeField, $value);
					});
				}
				break;
				
			case 'NOT IN':
				if (is_array($value)) {
					$query->$method(function($q) use ($safeField, $value) {
						$q->whereNotIn($safeField, $value);
					});
				}
				break;
				
			case 'BETWEEN':
				if (is_array($value) && count($value) === 2) {
					$query->$method(function($q) use ($safeField, $value) {
						$q->whereBetween($safeField, $value);
					});
				}
				break;
				
			case 'IS NULL':
				$query->$method(function($q) use ($safeField) {
					$q->whereNull($safeField);
				});
				break;
				
			case 'IS NOT NULL':
				$query->$method(function($q) use ($safeField) {
					$q->whereNotNull($safeField);
				});
				break;
				
			case 'LIKE':
			case 'NOT LIKE':
				// Handle wildcard patterns
				$pattern = $this->convertWildcardToSql($value);
				$query->$method($safeField, $operator, $pattern);
				break;
				
			default:
				$query->$method($safeField, $operator, $value);
				break;
		}
	}
	
	/**
	 * Validate and sanitize SQL operator
	 *
	 * @security 1.7.6 - Validates operator against whitelist
	 *
	 * @param string $operator Operator to validate
	 * @return string Validated operator
	 */
	private function validateOperator(string $operator): string {
		$validOperators = [
			'=', '!=', '<>', '>', '<', '>=', '<=',
			'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN',
			'IS NULL', 'IS NOT NULL'
		];
		
		$operator = strtoupper(trim($operator));
		
		if (!in_array($operator, $validOperators, true)) {
			Log::warning('[SECURITY] QueryBuilder::validateOperator() - Invalid operator', [
				'operator' => $operator,
				'context' => 'SQL injection prevention - operator validation'
			]);
			throw new SQLInjectionAttemptException('Invalid operator: ' . $operator);
		}
		
		return $operator;
	}
	
	/**
	 * Convert wildcard pattern to SQL LIKE pattern
	 *
	 * @param string $pattern Pattern with * wildcards
	 * @return string SQL LIKE pattern with % wildcards
	 */
	private function convertWildcardToSql(string $pattern): string {
		// Escape existing SQL wildcards
		$pattern = str_replace(['%', '_'], ['\%', '\_'], $pattern);
		
		// Convert * to %
		$pattern = str_replace('*', '%', $pattern);
		
		return $pattern;
	}
	
	/**
	 * Apply date range filter
	 *
	 * @security 1.7.6 - Uses query builder with parameter binding
	 *
	 * @param \Illuminate\Database\Query\Builder $query Query builder instance
	 * @param string $field Field name
	 * @param string|null $startDate Start date (Y-m-d format)
	 * @param string|null $endDate End date (Y-m-d format)
	 * @param string $boolean Boolean operator ('AND' or 'OR')
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function applyDateRangeFilter($query, string $field, ?string $startDate, ?string $endDate, string $boolean = 'AND') {
		// Sanitize field name
		$safeField = $this->sanitizeIdentifier($field);
		if ($safeField !== $field) {
			Log::warning('[SECURITY] QueryBuilder::applyDateRangeFilter() - Field sanitized', [
				'original' => $field,
				'sanitized' => $safeField
			]);
		}
		
		$method = ($boolean === 'OR') ? 'orWhere' : 'where';
		
		// Apply date range filter
		if ($startDate && $endDate) {
			// Both dates provided - use BETWEEN
			$query->$method(function($q) use ($safeField, $startDate, $endDate) {
				$q->whereBetween($safeField, [$startDate, $endDate]);
			});
		} elseif ($startDate) {
			// Only start date - greater than or equal
			$query->$method($safeField, '>=', $startDate);
		} elseif ($endDate) {
			// Only end date - less than or equal
			$query->$method($safeField, '<=', $endDate);
		}
		
		return $query;
	}
	
	/**
	 * Apply numeric range filter
	 *
	 * @security 1.7.6 - Uses query builder with parameter binding
	 *
	 * @param \Illuminate\Database\Query\Builder $query Query builder instance
	 * @param string $field Field name
	 * @param int|float|null $minValue Minimum value
	 * @param int|float|null $maxValue Maximum value
	 * @param string $boolean Boolean operator ('AND' or 'OR')
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function applyNumericRangeFilter($query, string $field, $minValue, $maxValue, string $boolean = 'AND') {
		// Sanitize field name
		$safeField = $this->sanitizeIdentifier($field);
		if ($safeField !== $field) {
			Log::warning('[SECURITY] QueryBuilder::applyNumericRangeFilter() - Field sanitized', [
				'original' => $field,
				'sanitized' => $safeField
			]);
		}
		
		$method = ($boolean === 'OR') ? 'orWhere' : 'where';
		
		// Apply numeric range filter
		if ($minValue !== null && $maxValue !== null) {
			// Both values provided - use BETWEEN
			$query->$method(function($q) use ($safeField, $minValue, $maxValue) {
				$q->whereBetween($safeField, [$minValue, $maxValue]);
			});
		} elseif ($minValue !== null) {
			// Only min value - greater than or equal
			$query->$method($safeField, '>=', $minValue);
		} elseif ($maxValue !== null) {
			// Only max value - less than or equal
			$query->$method($safeField, '<=', $maxValue);
		}
		
		return $query;
	}
	
	/**
	 * Apply text filter with wildcard or regex support
	 *
	 * @security 1.7.6 - Uses query builder with parameter binding
	 *           Regex patterns are validated before use
	 *
	 * @param \Illuminate\Database\Query\Builder $query Query builder instance
	 * @param string $field Field name
	 * @param string $pattern Search pattern
	 * @param string $patternType Pattern type ('wildcard' or 'regex')
	 * @param string $boolean Boolean operator ('AND' or 'OR')
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function applyTextFilterWithPattern($query, string $field, string $pattern, string $patternType = 'wildcard', string $boolean = 'AND') {
		// Sanitize field name
		$safeField = $this->sanitizeIdentifier($field);
		if ($safeField !== $field) {
			Log::warning('[SECURITY] QueryBuilder::applyTextFilterWithPattern() - Field sanitized', [
				'original' => $field,
				'sanitized' => $safeField
			]);
		}
		
		$method = ($boolean === 'OR') ? 'orWhere' : 'where';
		
		if ($patternType === 'regex') {
			// Validate regex pattern
			if (!$this->isValidRegex($pattern)) {
				Log::warning('[SECURITY] QueryBuilder::applyTextFilterWithPattern() - Invalid regex pattern', [
					'pattern' => $pattern,
					'falling_back_to' => 'wildcard'
				]);
				$patternType = 'wildcard';
			}
		}
		
		if ($patternType === 'regex') {
			// Use database-specific regex operator
			$connection = $this->config->getConnection();
			$dbType = $connection ? DB::connection($connection)->getDriverName() : DB::getDriverName();
			
			if ($dbType === 'mysql') {
				// MySQL REGEXP operator
				$query->$method($safeField, 'REGEXP', $pattern);
			} elseif ($dbType === 'pgsql') {
				// PostgreSQL ~ operator
				$query->$method($safeField, '~', $pattern);
			} else {
				// Fallback to LIKE with wildcard conversion
				Log::warning('QueryBuilder: Regex not supported for database type, using wildcard', [
					'db_type' => $dbType
				]);
				$sqlPattern = $this->convertWildcardToSql($pattern);
				$query->$method($safeField, 'LIKE', $sqlPattern);
			}
		} else {
			// Wildcard pattern - convert * to SQL %
			$sqlPattern = $this->convertWildcardToSql($pattern);
			$query->$method($safeField, 'LIKE', $sqlPattern);
		}
		
		return $query;
	}
	
	/**
	 * Validate regex pattern
	 *
	 * @security Validates regex pattern to prevent ReDoS attacks
	 *
	 * @param string $pattern Regex pattern to validate
	 * @return bool True if valid, false otherwise
	 */
	private function isValidRegex(string $pattern): bool {
		// Check for empty pattern
		if (empty($pattern)) {
			return false;
		}
		
		// Try to compile the regex
		set_error_handler(function() {}, E_WARNING);
		$isValid = @preg_match('/' . $pattern . '/', '') !== false;
		restore_error_handler();
		
		if (!$isValid) {
			return false;
		}
		
		// Check for potentially dangerous patterns (ReDoS prevention)
		// Reject patterns with excessive nesting or repetition
		$dangerousPatterns = [
			'/(\(.*\)){10,}/',  // Too many nested groups
			'/(\*|\+|\{.*\}){5,}/',  // Too many repetition operators
		];
		
		foreach ($dangerousPatterns as $dangerous) {
			if (preg_match($dangerous, $pattern)) {
				Log::warning('[SECURITY] QueryBuilder::isValidRegex() - Potentially dangerous regex pattern', [
					'pattern' => $pattern
				]);
				return false;
			}
		}
		
		return true;
	}
}
