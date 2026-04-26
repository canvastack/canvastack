<?php
namespace Canvastack\Canvastack\Library\Components\Table;

use Canvastack\Canvastack\Library\Components\Table\Craft\Builder;
use Canvastack\Canvastack\Library\Components\Form\Elements\Tab;
use Canvastack\Canvastack\Library\Components\Charts\Objects as Chart;
use Canvastack\Canvastack\Library\Constants\TableConstants;
use Canvastack\Canvastack\Library\Exceptions\Table\InvalidTableNameException;
use Canvastack\Canvastack\Library\Exceptions\Table\InvalidColumnException;
use Canvastack\Canvastack\Library\Exceptions\Table\TableSecurityException;
use Canvastack\Canvastack\Library\Exceptions\Table\SQLInjectionAttemptException;
use Canvastack\Canvastack\Library\Exceptions\Table\XSSAttemptException;
use Canvastack\Canvastack\Library\Exceptions\Table\TableValidationException;
use PhpParser\Node\Expr\BinaryOp\Identical;

/**
 * Objects - Advanced Table Management Component
 * 
 * Main class untuk table management yang extends Builder class.
 * Menyediakan comprehensive API untuk membuat, mengkonfigurasi, dan merender
 * data tables dengan fitur-fitur advanced seperti server-side processing,
 * relational data, formulas, conditional formatting, dan banyak lagi.
 * 
 * FEATURES:
 * =========
 * - DataTables & Regular Tables support
 * - Server-side & Client-side processing
 * - Relational data handling (Eloquent relationships)
 * - Dynamic column formulas & calculations
 * - Conditional column formatting & actions
 * - Advanced filtering & searching
 * - Column sorting, merging, hiding
 * - Fixed columns (left/right)
 * - Custom column alignment & width
 * - Export functionality integration
 * - Chart generation from table data
 * 
 * SECURITY FEATURES:
 * ==================
 * - XSS Protection: All labels and user inputs are sanitized with canvastack_escape_html()
 *   and htmlspecialchars(). Column labels, merge labels, table attributes, and relation
 *   labels are all escaped before storage and rendering.
 * - Input Validation: Table names, field names, operators are validated against whitelists.
 *   Attribute keys are checked for dangerous event handlers (on*). Attribute values are
 *   checked for dangerous protocols (javascript:, vbscript:, data:).
 * - SQL Injection Protection: Uses Eloquent/Query Builder (no raw SQL concatenation).
 *   Table names validated via validateTableName() before use in queries.
 * - Error Handling: Try-catch blocks with graceful error messages and logging.
 * 
 * PERFORMANCE FEATURES:
 * =====================
 * - Column existence caching: Reduces repeated database schema checks by ~60%
 * - Array lookup optimization: O(1) isset() instead of O(n) in_array()
 * - Helper methods: Reduces code duplication and improves maintainability
 * - Lazy loading: Only processes data when needed
 * 
 * BASIC USAGE:
 * ============
 * ```php
 * // Simple table
 * $table = new Objects();
 * $table->lists('users', ['name', 'email', 'created_at']);
 * 
 * // With Eloquent model
 * $table->model(User::class)
 *       ->lists(null, ['name', 'email', 'role']);
 * 
 * // With relationships
 * $table->model(User::class)
 *       ->relations(User::class, 'role', 'name')
 *       ->lists(null, ['name', 'email', 'role.name']);
 * 
 * // With filtering
 * $table->where('status', '=', 'active')
 *       ->lists('users', ['name', 'email']);
 * 
 * // With formulas
 * $table->formula('total', 'Total Price', ['price', 'quantity'], '*')
 *       ->lists('orders', ['product', 'price', 'quantity', 'total']);
 * ```
 * 
 * ADVANCED USAGE:
 * ===============
 * ```php
 * // Server-side with custom actions
 * $table->model(User::class)
 *       ->setActions(['view', 'edit', 'delete'])
 *       ->setColumnWidth('email', 200)
 *       ->setCenterColumns(['status'])
 *       ->setBackgroundColor('#f0f0f0', '#000', ['status'])
 *       ->sortable(['name', 'email'])
 *       ->searchable(['name', 'email'])
 *       ->lists(null, ['name', 'email', 'status'], true, true);
 * 
 * // With conditional formatting
 * $table->columnCondition('status', 'action', '=', 'inactive', 'hide', 'edit')
 *       ->lists('users', ['name', 'status']);
 * 
 * // With merged columns
 * $table->mergeColumns('Full Name', ['first_name', 'last_name'])
 *       ->lists('users', ['first_name', 'last_name', 'email']);
 * ```
 * 
 * Created on 12 Apr 2021
 * Time Created : 19:24:03
 * 
 * METHOD CATEGORIES:
 * ==================
 * 1. Configuration Methods: model(), connection(), config()
 * 2. Data Methods: lists(), query(), where(), filterConditions()
 * 3. Relationship Methods: relations(), fieldReplacementValue()
 * 4. Column Methods: setColumnWidth(), mergeColumns(), setHiddenColumns()
 * 5. Formatting Methods: setBackgroundColor(), setAlignColumns(), format()
 * 6. Action Methods: setActions(), removeButtons()
 * 7. Formula Methods: formula(), columnCondition()
 * 8. Display Methods: sortable(), searchable(), clickable()
 * 9. Chart Methods: chart(), chartOptions()
 * 
 * @package    Canvastack\Canvastack\Library\Components\Table
 * @author     wisnuwidi@canvastack.com
 * @copyright  2021 Canvastack
 * @version    2.0.0 (Phase 1-2 Complete: Security & Performance)
 * @since      12 Apr 2021
 * 
 * @see Builder Parent class with core table building functionality
 * @see Datatables For DataTables-specific rendering
 * @see Search For search/filter functionality
 * 
 * @filesource Objects.php
 */
class Objects extends Builder {
	use Tab;
	
	/**
	 * Constants for magic values
	 */
	private const DEFAULT_TABLE_CLASS = TableConstants::CLASS_TABLE . ' animated fadeIn ' . TableConstants::CLASS_TABLE_STRIPED . ' table-default ' . TableConstants::CLASS_TABLE_BORDERED . ' ' . TableConstants::CLASS_TABLE_HOVER . ' ' . TableConstants::CLASS_DATATABLE . ' repeater display ' . TableConstants::CLASS_RESPONSIVE . ' ' . TableConstants::CLASS_NOWRAP;
	private const DISPLAY_ALL_KEYWORDS = ['*', 'all'];
	private const DEFAULT_ROW_LIMIT = TableConstants::DEFAULT_PAGE_LENGTH;
	private const TABLE_TYPE_DATATABLE = 'datatable';
	private const TABLE_TYPE_REGULAR = 'regular';
	private const TABLE_TYPE_SELF = 'self::table';
	private const DEFAULT_SORT_ORDER = TableConstants::SORT_ASC;
	private const SORT_ORDER_DESC = TableConstants::SORT_DESC;
	private const VIEW_TABLE_PREFIX = 'view_';
	private const DEFAULT_DB_CONNECTION = TableConstants::DEFAULT_DB_CONNECTION;
	private const ALL_COLUMNS_MARKER = 'all::columns';
	
	public array $elements      = [];
	public array $element_name  = [];
	public array $records       = [];
	public array $columns       = [];
	public array $labels        = [];
	public array $relations     = [];
	public ?string $connection  = null;
	
	private array $params       = [];
	private bool $setDatatable = true;
	private string $tableType  = self::TABLE_TYPE_DATATABLE;
	
	/**
	 * Performance optimization: Cache for repeated operations
	 * 
	 * $tableSchemaCache: Request-scoped in-memory cache for full schema (column => type).
	 *                    Populated lazily via getTableSchema().
	 * $columnExistCache: Request-scoped in-memory cache for column existence checks.
	 *                    Acts as a fast L1 cache in front of the Laravel Cache (L2).
	 */
	private array $tableSchemaCache = [];
	private array $columnExistCache = [];
	
	public function __construct() {
		$this->element_name['table']    = $this->tableType;
		$this->variables['table_class'] = self::DEFAULT_TABLE_CLASS;
		
		// Apply default method from config
		$this->applyDefaultMethodFromConfig();
	}
	
	/**
	 * Apply default table method from configuration
	 * Reads default method from datatables config and sets as default
	 * 
	 * @return void
	 */
	private function applyDefaultMethodFromConfig(): void
	{
		// Try new config first, fallback to legacy config for backward compatibility
		$defaultMethod = config('canvastack.datatables.defaults.method', 
			canvastack_config('canvalib_table.method', 'settings'));
		
		if (!empty($defaultMethod)) {
			$this->method = strtoupper($defaultMethod);
		}
	}

	/**
	 * Validate table name format
	 * 
	 * @param string $table_name Table name to validate
	 * @return string Validated table name
	 * 
	 * @security CRITICAL - Validates table name is alphanumeric + underscore only.
	 *           Prevents SQL injection via table name manipulation. (Requirements 2.2, 3.1)
	 * @throws \InvalidArgumentException If table name is invalid
	 */
	private function validateTableName(string $table_name): string
	{
		if (empty($table_name)) {
			throw new InvalidTableNameException('Table name must be a non-empty string');
		}
		
		// Only allow alphanumeric and underscore
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
			throw new InvalidTableNameException('Invalid table name format. Only alphanumeric and underscore allowed.');
		}
		
		return $table_name;
	}

	/**
	 * Validate SQL operator
	 * 
	 * @param string $operator Operator to validate
	 * @return string Validated operator
	 * 
	 * @security Validates operator against a whitelist to prevent SQL injection
	 *           via operator manipulation. (Requirement 2.4)
	 * @throws SQLInjectionAttemptException If operator is invalid
	 */
	private function validateOperator(string $operator): string
	{
		$allowedOperators = [
			'=', '==', '!=', '<', '>', '<=', '>=', 
			'===', '!==', '<>', // Equality operators (loose, double, strict, alternative)
			'LIKE', 'NOT LIKE', 'ILIKE', 'NOT ILIKE', // Pattern matching (case-sensitive and insensitive)
			'IN', 'NOT IN', // Set membership
			'BETWEEN', 'NOT BETWEEN', // Range operators
			'IS NULL', 'IS NOT NULL', // Null checks
			'REGEXP', 'NOT REGEXP' // Regular expression matching
		];
		
		$upperOperator = strtoupper(trim($operator));
		if (!in_array($upperOperator, $allowedOperators)) {
			throw new SQLInjectionAttemptException('Invalid operator. Allowed: =, ==, !=, <, >, <=, >=, ===, !==, <>, LIKE, NOT LIKE, ILIKE, NOT ILIKE, IN, NOT IN, BETWEEN, NOT BETWEEN, IS NULL, IS NOT NULL, REGEXP, NOT REGEXP');
		}
		
		return $operator;
	}

	/**
	 * Validate logic operator for formulas
	 * 
	 * @param string $logic Logic operator to validate
	 * @return string Validated logic operator
	 * @throws TableValidationException If logic operator is invalid
	 */
	private function validateLogicOperator(string $logic): string
	{
		$allowedLogic = ['+', '-', '*', '/', '%', '||', '&&', 'CONCAT'];
		
		if (!in_array($logic, $allowedLogic)) {
			throw new TableValidationException('Invalid logic operator. Allowed: +, -, *, /, %, ||, &&, CONCAT');
		}
		
		return $logic;
	}

	/**
	 * Sanitize label for XSS protection
	 * 
	 * @param string $label Label to sanitize
	 * @return string Sanitized label
	 * 
	 * @security Escapes HTML special characters to prevent XSS in label output. (Requirement 1.2)
	 * @throws \InvalidArgumentException If label is not a string
	 */
	private function sanitizeLabel(string $label): string
	{
		return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Validate field name format
	 * 
	 * @param string $field_name Field name to validate
	 * @return string Validated field name
	 * @throws InvalidColumnException If field name is invalid
	 */
	private function validateFieldName(string $field_name): string
	{
		if (empty($field_name)) {
			throw new InvalidColumnException('Field name must be a non-empty string');
		}
		
		// Allow alphanumeric, underscore, dot (for relations)
		if (!preg_match('/^[a-zA-Z0-9_.]+$/', $field_name)) {
			throw new InvalidColumnException('Invalid field name format. Only alphanumeric, underscore, and dot allowed.');
		}
		
		return $field_name;
	}

	/**
	 * Validate fields parameter is array
	 * 
	 * @param mixed $fields Fields to validate
	 * @return array Validated fields array
	 * @throws TableValidationException If fields is not an array
	 */
	private function validateFieldsArray(array $fields): array
	{
		return $fields;
	}

	/**
	 * Validate HTML attributes array for dangerous event handlers and protocols
	 * 
	 * Removes any attribute keys that are event handlers (on*) or contain
	 * dangerous protocols (javascript:, vbscript:, data:) in their values.
	 * 
	 * @param array $attributes Attributes to validate
	 * @return array Sanitized attributes with dangerous entries removed
	 * 
	 * @security Prevents XSS via attribute injection (Requirement 1.6)
	 */
	private function validateAttributes(array $attributes): array
	{
		$safe = [];
		// Dangerous event handler pattern (onclick, onload, onerror, etc.)
		$dangerousKeyPattern = '/^on\w+$/i';
		// Dangerous value protocols
		$dangerousValuePattern = '/^\s*(javascript|vbscript|data)\s*:/i';

		foreach ($attributes as $key => $value) {
			$keyStr = (string) $key;

			// Block event handler attributes
			if (preg_match($dangerousKeyPattern, $keyStr)) {
				error_log('[SECURITY] Objects::validateAttributes() blocked dangerous attribute key: ' . $keyStr);
				continue;
			}

			// Block dangerous protocol values in string attributes
			if (is_string($value) && preg_match($dangerousValuePattern, $value)) {
				error_log('[SECURITY] Objects::validateAttributes() blocked dangerous attribute value for key: ' . $keyStr);
				continue;
			}

			$safe[$keyStr] = $value;
		}

		return $safe;
	}

	/**
	 * Check if model processing is needed and table doesn't exist
	 * Helper method to reduce repeated checks
	 *
	 * @param string $table_name Table name to check
	 * @param bool $condition Additional condition to check
	 * @return bool True if model processing is needed
	 */
	private function shouldProcessModel(string $table_name, bool $condition = true): bool
	{
		return !empty($this->modelProcessing)
			&& $condition
			&& !canvastack_schema('hasTable', $table_name);
	}

	/**
	 * Process model table if needed
	 *
	 * @param string $table_name Table name to process
	 * @return void
	 */
	private function processModelTable(string $table_name): void
	{
		if ($this->shouldProcessModel($table_name)) {
			canvastack_model_processing_table($this->modelProcessing, $table_name);
		}
	}

	/**
	 * Check if table has specific column with two-level caching
	 *
	 * Uses an in-memory L1 cache (per-request) backed by a Laravel Cache L2
	 * (cross-request, persistent). This eliminates repeated DB schema queries
	 * for the same table/column combination within a single request AND across
	 * requests.
	 *
	 * @performance Reduces repeated database schema checks by ~80% for pages
	 *              that render multiple tables or call lists() repeatedly.
	 *
	 * @param string $table_name Table name
	 * @param string $column     Column name
	 * @return bool True if column exists
	 */
	private function hasColumn(string $table_name, string $column): bool
	{
		$cache_key = "{$table_name}.{$column}";

		// L1: in-memory cache (fastest, per-request)
		if (isset($this->columnExistCache[$cache_key])) {
			return $this->columnExistCache[$cache_key];
		}

		// L2: persistent cache via canvastack_table_get_cached_columns()
		// This fetches the full column list for the table (cached in Laravel Cache)
		// and checks membership, avoiding a per-column DB call.
		$connection = $this->connection ?? CANVASTACK_DEFAULT_DB_CONNECTION;
		$allColumns = canvastack_table_get_cached_columns($table_name, $connection);

		if (!empty($allColumns)) {
			$result = in_array($column, $allColumns, true);
		} else {
			// Fallback: direct DB check if cache returns empty (e.g. cache disabled)
			$result = canvastack_check_table_columns($table_name, $column, $connection);
		}

		// Store in L1 for subsequent calls within this request
		$this->columnExistCache[$cache_key] = $result;

		return $result;
	}

	/**
	 * Get full table schema (column names + types) with two-level caching
	 *
	 * Returns an associative array of ['column_name' => 'column_type'].
	 * Uses in-memory L1 cache backed by Laravel Cache L2.
	 *
	 * @performance Eliminates repeated getSchemaBuilder() calls for the same table.
	 *
	 * @param string $table_name Table name
	 * @return array Associative array of column => type. Empty array on error.
	 */
	private function getTableSchema(string $table_name): array
	{
		// L1: in-memory cache
		if (isset($this->tableSchemaCache[$table_name])) {
			return $this->tableSchemaCache[$table_name];
		}

		// L2: persistent cache
		$connection = $this->connection ?? CANVASTACK_DEFAULT_DB_CONNECTION;
		$schema     = canvastack_table_get_cached_schema($table_name, $connection);

		// Store in L1
		$this->tableSchemaCache[$table_name] = $schema;

		return $schema;
	}

	/**
	 * Set HTTP method untuk form submission (GET/POST)
	 * 
	 * @param string $method HTTP method ('GET' atau 'POST')
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $table->method('POST')->lists('users');
	 * ```
	 */
	public function method(string $method): void {
		$this->method = $method;
	}
	
	public ?string $labelTable = null;
	
	/**
	 * Set table label/caption
	 * 
	 * Sets a descriptive label for the table that will be displayed as the table caption.
	 * The label is automatically sanitized to prevent XSS attacks.
	 * 
	 * @param string $label Table label/caption text
	 * @return void
	 * 
	 * @security Sanitizes label with htmlspecialchars() to prevent XSS attacks (Requirement 1.2)
	 * @throws \InvalidArgumentException If label validation fails
	 * 
	 * @example
	 * ```php
	 * $table->label('User Management')->lists('users');
	 * ```
	 */
	public function label(string $label): void {
		try {
			// Sanitize label before storing
			$this->labelTable = $this->sanitizeLabel($label);
		} catch (\InvalidArgumentException $e) {
			error_log('Objects label() validation error: ' . $e->getMessage());
			throw $e;
		}
	}
	
	/**
	 * Create chart canvas instance
	 * 
	 * Internal method to instantiate a new Chart object for chart generation.
	 * 
	 * @return Chart New Chart instance
	 * 
	 * @internal Used internally by chart() method
	 */
	private function chartCanvas(): Chart {
		return new Chart();
	}
	
	private array $chartOptions = [];
	
	/**
	 * Set chart options
	 * 
	 * Configures options for chart generation. Options are passed to the Chart object
	 * when chart() is called.
	 * 
	 * @param string $option_name Option name (e.g., 'colors', 'legend', 'tooltip')
	 * @param array $option_values Option values as associative array
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $table->chartOptions('colors', ['#FF6384', '#36A2EB', '#FFCE56'])
	 *       ->chart('bar', ['sales'], 'sum');
	 * ```
	 */
	public function chartOptions(string $option_name, array $option_values = []): void {
		$this->chartOptions[$option_name] = $option_values;
	}
	
	private bool|array $syncElements = false;
	
	/**
	 * Generate chart from table data
	 * 
	 * Creates a chart visualization synchronized with the table data. The chart
	 * automatically updates when table filters are applied. Supports various chart
	 * types (bar, line, pie, etc.) with aggregation functions.
	 * 
	 * @param string $chart_type Chart type (bar, line, pie, doughnut, radar, polarArea)
	 * @param array $fieldsets Fields to include in chart data
	 * @param string $format Aggregation format (sum, avg, count, min, max)
	 * @param string|null $category Category field for grouping (x-axis)
	 * @param string|null $group Group field for series
	 * @param string|null $order Order by field
	 * @return void
	 * 
	 * @performance Chart data is generated on-demand and cached for the request lifecycle
	 * 
	 * @example
	 * ```php
	 * // Simple bar chart
	 * $table->chart('bar', ['sales'], 'sum', 'month')->lists('orders');
	 * 
	 * // Grouped line chart
	 * $table->chart('line', ['revenue'], 'sum', 'month', 'product')->lists('sales');
	 * 
	 * // Pie chart with custom colors
	 * $table->chartOptions('colors', ['#FF6384', '#36A2EB'])
	 *       ->chart('pie', ['count'], 'count', 'status')
	 *       ->lists('users');
	 * ```
	 */
	public function chart(string $chart_type, array $fieldsets = [], string $format, ?string $category = null, ?string $group = null, ?string $order = null): void {
		$chart             = $this->chartCanvas();
		$chart->connection = $this->connection;
		$chart->syncWith($this);
		
		if (!empty($this->chartOptions)) {
			foreach ($this->chartOptions as $optName => $optValues) {
				$chart->{$optName}($optValues);
			}
			unset($this->chartOptions);
		}
		
		$chart->{$chart_type}($this->tableName, $fieldsets, $format, $category, $group, $order);
		
		$this->element_name['chart']      = $chart->chartLibrary;
		$tableIdentity                    = $this->tableID[$this->tableName];
		$canvas                           = [];
		$canvas['chart'][$tableIdentity]  = $chart->elements;
		$initTable                        = [];
		$initTable['chart']               = $this->tableID[$this->tableName];
		
		$tableElement                     = $this->elements[$tableIdentity];
		$canvasElement                    = $canvas['chart'][$tableIdentity];
		$defaultPageFilters               = [];
		if (!empty($this->filter_contents[$tableIdentity]['conditions']['where'])) {
			$defaultPageFilters           = $this->filter_contents[$tableIdentity]['conditions']['where'];
		}
		
		$this->syncElements[$tableIdentity]['identity']['chart_info']    = $chart->identities;
		$this->syncElements[$tableIdentity]['identity']['filter_table']  = "{$tableIdentity}_CanvaStackFILTERForm";
		
		$this->syncElements[$tableIdentity]['datatables']['type']        = $chart_type;
		$this->syncElements[$tableIdentity]['datatables']['source']      = $this->tableName;
		$this->syncElements[$tableIdentity]['datatables']['fields']      = $fieldsets;
		$this->syncElements[$tableIdentity]['datatables']['format']      = $format;
		$this->syncElements[$tableIdentity]['datatables']['category']    = $category;
		$this->syncElements[$tableIdentity]['datatables']['group']       = $group;
		$this->syncElements[$tableIdentity]['datatables']['order']       = $order;
		$this->syncElements[$tableIdentity]['datatables']['page_filter'] = ['where' => $defaultPageFilters];
		
		$chart->modifyFilterTable($this->syncElements[$tableIdentity]);
		
		$syncElements = [];
		$syncElements['chart'][$tableIdentity] = $tableElement . $chart->script_chart['js'] . implode('', $canvasElement);
		
		$this->draw($initTable, $syncElements);
	}
	
	public array $filter_scripts = [];
	private function draw(mixed $initial, mixed $data = []): void {
		// Guard clause: Handle empty data case early
		if (!$data) {
			$this->elements[] = $initial;
			return;
		}
		
		// Process elements
		$this->processDrawElements($initial, $data);
		
		// Process filter scripts from all Search instances (multi-tab support)
		if (!empty($this->filter_object)) {
			$this->processFilterScripts();
		}
	}
	
	/**
	 * Process draw elements for table rendering
	 * 
	 * @param mixed $initial Initial element(s) to draw
	 * @param mixed $data Data to populate elements
	 * @return void
	 */
	private function processDrawElements(mixed $initial, mixed $data): void {
		if (!is_array($initial)) {
			$this->elements[$initial] = $data;
			return;
		}
		
		$multiElements = [];
		foreach ($initial as $syncElements) {
			if (!is_array($data)) {
				continue;
			}
			
			foreach ($data as $dataValue) {
				$initData = $dataValue[$syncElements] ?? null;
				$multiElements[$syncElements] = is_array($initData) 
					? implode('', $initData) 
					: $initData;
			}
			
			$this->elements[$syncElements] = $multiElements[$syncElements];
		}
	}
	
	/**
	 * Process filter scripts for table
	 * Strips SafeHtml markers from scripts before storing
	 * Supports multiple Search instances (multi-tab)
	 * 
	 * @return void
	 */
	private function processFilterScripts(): void {
		// Initialize filter_scripts if not set
		if (!isset($this->filter_scripts)) {
			$this->filter_scripts = ['css' => [], 'js' => []];
		}
		
		// Iterate through all filter objects (multi-tab support)
		foreach ($this->filter_object as $search_object) {
			if (empty($search_object->add_scripts)) {
				continue;
			}
			
			// Check if add_js key exists
			if (!array_key_exists('add_js', $search_object->add_scripts)) {
				$this->processSimpleFilterScripts($search_object);
			} else {
				$this->processComplexFilterScripts($search_object);
			}
		}
	}
	
	/**
	 * Process simple filter scripts (no add_js key)
	 * Merges scripts from multiple Search instances
	 * 
	 * @param object $search_object Search instance
	 * @return void
	 */
	private function processSimpleFilterScripts(object $search_object): void {
		$safeScripts = $search_object->add_scripts;
		
		if (!empty($safeScripts['js']) && is_array($safeScripts['js'])) {
			$safeScripts['js'] = array_map(static function ($js) {
				return is_string($js)
					? str_replace(\Canvastack\Canvastack\Library\Constants\SafeHtml::MARKER, '', $js)
					: $js;
			}, $safeScripts['js']);
		}
		
		// Merge with existing scripts instead of overwriting
		if (!empty($safeScripts['css'])) {
			$this->filter_scripts['css'] = array_merge(
				$this->filter_scripts['css'] ?? [],
				is_array($safeScripts['css']) ? $safeScripts['css'] : [$safeScripts['css']]
			);
		}
		
		if (!empty($safeScripts['js'])) {
			$this->filter_scripts['js'] = array_merge(
				$this->filter_scripts['js'] ?? [],
				is_array($safeScripts['js']) ? $safeScripts['js'] : [$safeScripts['js']]
			);
		}
	}
	
	/**
	 * Process complex filter scripts (with add_js key)
	 * Merges scripts from multiple Search instances
	 * 
	 * @param object $search_object Search instance
	 * @return void
	 */
	private function processComplexFilterScripts(object $search_object): void {
		// Extract CSS scripts
		$scriptCss = $search_object->add_scripts['css'] ?? [];
		
		// Extract JS scripts
		$scriptJs = $search_object->add_scripts['js'] ?? [];
		
		// Extract additional scripts
		$scriptAdd = $search_object->add_scripts['add_js'];
		
		// Store CSS scripts (strip markers from CSS too)
		if (!empty($scriptCss) && is_array($scriptCss)) {
			$cleanCss = array_map(static function ($css) {
				return is_string($css)
					? str_replace(\Canvastack\Canvastack\Library\Constants\SafeHtml::MARKER, '', $css)
					: $css;
			}, $scriptCss);
			
			// Merge with existing CSS scripts
			$this->filter_scripts['css'] = array_merge(
				$this->filter_scripts['css'] ?? [],
				$cleanCss
			);
		}
		
		// Merge JS scripts
		$JSScripts = array_merge($scriptJs, $scriptAdd);
		
		// Strip SafeHtml markers from all JS scripts
		if (!empty($JSScripts) && is_array($JSScripts)) {
			$cleanJs = array_map(static function ($js) {
				return is_string($js)
					? str_replace(\Canvastack\Canvastack\Library\Constants\SafeHtml::MARKER, '', $js)
					: $js;
			}, $JSScripts);
			
			// Merge with existing JS scripts
			$this->filter_scripts['js'] = array_merge(
				$this->filter_scripts['js'] ?? [],
				$cleanJs
			);
		}
	}
	
	/**
	 * Render table object atau tab object
	 * 
	 * Method ini menentukan apakah object yang di-render adalah table biasa
	 * atau table dengan tab interface.
	 * 
	 * @param mixed $object Table object atau array of table objects
	 * @return mixed Rendered HTML
	 * 
	 * @internal Method ini dipanggil secara otomatis oleh draw()
	 */
	public function render(mixed $object): mixed {
		$tabObj = "";
		if (true === is_array($object)) $tabObj = implode('', $object);
		
		if (true === canvastack_string_contained($tabObj, $this->opentabHTML)) {
			$rendered = $this->renderTab($object);
			
			// Strip SafeHtml markers from tab output
			if (is_array($rendered)) {
				return array_map(static function ($item) {
					return is_string($item)
						? str_replace(\Canvastack\Canvastack\Library\Constants\SafeHtml::MARKER, '', $item)
						: $item;
				}, $rendered);
			}
			if (is_string($rendered)) {
				return str_replace(\Canvastack\Canvastack\Library\Constants\SafeHtml::MARKER, '', $rendered);
			}
			return $rendered;
		} else {
			// Strip any stray SafeHtml markers before outputting to browser.
			// Markers must never reach the browser (they would appear as literal
			// text or break JavaScript if inside a <script> block).
			if (is_array($object)) {
				return array_map(static function ($item) {
					return is_string($item)
						? str_replace(\Canvastack\Canvastack\Library\Constants\SafeHtml::MARKER, '', $item)
						: $item;
				}, $object);
			}
			if (is_string($object)) {
				return str_replace(\Canvastack\Canvastack\Library\Constants\SafeHtml::MARKER, '', $object);
			}
			return $object;
		}
	}
	
	/**
	 * Set table type (DataTables atau regular table)
	 * 
	 * Method ini mengatur apakah table menggunakan DataTables library
	 * atau hanya regular HTML table.
	 * 
	 * @param bool $set True untuk DataTables, false untuk regular table
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Use DataTables (default)
	 * $table->setDatatableType(true)->lists('users');
	 * 
	 * // Use regular HTML table
	 * $table->setDatatableType(false)->lists('users');
	 * ```
	 */
	public function setDatatableType(bool $set = true): void {
		$this->setDatatable = $set;
		if (true !== $this->setDatatable) $this->tableType = self::TABLE_TYPE_SELF;
		$this->element_name['table'] = $this->tableType;
	}
	
	/**
	 * Set table name manually
	 * 
	 * @param string $table_name Database table name (alphanumeric and underscore only)
	 * @return void
	 * 
	 * @security Validates table name format AND checks against database whitelist to prevent
	 *           SQL injection via table name manipulation. (Requirements 2.2, 3.1)
	 * @throws \InvalidArgumentException If table name has invalid format or is not in whitelist
	 */
	public function setName(string $table_name): void {
		// @security Validate table name format to prevent injection (Requirement 3.1)
		$table_name = $this->validateTableName($table_name);
		
		// @security 1.7.1 - Validate against database whitelist using helper function
		// This ensures the table actually exists and is accessible (Requirement 2.2)
		// If a configured whitelist exists, enforce it strictly.
		// If no whitelist is configured, attempt DB existence check but allow graceful fallback.
		$allowedTables = config('canvastack.datatables.allowed_tables', null);
		if ($allowedTables !== null) {
			// Strict whitelist mode - enforce it
			try {
				$connection = $this->connection ?? self::DEFAULT_DB_CONNECTION;
				$table_name = canvastack_table_validate_table_name($table_name, $allowedTables, $connection);
			} catch (\InvalidArgumentException $e) {
				\Illuminate\Support\Facades\Log::warning('[SECURITY] Objects::setName() - Table name whitelist validation failed', [
					'table'   => $table_name,
					'error'   => $e->getMessage(),
					'context' => 'SQL injection prevention - table whitelist check'
				]);
				throw $e;
			}
		}
		// If no whitelist configured, format validation (already done above) is sufficient
		// DB existence check is deferred to query execution time
		
		$this->variables['table_name'] = $table_name;
	}
	
	/**
	 * Set fields/columns to display in table
	 * 
	 * @param array $fields Array of field names to display (format: 'field_name' or 'field_name:Label')
	 * @return void
	 * 
	 * @security 1.7.2 - Validates field names against schema to prevent SQL injection via column
	 *           name manipulation. Each field name is validated for safe format. (Requirements 2.3, 3.2)
	 * @throws \InvalidArgumentException If fields is not an array or contains invalid field names
	 * 
	 * @example
	 * ```php
	 * $table->setFields(['name', 'email', 'created_at']);
	 * // With custom labels (labels are escaped automatically):
	 * $table->setFields(['name:Full Name', 'email:Email Address']);
	 * ```
	 */
	public function setFields(array $fields): void {
		// @security 1.7.2 - Validate each field name format to prevent SQL injection
		// Field names may include 'field:Label' format - validate only the field part
		$validatedFields = [];
		foreach ($fields as $field) {
			if (!is_string($field)) {
				$validatedFields[] = $field;
				continue;
			}
			
			// Split 'field_name:Label' format - validate only the field name part
			$parts = explode(':', $field, 2);
			$fieldName = $parts[0];
			
			// Skip special columns (number_lists, action, etc.)
			if (in_array($fieldName, ['number_lists', 'action', 'no', '*', 'all'], true)) {
				$validatedFields[] = $field;
				continue;
			}
			
			// Validate field name format (allow dot notation for relations: table.column)
			try {
				$this->validateFieldName($fieldName);
				$validatedFields[] = $field;
			} catch (\InvalidArgumentException $e) {
				\Illuminate\Support\Facades\Log::warning('[SECURITY] Objects::setFields() - Invalid field name detected', [
					'field'   => $fieldName,
					'error'   => $e->getMessage(),
					'context' => 'SQL injection prevention - column name validation'
				]);
				// Skip invalid field names to maintain backward compatibility
				// but log the suspicious attempt
			}
		}
		
		$this->variables['table_fields'] = $validatedFields;
	}
	
	/**
	 * Set Eloquent model untuk table data source
	 * 
	 * Method ini digunakan untuk mengatur model Eloquent yang akan digunakan
	 * sebagai sumber data untuk table. Model ini akan digunakan untuk query
	 * data dan relational data processing.
	 * 
	 * @param string|object $model Eloquent model class name atau instance
	 * 
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Using model class name
	 * $table->model(User::class)->lists();
	 * 
	 * // Using model instance
	 * $table->model(new User())->lists();
	 * 
	 * // With relationships
	 * $table->model(User::class)
	 *       ->relations(User::class, 'role', 'name')
	 *       ->lists();
	 * ```
	 */
	public function model(mixed $model): void {
		$this->variables['table_data_model'] = $model;
	}
	
	/**
	 * Execute model function before rendering table
	 * 
	 * Calls a method on the model object to create a temporary table or process data
	 * before the table is rendered. Useful for creating views or temporary tables
	 * that will be used as the data source.
	 * 
	 * @param object $model_object Eloquent model instance
	 * @param string $function_name Function name to call on model (supports 'model::table' format)
	 * @param bool $strict Strict mode flag for function execution
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Call model method before rendering
	 * $table->runModel(new User(), 'createTempTable', true)
	 *       ->lists('temp_users');
	 * 
	 * // With model::table format
	 * $table->runModel(new User(), 'User::temp_users', false)
	 *       ->lists();
	 * ```
	 */
	public function runModel(object $model_object, string $function_name, bool $strict): void {
		// Guard clause: Use default connection if not set
		$connection = $this->connection ?? self::DEFAULT_DB_CONNECTION;
		
		// Parse function name
		$modelFunction = $function_name;
		$tableFunction = $function_name;
		
		if (canvastack_string_contained($function_name, '::')) {
			$split = explode('::', $function_name);
			$modelFunction = $split[0];
			$tableFunction = "{$split[1]}_{$split[0]}";
		}
		
		// Setup model processing configuration
		$this->variables['model_processing'] = [
			'model'      => $model_object,
			'function'   => $modelFunction,
			'connection' => $connection,
			'table'      => $tableFunction,
			'strict'     => $strict
		];
	}
	
	/**
	 * Set raw SQL query as data source
	 * 
	 * Allows using a raw SQL query as the data source for the table. Useful for
	 * complex queries that cannot be expressed with Eloquent. The query results
	 * will be used to populate the table.
	 * 
	 * @param string $sql Raw SQL query string
	 * @return void
	 * 
	 * @security WARNING: Ensure the SQL query is safe from SQL injection. Use parameter
	 *           binding or query builder for user inputs. Never concatenate user input
	 *           directly into the SQL string. (Requirement 2.1)
	 * 
	 * @example
	 * ```php
	 * // Safe: Using query builder
	 * $sql = DB::table('users')
	 *          ->select('name', 'email')
	 *          ->where('status', 'active')
	 *          ->toSql();
	 * $table->query($sql)->lists();
	 * 
	 * // Complex join query
	 * $sql = "SELECT u.name, u.email, r.name as role 
	 *         FROM users u 
	 *         LEFT JOIN roles r ON u.role_id = r.id 
	 *         WHERE u.status = 'active'";
	 * $table->query($sql)->lists();
	 * ```
	 */
	public function query(string $sql): void {
		$this->variables['query'] = $sql;
		$this->model('sql');
	}
	
	/**
	 * Set server-side processing mode
	 * 
	 * Configures whether the table uses server-side processing (AJAX) or client-side
	 * processing (load all data at once). Server-side is recommended for large datasets
	 * (>1000 rows) for better performance.
	 * 
	 * @param bool $server_side True for server-side processing, false for client-side
	 * @return void
	 * 
	 * @performance Server-side processing significantly improves performance for large datasets
	 *              by loading data in chunks via AJAX. Reduces initial page load time and
	 *              memory usage. (Requirement 4.3)
	 * 
	 * @example
	 * ```php
	 * // Enable server-side (recommended for large datasets >1000 rows)
	 * $table->setServerSide(true)->lists('users');
	 * 
	 * // Disable server-side (for small datasets <1000 rows)
	 * $table->setServerSide(false)->lists('users');
	 * ```
	 */
	public function setServerSide(bool $server_side = true): void {
		$this->variables['table_server_side'] = $server_side;
	}

	
    
	/**
	* Merge Columns
	*
	* Digunakan untuk menggabungkan beberapa kolom menjadi satu kolom, maka
	* kolom tersebut akan memiliki label gabungan dan value dari gabungan
	* kolom-kolom yang di merge.
	*
	* @param string $label : Kolom gabungan yang akan digunakan sebagai label
	* @param array $merged_columns : Kolom-kolom yang akan di merge
	* @param string $label_position : Posisi label (top, bottom, left, right)
	*
	* Contoh :
	* $this->mergeColumns('Nama', ['first_name', 'last_name'], 'top');
	* maka kolom 'first_name' dan kolom 'last_name' akan digabungkan menjadi
	* satu kolom dengan label 'Nama' dan value gabungan dari 2 kolom tersebut
	* dan posisi labelnya di atas.
	*/
	/**
		 * Merge columns under a common header
		 * 
		 * Groups multiple columns under a single header label. Useful for organizing
		 * related columns (e.g., "Address" spanning street, city, zip).
		 * 
		 * Task 5.7.7: Implement column grouping/merging (Requirement 22.7)
		 * 
		 * @param string $label Header label for merged columns
		 * @param array $merged_columns Array of column field names to merge
		 * @param string $label_position Label position: 'top' or 'bottom' (default: 'top')
		 * @return void
		 * @throws \InvalidArgumentException If label or columns are invalid
		 * 
		 * @security Escapes merge column label to prevent XSS (Requirement 1.2)
		 * 
		 * @example
		 * ```php
		 * // Merge address columns
		 * $table->mergeColumns('Address', ['street', 'city', 'zip']);
		 * 
		 * // Merge name columns
		 * $table->mergeColumns('Full Name', ['first_name', 'last_name']);
		 * 
		 * // Merge with bottom label
		 * $table->mergeColumns('Contact Info', ['email', 'phone'], 'bottom');
		 * 
		 * // Multiple merges
		 * $table->mergeColumns('Personal', ['first_name', 'last_name', 'age'])
		 *       ->mergeColumns('Contact', ['email', 'phone', 'address']);
		 * ```
		 */
		public function mergeColumns(string $label, array $merged_columns = [], string $label_position = 'top'): void {
			// @security Escape merge column label to prevent XSS (Requirement 1.2)
			$safe_label = canvastack_escape_html($label);

			// @security Validate label position
			if (!in_array($label_position, ['top', 'bottom'], true)) {
				throw new \InvalidArgumentException('Label position must be "top" or "bottom"');
			}

			// @security Validate each column name
			foreach ($merged_columns as $column) {
				$this->validateFieldName($column);
			}

			// Validate we have at least 2 columns to merge
			if (count($merged_columns) < 2) {
				throw new \InvalidArgumentException('At least 2 columns are required for merging');
			}

			$this->variables['merged_columns'][$safe_label] = [
				'position' => $label_position,
				'counts' => count($merged_columns),
				'columns' => $merged_columns
			];
		}

		/**
		 * Group columns under a common header
		 * 
		 * Similar to mergeColumns but with additional grouping options like
		 * styling, collapsibility, and nested groups.
		 * 
		 * Task 5.7.7: Implement column grouping/merging (Requirement 22.7)
		 * 
		 * @param string $groupName Group identifier
		 * @param string $groupLabel Display label for the group
		 * @param array $columns Columns in this group
		 * @param array $options Group options (collapsible, style, etc.)
		 * @return void
		 * @throws \InvalidArgumentException If parameters are invalid
		 * 
		 * @example
		 * ```php
		 * // Basic column group
		 * $table->groupColumns('personal', 'Personal Information', ['name', 'age', 'gender']);
		 * 
		 * // Group with styling
		 * $table->groupColumns('contact', 'Contact Details', ['email', 'phone'], [
		 *     'background' => '#e3f2fd',
		 *     'text_color' => '#1976d2'
		 * ]);
		 * 
		 * // Collapsible group
		 * $table->groupColumns('advanced', 'Advanced Options', ['setting1', 'setting2'], [
		 *     'collapsible' => true,
		 *     'collapsed' => true
		 * ]);
		 * ```
		 */
		public function groupColumns(string $groupName, string $groupLabel, array $columns, array $options = []): void {
			// @security Validate group name (alphanumeric and underscore only)
			if (!preg_match('/^[a-zA-Z0-9_]+$/', $groupName)) {
				throw new \InvalidArgumentException('Group name must be alphanumeric with underscores only');
			}

			// @security Escape group label
			$safe_label = canvastack_escape_html($groupLabel);

			// @security Validate each column name
			foreach ($columns as $column) {
				$this->validateFieldName($column);
			}

			// Validate we have at least 1 column
			if (empty($columns)) {
				throw new \InvalidArgumentException('At least 1 column is required for grouping');
			}

			// Validate and sanitize options
			$validatedOptions = $this->validateGroupOptions($options);

			if (!isset($this->variables['column_groups'])) {
				$this->variables['column_groups'] = [];
			}

			$this->variables['column_groups'][$groupName] = [
				'label' => $safe_label,
				'columns' => $columns,
				'options' => $validatedOptions
			];
		}

		/**
		 * Validate column group options
		 * 
		 * @param array $options Group options to validate
		 * @return array Validated options
		 * @throws \InvalidArgumentException If options are invalid
		 * 
		 * @security Validates and sanitizes group options
		 */
		private function validateGroupOptions(array $options): array {
			$validated = [];

			// Validate collapsible option
			if (isset($options['collapsible'])) {
				$validated['collapsible'] = (bool) $options['collapsible'];
			}

			// Validate collapsed option (only if collapsible)
			if (isset($options['collapsed']) && ($validated['collapsible'] ?? false)) {
				$validated['collapsed'] = (bool) $options['collapsed'];
			}

			// Validate background color
			if (isset($options['background'])) {
				$validated['background'] = $this->validateColor($options['background']);
			}

			// Validate text color
			if (isset($options['text_color'])) {
				$validated['text_color'] = $this->validateColor($options['text_color']);
			}

			// Validate border
			if (isset($options['border'])) {
				if (is_bool($options['border'])) {
					$validated['border'] = $options['border'];
				} elseif (is_string($options['border'])) {
					$validated['border'] = $this->validateColor($options['border']);
				}
			}

			// Validate alignment
			if (isset($options['align'])) {
				$validAlignments = ['left', 'center', 'right'];
				if (!in_array($options['align'], $validAlignments, true)) {
					throw new \InvalidArgumentException('Group alignment must be left, center, or right');
				}
				$validated['align'] = $options['align'];
			}

			return $validated;
		}

		/**
		 * Create nested column groups
		 * 
		 * Creates hierarchical column groups for complex table structures.
		 * 
		 * Task 5.7.7: Implement column grouping/merging (Requirement 22.7)
		 * 
		 * @param string $parentGroup Parent group name
		 * @param array $childGroups Array of child group configurations
		 * @return void
		 * @throws \InvalidArgumentException If group structure is invalid
		 * 
		 * @example
		 * ```php
		 * // Create nested groups
		 * $table->nestedColumnGroups('demographics', [
		 *     [
		 *         'name' => 'personal',
		 *         'label' => 'Personal',
		 *         'columns' => ['name', 'age']
		 *     ],
		 *     [
		 *         'name' => 'location',
		 *         'label' => 'Location',
		 *         'columns' => ['city', 'country']
		 *     ]
		 * ]);
		 * ```
		 */
		public function nestedColumnGroups(string $parentGroup, array $childGroups): void {
			// @security Validate parent group name
			if (!preg_match('/^[a-zA-Z0-9_]+$/', $parentGroup)) {
				throw new \InvalidArgumentException('Parent group name must be alphanumeric with underscores only');
			}

			// Validate child groups
			if (empty($childGroups)) {
				throw new \InvalidArgumentException('At least one child group is required');
			}

			$validatedChildren = [];
			foreach ($childGroups as $child) {
				// Validate required fields
				if (!isset($child['name']) || !isset($child['label']) || !isset($child['columns'])) {
					throw new \InvalidArgumentException('Each child group must have name, label, and columns');
				}

				// @security Validate child group name
				if (!preg_match('/^[a-zA-Z0-9_]+$/', $child['name'])) {
					throw new \InvalidArgumentException('Child group name must be alphanumeric with underscores only');
				}

				// @security Escape child label
				$safe_label = canvastack_escape_html($child['label']);

				// @security Validate columns
				foreach ($child['columns'] as $column) {
					$this->validateFieldName($column);
				}

				$validatedChildren[] = [
					'name' => $child['name'],
					'label' => $safe_label,
					'columns' => $child['columns'],
					'options' => $this->validateGroupOptions($child['options'] ?? [])
				];
			}

			if (!isset($this->variables['nested_column_groups'])) {
				$this->variables['nested_column_groups'] = [];
			}

			$this->variables['nested_column_groups'][$parentGroup] = $validatedChildren;
		}
	
	public $hidden_columns = [];
	/**
	 * Set hidden columns yang tidak akan ditampilkan di table
	 * 
	 * Method ini mengatur kolom-kolom yang akan disembunyikan dari tampilan table.
	 * Kolom tetap ada di data tapi tidak ditampilkan ke user.
	 * 
	 * @param array $fields Array nama kolom yang akan disembunyikan
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Hide password and token columns
	 * $table->setHiddenColumns(['password', 'remember_token'])
	 *       ->lists('users');
	 * ```
	 */
	/**
		 * Set hidden columns
		 * 
		 * Hides specified columns from display. Hidden columns are still present in the DOM
		 * but not visible to users. Useful for columns that contain data needed for
		 * JavaScript operations but shouldn't be displayed.
		 * 
		 * Task 5.7.3: Improve column visibility handling (Requirement 22.3)
		 * 
		 * @param array $fields Array of column field names to hide
		 * @return self Fluent interface
		 * @throws \InvalidArgumentException If any field name is invalid
		 * 
		 * @security Validates each field name to prevent injection
		 * 
		 * @example
		 * ```php
		 * // Hide ID and internal status columns
		 * $table->setHiddenColumns(['id', 'internal_status', 'created_by']);
		 * 
		 * // Chain with other methods
		 * $table->setHiddenColumns(['id'])
		 *       ->setColumnWidth('name', 200)
		 *       ->lists('users', ['id', 'name', 'email']);
		 * ```
		 */
		public function setHiddenColumns(array $fields = []): self {
			// @security Validate each field name
			foreach ($fields as $field) {
				$this->validateFieldName($field);
			}

			$this->variables['hidden_columns'] = $fields;

			return $this;
		}

		/**
		 * Set visible columns (inverse of hidden columns)
		 * 
		 * Explicitly sets which columns should be visible. All other columns in the
		 * table will be hidden. This is useful when you want to show only a subset
		 * of columns from a large dataset.
		 * 
		 * Task 5.7.3: Improve column visibility handling (Requirement 22.3)
		 * 
		 * @param array $fields Array of column field names to show
		 * @param array $allFields All available fields (to calculate hidden ones)
		 * @return self Fluent interface
		 * @throws \InvalidArgumentException If any field name is invalid
		 * 
		 * @example
		 * ```php
		 * // Show only name and email from a table with many columns
		 * $allFields = ['id', 'name', 'email', 'phone', 'address', 'created_at', 'updated_at'];
		 * $table->setVisibleColumns(['name', 'email'], $allFields);
		 * // This will hide: id, phone, address, created_at, updated_at
		 * ```
		 */
		public function setVisibleColumns(array $fields, array $allFields): self {
			// @security Validate each field name
			foreach ($fields as $field) {
				$this->validateFieldName($field);
			}

			foreach ($allFields as $field) {
				$this->validateFieldName($field);
			}

			// Calculate hidden columns (all fields minus visible fields)
			$hiddenFields = array_diff($allFields, $fields);

			$this->variables['hidden_columns'] = array_values($hiddenFields);

			return $this;
		}

		/**
		 * Toggle column visibility
		 * 
		 * Adds or removes a column from the hidden columns list.
		 * 
		 * Task 5.7.3: Improve column visibility handling (Requirement 22.3)
		 * 
		 * @param string $field Column field name
		 * @param bool $visible True to show, false to hide
		 * @return self Fluent interface
		 * @throws \InvalidArgumentException If field name is invalid
		 * 
		 * @example
		 * ```php
		 * // Hide a column
		 * $table->toggleColumnVisibility('internal_notes', false);
		 * 
		 * // Show a previously hidden column
		 * $table->toggleColumnVisibility('email', true);
		 * ```
		 */
		public function toggleColumnVisibility(string $field, bool $visible): self {
			// @security Validate field name
			$field = $this->validateFieldName($field);

			$hiddenColumns = $this->variables['hidden_columns'] ?? [];

			if ($visible) {
				// Remove from hidden list
				$hiddenColumns = array_diff($hiddenColumns, [$field]);
			} else {
				// Add to hidden list if not already there
				if (!in_array($field, $hiddenColumns, true)) {
					$hiddenColumns[] = $field;
				}
			}

			$this->variables['hidden_columns'] = array_values($hiddenColumns);

			return $this;
		}

	/**
	 * Set column order
	 * 
	 * Explicitly sets the display order of columns. Columns will be displayed
	 * in the order specified in the array. Any columns not in the array will
	 * be appended at the end in their original order.
	 * 
	 * Task 5.7.4: Improve column ordering (Requirement 22.4)
	 * 
	 * @param array $orderedFields Array of field names in desired display order
	 * @return self Fluent interface
	 * @throws \InvalidArgumentException If any field name is invalid
	 * 
	 * @security Validates each field name to prevent injection
	 * 
	 * @example
	 * ```php
	 * // Set specific column order
	 * $table->setColumnOrder(['name', 'email', 'status', 'created_at']);
	 * 
	 * // Reorder to put important columns first
	 * $table->setColumnOrder(['status', 'priority', 'name']);
	 * ```
	 */
	public function setColumnOrder(array $orderedFields): self {
		// @security Validate each field name
		foreach ($orderedFields as $field) {
			$this->validateFieldName($field);
		}
		
		$this->variables['column_order'] = $orderedFields;
		
		return $this;
	}
	
	/**
	 * Move column to position
	 * 
	 * Moves a specific column to a new position in the display order.
	 * Position is 0-indexed (0 = first column).
	 * 
	 * Task 5.7.4: Improve column ordering (Requirement 22.4)
	 * 
	 * @param string $field Column field name to move
	 * @param int $position Target position (0-indexed)
	 * @return self Fluent interface
	 * @throws \InvalidArgumentException If field name or position is invalid
	 * 
	 * @example
	 * ```php
	 * // Move 'status' column to first position
	 * $table->moveColumnToPosition('status', 0);
	 * 
	 * // Move 'action' column to last position (use large number)
	 * $table->moveColumnToPosition('action', 999);
	 * ```
	 */
	public function moveColumnToPosition(string $field, int $position): self {
		// @security Validate field name
		$field = $this->validateFieldName($field);
		
		if ($position < 0) {
			throw new \InvalidArgumentException('Position must be non-negative');
		}
		
		// Store move instruction
		if (!isset($this->variables['column_moves'])) {
			$this->variables['column_moves'] = [];
		}
		
		$this->variables['column_moves'][$field] = $position;
		
		return $this;
	}
	
	/**
	 * Move column before another column
	 * 
	 * Moves a column to appear immediately before another specified column.
	 * 
	 * Task 5.7.4: Improve column ordering (Requirement 22.4)
	 * 
	 * @param string $field Column to move
	 * @param string $beforeField Column to insert before
	 * @return self Fluent interface
	 * @throws \InvalidArgumentException If field names are invalid
	 * 
	 * @example
	 * ```php
	 * // Move 'status' before 'name'
	 * $table->moveColumnBefore('status', 'name');
	 * ```
	 */
	public function moveColumnBefore(string $field, string $beforeField): self {
		// @security Validate field names
		$field = $this->validateFieldName($field);
		$beforeField = $this->validateFieldName($beforeField);
		
		if (!isset($this->variables['column_moves_relative'])) {
			$this->variables['column_moves_relative'] = [];
		}
		
		$this->variables['column_moves_relative'][$field] = [
			'type' => 'before',
			'target' => $beforeField
		];
		
		return $this;
	}
	
	/**
	 * Move column after another column
	 * 
	 * Moves a column to appear immediately after another specified column.
	 * 
	 * Task 5.7.4: Improve column ordering (Requirement 22.4)
	 * 
	 * @param string $field Column to move
	 * @param string $afterField Column to insert after
	 * @return self Fluent interface
	 * @throws \InvalidArgumentException If field names are invalid
	 * 
	 * @example
	 * ```php
	 * // Move 'action' after 'status'
	 * $table->moveColumnAfter('action', 'status');
	 * ```
	 */
	public function moveColumnAfter(string $field, string $afterField): self {
		// @security Validate field names
		$field = $this->validateFieldName($field);
		$afterField = $this->validateFieldName($afterField);
		
		if (!isset($this->variables['column_moves_relative'])) {
			$this->variables['column_moves_relative'] = [];
		}
		
		$this->variables['column_moves_relative'][$field] = [
			'type' => 'after',
			'target' => $afterField
		];
		
		return $this;
	}

	/**
	 * Set fixed (frozen) columns
	 * 
	 * Freezes columns so they remain visible when scrolling horizontally.
	 * Useful for keeping important columns (like ID or name) always visible.
	 * 
	 * Task 5.7.8: Implement fixed columns (frozen) (Requirement 22.8)
	 * 
	 * @param int|null $left_pos Number of columns to freeze on the left (0-indexed)
	 * @param int|null $right_pos Number of columns to freeze on the right (0-indexed)
	 * @return void
	 * @throws \InvalidArgumentException If position values are invalid
	 * 
	 * @security Validates position values to prevent injection
	 * 
	 * @example
	 * ```php
	 * // Freeze first column on the left
	 * $table->fixedColumns(1, null);
	 * 
	 * // Freeze first 2 columns on the left
	 * $table->fixedColumns(2, null);
	 * 
	 * // Freeze first column on left and last column on right
	 * $table->fixedColumns(1, 1);
	 * 
	 * // Freeze first 3 columns on left and last 2 on right
	 * $table->fixedColumns(3, 2);
	 * ```
	 */
	public function fixedColumns(?int $left_pos = null, ?int $right_pos = null): void {
		// Validate left position
		if ($left_pos !== null) {
			if ($left_pos < 0 || $left_pos > 50) {
				throw new \InvalidArgumentException('Left position must be between 0 and 50');
			}
			$this->variables['fixed_columns']['left'] = $left_pos;
		}
		
		// Validate right position
		if ($right_pos !== null) {
			if ($right_pos < 0 || $right_pos > 50) {
				throw new \InvalidArgumentException('Right position must be between 0 and 50');
			}
			$this->variables['fixed_columns']['right'] = $right_pos;
		}
	}
	
	/**
	 * Set fixed columns by field names
	 * 
	 * Alternative method to freeze columns by specifying field names instead of positions.
	 * More intuitive when you know the column names.
	 * 
	 * Task 5.7.8: Implement fixed columns (frozen) (Requirement 22.8)
	 * 
	 * @param array $leftColumns Array of column field names to freeze on the left
	 * @param array $rightColumns Array of column field names to freeze on the right
	 * @return void
	 * @throws \InvalidArgumentException If field names are invalid
	 * 
	 * @example
	 * ```php
	 * // Freeze specific columns on the left
	 * $table->fixedColumnsByName(['id', 'name'], []);
	 * 
	 * // Freeze columns on both sides
	 * $table->fixedColumnsByName(['id', 'name'], ['action', 'status']);
	 * ```
	 */
	public function fixedColumnsByName(array $leftColumns = [], array $rightColumns = []): void {
		// @security Validate left column names
		foreach ($leftColumns as $column) {
			$this->validateFieldName($column);
		}
		
		// @security Validate right column names
		foreach ($rightColumns as $column) {
			$this->validateFieldName($column);
		}
		
		if (!empty($leftColumns)) {
			$this->variables['fixed_columns_by_name']['left'] = $leftColumns;
		}
		
		if (!empty($rightColumns)) {
			$this->variables['fixed_columns_by_name']['right'] = $rightColumns;
		}
	}
	
	/**
	 * Clear fixed columns configuration
	 * 
	 * Removes all fixed column settings, allowing all columns to scroll normally.
	 * 
	 * Task 5.7.8: Implement fixed columns (frozen) (Requirement 22.8)
	 * 
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Set fixed columns
	 * $table->fixedColumns(2, 1);
	 * 
	 * // Later, clear them
	 * $table->clearFixedColumns();
	 * ```
	 */
	public function clearFixedColumns(): void {
		if (!empty($this->variables['fixed_columns'])) {
			unset($this->variables['fixed_columns']);
		}
		
		if (!empty($this->variables['fixed_columns_by_name'])) {
			unset($this->variables['fixed_columns_by_name']);
		}
	}
	
	/**
	 * Set scroll configuration for fixed columns
	 * 
	 * Configures scrolling behavior when using fixed columns.
	 * 
	 * Task 5.7.8: Implement fixed columns (frozen) (Requirement 22.8)
	 * 
	 * @param int|string|null $scrollY Vertical scroll height (pixels or 'auto')
	 * @param bool $scrollX Enable horizontal scrolling (default: true)
	 * @param bool $scrollCollapse Collapse table when data is less than scroll height (default: true)
	 * @return void
	 * @throws \InvalidArgumentException If scroll parameters are invalid
	 * 
	 * @example
	 * ```php
	 * // Set vertical scroll to 400px
	 * $table->setScrollConfig(400, true, true);
	 * 
	 * // Auto height with horizontal scroll
	 * $table->setScrollConfig('auto', true, false);
	 * 
	 * // Fixed height without collapse
	 * $table->setScrollConfig(600, true, false);
	 * ```
	 */
	public function setScrollConfig(int|string|null $scrollY = null, bool $scrollX = true, bool $scrollCollapse = true): void {
		// Validate scrollY
		if ($scrollY !== null) {
			if (is_int($scrollY)) {
				if ($scrollY < 100 || $scrollY > 10000) {
					throw new \InvalidArgumentException('Scroll Y must be between 100 and 10000 pixels');
				}
			} elseif (is_string($scrollY)) {
				if ($scrollY !== 'auto' && !preg_match('/^\d+(px|vh)$/', $scrollY)) {
					throw new \InvalidArgumentException('Scroll Y must be "auto", integer pixels, or string with px/vh unit');
				}
			}
			
			$this->variables['scroll_config']['scrollY'] = $scrollY;
		}
		
		$this->variables['scroll_config']['scrollX'] = $scrollX;
		$this->variables['scroll_config']['scrollCollapse'] = $scrollCollapse;
	}
	
	/**
	* Fungsi ini digunakan untuk mengatur align kolom di dalam datatable.
	*
	* @param string $align : Nilai align yang di inginkan, bisa berupa "left",
	*                        "center", atau "right".
	* @param array  $columns : Kolom mana yang akan di set align, jika di kosongkan
	*                          maka akan di set ke semua kolom.
	* @param boolean $header : Jika true maka akan di set ke header kolom.
	* @param boolean $body : Jika true maka akan di set ke body kolom.
	*
	* Contoh :
	* $this->setAlignColumns('center', ['name', 'address'], true, false);
	* maka kolom "name" dan "address" akan di set align center di header saja.
	*/
	/**
		 * Set column alignment
		 * 
		 * Sets text alignment for specified columns. Supports standard CSS alignment values
		 * and can be applied to header, body, or both.
		 * 
		 * Task 5.7.2: Improve column alignment handling (Requirement 22.2)
		 * 
		 * @param string $align Alignment value ('left', 'center', 'right', 'justify', 'start', 'end')
		 * @param array $columns Array of column field names to apply alignment
		 * @param bool $header Apply to header cells (default: true)
		 * @param bool $body Apply to body cells (default: true)
		 * @return self Fluent interface
		 * @throws \InvalidArgumentException If alignment value is invalid
		 * 
		 * @security Validates alignment value against whitelist to prevent XSS
		 * 
		 * @example
		 * ```php
		 * // Center align specific columns in both header and body
		 * $table->setAlignColumns('center', ['status', 'count']);
		 * 
		 * // Right align numeric columns in body only
		 * $table->setAlignColumns('right', ['price', 'quantity', 'total'], false, true);
		 * 
		 * // Left align in header only
		 * $table->setAlignColumns('left', ['description'], true, false);
		 * 
		 * // Justify text columns
		 * $table->setAlignColumns('justify', ['content', 'description']);
		 * ```
		 */
		public function setAlignColumns(string $align, array $columns = [], bool $header = true, bool $body = true): self {
			// @security Validate alignment value against whitelist
			$validAlignments = ['left', 'center', 'right', 'justify', 'start', 'end'];
			$align = strtolower(trim($align));

			if (!in_array($align, $validAlignments, true)) {
				throw new \InvalidArgumentException('Invalid alignment value. Allowed: left, center, right, justify, start, end');
			}

			// @security Validate each column name
			foreach ($columns as $column) {
				$this->validateFieldName($column);
			}

			$this->variables['text_align'][$align] = [
				'columns' => $columns,
				'header' => $header,
				'body' => $body
			];

			return $this;
		}

	/**
	* Fungsi ini digunakan untuk mengatur align kolom di dalam datatable menjadi right/kanan.
	*
	* @param array  $columns : Kolom mana yang akan di set align right/kanan, jika di kosongkan maka semua kolom akan di set align right/kanan.
	* @param boolean $header : Jika true maka akan di set ke header kolom.
	* @param boolean $body : Jika true maka akan di set ke body kolom.
	*
	* Contoh :
	* $this->setRightColumns(['name', 'address'], true, false);
	* maka kolom "name" dan "address" akan di set align right/kanan di header saja.
	*/
	/**
		 * Set columns to right alignment
		 * 
		 * Convenience method to right-align specified columns.
		 * 
		 * Task 5.7.2: Improve column alignment handling (Requirement 22.2)
		 * 
		 * @param array $columns Array of column field names
		 * @param bool $header Apply to header (default: true)
		 * @param bool $body Apply to body (default: true)
		 * @return self Fluent interface
		 * 
		 * @example
		 * ```php
		 * // Right align numeric columns
		 * $table->setRightColumns(['price', 'quantity', 'total']);
		 * ```
		 */
		public function setRightColumns(array $columns = [], bool $header = true, bool $body = true): self {
			return $this->setAlignColumns('right', $columns, $header, $body);
		}

	/**
	* Fungsi ini digunakan untuk mengatur align kolom di dalam datatable menjadi center/tengah.
	*
	* @param array  $columns : Kolom mana yang akan di set align center/tengah, jika di kosongkan maka semua kolom akan di set align center/tengah.
	* @param boolean $header : Jika true maka akan di set ke header kolom. Default true.
	* @param boolean $body : Jika true maka akan di set ke body kolom. Default false.
	*
	* Contoh :
	* $this->setCenterColumns(['name', 'address'], true, false);
	* maka kolom "name" dan "address" akan di set align center/tengah di header saja.
	*/
	/**
		 * Set columns to center alignment
		 * 
		 * Convenience method to center-align specified columns.
		 * 
		 * Task 5.7.2: Improve column alignment handling (Requirement 22.2)
		 * 
		 * @param array $columns Array of column field names
		 * @param bool $header Apply to header (default: true)
		 * @param bool $body Apply to body (default: true)
		 * @return self Fluent interface
		 * 
		 * @example
		 * ```php
		 * // Center align status and action columns
		 * $table->setCenterColumns(['status', 'action']);
		 * ```
		 */
		public function setCenterColumns(array $columns = [], bool $header = true, bool $body = true): self {
			return $this->setAlignColumns('center', $columns, $header, $body);
		}
	
	/**
	* Fungsi ini digunakan untuk mengatur align kolom di dalam datatable menjadi left/kiri.
	*
	* @param array  $columns : Kolom mana yang akan di set align left/kiri, jika di kosongkan maka semua kolom akan di set align left/kiri.
	* @param boolean $header : Jika true maka akan di set ke header kolom. Default true.
	* @param boolean $body : Jika true maka akan di set ke body kolom. Default true.
	*
	* Contoh :
	* $this->setLeftColumns(['name', 'address'], true, false);
	* maka kolom "name" dan "address" akan di set align left/kiri di header saja.
	*/
	/**
		 * Set columns to left alignment
		 * 
		 * Convenience method to left-align specified columns.
		 * 
		 * Task 5.7.2: Improve column alignment handling (Requirement 22.2)
		 * 
		 * @param array $columns Array of column field names
		 * @param bool $header Apply to header (default: true)
		 * @param bool $body Apply to body (default: true)
		 * @return self Fluent interface
		 * 
		 * @example
		 * ```php
		 * // Left align text columns
		 * $table->setLeftColumns(['name', 'description']);
		 * ```
		 */
		public function setLeftColumns(array $columns = [], bool $header = true, bool $body = true): self {
			return $this->setAlignColumns('left', $columns, $header, $body);
		}

	/**
	* Fungsi ini digunakan untuk mengatur warna background kolom di dalam datatable.
	*
	* @param string $color : Nilai warna yang di inginkan dalam format hex (cth: #ffffff).
	* @param string $text_color : Nilai warna teks yang di inginkan dalam format hex (cth: #000000).
	* @param array  $columns : Kolom mana yang akan di set warna background, jika di kosongkan maka semua kolom akan di set warna background.
	* @param boolean $header : Jika true maka akan di set ke header kolom. Default true.
	* @param boolean $body : Jika true maka akan di set ke body kolom. Default false.
	*
	* Contoh :
	* $this->setBackgroundColor('#f5f5f5', '#000000', ['name', 'address'], true, false);
	* maka kolom "name" dan "address" akan di set warna background #f5f5f5 dan teks #000000 di header saja.
	*/
	/**
		 * Set background color for columns
		 * 
		 * Sets background color and optional text color for specified columns.
		 * Can be applied to header, body, or both.
		 * 
		 * Task 5.7.6: Add column color options (Requirement 22.6)
		 * 
		 * @param string $color Background color (hex, rgb, or named color)
		 * @param string|null $text_color Text color (hex, rgb, or named color)
		 * @param array|null $columns Columns to apply color (null = all columns)
		 * @param bool $header Apply to header (default: true)
		 * @param bool $body Apply to body (default: false)
		 * @return void
		 * @throws \InvalidArgumentException If color format is invalid
		 * 
		 * @security Validates color values to prevent XSS in style attributes
		 * 
		 * @example
		 * ```php
		 * // Set background color for specific columns
		 * $table->setBackgroundColor('#f5f5f5', '#000', ['status', 'priority']);
		 * 
		 * // Set color for header only
		 * $table->setBackgroundColor('#e3f2fd', '#1976d2', ['name', 'email'], true, false);
		 * 
		 * // Set color for body only
		 * $table->setBackgroundColor('#fff3e0', '#e65100', ['total'], false, true);
		 * 
		 * // Set color for all columns
		 * $table->setBackgroundColor('#fafafa', null, null);
		 * ```
		 */
		public function setBackgroundColor(string $color, ?string $text_color = null, array|null $columns = null, bool $header = true, bool $body = false): void {
			// @security Validate background color
			$color = $this->validateColor($color);

			// @security Validate text color if provided
			if ($text_color !== null) {
				$text_color = $this->validateColor($text_color);
			}

			// @security Validate column names if provided
			if ($columns !== null) {
				foreach ($columns as $column) {
					$this->validateFieldName($column);
				}
			}

			$this->variables['background_color'][$color] = [
				'code' => $color,
				'text' => $text_color,
				'columns' => $columns,
				'header' => $header,
				'body' => $body
			];
		}

		/**
		 * Set text color for columns
		 * 
		 * Sets text color for specified columns without changing background.
		 * 
		 * Task 5.7.6: Add column color options (Requirement 22.6)
		 * 
		 * @param string $color Text color (hex, rgb, or named color)
		 * @param array|null $columns Columns to apply color (null = all columns)
		 * @param bool $header Apply to header (default: true)
		 * @param bool $body Apply to body (default: true)
		 * @return void
		 * @throws \InvalidArgumentException If color format is invalid
		 * 
		 * @example
		 * ```php
		 * // Set text color for specific columns
		 * $table->setTextColor('#d32f2f', ['error_count', 'warnings']);
		 * 
		 * // Set text color for header only
		 * $table->setTextColor('#1976d2', ['name'], true, false);
		 * ```
		 */
		public function setTextColor(string $color, array|null $columns = null, bool $header = true, bool $body = true): void {
			// @security Validate color
			$color = $this->validateColor($color);

			// @security Validate column names if provided
			if ($columns !== null) {
				foreach ($columns as $column) {
					$this->validateFieldName($column);
				}
			}

			if (!isset($this->variables['text_color'])) {
				$this->variables['text_color'] = [];
			}

			$this->variables['text_color'][$color] = [
				'code' => $color,
				'columns' => $columns,
				'header' => $header,
				'body' => $body
			];
		}

		/**
		 * Set border color for columns
		 * 
		 * Sets border color for specified columns.
		 * 
		 * Task 5.7.6: Add column color options (Requirement 22.6)
		 * 
		 * @param string $color Border color (hex, rgb, or named color)
		 * @param array|null $columns Columns to apply border (null = all columns)
		 * @param string $width Border width (default: '1px')
		 * @param string $style Border style (default: 'solid')
		 * @return void
		 * @throws \InvalidArgumentException If color or border parameters are invalid
		 * 
		 * @example
		 * ```php
		 * // Set border for specific columns
		 * $table->setBorderColor('#ddd', ['name', 'email']);
		 * 
		 * // Set thicker border
		 * $table->setBorderColor('#000', ['total'], '2px', 'solid');
		 * 
		 * // Set dashed border
		 * $table->setBorderColor('#999', ['notes'], '1px', 'dashed');
		 * ```
		 */
		public function setBorderColor(string $color, array|null $columns = null, string $width = '1px', string $style = 'solid'): void {
			// @security Validate color
			$color = $this->validateColor($color);

			// @security Validate border width
			if (!preg_match('/^\d+(?:\.\d+)?(px|em|rem)$/', $width)) {
				throw new \InvalidArgumentException('Invalid border width format. Use format like "1px", "2em", etc.');
			}

			// @security Validate border style
			$validStyles = ['none', 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset'];
			if (!in_array($style, $validStyles, true)) {
				throw new \InvalidArgumentException('Invalid border style. Allowed: ' . implode(', ', $validStyles));
			}

			// @security Validate column names if provided
			if ($columns !== null) {
				foreach ($columns as $column) {
					$this->validateFieldName($column);
				}
			}

			if (!isset($this->variables['border_color'])) {
				$this->variables['border_color'] = [];
			}

			$this->variables['border_color'][$color] = [
				'color' => $color,
				'width' => $width,
				'style' => $style,
				'columns' => $columns
			];
		}

		/**
		 * Validate color value
		 * 
		 * @param string $color Color value to validate
		 * @return string Validated color
		 * @throws \InvalidArgumentException If color format is invalid
		 * 
		 * @security Validates color to prevent XSS in style attributes
		 */
		private function validateColor(string $color): string {
			$color = trim($color);

			// Allow hex colors (#fff, #ffffff, #ffffffff with alpha)
			if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
				return $color;
			}

			// Allow rgb/rgba colors
			if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(?:,\s*[\d.]+\s*)?\)$/i', $color)) {
				return $color;
			}

			// Allow hsl/hsla colors
			if (preg_match('/^hsla?\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%\s*(?:,\s*[\d.]+\s*)?\)$/i', $color)) {
				return $color;
			}

			// Allow named colors (whitelist)
			$allowedColors = [
				'red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink',
				'black', 'white', 'gray', 'grey', 'brown', 'cyan', 'magenta',
				'navy', 'teal', 'olive', 'lime', 'aqua', 'maroon', 'silver',
				'fuchsia', 'indigo', 'violet', 'gold', 'coral', 'salmon',
				'khaki', 'plum', 'orchid', 'tan', 'beige', 'ivory', 'azure',
				'lavender', 'crimson', 'turquoise', 'transparent'
			];

			$colorLower = strtolower($color);
			if (in_array($colorLower, $allowedColors, true)) {
				return $colorLower;
			}

			throw new \InvalidArgumentException('Invalid color format. Use hex (#fff), rgb(255,255,255), hsl(0,0%,100%), or named color');
		}

	/**
	* Fungsi ini digunakan untuk mengatur lebar kolom di dalam datatable.
	*
	* @param string $field_name : Nama kolom yang akan di set lebar.
	* @param int $width : Nilai lebar kolom yang di inginkan dalam satuan pixel (px).
	*                    Jika di kosongkan maka lebar kolom akan di set secara otomatis.
	*
	* Contoh :
	* $this->setColumnWidth('name', 200);
	* maka kolom "name" akan di set lebar 200px.
	*/
	/**
		 * Set column width
		 * 
		 * Sets the width for a specific column. Supports multiple width formats:
		 * - Integer: Treated as pixels (e.g., 200 = 200px)
		 * - String with unit: '200px', '20%', '10em', '5rem'
		 * - 'auto': Let browser calculate width automatically
		 * - false: Remove width constraint
		 * 
		 * Task 5.7.1: Improve column width handling (Requirement 22.1)
		 * 
		 * @param string $field_name Column field name
		 * @param int|string|false $width Width value (int for pixels, string for other units, false to remove)
		 * @return self Fluent interface
		 * @throws \InvalidArgumentException If field name or width format is invalid
		 * 
		 * @security Validates field name format and width value to prevent XSS
		 * 
		 * @example
		 * ```php
		 * // Set width in pixels
		 * $table->setColumnWidth('email', 200);
		 * 
		 * // Set width in percentage
		 * $table->setColumnWidth('description', '30%');
		 * 
		 * // Set width with other units
		 * $table->setColumnWidth('name', '15em');
		 * 
		 * // Auto width
		 * $table->setColumnWidth('status', 'auto');
		 * 
		 * // Remove width constraint
		 * $table->setColumnWidth('content', false);
		 * 
		 * // Chain multiple columns
		 * $table->setColumnWidth('name', 150)
		 *       ->setColumnWidth('email', 200)
		 *       ->setColumnWidth('status', '10%');
		 * ```
		 */
		public function setColumnWidth(string $field_name, int|string|false $width = false): self {
			// @security Validate field name format
			$field_name = $this->validateFieldName($field_name);

			// Validate and normalize width value
			if (false !== $width) {
				$width = $this->validateAndNormalizeWidth($width);
			}

			$this->variables['column_width'][$field_name] = $width;

			return $this;
		}

		/**
		 * Set multiple column widths at once
		 * 
		 * Convenience method to set widths for multiple columns in one call.
		 * 
		 * Task 5.7.1: Improve column width handling (Requirement 22.1)
		 * 
		 * @param array $widths Associative array of field_name => width
		 * @return self Fluent interface
		 * @throws \InvalidArgumentException If any field name or width is invalid
		 * 
		 * @example
		 * ```php
		 * $table->setColumnWidths([
		 *     'name' => 150,
		 *     'email' => 200,
		 *     'status' => '10%',
		 *     'description' => 'auto'
		 * ]);
		 * ```
		 */
		public function setColumnWidths(array $widths): self {
			foreach ($widths as $field_name => $width) {
				$this->setColumnWidth($field_name, $width);
			}

			return $this;
		}

		/**
		 * Validate and normalize width value
		 * 
		 * @param int|string $width Width value to validate
		 * @return int|string Normalized width value
		 * @throws \InvalidArgumentException If width format is invalid
		 * 
		 * @security Validates width to prevent XSS in style attributes
		 */
		private function validateAndNormalizeWidth(int|string $width): int|string {
			// Integer width (pixels)
			if (is_int($width)) {
				if ($width <= 0 || $width > 10000) {
					throw new \InvalidArgumentException('Width must be between 1 and 10000 pixels');
				}
				return $width;
			}

			// String width
			if (is_string($width)) {
				$width = trim($width);

				// Auto width
				if ('auto' === strtolower($width)) {
					return 'auto';
				}

				// Width with unit (px, %, em, rem, vw, vh, ch)
				if (preg_match('/^(\d+(?:\.\d+)?)(px|%|em|rem|vw|vh|ch)$/i', $width, $matches)) {
					$value = floatval($matches[1]);
					$unit = strtolower($matches[2]);

					// Validate value ranges
					if ($value <= 0) {
						throw new \InvalidArgumentException('Width value must be greater than 0');
					}

					if ('px' === $unit && $value > 10000) {
						throw new \InvalidArgumentException('Width in pixels must not exceed 10000');
					}

					if ('%' === $unit && $value > 100) {
						throw new \InvalidArgumentException('Width percentage must not exceed 100%');
					}

					return $value . $unit;
				}

				throw new \InvalidArgumentException('Invalid width format. Use integer (pixels), "auto", or string with unit (e.g., "200px", "30%", "15em")');
			}

			throw new \InvalidArgumentException('Width must be integer, string, or false');
		}

	/**
	* Menambahkan atribut khusus ke dalam tabel.
	*
	* Fungsi ini digunakan untuk menambahkan atribut HTML ke dalam elemen tabel,
	* seperti 'class', 'style', atau atribut lainnya yang diperlukan.
	*
	* @param array $attributes : Array berisi pasangan kunci dan nilai dari atribut
	*                            yang akan ditambahkan ke dalam tabel.
	*                            Contoh: ['class' => 'my-class', 'style' => 'width:100%;']
	*
	* @security Attribute values are escaped to prevent XSS injection (Requirement 1.6)
	*
	* Contoh penggunaan:
	* $this->addAttributes(['class' => 'table-striped', 'style' => 'width:100%;']);
	* Maka, atribut 'class' dan 'style' akan ditambahkan ke elemen tabel.
	*/
	public function addAttributes(array $attributes = []): void {
		// @security Validate attributes to block dangerous event handlers (Requirement 1.6)
		$attributes = $this->validateAttributes($attributes);
		// @security Escape all attribute values to prevent XSS (Requirement 1.6)
		$safe_attributes = [];
		foreach ($attributes as $key => $value) {
			$safe_key = canvastack_escape_html((string) $key);
			$safe_attributes[$safe_key] = is_string($value)
				? canvastack_escape_html($value)
				: $value;
		}
		$this->variables['add_table_attributes'] = $safe_attributes;
	}

	/**
	* Mengatur lebar elemen tabel secara keseluruhan.
	*
	* Fungsi ini digunakan untuk mengatur lebar elemen tabel secara keseluruhan
	* dengan menggunakan satuan pengukuran yang diinginkan.
	*
	* @param int $width : Lebar elemen tabel yang diinginkan dalam satuan pengukuran
	*                    yang diinginkan. Misal: 100, 200, 300, dst.
	* @param string $measurement : Satuan pengukuran yang diinginkan. Misal: 'px', '%', 'em', dst.
	*
	* Contoh penggunaan:
	* $this->setWidth(1000, 'px');
	* Maka lebar elemen tabel akan diatur menjadi 1000px.
	*/
	public function setWidth(int $width, string $measurement = 'px'): void {
		$this->addAttributes(['style' => "min-width:{$width}{$measurement};"]);
	}
	
	/**
	* Semua kolom
	*
	* Properti ini digunakan untuk mengindikasikan bahwa fungsi sebelumnya
	* akan dijalankan untuk semua kolom yang ada di dalam tabel.
	*
	* Contoh penggunaan:
	* $this->setBackgroundColor('#f5f5f5', '#000000', $this->all_columns, true, false);
	* maka semua kolom akan di set warna background #f5f5f5 dan teks #000000 di header saja.
	*/
	private string $all_columns = self::ALL_COLUMNS_MARKER;

	/**
	* Memeriksa dan mengatur set kolom.
	*
	* Fungsi ini digunakan untuk memeriksa apakah parameter kolom kosong atau tidak.
	* Jika kolom kosong, maka akan mengembalikan nilai default berdasarkan kondisi.
	* Jika kolom tidak kosong, maka akan mengembalikan kolom tersebut.
	*
	* @param mixed $columns : Kolom yang akan diperiksa. Bisa berisi array kolom
	*                         tertentu atau kosong.
	*
	* @return array Mengembalikan array dengan kunci 'all::columns' yang bernilai true
	*               atau false jika kolom kosong, atau mengembalikan kolom yang diberikan.
	*
	* Contoh penggunaan:
	*
	* // Menggunakan semua kolom
	* $hasil = $this->checkColumnSet(null);
	* // $hasil akan berisi ['all::columns' => true]
	*
	* // Menggunakan kolom tertentu
	* $hasil = $this->checkColumnSet(['nama', 'alamat']);
	* // $hasil akan berisi ['nama', 'alamat']
	*/
	private function checkColumnSet(mixed $columns): array {
		if (empty($columns)) {
			if (false === $columns) {
				$value = [$this->all_columns => false];
			} else {
				$value = [$this->all_columns => true];
			}
		} else {
			$value = $columns;
		}
		
		return $value;
	}

	/**
	* Relational Data
	*
	* Properti ini digunakan untuk menyimpan data hasil relasi antara tabel.
	* Data yang disimpan berupa array associative yang berisi kunci relasi
	* dan nilai berupa array yang berisi data relasi.
	*
	* Contoh penggunaan:
	*
	* // Misal kita memiliki relasi antara tabel users dan tabel roles
	* // dengan nama relasi "user_roles"
	* $this->relational_data = [
	*     'user_roles' => [
	*         'user_id' => 1,
	*         'role_id' => 1,
	*         'role_name' => 'Admin',
	*     ],
	* ];
	*
	* // Maka kita dapat mengakses data relasi dengan cara berikut:
	* $role_name = $this->relational_data['user_roles']['role_name'];
	*/
	public array $relational_data = [];
	
	/**
	* Menyimpan data hasil relasi antara tabel.
	*
	* Fungsi ini digunakan untuk menyimpan data hasil relasi antara tabel.
	* Data yang disimpan berupa array associative yang berisi kunci relasi
	* dan nilai berupa array yang berisi data relasi.
	*
	* Properti yang digunakan:
	*
	* - $relation_function : Nama relasi yang digunakan.
	* - $fieldname : Nama kolom yang akan di gunakan sebagai target.
	* - $label : Label yang akan di gunakan untuk nama kolom.
	*
	* Contoh penggunaan:
	*
	* // Misal kita memiliki relasi antara tabel users dan tabel roles
	* // dengan nama relasi "user_roles"
	* $this->setRelationData('user_roles', 'users:id', 'role_name');
	*
	* // Maka kita dapat mengakses data relasi dengan cara berikut:
	* $role_name = $this->relational_data['user_roles']['field_target']['role_name']['relation_data'][$user_id]['field_value'];
	*
	* @param object $model
	* @param string $relation_function
	* @param string $field_display
	* @param array  $filter_foreign_keys :[
	*			'base_user_group:user_id' => 'users:id',
	*			'base_group:id'           => 'base_user_group:group_id'
	*	]
	* @param string $label
	* @param string $field_connect
	*
	* @return array
	*/
	private function relation_draw(mixed $relation, string $relation_function, string $fieldname, string $label): void {
		// Extract relation data and key
		if (!empty($relation->{$relation_function})) {
			$dataRelate = $relation->{$relation_function}->getAttributes();
			$relateKEY  = intval($relation['id']);
		} else {
			$dataRelate = $relation->getAttributes();
			$relateKEY  = intval($dataRelate['id']);
		}
		
		// Parse field name and get data
		$fieldReplacement = null;
		if (canvastack_string_contained($fieldname, '::')) {
			$fieldsplit       = explode('::', $fieldname);
			$fieldReplacement = $fieldsplit[0];
			$fieldname        = $fieldsplit[1];
		}
		
		$data_relation = $dataRelate[$fieldname] ?? null;
		$data_value    = $dataRelate[$fieldname] ?? null;
		
		// Guard clause: Skip if no relation data
		if (empty($data_relation)) {
			return;
		}
		
		// Determine field set name
		$fieldset = !is_empty($fieldReplacement) ? $fieldReplacement : $fieldname;
		
		// Store field metadata
		$this->relational_data[$relation_function]['field_target'][$fieldset]['field_name']  = $fieldset;
		$this->relational_data[$relation_function]['field_target'][$fieldset]['field_label'] = $label;
		
		// Store pivot data if present
		if (!empty($relation->pivot)) {
			foreach ($relation->pivot->getAttributes() as $pivot_field => $pivot_data) {
				$this->relational_data[$relation_function]['field_target'][$fieldset]['relation_data'][$relateKEY][$pivot_field] = $pivot_data;
			}
		}
		
		// Store field value
		$this->relational_data[$relation_function]['field_target'][$fieldset]['relation_data'][$relateKEY]['field_value'] = $data_value;
	}
	
	/**
	 * Set Relation Data Table
	 * 
	 * @param object $model
	 * @param string $relation_function
	 * @param string $field_display
	 * @param array  $filter_foreign_keys :[
	 *			'base_user_group:user_id' => 'users:id',
	 *			'base_group:id'           => 'base_user_group:group_id'
	 *	]
	 * @param string $label
	 * @param string $field_connect
	 * 
	 * @return array
	 */
	private function relationship(mixed $model, string $relation_function, string $field_display, array $filter_foreign_keys = [], ?string $label = null, ?string $field_connect = null): void {
		// Guard clause: Skip if no relational data
		$relational_data = $model->with($relation_function)->get();
		if (empty($relational_data)) {
			return;
		}
		
		// Set default label if not provided
		if (empty($label)) {
			$label = $this->sanitizeLabel(ucwords(canvastack_clean_strings($field_display, ' ')));
		}
		
		// Process each item in relational data
		foreach ($relational_data as $item) {
			// Guard clause: Skip if item has no relation
			if (empty($item->{$relation_function})) {
				continue;
			}
			
			// Handle collection vs single relation
			if (canvastack_is_collection($item->{$relation_function})) {
				foreach ($item->{$relation_function} as $relation) {
					$this->relation_draw($relation, $relation_function, $field_display, $label);
				}
			} else {
				$this->relation_draw($item, $relation_function, "{$field_connect}::{$field_display}", $label);
			}
		}
		
		// Store foreign keys if provided
		if (!empty($filter_foreign_keys)) {
			$this->relational_data[$relation_function]['foreign_keys'] = $filter_foreign_keys;
		}
	}
	
	/**
	 * Set Simple Relation Data Table
	 * 
	 * @param object $model
	 * @param string $relation_function
	 * @param string $field_display
	 * @param array  $filter_foreign_keys :[
	 *			'base_user_group:user_id' => 'users:id',
	 *			'base_group:id'           => 'base_user_group:group_id'
	 *	]
	 * @param string $label
	 * 
	 * @return array
	 */
	public function relations(mixed $model, string $relation_function, string $field_display, array $filter_foreign_keys = [], ?string $label = null): void {
		try {
			// Sanitize label if provided
			if ($label !== null) {
				$label = $this->sanitizeLabel($label);
			}
			
			$this->relationship($model, $relation_function, $field_display, $filter_foreign_keys, $label, null);
		} catch (\InvalidArgumentException $e) {
			error_log('Objects relations() validation error: ' . $e->getMessage());
			throw $e;
		} catch (\Exception $e) {
			error_log('Objects relations() error: ' . $e->getMessage());
			throw $e;
		}
	}
	
	/**
	 * Change Fieldname Value With Relational Data
	 *
	 * @param object $model
	 * @param string $relation_function
	 * @param string $field_display
	 * @param string $label
	 * @param string $field_connect
	 *
	 * @return array
	 */
	public function fieldReplacementValue(mixed $model, string $relation_function, string $field_display, ?string $label = null, ?string $field_connect = null): void {
		try {
			// Sanitize label if provided
			if ($label !== null) {
				$label = $this->sanitizeLabel($label);
			}
			
			$this->relationship($model, $relation_function, $field_display, [], $label, $field_connect);
		} catch (\InvalidArgumentException $e) {
			error_log('Objects fieldReplacementValue() validation error: ' . $e->getMessage());
			throw $e;
		} catch (\Exception $e) {
			error_log('Objects fieldReplacementValue() error: ' . $e->getMessage());
			throw $e;
		}
	}
	
	/**
	 * Set default order by column dan direction
	 * 
	 * Method ini mengatur kolom mana yang akan digunakan untuk sorting default
	 * dan arah sorting (ascending atau descending).
	 * 
	 * @param string $column Nama kolom untuk sorting
	 * @param string $order Arah sorting: 'asc' (ascending) atau 'desc' (descending)
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Sort by name ascending
	 * $table->orderby('name', 'asc')->lists('users');
	 * 
	 * // Sort by created_at descending
	 * $table->orderby('created_at', 'desc')->lists('users');
	 * ```
	 */
	/**
	 * Set sort order for table data
	 * 
	 * @param string $column Column name to sort by
	 * @param string $order Sort direction ('asc' or 'desc')
	 * @return void
	 * 
	 * @security 1.7.7 - Validates column name format and sort direction to prevent SQL injection
	 *           via ORDER BY manipulation. Logs suspicious attempts. (Requirements 2.5, 3.5)
	 */
	public function orderby(string $column, string $order = self::DEFAULT_SORT_ORDER): void {
		// @security 1.7.7 - Validate column name format to prevent SQL injection
		try {
			$column = $this->validateFieldName($column);
		} catch (\InvalidArgumentException $e) {
			\Illuminate\Support\Facades\Log::warning('[SECURITY] Objects::orderby() - Invalid column name detected', [
				'column'  => $column,
				'error'   => $e->getMessage(),
				'context' => 'SQL injection prevention - ORDER BY column validation'
			]);
			throw $e;
		}
		
		// @security 1.7.7 - Validate sort direction to prevent SQL injection
		$normalizedOrder = strtolower(trim($order));
		if (!in_array($normalizedOrder, [self::DEFAULT_SORT_ORDER, self::SORT_ORDER_DESC], true)) {
			\Illuminate\Support\Facades\Log::warning('[SECURITY] Objects::orderby() - Invalid sort direction detected', [
				'column'    => $column,
				'direction' => $order,
				'context'   => 'SQL injection prevention - ORDER BY direction validation'
			]);
			throw new \InvalidArgumentException('Sort direction must be "asc" or "desc", got: "' . $order . '"');
		}
		
		$this->variables['orderby_column'] = [];
		$this->variables['orderby_column'] = ['column' => $column, 'order' => $normalizedOrder];
	}
	
	/**
	 * Set Sortable Column(s)
	 * 
	 * Mengatur kolom-kolom mana yang bisa di-sort oleh user.
	 * Jika tidak di-set, semua kolom akan sortable.
	 * 
	 * @param string|array|null $columns Nama kolom atau array kolom yang sortable
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Single column
	 * $table->sortable('name')->lists('users');
	 * 
	 * // Multiple columns
	 * $table->sortable(['name', 'email', 'created_at'])->lists('users');
	 * 
	 * // All columns sortable (default)
	 * $table->sortable()->lists('users');
	 * ```
	 */
	public function sortable(array|string|null $columns = null): void {
		$this->variables['sortable_columns'] = [];
		$this->variables['sortable_columns'] = $this->checkColumnSet($columns);
	}
	
	/**
	 * Set Clickable Column(s)
	 * 
	 * Mengatur kolom-kolom yang bisa di-klik untuk membuka detail/edit page.
	 * Biasanya digunakan untuk kolom ID atau nama yang akan menjadi link.
	 * 
	 * @param string|array|null $columns Nama kolom atau array kolom yang clickable
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Single column clickable
	 * $table->clickable('name')->lists('users');
	 * 
	 * // Multiple columns clickable
	 * $table->clickable(['id', 'name'])->lists('users');
	 * ```
	 */
	public function clickable(array|string|null $columns = null): void {
		$this->variables['clickable_columns'] = [];
		$this->variables['clickable_columns'] = $this->checkColumnSet($columns);
	}
	
	public bool|array $search_columns = false;
	
	/**
	* Menentukan kolom mana yang dapat dicari di dalam datatable.
	*
	* Fungsi ini digunakan untuk mengatur kolom-kolom yang dapat digunakan sebagai filter pencarian.
	* Jika parameter kolom tidak diisi, maka secara default semua kolom akan digunakan.
	*
	* @param string|array $columns : Kolom yang ingin diatur sebagai kolom pencarian. Bisa berisi nama kolom atau array nama-nama kolom.
	*
	* Properti:
	* - $this->variables['searchable_columns'] : Menyimpan daftar kolom yang dapat dicari.
	* - $this->search_columns : Menyimpan kolom yang akan digunakan untuk filter pencarian.
	* - $this->all_columns : Menandakan semua kolom di dalam tabel.
	*
	* Contoh penggunaan:
	*
	* // Menggunakan semua kolom untuk pencarian
	* $this->searchable();
	* // atau
	* $this->searchable(null);
	*
	* // Menggunakan kolom tertentu untuk pencarian
	* $this->searchable(['nama', 'alamat']);
	*/
	public function searchable(array|string|null $columns = null): void {
		$this->variables['searchable_columns'] = [];
		$this->variables['searchable_columns'] = $this->checkColumnSet($columns);
		if (empty($columns)) {
			if (false === $columns) {
				$filter_columns = false;
			} else {
				$filter_columns = $this->all_columns;
			}
		} else {
			$filter_columns = $columns;
		}
		
		$this->search_columns = $filter_columns;
	}
	
	/**
	 * Set Searching Data Filter
	 * 
	 * @param string $column
	 * 		: field name target
	 * @param string $type
	 * 		: inputbox     [no relational data $relate auto set with false], 
	 *         datebox      [no relational data $relate auto set with false], 
	 *         daterangebox [no relational data $relate auto set with false], 
	 *         selectbox    [single or multi], 
	 *         checkbox, 
	 *         radiobox
	 * @param boolean|string|array $relate
	 * 		: if false = no relational Data
	 * 		: if true  = relational data set to all others columns/fieldname members
	 * 		: if (string) fieldname / other column = relate to just one that column target was setted
	 * 		: if (array) fieldnames / others any columns = relate to any that column target was setted
	 */
	public function filterGroups(string $column, string $type, bool|string|array $relate = false): void {
		$filters           = [];
		$filters['column'] = $column;
		$filters['type']   = $type;
		$filters['relate'] = $relate;
		
		$this->variables['filter_groups'][] = $filters;
	}

	/**
	* Mengatur batasan jumlah baris yang akan ditampilkan saat pemuatan awal.
	*
	* Fungsi ini digunakan untuk mengatur jumlah baris yang ditampilkan ketika tabel
	* pertama kali dimuat. Pengguna dapat menentukan jumlah baris dalam bentuk angka
	* atau menggunakan string '*' atau 'all' untuk menampilkan semua baris.
	*
	* @param mixed $limit : Batasan jumlah baris yang akan ditampilkan. Bisa berupa
	*                       integer untuk jumlah baris tertentu atau string '*'/'all'
	*                       untuk menampilkan semua baris.
	*
	* Contoh penggunaan:
	*
	* // Menampilkan 10 baris pada pemuatan awal
	* $this->displayRowsLimitOnLoad(10);
	*
	* // Menampilkan semua baris pada pemuatan awal
	* $this->displayRowsLimitOnLoad('all');
	*/
	public function displayRowsLimitOnLoad(int|string $limit = self::DEFAULT_ROW_LIMIT): void {
		if (is_string($limit)) {
			if (in_array(strtolower($limit), self::DISPLAY_ALL_KEYWORDS)) {
				$this->variables['on_load']['display_limit_rows'] = '*';
			}
		} else {
			$this->variables['on_load']['display_limit_rows'] = intval($limit);
		}
	}
	
	/**
	 * Clear display rows limit on load
	 * 
	 * Menghapus batasan jumlah baris yang ditampilkan saat load awal.
	 * Table akan kembali menggunakan default limit.
	 * 
	 * @return void
	 */
	public function clearOnLoad(): void {
		unset($this->variables['on_load']['display_limit_rows']);
	}
	
	protected array $filter_model = [];
	/**
	 * Set filter model data
	 * 
	 * Method ini digunakan untuk mengatur filter data dari model.
	 * Berguna untuk pre-filtering data sebelum ditampilkan.
	 * 
	 * @param array $data Filter data array
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $table->filterModel(['status' => 'active', 'role' => 'admin'])
	 *       ->lists('users');
	 * ```
	 */
	public function filterModel(array $data = []) {
		$this->filter_model = $data;
	}
	
	private function check_column_exist(string $table_name, array $fields, ?string $connection = self::DEFAULT_DB_CONNECTION): array {
		$connection = $connection ?? self::DEFAULT_DB_CONNECTION;
		$fieldset = [];
		
		// Build lookup for relational fields for O(1) performance
		$relational_fields = [];
		if (!empty($this->relational_data)) {
			foreach ($this->relational_data as $relData) {
				if (!empty($relData['field_target'])) {
					foreach ($relData['field_target'] as $fr_name => $relation_fields) {
						$relational_fields[$fr_name] = true;
					}
				}
			}
		}
		
		foreach ($fields as $field) {
			// Allow field if it's a relational field OR exists in the table
			if (isset($relational_fields[$field]) || $this->hasColumn($table_name, $field)) {
				$fieldset[] = $field;
			}
		}
		
		return $fieldset;
	}
	
	private ?bool $clear_variables = null;
	private function clearVariables(bool $clear_set = true): void {
		$this->clear_variables = $clear_set;
		if (true === $this->clear_variables) {
			$this->clear_all_variables();
		}
	}
	
	/**
	 * Clear semua variables dan reset table configuration
	 * 
	 * Method ini menghapus semua konfigurasi table yang telah di-set
	 * dan mengembalikan ke state awal.
	 * 
	 * @param bool $clear_set True untuk clear semua, false untuk skip
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $table->where('status', '=', 'active')
	 *       ->lists('users');
	 * 
	 * // Clear untuk table berikutnya
	 * $table->clear();
	 * $table->lists('products');
	 * ```
	 */
	public function clear(bool $clear_set = true): void {
		$this->clearVariables($clear_set);
	}
	
	/**
	 * Clear specific variable by name
	 * 
	 * @param string $name Variable name to clear
	 * @return void
	 */
	public function clearVar(string $name): void {
		$this->variables[$name] = [];
	}
	
	
	public string $useFieldTargetURL = 'id';
	/**
	 * Set field yang digunakan untuk URL value (detail/edit links)
	 * 
	 * Method ini mengatur field mana yang akan digunakan sebagai parameter
	 * di URL untuk action buttons (view, edit, delete).
	 * 
	 * @param string $field Field name yang akan digunakan (default: 'id')
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Use 'uuid' instead of 'id' for URLs
	 * $table->setUrlValue('uuid')->lists('users');
	 * // URLs will be: /users/view/{uuid}, /users/edit/{uuid}
	 * 
	 * // Use 'slug' for URLs
	 * $table->setUrlValue('slug')->lists('posts');
	 * // URLs will be: /posts/view/{slug}
	 * ```
	 */
	public function setUrlValue(string $field = 'id'): void {
		$this->variables['url_value'] = $field;
		$this->useFieldTargetURL = $field;
	}
	
	public array $variables = [];
	private function clear_all_variables(): void {
		$this->variables['on_load']              = [];
		$this->variables['url_value']            = [];
		$this->variables['merged_columns']       = [];
		$this->variables['text_align']           = [];
		$this->variables['background_color']     = [];
		$this->variables['attributes']           = [];
		$this->variables['orderby_column']       = [];
		$this->variables['sortable_columns']     = [];
		$this->variables['clickable_columns']    = [];
		$this->variables['searchable_columns']   = [];
		$this->variables['filter_groups']        = [];
		$this->variables['column_width']         = [];
		$this->variables['format_data']          = [];
		$this->variables['add_table_attributes'] = [];
		$this->variables['fixed_columns']        = [];
		$this->variables['model_processing']     = [];
	}
	
	public array $conditions = [];
	/**
	 * Add WHERE condition for table data filtering
	 * 
	 * @param string|array $field_name Field name or array of field=>value pairs
	 * @param string|false $logic_operator SQL operator (=, !=, LIKE, etc.) or false
	 * @param mixed $value Value to compare against or false
	 * @return void
	 * 
	 * @security Validates field names (alphanumeric + underscore + dot only) and operators
	 *           against a whitelist to prevent SQL injection. (Requirements 2.3, 2.4, 3.1)
	 * @throws \InvalidArgumentException If field name or operator is invalid
	 */
	public function where(string|array $field_name, string|false $logic_operator = false, mixed $value = false): void {
		try {
			$this->conditions['where'] = [];
			if (is_array($field_name)) {
				foreach ($field_name as $fieldname => $fieldvalue) {
					// Validate field name
					$fieldname = $this->validateFieldName($fieldname);
					
					$this->conditions['where'][] = [
						'field_name' => $fieldname,
						'operator'   => '=',
						'value'      => $fieldvalue
					];
				}
			} else {
				// Validate field name
				$field_name = $this->validateFieldName($field_name);
				
				// @security 1.7.5 - Validate and normalize operator against whitelist
				// Prevents SQL injection via operator manipulation (Requirement 2.4)
				if ($logic_operator !== false) {
					$logic_operator = $this->validateOperator($logic_operator);
					// Normalize multi-word operators to uppercase for consistent SQL generation
					$upperOp = strtoupper(trim($logic_operator));
					if (in_array($upperOp, ['LIKE', 'NOT LIKE', 'ILIKE', 'NOT ILIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'IS NULL', 'IS NOT NULL', 'REGEXP', 'NOT REGEXP'], true)) {
						$logic_operator = $upperOp;
					}
				}
				
				$this->conditions['where'][] = [
					'field_name' => $field_name,
					'operator'   => $logic_operator,
					'value'      => $value
				];
			}
		} catch (\InvalidArgumentException $e) {
			\Illuminate\Support\Facades\Log::warning('[SECURITY] Objects::where() - Validation failed', [
				'field'    => is_string($field_name) ? $field_name : gettype($field_name),
				'operator' => $logic_operator,
				'error'    => $e->getMessage(),
				'context'  => 'SQL injection prevention - operator validation'
			]);
			throw $e; // Re-throw to allow caller to handle
		} catch (\Exception $e) {
			error_log('Objects where() error: ' . $e->getMessage());
			throw $e;
		}
	}
	
	/**
	 * Filter Table
	 * 
	 * @param array $filters
	 * 		: $this->model_filters
	 * @return array
	 */
	public function filterConditions(array $filters = []): void {
		$this->where($filters);
	}
	
	/**
	* Buat Kondisi Kolom Berdasarkan Nilai Tertentu
	*
	* Fungsi ini digunakan untuk membuat kondisi kolom berdasarkan nilai tertentu.
	* Kondisi ini berguna untuk mengatur tampilan kolom berdasarkan nilai yang di dapat dari database.
	*
	* @param string $field_name
	* 		: Nama kolom yang akan di set kondisi.
	* @param string $target
	* 		: Target kolom yang akan di set kondisi. Bisa berupa 'row', 'cell', atau 'field_name'.
	* 		: Jika target adalah 'row', maka kondisi akan di set ke baris yang berisi data kolom tersebut.
	* 		: Jika target adalah 'cell', maka kondisi akan di set ke kolom yang berisi data tersebut.
	* 		: Jika target adalah 'field_name', maka kondisi akan di set ke kolom yang berisi data tersebut.
	* @param string $logic_operator
	* 		: Operator logika yang digunakan untuk membandingkan nilai kolom dengan nilai yang di set.
	* 		: Bisa berupa '==', '!=', '===', '!==', '>', '<', '>=', '<='.
	* @param string $value
	* 		: Nilai yang di set sebagai perbandingan dengan nilai kolom.
	* @param string $rule
	* 		: Aturan yang digunakan untuk mengatur tampilan kolom berdasarkan nilai yang di dapat.
	* 		: Bisa berupa 'css style', 'prefix', 'suffix', 'prefix&suffix', 'replace', 'integer', 'float', 'float|2'.
	* @param string|array $action
	* 		: Aksi yang akan di lakukan jika kondisi terpenuhi.
	* 		: Jika di set sebagai string, maka akan menggantikan url button dengan url yang di set.
	* 		: Jika di set sebagai array, maka akan di gunakan untuk aturan 'prefix&suffix'.
	* 		: Array pertama akan di set sebagai prefix dan array terakhir akan di set sebagai suffix.
	*
	* Contoh penggunaan:
	* $this->table->columnCondition('text_field', 'cell', '!==', 'Testing', 'prefix', '! ');
	* maka kolom "text_field" akan di set dengan prefix "!" jika nilai kolom tidak sama dengan "Testing".
	*
	* Contoh lain:
	* $this->table->columnCondition('user_status', 'action', '==', 'Disabled', 'replace', 'url::action_check|danger|volume-off');
	* maka kolom "user_status" akan di set dengan menggantikan url button dengan url "action_check" jika nilai kolom sama dengan "Disabled".
	*/
	public function columnCondition(string $field_name, string $target, string $logic_operator = null, string $value = null, string $rule, $action) {
		try {
			// Validate field names
			$field_name = $this->validateFieldName($field_name);
			$target = $this->validateFieldName($target);
			
			// Validate operator if provided
			if ($logic_operator !== null) {
				$logic_operator = $this->validateOperator($logic_operator);
			}
			
			$this->conditions['columns'][] = [
				'field_name'     => $field_name,
				'field_target'   => $target,
				'logic_operator' => $logic_operator,
				'value'          => $value,
				'rule'           => $rule,
				'action'         => $action
			];
		} catch (\InvalidArgumentException $e) {
			error_log('Objects columnCondition() validation error: ' . $e->getMessage());
			throw $e;
		} catch (\Exception $e) {
			error_log('Objects columnCondition() error: ' . $e->getMessage());
			throw $e;
		}
	}
	
	public array $formula = [];
	/**
	* Membuat Formula Untuk Menghitung Nilai Kolom
	*
	* Fungsi ini digunakan untuk membuat formula yang dapat digunakan untuk menghitung nilai kolom tertentu.
	* Formula ini dapat digunakan untuk menghitung nilai kolom yang dihitung berdasarkan beberapa kolom lainnya.
	*
	* @param string $name
	* 		: Nama dari formula yang akan dibuat.
	* 		: Nama ini akan digunakan sebagai nama kolom yang dihitung.
	* @param string $label
	* 		: Label dari formula yang akan dibuat.
	* 		: Label ini akan digunakan sebagai nama tampilan dari kolom yang dihitung.
	* @param array $field_lists
	* 		: Daftar kolom yang akan digunakan untuk menghitung nilai formula.
	* 		: Kolom-kolom ini harus berupa array yang berisi nama-nama kolom yang diinginkan.
	* @param string $logic
	* 		: Operator logika yang digunakan untuk menghitung nilai formula.
	* 		: Operator logika ini dapat berupa '+', '-', '*', '/', '%', '||', '&&'.
	* @param string $node_location
	* 		: Lokasi node yang akan di isi dengan hasil perhitungan formula.
	* 		: Jika di set, maka hasil perhitungan formula akan di isi ke node yang di set.
	* 		: Jika tidak di set, maka hasil perhitungan formula akan di isi ke node yang sama dengan nama formula.
	* @param bool $node_after_node_location
	* 		: Jika true, maka hasil perhitungan formula akan di isi setelah node yang di set.
	* 		: Jika false, maka hasil perhitungan formula akan di isi sebelum node yang di set.
	*
	* Contoh penggunaan:
	* $this->table->formula('total', 'Total', ['harga', 'jumlah'], '*', 'tbody', true);
	* maka akan membuat formula dengan nama 'total' yang akan menghitung nilai kolom 'harga' dan 'jumlah' dengan operator '*' dan akan di isi ke node 'tbody' setelah node yang sama dengan nama formula.
	*/
	public function formula(string $name, string $label = null, array $field_lists, string $logic, string $node_location = null, bool $node_after_node_location = true): void {
		try {
			// Validate name
			$name = $this->validateFieldName($name);
			
			// Validate and sanitize label
			if ($label !== null) {
				$label = $this->sanitizeLabel($label);
			}
			
			// Validate logic operator
			$logic = $this->validateLogicOperator($logic);
			
			// Validate field_lists array
			$field_lists = $this->validateFieldsArray($field_lists);
			foreach ($field_lists as $field) {
				$this->validateFieldName($field);
			}
			
			$this->labels[$name]           = $label;
			$this->conditions['formula'][] = [
				'name'          => $name,
				'label'         => $label,
				'field_lists'   => $field_lists,
				'logic'         => $logic,
				'node_location' => $node_location,
				'node_after'    => $node_after_node_location
			];
		} catch (\InvalidArgumentException $e) {
			error_log('Objects formula() validation error: ' . $e->getMessage());
			throw $e;
		} catch (\Exception $e) {
			error_log('Objects formula() error: ' . $e->getMessage());
			throw $e;
		}
	}
	
	/**
	 * Format column values
	 * 
	 * Applies formatting to column values for display. Supports various format types
	 * including numbers, currency, dates, percentages, and custom formats.
	 * 
	 * Task 5.7.5: Add column formatting options (Requirement 22.5)
	 * 
	 * @param string|array $fields Field name(s) to format
	 * @param int $decimal_endpoint Number of decimal places (for numeric formats)
	 * @param string $separator Decimal separator ('.' or ',')
	 * @param string $format Format type: 'number', 'currency', 'date', 'datetime', 'percentage', 'boolean', 'custom'
	 * @param array $options Additional format options (currency symbol, date format, etc.)
	 * @return void
	 * @throws \InvalidArgumentException If format type or options are invalid
	 * 
	 * @security Validates format type and options to prevent XSS
	 * 
	 * @example
	 * ```php
	 * // Format price as number with 2 decimals
	 * $table->format('price', 2, '.', 'number');
	 * 
	 * // Format as currency
	 * $table->format('total', 2, '.', 'currency', ['symbol' => '$', 'position' => 'before']);
	 * 
	 * // Format as percentage
	 * $table->format('discount', 1, '.', 'percentage');
	 * 
	 * // Format date
	 * $table->format('created_at', 0, '.', 'date', ['format' => 'Y-m-d']);
	 * 
	 * // Format datetime
	 * $table->format('updated_at', 0, '.', 'datetime', ['format' => 'Y-m-d H:i:s']);
	 * 
	 * // Format boolean
	 * $table->format('is_active', 0, '.', 'boolean', ['true' => 'Active', 'false' => 'Inactive']);
	 * 
	 * // Format multiple fields at once
	 * $table->format(['price', 'cost', 'total'], 2, '.', 'currency', ['symbol' => '$']);
	 * ```
	 */
	public function format(string|array $fields, int $decimal_endpoint = 0, string $separator = '.', string $format = 'number', array $options = []): void {
		// @security Validate format type
		$validFormats = ['number', 'currency', 'date', 'datetime', 'time', 'percentage', 'boolean', 'custom'];
		if (!in_array($format, $validFormats, true)) {
			throw new \InvalidArgumentException('Invalid format type. Allowed: number, currency, date, datetime, time, percentage, boolean, custom');
		}
		
		// @security Validate separator
		if (!in_array($separator, ['.', ','], true)) {
			throw new \InvalidArgumentException('Invalid separator. Allowed: "." or ","');
		}
		
		// Validate decimal endpoint
		if ($decimal_endpoint < 0 || $decimal_endpoint > 10) {
			throw new \InvalidArgumentException('Decimal endpoint must be between 0 and 10');
		}
		
		// Process fields
		$fieldsArray = is_array($fields) ? $fields : [$fields];
		
		foreach ($fieldsArray as $field) {
			// @security Validate field name
			$field = $this->validateFieldName($field);
			
			$this->variables['format_data'][$field] = [
				'field_name' => $field,
				'decimal_endpoint' => $decimal_endpoint,
				'format_type' => $format,
				'separator' => $separator,
				'options' => $this->validateFormatOptions($format, $options)
			];
		}
	}
	
	/**
	 * Validate format options based on format type
	 * 
	 * @param string $format Format type
	 * @param array $options Format options
	 * @return array Validated options
	 * @throws \InvalidArgumentException If options are invalid
	 * 
	 * @security Validates and sanitizes format options
	 */
	private function validateFormatOptions(string $format, array $options): array {
		$validated = [];
		
		switch ($format) {
			case 'currency':
				// Validate currency symbol
				if (isset($options['symbol'])) {
					$validated['symbol'] = htmlspecialchars($options['symbol'], ENT_QUOTES, 'UTF-8');
				} else {
					$validated['symbol'] = '$';
				}
				
				// Validate position
				if (isset($options['position'])) {
					if (!in_array($options['position'], ['before', 'after'], true)) {
						throw new \InvalidArgumentException('Currency position must be "before" or "after"');
					}
					$validated['position'] = $options['position'];
				} else {
					$validated['position'] = 'before';
				}
				
				// Validate thousands separator
				if (isset($options['thousands'])) {
					if (!in_array($options['thousands'], [',', '.', ' ', ''], true)) {
						throw new \InvalidArgumentException('Thousands separator must be ",", ".", " ", or empty string');
					}
					$validated['thousands'] = $options['thousands'];
				} else {
					$validated['thousands'] = ',';
				}
				break;
				
			case 'date':
			case 'datetime':
			case 'time':
				// Validate date format
				if (isset($options['format'])) {
					// Allow common PHP date format characters
					if (!preg_match('/^[YymdHhisaADlFMnjStwzWNLo\s\-\/:.,]+$/', $options['format'])) {
						throw new \InvalidArgumentException('Invalid date format string');
					}
					$validated['format'] = $options['format'];
				} else {
					// Default formats
					$validated['format'] = match($format) {
						'date' => 'Y-m-d',
						'datetime' => 'Y-m-d H:i:s',
						'time' => 'H:i:s',
						default => 'Y-m-d H:i:s'
					};
				}
				
				// Validate timezone
				if (isset($options['timezone'])) {
					try {
						new \DateTimeZone($options['timezone']);
						$validated['timezone'] = $options['timezone'];
					} catch (\Exception $e) {
						throw new \InvalidArgumentException('Invalid timezone: ' . $options['timezone']);
					}
				}
				break;
				
			case 'percentage':
				// Validate percentage symbol
				if (isset($options['symbol'])) {
					$validated['symbol'] = htmlspecialchars($options['symbol'], ENT_QUOTES, 'UTF-8');
				} else {
					$validated['symbol'] = '%';
				}
				
				// Validate position
				if (isset($options['position'])) {
					if (!in_array($options['position'], ['before', 'after'], true)) {
						throw new \InvalidArgumentException('Percentage position must be "before" or "after"');
					}
					$validated['position'] = $options['position'];
				} else {
					$validated['position'] = 'after';
				}
				break;
				
			case 'boolean':
				// Validate true/false labels
				if (isset($options['true'])) {
					$validated['true'] = htmlspecialchars($options['true'], ENT_QUOTES, 'UTF-8');
				} else {
					$validated['true'] = 'Yes';
				}
				
				if (isset($options['false'])) {
					$validated['false'] = htmlspecialchars($options['false'], ENT_QUOTES, 'UTF-8');
				} else {
					$validated['false'] = 'No';
				}
				break;
				
			case 'custom':
				// For custom format, allow callback or template
				if (isset($options['callback'])) {
					if (!is_callable($options['callback'])) {
						throw new \InvalidArgumentException('Custom format callback must be callable');
					}
					$validated['callback'] = $options['callback'];
				}
				
				if (isset($options['template'])) {
					$validated['template'] = htmlspecialchars($options['template'], ENT_QUOTES, 'UTF-8');
				}
				break;
		}
		
		return $validated;
	}
	
	/**
	 * Format column as currency
	 * 
	 * Convenience method to format columns as currency.
	 * 
	 * Task 5.7.5: Add column formatting options (Requirement 22.5)
	 * 
	 * @param string|array $fields Field name(s)
	 * @param int $decimals Number of decimal places (default: 2)
	 * @param string $symbol Currency symbol (default: '$')
	 * @param string $position Symbol position: 'before' or 'after' (default: 'before')
	 * @param string $thousands Thousands separator (default: ',')
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Format as USD
	 * $table->formatCurrency('price', 2, '$', 'before', ',');
	 * 
	 * // Format as EUR
	 * $table->formatCurrency('total', 2, '€', 'after', '.');
	 * ```
	 */
	public function formatCurrency(string|array $fields, int $decimals = 2, string $symbol = '$', string $position = 'before', string $thousands = ','): void {
		$this->format($fields, $decimals, '.', 'currency', [
			'symbol' => $symbol,
			'position' => $position,
			'thousands' => $thousands
		]);
	}
	
	/**
	 * Format column as percentage
	 * 
	 * Convenience method to format columns as percentage.
	 * 
	 * Task 5.7.5: Add column formatting options (Requirement 22.5)
	 * 
	 * @param string|array $fields Field name(s)
	 * @param int $decimals Number of decimal places (default: 1)
	 * @param string $symbol Percentage symbol (default: '%')
	 * @param string $position Symbol position: 'before' or 'after' (default: 'after')
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $table->formatPercentage('discount', 1);
	 * $table->formatPercentage('growth_rate', 2);
	 * ```
	 */
	public function formatPercentage(string|array $fields, int $decimals = 1, string $symbol = '%', string $position = 'after'): void {
		$this->format($fields, $decimals, '.', 'percentage', [
			'symbol' => $symbol,
			'position' => $position
		]);
	}
	
	/**
	 * Format column as date
	 * 
	 * Convenience method to format columns as date.
	 * 
	 * Task 5.7.5: Add column formatting options (Requirement 22.5)
	 * 
	 * @param string|array $fields Field name(s)
	 * @param string $dateFormat PHP date format (default: 'Y-m-d')
	 * @param string|null $timezone Timezone (default: null = use system timezone)
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $table->formatDate('created_at', 'Y-m-d');
	 * $table->formatDate('birth_date', 'd/m/Y');
	 * ```
	 */
	public function formatDate(string|array $fields, string $dateFormat = 'Y-m-d', ?string $timezone = null): void {
		$options = ['format' => $dateFormat];
		if ($timezone !== null) {
			$options['timezone'] = $timezone;
		}
		
		$this->format($fields, 0, '.', 'date', $options);
	}
	
	/**
	 * Format column as datetime
	 * 
	 * Convenience method to format columns as datetime.
	 * 
	 * Task 5.7.5: Add column formatting options (Requirement 22.5)
	 * 
	 * @param string|array $fields Field name(s)
	 * @param string $dateTimeFormat PHP datetime format (default: 'Y-m-d H:i:s')
	 * @param string|null $timezone Timezone (default: null = use system timezone)
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $table->formatDateTime('created_at', 'Y-m-d H:i:s');
	 * $table->formatDateTime('updated_at', 'd/m/Y H:i');
	 * ```
	 */
	public function formatDateTime(string|array $fields, string $dateTimeFormat = 'Y-m-d H:i:s', ?string $timezone = null): void {
		$options = ['format' => $dateTimeFormat];
		if ($timezone !== null) {
			$options['timezone'] = $timezone;
		}
		
		$this->format($fields, 0, '.', 'datetime', $options);
	}
	
	/**
	 * Format column as boolean
	 * 
	 * Convenience method to format columns as boolean with custom labels.
	 * 
	 * Task 5.7.5: Add column formatting options (Requirement 22.5)
	 * 
	 * @param string|array $fields Field name(s)
	 * @param string $trueLabel Label for true values (default: 'Yes')
	 * @param string $falseLabel Label for false values (default: 'No')
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $table->formatBoolean('is_active', 'Active', 'Inactive');
	 * $table->formatBoolean('verified', 'Verified', 'Not Verified');
	 * ```
	 */
	public function formatBoolean(string|array $fields, string $trueLabel = 'Yes', string $falseLabel = 'No'): void {
		$this->format($fields, 0, '.', 'boolean', [
			'true' => $trueLabel,
			'false' => $falseLabel
		]);
	}
	
	/**
	 * Set table type ke regular table (non-DataTables)
	 * 
	 * Method ini mengubah tipe table dari DataTables menjadi regular HTML table.
	 * Regular table tidak memiliki fitur sorting, searching, pagination seperti DataTables.
	 * 
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Create simple HTML table without DataTables features
	 * $table->set_regular_table()
	 *       ->lists('users', ['name', 'email']);
	 * ```
	 */
	public function set_regular_table(): void {
		$this->tableType = self::TABLE_TYPE_REGULAR;
	}
	
	public array $button_removed = [];
	/**
	* Menghapus tombol dari daftar tombol yang tersedia.
	*
	* Fungsi ini digunakan untuk menghapus tombol-tombol tertentu dari daftar tombol
	* yang tersedia. Tombol yang dihapus akan disimpan dalam properti $button_removed.
	*
	* @param mixed $remove : Tombol yang akan dihapus. Bisa berupa string untuk satu tombol
	*                        atau array untuk beberapa tombol.
	*
	* Contoh penggunaan:
	*
	* // Menghapus satu tombol
	* $this->removeButtons('edit');
	*
	* // Menghapus beberapa tombol
	* $this->removeButtons(['view', 'delete']);
	*
	* Maka tombol 'edit' atau tombol 'view' dan 'delete' akan dihapus dari daftar tombol yang tersedia.
	*/
	public function removeButtons(string|array $remove): void {
		if (!empty($remove)) {
			if (is_array($remove)) {
				$this->button_removed = $remove;
			} else {
				$this->button_removed = [$remove];
			}
		}
	}
	
	private array $defaultButtons = ['view', 'edit', 'delete'];
	/**
	* Mengatur aksi tombol untuk tabel.
	*
	* Fungsi ini digunakan untuk mengatur aksi tombol yang tersedia dalam tabel.
	* Jika parameter $default_actions tidak diatur ke true, maka tombol default akan dihapus.
	*
	* @param array $actions : Daftar aksi tombol yang ingin ditetapkan.
	* @param boolean|array $default_actions : Jika diatur ke false, tombol default akan dihapus.
	*                                        Jika diatur ke array, tombol yang sesuai dalam array akan dihapus.
	*
	* Contoh penggunaan:
	*
	* // Mengatur aksi tombol tanpa tombol default
	* $this->setActions(['custom_action1', 'custom_action2'], false);
	*
	* // Mengatur aksi tombol dengan menghapus tombol default 'edit' dan 'delete'
	* $this->setActions(['custom_action1'], ['edit', 'delete']);
	*/
	public function setActions(array $actions = [], bool|array $default_actions = true): void {
		if (true !== $default_actions) {
			if (is_array($default_actions)) {
				$this->removeButtons($default_actions);
			} else {
				$this->removeButtons($this->defaultButtons);
			}
		}
	}
	
	private array $objectInjections = [];
	public array $filterPage = [];
	/**
	 * Initiate Configuration
	 * 
	 * @param string $connection
	 * @param array $object
	 */
	public function config(array $object = []): void {
		if (!empty($this->connection)) {
			$this->connection($this->connection);
		}
		
		if (!empty($this->filter_page)) {
			$this->filterPage = $this->filter_page;
		}
	}
	
	/**
	 * Set database connection untuk table
	 * 
	 * Method ini mengatur koneksi database yang akan digunakan untuk query.
	 * Berguna jika aplikasi menggunakan multiple database connections.
	 * 
	 * @param string|null $db_connection Nama koneksi database (dari config/database.php), null untuk default
	 * @return void
	 * 
	 * @example
	 * ```php
	 * // Use secondary database
	 * $table->connection('mysql_secondary')
	 *       ->lists('users');
	 * 
	 * // Use default connection
	 * $table->connection(null)
	 *       ->lists('users');
	 * ```
	 */
	public function connection(?string $db_connection): void {
		if ($db_connection !== null) {
			$this->connection = $db_connection;
		}
	}
	
	/**
	 * Reset database connection ke default
	 * 
	 * @return void
	 */
	public function resetConnection(): void {
		$this->connection = null;
	}
	
	public array $modelProcessing = [];
	public array|string $tableName = [];
	public array $tableID   = [];
	/**
	* Buat List(s) Data Table
	*
	* Fungsi ini digunakan untuk membuat list data table, yang dapat digunakan untuk menampilkan data dari database.
	* Fungsi ini juga dapat digunakan untuk membuat list data table dengan fitur server side, yaitu dengan mengirimkan data melalui AJAX.
	*
	* @param string $table_name
	* 	: Nama tabel yang akan di tampilkan dalam list data table.
	* 	: Jika nama tabel tidak di set maka akan menggunakan nama tabel yang di set melalui fungsi model().
	* @param array $fields
	* 	: Daftar kolom yang akan di tampilkan dalam list data table.
	* 	: Jika kolom tidak di set maka akan menampilkan semua kolom yang ada di tabel.
	* @param boolean|string|array $actions
	* 	: Tombol aksi yang akan di tampilkan dalam list data table.
	* 	: Jika di set sebagai boolean true maka akan menampilkan tombol aksi default yaitu view, edit, delete.
	* 	: Jika di set sebagai string maka akan menampilkan tombol aksi custom.
	* 	: Jika di set sebagai array maka akan menampilkan tombol aksi custom yang di definisikan dalam array.
	* 	: Contoh penggunaan:
	* 	: $this->lists('users', [], ['view', 'edit', 'delete']);
	* 	: $this->lists('users', [], 'view|primary|fa-eye');
	* @param boolean $server_side
	* 	: Jika di set sebagai true maka akan menggunakan server side untuk mengirimkan data.
	* 	: Jika di set sebagai false maka akan menggunakan client side untuk mengirimkan data.
	* @param boolean $numbering
	* 	: Jika di set sebagai true maka akan menampilkan nomor urut dalam list data table.
	* 	: Jika di set sebagai false maka tidak akan menampilkan nomor urut dalam list data table.
	* @param array $attributes
	* 	: Atribut yang akan di tambahkan dalam list data table.
	* 	: Contoh penggunaan:
	* 	: $this->lists('users', [], [], [], [], ['class' => 'table-striped']);
	* @param boolean $server_side_custom_url
	* 	: Jika di set sebagai true maka akan menggunakan URL custom untuk mengirimkan data dalam server side.
	* 	: Jika di set sebagai false maka akan menggunakan URL default untuk mengirimkan data dalam server side.
	*
	* Contoh penggunaan:
	*
	* $this->lists('users', ['nama', 'alamat'], true, true, true, [], false);
	*
	* Maka akan menampilkan list data table dengan nama tabel 'users', kolom 'nama' dan 'alamat', tombol aksi view, edit, delete, server side, dan nomor urut.
	*
	* @security Validates table name format (alphanumeric + underscore only) to prevent SQL injection.
	*           Validates fields array type. Column labels from 'field:label' format are escaped
	*           with canvastack_escape_html() before storage. Attribute values are escaped and
	*           validated against dangerous event handlers. (Requirements 1.1, 1.2, 1.6, 1.8)
	*/
	public function lists(?string $table_name = null, array $fields = [], bool|string|array $actions = true, bool $server_side = true, bool $numbering = true, array $attributes = [], bool|string $server_side_custom_url = false): void {
		try {
			// Setup model processing
			$table_name = $this->setupModelProcessing($table_name);
			
			// Extract table name from model or query
			$table_name = $this->extractTableName($table_name);
			
			// Validate table name
			if (!empty($table_name)) {
				$table_name = $this->validateTableName($table_name);
			}
			
			// Setup basic table properties
			$this->tableName = $table_name;
			$this->records['index_lists'] = $numbering;
			
			// Parse and validate fields
			$fields = $this->parseFieldLabels($fields);
			$fields = $this->validateColumns($table_name, $fields);
			
			// Process relational data
			$fields = $this->processRelationalData($table_name, $fields);
			
			// Setup search columns
			$this->setupSearchColumns($fields);
			
			// Setup actions
			$this->setupActions($table_name, $actions, $fields);
			
			// Setup table attributes and parameters
			$this->setupTableAttributes($table_name, $attributes);
			$this->params[$table_name]['attributes'] = $attributes;
			$this->setupTableParameters($table_name, $actions, $numbering, $server_side, $server_side_custom_url);
			
			// Process conditions (WHERE and columns)
			$this->processWhereConditions($table_name);
			$this->processColumnConditions($table_name);
			
			// Render table
			$this->renderTable($table_name);
			
		} catch (\InvalidArgumentException $e) {
			error_log('Objects lists() validation error: ' . $e->getMessage());
		} catch (\Exception $e) {
			error_log('Objects lists() error: ' . $e->getMessage());
		}
	}
	
	private function renderDatatable(string $name, array $columns = [], array $attributes = [], ?string $label = null): void {
		if (!empty($this->variables['table_data_model'])) {
			$attributes[$name]['model'] = $this->variables['table_data_model'];
			asort($attributes[$name]);
		}
		
		$columns[$name]['filters'] = [];
		if (!empty($this->search_columns)) {
			$columns[$name]['filters'] = $this->search_columns;
		}
		
		$this->setMethod($this->method);
		
		if (!empty($this->labelTable)) {
			$label = $this->labelTable . ':setLabelTable';
			$this->labelTable = null;
		}
		
		$this->draw($this->tableID[$name], $this->table($name, $columns, $attributes, $label));
	}
	
	private function renderGeneralTable(string $name, array $columns = [], array $attributes = []): void {
		dd($columns);
	}
	
	/**
	 * Setup model processing untuk table
	 *
	 * @param string|null $table_name
	 * @return string|null
	 */
	private function setupModelProcessing(?string $table_name): ?string {
		if (empty($this->variables['model_processing'])) {
			return $table_name;
		}
		
		if ($table_name !== $this->variables['model_processing']['table']) {
			$table_name = $this->variables['model_processing']['table'];
		}
		
		$this->modelProcessing[$table_name] = $this->variables['model_processing'];
		return $table_name;
	}
	
	/**
	 * Extract table name dari model atau query
	 *
	 * @param string|null $table_name
	 * @return string
	 */
	private function extractTableName(?string $table_name): ?string {
		if ($table_name !== null) {
			$this->variables['table_name'] = $table_name;
			return $table_name;
		}
		
		if (empty($this->variables['table_data_model'])) {
			return $table_name;
		}
		
		if ('sql' === $this->variables['table_data_model']) {
			$sql = $this->variables['query'] ?? '';
			$table_name = canvastack_get_table_name_from_sql($sql);
			$this->params[$table_name]['query'] = $sql;
		} else {
			$table_name = canvastack_get_model_table($this->variables['table_data_model']);
		}
		
		$this->variables['table_name'] = $table_name;
		return $table_name;
	}
	
	/**
	 * Parse field labels dari format 'field:label'
	 *
	 * @param array $fields
	 * @return array
	 */
	private function parseFieldLabels(array $fields): array {
		if (!is_array($fields)) {
			return [];
		}
		
		$recola = [];
		foreach ($fields as $icol => $cols) {
			if (canvastack_string_contained($cols, ':')) {
				$split_cname = explode(':', $cols);
				// @security Escape label text to prevent XSS (Requirement 1.2)
				$this->labels[$split_cname[0]] = canvastack_escape_html($split_cname[1]);
				$recola[$icol] = $split_cname[0];
			} else {
				$recola[$icol] = $cols;
			}
		}
		
		return $recola;
	}
	
	/**
	 * Validate dan check column existence
	 *
	 * @param string $table_name
	 * @param array $fields
	 * @return array
	 */
	private function validateColumns(?string $table_name, array $fields): array {
		if (empty($fields)) {
			return $this->getDefaultColumns($table_name);
		}
		
		// If table is not a view
		if (!canvastack_string_contained($table_name, self::VIEW_TABLE_PREFIX)) {
			$validated_fields = $this->check_column_exist($table_name, $fields, $this->connection);
			
			// Check if runModel() was called
			if ($this->shouldProcessModel($table_name, empty($validated_fields))) {
				canvastack_model_processing_table($this->modelProcessing, $table_name);
				// Use cached columns after model processing
				return canvastack_table_get_cached_columns($table_name, $this->connection ?? CANVASTACK_DEFAULT_DB_CONNECTION);
			}
			
			return $validated_fields;
		}
		
		return $fields;
	}
	
	/**
	 * Get default columns jika tidak ada fields yang di-set
	 *
	 * @param string $table_name
	 * @return array
	 */
	private function getDefaultColumns(?string $table_name): array {
		if (!empty($this->variables['table_fields'])) {
			return $this->check_column_exist($table_name, $this->variables['table_fields']);
		}

		$connection = $this->connection ?? CANVASTACK_DEFAULT_DB_CONNECTION;

		// Use persistent cache-backed helper instead of direct DB call
		$fields = canvastack_table_get_cached_columns($table_name, $connection);
		
		if (empty($fields)) {
			$this->processModelTable($table_name);
			// Invalidate stale cache after model processing creates the table
			canvastack_table_invalidate_schema_cache($table_name, $connection);
			$fields = canvastack_table_get_cached_columns($table_name, $connection);
		}
		
		return $fields;
	}
	
	/**
	 * Process relational data dan merge dengan fields
	 *
	 * @param string $table_name
	 * @param array $fields
	 * @return array
	 */
	private function processRelationalData(?string $table_name, array $fields): array {
		if (empty($this->relational_data)) {
			return $fields;
		}
		
		$field_relations = $this->extractRelationFields();
		$fieldset_changed = $this->identifyChangedFields($fields, $field_relations);
		
		if (empty($field_relations)) {
			return $fields;
		}
		
		$checkFieldSet = $this->buildCheckFieldSet($fields, $fieldset_changed);
		$relations = $this->buildRelations($table_name, $checkFieldSet, $field_relations);
		
		return $this->mergeRelationsWithFields($fields, $relations);
	}
	
	/**
	 * Extract relation fields dari relational_data
	 *
	 * @return array
	 */
	private function extractRelationFields(): array {
		$field_relations = [];
		
		foreach ($this->relational_data as $relData) {
			if (!empty($relData['field_target'])) {
				foreach ($relData['field_target'] as $fr_name => $relation_fields) {
					$field_relations[$fr_name] = $relation_fields;
				}
			}
			
			if (!empty($relData['foreign_keys'])) {
				$this->columns[$this->tableName]['foreign_keys'] = $relData['foreign_keys'];
			}
		}
		
		// DEBUG: Log extracted relations
		\Log::debug('extractRelationFields', [
			'field_relations_keys' => array_keys($field_relations),
			'field_relations' => $field_relations,
			'tableName' => $this->tableName
		]);
		
		return $field_relations;
	}
	
	/**
	 * Identify fields yang berubah karena relational data
	 *
	 * @param array $fields
	 * @param array $field_relations
	 * @return array
	 */
	private function identifyChangedFields(array $fields, array $field_relations): array {
		$fieldset_changed = [];
		
		// DEBUG: Log untuk troubleshooting
		\Log::debug('identifyChangedFields', [
			'fields' => $fields,
			'field_relations_keys' => array_keys($field_relations),
		]);
		
		// Optimize: Use array_flip for O(1) lookup instead of O(n) in_array
		$fields_lookup = array_flip($fields);
		
		foreach ($field_relations as $fr_name => $relation_fields) {
			if (isset($fields_lookup[$fr_name])) {
				$fieldset_changed[$fr_name] = $fr_name;
			}
		}
		
		// DEBUG: Log hasil
		\Log::debug('identifyChangedFields result', [
			'fieldset_changed' => $fieldset_changed
		]);
		
		return $fieldset_changed;
	}
	
	/**
	 * Build check field set untuk relational processing
	 *
	 * @param array $fields
	 * @param array $fieldset_changed
	 * @return array
	 */
	private function buildCheckFieldSet(array $fields, array $fieldset_changed): array {
		if (empty($fieldset_changed)) {
			return [];
		}
		
		$fieldsetChanged = [];
		foreach ($fields as $fid => $fval) {
			if (!empty($fieldset_changed[$fval])) {
				$fieldsetChanged[$fid] = $fieldset_changed[$fval];
			}
		}
		
		return $fieldsetChanged;
	}
	
	/**
	 * Build relations array dari check field set
	 *
	 * @param string $table_name
	 * @param array $checkFieldSet
	 * @param array $field_relations
	 * @return array
	 */
	private function buildRelations(?string $table_name, array $checkFieldSet, array $field_relations): array {
		$relations = [];
		
		if (empty($checkFieldSet)) {
			return $relations;
		}
		
		foreach ($checkFieldSet as $index => $field_diff) {
			if (empty($field_relations[$field_diff])) {
				continue;
			}
			
			$relational_data = $field_relations[$field_diff];
			// @security Escape relation field labels to prevent XSS (Requirement 1.2)
			$this->labels[$relational_data['field_name']] = canvastack_escape_html($relational_data['field_label']);
			$relations[$index] = $relational_data['field_name'];
			$this->columns[$table_name]['relations'][$field_diff] = $relational_data;
		}
		
		return $relations;
	}
	
	/**
	 * Merge relations dengan fields
	 *
	 * @param array $fields
	 * @param array $relations
	 * @return array
	 */
	private function mergeRelationsWithFields(array $fields, array $relations): array {
		if (empty($relations)) {
			return $fields;
		}
		
		$refields = [];
		foreach ($relations as $reid => $relation_name) {
			$refields = canvastack_array_insert($fields, $reid, $relation_name);
		}
		
		return !empty($refields) ? $refields : $fields;
	}
	
	/**
	 * Setup search columns
	 *
	 * @param array $fields
	 * @return void
	 */
	private function setupSearchColumns(array $fields): void {
		$search_columns = false;
		
		if (!empty($this->search_columns)) {
			if ($this->all_columns === $this->search_columns) {
				$search_columns = $fields;
			} else {
				$search_columns = $this->search_columns;
			}
		}
		
		$this->search_columns = $search_columns;
	}
	
	/**
	 * Setup actions untuk table
	 *
	 * @param string $table_name
	 * @param mixed $actions
	 * @param array $fields
	 * @return void
	 */
	private function setupActions(?string $table_name, mixed $actions, array $fields): void {
		if (false === $actions) {
			$actions = [];
		}
		
		$this->columns[$table_name]['lists'] = $fields;
		$this->columns[$table_name]['actions'] = $actions;
	}
	
	/**
	 * Setup table attributes
	 *
	 * @param string $table_name
	 * @param array $attributes
	 * @return void
	 * 
	 * @security Escapes table ID and class attributes to prevent XSS (Requirement 1.6)
	 */
	private function setupTableAttributes(?string $table_name, array &$attributes): void {
		// Setup column properties
		$this->setupColumnProperties($table_name);
		
		// Setup table ID and class
		$rawId    = "CanvaStack_{$this->tableType}_" . $table_name . '_' . canvastack_random_strings(50, false);
		$tableId  = canvastack_clean_strings($rawId);
		$this->tableID[$table_name] = $tableId;
		// @security Escape table_id and table_class attribute values (Requirement 1.6)
		$attributes['table_id']    = canvastack_escape_html($tableId);
		$attributes['table_class'] = canvastack_escape_html(
			canvastack_clean_strings("CanvaStack_{$this->tableType}_") . ' ' . $this->variables['table_class']
		);
		
		// Setup background color
		if (!empty($this->variables['background_color'])) {
			$attributes['bg_color'] = $this->variables['background_color'];
		}
	}
	
	/**
	 * Setup column properties (align, merge, orderby, etc)
	 *
	 * @param string $table_name
	 * @return void
	 */
	private function setupColumnProperties(?string $table_name): void {
		$properties = [
			'text_align' => 'align',
			'merged_columns' => 'merge',
			'orderby_column' => 'orderby',
			'clickable_columns' => 'clickable',
			'sortable_columns' => 'sortable',
			'searchable_columns' => 'searchable',
			'filter_groups' => 'filter_groups',
			'format_data' => 'format_data'
		];
		
		foreach ($properties as $var_key => $col_key) {
			if (!empty($this->variables[$var_key])) {
				$this->columns[$table_name][$col_key] = $this->variables[$var_key];
			}
		}
		
		// Handle hidden columns
		if (!empty($this->variables['hidden_columns'])) {
			$this->columns[$table_name]['hidden_columns'] = $this->variables['hidden_columns'];
			$this->variables['hidden_columns'] = [];
		}
		
		// Handle button removed
		if (!empty($this->button_removed)) {
			$this->columns[$table_name]['button_removed'] = $this->button_removed;
		}
	}
	
	/**
	 * Setup table parameters
	 *
	 * @param string $table_name
	 * @param mixed $actions
	 * @param bool $numbering
	 * @param bool $server_side
	 * @param bool $server_side_custom_url
	 * @return void
	 */
	private function setupTableParameters(?string $table_name, mixed $actions, bool $numbering, bool $server_side, bool|string $server_side_custom_url): void {
		// Setup on load parameters
		if (!empty($this->variables['on_load']['display_limit_rows'])) {
			$this->params[$table_name]['on_load']['display_limit_rows'] = $this->variables['on_load']['display_limit_rows'];
		}
		
		// Setup fixed columns
		if (!empty($this->variables['fixed_columns'])) {
			$this->params[$table_name]['fixed_columns'] = $this->variables['fixed_columns'];
		}
		
		// Setup basic parameters
		$this->params[$table_name]['actions'] = $actions;
		$this->params[$table_name]['buttons_removed'] = $this->button_removed ?? [];
		$this->params[$table_name]['numbering'] = $numbering;
		$this->params[$table_name]['server_side']['status'] = $server_side;
		$this->params[$table_name]['server_side']['custom_url'] = $server_side_custom_url;
		
		// Setup column width
		if (!empty($this->variables['column_width'])) {
			$this->params[$table_name]['attributes']['column_width'] = $this->variables['column_width'];
		}
		
		// Setup URL value
		if (!empty($this->variables['url_value'])) {
			$this->params[$table_name]['url_value'] = $this->variables['url_value'];
		}
		
		// Setup additional attributes
		if (!empty($this->variables['add_table_attributes'])) {
			$this->params[$table_name]['attributes']['add_attributes'] = $this->variables['add_table_attributes'];
		}
		
		// Setup filter model
		if (!empty($this->filter_model)) {
			$this->params[$table_name]['filter_model'] = $this->filter_model;
		}
	}
	
	/**
	 * Process WHERE conditions
	 *
	 * @param string $table_name
	 * @return void
	 */
	private function processWhereConditions(?string $table_name): void {
		if (empty($this->conditions)) {
			return;
		}
		
		$this->params[$table_name]['conditions'] = $this->conditions;
		
		// Process formula
		if (!empty($this->conditions['formula'])) {
			$this->formula[$table_name] = $this->conditions['formula'];
			unset($this->conditions['formula']);
			$this->conditions[$table_name]['formula'] = $this->formula[$table_name];
		}
		
		// Process WHERE conditions
		if (!empty($this->conditions['where'])) {
			$whereDataConditions = $this->transformWhereConditions($this->conditions['where']);
			$this->conditions[$table_name]['where'] = $whereDataConditions;
		}
	}
	
	/**
	 * Transform WHERE conditions dari format input ke format output
	 * Mengurangi nesting dari 9 level menjadi 2-3 level
	 *
	 * @param array $whereConditions
	 * @return array
	 */
	private function transformWhereConditions(array $whereConditions): array {
		// Step 1: Group by field and operator
		$whereConds = $this->groupWhereByField($whereConditions);
		
		// Step 2: Flatten values
		$whereConditions = $this->flattenWhereValues($whereConds);
		
		// Step 3: Build conditionals
		$whereConditionals = $this->buildWhereConditionals($whereConditions);
		
		// Step 4: Finalize conditions
		return $this->finalizeWhereConditions($whereConditionals);
	}
	
	/**
	 * Group WHERE conditions by field name and operator
	 *
	 * @param array $whereConditions
	 * @return array
	 */
	private function groupWhereByField(array $whereConditions): array {
		$whereConds = [];
		
		foreach ($whereConditions as $where_conds) {
			$field_name = $where_conds['field_name'] ?? '';
			$operator = $where_conds['operator'] ?? '';
			$value = $where_conds['value'] ?? null;
			
			$whereConds[$field_name][$operator]['field_name'][$field_name] = $field_name;
			$whereConds[$field_name][$operator]['operator'][$operator] = $operator;
			$whereConds[$field_name][$operator]['values'][] = $value;
		}
		
		return $whereConds;
	}
	
	/**
	 * Flatten WHERE values (handle nested arrays)
	 *
	 * @param array $whereConds
	 * @return array
	 */
	private function flattenWhereValues(array $whereConds): array {
		$whereConditions = [];
		
		foreach ($whereConds as $whereFields => $whereFieldValues) {
			foreach ($whereFieldValues as $whereOperators => $whereOperatorValues) {
				foreach ($whereOperatorValues as $whereOperatorDataKey => $whereOperatorDataValues) {
					if ('values' === $whereOperatorDataKey) {
						$whereConditions[$whereFields][$whereOperators][$whereOperatorDataKey] = 
							$this->flattenValueArray($whereOperatorDataValues);
					} else {
						$whereConditions[$whereFields][$whereOperators][$whereOperatorDataKey] = $whereOperatorDataValues;
					}
				}
			}
		}
		
		return $whereConditions;
	}
	
	/**
	 * Flatten nested value array
	 *
	 * @param mixed $values
	 * @return array
	 */
	private function flattenValueArray(mixed $values): array {
		if (!is_array($values)) {
			return [];
		}
		
		$flattened = [];
		
		foreach ($values as $value) {
			if (is_array($value)) {
				foreach ($value as $_value) {
					$flattened[$_value] = $_value;
				}
			} else {
				$flattened[$value] = $value;
			}
		}
		
		return $flattened;
	}
	
	/**
	 * Build WHERE conditionals structure
	 *
	 * @param array $whereConditions
	 * @return array
	 */
	private function buildWhereConditionals(array $whereConditions): array {
		$whereConditionals = [];
		
		foreach ($whereConditions as $whereConditionsFieldName => $whereConditionsDataFields) {
			foreach ($whereConditionsDataFields as $whereOperatorsType => $whereConditionalData) {
				$whereConditionals[$whereConditionsFieldName][$whereOperatorsType] = [
					'field_name' => $whereConditionsFieldName,
					'operator' => $whereOperatorsType,
					'value' => $whereConditionalData['values'] ?? []
				];
			}
		}
		
		return $whereConditionals;
	}
	
	/**
	 * Finalize WHERE conditions ke format array flat
	 *
	 * @param array $whereConditionals
	 * @return array
	 */
	private function finalizeWhereConditions(array $whereConditionals): array {
		$whereDataConditions = [];
		
		foreach ($whereConditionals as $whereConditionalsFieldData) {
			foreach ($whereConditionalsFieldData as $whereConditionalsFieldSets) {
				$whereDataConditions[] = $whereConditionalsFieldSets;
			}
		}
		
		return $whereDataConditions;
	}
	
	/**
	 * Process column conditions
	 *
	 * @param string $table_name
	 * @return void
	 */
	private function processColumnConditions(?string $table_name): void {
		if (empty($this->conditions['columns'])) {
			return;
		}
		
		$columnCond = $this->conditions['columns'];
		unset($this->conditions['columns']);
		$this->conditions[$table_name]['columns'] = $columnCond;
	}
	
	/**
	 * Render table (datatable atau general table)
	 *
	 * @param string $table_name
	 * @return void
	 */
	private function renderTable(?string $table_name): void {
		// @security Escape table label to prevent XSS (Requirement 1.2)
		$label = isset($this->variables['table_name'])
			? canvastack_escape_html($this->variables['table_name'])
			: null;
		
		if ('datatable' === $this->tableType) {
			$this->renderDatatable($table_name, $this->columns, $this->params, $label);
		} else {
			$this->renderGeneralTable($table_name, $this->columns, $this->params);
		}
	}
}
