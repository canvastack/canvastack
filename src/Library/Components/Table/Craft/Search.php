<?php
namespace Canvastack\Canvastack\Library\Components\Table\Craft;

use Canvastack\Canvastack\Library\Components\Table\Craft\Search\Config\SearchConfig;
use Canvastack\Canvastack\Library\Components\Table\Craft\Search\QueryBuilder;
use Canvastack\Canvastack\Library\Components\Table\Craft\Search\FormGenerator;
use Canvastack\Canvastack\Library\Components\Table\Craft\Search\ScriptGenerator;
use Canvastack\Canvastack\Library\Components\Table\Craft\Search\ModalRenderer;
use Canvastack\Canvastack\Library\Constants\TableConstants;
use Illuminate\Support\Facades\Log;

/**
 * Search - Main orchestrator for table search functionality
 * 
 * Created on 24 Apr 2021
 * Time Created : 20:51:52
 * Refactored: Split into multiple classes for better maintainability
 *
 * @filesource Search.php
 *
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 */
class Search {
	
	// Backward compatibility: Keep constants in main class
	const EXPORT_CLASS_PREFIX = 'export_';
	const FILTER_MODAL_SUFFIX = 'CanvaStackFILTERmodalBOX';
	const FILTER_FIELD_SUFFIX = 'CanvaStackFILTERField';
	const SCRIPT_NODE_PREFIX = 'canvastackScriptNode::';
	const LOADER_PREFIX = 'CanvaStackInpLdr';
	
	const VALID_FIELD_TYPES = [
		'selectbox', 'text', 'date', 'datetime', 
		'time', 'daterange', 'checkbox', 'radiobox',
		'string', 'smallint'
	];
	
	// Component instances
	private $config;
	private $queryBuilder;
	private $formGenerator;
	private $scriptGenerator;
	private $modalRenderer;
	
	// Original properties (for backward compatibility)
	private $model;
	private $filters;
	private $input_relations;
	private $sql;
	private $table;
	private $tableFromView = false;
	private $info;
	private $searchConnection;
	private $model_filters = [];
	private $data = [];
	private $html = false;
	
	// Public property (backward compatibility)
	public $add_scripts = [];
	
	/**
	 * Constructor - Initialize Search component
	 *
	 * @param string $info Component identifier
	 * @param string|null $model Model class name
	 * @param array $filters Filter configuration
	 * @param string|null $sql Custom SQL query
	 * @param string|null $connection Database connection name
	 * @param array $filterQuery Additional filter query
	 * @throws \InvalidArgumentException If invalid parameters provided
	 */
	public function __construct(string $info, ?string $model = null, array $filters = [], ?string $sql = null, ?string $connection = null, array $filterQuery = []) {
		// Validate inputs
		$this->validateConstructorInputs($info, $filters, $connection);
		
		// DEBUG: Log connection parameter
		\Log::debug('Search: Constructor called', [
			'info' => $info,
			'model' => $model,
			'connection' => $connection,
			'table_name' => $filters['table_name'] ?? 'not set',
			'has_filter_groups' => !empty($filters['filter_groups'])
		]);
		
		if (!empty($connection)) {
			$this->searchConnection = $connection;
		}
		
		if (isset($filters['table_name']) && canvastack_string_contained($filters['table_name'], 'view_')) {
			$this->tableFromView = true;
		}
		
		$this->info = $info;
		
		if (!empty($model)) {
			$model = new $model();
		}
		
		if (!empty($filters['filter_model'])) {
			$this->model_filters = $filters['filter_model'];
			$this->model = $model->where($this->model_filters);
		} else {
			$this->model = $model;
		}
		
		$this->table = $filters['table_name'] ?? '';
		$this->filters = $filters;
		$this->sql = $sql;
		
		// Initialize components
		$this->config = new SearchConfig($info, $this->table, $filters, $connection);
		$this->queryBuilder = new QueryBuilder($this->config);
		$this->formGenerator = new FormGenerator($this->config);
		$this->scriptGenerator = new ScriptGenerator($this->config);
		$this->modalRenderer = new ModalRenderer($this->config);
		
		if (!empty($filters['filter_groups'])) {
			$this->getFilterData($filters['filter_groups']);
		}
		
		if (!empty($filterQuery)) {
			$this->filters['filter_query'] = canvastack_filter_data_normalizer($filterQuery);
		}
	}
	
	/**
	 * Validate constructor inputs
	 *
	 * @param mixed $info
	 * @param mixed $filters
	 * @param mixed $connection
	 * @throws \InvalidArgumentException
	 */
	private function validateConstructorInputs(mixed $info, mixed $filters, mixed $connection): void {
		if (empty($info) || !is_string($info)) {
			throw new \InvalidArgumentException('Info parameter must be a non-empty string');
		}
		
		if (!is_array($filters)) {
			throw new \InvalidArgumentException('Filters must be an array');
		}
		
		if (!empty($connection) && !is_string($connection)) {
			throw new \InvalidArgumentException('Connection must be a string');
		}
		
		// Validate required filter keys
		$requiredKeys = ['table_name', 'relations', 'foreign_keys'];
		foreach ($requiredKeys as $key) {
			if (!isset($filters[$key])) {
				throw new \InvalidArgumentException("Missing required filter key: {$key}");
			}
		}
	}
	
	/**
	 * Render search box
	 *
	 * @param string $info Component identifier
	 * @param string $table Table name
	 * @param array $fields Field list
	 * @return array Rendered data
	 */
	public function render(string $info, string $table, array $fields): ?array {
		if ($this->info === $info) {
			$this->search_box($info, $table, $this->getColumnInfo($table, $fields), $this->model);
			
			$data = [];
			$data['name'] = ucwords(str_replace('-', ' ', canvastack_clean_strings($table)));
			$data['html'] = $this->html;
			
			return $data;
		}
	}
	
	/**
	 * Get filter data and build relations
	 *
	 * @param array $data Filter groups
	 * @return void
	 */
	private function getFilterData(array $data): void {
		$all_columns = $this->collectAllColumns();
		$data = $this->processFilterRows($data, $all_columns);
		$this->data = $data;
		
		$input_relations = $this->buildInputRelations($data);
		$this->setInputRelations($input_relations);
	}
	
	/**
	 * Collect all columns from filters - optimized
	 *
	 * @return array Column mapping
	 */
	private function collectAllColumns(): array {
		$columns = $this->filters['columns'] ?? [];
		if (!is_array($columns) || empty($columns)) {
			return [];
		}
		return array_combine($columns, $columns);
	}
	
	/**
	 * Process filter rows and build relists
	 *
	 * @param array $data Filter data
	 * @param array $all_columns All columns
	 * @return array Processed data
	 */
	private function processFilterRows(array $data, array $all_columns): array {
		$processed = [];
		
		foreach ($data as $key => $row) {
			$column = $row['column'] ?? '';
			$type = $row['type'] ?? 'text';
			$relate = $row['relate'] ?? false;
			
			// Determine relate value
			if (!empty($relate)) {
				$relate = (true === $relate) ? array_keys($all_columns) : $relate;
			}
			
			$processed[$column] = [
				'name' => $column,
				'type' => $type,
				'relate' => $relate
			];
		}
		
		return $processed;
	}
	
	/**
	 * Build input relations from processed data
	 *
	 * @param array $data Filter data
	 * @return array Input relations
	 */
	private function buildInputRelations(array $data): array {
		if (count($data) >= 2) {
			return $this->buildMultipleInputRelations($data);
		}
		
		return $this->buildSingleInputRelation($data);
	}
	
	/**
	 * Build input relations for multiple data
	 *
	 * @param array $data Filter data
	 * @return array Input relations
	 */
	private function buildMultipleInputRelations(array $data): array {
		$input_relations = ['lists' => [], 'type' => []];
		
		foreach ($data as $column => $row) {
			$relate = $row['relate'] ?? false;
			$type = $row['type'] ?? 'text';
			
			// Always add the field itself to the lists
			$input_relations['lists'][] = $column;
			$input_relations['type'][$column] = $type;
			
			// If field has relations, add them too
			if (false !== $relate && !empty($relate)) {
				foreach ((array)$relate as $relation) {
					if (!in_array($relation, $input_relations['lists'])) {
						$input_relations['lists'][] = $relation;
						$input_relations['type'][$relation] = $data[$relation]['type'] ?? 'text';
					}
				}
			}
		}
		
		return $input_relations;
	}
	
	/**
	 * Build input relation for single data
	 *
	 * @param array $data Filter data
	 * @return array Input relation
	 */
	private function buildSingleInputRelation(array $data): array {
		$the_only_data = array_keys($data);
		$first_key = $the_only_data[0] ?? null;
		
		if ($first_key === null) {
			return ['lists' => [], 'type' => []];
		}
		
		return [
			'lists' => [$first_key],
			'type' => [$first_key => 'selectbox']
		];
	}
	
	/**
	 * Set input relations to class property
	 *
	 * @param array $input_relations Input relations
	 * @return void
	 */
	private function setInputRelations(array $input_relations): void {
		if (!empty($input_relations['lists'])) {
			$this->input_relations['lists'] = array_unique($input_relations['lists']);
		}
		
		if (!empty($input_relations['type'])) {
			$this->input_relations['type'] = $input_relations['type'];
		}
	}
	
	/**
	 * Main search box generation
	 *
	 * @param string $info Component info
	 * @param string $tablename Table name
	 * @param array $data Column data
	 * @param mixed $model Model instance
	 * @return void
	 */
	private function search_box(string $info, string $tablename, array $data, mixed $model): void {
		$filterQuery = $this->config->getFilterQuery();
		$this->formGenerator->setupSearchFields($this->data);
		
		// Debug logging
		if (empty($data)) {
			Log::warning('Search: search_box called with empty data', [
				'info' => $info,
				'tablename' => $tablename,
				'filters_columns' => $this->filters['columns'] ?? 'not set'
			]);
		}
		
		$script_elements = [];
		
		if (!empty($this->input_relations['type'])) {
			$script_elements = $this->processInputRelations($info, $tablename);
		} else {
			$script_elements = $this->processDefaultData($info, $data);
		}
		
		// Generate modal HTML
		$this->modalRenderer->generateModalHTML(
			$info, 
			$tablename, 
			$this->formGenerator->getFormElements(),
			$this->scriptGenerator,
			$script_elements,
			$filterQuery
		);
		
		$this->html = $this->modalRenderer->getHtml();
		$this->add_scripts = $this->scriptGenerator->getScripts();
	}
	
	/**
	 * Process input relations (SECURE - XSS protected)
	 * OPTIMIZED: Batch load all field values in single query
	 *
	 * @param string $info Component info
	 * @param string $tablename Table name
	 * @return array Script elements
	 */
	private function processInputRelations(string $info, string $tablename): array {
		$script_elements = [];
		$inputRelations = $this->prepareInputRelations();
		$this->input_relations['type'] = $inputRelations;
		
		$open_field = $this->input_relations['lists'][0] ?? null;
		
		if (!empty($open_field)) {
			// OPTIMIZATION: Batch load all field values in single query
			$allFields = array_keys($this->input_relations['type']);
			$this->formGenerator->batchLoadFieldValues($tablename, $allFields, $open_field, $this->queryBuilder);
			
			foreach ($this->input_relations['type'] as $field => $type) {
				$values = $this->formGenerator->getFieldValuesFromCache($field, $open_field);
				$attributes = $this->formGenerator->buildFieldAttributes($field, $info, $values);
				
				// FIX XSS: Escape field label
				$field_label = $this->formGenerator->escapeHtml(ucwords(canvastack_clean_strings($field, ' ')));
				$values = $this->formGenerator->prepareFieldOptions($field, $type, $values, $field_label);
				
				$this->formGenerator->generateFormElement($field, $type, $values, $attributes);
				$script_elements[$info][$field] = $type;
			}
		}
		
		return $script_elements;
	}
	
	/**
	 * Prepare input relations
	 *
	 * @return array Prepared relations
	 */
	private function prepareInputRelations(): array {
		$inputRelations = [];
		$searchFields = $this->formGenerator->getSearchFields();
		
		foreach ($this->input_relations['type'] as $inputFields => $inputType) {
			if (!empty($searchFields[$inputFields])) {
				$fieldName = $searchFields[$inputFields];
				// FIX: Skip empty field names to prevent invalid selectors
				if (!empty($fieldName) && trim($fieldName) !== '') {
					$inputRelations[$fieldName] = $inputType;
				}
			}
		}
		
		return $inputRelations;
	}
	
	/**
	 * Process default data
	 *
	 * @param string $info Component info
	 * @param array $data Column data
	 * @return array Script elements
	 */
	private function processDefaultData(string $info, array $data): array {
		$script_elements = [];
		
		foreach ($data as $field => $type) {
			// FIX: Use buildFieldAttributes to generate unique IDs per table
			$attributes = $this->formGenerator->buildFieldAttributes($field, $info, null);
			$this->formGenerator->generateFormElement($field, $type, null, $attributes);
			$script_elements[$info][$field] = $type;
		}
		
		return $script_elements;
	}
	
	/**
	 * Get column information for table fields
	 *
	 * @param string $table Table name
	 * @param array $fields Field names
	 * @return array Column type information
	 */
	private function getColumnInfo(string $table, array $fields): array {
		$columns = [];
		$allColumns = $this->getColumns($table);
		
		// DEBUG: Log before processing
		Log::debug('Search: getColumnInfo() before processing', [
			'table' => $table,
			'allColumns_count' => count($allColumns),
			'allColumns' => $allColumns,
			'tableFromView' => $this->tableFromView,
			'requested_fields' => $fields,
			'has_relations' => !empty($this->filters['relations'])
		]);
		
		// For views, we don't need column types - just check existence
		if (false === $this->tableFromView) {
			foreach ($allColumns as $column) {
				$columns[$column] = $this->getColumnType($table, $column);
			}
		} else {
			// For views, just mark columns as existing (use 'string' as default type)
			foreach ($allColumns as $column) {
				$columns[$column] = 'string';
			}
		}
		
		// DEBUG: Log after populating columns
		Log::debug('Search: getColumnInfo() after populating columns', [
			'table' => $table,
			'columns_count' => count($columns),
			'columns_keys' => array_keys($columns),
			'tableFromView' => $this->tableFromView
		]);
		
		$info = [];
		foreach ($fields as $field) {
			// Check if field exists in table columns
			if (!empty($columns[$field])) {
				$info[$field] = $columns[$field];
			}
			// If not in table, check if it's a relational field
			elseif (!empty($this->filters['relations'])) {
				foreach ($this->filters['relations'] as $relationKey => $relationData) {
					if ($relationKey === $field || (isset($relationData['field_name']) && $relationData['field_name'] === $field)) {
						// It's a relational field, use 'string' as default type
						$info[$field] = 'string';
						Log::debug('Search: Found relational field', [
							'field' => $field,
							'relation_key' => $relationKey
						]);
						break;
					}
				}
			}
		}
		
		// DEBUG: Log final result
		Log::debug('Search: getColumnInfo() final result', [
			'table' => $table,
			'info_count' => count($info),
			'info_keys' => array_keys($info)
		]);
		
		// Debug logging when no columns match
		if (empty($info) && !empty($fields)) {
			Log::warning('Search: No matching columns found', [
				'table' => $table,
				'requested_fields' => $fields,
				'available_columns' => array_keys($columns),
				'is_view' => $this->tableFromView,
				'has_relations' => !empty($this->filters['relations'])
			]);
		}
		
		return $info;
	}
	
	/**
	 * Get columns for table
	 *
	 * @param string $table Table name
	 * @return array Column names
	 */
	private function getColumns(string $table): array {
		$connection = $this->searchConnection ?? 'mysql';
		
		// DEBUG: Log before calling helper
		\Log::debug('Search: getColumns() called', [
			'table' => $table,
			'connection' => $connection,
			'searchConnection' => $this->searchConnection
		]);
		
		$columns = canvastack_get_table_columns($table, $connection);
		
		// DEBUG: Log result
		\Log::debug('Search: getColumns() result', [
			'table' => $table,
			'connection' => $connection,
			'columns_count' => count($columns),
			'columns' => $columns
		]);
		
		return $columns;
	}
	
	/**
	 * Get column type
	 *
	 * @param string $table Table name
	 * @param string $column Column name
	 * @return string Column type
	 */
	private function getColumnType(string $table, string $column): string {
		$connection = $this->searchConnection ?? 'mysql';
		return canvastack_get_table_column_type($table, $column, $connection);
	}
}
