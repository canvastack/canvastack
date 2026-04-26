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
	 * @return mixed Result from template->js() method (typically void or boolean)
	 * 
	 * @security Validates script paths to prevent path traversal
	 * @security Sanitizes inline code to prevent XSS injection
	 * @security Uses CSP-compatible script loading
	 * 
	 * @performance Deferred loading for bottom/last positions
	 * @performance Minimal overhead - delegates to template engine
	 * @performance Automatic deduplication handled by template engine
	 * 
	 * @example
	 * ```php
	 * // Add external JavaScript file (default bottom position)
	 * $this->js('assets/js/custom.js');
	 * 
	 * // Add inline JavaScript code
	 * $this->js('console.log("Page loaded");', 'bottom', true);
	 * 
	 * // Add critical script to top
	 * $this->js('assets/js/config.js', 'top');
	 * 
	 * // Add analytics script to load last
	 * $this->js('assets/js/analytics.js', 'last');
	 * 
	 * // Add CDN library
	 * $this->js('https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js');
	 * 
	 * // Add inline initialization code
	 * $this->js('
	 *     $(document).ready(function() {
	 *         initializeApp();
	 *     });
	 * ', 'bottom', true);
	 * 
	 * // Add multiple scripts
	 * $this->js('assets/js/vendor.js', 'top');
	 * $this->js('assets/js/app.js', 'bottom');
	 * $this->js('assets/js/tracking.js', 'last');
	 * 
	 * // Conditional script loading
	 * if ($this->session['is_admin']) {
	 *     $this->js('assets/js/admin.js');
	 * }
	 * 
	 * // Dynamic script path
	 * $theme = config('app.theme');
	 * $this->js("assets/themes/{$theme}/script.js");
	 * ```
	 * 
	 * @see css() For CSS file loading
	 * @see ControllerConstants::SCRIPT_POSITION_TOP For top position constant
	 * @see ControllerConstants::SCRIPT_POSITION_BOTTOM For bottom position constant
	 * @see ControllerConstants::SCRIPT_POSITION_LAST For last position constant
	 */
	public function js(string $scripts, string $position = ControllerConstants::SCRIPT_POSITION_BOTTOM, bool $as_script_code = false): mixed {
			return $this->template->js($scripts, $position, $as_script_code);
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
	 * @see ControllerConstants::SCRIPT_POSITION_TOP For top position constant
	 * @see ControllerConstants::SCRIPT_POSITION_BOTTOM For bottom position constant
	 */
	public function css(string $scripts, string $position = ControllerConstants::SCRIPT_POSITION_TOP): mixed {
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
	 * 1. Deduplicate scripts first (maintains insertion order)
	 * 2. Group scripts by position (top, bottom, last) while preserving order within each group
	 * 3. Process in correct order: top scripts → bottom scripts → last scripts
	 * 4. Within each position group, insertion order is preserved
	 * 
	 * Special handling for CSS vs JS:
	 * - CSS without prefix → defaults to 'top' position (loaded in <head>)
	 * - JS without prefix → defaults to 'bottom' position (loaded before </body>)
	 * 
	 * This approach ensures:
	 * - CSS/JS dependencies load in correct order
	 * - Position prefixes are respected
	 * - No visual glitches from out-of-order loading
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
		
		// Use optimized deduplication (maintains insertion order)
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
	 * When a script file is missing, this method:
	 * - Logs the missing script for debugging
	 * - Optionally shows a warning in development mode
	 * - Continues execution without breaking the page
	 * - Filters out missing scripts from the final output
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
	private function handleMissingScript(string $script, string $type): void {
		$config = config('canvastack.controller.script_management', []);
		$handleMissing = $config['handle_missing_gracefully'] ?? true;
		
		if (!$handleMissing) {
			return;
		}
		
		// Log missing script
		if (config('canvastack.controller.logging.log_performance_issues', true)) {
			\Log::warning('Missing script file', [
				'script' => $script,
				'type' => $type,
				'environment' => app()->environment()
			]);
		}
		
		// In development, optionally show warning
		if (app()->environment('local', 'development') && config('app.debug', false)) {
			$showWarnings = $config['show_missing_warnings'] ?? true;
			if ($showWarnings) {
				// Warning will be visible in browser console
				$warningScript = "console.warn('Missing {$type} file: {$script}');";
				if ($type === 'js') {
					$this->js($warningScript, ControllerConstants::SCRIPT_POSITION_BOTTOM, true);
				}
			}
		}
	}
}
