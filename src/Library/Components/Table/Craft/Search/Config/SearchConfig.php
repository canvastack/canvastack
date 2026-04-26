<?php
namespace Canvastack\Canvastack\Library\Components\Table\Craft\Search\Config;

/**
 * SearchConfig - Configuration and constants for Search component
 *
 * @filesource SearchConfig.php
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 */
class SearchConfig {
	
	// Class constants for magic strings
	const EXPORT_CLASS_PREFIX = 'export_';
	const FILTER_MODAL_SUFFIX = 'CanvaStackFILTERmodalBOX';
	const FILTER_FIELD_SUFFIX = 'CanvaStackFILTERField';
	const SCRIPT_NODE_PREFIX = 'canvastackScriptNode::';
	const LOADER_PREFIX = 'CanvaStackInpLdr';

	// Cache constants (2.4.2)
	const CACHE_KEY_PREFIX = 'search_selections_';
	const CACHE_TTL_DEFAULT = 300; // 5 minutes
	const CACHE_TTL_LONG = 3600;   // 1 hour for stable reference data
	
	const VALID_FIELD_TYPES = [
		'selectbox', 'text', 'date', 'datetime', 
		'time', 'daterange', 'checkbox', 'radiobox',
		'string', 'smallint'
	];
	
	private string $info;
	private string $table;
	private array $filters;
	private array $relations;
	private array $foreignKeys;
	private array $modelFilters;
	private ?string $searchConnection;
	private bool $tableFromView;
	
	/**
	 * Constructor
	 *
	 * @param string $info Component identifier
	 * @param string $table Table name
	 * @param array $filters Filter configuration
	 * @param string|null $connection Database connection
	 */
	public function __construct(string $info, string $table, array $filters, ?string $connection = null) {
		$this->info = $info;
		$this->table = $table;
		$this->filters = $filters;
		$this->searchConnection = $connection;
		$this->relations = $filters['relations'] ?? [];
		$this->foreignKeys = $filters['foreign_keys'] ?? [];
		$this->modelFilters = $filters['filter_model'] ?? [];
		$this->tableFromView = isset($filters['table_name']) && canvastack_string_contained($filters['table_name'], 'view_');
		
		// DEBUG: Log connection initialization
		\Log::debug('SearchConfig: Initialized', [
			'info' => $info,
			'table' => $table,
			'connection' => $connection,
			'has_filter_groups' => !empty($filters['filter_groups'])
		]);
	}
	
	/**
	 * Get component info
	 *
	 * @return string
	 */
	public function getInfo(): string {
		return $this->info;
	}
	
	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function getTable(): string {
		return $this->table;
	}
	
	/**
	 * Get filters
	 *
	 * @return array
	 */
	public function getFilters(): array {
		return $this->filters;
	}
	
	/**
	 * Get relations
	 *
	 * @return array
	 */
	public function getRelations(): array {
		return $this->relations;
	}
	
	/**
	 * Get foreign keys
	 *
	 * @return array
	 */
	public function getForeignKeys(): array {
		return $this->foreignKeys;
	}
	
	/**
	 * Get model filters
	 *
	 * @return array
	 */
	public function getModelFilters(): array {
		return $this->modelFilters;
	}
	
	/**
	 * Get database connection
	 *
	 * @return string|null
	 */
	public function getConnection(): ?string {
		return $this->searchConnection;
	}
	
	/**
	 * Check if table is from view
	 *
	 * @return bool
	 */
	public function isTableFromView(): bool {
		return $this->tableFromView;
	}
	
	/**
	 * Get filter query
	 *
	 * @return array
	 */
	public function getFilterQuery(): array {
		return $this->filters['filter_query'] ?? [];
	}
}
