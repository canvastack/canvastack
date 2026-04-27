<?php
namespace Canvastack\Canvastack\Library\Components\Table\Craft;

use Canvastack\Canvastack\Models\Admin\System\DynamicTables;
use Canvastack\Canvastack\Library\Components\Table\Craft\Method\Post;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\TableConstants;
use Canvastack\Canvastack\Library\Exceptions\Table\TableValidationException;
use Canvastack\Canvastack\Library\Exceptions\Table\XSSAttemptException;

/**
 * Builder Class - HTML Table Builder with DataTables Integration
 * 
 * This class provides a fluent interface for building HTML tables with DataTables
 * integration, including support for:
 * - Dynamic column configuration
 * - Server-side processing
 * - Advanced filtering and searching
 * - Column merging and grouping
 * - Custom styling and formatting
 * - Action buttons and row operations
 * - Formula columns and calculations
 * 
 * SECURITY FEATURES:
 * - Input validation for all parameters
 * - XSS protection with output escaping
 * - SQL injection prevention via Eloquent/Query Builder
 * - Error handling with graceful degradation
 * 
 * PERFORMANCE FEATURES:
 * - Caching for repeated operations
 * - Optimized array access
 * - Reduced string manipulation overhead
 * 
 * USAGE EXAMPLE:
 * ```php
 * $builder = new Builder();
 * $html = $builder->table('users', [
 *     'users' => [
 *         'lists' => ['name', 'email', 'created_at'],
 *         'searchable' => ['name', 'email'],
 *         'sortable' => ['name', 'created_at']
 *     ]
 * ], [
 *     'users' => [
 *         'model' => User::class,
 *         'attributes' => [
 *             'table_id' => 'users-table',
 *             'table_class' => 'table table-striped'
 *         ],
 *         'server_side' => [
 *             'status' => true
 *         ]
 *     ]
 * ], 'User Management');
 * ```
 * 
 * Created on 21 Apr 2021
 * Time Created	: 08:13:39
 * 
 * @package    Canvastack\Canvastack\Library\Components\Table\Craft
 * @author     wisnuwidi@canvastack.com
 * @copyright  wisnuwidi
 * @version    2.0.0
 * @since      21 Apr 2021
 */
 
class Builder {
	use Scripts;
	
	public $model;
	public $method = 'POST';
	
	// Constants for validation
	private const ALLOWED_METHODS = ['GET', 'POST'];
	private const SPECIAL_COLUMNS = [TableConstants::COL_NO, TableConstants::COL_ID, 'nik', TableConstants::COL_NUMBER_LISTS, TableConstants::COL_ACTION];
	
	// Constants for column widths
	private const COLUMN_WIDTH_SMALL = 30;
	private const COLUMN_WIDTH_MEDIUM = 50;
	
	// Constants for default values
	private const DEFAULT_TABLE_ID = 'datatable';
	private const DEFAULT_TABLE_CLASS = TableConstants::CLASS_TABLE;
	private const MERGE_TEXT_SEPARATOR = '::merge::';
	private const LIST_SUFFIX = ' List(s)';
	private const LABEL_TABLE_MARKER = ':setLabelTable';
	
	// Constants for special column names
	private const COLUMN_NUMBER_LISTS = TableConstants::COL_NUMBER_LISTS;
	private const COLUMN_ACTION = TableConstants::COL_ACTION;
	private const COLUMN_NO = TableConstants::COL_NO;
	private const COLUMN_ID = TableConstants::COL_ID;
	private const COLUMN_NIK = 'nik';
	
	/**
	 * Dangerous HTML event handler attribute prefixes/names that must be blocked.
	 * These can be used to inject JavaScript via attribute injection attacks.
	 *
	 * @security XSS Prevention - blocks event handler injection
	 * @var string[]
	 */
	private const DANGEROUS_ATTR_PATTERNS = [
		'on',        // Matches all on* handlers (onclick, onload, onerror, etc.)
		'formaction',
		'srcdoc',
		'xlink:href',
	];	
	/**
	 * Validate table inputs
	 *
	 * @security XSS Prevention - validates that $name is a non-empty string and
	 *           $columns/$attributes are arrays. This is the first line of defense
	 *           before any user-supplied data is processed or rendered.
	 *
	 * @param string $name Table name
	 * @param array $columns Column configuration
	 * @param array $attributes Table attributes
	 * @throws \InvalidArgumentException If validation fails
	 */
	private function validateTableInputs(string $name, array $columns, array $attributes): void {
		// Validate table name
		if (!is_string($name) || empty($name)) {
			throw new TableValidationException('Table name must be a non-empty string');
		}
		
		// Validate columns parameter
		if (!is_array($columns)) {
			throw new TableValidationException('Columns parameter must be an array');
		}
		
		// Validate attributes parameter
		if (!is_array($attributes)) {
			throw new TableValidationException('Attributes parameter must be an array');
		}
		
		// Validate columns structure if provided
		if (!empty($columns[$name]) && !is_array($columns[$name])) {
			throw new TableValidationException('Column configuration must be an array');
		}
	}
	
	/**
	 * Validate and sanitize table ID
	 * SECURITY: Prevents XSS in HTML attributes
	 *
	 * @param string $tableID Table ID to validate
	 * @return string Sanitized table ID
	 */
	private function validateTableID(string $tableID): string {
		// Remove any HTML/script tags
		$tableID = strip_tags($tableID);
		
		// Only allow alphanumeric, dash, underscore
		$tableID = preg_replace('/[^a-zA-Z0-9_-]/', '', $tableID);
		
		if (empty($tableID)) {
			return self::DEFAULT_TABLE_ID; // Default fallback
		}
		
		return $tableID;
	}
	
	/**
	 * Validate width value
	 * SECURITY: Prevents XSS in width attributes
	 *
	 * @param mixed $width Width value to validate
	 * @return int|null Valid width or null
	 */
	private function validateWidth(mixed $width): ?int {
		// Only accept numeric values
		$width = filter_var($width, FILTER_VALIDATE_INT);
		
		if ($width === false || $width <= 0 || $width > 1000) {
			return null;
		}
		
		return $width;
	}
	
	/**
	 * Validate color value
	 * SECURITY: Prevents XSS in style attributes
	 *
	 * @security XSS Prevention - only allows safe color values
	 * @param string $color Color value to validate
	 * @return string|null Valid color or null
	 */
	private function validateColor(string $color): ?string {
		// Allow hex colors (#fff, #ffffff)
		if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $color)) {
			return $color;
		}
		
		// Allow named colors (whitelist)
		$allowedColors = [
			'red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink',
			'black', 'white', 'gray', 'grey', 'brown', 'cyan', 'magenta'
		];
		
		if (in_array(strtolower($color), $allowedColors)) {
			return strtolower($color);
		}
		
		return null;
	}
	
	/**
	 * Validate an attributes array for dangerous event handler keys.
	 *
	 * Strips any attribute whose key starts with "on" (e.g. onclick, onload)
	 * or matches other dangerous patterns that could be used for XSS injection.
	 * Attribute values are escaped separately by setAttributes().
	 *
	 * @security XSS Prevention - removes event handler injection vectors.
	 *           Call this before passing user-supplied attributes to setAttributes().
	 *
	 * @param  array $attributes Key-value pairs of HTML attributes
	 * @return array Sanitized attributes with dangerous keys removed
	 *
	 * @example
	 * // Dangerous input
	 * $attrs = ['class' => 'table', 'onclick' => 'alert(1)', 'id' => 'my-table'];
	 * $safe  = $this->validateAttributeKeys($attrs);
	 * // Returns: ['class' => 'table', 'id' => 'my-table']
	 */
	private function validateAttributeKeys(array $attributes): array {
		$safe = [];
		foreach ($attributes as $key => $value) {
			$keyLower = strtolower((string) $key);
			
			$isDangerous = false;
			foreach (self::DANGEROUS_ATTR_PATTERNS as $pattern) {
				// Block exact match or prefix match (e.g. "on" catches "onclick")
				if ($keyLower === $pattern || str_starts_with($keyLower, $pattern)) {
					$isDangerous = true;
					error_log('[SECURITY] Builder::validateAttributeKeys(): Blocked dangerous attribute key "' . $key . '"');
					break;
				}
			}
			
			if (!$isDangerous) {
				$safe[$key] = $value;
			}
		}
		return $safe;
	}
	/**
	 * Cache for escaped tableID values (in-memory, per-request)
	 *
	 * @var array
	 */
	private $escapedTableIDCache = [];

	/**
	 * Cache for column labels (in-memory, per-request)
	 *
	 * @var array
	 */
	private $columnLabelCache = [];

	/**
	 * Get escaped tableID with in-memory caching
	 *
	 * @param string $tableID Table ID to escape
	 * @return string Escaped table ID
	 */
	private function getEscapedTableID(string $tableID): string {
		if (!isset($this->escapedTableIDCache[$tableID])) {
			$this->escapedTableIDCache[$tableID] = htmlspecialchars($tableID, ENT_QUOTES, 'UTF-8');
		}
		return $this->escapedTableIDCache[$tableID];
	}

	/**
	 * Get formatted column label with two-level caching
	 *
	 * Uses an in-memory L1 cache (per-request) backed by a Laravel Cache L2
	 * (cross-request, persistent). Column labels are derived from column names
	 * and are stable across requests, making them good candidates for persistent
	 * caching.
	 *
	 * @performance Eliminates repeated ucwords/str_replace/htmlspecialchars calls
	 *              for the same column name across multiple table renders.
	 *
	 * @security XSS Prevention - the label is escaped with htmlspecialchars() before
	 *           being stored in the cache and returned. This ensures all column labels
	 *           rendered in table headers are safe from XSS injection.
	 *
	 * @param string $column Column name (or custom label string)
	 * @return string Formatted and HTML-escaped label
	 */
	private function getColumnLabel(string $column): string {
		// L1: in-memory cache (fastest, per-request)
		if (isset($this->columnLabelCache[$column])) {
			return $this->columnLabelCache[$column];
		}

		// L2: persistent cache (CONFIG: Check development settings)
		$cacheEnabled = $this->shouldUseCache();
		$cacheKey     = config('canvastack.cache.prefix', 'canvastack_') . config('canvastack.cache.config.key_prefix', 'config_') . 'col_label_' . md5($column);
		$cacheTtl     = (int) config('canvastack.cache.config.ttl', 3600);

		if ($cacheEnabled) {
			$cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
			if ($cached !== null) {
				$this->columnLabelCache[$column] = $cached;
				
				// Monitor cache hit
				canvastack_table_cache_monitor('get', $cacheKey, true);
				
				// Log cache operation in development
				if (config('canvastack.cache.development.log_operations', false)) {
					\Log::debug('Cache HIT: Column label', ['column' => $column, 'key' => $cacheKey]);
				}
				
				return $cached;
			}
			
			// Monitor cache miss
			canvastack_table_cache_monitor('get', $cacheKey, false);
		}

		// Compute label
		$label  = ucwords(str_replace('_', ' ', $column));
		$result = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

		// Store in L1
		$this->columnLabelCache[$column] = $result;

		// Store in L2
		if ($cacheEnabled) {
			try {
				\Illuminate\Support\Facades\Cache::put($cacheKey, $result, $cacheTtl);
			} catch (\Exception $e) {
				// Non-fatal: log and continue without persistent cache
				error_log('Builder::getColumnLabel() cache write failed: ' . $e->getMessage());
			}
		}

		return $result;
	}
	/**
	/**
	 * Check if cache should be used based on development settings
	 *
	 * @param string $cacheType Type of cache (config, validation, etc.)
	 * @return bool
	 */
	private function shouldUseCache(string $cacheType = 'config'): bool
	{
	    // Disable cache in development if configured
	    if (config('canvastack.cache.development.disable_in_dev', false) && app()->environment('local')) {
	        if (config('canvastack.cache.development.log_operations', false)) {
	            \Log::debug('Cache disabled in development environment', ['type' => $cacheType]);
	        }
	        return false;
	    }

	    return config("canvastack.cache.{$cacheType}.enabled", true);
	}
	/**
	 * Initialize table data model
	 *
	 * @param string $name Table name
	 * @param array $attributes Table attributes
	 * @return array Data array with model information
	 */
	private function initializeTableModel(string $name, array $attributes): array {
		$data = [];
		$model = null;

		if (!empty($attributes[$name]['model'])) {
			if ('sql' === $attributes[$name]['model']) {
				$data[$name]['model'] = 'sql';
				$data[$name]['sql']   = $attributes[$name]['query'];
			} else {
				$model = new $attributes[$name]['model']();
				$data[$name]['model'] = $attributes[$name]['model'];
			}
		} else {
			$model = new DynamicTables(null, $this->connection);
			$model->setTable($name);
			$data[$name]['model']       = get_class($model);
			$attributes[$name]['model'] = get_class($model);
		}

		if (!empty($model)) {
			$this->model[$name]['type']   = 'model';
			$this->model[$name]['source'] = $model;
		} else {
			$this->model[$name]['type']   = 'sql';
			$this->model[$name]['source'] = $data[$name]['sql'];
		}

		return $data;
	}

	/**
	 * Extract table configuration from attributes
	 *
	 * @param string $name Table name
	 * @param array $attributes Table attributes
	 * @return array Configuration array with tableID, tableClass, etc.
	 */
	private function extractTableConfig(string $name, array $attributes): array {
		$config = [
			'tableID' => self::DEFAULT_TABLE_ID,
			'tableClass' => self::DEFAULT_TABLE_CLASS,
			'serverSide' => false,
			'customURL' => null
		];

		if (!empty($attributes[$name])) {
			// SECURITY: Validate and sanitize tableID
			$rawTableID = isset($attributes[$name]['attributes']['table_id'])
				? $attributes[$name]['attributes']['table_id']
				: self::DEFAULT_TABLE_ID;
			$config['tableID'] = $this->validateTableID($rawTableID);

			$config['tableClass'] = isset($attributes[$name]['attributes']['table_class'])
				? $attributes[$name]['attributes']['table_class']
				: self::DEFAULT_TABLE_CLASS;

			$config['serverSide'] = isset($attributes[$name]['server_side']['status'])
				? $attributes[$name]['server_side']['status']
				: false;

			$config['customURL'] = isset($attributes[$name]['server_side']['custom_url'])
				? $attributes[$name]['server_side']['custom_url']
				: null;

			$this->serverSide = $config['serverSide'];
			$this->customURL = $config['customURL'];
		}

		return $config;
	}

	/**
	 * Build table data array
	 *
	 * @param string $name Table name
	 * @param array $columns Column configuration
	 * @param array $attributes Table attributes
	 * @param array $data Existing data array
	 * @return array Complete data array
	 */
	private function buildTableData(string $name, array $columns, array $attributes, array $data): array {
		$data[$name]['name']       = $name;
		$data[$name]['columns']    = $columns[$name];
		$data[$name]['attributes'] = $attributes[$name];

		// FORMULATION
		if (!empty($data[$name]['attributes']['conditions']['formula'])) {
			if (!empty($data[$name]['columns']['lists'])) {
				$data[$name]['columns']['lists'] = $this->setFormulaColumns(
					$data[$name]['columns']['lists'],
					$data[$name]
				);
			}
		}

		return $data;
	}

	/**
	 * Build table title HTML
	 *
	 * @security XSS Prevention - the title text (derived from $name or $label) is
	 *           escaped with htmlspecialchars() before being rendered into HTML.
	 *           This prevents XSS if a table name or label contains special characters.
	 *
	 * @param string $name Table name
	 * @param string|null $label Custom label
	 * @return string Table title HTML
	 */
	private function buildTableTitle(string|false $name, ?string $label): string {
		if (false === $name) {
			return '';
		}

		$list = null;
		if (canvastack_string_contained($label, self::LABEL_TABLE_MARKER)) {
			$list = null;
			$label = str_replace(self::LABEL_TABLE_MARKER, '', $label);
		} else {
			$list = self::LIST_SUFFIX;
		}

		if (empty($label)) {
			$titleText = ucwords(str_replace('_', ' ', $name)) . $list;
		} else {
			$titleText = ucwords(str_replace('_', ' ', $label)) . $list;
		}

		// SECURITY: Escape title text untuk mencegah XSS
		$safeTitleText = htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8');

		return '<div class="panel-heading"><div class="pull-left"><h3 class="panel-title">'
			. $safeTitleText
			. '</h3></div><div class="clearfix"></div></div>';
	}

	/**
	 * Build table HTML structure
	 *
	 * @security XSS Prevention - validates and escapes all table attributes before rendering.
	 *           User-supplied add_attributes are filtered for dangerous event handlers.
	 *
	 * @param array $data Table data
	 * @param array $config Table configuration
	 * @param string $name Table name
	 * @param array $attributes Table attributes
	 * @return string Table HTML
	 */
	private function buildTableHTML(array $data, array $config, string $name, array $attributes): string {
			// SECURITY: Escape tableClass to prevent XSS in class attribute
			$safeTableClass = htmlspecialchars($config['tableClass'], ENT_QUOTES, 'UTF-8');
			$baseTableAttributes = [
				'id' => $config['tableID'], 
				'class' => $safeTableClass,
				TableConstants::ATTR_ROLE => TableConstants::ROLE_TABLE
			];
			$tableAttributes = $baseTableAttributes;

			if (!empty($attributes[$name]['attributes']['add_attributes'])) {
				// SECURITY: Strip dangerous event handler keys (1.4.4) before merging
				$safeAddAttributes = $this->validateAttributeKeys(
					$attributes[$name]['attributes']['add_attributes']
				);
				$tableAttributes = array_merge_recursive($baseTableAttributes, $safeAddAttributes);
			}

			// Task 4.4.1: Generate descriptive table caption for screen readers
			$caption = $this->generateTableCaption($name, $data, $attributes);

			$table  = '<div class="panel-body no-padding">';
			$table .= '<table' . $this->setAttributes($tableAttributes) . '>';
			$table .= $caption; // Add caption after <table> tag
			$table .= $this->header($data[$name]);
			$table .= '</table>';
			$table .= '</div>';

			return $table;
		}
	/**
		 * Generate descriptive table caption for screen readers
		 *
		 * Creates an accessible caption that describes the table content and provides
		 * context for screen reader users. The caption includes the table name and
		 * optionally the record count if available.
		 *
		 * Task 4.4.1: Add descriptive table caption (Requirement 13.1)
		 *
		 * @param string $name Table name
		 * @param array $data Table data configuration
		 * @param array $attributes Table attributes
		 * @return string HTML caption element
		 */
		private function generateTableCaption(string $name, array $data, array $attributes): string {
			// Get human-readable table name
			$tableName = $name;
			if (!empty($data[$name]['attributes']['label'])) {
				$tableName = $data[$name]['attributes']['label'];
			} else {
				$tableName = ucwords(str_replace('_', ' ', $name));
			}

			// SECURITY: Escape table name to prevent XSS
			$safeTableName = htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8');

			// Build caption text
			$captionText = "Table showing {$safeTableName}";

			// Add record count if available (will be updated by JavaScript for server-side tables)
			if (!empty($attributes[$name]['server_side']['status'])) {
				// For server-side tables, add a placeholder that will be updated via JavaScript
				$captionText .= '. <span class="table-record-count" aria-live="polite">Loading records...</span>';
			}

			// Add screen reader only class to hide visually but keep accessible
			return '<caption class="sr-only">' . $captionText . '</caption>';
		}

	/**
	 * Build filter section HTML
	 *
	 * @param string $tableID Table ID
	 * @return string Filter HTML or empty string
	 */
	/**
	 * Build filter section HTML
	 *
	 * @param string $tableID Table ID
	 * @return string Filter HTML or empty string
	 */
	/**
	 * Build filter section HTML
	 *
	 * @param string $tableID Table ID
	 * @return string Filter HTML or empty string
	 */
	private function buildFilterSection(string $tableID): string {
		$filterContent = $this->filter_contents[$tableID] ?? null;

		if (empty($filterContent['id']) || $tableID !== $filterContent['id']) {
			return '';
		}

		// SECURITY: Escape tableID in HTML output (with caching)
		$safeTableID = $this->getEscapedTableID($tableID);

		$html  = '<span class="canvastack-dt-search-box hide" id="canvastack-'
			. $safeTableID
			. '-search-box">'
			. $this->filterButton($filterContent)
			. '</span>';
		$html .= $this->filterModalbox($filterContent);

		return $html;
	}

	/**
	 * Wrap table in container HTML
	 *
	 * @param string $tableTitle Table title HTML
	 * @param string $tableHTML Table HTML
	 * @param string $datatableColumns DataTable columns
	 * @param string $tableID Table ID
	 * @return string Complete wrapped HTML
	 */
	/**
	 * Wrap table in container HTML
	 *
	 * @param string $tableTitle Table title HTML
	 * @param string $tableHTML Table HTML
	 * @param string $datatableColumns DataTable columns
	 * @param string $tableID Table ID
	 * @return string Complete wrapped HTML
	 */
	private function wrapTableInContainer(string $tableTitle, string $tableHTML, string $datatableColumns, string $tableID): string {
		$safeTableID = $this->getEscapedTableID($tableID);

		// Task 4.4.5-4.4.8: Add aria-live regions for screen reader announcements
		$ariaLiveRegions = $this->buildAriaLiveRegions($safeTableID);

		$html  = '<div class="row">';
		$html .= '<div class="col-md-12">';
		$html .= '<div class="panel">' . $tableTitle . '<br />';
		$html .= '<div class="relative canvastack-table-box-' . $safeTableID . '">';
		$html .= $ariaLiveRegions; // Add aria-live regions before table
		$html .= $this->buildFilterSection($tableID);
		$html .= $tableHTML . $datatableColumns;
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
	/**
		 * Build aria-live regions for screen reader announcements
		 *
		 * Creates hidden aria-live regions that will be updated by JavaScript to announce
		 * table state changes to screen reader users. These regions are visually hidden
		 * but accessible to assistive technologies.
		 *
		 * Tasks 4.4.5-4.4.8: Screen reader announcements for pagination, filters, sorting, and loading
		 *
		 * @param string $tableID Escaped table ID
		 * @return string HTML for aria-live regions
		 */
		private function buildAriaLiveRegions(string $tableID): string {
			$html = '<div class="sr-only" aria-live="polite" aria-atomic="true">';

			// Task 4.4.5: Pagination announcements
			$html .= '<div id="' . $tableID . '-pagination-status" class="table-pagination-status"></div>';

			// Task 4.4.6: Filter status announcements
			$html .= '<div id="' . $tableID . '-filter-status" class="table-filter-status"></div>';

			// Task 4.4.7: Sort direction announcements
			$html .= '<div id="' . $tableID . '-sort-status" class="table-sort-status"></div>';

			// Task 4.4.8: Loading status announcements (aria-busy will be set on table)
			$html .= '<div id="' . $tableID . '-loading-status" class="table-loading-status"></div>';

			$html .= '</div>';

			return $html;
		}
	
	/**
	 * Set HTTP method for table requests
	 * 
	 * Configures the HTTP method used for DataTables AJAX requests.
	 * 
	 * @param string $method HTTP method ('GET' or 'POST')
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $builder->setMethod('POST');
	 * ```
	 */
	protected function setMethod(string $method): void {
		$this->method = $method;
	}
	
	/**
	 * Build HTML table with DataTables integration
	 * 
	 * Generates complete table HTML including:
	 * - Table header with column configuration
	 * - DataTables body with AJAX support
	 * - Filter integration if configured
	 * - Action buttons if configured
	 * 
	 * @security XSS Prevention - all user-controllable data is escaped before rendering:
	 *           - Table name/label escaped via buildTableTitle()
	 *           - Column labels escaped via getColumnLabel() (htmlspecialchars)
	 *           - Table attributes escaped via setAttributes() (htmlspecialchars)
	 *           - add_attributes filtered for dangerous event handlers via validateAttributeKeys()
	 *           - tableClass escaped before use in HTML
	 *           - Output marked with SafeHtml::mark() to prevent double-encoding
	 *
	 * @param string $name Table name
	 * @param array $columns Column configuration
	 * @param array $attributes Table attributes
	 * @param string|null $label Custom table label
	 * @return string Complete table HTML (marked as SafeHtml)
	 * @throws \InvalidArgumentException If invalid inputs
	 */
	protected function table(string $name, array $columns = [], array $attributes = [], ?string $label = null): string {
		try {
			// SECURITY: Validate all inputs
			$this->validateTableInputs($name, $columns, $attributes);
			
			// Initialize model and data
			$data = $this->initializeTableModel($name, $attributes);
			
			// Extract configuration
			$config = $this->extractTableConfig($name, $attributes);
			
			// Build complete data array
			$data = $this->buildTableData($name, $columns, $attributes, $data);
			
			// Build table components
			$tableTitle = $this->buildTableTitle($name, $label);
			$tableHTML = $this->buildTableHTML($data, $config, $name, $attributes);
			$datatableColumns = $this->body($data[$name]);
			
			// Wrap and return
			$output = $this->wrapTableInContainer(
				$tableTitle, 
				$tableHTML, 
				$datatableColumns, 
				$config['tableID']
			);
			
			// Return final HTML directly - no SafeHtml marking needed here since
			// this output goes straight to the browser and is not passed through
			// any further escaping layer. Marking would cause the marker to leak
			// into the rendered page (including inside <script> blocks).
			return $output;
			
		} catch (\InvalidArgumentException $e) {
			// SECURITY: Log error and return safe error message
			\Log::error('Builder table() validation error: ' . $e->getMessage(), [
				'exception' => $e,
				'table' => $name ?? 'unknown'
			]);
			return '<div class="alert alert-danger">Error: Invalid table configuration</div>';
		} catch (\Exception $e) {
			// SECURITY: Log error and return safe error message
			\Log::error('Builder table() error: ' . $e->getMessage(), [
				'exception' => $e,
				'table' => $name ?? 'unknown'
			]);
			return '<div class="alert alert-danger">Error: Unable to render table</div>';
		}
	}
	
	private $columnManipulated = [];
	
	/**
	 * Check and manipulate column labels
	 * 
	 * Processes column labels to extract custom labels from 'field:Label' format
	 * and stores them for later use in table rendering.
	 * 
	 * @param array $check_labels Labels to check and process
	 * @param array $columns Column list
	 * @return array Processed labels array
	 * 
	 * @security Labels are sanitized via getColumnLabel() which applies htmlspecialchars()
	 * 
	 * @internal Used internally by table() method
	 */
	private function checkColumnLabel(array $check_labels, array $columns): array {
		$labels = [];
		foreach ($columns as $icol => $vcol) {
			if (!empty($this->labels[$vcol])) {
				$this->columnManipulated[$this->labels[$vcol]] = $vcol;
				$labels[$icol] = $this->labels[$vcol];
			} else {
				$this->columnManipulated[$vcol] = $vcol;
				$labels[$icol] = $vcol;
			}
		}
		
		return $labels;
	}
	
	/**
	 * Build table header with columns
	 * Refactored to reduce nesting from 5 to 2 levels
	 *
	 * @security XSS Prevention - column labels are escaped via getColumnLabel() which
	 *           applies htmlspecialchars(). Custom labels from $this->labels are also
	 *           routed through getColumnLabel() ensuring consistent escaping.
	 * 
	 * @param array $data Table data configuration
	 * @return string HTML for table header
	 */
	private function header(array $data = []): string {
		$config = $this->prepareHeaderConfig($data);
		
		// Early return for empty columns
		if (empty($config['columns']) || !is_array($config['columns'])) {
			return '<thead></thead>';
		}
		
		if (!empty($config['mergeColumn'])) {
			return $this->buildMergedHeader($config);
		}
		
		return $this->buildStandardHeader($config);
	}
	
	/**
	 * Prepare header configuration from data
	 * 
	 * @param array $data Table data
	 * @return array Configuration array
	 */
	private function prepareHeaderConfig(array $data): array {
		$columns = $data['columns'];
		$attributes = $data['attributes'];
		
		$sortable = $data['columns']['sortable'] ?? false;
		$hiddenColumn = $data['columns']['hidden_columns'] ?? [];
		$alignColumn = $this->extractAlignmentConfig($columns);
		$mergeColumn = $this->extractMergeConfig($columns);
		
		// Prepare columns list
		$columnsList = $columns['lists'] ?? $columns;
		$columnsList = $this->addSpecialColumns($columnsList, $attributes);
		
		if (!empty($this->labels)) {
			$columnsList = $this->checkColumnLabel($this->labels, $columnsList);
		}
		
		$dataColumns = $this->columnManipulated ?? [];
		
		// Extract colors
		[$columnColor, $headerColor] = $this->extractColorSettings($attributes);
		
		// Setup attributes for sortable columns
		$attributes['sortable_columns'] = $sortable;
		$attributes['attributes']['column']['id'] = [];
		$attributes['attributes']['column']['class'] = [];
		
		$widthColumn = $attributes['attributes']['column_width'] ?? [];
		
		return compact('columns', 'columnsList', 'attributes', 'sortable', 'hiddenColumn', 
		               'alignColumn', 'mergeColumn', 'dataColumns', 'columnColor', 
		               'headerColor', 'widthColumn');
	}
	
	/**
	 * Extract alignment configuration from columns
	 * 
	 * @param array $columns Column configuration
	 * @return array Alignment configuration
	 */
	private function extractAlignmentConfig(array $columns): array {
		$alignColumn = [];
		
		if (empty($columns['align'])) {
			return $alignColumn;
		}
		
		foreach ($columns['align'] as $align => $column_data) {
			if ($column_data['header'] !== true) {
				continue;
			}
			
			foreach ($column_data['columns'] as $field) {
				$alignColumn['header'][$field] = $align;
			}
		}
		
		return $alignColumn;
	}
	
	/**
	 * Extract merge column configuration
	 * 
	 * @param array $columns Column configuration
	 * @return array|null Merge configuration
	 */
	private function extractMergeConfig(array $columns): ?array {
		if (empty($columns['merge'])) {
			return null;
		}
		
		$mergeColumn = $columns['merge'];
		
		// Manipulation Column Merged Label
		if (!empty($this->labels)) {
			$merged_labels = [];
			foreach ($mergeColumn as $colmergename => $colmerged) {
				$merged_labels[$colmergename]['position'] = $colmerged['position'];
				$merged_labels[$colmergename]['counts'] = $colmerged['counts'];
				$merged_labels[$colmergename]['columns'] = $this->checkColumnLabel($this->labels, $colmerged['columns']);
			}
			if (!empty($merged_labels)) {
				$mergeColumn = $merged_labels;
			}
		}
		
		return $mergeColumn;
	}
	
	/**
	 * Add special columns (numbering, actions) to column list
	 * 
	 * @param array $columns Column list
	 * @param array $attributes Table attributes
	 * @return array Modified column list
	 */
	private function addSpecialColumns(array $columns, array $attributes): array {
		$numbering = $attributes['numbering'] ?? false;
		$actions = $attributes['actions'] ?? false;
		
		if (true === $numbering && !in_array(self::COLUMN_ID, $columns)) {
			$columns = array_merge([self::COLUMN_NUMBER_LISTS], $columns);
		}
		
		if (!empty($actions)) {
			$columns[] = 'action';
		}
		
		return $columns;
	}
	
	/**
	 * Build merged header (with merge columns)
	 * 
	 * @param array $config Header configuration
	 * @return string HTML for merged header
	 */
	private function buildMergedHeader(array $config): string {
		// Merge alignment classes if needed
		if (!empty($config['alignColumn']['header'])) {
			$config['attributes']['attributes']['column']['class'] = array_merge_recursive(
				$config['attributes']['attributes']['column']['class'],
				$config['alignColumn']['header']
			);
		}
		
		$headerContent = $this->mergeColumns(
			$config['mergeColumn'],
			$config['columnsList'],
			$config['attributes']
		);
		
		return '<thead>' . $headerContent . '</thead>';
	}
	
	/**
	 * Build standard header (no merge columns)
	 * 
	 * @param array $config Header configuration
	 * @return string HTML for standard header
	 */
	private function buildStandardHeader(array $config): string {
		/**
		 * @performance Use array collection + implode() instead of repeated .= in loop
		 * to avoid repeated string reallocation on each append.
		 */
		$headerParts = [];
		
		foreach ($config['columnsList'] as $column) {
			$headerParts[] = $this->renderStandardColumnHeader($column, $config);
		}
		
		return '<thead><tr>' . implode('', $headerParts) . '</tr></thead>';
	}
	
	/**
	 * Render a single standard column header
	 * 
	 * @param string $column Column name
	 * @param array $config Header configuration
	 * @return string HTML for column header
	 * 
	 * @accessibility Adds keyboard navigation attributes for sortable columns (Task 4.3.1, 4.3.3)
	 */
	private function renderStandardColumnHeader(string $column, array $config): string {
		// PERFORMANCE: Use cached column label
		$headerLabel = $this->getColumnLabel($column);
		$columnLower = strtolower($column);
		
		// Build column ID
		$id = '';
		if (!empty($config['dataColumns'])) {
			$columnKey = $config['dataColumns'][$column] ?? $column;
			$id = $this->setAttributes(['id' => canvastack_decrypt(canvastack_encrypt($columnKey))]);
		} else {
			$id = $this->setAttributes(['id' => canvastack_decrypt(canvastack_encrypt($column))]);
		}
		
		// Build ARIA attributes
		$ariaAttrs = $this->buildHeaderAriaAttributes($column, $config);
		
		// Task 4.4.2: Add scope="col" for header association (Requirement 13.2)
		$scopeAttr = ' scope="col"';
		
		// Build keyboard navigation attributes for sortable columns (Task 4.3.1, 4.3.3)
		$keyboardAttrs = '';
		$tooltip = '';
		$focusClass = '';
		$isSortable = !empty($config['sortable']) && (is_array($config['sortable']) ? in_array($column, $config['sortable']) : $config['sortable']);
		if ($isSortable) {
			$kbAttrs = $this->buildKeyboardAttributes('header', ['sortable' => true]);
			$keyboardAttrs = $this->setAttributes($kbAttrs);
			// Add tooltip for keyboard sorting (Task 4.3.3)
			$tooltip = ' title="Click or press Enter/Space to sort"';
			// Add focus indicator class (Task 4.3.5)
			$focusClass = ' class="canvastack-keyboard-focus"';
		}
		
		// Special columns
		if (in_array($columnLower, [self::COLUMN_NO, self::COLUMN_ID, self::COLUMN_NIK])) {
			return "<th width=\"" . self::COLUMN_WIDTH_MEDIUM . "\"{$scopeAttr}{$ariaAttrs}{$keyboardAttrs}{$tooltip}{$focusClass}{$config['headerColor']}>{$headerLabel}</th>";
		}
		
		if (self::COLUMN_NUMBER_LISTS === $columnLower) {
			return '<th width="' . self::COLUMN_WIDTH_SMALL . '"' . $scopeAttr . $ariaAttrs . $keyboardAttrs . $tooltip . $focusClass . $config['headerColor'] . '>No</th>' .
			       '<th width="' . self::COLUMN_WIDTH_SMALL . '"' . $scopeAttr . $ariaAttrs . $keyboardAttrs . $tooltip . $focusClass . $config['headerColor'] . '>ID</th>';
		}
		
		// Standard column
		$class = $this->buildStandardColumnClass($column, $config);
		$width = $this->getColumnWidthFromConfig($column, $config['widthColumn']);
		$colorStyle = $this->getColumnColorStyle($column, $config['columnColor']);
		
		// Merge focus class with existing class if sortable
		if ($isSortable && !empty($class)) {
			$class = str_replace(' class="', ' class="canvastack-keyboard-focus ', $class);
		} elseif ($isSortable) {
			$class = $focusClass;
		}
		
		return "<th{$id}{$class}{$scopeAttr}{$ariaAttrs}{$keyboardAttrs}{$tooltip}{$config['headerColor']}{$colorStyle}{$width}>{$headerLabel}</th>";
	}
	
	/**
	 * Build class attribute for standard column header
	 * 
	 * @param string $column Column name
	 * @param array $config Header configuration
	 * @return string Class attribute string
	 */
	private function buildStandardColumnClass(string $column, array $config): string {
		$classAttributes = '';
		
		if (in_array($column, $config['hiddenColumn'])) {
			$classAttributes .= ' canvastack-hide-column';
		}
		
		if (!empty($config['alignColumn']['header'][$column])) {
			$classAttributes .= $config['alignColumn']['header'][$column];
		}
		
		if (self::COLUMN_ACTION === strtolower($column)) {
			$classAttributes .= ' canvastack-column-action';
		}
		
		if (!empty($classAttributes)) {
			return $this->setAttributes(['class' => $classAttributes]);
		}
		
		return '';
	}
	
	/**
	 * Build ARIA attributes for table header cell
	 * 
	 * Generates ARIA role and other accessibility attributes for table header cells.
	 * 
	 * @param string $column Column name
	 * @param array $config Header configuration
	 * @return string ARIA attributes string
	 */
	private function buildHeaderAriaAttributes(string $column, array $config): string {
		// Check if ARIA is enabled globally
		if (!config('canvastack.datatables.accessibility.aria_enabled', true)) {
			return '';
		}
		
		$ariaAttributes = [];
		
		// Add role="columnheader"
		$ariaAttributes[TableConstants::ATTR_ROLE] = TableConstants::ROLE_COLUMNHEADER;
		
		// Add aria-sort if column is sortable and config enabled
		$isSortable = !empty($config['sortable']) && (is_array($config['sortable']) ? in_array($column, $config['sortable']) : $config['sortable']);
		if ($isSortable && config('canvastack.datatables.accessibility.add_aria_sort', true)) {
			$ariaAttributes[TableConstants::ATTR_ARIA_SORT] = 'none';
		}
		
		// Add aria-label for columns if config enabled
		if (config('canvastack.datatables.accessibility.add_aria_labels', true)) {
			$columnLabel = $this->getColumnLabel($column);
			
			// Add sortable indicator to label
			if ($isSortable) {
				$ariaAttributes[TableConstants::ATTR_ARIA_LABEL] = $columnLabel . ' (sortable)';
			} else {
				$ariaAttributes[TableConstants::ATTR_ARIA_LABEL] = $columnLabel;
			}
			
			// Add filterable indicator if applicable
			if (!empty($config['attributes']['searchable']) && in_array($column, $config['attributes']['searchable'])) {
				$ariaAttributes[TableConstants::ATTR_ARIA_LABEL] .= ', filterable';
			}
		}
		
		return $this->setAttributes($ariaAttributes);
	}
	
	/**
	 * Build keyboard navigation attributes for table elements
	 * 
	 * Adds tabindex and keyboard event handler attributes to make table
	 * elements keyboard accessible per WCAG 2.1 Level A requirements.
	 * 
	 * @param string $elementType Type of element ('header', 'button', 'pagination', 'filter')
	 * @param array $config Additional configuration
	 * @return array Keyboard attributes
	 * 
	 * @accessibility Implements Requirement 12.2 - proper tab order for interactive elements
	 *                Implements Task 4.3.1 - ensure proper tab order
	 *                Implements Task 4.3.2 - add keyboard shortcuts
	 */
	private function buildKeyboardAttributes(string $elementType, array $config = []): array {
		// Check if keyboard navigation is enabled
		if (!config('canvastack.datatables.accessibility.keyboard_navigation', true)) {
			return [];
		}
		
		$attributes = [];
		
		switch ($elementType) {
			case 'header':
				// Sortable column headers should be keyboard accessible
				if (!empty($config['sortable'])) {
					$attributes['tabindex'] = '0';
					$attributes['role'] = 'button';
					$attributes['aria-keyshortcuts'] = 'Enter Space';
				}
				break;
				
			case 'button':
			case 'action':
				// Action buttons should be keyboard accessible
				$attributes['tabindex'] = '0';
				break;
				
			case 'pagination':
				// Pagination controls should be keyboard accessible
				$attributes['tabindex'] = '0';
				$attributes['aria-keyshortcuts'] = 'ArrowLeft ArrowRight';
				break;
				
			case 'filter':
				// Filter button should be keyboard accessible
				$attributes['tabindex'] = '0';
				break;
		}
		
		return $attributes;
	}
	
	/**
	 * Build focus indicator CSS class
	 * 
	 * Returns CSS class for visible focus indicators on keyboard-focusable elements.
	 * 
	 * @return string CSS class for focus indicators
	 * 
	 * @accessibility Implements Requirement 12.7 - visible focus indicators
	 *                Implements Task 4.3.5 - add visible focus indicators
	 */
	private function getFocusIndicatorClass(): string {
		// Check if focus indicators are enabled
		if (!config('canvastack.datatables.accessibility.focus_indicators', true)) {
			return '';
		}
		
		return 'canvastack-keyboard-focus';
	}
	
	/**
	 * Get column width from configuration
	 * 
	 * @param string $column Column name
	 * @param array $widthColumn Width configuration
	 * @return string Width attribute string
	 */
	private function getColumnWidthFromConfig(string $column, array $widthColumn): string {
		$columnLower = strtolower($column);
		
		if (!empty($widthColumn[$columnLower])) {
			// SECURITY: Validate width value
			$validatedWidth = $this->validateWidth($widthColumn[$columnLower]);
			if ($validatedWidth !== null) {
				return ' width="' . $validatedWidth . '"';
			}
		}
		
		return '';
	}
	
	/**
	 * Build merged column headers for complex table layouts
	 * Refactored to reduce nesting from 6 to 2 levels
	 * 
	 * @param array $mergeColumn Merge configuration
	 * @param array $columns Column list
	 * @param array $attributes Table attributes
	 * @return string HTML for merged headers
	 */
	private function mergeColumns(array $mergeColumn = [], array $columns = [], array $attributes = []): string {
		if (empty($mergeColumn)) {
			return '';
		}
		
		$columns = $this->checkColumnLabel($this->labels, $columns);
		$dataColumns = $this->columnManipulated;
		
		[$columnColor, $headerColor] = $this->extractColorSettings($attributes);
		
		$mergedTable = $this->buildMergedTableRow($columns, $mergeColumn, $dataColumns, $columnColor, $headerColor, $attributes);
		$headerTable = $this->buildHeaderTableRow($columns, $dataColumns, $columnColor, $headerColor, $attributes);
		
		return $headerTable . $mergedTable;
	}
	
	/**
	 * Extract color settings from attributes
	 * 
	 * @param array $attributes Table attributes
	 * @return array [columnColor, headerColor]
	 */
	private function extractColorSettings(array $attributes): array {
		$columnColor = [];
		$headerColor = null;
		
		if (!empty($attributes['attributes']['bg_color'])) {
			$tableColor = $this->backgroundColor($attributes['attributes']['bg_color']);
			$columnColor = $tableColor['columns'] ?? [];
			$headerColor = $tableColor['header'] ?? null;
		}
		
		return [$columnColor, $headerColor];
	}
	
	/**
	 * Build the merged table row (bottom row with actual column headers)
	 * 
	 * @param array $columns Column list (modified by reference)
	 * @param array $mergeColumn Merge configuration
	 * @param array $dataColumns Column manipulation data
	 * @param array $columnColor Column color settings
	 * @param string|null $headerColor Header color style
	 * @param array $attributes Table attributes
	 * @return string HTML for merged row
	 */
	private function buildMergedTableRow(array &$columns, array $mergeColumn, array $dataColumns, array $columnColor, ?string $headerColor, array $attributes): string {
		$setMergeText = self::MERGE_TEXT_SEPARATOR;
		
		/**
		 * @performance Use array collection + implode() instead of repeated .= in loop
		 * to avoid repeated string reallocation on each append.
		 */
		$mergedParts = [];
		
		foreach ($columns as $index => $column) {
			$matchedMerge = $this->findMatchingMergeColumn($column, $mergeColumn);
			
			if ($matchedMerge) {
				$mergedParts[] = $this->renderMergedColumnHeader(
					$column,
					$matchedMerge,
					$dataColumns,
					$columnColor,
					$headerColor,
					$attributes
				);
				
				unset($columns[$index]);
				$columns[$index] = $matchedMerge['label'] . $setMergeText . $matchedMerge['counts'];
			}
		}
		
		return '<tr>' . implode('', $mergedParts) . '</tr>';
	}
	
	/**
	 * Find matching merge column configuration for a given column
	 * 
	 * @param string $column Column name to match
	 * @param array $mergeColumn Merge configuration
	 * @return array|null Matched merge data or null
	 */
	private function findMatchingMergeColumn(string $column, array $mergeColumn): ?array {
		foreach ($mergeColumn as $mergeLabel => $mergeData) {
			if (in_array($column, $mergeData['columns'])) {
				return [
					'label' => $mergeLabel,
					'counts' => $mergeData['counts'],
					'data' => $mergeData
				];
			}
		}
		return null;
	}
	
	/**
	 * Render a single merged column header with all attributes
	 * 
	 * @param string $column Column name
	 * @param array $mergeData Merge configuration data
	 * @param array $dataColumns Column manipulation data
	 * @param array $columnColor Column color settings
	 * @param string|null $headerColor Header color style
	 * @param array $attributes Table attributes
	 * @return string HTML for column header
	 */
	private function renderMergedColumnHeader(string $column, array $mergeData, array $dataColumns, array $columnColor, ?string $headerColor, array $attributes): string {
		// PERFORMANCE: Use cached column label
		$headerLabel = $this->getColumnLabel($column);
		$id = $this->buildColumnId($column, $dataColumns);
		$columnClass = $this->buildMergedColumnClass($column, $attributes);
		$colorStyle = $this->getColumnColorStyle($column, $columnColor);
		
		// Build ARIA attributes - create a minimal config for the helper
		$config = ['sortable' => $attributes['sortable_columns'] ?? false, 'attributes' => $attributes];
		$ariaAttrs = $this->buildHeaderAriaAttributes($column, $config);
		
		// Task 4.4.2: Add scope="col" for header association (Requirement 13.2)
		$scopeAttr = ' scope="col"';
		
		return "<th{$id}{$columnClass}{$scopeAttr}{$ariaAttrs}{$headerColor}{$colorStyle}>{$headerLabel}</th>";
	}
	
	/**
	 * Build column ID attribute
	 * 
	 * @param string $column Column name
	 * @param array $dataColumns Column manipulation data
	 * @return string ID attribute string
	 */
	private function buildColumnId(string $column, array $dataColumns): string {
		if (!empty($dataColumns) && isset($dataColumns[$column])) {
			return $this->setAttributes(['id' => canvastack_decrypt(canvastack_encrypt($dataColumns[$column]))]);
		}
		return '';
	}
	
	/**
	 * Build column class attribute for merged columns
	 * 
	 * @param string $column Column name
	 * @param array $attributes Table attributes
	 * @return string Class attribute string
	 */
	private function buildMergedColumnClass(string $column, array $attributes): string {
		if (!empty($attributes['attributes']['column']['class'][$column])) {
			return $this->setAttributes(['class' => $attributes['attributes']['column']['class'][$column]]);
		}
		return '';
	}
	
	/**
	 * Get column color style attribute
	 * 
	 * @param string $column Column name
	 * @param array $columnColor Column color settings
	 * @return string Color style attribute
	 */
	private function getColumnColorStyle(string $column, array $columnColor): string {
		return !empty($columnColor[$column]) ? $columnColor[$column] : '';
	}
	
	/**
	 * Build the header table row (top row with merge labels and rowspan columns)
	 * 
	 * @param array $columns Column list (already modified with merge markers)
	 * @param array $dataColumns Column manipulation data
	 * @param array $columnColor Column color settings
	 * @param string|null $headerColor Header color style
	 * @param array $attributes Table attributes
	 * @return string HTML for header row
	 */
	private function buildHeaderTableRow(array $columns, array $dataColumns, array $columnColor, ?string $headerColor, array $attributes): string {
		$columns = array_unique($columns);
		ksort($columns);
		
		$setMergeText = self::MERGE_TEXT_SEPARATOR;
		
		/**
		 * @performance Use array collection + implode() instead of repeated .= in loop
		 * to avoid repeated string reallocation on each append.
		 */
		$headerParts = [];
		
		foreach ($columns as $index => $column) {
			if (str_contains($column, $setMergeText)) {
				$headerParts[] = $this->renderMergeLabel($column, $headerColor);
			} else {
				$headerParts[] = $this->renderRowspanColumn($column, $dataColumns, $columnColor, $headerColor, $attributes);
			}
		}
		
		return '<tr>' . implode('', $headerParts) . '</tr>';
	}
	
	/**
	 * Render merge label header (colspan header)
	 * 
	 * @param string $column Column with merge marker
	 * @param string|null $headerColor Header color style
	 * @return string HTML for merge label
	 */
	private function renderMergeLabel(string $column, ?string $headerColor): string {
		$setMergeText = self::MERGE_TEXT_SEPARATOR;
		$merge_label = explode($setMergeText, $column);
		$colspan = intval($merge_label[1]);
		// PERFORMANCE: Use cached column label
		$headerLabel = $this->getColumnLabel($merge_label[0]);
		
		// Add ARIA role for merge label headers
		$roleAttr = $this->setAttributes([TableConstants::ATTR_ROLE => TableConstants::ROLE_COLUMNHEADER]);
		
		// Task 4.4.2: Add scope="col" for header association (Requirement 13.2)
		$scopeAttr = ' scope="col"';
		
		return "<th class=\"merge-column\" colspan=\"{$colspan}\"{$scopeAttr}{$roleAttr}{$headerColor}>{$headerLabel}</th>";
	}
	
	/**
	 * Render rowspan column header (columns that span both rows)
	 * 
	 * @param string $column Column name
	 * @param array $dataColumns Column manipulation data
	 * @param array $columnColor Column color settings
	 * @param string|null $headerColor Header color style
	 * @param array $attributes Table attributes
	 * @return string HTML for rowspan column
	 */
	private function renderRowspanColumn(string $column, array $dataColumns, array $columnColor, ?string $headerColor, array $attributes): string {
		// PERFORMANCE: Use cached column label
		$headerLabel = $this->getColumnLabel($column);
		$id = $this->buildColumnId($column, $dataColumns);
		
		$columnLower = strtolower($column);
		
		// Build ARIA role attribute
		$roleAttr = $this->setAttributes([TableConstants::ATTR_ROLE => TableConstants::ROLE_COLUMNHEADER]);
		
		// Task 4.4.2: Add scope="col" for header association (Requirement 13.2)
		$scopeAttr = ' scope="col"';
		
		// Special columns: no, id, nik
		if (in_array($columnLower, [self::COLUMN_NO, self::COLUMN_ID, self::COLUMN_NIK])) {
			return "<th rowspan=\"2\" width=\"" . self::COLUMN_WIDTH_MEDIUM . "\"{$scopeAttr}{$roleAttr}{$headerColor}>{$headerLabel}</th>";
		}
		
		// Special column: number_lists
		if (self::COLUMN_NUMBER_LISTS === $columnLower) {
			return "<th rowspan=\"2\" width=\"" . self::COLUMN_WIDTH_SMALL . "\"{$scopeAttr}{$roleAttr}{$headerColor}>No</th><th rowspan=\"2\" width=\"" . self::COLUMN_WIDTH_SMALL . "\"{$scopeAttr}{$roleAttr}{$headerColor}>ID</th>";
		}
		
		// Standard columns
		return $this->renderStandardRowspanColumn($column, $id, $headerLabel, $columnColor, $headerColor, $attributes);
	}
	
	/**
	 * Render standard rowspan column with all attributes
	 * 
	 * @param string $column Column name
	 * @param string $id ID attribute
	 * @param string $headerLabel Escaped header label
	 * @param array $columnColor Column color settings
	 * @param string|null $headerColor Header color style
	 * @param array $attributes Table attributes
	 * @return string HTML for standard rowspan column
	 */
	private function renderStandardRowspanColumn(string $column, string $id, string $headerLabel, array $columnColor, ?string $headerColor, array $attributes): string {
		$columnClass = $this->buildRowspanColumnClass($column, $attributes);
		$width = $this->getColumnWidth($column, $attributes);
		$colorStyle = $this->getColumnColorStyle($column, $columnColor);
		
		// Build ARIA role attribute
		$roleAttr = $this->setAttributes([TableConstants::ATTR_ROLE => TableConstants::ROLE_COLUMNHEADER]);
		
		// Task 4.4.2: Add scope="col" for header association (Requirement 13.2)
		$scopeAttr = ' scope="col"';
		
		return "<th rowspan=\"2\"{$id}{$columnClass}{$scopeAttr}{$roleAttr}{$headerColor}{$colorStyle}{$width}>{$headerLabel}</th>";
	}
	
	/**
	 * Build column class for rowspan columns
	 * 
	 * @param string $column Column name
	 * @param array $attributes Table attributes
	 * @return string Class attribute string
	 */
	private function buildRowspanColumnClass(string $column, array $attributes): string {
		$classAttributes = '';
		
		if (!empty($attributes['attributes']['column']['class'][$column])) {
			$classAttributes .= $attributes['attributes']['column']['class'][$column];
		}
		
		if (self::COLUMN_ACTION === strtolower($column)) {
			$classAttributes .= ' canvastack-column-action';
		}
		
		if (!empty($classAttributes)) {
			return $this->setAttributes(['class' => $classAttributes]);
		}
		
		return '';
	}
	
	/**
	 * Get column width attribute
	 * 
	 * @param string $column Column name
	 * @param array $attributes Table attributes
	 * @return string Width attribute string
	 */
	private function getColumnWidth(string $column, array $attributes): string {
		$columnLower = strtolower($column);
		
		if (!empty($attributes['attributes']['column_width'][$columnLower])) {
			// SECURITY: Validate width value
			$validatedWidth = $this->validateWidth($attributes['attributes']['column_width'][$columnLower]);
			if ($validatedWidth !== null) {
				return ' width="' . $validatedWidth . '"';
			}
		}
		
		return '';
	}
	
	/**
	 * Set column elements (sortable, searchable, clickable)
	 * 
	 * @param string $name Element name (sortable, searchable, clickable)
	 * @param array $column_data Column data configuration
	 * @param array $columns Column list
	 * @return array Element configuration
	 */
	private function setColumnElements(string $name, array $column_data, array $columns): array {
		$element = [];
		if (!empty($column_data[$name])) {
			if (!empty($column_data[$name]['all::columns'])) {
				if (true === $column_data[$name]['all::columns']) {
					if (!empty($columns['columns']['lists'])) {
						foreach ($columns['columns']['lists'] as $clickList) {
							$element[$clickList] = true;
						}
					}
				}
			} else {
				foreach ($column_data[$name] as $clicKey) {
					$element[$clicKey] = true;
				}
			}
		}
		
		return $element;
	}
	
	/**
	 * Set formula columns
	 * 
	 * @param array $columns Column list
	 * @param array $data Formula data
	 * @return array Modified columns with formula
	 */
	private function setFormulaColumns(array $columns, array $data): array {
		return canvastack_set_formula_columns($columns, $data['attributes']['conditions']['formula']);
	}
	
	public $filter_contents  = [];
	protected $filter_object = [];
	/**
	 * Build table body configuration and DataTables script
	 * Refactored to reduce nesting from 5 to 2 levels
	 * 
	 * @param array $data Table data configuration
	 * @return string DataTables JavaScript
	 */
	private function body(array $data = []): string {
		$config = $this->prepareBodyConfig($data);
		$columns = $this->prepareBodyColumns($config);
		
		// Build DataTables column configuration
		$dt_columns = $this->buildDataTableColumns($columns, $config);
		$dt_info = $this->buildDataTableInfo($data, $dt_columns, $config);
		
		// Add filter configuration if searchable
		if ($config['hasSearchable']) {
			$dt_info = $this->addFilterConfiguration($dt_info, $data, $config['tableID']);
		}
		
		$this->filter_contents[$config['tableID']] = $dt_info;
		
		return $this->renderDataTable($config['tableID'], $dt_columns, $dt_info, $config['hasSearchable']);
	}
	
	/**
	 * Prepare body configuration from data
	 * 
	 * @param array $data Table data
	 * @return array Configuration array
	 */
	private function prepareBodyConfig(array $data): array {
		$attributes = $data['attributes'];
		$columnData = $data['columns'];
		
		return [
			'name' => $data['name'],
			'attributes' => $attributes,
			'columnData' => $columnData,
			'server_side' => $attributes['server_side']['status'],
			'tableID' => $attributes['attributes']['table_id'] ?? 'datatable',
			'actions' => $attributes['actions'] ?? false,
			'numbering' => $attributes['numbering'] ?? false,
			'hiddenColumn' => $columnData['hidden_columns'] ?? [],
			'hasSearchable' => !empty($columnData['searchable'])
		];
	}
	
	/**
	 * Prepare columns list with special columns
	 * 
	 * @param array $config Body configuration
	 * @return array Column list
	 */
	private function prepareBodyColumns(array $config): array {
		$columns = $config['columnData']['lists'];
		
		if (true === $config['numbering']) {
			$columns = array_merge([self::COLUMN_NUMBER_LISTS], $columns);
		}
		
		if (!empty($config['actions'])) {
			$columns[] = 'action';
		}
		
		return $columns;
	}
	
	/**
	 * Build DataTables column configuration array
	 * 
	 * @param array $columns Column list
	 * @param array $config Body configuration
	 * @return array DataTables columns configuration
	 */
	private function buildDataTableColumns(array $columns, array $config): array {
		$alignment = $this->extractBodyAlignment($config['columnData']);
		$sortable = $this->setColumnElements('sortable', $config['columnData'], ['columns' => $config['columnData']]);
		$searchable = $this->setColumnElements('searchable', $config['columnData'], ['columns' => $config['columnData']]);
		$clickable = $this->setColumnElements('clickable', $config['columnData'], ['columns' => $config['columnData']]);
		
		$column_id = $this->prepareColumnId($config['server_side'], $columns);
		$formula_fields = $this->extractFormulaFields($config);
		
		$dt_columns = [];
		
		foreach ($columns as $column) {
			$columnConfig = $this->buildSingleColumnConfig(
				$column,
				$config,
				$alignment,
				$sortable,
				$searchable,
				$clickable,
				$formula_fields,
				$column_id
			);
			
			if (!empty($columnConfig)) {
				$dt_columns = array_merge($dt_columns, $columnConfig);
			}
		}
		
		return $dt_columns;
	}
	
	/**
	 * Extract body alignment configuration
	 * 
	 * @param array $columnData Column data
	 * @return array Alignment configuration
	 */
	private function extractBodyAlignment(array $columnData): array {
		$alignment = [];
		
		if (empty($columnData['align'])) {
			return $alignment;
		}
		
		foreach ($columnData['align'] as $align => $col_data) {
			if ($col_data['body'] !== true) {
				continue;
			}
			
			foreach ($col_data['columns'] as $field) {
				$alignment['body'][$field] = $align;
			}
		}
		
		return $alignment;
	}
	
	/**
	 * Prepare column ID configuration for server-side processing
	 * 
	 * @param bool $server_side Server-side status
	 * @param array $columns Column list
	 * @return array Column ID configuration
	 */
	private function prepareColumnId(bool $server_side, array $columns): array {
		if (false === $server_side) {
			return [];
		}
		
		// The ID column is hidden but required for row identification and click actions
		// Don't try to find it in the columns array because it might have been replaced with 'number_lists'
		// Detect ID field: use 'id' if exists, otherwise use first actual column
		// Note: columns[0] might be 'number_lists', so we check for 'id' first
		$firstField = 'id';
		if (!in_array('id', $columns)) {
			// Find first non-special column (skip number_lists if present)
			$firstField = $columns[0] === self::COLUMN_NUMBER_LISTS ? ($columns[1] ?? 'id') : ($columns[0] ?? 'id');
		}
		
		// Return hidden ID column configuration for row identification
		$result = [
			'data' => $firstField,
			'name' => $firstField,
			'sortable' => false,
			'searchable' => false,
			'class' => 'control hidden-column',
			'visible' => false
		];
		
		return $result;
	}
	
	/**
	 * Extract formula fields from configuration
	 * 
	 * @param array $config Body configuration
	 * @return array Formula fields
	 */
	private function extractFormulaFields(array $config): array {
		$formula_fields = [];
		
		if (empty($config['attributes']['conditions']['formula'])) {
			return $formula_fields;
		}
		
		foreach ($config['attributes']['conditions']['formula'] as $formula) {
			$formula_fields[$formula['name']] = $formula['name'];
		}
		
		return $formula_fields;
	}
	
	/**
	 * Build configuration for a single column
	 * 
	 * @param string $column Column name
	 * @param array $config Body configuration
	 * @param array $alignment Alignment settings
	 * @param array $sortable Sortable settings
	 * @param array $searchable Searchable settings
	 * @param array $clickable Clickable settings
	 * @param array $formula_fields Formula fields
	 * @param array $column_id Column ID config
	 * @return array Column configuration(s)
	 */
	private function buildSingleColumnConfig(string $column, array $config, array $alignment, array $sortable, array $searchable, array $clickable, array $formula_fields, array $column_id): array {
		$jsonData = [
			'data' => $column,
			'name' => $column,
			'sortable' => false,
			'searchable' => false,
			'class' => 'auto-cut-text',
			'onclick' => 'return false'
		];
		
		// Apply hidden column class
		if (in_array($column, $config['hiddenColumn'])) {
			$jsonData['class'] = 'auto-cut-text canvastack-hide-column';
		}
		
		// Handle special column types
		if (self::COLUMN_NUMBER_LISTS === $column) {
			return $this->buildNumberListsColumn($column_id);
		}
		
		if (isset($formula_fields[$column])) {
			return [$this->buildFormulaColumn($column, $jsonData, $alignment, $clickable)];
		}
		
		return [$this->buildStandardBodyColumn($column, $jsonData, $alignment, $sortable, $searchable, $clickable)];
	}
	
	/**
	 * Build number lists column configuration
	 * 
	 * @param array $column_id Column ID config
	 * @return array Column configurations
	 */
	private function buildNumberListsColumn(array $column_id): array {
		$numberColumn = [
			'data' => 'DT_RowIndex',
			'name' => 'DT_RowIndex',
			'sortable' => false,
			'searchable' => false,
			'class' => 'center un-clickable',
			'onclick' => 'return false'
		];
		
		$result = [$numberColumn];
		
		if (!empty($column_id)) {
			$result[] = $column_id;
		}
		
		return $result;
	}
	
	/**
	 * Build formula column configuration
	 * 
	 * @param string $column Column name
	 * @param array $jsonData Base column data
	 * @param array $alignment Alignment settings
	 * @param array $clickable Clickable settings
	 * @return array Column configuration
	 */
	private function buildFormulaColumn(string $column, array $jsonData, array $alignment, array $clickable): array {
		if (!empty($alignment['body'][$column])) {
			$jsonData['class'] .= " {$alignment['body'][$column]}";
		}
		
		if (!empty($clickable[$column])) {
			unset($jsonData['onclick']);
			$jsonData['class'] .= " clickable";
		}
		
		return $jsonData;
	}
	
	/**
	 * Build standard body column configuration
	 * 
	 * @param string $column Column name
	 * @param array $jsonData Base column data
	 * @param array $alignment Alignment settings
	 * @param array $sortable Sortable settings
	 * @param array $searchable Searchable settings
	 * @param array $clickable Clickable settings
	 * @return array Column configuration
	 */
	private function buildStandardBodyColumn(string $column, array $jsonData, array $alignment, array $sortable, array $searchable, array $clickable): array {
		if (!empty($alignment['body'][$column])) {
			$jsonData['class'] .= " {$alignment['body'][$column]}";
		}
		
		if (!empty($sortable[$column])) {
			$jsonData['sortable'] = $sortable[$column];
		}
		
		if (!empty($searchable[$column])) {
			$jsonData['searchable'] = $searchable[$column];
		}
		
		if (!empty($clickable[$column])) {
			unset($jsonData['onclick']);
			$jsonData['class'] .= " clickable";
		}
		
		return $jsonData;
	}
	
	/**
	 * Build DataTables info configuration
	 * 
	 * @param array $data Table data
	 * @param array $dt_columns DataTables columns
	 * @param array $config Body configuration
	 * @return array DataTables info
	 */
	private function buildDataTableInfo(array $data, array $dt_columns, array $config): array {
		$new_data_columns = [];
		foreach ($dt_columns as $dtcols) {
			$new_data_columns[] = ($dtcols['name'] === 'DT_RowIndex') ? self::COLUMN_NUMBER_LISTS : $dtcols['name'];
		}
		
		$dt_info = [
			'searchable' => [],
			'name' => $config['name']
		];
		
		if (!empty($data['columns']['sortable'])) {
			$dt_info['sortable'] = $data['columns']['sortable'];
		}
		
		if (!empty($data['attributes']['conditions'])) {
			$dt_info['conditions'] = $data['attributes']['conditions'];
			$dt_info['columns'] = $new_data_columns;
		}
		
		if (!empty($data['attributes']['on_load']['display_limit_rows'])) {
			$dt_info['onload_limit_rows'] = $data['attributes']['on_load']['display_limit_rows'];
		}
		
		if (!empty($data['attributes']['fixed_columns'])) {
			$dt_info['fixed_columns'] = $data['attributes']['fixed_columns'];
		}
		
		return $dt_info;
	}
	
	/**
	 * Add filter configuration to DataTables info
	 * 
	 * @param array $dt_info DataTables info
	 * @param array $data Table data
	 * @param string $tableID Table ID
	 * @return array Updated DataTables info
	 */
	private function addFilterConfiguration(array $dt_info, array $data, string $tableID): array {
		$dt_info['searchable'] = $data['columns']['searchable'];
		
		if (empty($data['columns']['filters'])) {
			return $dt_info;
		}
		
		$search_data = $this->buildSearchData($data);
		[$data_model, $data_sql] = $this->extractModelOrSql($data);
		$filterQuery = $this->conditions['where'] ?? [];
		
		$searchInfoAttribute = "{$tableID}_CanvaStackFILTER";
		$search_object = new Search(
			$searchInfoAttribute,
			$data_model,
			$search_data,
			$data_sql,
			$this->connection,
			$filterQuery
		);
		
		// Store search object in array to support multiple tabs
		$this->filter_object[] = $search_object;
		
		return $this->addFilterInfo($dt_info, $tableID, $searchInfoAttribute, $search_object, $data);
	}
	
	/**
	 * Build search data configuration
	 * 
	 * @param array $data Table data
	 * @return array Search data configuration
	 */
	private function buildSearchData(array $data): array {
		// Determine which columns to use for filtering
		$filterColumns = [];
		if (is_array($data['columns']['filters'])) {
			// Explicit filters defined
			$filterColumns = $data['columns']['filters'];
		} elseif (!empty($data['columns']['filter_groups'])) {
			// Extract columns from filter_groups configuration
			foreach ($data['columns']['filter_groups'] as $filterGroup) {
				if (isset($filterGroup['column'])) {
					$filterColumns[] = $filterGroup['column'];
				}
			}
		} elseif (is_array($data['columns']['searchable']) && !empty($data['columns']['searchable'])) {
			// Fall back to searchable columns
			$filterColumns = array_keys($data['columns']['searchable']);
		} elseif (isset($data['columns']['lists'])) {
			// Last resort: use lists columns
			$filterColumns = $data['columns']['lists'];
		}
		
		$search_data = [
			'table_name' => $data['name'],
			'searchable' => $data['columns']['searchable'],
			'columns' => $filterColumns,
			'relations' => $data['columns']['relations'] ?? [],
			'foreign_keys' => $data['columns']['foreign_keys'] ?? []
		];
		
		if (!empty($data['columns']['filter_groups'])) {
			$search_data['filter_groups'] = $data['columns']['filter_groups'];
		}
		
		if (!empty($data['attributes']['filter_model'])) {
			$search_data['filter_model'] = $data['attributes']['filter_model'];
		}
		
		return $search_data;
	}
	
	/**
	 * Extract model or SQL from data
	 * 
	 * @param array $data Table data
	 * @return array [model, sql]
	 */
	private function extractModelOrSql(array $data): array {
		if (!empty($data['sql'])) {
			return [null, $data['sql']];
		}
		return [$data['model'], null];
	}
	
	/**
	 * Add filter info to DataTables configuration
	 * 
	 * @param array $dt_info DataTables info
	 * @param string $tableID Table ID
	 * @param string $searchInfoAttribute Search attribute ID
	 * @param object $search_object Search object instance
	 * @param array $data Table data
	 * @return array Updated DataTables info
	 */
	private function addFilterInfo(array $dt_info, string $tableID, string $searchInfoAttribute, object $search_object, array $data): array {
		$dt_info['id'] = $tableID;
		$dt_info['class'] = 'dt-button buttons-filter';
		$dt_info['attributes'] = [
			'id' => $searchInfoAttribute,
			'class' => "modal fade {$tableID}",
			'role' => 'dialog',
			'tabindex' => '-1',
			'aria-hidden' => 'true',
			'aria-controls' => $tableID,
			'aria-labelledby' => $tableID,
			'data-backdrop' => 'static',
			'data-keyboard' => 'true'
		];
		$dt_info['button_label'] = '<i class="fa fa-filter"></i> Filter';
		$dt_info['action_button_removed'] = $data['attributes']['buttons_removed'];
		$dt_info['modal_title'] = '<i class="fa fa-filter"></i> &nbsp; Filter';
		// Determine which columns to use for filtering
		$filterColumns = [];
		if (is_array($data['columns']['filters'])) {
			// Explicit filters defined
			$filterColumns = $data['columns']['filters'];
		} elseif (!empty($data['columns']['filter_groups'])) {
			// Extract columns from filter_groups configuration
			foreach ($data['columns']['filter_groups'] as $filterGroup) {
				if (isset($filterGroup['column'])) {
					$filterColumns[] = $filterGroup['column'];
				}
			}
		} elseif (is_array($data['columns']['searchable']) && !empty($data['columns']['searchable'])) {
			// Fall back to searchable columns
			$filterColumns = array_keys($data['columns']['searchable']);
		} elseif (isset($data['columns']['lists'])) {
			// Last resort: use lists columns
			$filterColumns = $data['columns']['lists'];
		}
		
		$dt_info['modal_content'] = $search_object->render(
			$searchInfoAttribute,
			$dt_info['name'],
			$filterColumns
		);
		
		return $dt_info;
	}
	
	/**
	 * Render DataTables script
	 * 
	 * @param string $tableID Table ID
	 * @param array $dt_columns DataTables columns
	 * @param array $dt_info DataTables info
	 * @param bool $hasSearchable Has searchable columns
	 * @return string DataTables JavaScript
	 */
	private function renderDataTable(string $tableID, array $dt_columns, array $dt_info, bool $hasSearchable): string {
		$filter_data = [];
		if (true === $hasSearchable) {
			$filter_data = $this->getFilterDataTables();
		}
		
		// REFACTOR: Pass columns as array instead of JSON string
		// This allows Scripts.php to properly convert to JSON for external JS
		// Removed: $dt_columns = canvastack_clear_json(json_encode($dt_columns));
		
		if ('GET' === $this->method) {
			return $this->datatables($tableID, $dt_columns, $dt_info, true, $filter_data);
		}
		
		// POST method (currently same as GET)
		return $this->datatables($tableID, $dt_columns, $dt_info, true, $filter_data);
	}
	
	/**
	 * Get filter data from request (GET or POST)
	 * 
	 * FIXED: 2026-04-27 - Support both GET and POST filter parameters
	 * 
	 * @return string|null Filter string for DataTables
	 */
	private function getFilterDataTables(): ?string {
		$filter_strings = null;
		// SECURITY: Use Laravel request()
		$request = request();
		if (!$request->has('filters')) {
			return $filter_strings;
		}
		
		$input_filters = [];
		$_ajax_url     = 'renderDataTables';
		
		// FIXED: Check both GET and POST parameters
		$allParams = array_merge($request->query(), $request->post());
		
		foreach ($allParams as $name => $value) {
			if ('filters'!== $name && '' !== $value) {
				if (!is_array($value)) {
					if (
						$name !== $_ajax_url &&
						$name !== 'draw'     &&
						$name !== 'columns'  &&
						$name !== 'order'    &&
						$name !== 'start'    &&
						$name !== 'length'   &&
						$name !== 'search'   &&
						$name !== '_token'   &&
						$name !== '_'        &&
						$name !== 'difta'    &&
						$name !== 'renderDataTables'
					) {
						// SECURITY: URL encode
						$safeName  = urlencode($name);
						$safeValue = urlencode($value);
						$input_filters[] = "infil[{$safeName}]={$safeValue}";
					}
				}
			}
		}
		
		if (!empty($input_filters)) {
			$filter_strings = '&filters=true&' . implode('&', $input_filters);
		}
		
		// Debug logging
		if (config('app.debug') && !empty($input_filters)) {
			\Log::debug('Builder: Filter data extracted', [
				'filter_strings' => $filter_strings,
				'input_filters' => $input_filters,
				'method' => $request->method()
			]);
		}
		
		return $filter_strings;
	}
	
	private function backgroundColor(array $attributes = []): ?array {
		if (!empty($attributes)) {
			$tableDataColor = [];
			foreach ($attributes as $colorCode => $dataColor) {
				// SECURITY: Validate color code
				$safeColorCode = $this->validateColor($colorCode);
				
				$textColor = '';
				if (!empty($dataColor['text'])) {
					// SECURITY: Validate text color
					$safeTextColor = $this->validateColor($dataColor['text']);
					$textColor = " color:{$safeTextColor};";
				}
				
				if (!empty($dataColor['columns'])) {
					foreach ($dataColor['columns'] as $columnName) {
						$tableDataColor['columns'][$columnName] = $this->setAttributes(['style' => "background-color:{$safeColorCode} !important;{$textColor}"]);
					}
				}
				
				if (empty($dataColor['columns'])) {
					if (true === $dataColor['header']) $tableDataColor['header'] = $this->setAttributes(['style' => "background-color:{$safeColorCode} !important;{$textColor}"]);
				}
			}
			
			return $tableDataColor;
		}
	}
	
	/**
	 * Build HTML attribute string from key-value pairs.
	 *
	 * @security XSS Prevention - all attribute values are escaped with htmlspecialchars().
	 *           Attribute keys are NOT escaped here; use validateAttributeKeys() first
	 *           for any user-supplied attribute arrays to strip dangerous event handlers.
	 *
	 * @param  array $attributes Key-value pairs of HTML attributes
	 * @return string|null Rendered attribute string (e.g. ' id="foo" class="bar"')
	 */
	private function setAttributes(array $attributes = []): ?string {
		$textAttribute = null;
		if (is_array($attributes)) {
			/**
			 * @performance Use array collection + implode() instead of repeated .= in loop
			 * to avoid repeated string reallocation on each append.
			 */
			$attrParts = [];
			foreach ($attributes as $key => $value) {
				// SECURITY: Escape attribute values to prevent XSS
				$safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
				$attrParts[] = "{$key}=\"{$safeValue}\"";
			}
			if (!empty($attrParts)) {
				$textAttribute = ' ' . implode(' ', $attrParts);
			}
		}
		
		return $textAttribute;
	}
}