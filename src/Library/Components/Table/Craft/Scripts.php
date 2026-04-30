<?php
namespace Canvastack\Canvastack\Library\Components\Table\Craft;

use Canvastack\Canvastack\Library\Constants\TableConstants;

/**
 * Created on 22 May 2021
 * Time Created : 00:29:19
 *
 * @filesource Scripts.php
 *
 * @author    wisnuwidi@canvastack.com - 2021
 * @copyright wisnuwidi
 * @email     wisnuwidi@canvastack.com
 */
 
trait Scripts {
	
	private const MAX_ROWS_LIMIT = 999999999; // Safe limit untuk 32-bit dan 64-bit systems
	private const DEFAULT_ROWS_LIMIT = TableConstants::DEFAULT_PAGE_LENGTH;
	private const HASH_CALCULATION_DIVISOR = 80; // 8*800/80 = 80
	
	private $datatablesMode = 'GET';
	private $strictGetUrls  = true;
	private $strictColumns  = true;
	
	/**
	 * Javascript Config for Rendering Datatables
	 *
	 * created @Oct 11, 2018
	 * author: wisnuwidi
	 *
	 * @param string $attr_id
	 * @param string $columns
	 * @param array $data_info
	 * @param boolean $server_side
	 * @param boolean|array $filters
	 * @param boolean|string|array $custom_link
	 *
	 * @return string
	 */
	protected function datatables(string $attr_id, string|array $columns, array $data_info = [], bool|array $server_side = false, bool|array|null $filters = false, bool|string|array $custom_link = false): string {
		// Input validation
		if (empty($attr_id) || empty($columns)) {
			trigger_error('datatables(): attr_id and columns are required', E_USER_WARNING);
			return '';
		}
		
		$varTableID   = $this->sanitizeTableId($attr_id);
		$current_url  = url(canvastack_current_route()->uri);
		
		// Build configuration object for external JS
		$config = $this->buildDataTablesConfig(
			$attr_id,
			$varTableID,
			$columns,
			$data_info,
			$current_url,
			$server_side,
			$filters,
			$custom_link
		);
		
		// Convert entire config to JSON
		$configJson = json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		
		// Generate simple function call to external JS
		$js = '<script type="text/javascript">jQuery(function($) {';
		$js .= "CanvaStack_{$varTableID}_dt = CanvastackDataTables.initialize('{$attr_id}', {$configJson});";
		
		// Document load script for additional setup
		$documentLoad = $this->buildDocumentLoadScript($attr_id, $filters, $data_info, $current_url);
		$js .= '});' . $documentLoad . '</script>';
		
		return $js;
	}

	/**
	 * Build DataTables configuration object for external JS
	 *
	 * This method builds a configuration array that will be passed to
	 * the external JavaScript file (canvastack-datatables.js) as JSON.
	 *
	 * @param string $attr_id
	 * @param string $varTableID
	 * @param string|array $columns Columns as array or JSON string
	 * @param array $data_info
	 * @param string $current_url
	 * @param bool|array $server_side
	 * @param bool|array|null $filters
	 * @param bool|string|array $custom_link
	 * @return array
	 */
	private function buildDataTablesConfig(string $attr_id, string $varTableID, string|array $columns, array $data_info, string $current_url, bool|array $server_side, bool|array|null $filters, bool|string|array $custom_link): array {
		$buttonset    = $this->buildButtonSet($attr_id);
		$fixedColumn  = $this->buildFixedColumnConfig($data_info);
		$lengthMenu   = $this->buildLengthMenu($data_info);

		// Build base configuration
		$config = [
			'datatableConfig' => [],
			'clickAction' => null,
			'filterButton' => null
		];

		// Parse default config string into array
		$defaultConfigStr = $this->buildDefaultConfig($fixedColumn, $lengthMenu, $buttonset);
		$config['datatableConfig'] = $this->parseConfigString($defaultConfigStr);
		
		// Phase 3: Apply DataTables Defaults (13 options)
		// Override with config values if set
		$config['datatableConfig']['pageLength'] = config('canvastack.datatables.defaults.page_length', 
			$config['datatableConfig']['pageLength'] ?? 10);
		$config['datatableConfig']['ordering'] = config('canvastack.datatables.defaults.ordering', true);
		$config['datatableConfig']['searching'] = config('canvastack.datatables.defaults.searching', true);
		$config['datatableConfig']['paging'] = config('canvastack.datatables.defaults.paging', true);
		$config['datatableConfig']['info'] = config('canvastack.datatables.defaults.info', true);
		$config['datatableConfig']['autoWidth'] = config('canvastack.datatables.defaults.auto_width', false);
		$config['datatableConfig']['responsive'] = config('canvastack.datatables.defaults.responsive', true);
		
		// Scroll settings
		if (config('canvastack.datatables.defaults.scroll_x', false)) {
			$config['datatableConfig']['scrollX'] = true;
		}
		if ($scrollY = config('canvastack.datatables.defaults.scroll_y', null)) {
			$config['datatableConfig']['scrollY'] = $scrollY;
		}
		if (config('canvastack.datatables.defaults.scroll_collapse', false)) {
			$config['datatableConfig']['scrollCollapse'] = true;
		}
		
		// State saving
		if (config('canvastack.datatables.defaults.state_save', false)) {
			$config['datatableConfig']['stateSave'] = true;
			$config['datatableConfig']['stateDuration'] = config('canvastack.datatables.defaults.state_duration', 7200);
		}
		
		// DOM layout
		if ($dom = config('canvastack.datatables.defaults.dom', null)) {
			$config['datatableConfig']['dom'] = $dom;
		}
		
		// Add search configuration
		if (config('canvastack.datatables.search.global_search', true)) {
			$config['datatableConfig']['searching'] = true;
			
			// Add search options
			$searchConfig = [];
			
			if (config('canvastack.datatables.search.case_insensitive', true)) {
				$searchConfig['caseInsensitive'] = true;
			}
			
			if (config('canvastack.datatables.search.regex_search', false)) {
				$searchConfig['regex'] = true;
			}
			
			if (!empty($searchConfig)) {
				$config['datatableConfig']['search'] = $searchConfig;
			}
		} else {
			$config['datatableConfig']['searching'] = false;
		}

		// Handle columns - convert to array if string
		if (is_string($columns)) {
			// Legacy: columns passed as JSON string
			$config['datatableConfig']['columns'] = json_decode($columns, true);
		} else {
			// New: columns passed as array
			$config['datatableConfig']['columns'] = $columns;
		}

		// Server-side or client-side configuration
		if (false !== $server_side) {
			$config['datatableConfig']['serverSide'] = true;
			$config['datatableConfig']['rowReorder'] = ['selector' => 'td:nth-child(2)'];
			$config['datatableConfig']['responsive'] = false;

			// Build AJAX configuration
			$dataName = $data_info['name'] ?? 'unknown';
			$diftaParams = http_build_query([
				'difta' => [
					'name' => $dataName,
					'source' => 'dynamics'
				]
			]);
			$link_url = "renderDataTables=true&{$diftaParams}";

			if (false !== $custom_link) {
				if (is_array($custom_link) && count($custom_link) >= 2) {
					$link_url = urlencode($custom_link[0]) . "=" . urlencode($custom_link[1]);
				} elseif (is_string($custom_link)) {
					$link_url = urlencode($custom_link) . "=true";
				}
			}

			$scriptURI = "{$current_url}?{$link_url}";
			$ajaxConfig = $this->buildAjaxConfigArray($attr_id, $scriptURI, $filters);
			$config['datatableConfig']['ajax'] = $ajaxConfig;

			// Column definitions
			$config['datatableConfig']['columnDefs'] = [
				[
					'targets' => [1],
					'visible' => false,
					'searchable' => false,
					'className' => 'control hidden-column'
				]
			];
			$config['datatableConfig']['order'] = [[1, 'desc']];

			// Click action configuration
			$url_path = url(canvastack_current_route()->uri);
			$hash = hash_code_id();
			$config['clickAction'] = [
				'hash' => $hash,
				'hashDivisor' => self::HASH_CALCULATION_DIVISOR,
				'urlPath' => $url_path
			];

			// Filter button configuration
			if (false !== $filters) {
				$config['filterButton'] = "CanvaStack_{$attr_id}_canvastack-dt-filter-box";
			}
		}

		// Add initComplete callback
		$config['datatableConfig']['initComplete'] = $this->buildInitCompleteConfig($attr_id, false);

		// Add conditional columns if exists
		$jsConditional = $this->buildConditionalColumns($varTableID, $data_info);
		if (!empty($jsConditional)) {
			// Store as string marker - will be handled by external JS
			$config['datatableConfig']['createdRowJs'] = $jsConditional;
		}

		// Tasks 4.4.5-4.4.8: Add screen reader announcement configuration
		$config['screenReaderConfig'] = [
			'enabled' => config('canvastack.datatables.accessibility.screen_reader_support', true),
			'announceLoading' => config('canvastack.datatables.accessibility.announce_loading', true),
			'announceFilters' => config('canvastack.datatables.accessibility.announce_filters', true),
			'announceSorting' => config('canvastack.datatables.accessibility.announce_sorting', true),
			'paginationStatusId' => $attr_id . '-pagination-status',
			'filterStatusId' => $attr_id . '-filter-status',
			'sortStatusId' => $attr_id . '-sort-status',
			'loadingStatusId' => $attr_id . '-loading-status'
		];
		
		// Add search debounce and min length for JS
		$config['searchConfig'] = [
			'debounceDelay' => config('canvastack.datatables.search.debounce_delay', 300),
			'minSearchLength' => config('canvastack.datatables.search.min_search_length', 1),
			'highlightResults' => config('canvastack.datatables.search.highlight_results', false),
		];

		return $config;
	}

	/**
	 * Parse config string into array
	 *
	 * @param string $configStr
	 * @return array
	 */
	private function parseConfigString(string $configStr): array {
		$config = [];
		
		// Extract lengthMenu
		if (preg_match('/lengthMenu\s*:\s*(\[\[.*?\],\[.*?\]\])/', $configStr, $matches)) {
			$config['lengthMenu'] = json_decode($matches[1], true);
		} else {
			$config['lengthMenu'] = null;
		}
		
		// Parse boolean values (handle spaces around colon)
		$config['searching'] = preg_match('/"searching"\s*:\s*true/', $configStr) === 1;
		$config['processing'] = preg_match('/"processing"\s*:\s*true/', $configStr) === 1;
		$config['retrieve'] = preg_match('/"retrieve"\s*:\s*false/', $configStr) === 1 ? false : true;
		$config['paginate'] = preg_match('/"paginate"\s*:\s*true/', $configStr) === 1;
		$config['bDeferRender'] = preg_match('/"bDeferRender"\s*:\s*true/', $configStr) === 1;
		$config['responsive'] = preg_match('/"responsive"\s*:\s*false/', $configStr) === 1 ? false : true;
		$config['autoWidth'] = preg_match('/"autoWidth"\s*:\s*false/', $configStr) === 1 ? false : true;
		
		// Parse numeric values
		if (preg_match('/"searchDelay"\s*:\s*(\d+)/', $configStr, $matches)) {
			$config['searchDelay'] = intval($matches[1]);
		} else {
			$config['searchDelay'] = 1000;
		}
		
		// Parse string values
		if (preg_match('/"dom"\s*:\s*"([^"]+)"/', $configStr, $matches)) {
			$config['dom'] = $matches[1];
		} else {
			$config['dom'] = 'lBfrtip';
		}
		
		// CRITICAL FIX: Don't parse buttons as JSON - it's JavaScript syntax, not JSON
		// Extract buttons as RAW STRING and mark it for JavaScript evaluation
		if (preg_match('/"buttons"\s*:\s*(\[.+\]),/', $configStr, $matches)) {
			// Store as special marker that will be evaluated in JavaScript
			$config['buttonsJs'] = $matches[1];
		} else {
			$config['buttonsJs'] = null;
		}
		
		// Parse fixed columns config
		if (preg_match('/scrollY:\s*(\d+)/', $configStr, $matches)) {
			$config['scrollY'] = intval($matches[1]);
		}
		if (strpos($configStr, 'scrollX:true') !== false) {
			$config['scrollX'] = true;
		}
		if (strpos($configStr, 'scrollCollapse:true') !== false) {
			$config['scrollCollapse'] = true;
		}
		if (preg_match('/fixedColumns:\s*(\{[^}]+\})/', $configStr, $matches)) {
			$config['fixedColumns'] = json_decode($matches[1], true);
		}
		
		return $config;
	}

	/**
	 * Build AJAX configuration as array (not string)
	 *
	 * @param string $attr_id
	 * @param string $scriptURI
	 * @param bool|array|null $filters
	 * @return array
	 */
	private function buildAjaxConfigArray(string $attr_id, string $scriptURI, bool|array|null $filters): array {
		if (!empty($this->method)) {
			$this->datatablesMode = $this->method;
		}

		// Normalize filters
		$filterString = '';
		if (false !== $filters && null !== $filters) {
			if (is_array($filters) && !empty($filters)) {
				$filterString = '&' . http_build_query(['filters' => $filters]);
			}
		}

		$ajaxConfig = [
			'url' => $scriptURI . $filterString
		];

		if ('POST' === $this->datatablesMode) {
			$ajaxConfig['type'] = 'POST';
			$ajaxConfig['headers'] = [
				'X-CSRF-TOKEN' => csrf_token()
			];
		} else {
			// GET mode with URL optimization
			if (true === $this->strictGetUrls) {
				$idString = str_replace('-', '', $attr_id);
				$strictColumns = $this->strictColumns ? 'true' : 'false';
				// Store as markers that will be evaluated in JS
				$ajaxConfig['dataFilter'] = 'deleteUnnecessaryDatatableComponents';
				$ajaxConfig['dataFilterParams'] = [
					'varName' => "canvastackDUDC{$idString}",
					'strictColumns' => $strictColumns
				];
			}
		}

		return $ajaxConfig;
	}

	/**
	 * Build initComplete configuration as array
	 *
	 * @param string $id
	 * @param bool|string $location
	 * @return array
	 */
	private function buildInitCompleteConfig(string $id, bool|string $location = 'footer'): array {
		$config = [
			'deleteTFoot' => false,
			'columnSearch' => false,
			'location' => $location
		];

		if (false === $location) {
			$config['deleteTFoot'] = true;
		} else {
			if (true === $location) {
				$location = 'footer';
			}
			$config['columnSearch'] = true;
			$config['location'] = $location;
		}

		return $config;
	}


	/**
	 * Build ARIA attributes helper function for DataTables
	 * 
	 * This function adds ARIA attributes to table rows and cells for accessibility.
	 * It's called after DataTables initialization to ensure proper screen reader support.
	 * 
	 * Implements:
	 * - Requirement 11.8: aria-label for action buttons
	 * - Requirement 11.7: aria-label for pagination controls
	 * - Requirement 11.9: aria-busy for loading states
	 * - Task 4.2.1: aria-label to pagination controls
	 * - Task 4.2.2: aria-current to current page
	 * - Task 4.2.3: aria-label to action buttons
	 * - Task 4.2.4: aria-live for status updates
	 * 
	 * @return string JavaScript function definition
	 */
	/**
	 * DEPRECATED: This method has been moved to external JS file
	 * @see public/assets/templates/default/js/canvastack-datatables.js
	 * 
	 * @deprecated Use external JS file instead
	 * @return string Empty string
	 */
	private function buildAriaAttributesHelper(): string {
		// Moved to canvastack-datatables.js
		return '';
	}
	
	/**
	 * Build keyboard navigation handler for DataTables
	 * 
	 * Implements keyboard shortcuts and navigation for table accessibility:
	 * - Enter/Space on sortable headers to sort columns
	 * - Arrow keys for pagination navigation
	 * - Keyboard shortcuts for common actions
	 * - Visible focus indicators via CSS
	 * 
	 * Implements:
	 * - Requirement 12.5: Keyboard sorting functionality
	 * - Requirement 12.6: Keyboard shortcuts for common actions
	 * - Task 4.3.2: Add keyboard shortcuts for common actions
	 * - Task 4.3.3: Implement keyboard sorting (Enter/Space on headers)
	 * - Task 4.3.4: Implement keyboard pagination (Arrow keys)
	 * - Task 4.3.5: Add visible focus indicators
	 * 
	 * @return string JavaScript function definition
	 */
	/**
	 * DEPRECATED: This method has been moved to external JS file
	 * @see public/assets/templates/default/js/canvastack-datatables.js
	 * 
	 * @deprecated Use external JS file instead
	 * @return string Empty string
	 */
	private function buildKeyboardNavigationHandler(): string {
		// Moved to canvastack-datatables.js
		return '';
	}
	
	/**
	 * Build keyboard help modal
	 * 
	 * Generates HTML and JavaScript for keyboard shortcuts help modal.
	 * Modal is triggered by Ctrl+Shift+H shortcut.
	 * 
	 * Implements Task 4.3.2: Add keyboard shortcuts help modal
	 * 
	 * @return string JavaScript code for help modal
	 */
	
	/**
	 * Sanitize table ID for use in JavaScript variable names
	 *
	 * @param string $attr_id
	 * @return string
	 */
	private function sanitizeTableId(string $attr_id): string {
		$varTableID = explode('-', $attr_id);
		return implode('', $varTableID);
	}
	
	/**
	 * Sanitize string for JavaScript context
	 * Uses addslashes for simple and efficient escaping
	 *
	 * @param string $value
	 * @return string
	 */
	private function sanitizeJsValue(string $value): string {
		return addslashes($value);
	}
	
	/**
	 * Validate and get array value with default
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	private function getArrayValue(array $array, string $key, mixed $default = null): mixed {
		return isset($array[$key]) ? $array[$key] : $default;
	}
	
	/**
	 * Build button set configuration
	 *
	 * @param string $attr_id
	 * @return string
	 */
	private function buildButtonSet(string $attr_id): string {
		$buttonConfig = 'exportOptions:{columns:":visible:not(:last-child)"}';
		$result = $this->setButtons($attr_id, [
			'excel|text:"<i class=\"fa fa-external-link\" aria-hidden=\"true\"></i> <u>E</u>xcel"|key:{key:"e",altKey:true}',
			'csv|'   . $buttonConfig,
			'pdf|'   . $buttonConfig,
			'copy|'  . $buttonConfig,
			'print|' . $buttonConfig
		]);
		
		return $result;
	}
	
	/**
	 * Build fixed column configuration
	 *
	 * @param array $data_info
	 * @return string
	 */
	private function buildFixedColumnConfig(array $data_info): string {
		if (empty($data_info['fixed_columns'])) {
			return '';
		}
		
		$fixedColumnData = json_encode($data_info['fixed_columns']);
		return 'scrollY:300,scrollX:true,scrollCollapse:true,fixedColumns:' . $fixedColumnData . ',';
	}
	
	/**
	 * Build length menu configuration
	 *
	 * @param array $data_info
	 * @return string
	 */
	private function buildLengthMenu(array $data_info): string {
		$allLimitRows    = self::MAX_ROWS_LIMIT;
		$limitRowsData   = [10, 25, 50, 100, 250, 500, 1000, $allLimitRows];
		$onloadRowsLimit = [self::DEFAULT_ROWS_LIMIT];
		
		if (!empty($data_info['onload_limit_rows'])) {
			if (is_string($data_info['onload_limit_rows'])) {
				if (in_array(strtolower($data_info['onload_limit_rows']), ['*', 'all'])) {
					unset($limitRowsData[array_search(end($limitRowsData), $limitRowsData)]);
					$onloadRowsLimit = [$allLimitRows];
				}
			} else {
				unset($limitRowsData[array_search($data_info['onload_limit_rows'], $limitRowsData)]);
				$onloadRowsLimit = [intval($data_info['onload_limit_rows'])];
			}
			
			$limitRowsData = array_merge_recursive($onloadRowsLimit, $limitRowsData);
		}
		
		$limitRowsDataString = [];
		foreach ($limitRowsData as $row => $limit) {
			$limitRowsDataString[$row] = ($allLimitRows === $limit) 
				? "Show All" 
				: (string) $limit . ' Rows';
		}
		
		$lengthMenu = json_encode([$limitRowsData, $limitRowsDataString]);
		return "lengthMenu :{$lengthMenu}, ";
	}
	
	/**
	 * Build default DataTable configuration
	 *
	 * @param string $fixedColumn
	 * @param string $lengthMenu
	 * @param string $buttonset
	 * @return string
	 */
	private function buildDefaultConfig(string $fixedColumn, string $lengthMenu, string $buttonset): string {
		$config = [
			$fixedColumn,
			'"searching"    :true,',
			'"processing"   :true,',
			'"retrieve"     :false,',
			'"paginate"     :true,',
			'"searchDelay"  :1000,',
			'"bDeferRender" :true,',
			'"responsive"   :false,',
			'"autoWidth"    :false,',
			'"dom"          :"lBfrtip",',
			$lengthMenu,
			'"buttons"  :' . $buttonset . ','
		];
		
		$result = implode('', $config);
		
		return $result;
	}
	
	/**
	 * Build conditional columns JavaScript
	 *
	 * @param string $varTableID
	 * @param array $data_info
	 * @return string|null
	 */
	private function buildConditionalColumns(string $varTableID, array $data_info): ?string {
		if (empty($data_info['conditions']['columns'])) {
			return null;
		}
		
		return $this->conditionalColumns(
			"CanvaStack_{$varTableID}_dt", 
			$data_info['conditions']['columns'], 
			$data_info['columns']
		);
	}

	/**
	 * Build server-side DataTable configuration
	 *
	 * @param string $attr_id
	 * @param string $varTableID
	 * @param string $columns
	 * @param array $data_info
	 * @param string $current_url
	 * @param string $defaultConfig
	 * @param string|null $jsConditional
	 * @param mixed $filters
	 * @param mixed $custom_link
	 * @return string
	 */
	
	/**
	 * Build AJAX configuration for DataTables
	 *
	 * @param string $attr_id
	 * @param string $scriptURI
	 * @param boolean|array|null $filters
	 * @return string
	 */
	/**
	 * DEPRECATED: This method has been replaced by buildAjaxConfigArray()
	 * @see buildAjaxConfigArray()
	 * 
	 * @deprecated Use buildAjaxConfigArray() instead
	 * @return string Empty string
	 */
	private function buildAjaxConfig(string $attr_id, string $scriptURI, bool|array|null $filters): string {
		// Replaced by buildAjaxConfigArray()
		return '';
	}
	
	/**
	 * Build client-side DataTable configuration
	 *
	 * @param string $attr_id
	 * @param string $varTableID
	 * @param string $columns
	 * @param string $defaultConfig
	 * @return string
	 */
	
	/**
	 * Build document load script
	 *
	 * @param string $attr_id
	 * @param boolean|array|null $filters
	 * @param array $data_info
	 * @param string $current_url
	 * @return string
	 */
	private function buildDocumentLoadScript(string $attr_id, bool|array|null $filters, array $data_info, string $current_url): string {
		$varTableID = $this->sanitizeTableId($attr_id);
		$filterJs   = '';
		
		// PENTING: Check harus dilakukan SEBELUM normalize filters
		if (false !== $filters) {
			// Validate data_info has name
			$dataName = $data_info['name'] ?? 'unknown';
			
			$diftaParams = http_build_query([
				'difta' => [
					'name' => $dataName,
					'source' => 'dynamics'
				]
			]);
			
			// ✅ FIX: Proper URL building - check if query string exists
			$separator = strpos($current_url, '?') !== false ? '&' : '?';
			$scriptURI = "{$current_url}{$separator}renderDataTables=true&{$diftaParams}";
			
			// Normalize filters (dilakukan SETELAH check)
			if (is_array($filters) && empty($filters)) {
				$filters = null;
			}
			
			$filterJs = $this->filter($attr_id, $scriptURI);
			
			$exportParams = http_build_query([
				'exportDataTables' => 'true',
				'difta' => [
					'name' => $dataName,
					'source' => 'dynamics'
				]
			]);
			$exportURI = route('ajax.export') . "?{$exportParams}";
			$connection = !empty($this->connection) ? "::{$this->connection}" : '';
			$filterJs .= '; ' . $this->export($attr_id . $connection, $exportURI);
		}
		
		$jsOrder = '';
		$hiddenColumn = '';
		$fixedColumn  = "$('.dtfc-fixed-left').last().addClass('last-of-scrool-column-table');";
		
		return "$(document).ready(function() { $('#{$attr_id}').wrap('<div class=\"canvastack-wrapper-table\"></div>'); {$filterJs} {$jsOrder} {$hiddenColumn} {$fixedColumn} });";
	}

	/**
	 * Get jQuery selector for contains/not contains match
	 *
	 * @param string $data
	 * @param string|null $match_contained
	 * @return string|null
	 */
	private function getJsContainMatch(string $data, ?string $match_contained = null): ?string {
		$isNegativeMatch = in_array($match_contained, ['!=', '!=='], true);
		$isPositiveMatch = in_array($match_contained, ['==', '==='], true);
		
		// Escape data untuk keamanan
		$safeData = addslashes($data);
		
		if ($isPositiveMatch) {
			return ":contains(\"{$safeData}\")";
		}
		
		if ($isNegativeMatch) {
			return ":not(:contains(\"{$safeData}\"))";
		}
		
		return null;
	}
	
	/**
	 * Build conditional columns JavaScript
	 *
	 * @param string $tableIdentity
	 * @param array $data
	 * @param array $columns
	 * @return string|null
	 */
	private function conditionalColumns(string $tableIdentity, array $data, array $columns): ?string {
		if (empty($data)) {
			return null;
		}
		
		$icols = array_flip($columns);
		$data = $this->mapColumnIndices($data, $icols);
		
		// Return just the function body (without leading comma and property name)
		// This will be wrapped in new Function() by external JS
		$js = "";
		
		foreach ($data as $condition) {
			if (empty($condition['logic_operator'])) {
				continue;
			}
			
			$js .= $this->buildConditionCheck($condition);
			$js .= $this->applyConditionAction($condition, $tableIdentity);
			$js .= "}";
		}
		
		return $js;
	}
	
	/**
	 * Map column names to their indices
	 *
	 * @param array $data
	 * @param array $icols
	 * @return array
	 */
	private function mapColumnIndices(array $data, array $icols): array {
		foreach ($data as $idx => $_data) {
			// Validate required keys
			if (!isset($_data['field_name'])) {
				continue;
			}
			
			$data[$idx]['node']['field_name'] = $icols[$_data['field_name']] ?? null;
			
			if (isset($_data['field_target']) && !empty($icols[$_data['field_target']])) {
				$data[$idx]['node']['field_target'] = $icols[$_data['field_target']];
			} else {
				$data[$idx]['node']['field_target'] = null;
			}
		}
		
		return $data;
	}
	
	/**
	 * Build condition check JavaScript
	 *
	 * @param array $condition
	 * @return string
	 */
	private function buildConditionCheck(array $condition): string {
		// Validate condition structure
		if (!isset($condition['field_name']) || !isset($condition['value'])) {
			return '';
		}
		
		$conditionValue = $condition['value'];
		
		if (canvastack_string_contained($condition['value'], '|')) {
			$conditionValue = explode('|', $condition['value']);
		}
		
		// Direct comparison operators
		if (in_array($condition['logic_operator'], ['=', '==', '===', '<', '<=', '>', '>='], true)) {
			// Escape value untuk keamanan
			$safeValue = addslashes($condition['value']);
			return "if (data.{$condition['field_name']} {$condition['logic_operator']} '{$safeValue}') {";
		}
		
		// LIKE / NOT LIKE operators
		$isNot = in_array($condition['logic_operator'], ['NOT LIKE'], true) ? '!' : '';
		$jsConds = [];
		
		if (is_array($conditionValue)) {
			foreach ($conditionValue as $condVal) {
				$safeCondVal = addslashes($condVal);
				$jsConds[] = "{$isNot}~data.{$condition['field_name']}.indexOf('{$safeCondVal}')";
			}
			$jsCond = implode(' && ', $jsConds);
		} else {
			$safeValue = addslashes($conditionValue);
			$jsCond = "{$isNot}~data.{$condition['field_name']}.indexOf('{$safeValue}')";
		}
		
		return "if ({$jsCond}) {";
	}

	/**
	 * Apply condition action to row/cell/column
	 *
	 * @param array $condition
	 * @param string $tableIdentity
	 * @return string
	 */
	private function applyConditionAction(array $condition, string $tableIdentity): string {
		$target = $condition['field_target'];
		
		if ('row' === $target) {
			return $this->applyRowAction($condition);
		}
		
		if ('cell' === $target) {
			return $this->applyCellAction($condition, $tableIdentity);
		}
		
		if ('column' === $target) {
			return $this->applyColumnAction($condition);
		}
		
		// Default case: target is specific field
		return $this->applyFieldTargetAction($condition, $tableIdentity);
	}
	
	/**
	 * Apply action to entire row
	 *
	 * @param array $condition
	 * @return string
	 */
	private function applyRowAction(array $condition): string {
		// Validate condition has required keys
		if (!isset($condition['rule']) || !isset($condition['action'])) {
			return '';
		}
		
		$safeRule = addslashes($condition['rule']);
		$safeAction = addslashes($condition['action']);
		return "$(row).children('td').css({'{$safeRule}': '{$safeAction}'});";
	}
	
	/**
	 * Apply action to specific cell
	 *
	 * @param array $condition
	 * @param string $tableIdentity
	 * @return string
	 */
	private function applyCellAction(array $condition, string $tableIdentity): string {
		$rule = $condition['rule'] ?? null;
		$fieldName = $condition['node']['field_name'] ?? null;
		
		if (null === $fieldName || null === $rule) {
			return '';
		}
		
		$cellSelector = "$(cells[\"{$fieldName}\"])";
		
		if ('prefix&suffix' === $rule) {
			// Validate action is array with 2 elements
			if (!is_array($condition['action']) || count($condition['action']) < 2) {
				return '';
			}
			$safePrefix = addslashes($condition['action'][0]);
			$safeSuffix = addslashes($condition['action'][1]);
			return "{$cellSelector}.text(\"{$safePrefix}\" + data.{$condition['field_name']} + \"{$safeSuffix}\");";
		}
		
		if ('prefix' === $rule) {
			$safeAction = addslashes($condition['action']);
			return "{$cellSelector}.text(\"{$safeAction}\" + data.{$condition['field_name']});";
		}
		
		if ('suffix' === $rule) {
			$safeAction = addslashes($condition['action']);
			return "{$cellSelector}.text(data.{$condition['field_name']} + \"{$safeAction}\");";
		}
		
		if ('replace' === $rule) {
			return $this->applyReplaceAction($cellSelector, $condition);
		}
		
		// Default CSS rule
		$safeRule = addslashes($condition['rule']);
		$safeAction = addslashes($condition['action']);
		return "{$cellSelector}.css({'{$safeRule}': '{$safeAction}'});";
	}
	
	/**
	 * Apply action to column
	 *
	 * @param array $condition
	 * @return string
	 */
	private function applyColumnAction(array $condition): string {
		$rule = $condition['rule'] ?? null;
		$fieldName = $condition['node']['field_name'] ?? null;
		
		if (null === $fieldName || null === $rule) {
			return '';
		}
		
		$cellSelector = "$(cells[\"{$fieldName}\"])";
		
		if ('prefix&suffix' === $rule) {
			// Validate action is array with 2 elements
			if (!is_array($condition['action']) || count($condition['action']) < 2) {
				return '';
			}
			$safePrefix = addslashes($condition['action'][0]);
			$safeSuffix = addslashes($condition['action'][1]);
			return "{$cellSelector}.text(\"{$safePrefix}\" + data.{$condition['field_name']} + \"{$safeSuffix}\");";
		}
		
		if ('prefix' === $rule) {
			$safeAction = addslashes($condition['action']);
			return "{$cellSelector}.text(\"{$safeAction}\" + data.{$condition['field_name']});";
		}
		
		if ('suffix' === $rule) {
			$safeAction = addslashes($condition['action']);
			return "{$cellSelector}.text(data.{$condition['field_name']} + \"{$safeAction}\");";
		}
		
		if ('replace' === $rule) {
			return $this->applyReplaceAction($cellSelector, $condition);
		}
		
		// Default CSS rule
		$safeRule = addslashes($condition['rule']);
		$safeAction = addslashes($condition['action']);
		return "{$cellSelector}.css({'{$safeRule}': '{$safeAction}'});";
	}

	/**
	 * Apply action to field target (non-row, non-cell, non-column)
	 *
	 * @param array $condition
	 * @param string $tableIdentity
	 * @return string
	 */
	private function applyFieldTargetAction(array $condition, string $tableIdentity): string {
		$fieldTarget = $condition['node']['field_target'] ?? null;
		$rule = $condition['rule'] ?? null;
		
		if (null === $fieldTarget || null === $rule) {
			return '';
		}
		
		$cellSelector = "$(cells[\"{$fieldTarget}\"])";
		
		if ('replace' === $rule) {
			$action = $condition['action'] ?? '';
			
			// Handle URL/AJAX button replacement
			if (canvastack_string_contained($action, 'url::') || canvastack_string_contained($action, 'ajax::')) {
				return $this->buildButtonReplacement($tableIdentity, $action, $fieldTarget);
			}
			
			// Handle type conversion
			return $this->applyReplaceAction($cellSelector, $condition);
		}
		
		// Default CSS rule (not prefix/suffix)
		if (!in_array($rule, ['prefix', 'suffix', 'prefix&suffix'], true)) {
			$safeRule = addslashes($condition['rule']);
			$safeAction = addslashes($condition['action'] ?? '');
			return "{$cellSelector}.css({'{$safeRule}': '{$safeAction}'});";
		}
		
		return '';
	}
	
	/**
	 * Apply replace action (integer, float, or text)
	 *
	 * @param string $cellSelector
	 * @param array $condition
	 * @return string
	 */
	private function applyReplaceAction(string $cellSelector, array $condition): string {
		$action = $condition['action'] ?? '';
		
		if ('integer' === $action) {
			return "{$cellSelector}.text(parseInt({$cellSelector}.text()));";
		}
		
		if ('float' === $action || canvastack_string_contained($action, 'float')) {
			$decimals = 2;
			
			if (canvastack_string_contained($action, '|')) {
				$condAcFloat = explode('|', $action);
				$decimals = intval($condAcFloat[1] ?? 2);
			}
			
			return "{$cellSelector}.text(parseFloat({$cellSelector}.text()).toFixed({$decimals}));";
		}
		
		// Text replacement - escape untuk keamanan
		$safeAction = addslashes($action);
		return "{$cellSelector}.text('{$safeAction}');";
	}
	
	/**
	 * Build button replacement JavaScript for URL/AJAX actions
	 *
	 * @param string $tableIdentity
	 * @param string $action
	 * @param string $fieldTarget
	 * @return string
	 */
	private function buildButtonReplacement(string $tableIdentity, string $action, string $fieldTarget): string {
		$tableIdentityParts = explode('_', $tableIdentity);
		$node_table = isset($tableIdentityParts[1]) ? $tableIdentityParts[1] : 'table';
		
		$node_buttons = explode('::', $action);
		if (count($node_buttons) < 2) {
			return '';
		}
		
		$action_buttons = explode('|', $node_buttons[1]);
		
		if (count($action_buttons) < 3) {
			trigger_error('buildButtonReplacement(): Invalid action format, expected 3 parts', E_USER_WARNING);
			return '';
		}
		
		$button = [
			'name'  => $action_buttons[0],
			'class' => "btn {$action_buttons[0]} btn-{$action_buttons[1]} btn-xs",
			'icon'  => "fa fa-{$action_buttons[2]}",
			'token' => csrf_token()
		];
		
		$js = "$(cells[\"{$fieldTarget}\"]).each(function() {";
		$js .= "var anchorNode{$node_table} = $(this).children().find('.action-buttons').find('.{$button['name']}');";
		
		if ('ajax' === $node_buttons[0]) {
			$js .= $this->buildAjaxButtonHandler($node_table, $button, $tableIdentity);
		}
		
		$js .= "anchorNode{$node_table}.removeClass().addClass('{$button['class']}').find('i.fa').removeClass().addClass('{$button['icon']}');";
		$js .= "});";
		
		return $js;
	}
	
	/**
	 * Build AJAX button handler JavaScript
	 *
	 * @param string $node_table
	 * @param array $button
	 * @param string $tableIdentity
	 * @return string
	 */
	private function buildAjaxButtonHandler(string $node_table, array $button, string $tableIdentity): string {
		// Escape token untuk keamanan
		$safeToken = addslashes($button['token']);
		
		$js = "var dataURLi{$node_table} = anchorNode{$node_table}.attr('href').split('/');";
		$js .= "var anchorValue{$node_table} = dataURLi{$node_table}[dataURLi{$node_table}.length-2];";
		$js .= "var dataValue{$node_table} = {'_token':'{$safeToken}',data:anchorValue{$node_table}};";
		$js .= "var anchorUrl{$node_table} = anchorNode{$node_table}.attr('href').replace(anchorValue{$node_table} + '/' + dataURLi{$node_table}[dataURLi{$node_table}.length-1], dataURLi{$node_table}[dataURLi{$node_table}.length-1]);";
		
		$js .= "anchorNode{$node_table}.removeAttr('href');";
		$js .= "anchorNode{$node_table}.click(function() {";
		$js .= "$.ajax({";
		$js .= "url: anchorUrl{$node_table},";
		$js .= "type: 'post',";
		$js .= "data: dataValue{$node_table},";
		$js .= "success: function (response) {";
		$js .= "{$tableIdentity}.draw();";
		$js .= "},";
		$js .= "error: function(jqXHR, textStatus, errorThrown) {";
		$js .= "console.log(textStatus, errorThrown);";
		$js .= "}";
		$js .= "});";
		$js .= "});";
		
		return $js;
	}

	/**
	 * Escape string for safe use in JavaScript (Advanced)
	 * Uses json_encode for proper escaping with special character handling
	 * 
	 * NOTE: For most cases, use addslashes() instead. This method is for complex scenarios.
	 * 
	 * Use addslashes() for:
	 * - Simple string values in JavaScript
	 * - CSS property names and values
	 * - URLs and identifiers
	 * 
	 * Use this method (escapeJsString) for:
	 * - Complex user-generated content with special characters
	 * - Data that may contain newlines, tabs, or control characters
	 * - When you need JSON-compatible escaping
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function escapeJsString(mixed $value): string {
		return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	}
	
	/**
	 * Build filter button HTML
	 *
	 * @param array $data
	 * @return string|false
	 */
	protected function filterButton(array $data) {
		if (empty($data['searchable'])) {
			return false;
		}
		
		if (!empty($data['searchable']['all::columns'])) {
			if (false === $data['searchable']['all::columns']) {
				return false;
			}
		}
		
		if (false !== $data['searchable'] && !empty($data['class'])) {
			$btn_class = $data['class'];
			if (empty($data['class'])) {
				$btn_class = 'btn btn-primary btn-flat btn-lg mt-3';
			}
			
			// Validate required fields
			if (empty($data['id']) || empty($data['button_label'])) {
				return false;
			}
			
			// Escape class and id attributes, but NOT button_label (contains HTML icons)
			$safeClass = htmlspecialchars($btn_class, ENT_QUOTES, 'UTF-8');
			$safeId = htmlspecialchars($data['id'], ENT_QUOTES, 'UTF-8');
			
			// Note: button_label may contain HTML (icons), so we don't escape it
			// Caller is responsible for sanitizing button_label if it comes from user input

			// Use Bootstrap 5 attributes for canvasign template, Bootstrap 4 for others
			$isBS5 = in_array(canvastack_current_template(), ['canvasign']);
			if ($isBS5) {
				return '<button type="button" class="' . $safeClass . ' ' . $safeId . '" data-bs-toggle="modal" data-bs-target="#' . $safeId . '_CanvaStackFILTER">' . $data['button_label'] . '</button>';
			}
			return '<button type="button" class="' . $safeClass . ' ' . $safeId . '" data-toggle="modal" data-target=".' . $safeId . '">' . $data['button_label'] . '</button>';
		}
		
		return false;
	}
	
	/**
	 * Build filter modal box HTML
	 *
	 * @param array $data
	 * @return string|false
	 */
	protected function filterModalbox(array $data) {
		if (empty($data['searchable'])) {
			return false;
		}
		
		if (!empty($data['searchable']['all::columns'])) {
			if (false === $data['searchable']['all::columns']) {
				return false;
			}
		}
		
		if (empty($data['modal_content']['html']) || empty($data['id'])) {
			return false;
		}
		
		$current_url = url(canvastack_current_route()->uri);
		$attributes = '';
		
		if (!empty($data['attributes'])) {
			foreach ($data['attributes'] as $key => $attr) {
				$safeKey = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
				$safeAttr = htmlspecialchars($attr, ENT_QUOTES, 'UTF-8');
				$attributes .= " {$safeKey}=\"{$safeAttr}\"";
			}
		}
		
		$title = $data['modal_title'] ?? '';
		$name = $data['modal_content']['name'] ?? '';
		$content = $data['modal_content']['html'] ?? '';
		
		// Escape untuk HTML attributes (id dan action URL)
		$safeId = htmlspecialchars($data['id'], ENT_QUOTES, 'UTF-8');
		$safeCurrentUrl = htmlspecialchars($current_url, ENT_QUOTES, 'UTF-8');
		$token = csrf_token();
		
		// Get filter form method from config (same as DataTables method)
		// This ensures filter form uses the same HTTP method as DataTables AJAX requests
		$filterMethod = strtoupper(config('canvastack.datatables.defaults.method', 'POST'));
		
		// Note: title dan name bisa berisi HTML (icons), jadi tidak di-escape
		// Sama seperti button_label, caller bertanggung jawab untuk sanitasi jika dari user input

		// Use Bootstrap 5 attributes for canvasign template, Bootstrap 4 for others
		$isBS5 = in_array(canvastack_current_template(), ['canvasign']);
		$dismissAttr   = $isBS5 ? 'data-bs-dismiss="modal"' : 'data-dismiss="modal"';
		$closeBtnClass = $isBS5 ? 'btn-close' : 'close';
		$closeBtnInner = $isBS5 ? '' : '<span aria-hidden="true">&times;</span>';

		$html  = '<div ' . $attributes . '>';
		$html .= '<div id="' . $safeId . '_CanvaStackFILTERFormBox" class="modal-dialog modal-lg" role="document">';
		$html .= '<form action="' . $safeCurrentUrl . '?renderDataTables=true&filters=true" method="' . $filterMethod . '" id="' . $safeId . '_CanvaStackFILTERForm" role="form">';
		$html .= '<div class="modal-content">';
		$html .= '<div id="' . $safeId . '_CanvaStackProcessing" class="dataTables_processing" style="display:none"></div>';
		$html .= '<div class="modal-header">';
		$html .= '<h5 class="modal-title" id="">' . $title . ' Data ' . $name . '</h5>';
		$html .= '<button type="button" class="' . $closeBtnClass . '" ' . $dismissAttr . ' aria-label="Close">' . $closeBtnInner . '</button>';
		$html .= '</div>';
		$html .= '<input type="hidden" name="_token" value="' . $token . '" />';
		$html .= $content; // Content should be pre-sanitized by caller
		$html .= '</div>';
		$html .= '</form>';
		$html .= '</div>';
		$html .= '</div>';
		
		return $html;
	}

	/**
	 * Build export functionality JavaScript
	 *
	 * @param string $id
	 * @param string $url
	 * @param string $type
	 * @param string $delimeter
	 * @return string
	 */
	private function export(string $id, string $url, string $type = 'csv', string $delimeter = '|'): string {
		$connection = null;
		
		if (canvastack_string_contained($id, '::')) {
			$stringID   = explode('::', $id);
			$id         = $stringID[0];
			$connection = canvastack_encrypt($stringID[1]);
		} else {
			// Use default connection if not specified
			$defaultConnection = config('database.default', 'mysql');
			$connection = canvastack_encrypt($defaultConnection);
		}
		
		$varTableID = $this->sanitizeTableId($id);
		$modalID    = "{$id}_CanvaStackFILTERmodalBOX";
		$filterID   = "{$id}_CanvaStackFILTER";
		$exportID   = 'export_' . str_replace('-', '_', $id) . '_CanvaStackFILTERField';
		$token      = csrf_token();
		
		$filters = [];
		if (!empty($this->conditions['where'])) {
			$filters = $this->conditions['where'];
		}
		$filter = json_encode($filters);
		
		// ✅ FIX: Escape JSON untuk JavaScript context to prevent injection
		$safeFilter = addslashes($filter);
		
		// Escape strings untuk JavaScript
		$safeModalId = addslashes($modalID);
		$safeExportId = addslashes($exportID);
		$safeFilterId = addslashes($filterID);
		$safeToken = addslashes($token);
		$safeUrl = addslashes($url);
		$safeConnection = addslashes($connection);
		
		return "exportFromModal('{$safeModalId}', '{$safeExportId}', '{$safeFilterId}', '{$safeToken}', '{$safeUrl}', '{$safeConnection}', JSON.parse('{$safeFilter}'));";
	}
	
	/**
	 * Build filter functionality JavaScript
	 *
	 * @param string $id
	 * @param string $url
	 * @return string
	 */
	private function filter(string $id, string $url): string {
		$varTableID = $this->sanitizeTableId($id);
		
		// Escape untuk JavaScript
		$safeId = addslashes($id);
		$safeUrl = addslashes($url);
		
		return "canvastackDataTableFilters('{$safeId}', '{$safeUrl}', CanvaStack_{$varTableID}_dt);";
	}
	
	/**
	 * Build initComplete callback for DataTables
	 *
	 * @param string $id
	 * @param boolean|string $location
	 * @return string
	 */
	/**
	 * DEPRECATED: This method has been replaced by buildInitCompleteConfig()
	 * @see buildInitCompleteConfig()
	 * 
	 * @deprecated Use buildInitCompleteConfig() instead
	 * @return string Empty string
	 */
	private function initComplete(string $id, bool|string $location = 'footer'): string {
		// Replaced by buildInitCompleteConfig()
		return '';
	}

	/** 
	 * Set Buttons configuration for DataTables
	 * 
	 * @param string $id
	 * @param array $button_sets
	 * @return string
	 * 
	 * @example
	 * $buttonset = '[
	 *   {
	 *     extend:"collection",
	 *     exportOptions:{columns:":visible:not(:last-child)"},
	 *     text:"<i class=\"fa fa-external-link\" aria-hidden=\"true\"></i> <u>E</u>xport",
	 *     buttons:[{text:"Excel",buttons:"excel"}, "csv", "pdf"],
	 *     key:{key:"e",altKey:true}
	 *   },
	 *   "copy",
	 *   "print"
	 * ]';
	 */
	private function setButtons(string $id, array $button_sets = []): string {
		if (empty($button_sets)) {
			return '[]';
		}
		
		$buttons = [];
		
		foreach ($button_sets as $button) {
			$button = trim($button);
			$options = [];
			
			if (canvastack_string_contained($button, '|')) {
				$splits = explode('|', $button);
				
				foreach ($splits as $split) {
					if (canvastack_string_contained($split, ':')) {
						$options[] = $split;
					} else {
						$button = $split;
					}
				}
			}
			
			$option = !empty($options) ? implode(',', $options) : '';
			$buttons[] = '{extend:"' . $button . '", ' . $option . '}';
		}
		
		return '[' . implode(',', $buttons) . ']';
	}
}
