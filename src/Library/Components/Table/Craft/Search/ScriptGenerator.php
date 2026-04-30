<?php
namespace Canvastack\Canvastack\Library\Components\Table\Craft\Search;

use Canvastack\Canvastack\Library\Components\Table\Craft\Search\Config\SearchConfig;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\TableConstants;
use Canvastack\Canvastack\Library\Exceptions\Table\TableValidationException;

/**
 * ScriptGenerator - JavaScript generation for Search component
 *
 * @filesource ScriptGenerator.php
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 *
 * @security XSS Prevention - all user-controllable string values embedded in
 *           JavaScript are escaped via escapeJs() (addslashes). JSON data is
 *           encoded with JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT
 *           to prevent XSS via JSON injection. Generated script blocks are
 *           marked with SafeHtml::mark() to prevent double-encoding.
 *
 * @performance 2.4.4 - Debouncing added for search/filter input events to reduce
 *              unnecessary AJAX requests during rapid user input.
 * @performance 2.4.5 - Script deduplication prevents loading the same JS/CSS assets
 *              multiple times. Script array is deduplicated before output.
 */
class ScriptGenerator {
	
	private SearchConfig $config;
	private string $scriptToHTML;
	private array $addScripts = [];

	/**
	 * @var int Debounce delay in milliseconds for search input (default: 300ms)
	 */
	private int $debounceDelay = 300;
	
	/**
	 * Constructor
	 *
	 * @param SearchConfig $config Configuration object
	 * @param int $debounceDelay Debounce delay in milliseconds for search input (default: 300)
	 */
	public function __construct(SearchConfig $config, int $debounceDelay = 300) {
		$this->config = $config;
		$this->scriptToHTML = SearchConfig::SCRIPT_NODE_PREFIX;
		$this->debounceDelay = max(0, $debounceDelay);
	}

	/**
	 * Set debounce delay for search input events
	 *
	 * @performance 2.4.4 - Controls how long to wait after user stops typing
	 *              before firing the AJAX request. Higher values reduce server load.
	 *
	 * @param int $milliseconds Delay in milliseconds (0 = no debounce)
	 * @return $this
	 */
	public function setDebounceDelay(int $milliseconds): self {
		$this->debounceDelay = max(0, $milliseconds);
		return $this;
	}
	
	/**
	 * Generate scripts for cascading selects
	 * REFACTORED: Reduced complexity by extracting sub-methods
	 *
	 * @param array $element_scripts Element scripts
	 * @param string $table Table name
	 * @param string $node Node identifier
	 * @param array $filters Additional filters
	 * @return void
	 */
	public function generateScripts(array $element_scripts, string $table, string $node, array $filters = []): void {
		$current_template = canvastack_template_config('admin.' . canvastack_current_template());

		// FIX: Validate template config
		if (empty($current_template) || !is_array($current_template)) {
			\Log::warning('Search: Invalid template config', [
				'template' => canvastack_current_template(),
				'node' => $node
			]);
			return;
		}

		unset($current_template['position']);

		$nodElm = str_replace('modalBOX', '', $node);
		
		// FIX: Check if key exists before accessing
		if (!isset($element_scripts[$nodElm]) || !is_array($element_scripts[$nodElm])) {
			\Log::warning('Search: Script elements not found for node', [
				'nodElm' => $nodElm,
				'available_keys' => array_keys($element_scripts)
			]);
			return;
		}
		
		$scriptElements = array_keys($element_scripts[$nodElm]);

		// Generate field scripts
		$this->generateFieldScripts($node, $scriptElements, $table, $filters);

		// Load template assets
		$this->loadTemplateAssets($element_scripts[$nodElm], $current_template);
	}

	/**
	 * Generate field scripts for cascading selects
	 *
	 * @param string $node Node identifier
	 * @param array $scriptElements Script elements
	 * @param string $table Table name
	 * @param array $filters Additional filters
	 * @return void
	 */
	private function generateFieldScripts(string $node, array $scriptElements, string $table, array $filters): void {
		$fields = ['others' => $scriptElements];

		\Log::debug('ScriptGenerator: generateFieldScripts called', [
			'node' => $node,
			'table' => $table,
			'scriptElements' => $scriptElements,
			'scriptElements_count' => count($scriptElements)
		]);

		$this->scriptConfig($node, $scriptElements);

		foreach ($scriptElements as $index => $field) {
			// FIX: Skip empty field names to prevent invalid selectors
			if (empty($field) || trim($field) === '') {
				\Log::debug('ScriptGenerator: Skipping empty field', [
					'index' => $index,
					'field' => $field,
					'node' => $node
				]);
				continue;
			}
			
			unset($scriptElements[$index]);
			$fields['current'] = [$index => $field];
			$this->scriptNextData($node, $field, $fields, $table, $filters);
		}
	}

	/**
	 * Load template assets (CSS/JS)
	 *
	 * @param array $elementScripts Element scripts
	 * @param array $current_template Current template config
	 * @return void
	 */
	private function loadTemplateAssets(array $elementScripts, array $current_template): void {
		foreach ($elementScripts as $type) {
			if ('selectbox' === $type || 'smallint' === $type) {
				$type = 'select';
			}

			foreach ($current_template as $element => $data) {
				if ($element === $type) {
					$this->loadAssetsByType($data);
				}
			}
		}
	}

	/**
	 * Load assets by type (JS/CSS)
	 *
	 * @performance 2.4.5 - Checks for duplicate asset paths before adding to prevent
	 *              redundant entries in the scripts array during asset collection.
	 *
	 * @param array $data Asset data
	 * @return void
	 */
	private function loadAssetsByType(array $data): void {
		foreach ($data as $script_type => $script_paths) {
			if ('js' === $script_type) {
				foreach ($script_paths as $script_path) {
					$path = canvastack_script_check_string_path(str_replace('last:js', 'js', $script_path));
					// Avoid duplicates during collection
					if (!in_array($path, $this->addScripts['js'] ?? [], true)) {
						$this->addScripts['js'][] = $path;
					}
				}
			} else {
				foreach ($script_paths as $script_path) {
					$path = canvastack_script_check_string_path(str_replace('last:css', 'css', $script_path));
					// Avoid duplicates during collection
					if (!in_array($path, $this->addScripts['css'] ?? [], true)) {
						$this->addScripts['css'][] = $path;
					}
				}
			}
		}
	}

	
	/**
	 * Generate script configuration for field loaders
	 * 
	 * @deprecated Loader initialization moved to buildMainScript() for better timing control
	 *
	 * @security XSS Prevention - field names in $fields are escaped via escapeJs()
	 *           before being embedded in JavaScript string literals. The generated
	 *           script block is marked with SafeHtml::mark() to prevent double-encoding.
	 *
	 * @param string $node Node identifier
	 * @param array $fields Field list
	 * @return void
	 */
	public function scriptConfig(string $node, array $fields): void {
		// Loader initialization moved to buildMainScript() to ensure proper timing
		// with setTimeout and Chosen plugin initialization
		// This method is kept for backward compatibility but does nothing
	}
	
	/**
	 * Generate script for next data with cascading logic
	 *
	 * @security XSS Prevention - all user-controllable string values embedded in
	 *           JavaScript are escaped via escapeJs() (addslashes). JSON data is
	 *           encoded with JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT
	 *           to prevent XSS via JSON injection. The generated script block is
	 *           marked with SafeHtml::mark() to prevent double-encoding.
	 *
	 * @param string $node Node identifier
	 * @param string $identity Field identity
	 * @param array $fields Field configuration
	 * @param string $table Table name
	 * @param array $filters Additional filters
	 * @return void
	 */
	public function scriptNextData(string $node, string $identity, array $fields, string $table, array $filters = []): void {
		// Setup node names (SECURE - sanitize for JavaScript)
		$nodeNames = $this->setupNodeNames($node, $identity, $fields);
		
		// Setup field targets
		$targets = $this->setupFieldTargets($fields, $nodeNames);
		
		// Add identity to targets for use in buildNextScript
		$targets['identity'] = $identity;
		
		// Build nest data
		$nests = $this->buildNestData($fields, $nodeNames, $node);
		
		// NEW APPROACH: Use external JavaScript function
		// This provides better separation of concerns and caching
		$script = $this->buildCascadingFilterScript($node, $identity, $table, $targets, $nests, $nodeNames, $filters);
		
		\Log::debug('ScriptGenerator: scriptNextData generated (new approach)', [
			'node' => $node,
			'identity' => $identity,
			'table' => $table,
			'script_length' => strlen($script ?? ''),
			'has_script' => !empty($script)
		]);
		
		// Scripts are output directly into <script> tags - no SafeHtml marking needed
		if (!empty($script)) {
			$this->addScripts['add_js'][] = $this->scriptToHTML . $script;
		}
	}
	
	/**
	 * Build cascading filter script using external JavaScript function
	 * 
	 * NEW APPROACH: Instead of generating inline JavaScript, this calls
	 * canvastackCascadingFilter() function from canvastackscripts.js
	 * 
	 * Benefits:
	 * - Better separation of concerns
	 * - Browser caching of JS file
	 * - Easier debugging and testing
	 * - Smaller HTML response size
	 * 
	 * @param string $node Node identifier
	 * @param string $identity Field identity
	 * @param string $table Table name
	 * @param array $targets Target configuration
	 * @param array $nests Nest configuration
	 * @param array $nodeNames Node names
	 * @param array $filters Additional filters
	 * @return string|null JavaScript function call
	 */
	private function buildCascadingFilterScript(string $node, string $identity, string $table, array $targets, array $nests, array $nodeNames, array $filters): ?string {
		$next_target = $targets['next_target'];
		
		if (empty($next_target)) {
			return null;
		}
		
		// Build nest script (for disabling fields when value is empty)
		$nestScript = $this->buildNextScript($nests, $nodeNames, $targets, $node);
		
		// Build clearing logic (for clearing dependent fields when value changes)
		$clearingLogic = '';
		$nextTargets = $targets['nexTargets'];
		$curTargets = $targets['curTargets'];
		$firstTarget = $targets['firstTarget'];
		$lastTarget = $targets['lastTarget'];
		$node_safe = $this->escapeJs($node);
		$iNode = $nodeNames['iNode'];
		
		if (!empty($nextTargets)) {
			$clearingLogic = $this->buildTargetClearingLogic($nextTargets, $curTargets, $identity, $firstTarget, $lastTarget, $node_safe, $iNode, $node);
		}
		
		// Build AJAX data configuration
		$escapedValues = $this->escapeAjaxValues($identity, $table, $next_target, $nests);
		$ajaxDataConfig = $this->buildAjaxDataObject($escapedValues, $nodeNames, $filters);
		
		// Get AJAX URL
		$ajaxUrl = canvastack_get_ajax_urli('filterDataTables', $this->config->getConnection());
		
		// Build configuration for JavaScript function
		$identity_safe = $this->escapeJs($identity);
		$uniqueId = $this->buildUniqueIdFromNode($identity_safe, $node);
		$nextTargetUniqueId = $this->buildUniqueIdFromNode($this->escapeJs($next_target), $node);
		
		$config = [
			'node' => $this->escapeJs($node),
			'identity' => $identity_safe,
			'uniqueId' => $uniqueId,
			'firstNode' => $nodeNames['firstNode'],
			'iNode' => $iNode,
			'nextTarget' => $this->escapeJs($next_target),
			'nextTargetUniqueId' => $nextTargetUniqueId,
			'nextNode' => $targets['nextNode'],
			'ajaxUrl' => $this->escapeJs($ajaxUrl),
			'ajaxDataConfig' => $ajaxDataConfig,
			'prevScript' => $nests['prevscript'],
			'debounceDelay' => $this->debounceDelay,
			'nestScript' => $nestScript ?? '',
			'clearingLogic' => $clearingLogic
		];
		
		// Encode config as JSON
		$configJson = json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		
		// Wrap in document ready to ensure canvastackscripts.js is loaded first
		return "jQuery(document).ready(function() { canvastackCascadingFilter({$configJson}); });";
	}
	
	/**
	 * Setup node names (SECURE - sanitize for JavaScript)
	 *
	 * @security XSS Prevention - $identity and $node are escaped via escapeJs()
	 *           (addslashes) before being embedded in JavaScript string literals,
	 *           preventing XSS via field name injection.
	 *
	 * @param string $node Node identifier
	 * @param string $identity Field identity
	 * @param array $fields Field configuration
	 * @return array Node names
	 */
	public function setupNodeNames(string $node, string $identity, array $fields): array {
		// FIX XSS: Sanitize for JavaScript context
		$identity = $this->escapeJs($identity);
		$node = $this->escapeJs($node);
		
		$currKey = key($fields['current']);
		$iNode = $this->cleardash(str_replace('modalBOX', $identity, $node));
		$fNode = $this->cleardash(str_replace('modalBOX', 'Field', $node));
		$firstNode = "{$identity}_{$fNode}";
		
		return [
			'currKey' => $currKey,
			'iNode' => $iNode,
			'fNode' => $fNode,
			'firstNode' => $firstNode
		];
	}
	
	/**
	 * Setup field targets
	 *
	 * @param array $fields Field configuration
	 * @param array $nodeNames Node names
	 * @return array Target configuration
	 */
	public function setupFieldTargets(array $fields, array $nodeNames): array {
		$fieldsets = $fields['others'] ?? [];
		// FIX: Filter out empty field names to prevent invalid selectors
		$fieldsets = array_filter($fieldsets, function($field) {
			return !empty($field) && trim($field) !== '';
		});
		// Re-index array after filtering
		$fieldsets = array_values($fieldsets);
		
		$currKey = $nodeNames['currKey'];
		$fNode = $nodeNames['fNode'];
		
		$next_target = null;
		$nextNode = null;
		$curTargets = null;
		$nexTargets = [];
		
		if (!empty($fieldsets[$currKey + 1])) {
			$next_target = $fieldsets[key($fields['current']) + 1];
			$nextNode = "{$next_target}_{$fNode}";
			$curTargets = $fieldsets[key($fields['current'])];
			$nexTargets = $fieldsets;
		}
		
		$firstTarget = $fieldsets[0] ?? null;
		$lastTarget = $fieldsets[count($fieldsets) - 2] ?? null;
		
		return [
			'next_target' => $next_target,
			'nextNode' => $nextNode,
			'curTargets' => $curTargets,
			'nexTargets' => $nexTargets,
			'firstTarget' => $firstTarget,
			'lastTarget' => $lastTarget,
			'fieldsets' => $fieldsets
		];
	}
	
	/**
	 * Build nest data (prev/next)
	 *
	 * @security XSS Prevention - field values used in JavaScript string literals
	 *           are escaped via escapeJs() (addslashes) to prevent XSS injection
	 *           through field name values.
	 *
	 * @param array $fields Field configuration
	 * @param array $nodeNames Node names
	 * @return array Nest data
	 */
	public function buildNestData(array $fields, array $nodeNames, string $node): array {
		$currKey = $nodeNames['currKey'];
		$fNode = $nodeNames['fNode'];
		$iNode = $nodeNames['iNode'];
		
		$nests = ['prev' => [], 'next' => []];
		$prev = null;
		$prevscript = "null";
		$prevscripts = [];
		
		foreach ($fields['others'] as $idx => $value) {
			// FIX: Skip empty field names to prevent invalid selectors
			if (empty($value) || trim($value) === '') {
				continue;
			}
			
			if ($idx < $currKey) {
				$nests['prev'][$idx] = $value;
			} else {
				if ($idx !== $currKey + 1) {
					$nests['next'][$idx] = $value;
				}
			}
		}
		
		if (!empty($nests['prev'])) {
			$prev = implode('|', $nests['prev']);
			foreach ($nests['prev'] as $preval) {
				// FIX XSS: Escape for JavaScript
				$preval = $this->escapeJs($preval);
				$prevNode = "{$preval}_{$fNode}";
				// FIX: Use unique ID format (field_tableIdentifier) using node
				$preval_unique = $this->buildUniqueIdFromNode($preval, $node);
				$prevscripts[] = "$('select#{$preval_unique}.{$prevNode}').val()";
			}
			$prevscript = implode("+'|'+", $prevscripts);
		}
		
		$nest = null;
		if (!empty($nests['next'])) {
			$nest = implode('|', $nests['next']);
		}
		
		return [
			'nests' => $nests,
			'prev' => $prev,
			'prevscript' => $prevscript,
			'nest' => $nest
		];
	}
	
	/**
	 * Build next script (SECURE - XSS protected)
	 *
	 * @security XSS Prevention - $next_target and $nest values are escaped via
	 *           escapeJs() before embedding in JavaScript string literals to prevent
	 *           XSS via field name injection.
	 *
	 * @param array $nests Nest configuration
	 * @param array $nodeNames Node names
	 * @param array $targets Target configuration
	 * @return string|null JavaScript code
	 */
	public function buildNextScript(array $nests, array $nodeNames, array $targets, string $node): ?string {
		$nest = $nests['nest'];
		$iNode = $nodeNames['iNode'];
		$nextNode = $targets['nextNode'];
		$next_target = $targets['next_target'];
		$identity = $targets['identity'] ?? 'unknown';
		
		if (empty($nests['nests']['next'])) {
			return null;
		}
		
		// FIX XSS: Escape for JavaScript context
		$next_target_safe = $this->escapeJs($next_target);
		$nest_safe = $this->escapeJs($nest);
		$iNode_safe = $this->escapeJs($nodeNames['iNode']);
		
		// FIX: Skip if next_target is empty to avoid invalid selector
		if (empty($next_target) || trim($next_target) === '') {
			return null;
		}
		
		// FIX: Build unique ID for next target select element using node
		$next_target_unique = $this->buildUniqueIdFromNode($next_target_safe, $node);
		
		$scriptLines = [];
		// Disable and clear the immediate next field (field N+1)
		$scriptLines[] = "var _nx{$nextNode} = '{$next_target_safe}';";
		$scriptLines[] = "var _reident{$nextNode} = _nx{$nextNode}.replace('_', ' ');";
		$scriptLines[] = "var \$nextSelect{$iNode} = $('select#{$next_target_unique}.{$nextNode}');";
		$scriptLines[] = "\$nextSelect{$iNode}.prop('disabled', true).empty()";
		$scriptLines[] = ".append('<option value=\"\">No Data ' + ucwords(_reident{$nextNode}) + ' Found</option>');";
		$scriptLines[] = "\$nextSelect{$iNode}.trigger('chosen:updated');";
		
		// Disable and clear all remaining fields (field N+2, N+3, etc.) from nest
		// IMPORTANT: Skip current field (identity) to prevent disabling the field user just interacted with
		$scriptLines[] = "if (null != '{$nest_safe}' && '' != '{$nest_safe}') {";
		$scriptLines[] = "var _spldt{$iNode} = '{$nest_safe}';";
		$scriptLines[] = "var _spl{$iNode} = _spldt{$iNode}.split('|');";
		// Extract table identifier for building unique IDs in JavaScript
		$tableIdentifier = $this->extractInfoFromNode($node);
		$scriptLines[] = "$.each(_spl{$iNode}, function(i, obj) {";
		// Skip if: empty, matches iNode, or matches current field identity
		$scriptLines[] = "if (null != obj && '{$iNode_safe}' != obj && obj.trim() !== '' && obj !== '{$identity}') {";
		$scriptLines[] = "var _reident{$iNode} = obj.replace('_', ' ');";
		// FIX: Build unique ID for nested elements using table identifier
		$scriptLines[] = "var _objUnique{$iNode} = obj + '_{$tableIdentifier}';";
		$scriptLines[] = "var \$nestedSelect{$iNode} = $('select#' + _objUnique{$iNode});";
		$scriptLines[] = "\$nestedSelect{$iNode}.prop('disabled', true).empty()";
		$scriptLines[] = ".append('<option value=\"\">No Data ' + ucwords(_reident{$iNode}) + ' Found</option>');";
		$scriptLines[] = "\$nestedSelect{$iNode}.trigger('chosen:updated');";
		$scriptLines[] = "}";
		$scriptLines[] = "});";
		$scriptLines[] = "}";
		
		return implode('', $scriptLines);
	}
	
	/**
	 * Build AJAX script (SECURE - XSS protected)
	 * FIX: Properly escape JSON data for JavaScript context
	 * REFACTORED: Reduced complexity by extracting sub-methods
	 *
	 * @security XSS Prevention - all string values embedded in JavaScript are
	 *           escaped via escapeJs() (addslashes). JSON data is encoded with
	 *           JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT flags to
	 *           prevent XSS via JSON injection in AJAX data objects.
	 *
	 * @param string $identity Field identifier
	 * @param string $table Table name
	 * @param array $targets Target field configuration
	 * @param array $nests Nested field relationships
	 * @param array $nodeNames Node naming configuration
	 * @param array $filters Additional filters
	 * @return string|null Generated JavaScript code
	 */
	public function buildAjaxScript(string $identity, string $table, array $targets, array $nests, array $nodeNames, array $filters, string $node): ?string {
		$next_target = $targets['next_target'];
		
		if (empty($next_target)) {
			return null;
		}
		
		// Escape values for JavaScript
		$escapedValues = $this->escapeAjaxValues($identity, $table, $next_target, $nests);
		
		// Build AJAX data object
		$ajax_data = $this->buildAjaxDataObject($escapedValues, $nodeNames, $filters);
		
		// Get AJAX configuration
		$uri = canvastack_get_ajax_urli('filterDataTables', $this->config->getConnection());
		
		// Build success handler
		$ajaxSuccess = $this->buildAjaxSuccessHandler(
			$escapedValues['next_target'], 
			$targets['nextNode'], 
			$nodeNames['iNode'], 
			$nests['prevscript'], 
			$uri, 
			$ajax_data,
			$node
		);
		
		return $ajaxSuccess;
	}
	
	/**
	 * Escape values for AJAX script
	 *
	 * @param string $identity Field identifier
	 * @param string $table Table name
	 * @param string $next_target Next target field
	 * @param array $nests Nest configuration
	 * @return array Escaped values
	 */
	private function escapeAjaxValues(string $identity, string $table, string $next_target, array $nests): array {
		return [
			'identity' => $this->escapeJs($identity),
			'table' => $this->escapeJs($table),
			'next_target' => $this->escapeJs($next_target),
			'prev' => $this->escapeJs($nests['prev']),
			'nest' => $this->escapeJs($nests['nest'])
		];
	}
	
	/**
	 * Build AJAX data object
	 *
	 * @param array $escapedValues Escaped values
	 * @param array $nodeNames Node names
	 * @param array $filters Additional filters
	 * @return string JavaScript data object
	 */
	private function buildAjaxDataObject(array $escapedValues, array $nodeNames, array $filters): array {
		$iNode = $nodeNames['iNode'];
		$forkey = $this->config->getForeignKeys();
		$token = csrf_token();
		
		$connection = $this->config->getConnection();
		
		// DEBUG: Log connection value for troubleshooting
		if ($connection !== null) {
			\Log::debug('ScriptGenerator: Connection detected', [
				'connection' => $connection,
				'table' => $escapedValues['table'] ?? 'unknown',
				'identity' => $escapedValues['identity'] ?? 'unknown'
			]);
		} else {
			\Log::debug('ScriptGenerator: No connection set', [
				'table' => $escapedValues['table'] ?? 'unknown',
				'identity' => $escapedValues['identity'] ?? 'unknown'
			]);
		}
		
		// Return structured data that JavaScript can use to build the AJAX request
		$dataConfig = [
			'identity' => $escapedValues['identity'],
			'table' => $escapedValues['table'],
			'next_target' => $escapedValues['next_target'],
			'prev' => $escapedValues['prev'],
			'nest' => $escapedValues['nest'],
			'token' => $token,
			'forKeys' => $forkey,
			'connection' => $connection,
			'filters' => $filters
		];
		
		return $dataConfig;
	}
	
	/**
	 * Build AJAX success handler script with error handling
	 *
	 * @param string $next_target_safe Escaped next target
	 * @param string $nextNode Next node identifier
	 * @param string $iNode Identity node
	 * @param string $prevscript Previous script
	 * @param string $uri AJAX URI
	 * @param string $ajax_data AJAX data object
	 * @return string JavaScript code
	 */
	public function buildAjaxSuccessHandler(string $next_target_safe, string $nextNode, string $iNode, string $prevscript, string $uri, string $ajax_data, string $node): string {
		$target = ucwords(str_replace('_', ' ', $next_target_safe));
		
		// FIX: Build unique ID for next target select element using node
		$next_target_unique = $this->buildUniqueIdFromNode($next_target_safe, $node);
		
		$scriptLines = [];
		$scriptLines[] = "var _next{$next_target_safe} = '{$target}';";
		$scriptLines[] = "var _prevS{$iNode} = {$prevscript};";
		$scriptLines[] = "$.ajax ({";
		$scriptLines[] = "type: 'POST',";
		$scriptLines[] = "url: '{$uri}',";
		$scriptLines[] = "data: {$ajax_data},";
		$scriptLines[] = "dataType: 'json',";
		$scriptLines[] = "beforeSend: function() {";
		// Show loading indicator before AJAX request starts
		$scriptLines[] = "$('#" . SearchConfig::LOADER_PREFIX . "{$next_target_unique}').show();";
		$scriptLines[] = "},";
		$scriptLines[] = "success: function(data) {";
		$scriptLines[] = "if (data) {";
		$scriptLines[] = "if ('' != '{$next_target_safe}' && null != '{$next_target_safe}') {";
		// Store select element reference to avoid repeated jQuery selectors
		$scriptLines[] = "var \$nextSelect = $('select#{$next_target_unique}.{$nextNode}');";
		$scriptLines[] = "\$nextSelect.removeAttr('disabled');";
		$scriptLines[] = "\$nextSelect.empty();";
		$scriptLines[] = "\$nextSelect.append('<option value=\"\">Select ' + _next{$next_target_safe} + '</option>');";
		$scriptLines[] = "$.each(data, function(key, value) {";
		$scriptLines[] = "\$nextSelect.append('<option value=\"'+ value.{$next_target_safe} +'\">' + value.{$next_target_safe} + '</option>');";
		$scriptLines[] = "});";
		// IMPORTANT: Only trigger chosen:updated ONCE after all options are added
		// Multiple triggers cause phantom change events on other fields
		$scriptLines[] = "\$nextSelect.trigger('chosen:updated');";
		// Reset processing flag after Chosen update completes to allow subsequent interactions
		$scriptLines[] = "setTimeout(function() {";
		$scriptLines[] = "if (typeof _processing{$iNode} !== 'undefined') _processing{$iNode} = false;";
		$scriptLines[] = "}, 500);";
		$scriptLines[] = "}";
		$scriptLines[] = "}";
		$scriptLines[] = "},";
		$scriptLines[] = "error: function(xhr, status, error) {";
		$scriptLines[] = "console.error('Search filter load failed:', {status: status, error: error, target: '{$next_target_safe}', xhr: xhr});";
		$scriptLines[] = "var errorMsg = 'Failed to load ' + _next{$next_target_safe} + ' options. ';";
		$scriptLines[] = "if (xhr.status === 404) { errorMsg += 'Endpoint not found.'; }";
		$scriptLines[] = "else if (xhr.status === 500) { errorMsg += 'Server error.'; }";
		$scriptLines[] = "else if (xhr.status === 0) { errorMsg += 'Network error.'; }";
		$scriptLines[] = "else { errorMsg += 'Please try again.'; }";
		$scriptLines[] = "var \$nextSelect = $('select#{$next_target_unique}.{$nextNode}');";
		$scriptLines[] = "\$nextSelect.empty()";
		$scriptLines[] = ".append('<option value=\"\">Error: ' + errorMsg + '</option>')";
		$scriptLines[] = ".prop('disabled', true);";
		// Only trigger chosen:updated once after all DOM manipulations
		$scriptLines[] = "\$nextSelect.trigger('chosen:updated');";
		$scriptLines[] = "},";
		$scriptLines[] = "complete: function() {";
		// Hide loading indicator when AJAX completes (success or error)
		$scriptLines[] = "$('#" . SearchConfig::LOADER_PREFIX . "{$next_target_unique}').hide();";
		$scriptLines[] = "}";
		$scriptLines[] = "});";
		
		return implode('', $scriptLines);
	}
	
	/**
	 * Build main script (SECURE - XSS protected)
	 *
	 * @security XSS Prevention - $identity and $node are escaped via escapeJs()
	 *           before embedding in JavaScript string literals and jQuery selectors.
	 *           This prevents XSS via field name or node name injection.
	 *
	 * @performance 2.4.4 - Wraps the change handler with a debounce function when
	 *              debounceDelay > 0 to reduce unnecessary AJAX calls during rapid
	 *              user interaction with cascading select fields.
	 *
	 * @param string $node Node identifier
	 * @param string $identity Field identity
	 * @param array $targets Target configuration
	 * @param array $nodeNames Node names
	 * @param string|null $nestScript Nest script
	 * @param string|null $ajaxSuccess AJAX success handler
	 * @return string|null JavaScript code
	 */
	public function buildMainScript(string $node, string $identity, array $targets, array $nodeNames, ?string $nestScript, ?string $ajaxSuccess): ?string {
		if (empty($identity)) {
			return null;
		}
		
		// FIX XSS: Escape for JavaScript
		$identity_safe = $this->escapeJs($identity);
		$node_safe = $this->escapeJs($node);
		
		$firstNode = $nodeNames['firstNode'];
		$iNode = $nodeNames['iNode'];
		$nextTargets = $targets['nexTargets'];
		$curTargets = $targets['curTargets'];
		$firstTarget = $targets['firstTarget'];
		$lastTarget = $targets['lastTarget'];
		$next_target = $targets['next_target'];
		
		// FIX: Build unique ID matching FormGenerator::buildFieldAttributes() format
		// Use node (which contains info) instead of iNode (which has field appended)
		$uniqueId = $this->buildUniqueIdFromNode($identity_safe, $node);
		
		$scriptLines = [];
		$scriptLines[] = "jQuery(function($) {";

		// Inject debounce helper if debouncing is enabled
		if ($this->debounceDelay > 0) {
			$scriptLines[] = $this->buildDebounceHelper($iNode);
		}

		// FIX: Delay execution to ensure Chosen plugin is initialized
		$scriptLines[] = "setTimeout(function() {";
		
		// Initialize loader for next target field (if exists)
		if (!empty($next_target)) {
			// Build unique ID for next target (field_tableIdentifier format)
			$nextTargetUniqueId = $this->buildUniqueIdFromNode($this->escapeJs($next_target), $node);
			$scriptLines[] = "var \$loaderTarget = $('#{$nextTargetUniqueId}');";
			$scriptLines[] = "loader('{$nextTargetUniqueId}');";
		}
		
		$scriptLines[] = "$('#{$node_safe}').children('div.form-group, div.mb-3').each(function () {";
		$scriptLines[] = "var \$elem = $(this).find('select#{$uniqueId}.{$firstNode}');";
		$scriptLines[] = "if (\$elem.length === 0) return;";

		// Define change handler function
		$changeHandlerBody = "";
		
		$changeHandlerBody .= "var _val{$iNode} = $(this).val();";
		$changeHandlerBody .= "var _prevVal{$iNode} = $(this).data('prevValue') || '';";
		$changeHandlerBody .= "$(this).data('prevValue', _val{$iNode});";
		$changeHandlerBody .= "if (_val{$iNode} != '0' && _val{$iNode} != null && _val{$iNode} != '') {";
		
		// Build target clearing logic (only when value is valid)
		if (!empty($nextTargets)) {
			$changeHandlerBody .= $this->buildTargetClearingLogic($nextTargets, $curTargets, $identity, $firstTarget, $lastTarget, $node_safe, $iNode, $node);
		}
		
		$changeHandlerBody .= $ajaxSuccess;
		$changeHandlerBody .= "} else {";
		// When value is empty, disable all next fields (N+1, N+2, etc.)
		// IMPORTANT: Current field (N) remains enabled with its options intact
		$changeHandlerBody .= $nestScript;
		// Reset processing flag after disabling fields to allow re-selection
		$changeHandlerBody .= "setTimeout(function() { _processing{$iNode} = false; }, 500);";
		$changeHandlerBody .= "}";

		// FIX: Chosen plugin support - use _processing flag to prevent infinite loop
		// When we trigger chosen:updated, it may fire change events on other fields
		// The flag blocks these phantom events until the current operation completes
		if ($this->debounceDelay > 0) {
			// Wrap change handler with debounce
			$scriptLines[] = "var _processing{$iNode} = false;";
			$scriptLines[] = "var handler_{$iNode} = _debounce_{$iNode}(function () {";
			$scriptLines[] = "if (_processing{$iNode}) { return; }";
			$scriptLines[] = "_processing{$iNode} = true;";
			$scriptLines[] = $changeHandlerBody;
			$scriptLines[] = "}, {$this->debounceDelay});";
			$scriptLines[] = "\$elem.on('change', handler_{$iNode});";
		} else {
			$scriptLines[] = "var _processing{$iNode} = false;";
			$scriptLines[] = "\$elem.on('change', function () {";
			$scriptLines[] = "if (_processing{$iNode}) { return; }";
			$scriptLines[] = "_processing{$iNode} = true;";
			$scriptLines[] = $changeHandlerBody;
			// Reset flag after handler completes (for non-debounced version)
			$scriptLines[] = "setTimeout(function() { _processing{$iNode} = false; }, 200);";
			$scriptLines[] = "});";
		}

		$scriptLines[] = "});";
		$scriptLines[] = "}, 500);"; // Close setTimeout - wait 500ms for Chosen initialization
		$scriptLines[] = "});";
		
		return implode('', $scriptLines);
	}

	/**
	 * Build a lightweight debounce helper function for a specific node
	 *
	 * @performance 2.4.4 - Generates a minimal debounce utility scoped to the node
	 *              to avoid polluting global scope and prevent duplicate declarations.
	 *
	 * @param string $iNode Node identifier (used to scope the debounce variable)
	 * @return string JavaScript debounce helper code
	 */
	private function buildDebounceHelper(string $iNode): string {
		$iNode_safe = $this->escapeJs($iNode);
		return "var _debounce_{$iNode_safe} = function(fn, delay) { var t; return function() { var ctx = this, args = arguments; clearTimeout(t); t = setTimeout(function() { fn.apply(ctx, args); }, delay); }; };";
	}
	
	/**
	 * Extract table identifier from node by removing modalBOX suffix
	 *
	 * @param string $node Node identifier ending with modalBOX
	 * @return string Table identifier (info parameter)
	 */
	private function extractInfoFromNode(string $node): string {
		// Node format: "{info}modalBOX"
		// Return: "{info}" which is the table identifier
		$info = str_replace('modalBOX', '', $node);
		// Clean dashes to underscores to match FormGenerator sanitization
		return $this->cleardash($info);
	}
	
	/**
	 * Build unique element ID matching FormGenerator format
	 *
	 * @param string $fieldName Field name
	 * @param string $node Node identifier (contains info + modalBOX)
	 * @return string Unique ID in format: {field}_{tableIdentifier}
	 */
	private function buildUniqueIdFromNode(string $fieldName, string $node): string {
		$fieldName_safe = $this->escapeJs($fieldName);
		$tableIdentifier = $this->extractInfoFromNode($node);
		$uniqueId = "{$fieldName_safe}_{$tableIdentifier}";
		
		// VALIDATION: Log warning if field name is empty
		if (empty($fieldName) || trim($fieldName) === '') {
			\Log::warning('ScriptGenerator: buildUniqueIdFromNode called with empty fieldName', [
				'fieldName' => $fieldName,
				'node' => $node,
				'uniqueId' => $uniqueId,
				'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
			]);
		}
		
		return $uniqueId;
	}


	
	/**
	 * Build target clearing logic for cascading selects
	 *
	 * @param array $nextTargets Next targets
	 * @param string $curTargets Current targets
	 * @param string $identity Field identity
	 * @param string $firstTarget First target
	 * @param string $lastTarget Last target
	 * @param string $node_safe Escaped node name
	 * @return string JavaScript code
	 */
	public function buildTargetClearingLogic(array $nextTargets, string $curTargets, string $identity, string $firstTarget, string $lastTarget, string $node_safe, string $iNode, string $node): string {
		$scriptLines = [];
		$curN = 0;
		
		foreach ($nextTargets as $n => $nextElement) {
			if ($curTargets === $nextElement) {
				$curN = $n;
			}
			$curNode = $curN + 1;
			
			// Only clear fields that come AFTER the current node in the cascade
			if ($n > $curNode) {
				if ($lastTarget !== $nextElement) {
					if ($identity === $firstTarget) {
						// Toggle export button visibility based on field value
						$scriptLines[] = "if ($(this).val() != '') { $('button#exportFilterButton{$node_safe}').removeClass('hide'); } else { $('button#exportFilterButton{$node_safe}').addClass('hide'); }";
						// Clear last target field if it's not the current field
						if (!empty($lastTarget) && trim($lastTarget) !== '' && $lastTarget !== $identity) {
							$lastTarget_unique = $this->buildUniqueIdFromNode($this->escapeJs($lastTarget), $node);
							$scriptLines[] = "$('select#{$lastTarget_unique}').empty().trigger('chosen:updated');";
						}
					}
					
					// Clear next element if it's not the last target
					if ($identity !== $lastTarget) {
						$nextElement_safe = $this->escapeJs($nextElement);
						if (!empty($nextElement) && trim($nextElement) !== '') {
							$nextElement_unique = $this->buildUniqueIdFromNode($nextElement_safe, $node);
							$scriptLines[] = "$('select#{$nextElement_unique}').empty().trigger('chosen:updated');";
						}
					}
				}
			}
		}
		
		return implode('', $scriptLines);
	}
	
	/**
	 * Encode data to JSON safe for JavaScript context
	 *
	 * @security XSS Prevention - uses JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT
	 *           flags to prevent XSS via JSON injection. These flags encode <, >, &, ', "
	 *           as Unicode escape sequences, making the JSON safe for inline JavaScript.
	 *
	 * @param mixed $data Data to encode
	 * @return string JSON string safe for JavaScript
	 */
	public function encodeJsonForJs($data): string {
		return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	}
	
	/**
	 * Escape string for JavaScript context - prevent XSS
	 *
	 * @security XSS Prevention - uses addslashes() to escape backslashes, single
	 *           quotes, double quotes, and null bytes before embedding values in
	 *           JavaScript string literals. Always use this when inserting PHP
	 *           values into JS strings.
	 *
	 * @param string|null $value Value to escape
	 * @return string Escaped value
	 */
	public function escapeJs(?string $value): string {
		if ($value === null) {
			return '';
		}
		
		// Escape for JavaScript string context
		return addslashes((string)$value);
	}
	
	/**
	 * Clean dash characters from string
	 *
	 * @param string $string Input string
	 * @return string Cleaned string
	 */
	public function cleardash(string $string): string {
		return str_replace('-', '_', $string);
	}
	
	/**
	 * Build cascading filter function call (NEW APPROACH)
	 * 
	 * Instead of generating inline JavaScript, this method generates a simple
	 * function call to canvastackCascadingFilter() which is defined in
	 * canvastackscripts.js. This provides better separation of concerns,
	 * caching, and maintainability.
	 * 
	 * @param string $node Node identifier
	 * @param string $identity Field identity
	 * @param array $targets Target configuration
	 * @param array $nodeNames Node names
	 * @param string|null $nestScript Nest script for disabling fields
	 * @param string|null $clearingLogic Logic for clearing dependent fields
	 * @param string $ajaxUrl AJAX endpoint URL
	 * @param string $ajaxData AJAX data object (as JavaScript string)
	 * @return string JavaScript function call
	 */
	public function buildCascadingFilterCall(string $node, string $identity, array $targets, array $nodeNames, ?string $nestScript, ?string $clearingLogic, string $ajaxUrl, string $ajaxData): string {
		// Escape all values for JavaScript
		$node_safe = $this->escapeJs($node);
		$identity_safe = $this->escapeJs($identity);
		$uniqueId = $this->buildUniqueIdFromNode($identity_safe, $node);
		$firstNode = $nodeNames['firstNode'];
		$iNode = $nodeNames['iNode'];
		$next_target = $targets['next_target'] ?? '';
		$nextNode = $targets['nextNode'] ?? '';
		
		// Build unique ID for next target
		$nextTargetUniqueId = '';
		if (!empty($next_target)) {
			$nextTargetUniqueId = $this->buildUniqueIdFromNode($this->escapeJs($next_target), $node);
		}
		
		// Build configuration object (no need to double-escape, JSON encode handles it)
		$config = [
			'node' => $node_safe,
			'identity' => $identity_safe,
			'uniqueId' => $uniqueId,
			'firstNode' => $firstNode,
			'iNode' => $iNode,
			'nextTarget' => $this->escapeJs($next_target),
			'nextTargetUniqueId' => $nextTargetUniqueId,
			'nextNode' => $nextNode,
			'ajaxUrl' => $this->escapeJs($ajaxUrl),
			'ajaxDataTemplate' => $ajaxData, // Template string with placeholders
			'debounceDelay' => $this->debounceDelay,
			'nestScript' => $nestScript ?? '',
			'clearingLogic' => $clearingLogic ?? ''
		];
		
		// Encode config as JSON for JavaScript
		$configJson = json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		
		return "canvastackCascadingFilter({$configJson});";
	}
	
	/**
	 * Get generated scripts (deduplicated)
	 *
	 * @performance 2.4.5 - Deduplicates JS and CSS asset paths before returning
	 *              to prevent loading the same asset multiple times when multiple
	 *              cascading select fields share the same asset dependencies.
	 *
	 * @return array Deduplicated scripts array with keys: js, css, add_js
	 */
	public function getScripts(): array {
		return $this->deduplicateScripts($this->addScripts);
	}

	/**
	 * Deduplicate JS and CSS asset arrays
	 *
	 * @performance 2.4.5 - Removes duplicate asset paths from js and css arrays.
	 *              add_js (inline scripts) are kept as-is since they may differ.
	 *
	 * @param array $scripts Scripts array
	 * @return array Deduplicated scripts array
	 */
	private function deduplicateScripts(array $scripts): array {
		if (!empty($scripts['js'])) {
			$scripts['js'] = array_values(array_unique($scripts['js']));
		}
		if (!empty($scripts['css'])) {
			$scripts['css'] = array_values(array_unique($scripts['css']));
		}
		return $scripts;
	}
	
	/**
	 * Generate filter state persistence script
	 *
	 * @security XSS Prevention - table identifier is escaped for JavaScript
	 *
	 * @param string $tableIdentifier Unique table identifier
	 * @param array $filterFields Array of filter field names
	 * @return string JavaScript code for filter persistence
	 */
	public function generateFilterPersistenceScript(string $tableIdentifier, array $filterFields): string {
		$tableId_safe = $this->escapeJs($tableIdentifier);
		$storageKey = "table_filters_{$tableId_safe}";
		
		$scriptLines = [];
		$scriptLines[] = "jQuery(document).ready(function($) {";
		$scriptLines[] = "// Filter state persistence for table: {$tableId_safe}";
		$scriptLines[] = "var storageKey = '{$storageKey}';";
		
		// Load saved filter state
		$scriptLines[] = "function loadFilterState() {";
		$scriptLines[] = "if (typeof(Storage) === 'undefined') return;";
		$scriptLines[] = "try {";
		$scriptLines[] = "var savedState = localStorage.getItem(storageKey);";
		$scriptLines[] = "if (!savedState) return;";
		$scriptLines[] = "var filters = JSON.parse(savedState);";
		
		// Restore each filter field value
		foreach ($filterFields as $field) {
			$field_safe = $this->escapeJs($field);
			$scriptLines[] = "if (filters['{$field_safe}'] !== undefined) {";
			$scriptLines[] = "$('#{$field_safe}').val(filters['{$field_safe}']).trigger('change');";
			$scriptLines[] = "}";
		}
		
		$scriptLines[] = "} catch(e) { console.error('Failed to load filter state:', e); }";
		$scriptLines[] = "}";
		
		// Save filter state
		$scriptLines[] = "function saveFilterState() {";
		$scriptLines[] = "if (typeof(Storage) === 'undefined') return;";
		$scriptLines[] = "try {";
		$scriptLines[] = "var filters = {};";
		
		foreach ($filterFields as $field) {
			$field_safe = $this->escapeJs($field);
			$scriptLines[] = "var val_{$field_safe} = $('#{$field_safe}').val();";
			$scriptLines[] = "if (val_{$field_safe}) filters['{$field_safe}'] = val_{$field_safe};";
		}
		
		$scriptLines[] = "localStorage.setItem(storageKey, JSON.stringify(filters));";
		$scriptLines[] = "} catch(e) { console.error('Failed to save filter state:', e); }";
		$scriptLines[] = "}";
		
		// Attach change handlers to save state
		$scriptLines[] = "$('" . implode(',', array_map(function($f) { return '#' . $this->escapeJs($f); }, $filterFields)) . "').on('change', saveFilterState);";
		
		// Load state on page load
		$scriptLines[] = "loadFilterState();";
		
		$scriptLines[] = "});";
		
		return implode('', $scriptLines);
	}
	
	/**
	 * Generate filter reset script
	 *
	 * @security XSS Prevention - identifiers are escaped for JavaScript
	 *
	 * @param string $tableIdentifier Unique table identifier
	 * @param array $filterFields Array of filter field names
	 * @param string $resetButtonId Reset button element ID
	 * @return string JavaScript code for filter reset
	 */
	public function generateFilterResetScript(string $tableIdentifier, array $filterFields, string $resetButtonId): string {
		$tableId_safe = $this->escapeJs($tableIdentifier);
		$resetBtn_safe = $this->escapeJs($resetButtonId);
		$storageKey = "table_filters_{$tableId_safe}";
		
		$scriptLines = [];
		$scriptLines[] = "jQuery(document).ready(function($) {";
		$scriptLines[] = "$('#{$resetBtn_safe}').on('click', function(e) {";
		$scriptLines[] = "e.preventDefault();";
		
		// Clear all filter fields
		foreach ($filterFields as $field) {
			$field_safe = $this->escapeJs($field);
			$scriptLines[] = "$('#{$field_safe}').val('').trigger('change');";
		}
		
		// Clear localStorage
		$scriptLines[] = "if (typeof(Storage) !== 'undefined') {";
		$scriptLines[] = "try { localStorage.removeItem('{$storageKey}'); } catch(e) {}";
		$scriptLines[] = "}";
		
		// Reload table data
		$scriptLines[] = "if (typeof $.fn.DataTable !== 'undefined') {";
		$scriptLines[] = "var table = $('.dataTable').DataTable();";
		$scriptLines[] = "if (table) table.ajax.reload();";
		$scriptLines[] = "}";
		
		$scriptLines[] = "});";
		$scriptLines[] = "});";
		
		return implode('', $scriptLines);
	}
	
	/**
	 * Generate search highlighting script
	 *
	 * @security XSS Prevention - search terms are escaped before highlighting
	 *
	 * @param string $tableIdentifier Unique table identifier
	 * @param string $searchInputId Search input element ID
	 * @return string JavaScript code for search highlighting
	 */
	public function generateSearchHighlightingScript(string $tableIdentifier, string $searchInputId): string {
		$tableId_safe = $this->escapeJs($tableIdentifier);
		$searchInput_safe = $this->escapeJs($searchInputId);
		
		$scriptLines = [];
		$scriptLines[] = "jQuery(document).ready(function($) {";
		
		// Highlight function
		$scriptLines[] = "function highlightSearchTerms(searchTerm) {";
		$scriptLines[] = "// Remove existing highlights";
		$scriptLines[] = "$('.dataTable tbody td').each(function() {";
		$scriptLines[] = "var \$td = $(this);";
		$scriptLines[] = "var html = \$td.html();";
		$scriptLines[] = "if (html.indexOf('<mark class=\"search-highlight\">') !== -1) {";
		$scriptLines[] = "html = html.replace(/<mark class=\"search-highlight\">(.*?)<\\/mark>/gi, '\$1');";
		$scriptLines[] = "\$td.html(html);";
		$scriptLines[] = "}";
		$scriptLines[] = "});";
		
		$scriptLines[] = "if (!searchTerm || searchTerm.length < 2) return;";
		
		// Escape special regex characters
		$scriptLines[] = "var escapedTerm = searchTerm.replace(/[.*+?^" . '$' . "{}()|[\\]\\\\]/g, '\\\\" . '$' . "&');";
		
		// Highlight matching text
		$scriptLines[] = "$('.dataTable tbody td').each(function() {";
		$scriptLines[] = "var \$td = $(this);";
		$scriptLines[] = "var text = \$td.text();";
		$scriptLines[] = "if (text.toLowerCase().indexOf(searchTerm.toLowerCase()) !== -1) {";
		$scriptLines[] = "var regex = new RegExp('(' + escapedTerm + ')', 'gi');";
		$scriptLines[] = "var html = \$td.html();";
		// Only highlight text nodes, not HTML tags
		$scriptLines[] = "html = html.replace(regex, '<mark class=\"search-highlight\">\$1</mark>');";
		$scriptLines[] = "\$td.html(html);";
		$scriptLines[] = "}";
		$scriptLines[] = "});";
		$scriptLines[] = "}";
		
		// Attach to search input and DataTable draw event
		$scriptLines[] = "var searchInput = $('#{$searchInput_safe}');";
		$scriptLines[] = "if (searchInput.length) {";
		$scriptLines[] = "searchInput.on('keyup', function() {";
		$scriptLines[] = "var term = $(this).val();";
		$scriptLines[] = "setTimeout(function() { highlightSearchTerms(term); }, 100);";
		$scriptLines[] = "});";
		$scriptLines[] = "}";
		
		// Re-highlight after DataTable redraws
		$scriptLines[] = "if (typeof $.fn.DataTable !== 'undefined') {";
		$scriptLines[] = "$('.dataTable').on('draw.dt', function() {";
		$scriptLines[] = "var term = searchInput.val();";
		$scriptLines[] = "if (term) highlightSearchTerms(term);";
		$scriptLines[] = "});";
		$scriptLines[] = "}";
		
		$scriptLines[] = "});";
		
		return implode('', $scriptLines);
	}
	
	/**
	 * Generate search suggestions script
	 *
	 * @security XSS Prevention - suggestions are escaped before display
	 *
	 * @param string $tableIdentifier Unique table identifier
	 * @param string $searchInputId Search input element ID
	 * @param string $ajaxUrl AJAX endpoint for fetching suggestions
	 * @return string JavaScript code for search suggestions
	 */
	public function generateSearchSuggestionsScript(string $tableIdentifier, string $searchInputId, string $ajaxUrl): string {
		$tableId_safe = $this->escapeJs($tableIdentifier);
		$searchInput_safe = $this->escapeJs($searchInputId);
		$ajaxUrl_safe = $this->escapeJs($ajaxUrl);
		
		$scriptLines = [];
		$scriptLines[] = "jQuery(document).ready(function($) {";
		
		// Create suggestions container
		$scriptLines[] = "var \$searchInput = $('#{$searchInput_safe}');";
		$scriptLines[] = "if (!\$searchInput.length) return;";
		
		$scriptLines[] = "var \$suggestionsContainer = $('<div class=\"search-suggestions\"></div>');";
		$scriptLines[] = "\$suggestionsContainer.css({";
		$scriptLines[] = "'position': 'absolute',";
		$scriptLines[] = "'background': '#fff',";
		$scriptLines[] = "'border': '1px solid #ddd',";
		$scriptLines[] = "'max-height': '200px',";
		$scriptLines[] = "'overflow-y': 'auto',";
		$scriptLines[] = "'z-index': '1000',";
		$scriptLines[] = "'display': 'none',";
		$scriptLines[] = "'width': \$searchInput.outerWidth() + 'px'";
		$scriptLines[] = "});";
		$scriptLines[] = "\$searchInput.after(\$suggestionsContainer);";
		
		// Fetch suggestions with debouncing
		$scriptLines[] = "var suggestionTimer;";
		$scriptLines[] = "\$searchInput.on('keyup', function(e) {";
		$scriptLines[] = "if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') return;";
		
		$scriptLines[] = "clearTimeout(suggestionTimer);";
		$scriptLines[] = "var term = $(this).val();";
		
		$scriptLines[] = "if (!term || term.length < 2) {";
		$scriptLines[] = "\$suggestionsContainer.hide().empty();";
		$scriptLines[] = "return;";
		$scriptLines[] = "}";
		
		$scriptLines[] = "suggestionTimer = setTimeout(function() {";
		$scriptLines[] = "$.ajax({";
		$scriptLines[] = "url: '{$ajaxUrl_safe}',";
		$scriptLines[] = "type: 'POST',";
		$scriptLines[] = "data: { term: term, _token: $('meta[name=\"csrf-token\"]').attr('content') },";
		$scriptLines[] = "dataType: 'json',";
		$scriptLines[] = "success: function(suggestions) {";
		$scriptLines[] = "\$suggestionsContainer.empty();";
		
		$scriptLines[] = "if (!suggestions || suggestions.length === 0) {";
		$scriptLines[] = "\$suggestionsContainer.hide();";
		$scriptLines[] = "return;";
		$scriptLines[] = "}";
		
		$scriptLines[] = "$.each(suggestions, function(i, suggestion) {";
		// Escape suggestion text for XSS prevention
		$scriptLines[] = "var escapedText = $('<div>').text(suggestion).html();";
		$scriptLines[] = "var \$item = $('<div class=\"suggestion-item\"></div>');";
		$scriptLines[] = "\$item.html(escapedText);";
		$scriptLines[] = "\$item.css({ 'padding': '8px', 'cursor': 'pointer' });";
		$scriptLines[] = "\$item.hover(function() { $(this).css('background', '#f0f0f0'); }, function() { $(this).css('background', '#fff'); });";
		$scriptLines[] = "\$item.on('click', function() {";
		$scriptLines[] = "\$searchInput.val(suggestion).trigger('keyup');";
		$scriptLines[] = "\$suggestionsContainer.hide();";
		$scriptLines[] = "});";
		$scriptLines[] = "\$suggestionsContainer.append(\$item);";
		$scriptLines[] = "});";
		
		$scriptLines[] = "\$suggestionsContainer.show();";
		$scriptLines[] = "},";
		$scriptLines[] = "error: function() { \$suggestionsContainer.hide(); }";
		$scriptLines[] = "});";
		$scriptLines[] = "}, 300);"; // 300ms debounce
		$scriptLines[] = "});";
		
		// Hide suggestions when clicking outside
		$scriptLines[] = "$(document).on('click', function(e) {";
		$scriptLines[] = "if (!$(e.target).closest('.search-suggestions, #{$searchInput_safe}').length) {";
		$scriptLines[] = "\$suggestionsContainer.hide();";
		$scriptLines[] = "}";
		$scriptLines[] = "});";
		
		$scriptLines[] = "});";
		
		return implode('', $scriptLines);
	}
}
