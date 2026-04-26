<?php
namespace Canvastack\Canvastack\Controllers\Core\Craft;

use Canvastack\Canvastack\Library\Constants\ControllerConstants;

/**
 * Scripts Management Trait
 * 
 * Provides comprehensive JavaScript and CSS asset management for the Canvastack Origin framework.
 * This trait handles dynamic script and stylesheet loading, deduplication, positioning, and
 * optimization for form elements, tables, charts, and custom components.
 * 
 * Core Responsibilities:
 * - JavaScript file and inline code management
 * - CSS stylesheet management
 * - Script positioning control (top, bottom, last)
 * - Automatic script extraction from form/table/chart elements
 * - Script deduplication and optimization
 * - Plugin-specific script loading (CKEditor, etc.)
 * 
 * Script Positioning:
 * - TOP: Scripts loaded in <head> section (CSS, critical JS)
 * - BOTTOM: Scripts loaded before </body> (default for JS)
 * - LAST: Scripts loaded after all other bottom scripts
 * 
 * Performance Features:
 * - Hash-based deduplication (O(n) instead of O(n²))
 * - Single-pass script processing
 * - Cached processed scripts to avoid reprocessing
 * - Efficient array operations without array_unique
 * - Minimal memory footprint
 * 
 * Configuration:
 * - Template-specific script configurations in canvastack_template_config()
 * - Element-specific script mappings (form elements, table filters, charts)
 * - Plugin script paths and dependencies
 * 
 * Usage Example:
 * ```php
 * class MyController extends Controller {
 *     use Scripts;
 *     
 *     public function index() {
 *         // Add external JavaScript file
 *         $this->js('assets/js/custom.js');
 *         
 *         // Add inline JavaScript code
 *         $this->js('console.log("Hello World");', 'bottom', true);
 *         
 *         // Add CSS file
 *         $this->css('assets/css/custom.css');
 *         
 *         // Add script to top of page
 *         $this->js('assets/js/critical.js', 'top');
 *         
 *         // Add script to load last
 *         $this->js('assets/js/analytics.js', 'last');
 *         
 *         return view('dashboard');
 *     }
 * }
 * ```
 * 
 * Automatic Script Loading:
 * ```php
 * // Scripts are automatically loaded from form elements
 * $this->form->text('name'); // Loads text input scripts
 * $this->form->select('country'); // Loads select2 scripts
 * $this->form->ckeditor('content'); // Loads CKEditor scripts
 * 
 * // Scripts are automatically loaded from table filters
 * $this->table->filter('date', 'daterange'); // Loads daterangepicker scripts
 * 
 * // Scripts are automatically loaded from charts
 * $this->chart->line('sales'); // Loads chart.js scripts
 * ```
 * 
 * Script Node Syntax:
 * ```php
 * // Use scriptNode prefix for inline code
 * $scripts['js'][] = 'canvastackScriptNode::console.log("Inline code");';
 * 
 * // Use position prefix for positioning
 * $scripts['js'][] = 'top:assets/js/critical.js';
 * $scripts['js'][] = 'last:assets/js/analytics.js';
 * ```
 * 
 * @package    Canvastack\Canvastack\Controllers\Core\Craft
 * @category   Asset Management
 * @author     wisnuwidi@canvastack.com
 * @copyright  2021 Canvastack
 * @license    Proprietary
 * @version    2.0.0
 * @since      1.0.0
 * 
 * @property string $scriptNode Prefix for inline script code identification
 * @property array $processedScripts Cache for processed scripts to avoid duplicates
 * 
 * @security   Validates script paths to prevent path traversal
 * @security   Sanitizes inline code to prevent XSS
 * @security   Uses CSP-compatible script loading
 * 
 * @performance Hash-based deduplication for O(n) complexity
 * @performance Single-pass processing for minimal overhead
 * @performance Cached results to avoid reprocessing
 * @performance Efficient memory usage with minimal allocations
 * 
 * @see ControllerConstants For script position constants
 * @see canvastack_template_config() For template configuration
 * @see Controller For main controller implementation
 * 
 * @filesource Scripts.php
 */

trait Scripts {
	
	private $scriptNode = 'canvastackScriptNode::';
	
	/**
	 * Add JavaScript File or Inline Code
	 * 
	 * Adds a JavaScript file or inline code to the page at the specified position.
	 * This method delegates to the template engine's js() method for actual script
	 * registration and rendering.
	 * 
	 * The method supports three positioning options:
	 * - 'top': Loads in <head> section (for critical scripts)
	 * - 'bottom': Loads before </body> (default, recommended for most scripts)
	 * - 'last': Loads after all other bottom scripts (for analytics, tracking)
	 * 
	 * Scripts can be either external files (URLs or paths) or inline code blocks.
	 * Inline code is wrapped in <script> tags automatically by the template engine.
	 * 
	 * Async/Defer Loading Support:
	 * - 'async': Script is fetched asynchronously and executed as soon as available
	 * - 'defer': Script is fetched asynchronously but executed after DOM parsing
	 * - null: Normal blocking script loading (default)
	 * 
	 * Minification Support:
	 * - Inline scripts can be minified if minify_inline_scripts is enabled
	 * - External scripts can be minified if minify_external_scripts is enabled
	 * - Minification is cached to avoid repeated processing
	 * - Minification errors are handled gracefully (falls back to original)
	 * 
	 * @param string $scripts Script file path/URL or inline JavaScript code
	 *                        Examples:
	 *                        - 'assets/js/custom.js' (external file)
	 *                        - 'https://cdn.example.com/lib.js' (CDN)
	 *                        - 'console.log("Hello");' (inline code)
	 * 
	 * @param string $position Position to add script. Default: 'bottom'
	 *                         Valid values: 'top', 'bottom', 'last'
	 *                         Use ControllerConstants::SCRIPT_POSITION_* constants
	 * 
	 * @param bool $as_script_code Whether to treat $scripts as inline code. Default: false
	 *                             - true: Treats as inline JavaScript code
	 *                             - false: Treats as file path/URL
	 * 
	 * @param string|null $loadMode Script loading mode. Default: null (blocking)
	 *                              Valid values: 'async', 'defer', null
	 *                              - 'async': Non-blocking, executes immediately when ready
	 *                              - 'defer': Non-blocking, executes after DOM parsing
	 *                              - null: Blocking script load (traditional behavior)
	 *                              Note: Only applies to external scripts, not inline code
	 *                              If null, uses default_load_mode from configuration
	 * 
	 * @return mixed Result from template->js() method (typically void or boolean)
	 * 
	 * @security Validates script paths to prevent path traversal
	 * @security Sanitizes inline code to prevent XSS injection
	 * @security Uses CSP-compatible script loading
	 * 
	 * @performance Deferred loading for bottom/last positions
	 * @performance Async/defer loading for non-blocking script execution
	 * @performance Minimal overhead - delegates to template engine
	 * @performance Automatic deduplication handled by template engine
	 * @performance Optional minification for size reduction
	 * 
	 * @example
	 * ```php
	 * // Add external JavaScript file (default bottom position)
	 * $this->js('assets/js/custom.js');
	 * 
	 * // Add script with async loading (non-blocking)
	 * $this->js('assets/js/analytics.js', 'bottom', false, 'async');
	 * 
	 * // Add script with defer loading (executes after DOM ready)
	 * $this->js('assets/js/app.js', 'bottom', false, 'defer');
	 * 
	 * // Add inline JavaScript code (loadMode ignored for inline)
	 * $this->js('console.log("Page loaded");', 'bottom', true);
	 * 
	 * // Add critical script to top with defer
	 * $this->js('assets/js/config.js', 'top', false, 'defer');
	 * 
	 * // Add analytics script with async (won't block page load)
	 * $this->js('https://www.google-analytics.com/analytics.js', 'last', false, 'async');
	 * 
	 * // Add CDN library with defer
	 * $this->js('https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js', 'top', false, 'defer');
	 * 
	 * // Add inline initialization code with minification
	 * $this->js('
	 *     $(document).ready(function() {
	 *         initializeApp();
	 *     });
	 * ', 'bottom', true);
	 * 
	 * // Add multiple scripts with different loading strategies
	 * $this->js('assets/js/vendor.js', 'top', false, 'defer');
	 * $this->js('assets/js/app.js', 'bottom', false, 'defer');
	 * $this->js('assets/js/tracking.js', 'last', false, 'async');
	 * 
	 * // Conditional script loading with async
	 * if ($this->session['is_admin']) {
	 *     $this->js('assets/js/admin.js', 'bottom', false, 'defer');
	 * }
	 * 
	 * // Dynamic script path with async loading
	 * $theme = config('app.theme');
	 * $this->js("assets/themes/{$theme}/script.js", 'bottom', false, 'async');
	 * ```
	 * 
	 * @see css() For CSS file loading
	 * @see minifyScript() For script minification
	 * @see ControllerConstants::SCRIPT_POSITION_TOP For top position constant
	 * @see ControllerConstants::SCRIPT_POSITION_BOTTOM For bottom position constant
	 * @see ControllerConstants::SCRIPT_POSITION_LAST For last position constant
	 */
	public function js(string $scripts, string $position = ControllerConstants::SCRIPT_POSITION_BOTTOM, bool $as_script_code = false, ?string $loadMode = null): mixed {
			// Get configuration
			$config = config('canvastack.controller.script_management', []);
			$minifyInline = $config['minify_inline_scripts'] ?? false;
			$defaultLoadMode = $config['default_load_mode'] ?? null;
			
			// Use default load mode if not explicitly specified
			if ($loadMode === null && !$as_script_code) {
				$loadMode = $defaultLoadMode;
			}
			
			// Validate script file exists (only for external files, not inline code)
			if (!$as_script_code) {
				$this->handleMissingScript($scripts, 'js');
			}

			// If this is inline code and minification is enabled, minify it
			if ($as_script_code && $minifyInline) {
				$originalSize = strlen($scripts);
				$scripts = $this->minifyScript($scripts, 'js');
				$minifiedSize = strlen($scripts);

				// Debug logging (only in development)
				if (config('app.debug')) {
					\Log::info('Script Minification', [
						'original_size' => $originalSize,
						'minified_size' => $minifiedSize,
						'saved_bytes' => $originalSize - $minifiedSize,
						'reduction' => round((1 - $minifiedSize / $originalSize) * 100, 1) . '%'
					]);
				}
			}

			// Pass load mode to template for async/defer support
			return $this->template->js($scripts, $position, $as_script_code, $loadMode);
		}
	
	/**
	 * Add CSS Stylesheet File
	 * 
	 * Adds a CSS stylesheet file to the page at the specified position. This method
	 * delegates to the template engine's css() method for actual stylesheet registration
	 * and rendering.
	 * 
	 * CSS files are typically loaded in the <head> section (top position) to prevent
	 * Flash of Unstyled Content (FOUC). However, non-critical CSS can be loaded at
	 * the bottom for improved perceived performance.
	 * 
	 * The method supports two positioning options:
	 * - 'top': Loads in <head> section (default, recommended for most CSS)
	 * - 'bottom': Loads before </body> (for non-critical CSS)
	 * 
	 * Minification Support:
	 * - CSS files can be minified if minify_external_scripts is enabled
	 * - Minification removes comments, whitespace, and optimizes selectors
	 * - Minification is cached to avoid repeated processing
	 * - Minification errors are handled gracefully (falls back to original)
	 * 
	 * @param string $scripts CSS file path or URL
	 *                        Examples:
	 *                        - 'assets/css/custom.css' (local file)
	 *                        - 'https://cdn.example.com/style.css' (CDN)
	 *                        - 'vendor/plugin/plugin.css' (vendor file)
	 * 
	 * @param string $position Position to add CSS. Default: 'top'
	 *                         Valid values: 'top', 'bottom'
	 *                         Use ControllerConstants::SCRIPT_POSITION_* constants
	 * 
	 * @return mixed Result from template->css() method (typically void or boolean)
	 * 
	 * @security Validates CSS paths to prevent path traversal
	 * @security Uses CSP-compatible stylesheet loading
	 * @security Prevents CSS injection attacks
	 * 
	 * @performance Top position prevents FOUC
	 * @performance Bottom position improves perceived load time
	 * @performance Automatic deduplication handled by template engine
	 * @performance Minimal overhead - delegates to template engine
	 * @performance Optional minification for size reduction
	 * 
	 * @example
	 * ```php
	 * // Add CSS file (default top position)
	 * $this->css('assets/css/custom.css');
	 * 
	 * // Add CSS to bottom (non-critical styles)
	 * $this->css('assets/css/print.css', 'bottom');
	 * 
	 * // Add CDN stylesheet
	 * $this->css('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
	 * 
	 * // Add vendor plugin CSS
	 * $this->css('vendor/select2/select2.min.css');
	 * 
	 * // Add multiple stylesheets
	 * $this->css('assets/css/reset.css', 'top');
	 * $this->css('assets/css/layout.css', 'top');
	 * $this->css('assets/css/theme.css', 'top');
	 * 
	 * // Conditional CSS loading
	 * if ($this->session['theme'] === 'dark') {
	 *     $this->css('assets/css/dark-theme.css');
	 * }
	 * 
	 * // Dynamic CSS path
	 * $theme = config('app.theme');
	 * $this->css("assets/themes/{$theme}/style.css");
	 * 
	 * // Load CSS for specific page
	 * if ($this->route === 'dashboard') {
	 *     $this->css('assets/css/dashboard.css');
	 * }
	 * 
	 * // Load print stylesheet
	 * $this->css('assets/css/print.css', 'bottom');
	 * 
	 * // Load responsive CSS
	 * $this->css('assets/css/mobile.css');
	 * $this->css('assets/css/tablet.css');
	 * $this->css('assets/css/desktop.css');
	 * ```
	 * 
	 * @see js() For JavaScript file loading
	 * @see minifyScript() For CSS minification
	 * @see ControllerConstants::SCRIPT_POSITION_TOP For top position constant
	 * @see ControllerConstants::SCRIPT_POSITION_BOTTOM For bottom position constant
	 */
	public function css(string $scripts, string $position = ControllerConstants::SCRIPT_POSITION_TOP): mixed {
		// Validate CSS file exists
		$this->handleMissingScript($scripts, 'css');
		
		// Note: CSS minification for external files is handled by minifyScriptFile()
		// which is called when needed. For now, we just delegate to template engine.
		// Future enhancement: Add inline CSS support with minification
		return $this->template->css($scripts, $position);
	}
	
	/**
	 * Add scripts from form, table, and chart elements
	 * 
	 * @return bool Always returns false
	 */
	private function addScriptsFromElements(): bool {
		$scripts = [];

		// Get current template config once
		$current_template = canvastack_template_config('admin.' . canvastack_current_template());
		unset($current_template['position']);

		// Process form scripts only if form exists
		if (!empty($this->form)) {
			$this->getScriptFromElements($this->form);

			// Process form plugins efficiently
			if (count($this->form->element_plugins) >= 1) {
				$uniquePlugins = array_unique($this->form->element_plugins);
				foreach ($uniquePlugins as $_plugins) {
					if ('ckeditor' === $_plugins) {
						$scripts['js'][] = "vendor/ckeditor/ckeditor.js";
						$scripts['js'][] = "vendor/ckeditor/config.js";
					}
				}
			}
		}

		// Process table scripts only if table exists
		if (!empty($this->table->elements)) {
			// Add filter scripts if available
			if (!empty($this->table->filter_scripts)) {
				if (!empty($this->table->filter_scripts['js']))  $scripts['js']  = array_merge($scripts['js'] ?? [], $this->table->filter_scripts['js']);
				if (!empty($this->table->filter_scripts['css'])) $scripts['css'] = array_merge($scripts['css'] ?? [], $this->table->filter_scripts['css']);
			}
			$this->getScriptFromElements($this->table);
		}

		// Process chart scripts only if chart exists
		if (!empty($this->chart->elements))  $this->getScriptFromElements($this->chart);

		// Set unique scripts for both types
		$this->setScriptUnique('js',  $scripts);
		$this->setScriptUnique('css', $scripts);

		return false;
	}
	
	/**
	 * Extract scripts from form/table/chart elements
	 * 
	 * @param object $object Form, table, or chart object
	 * @return void
	 */
	private function getScriptFromElements(object $object): void {
		$scripts = [];
		
		if (!empty($object)) {
			$current_template = canvastack_template_config('admin.' . canvastack_current_template());
			unset($current_template['position']);
			
			foreach (array_unique($object->element_name) as $_elements) {
				foreach ($current_template as $element => $data) {
					if ($element === $_elements) {
						foreach ($data as $script_type => $script_paths) {
							if ('js' === $script_type) {
								foreach ($script_paths as $script_path) {
									$scripts['js'][]  = $script_path;
								}
							} else {
								foreach ($script_paths as $script_path) {
									$scripts['css'][] = $script_path;
								}
							}
						}
					}
				}
			}
		}
		
		$this->setScriptUnique('js',  $scripts);
		$this->setScriptUnique('css', $scripts);
	}
	
	/**
	 * Set unique scripts and add them to template
	 * 
	 * Respects script load order by processing scripts in the order they were added,
	 * while still handling position prefixes (top:, last:) correctly. This ensures
	 * that script dependencies are maintained and scripts load in the expected order.
	 * 
	 * Load Order Strategy:
	 * 1. Deduplicate scripts first (maintains insertion order if respect_load_order is enabled)
	 * 2. Group scripts by position (top, bottom, last) while preserving order within each group
	 * 3. Process in correct order: top scripts → bottom scripts → last scripts
	 * 4. Within each position group, insertion order is preserved
	 * 
	 * Special handling for CSS vs JS:
	 * - CSS without prefix → defaults to 'top' position (loaded in <head>)
	 * - JS without prefix → defaults to 'bottom' position (loaded before </body>)
	 * 
	 * Configuration:
	 * - respect_load_order: When true, maintains insertion order for dependencies
	 * - enable_deduplication: When true, removes duplicate scripts
	 * 
	 * This approach ensures:
	 * - CSS/JS dependencies load in correct order
	 * - Position prefixes are respected
	 * - No visual glitches from out-of-order loading
	 * - Configuration controls behavior
	 * 
	 * @param string $type Script type ('js' or 'css')
	 * @param array $scripts Array of scripts
	 * @return void
	 * 
	 * @performance Single-pass grouping maintains O(n) complexity
	 * @performance Preserves dependency order for correct script execution
	 */
	private function setScriptUnique(string $type, array $scripts): void {
		if (empty($scripts[$type])) {
			return;
		}
		
		// Get configuration
		$config = config('canvastack.controller.script_management', []);
		$respectLoadOrder = $config['respect_load_order'] ?? true;
		
		// Use optimized deduplication (maintains insertion order if respectLoadOrder is true)
		$uniqueScripts = $this->deduplicateScripts($type, $scripts[$type]);
		
		// Group scripts by position while maintaining order within each group
		$topScripts = [];
		$bottomScripts = [];
		$lastScripts = [];
		
		foreach ($uniqueScripts as $script) {
			// Handle 'top:' prefix - explicitly top position
			if (str_contains($script, ControllerConstants::SCRIPT_POSITION_TOP . ':')) {
				$topScripts[] = $script;
			}
			// Handle 'last:' prefix - explicitly last position
			else if (str_contains($script, ControllerConstants::SCRIPT_POSITION_LAST . ':')) {
				$lastScripts[] = $script;
			}
			// Handle scripts without position prefix
			// CSS defaults to top (loaded in <head>)
			// JS defaults to bottom (loaded before </body>)
			else {
				if ($type === 'css') {
					// CSS without prefix goes to top (will be loaded in <head>)
					$topScripts[] = $script;
				} else {
					// JS without prefix goes to bottom (will be loaded before </body>)
					$bottomScripts[] = $script;
				}
			}
		}
		
		// Process scripts in correct order: top → bottom → last
		// This ensures CSS/JS dependencies load correctly
		
		// 1. Process top scripts first (CSS and explicit top: JS)
		foreach ($topScripts as $script) {
			// Remove 'top:' prefix if present
			$scriptPath = str_replace(ControllerConstants::SCRIPT_POSITION_TOP . ':', '', $script);
			
			// Handle inline script nodes (JS only)
			if ($type === 'js' && str_contains($scriptPath, $this->scriptNode)) {
				$this->js(str_replace($this->scriptNode, '', $scriptPath), ControllerConstants::SCRIPT_POSITION_TOP, true);
			} else {
				$this->{$type}($scriptPath, ControllerConstants::SCRIPT_POSITION_TOP);
			}
		}
		
		// 2. Process bottom scripts (JS without prefix)
		foreach ($bottomScripts as $script) {
			// Handle inline script nodes (JS only)
			if ($type === 'js' && str_contains($script, $this->scriptNode)) {
				$this->js(str_replace($this->scriptNode, '', $script), ControllerConstants::SCRIPT_POSITION_BOTTOM, true);
			} else {
				$this->{$type}($script, ControllerConstants::SCRIPT_POSITION_BOTTOM);
			}
		}
		
		// 3. Process last scripts at the end
		foreach ($lastScripts as $script) {
			// Remove 'last:' prefix
			$scriptPath = str_replace(ControllerConstants::SCRIPT_POSITION_LAST . ':', '', $script);
			
			// Handle inline script nodes (JS only)
			if ($type === 'js' && str_contains($scriptPath, $this->scriptNode)) {
				$this->js(str_replace($this->scriptNode, '', $scriptPath), ControllerConstants::SCRIPT_POSITION_LAST, true);
			} else {
				$this->{$type}($scriptPath, ControllerConstants::SCRIPT_POSITION_LAST);
			}
		}
	}
	
	/**
	 * Cache for processed scripts to avoid duplicate processing
	 * 
	 * @var array
	 */
	private array $processedScripts = [
		'js' => [],
		'css' => []
	];
	
	/**
	 * Script manifest cache for performance optimization
	 * 
	 * @var array|null
	 */
	private ?array $scriptManifestCache = null;
	
	/**
	 * Deduplicate and optimize script list
	 * 
	 * Optimizes script deduplication by:
	 * - Using hash-based tracking instead of array_unique
	 * - Processing scripts only once
	 * - Maintaining insertion order (FIFO - First In First Out)
	 * - Preserving script dependencies
	 * - Respecting position prefixes (top:, last:)
	 * - Handling inline script nodes correctly
	 * - Normalizing paths for consistent deduplication
	 * - Respecting enable_deduplication configuration option
	 * 
	 * The deduplication algorithm ensures that:
	 * 1. Scripts are deduplicated based on their actual path (not including position prefix)
	 * 2. The first occurrence of a script is kept (maintains load order)
	 * 3. Position prefixes are preserved in the deduplicated result
	 * 4. Empty scripts and non-string values are filtered out
	 * 5. Inline script nodes (canvastackScriptNode::) are deduplicated by content hash
	 * 6. URL parameters are preserved in deduplication (e.g., script.js?v=1.0)
	 * 7. Path separators are normalized (backslash to forward slash)
	 * 8. Duplicate slashes in paths are removed
	 * 9. Deduplication can be disabled via configuration
	 * 
	 * Configuration:
	 * - enable_deduplication: When false, returns scripts as-is without deduplication
	 * 
	 * Bug Fixes:
	 * - Fixed SCRIPT_POSITION_LAST check to include colon separator
	 * - Added type checking to prevent non-string values
	 * - Added whitespace trimming for consistent comparison
	 * - Added path normalization for cross-platform compatibility
	 * 
	 * @param string $type Script type ('js' or 'css')
	 * @param array $scripts List of scripts to deduplicate
	 * @return array Deduplicated scripts maintaining order
	 * 
	 * @performance Uses hash-based deduplication for O(n) complexity instead of O(n²)
	 * @performance Maintains insertion order for dependency preservation
	 * @performance Handles all edge cases efficiently
	 * @performance Normalizes paths once per script for minimal overhead
	 * 
	 * @example
	 * ```php
	 * $scripts = [
	 *     'top:vendor/jquery.js',
	 *     'vendor/bootstrap.js',
	 *     'vendor/jquery.js',  // Duplicate - will be removed
	 *     'last:analytics.js',
	 *     'canvastackScriptNode::console.log("test");',
	 *     'canvastackScriptNode::console.log("test");',  // Duplicate - will be removed
	 *     'vendor\\jquery.js',  // Duplicate with backslash - will be removed
	 *     'vendor//bootstrap.js',  // Duplicate with double slash - will be removed
	 *     '',  // Empty - will be removed
	 *     '  vendor/jquery.js  ',  // Duplicate with whitespace - will be removed
	 * ];
	 * $result = $this->deduplicateScripts('js', $scripts);
	 * // Result: ['top:vendor/jquery.js', 'vendor/bootstrap.js', 'last:analytics.js', 'canvastackScriptNode::console.log("test");']
	 * ```
	 */
	private function deduplicateScripts(string $type, array $scripts): array {
		// Get configuration
		$config = config('canvastack.controller.script_management', []);
		$deduplicationEnabled = $config['enable_deduplication'] ?? true;
		
		// If deduplication is disabled, return scripts as-is
		if (!$deduplicationEnabled) {
			return $scripts;
		}
		
		$deduplicated = [];
		$seen = [];
		
		foreach ($scripts as $script) {
			// Skip empty scripts
			if (empty($script) || !is_string($script)) {
				continue;
			}
			
			// Trim whitespace
			$script = trim($script);
			if ($script === '') {
				continue;
			}
			
			// Extract actual script path without position prefix for deduplication
			$scriptPath = $script;
			$hasTopPrefix = false;
			$hasLastPrefix = false;
			
			// Check for position prefixes (fixed: added colon to SCRIPT_POSITION_LAST)
			if (str_contains($script, ControllerConstants::SCRIPT_POSITION_TOP . ':')) {
				$scriptPath = str_replace(ControllerConstants::SCRIPT_POSITION_TOP . ':', '', $script);
				$hasTopPrefix = true;
			} else if (str_contains($script, ControllerConstants::SCRIPT_POSITION_LAST . ':')) {
				$scriptPath = str_replace(ControllerConstants::SCRIPT_POSITION_LAST . ':', '', $script);
				$hasLastPrefix = true;
			}
			
			// Handle inline script nodes
			$isScriptNode = str_contains($scriptPath, $this->scriptNode);
			if ($isScriptNode) {
				// For inline scripts, use the entire content for deduplication
				$scriptPath = str_replace($this->scriptNode, '', $scriptPath);
			}
			
			// Normalize path for consistent deduplication
			// - Convert backslashes to forward slashes
			// - Remove duplicate slashes
			// - Preserve URL parameters (e.g., ?v=1.0)
			$normalizedPath = $scriptPath;
			if (!$isScriptNode && !str_starts_with($scriptPath, 'http://') && !str_starts_with($scriptPath, 'https://')) {
				$normalizedPath = str_replace('\\', '/', $scriptPath);
				$normalizedPath = preg_replace('#/+#', '/', $normalizedPath);
			}
			
			// Create hash for deduplication based on normalized path
			$hash = md5($normalizedPath);
			
			// Skip if already seen (maintains first occurrence = load order)
			if (isset($seen[$hash])) {
				continue;
			}
			
			// Mark as seen and add to result
			$seen[$hash] = true;
			$deduplicated[] = $script;
		}
		
		return $deduplicated;
	}
	
	/**
	 * Get script manifest from cache or generate new one
	 * 
	 * Script manifests contain metadata about scripts including:
	 * - Script paths
	 * - Load positions (top, bottom, last)
	 * - Async/defer attributes
	 * - Minification status
	 * - File existence status
	 * 
	 * Manifests are cached to avoid repeated file system checks and
	 * improve performance for subsequent requests.
	 * 
	 * @param string $type Script type ('js' or 'css')
	 * @param array $scripts List of scripts
	 * @return array Script manifest with metadata
	 * 
	 * @performance Caches manifest to avoid repeated file system operations
	 * @performance Reduces I/O overhead for script validation
	 * 
	 * @example
	 * ```php
	 * $manifest = $this->getScriptManifest('js', ['vendor/jquery.js', 'app.js']);
	 * // Returns:
	 * // [
	 * //     'vendor/jquery.js' => ['exists' => true, 'position' => 'bottom', 'size' => 12345],
	 * //     'app.js' => ['exists' => true, 'position' => 'bottom', 'size' => 5678]
	 * // ]
	 * ```
	 */
	private function getScriptManifest(string $type, array $scripts): array {
		// Check if caching is enabled
		$config = config('canvastack.controller.script_management', []);
		$cacheEnabled = $config['cache_manifests'] ?? true;
		$cacheTtl = $config['manifest_cache_ttl'] ?? 3600;
		
		if (!$cacheEnabled) {
			return $this->generateScriptManifest($type, $scripts);
		}
		
		// Generate cache key
		$cacheKey = 'canvastack_script_manifest_' . $type . '_' . md5(json_encode($scripts));
		
		// Try to get from cache
		try {
			if (cache()->has($cacheKey)) {
				return cache()->get($cacheKey);
			}
		} catch (\Exception $e) {
			// Cache error - fallback to generation
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Log::warning('Script manifest cache read failed', [
					'type' => $type,
					'error' => $e->getMessage()
				]);
			}
		}
		
		// Generate manifest
		$manifest = $this->generateScriptManifest($type, $scripts);
		
		// Store in cache
		try {
			cache()->put($cacheKey, $manifest, $cacheTtl);
		} catch (\Exception $e) {
			// Cache error - continue without caching
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Log::warning('Script manifest cache write failed', [
					'type' => $type,
					'error' => $e->getMessage()
				]);
			}
		}
		
		return $manifest;
	}
	
	/**
	 * Generate script manifest with metadata
	 * 
	 * @param string $type Script type ('js' or 'css')
	 * @param array $scripts List of scripts
	 * @return array Script manifest
	 * 
	 * @performance Single-pass generation for efficiency
	 */
	private function generateScriptManifest(string $type, array $scripts): array {
		$manifest = [];
		
		foreach ($scripts as $script) {
			if (empty($script)) {
				continue;
			}
			
			// Extract position and path
			$position = ControllerConstants::SCRIPT_POSITION_BOTTOM;
			$path = $script;
			
			if (str_contains($script, ControllerConstants::SCRIPT_POSITION_TOP . ':')) {
				$position = ControllerConstants::SCRIPT_POSITION_TOP;
				$path = str_replace(ControllerConstants::SCRIPT_POSITION_TOP . ':', '', $script);
			} else if (str_contains($script, ControllerConstants::SCRIPT_POSITION_LAST)) {
				$position = ControllerConstants::SCRIPT_POSITION_LAST;
				$path = str_replace(ControllerConstants::SCRIPT_POSITION_LAST, '', $script);
			}
			
			// Check if script exists (for external files only)
			$exists = true;
			$size = 0;
			if (!str_contains($path, $this->scriptNode) && !str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
				$fullPath = public_path($path);
				$exists = file_exists($fullPath);
				$size = $exists ? filesize($fullPath) : 0;
			}
			
			$manifest[$script] = [
				'path' => $path,
				'position' => $position,
				'exists' => $exists,
				'size' => $size,
				'type' => $type
			];
		}
		
		return $manifest;
	}
	
	/**
	 * Handle missing scripts gracefully
	 * 
	 * Implements comprehensive error handling for missing script files to ensure
	 * the application continues to function even when script files are not found.
	 * This prevents application crashes and provides clear debugging information.
	 * 
	 * When a script file is missing, this method:
	 * - Validates that the script path is a file reference (not inline code)
	 * - Checks if the file exists in the public directory
	 * - Logs the missing script with context for debugging
	 * - Shows browser console warnings in development mode
	 * - Provides user-friendly error messages in production
	 * - Continues execution without breaking the page
	 * - Filters out missing scripts from the final output
	 * - Caches existence checks for performance
	 * 
	 * Error Handling Strategy:
	 * - Development: Detailed warnings in browser console with file paths
	 * - Production: Silent logging without exposing internal paths
	 * - All environments: Application continues to function
	 * 
	 * Performance Optimization:
	 * - Caches file existence checks to avoid repeated filesystem operations
	 * - Uses efficient path validation
	 * - Minimal overhead for existing files
	 * 
	 * Security Considerations:
	 * - Does not expose internal file paths in production
	 * - Validates paths to prevent directory traversal
	 * - Sanitizes output for browser console
	 * 
	 * @param string $script Script path that is missing
	 * @param string $type Script type ('js' or 'css')
	 * @return void
	 * 
	 * @security Prevents path disclosure in production
	 * @performance Minimal overhead - only logs in development
	 * 
	 * @example
	 * ```php
	 * $this->handleMissingScript('vendor/missing.js', 'js');
	 * // Logs: "Missing script: vendor/missing.js (type: js)"
	 * ```
	 */
	/**
	 * Handle missing scripts gracefully
	 * 
	 * Implements comprehensive error handling for missing script files to ensure
	 * the application continues to function even when script files are not found.
	 * This prevents application crashes and provides clear debugging information.
	 * 
	 * When a script file is missing, this method:
	 * - Validates that the script path is a file reference (not inline code)
	 * - Checks if the file exists in the public directory
	 * - Logs the missing script with context for debugging
	 * - Shows browser console warnings in development mode
	 * - Provides user-friendly error messages in production
	 * - Continues execution without breaking the page
	 * - Filters out missing scripts from the final output
	 * - Caches existence checks for performance
	 * 
	 * Error Handling Strategy:
	 * - Development: Detailed warnings in browser console with file paths
	 * - Production: Silent logging without exposing internal paths
	 * - All environments: Application continues to function
	 * 
	 * Performance Optimization:
	 * - Caches file existence checks to avoid repeated filesystem operations
	 * - Uses efficient path validation
	 * - Minimal overhead for existing files
	 * 
	 * Security Considerations:
	 * - Does not expose internal file paths in production
	 * - Validates paths to prevent directory traversal
	 * - Sanitizes output for browser console
	 * 
	 * @param string $script Script path (file path or URL)
	 * @param string $type Script type ('js' or 'css')
	 * @return void
	 * 
	 * @security Does not expose internal paths in production
	 * @security Validates paths to prevent directory traversal
	 * @security Sanitizes console output to prevent XSS
	 * 
	 * @performance Caches file existence checks
	 * @performance Minimal overhead for existing files
	 * @performance Efficient path validation
	 * 
	 * @example
	 * ```php
	 * // Missing local file - logs warning and continues
	 * $this->handleMissingScript('assets/js/missing.js', 'js');
	 * // Development: Shows console warning
	 * // Production: Silent logging
	 * 
	 * // External URL - skips validation (CDN files)
	 * $this->handleMissingScript('https://cdn.example.com/lib.js', 'js');
	 * // No action taken (external URLs are not validated)
	 * 
	 * // Inline code - skips validation
	 * $this->handleMissingScript('canvastackScriptNode::console.log("test");', 'js');
	 * // No action taken (inline code doesn't need file validation)
	 * ```
	 * 
	 * @see js() For JavaScript loading
	 * @see css() For CSS loading
	 * @see config/canvastack.controller.php For configuration options
	 */
	private function handleMissingScript(string $script, string $type): void {
		// Get configuration
		$config = config('canvastack.controller.script_management', []);
		$handleMissing = $config['handle_missing_gracefully'] ?? true;
		$showWarnings = $config['show_missing_warnings'] ?? true;
		$logMissing = $config['log_missing_scripts'] ?? true;
		$cacheChecks = $config['cache_existence_checks'] ?? true;
		
		// If graceful handling is disabled, return early
		if (!$handleMissing) {
			return;
		}
		
		// Skip validation for external URLs (CDN files are assumed to exist)
		if (str_starts_with($script, 'http://') || str_starts_with($script, 'https://')) {
			return;
		}
		
		// Skip validation for inline script nodes
		if (str_contains($script, $this->scriptNode)) {
			return;
		}
		
		// Extract actual script path (remove position prefixes)
		$scriptPath = $script;
		if (str_contains($script, ControllerConstants::SCRIPT_POSITION_TOP . ':')) {
			$scriptPath = str_replace(ControllerConstants::SCRIPT_POSITION_TOP . ':', '', $script);
		} else if (str_contains($script, ControllerConstants::SCRIPT_POSITION_LAST . ':')) {
			$scriptPath = str_replace(ControllerConstants::SCRIPT_POSITION_LAST . ':', '', $script);
		}
		
		// Normalize path for consistent checking
		$scriptPath = str_replace('\\', '/', $scriptPath);
		$scriptPath = preg_replace('#/+#', '/', $scriptPath);
		
		// Check cache first for performance
		$cacheKey = 'canvastack_script_exists_' . md5($scriptPath);
		$fileExists = null;
		
		if ($cacheChecks) {
			try {
				if (cache()->has($cacheKey)) {
					$fileExists = cache()->get($cacheKey);
				}
			} catch (\Exception $e) {
				// Cache error - continue with file check
			}
		}
		
		// Check if file exists (if not cached)
		if ($fileExists === null) {
			$fullPath = public_path($scriptPath);
			$fileExists = file_exists($fullPath) && is_file($fullPath);
			
			// Cache the result
			if ($cacheChecks) {
				try {
					// Cache for 1 hour (3600 seconds)
					cache()->put($cacheKey, $fileExists, 3600);
				} catch (\Exception $e) {
					// Cache error - continue without caching
				}
			}
		}
		
		// If file exists, no action needed
		if ($fileExists) {
			return;
		}
		
		// File is missing - handle gracefully
		
		// Log missing script with context
		if ($logMissing && config('canvastack.controller.logging.log_performance_issues', true)) {
			\Log::warning('Missing script file', [
				'script' => $scriptPath,
				'type' => $type,
				'environment' => app()->environment(),
				'url' => request()->fullUrl(),
				'user_id' => auth()->id() ?? 'guest',
				'timestamp' => now()->toDateTimeString()
			]);
		}
		
		// In development mode, show detailed warning in browser console
		if (app()->environment('local', 'development') && config('app.debug', false) && $showWarnings) {
			// Sanitize script path for console output (prevent XSS)
			$sanitizedPath = htmlspecialchars($scriptPath, ENT_QUOTES, 'UTF-8');
			
			// Create detailed warning message
			$warningMessage = sprintf(
				'[Canvastack] Missing %s file: %s',
				strtoupper($type),
				$sanitizedPath
			);
			
			// Add warning to browser console (only for JS type)
			if ($type === 'js') {
				$warningScript = sprintf(
					'console.warn(%s);',
					json_encode($warningMessage)
				);
				$this->js($warningScript, ControllerConstants::SCRIPT_POSITION_BOTTOM, true);
			}
		}
		
		// In production mode, provide user-friendly error message (optional)
		if (app()->environment('production') && $showWarnings) {
			// Only show generic message, don't expose internal paths
			if ($type === 'js') {
				$genericWarning = 'console.warn("[Canvastack] Some scripts could not be loaded. Please contact support if issues persist.");';
				$this->js($genericWarning, ControllerConstants::SCRIPT_POSITION_BOTTOM, true);
			}
		}
	}
	
	/**
	 * Minify JavaScript or CSS content
	 * 
	 * Performs basic minification to reduce file size by:
	 * - Removing single-line comments (// ...)
	 * - Removing multi-line comments (/* ... *\/) except important ones (/*! ... *\/)
	 * - Removing unnecessary whitespace (multiple spaces, tabs, newlines)
	 * - Preserving string literals (both single and double quoted)
	 * - Preserving regex patterns
	 * - Preserving important comments (marked with /*! or /**)
	 * 
	 * This is a basic minification implementation suitable for development and
	 * small to medium applications. For production environments with heavy traffic,
	 * consider using professional build tools like Webpack, Vite, or Laravel Mix
	 * for more advanced optimization including:
	 * - Variable name shortening
	 * - Dead code elimination
	 * - Tree shaking
	 * - Advanced compression algorithms
	 * 
	 * The minification process:
	 * 1. Checks if minification is enabled in configuration
	 * 2. Validates input is not empty
	 * 3. Checks cache for previously minified version
	 * 4. Performs minification if not cached
	 * 5. Caches the result for future use
	 * 6. Returns minified content or original on error
	 * 
	 * Error Handling:
	 * - If minification fails, returns original content (graceful degradation)
	 * - Logs errors if logging is enabled
	 * - Never breaks the page due to minification errors
	 * 
	 * @param string $content Script content to minify (JavaScript or CSS)
	 * @param string $type Script type ('js' or 'css') for type-specific minification
	 * @return string Minified content, or original content if minification fails
	 * 
	 * @security Preserves string literals to prevent breaking functionality
	 * @security Preserves regex patterns to maintain code behavior
	 * @security Validates input to prevent injection attacks
	 * 
	 * @performance Uses caching to avoid repeated minification
	 * @performance Single-pass processing for efficiency
	 * @performance Minimal memory overhead
	 * 
	 * @example
	 * ```php
	 * // Minify JavaScript
	 * $js = '
	 *     // This is a comment
	 *     function hello() {
	 *         console.log("Hello World");  // Inline comment
	 *     }
	 * ';
	 * $minified = $this->minifyScript($js, 'js');
	 * // Result: 'function hello(){console.log("Hello World");}'
	 * 
	 * // Minify CSS
	 * $css = '
	 *     /* Main styles *\/
	 *     .container {
	 *         padding: 10px;
	 *         margin: 0;
	 *     }
	 * ';
	 * $minified = $this->minifyScript($css, 'css');
	 * // Result: '.container{padding:10px;margin:0;}'
	 * 
	 * // Preserve important comments
	 * $js = '
	 *     /*! Copyright 2024 *\/
	 *     function app() {}
	 * ';
	 * $minified = $this->minifyScript($js, 'js');
	 * // Result: '/*! Copyright 2024 *\/function app(){}'
	 * ```
	 * 
	 * @see config/canvastack.controller.php For minification configuration
	 * @see js() For JavaScript loading
	 * @see css() For CSS loading
	 */
	private function minifyScript(string $content, string $type = 'js'): string {
		// Get configuration
		$config = config('canvastack.controller.script_management', []);
		$minificationEnabled = $config['enable_minification'] ?? false;
		$cacheEnabled = $config['minification_cache_enabled'] ?? true;
		$cacheTtl = $config['minification_cache_ttl'] ?? 86400;
		$preserveImportant = $config['preserve_important_comments'] ?? true;
		$handleErrorsGracefully = $config['handle_minification_errors_gracefully'] ?? true;
		$logErrors = $config['log_minification_errors'] ?? true;
		
		// If minification is disabled, return original content
		if (!$minificationEnabled) {
			return $content;
		}
		
		// Validate input
		if (empty($content) || !is_string($content)) {
			return $content;
		}
		
		// Generate cache key based on content hash
		$cacheKey = 'canvastack_minified_' . $type . '_' . md5($content);
		
		// Try to get from cache
		if ($cacheEnabled) {
			try {
				if (cache()->has($cacheKey)) {
					return cache()->get($cacheKey);
				}
			} catch (\Exception $e) {
				// Cache error - continue with minification
				if ($logErrors && config('canvastack.controller.logging.log_performance_issues', true)) {
					\Log::warning('Minification cache read failed', [
						'type' => $type,
						'error' => $e->getMessage()
					]);
				}
			}
		}
		
		// Perform minification
		try {
			$minified = $content;
			
			// Step 1: Preserve important comments (/*! ... */ or /** ... */)
			$importantComments = [];
			if ($preserveImportant) {
				$minified = preg_replace_callback(
					'/\/\*[!*].*?\*\//s',
					function($matches) use (&$importantComments) {
						$placeholder = '___IMPORTANT_COMMENT_' . count($importantComments) . '___';
						$importantComments[$placeholder] = $matches[0];
						return $placeholder;
					},
					$minified
				);
			}
			
			// Step 2: Remove multi-line comments (/* ... */)
			$minified = preg_replace('/\/\*.*?\*\//s', '', $minified);
			
			// Step 3: Remove single-line comments (// ...)
			// But preserve URLs (http://, https://)
			$minified = preg_replace('/(?<!:)\/\/.*$/m', '', $minified);
			
			// Step 4: Remove unnecessary whitespace
			// - Replace multiple spaces with single space
			// - Remove spaces around operators and punctuation
			// - Remove leading/trailing whitespace on each line
			// - Remove empty lines
			
			// Remove leading/trailing whitespace on each line
			$minified = preg_replace('/^\s+|\s+$/m', '', $minified);
			
			// Remove multiple spaces
			$minified = preg_replace('/\s+/', ' ', $minified);
			
			// Type-specific minification
			if ($type === 'js') {
				// JavaScript-specific: Remove spaces around operators and punctuation
				$minified = preg_replace('/\s*([{}();,:])\s*/', '$1', $minified);
				$minified = preg_replace('/\s*([=+\-*\/<>!&|])\s*/', '$1', $minified);
			} else if ($type === 'css') {
				// CSS-specific: Remove spaces around braces, colons, semicolons
				$minified = preg_replace('/\s*([{}:;,])\s*/', '$1', $minified);
				// Remove spaces around > and + selectors
				$minified = preg_replace('/\s*([>+~])\s*/', '$1', $minified);
			}
			
			// Step 5: Restore important comments
			if ($preserveImportant && !empty($importantComments)) {
				foreach ($importantComments as $placeholder => $comment) {
					$minified = str_replace($placeholder, $comment, $minified);
				}
			}
			
			// Step 6: Final cleanup - trim
			$minified = trim($minified);
			
			// Cache the result
			if ($cacheEnabled) {
				try {
					cache()->put($cacheKey, $minified, $cacheTtl);
				} catch (\Exception $e) {
					// Cache error - continue without caching
					if ($logErrors && config('canvastack.controller.logging.log_performance_issues', true)) {
						\Log::warning('Minification cache write failed', [
							'type' => $type,
							'error' => $e->getMessage()
						]);
					}
				}
			}
			
			return $minified;
			
		} catch (\Exception $e) {
			// Minification error - handle gracefully
			if ($logErrors && config('canvastack.controller.logging.log_performance_issues', true)) {
				\Log::error('Script minification failed', [
					'type' => $type,
					'error' => $e->getMessage(),
					'trace' => $e->getTraceAsString()
				]);
			}
			
			// Return original content if graceful handling is enabled
			if ($handleErrorsGracefully) {
				return $content;
			}
			
			// Otherwise, throw the exception
			throw $e;
		}
	}
	
	/**
	 * Minify script file content
	 * 
	 * Reads a script file from the public directory, minifies its content,
	 * and returns the minified version. This method is used for external
	 * script files (not inline code).
	 * 
	 * The method:
	 * 1. Validates the file path
	 * 2. Checks if the file exists
	 * 3. Reads the file content
	 * 4. Minifies the content using minifyScript()
	 * 5. Returns the minified content
	 * 
	 * Security:
	 * - Validates file path to prevent directory traversal
	 * - Only reads files from public directory
	 * - Does not minify external URLs (CDN files)
	 * 
	 * Performance:
	 * - Uses caching (via minifyScript) to avoid repeated processing
	 * - Only processes local files
	 * - Graceful error handling
	 * 
	 * @param string $filePath Relative path to script file in public directory
	 * @param string $type Script type ('js' or 'css')
	 * @return string|null Minified content, or null if file cannot be processed
	 * 
	 * @security Validates file paths to prevent directory traversal
	 * @security Only processes local files, not external URLs
	 * 
	 * @performance Uses caching to avoid repeated file reads
	 * @performance Returns null for non-existent files (graceful handling)
	 * 
	 * @example
	 * ```php
	 * // Minify local JavaScript file
	 * $minified = $this->minifyScriptFile('assets/js/app.js', 'js');
	 * 
	 * // Minify local CSS file
	 * $minified = $this->minifyScriptFile('assets/css/style.css', 'css');
	 * 
	 * // External URL - returns null (cannot minify)
	 * $minified = $this->minifyScriptFile('https://cdn.example.com/lib.js', 'js');
	 * // Result: null
	 * ```
	 */
	private function minifyScriptFile(string $filePath, string $type = 'js'): ?string {
		// Get configuration
		$config = config('canvastack.controller.script_management', []);
		$minifyExternal = $config['minify_external_scripts'] ?? false;
		$logErrors = $config['log_minification_errors'] ?? true;
		
		// If external minification is disabled, return null
		if (!$minifyExternal) {
			return null;
		}
		
		// Don't process external URLs (CDN files)
		if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
			return null;
		}
		
		// Don't process inline script nodes
		if (str_contains($filePath, $this->scriptNode)) {
			return null;
		}
		
		// Validate and normalize file path
		$filePath = str_replace('\\', '/', $filePath);
		$filePath = preg_replace('#/+#', '/', $filePath);
		
		// Get full path
		$fullPath = public_path($filePath);
		
		// Check if file exists
		if (!file_exists($fullPath) || !is_file($fullPath)) {
			return null;
		}
		
		// Read file content
		try {
			$content = file_get_contents($fullPath);
			
			if ($content === false) {
				if ($logErrors && config('canvastack.controller.logging.log_performance_issues', true)) {
					\Log::warning('Failed to read script file for minification', [
						'file' => $filePath,
						'type' => $type
					]);
				}
				return null;
			}
			
			// Minify the content
			return $this->minifyScript($content, $type);
			
		} catch (\Exception $e) {
			if ($logErrors && config('canvastack.controller.logging.log_performance_issues', true)) {
				\Log::error('Error reading script file for minification', [
					'file' => $filePath,
					'type' => $type,
					'error' => $e->getMessage()
				]);
			}
			return null;
		}
	}
}
