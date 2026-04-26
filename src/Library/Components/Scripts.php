<?php
namespace Canvastack\Canvastack\Library\Components;

/**
 * Created on 12 Mar 2021
 * Time Created : 09:18:04
 *
 * @filesource Scripts.php
 *
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 */
 
class Scripts {
	
	public $baseURL;
	public $assetPath;
	public $currentURL;
	public $scripts = [];
	
	public function __construct() {
		$this->baseURL    = canvastack_config('baseURL');
		$this->currentURL = canvastack_url('current');
		$this->assetPath  = canvastack_script_asset_path();
	}
	
	/**
	 * Render Javascript HTML
	 *
	 * @param string $scripts
	 * @param string $position
	 * 		: default [general script located in top],
	 * 		top [by default, position set in top],
	 * 		bottom
	 */
	public function js($scripts, $position = 'bottom', $as_script_code = false, $loadMode = null) {
			if (!is_array($scripts)) {
				if (!empty($this->check_js_strings($scripts, $as_script_code, $loadMode))) {
					$scriptObj = $this->check_js_strings($scripts, $as_script_code, $loadMode);

					// Minify inline scripts if enabled
					if ($as_script_code && function_exists('canvastack_minify_inline_script')) {
						$scriptObj = $this->minifyScriptObject($scriptObj);
					}

					$this->scripts [__FUNCTION__] [$position] [] = $scriptObj;
				}
			}

			if (is_array($scripts)) {
				foreach($scripts as $script) {
					if (!empty($this->check_js_strings($script, $as_script_code, $loadMode))) {
						$scriptObj = $this->check_js_strings($script, $as_script_code, $loadMode);

						// Minify inline scripts if enabled
						if ($as_script_code && function_exists('canvastack_minify_inline_script')) {
							$scriptObj = $this->minifyScriptObject($scriptObj);
						}

						$this->scripts [__FUNCTION__] [$position] [] = $scriptObj;
					}
				}
			}

			return $this->scripts;
		}
	
	/**
	 * Render CSS HTML
	 *
	 * @param string $scripts
	 * @param string $position
	 * 		: default [general script located in top],
	 * 		top [by default, position set in top],
	 * 		bottom
	 */
	public function css($scripts, $position = 'top', $as_script_code = false) {
		if (!is_array($scripts)) {
			$this->scripts [__FUNCTION__] [$position] [] = $this->check_css_strings($scripts, $as_script_code);
		}
		
		if (is_array($scripts)) {
			foreach($scripts as $script) {
				$this->scripts [__FUNCTION__] [$position] [] = $this->check_css_strings($script, $as_script_code);
			}
		}
		
		return $this->scripts;
	}
	private function check_js_strings($string, $as_script_code = false, $loadMode = null) {
		$containedType = 'type="text/javascript"';
		$containedTag  = '<script';
		$containedSrc  = 'src=';
		
		$scriptsText = [ ];
		$scriptsHTML = [ ];
		
		// Build load mode attribute (async or defer)
		$loadModeAttr = '';
		if (!$as_script_code && in_array($loadMode, ['async', 'defer'])) {
			$loadModeAttr = ' ' . $loadMode;
		}
		
		if (true === $as_script_code) {
			return canvastack_array_to_object_recursive([
				'url'  => false,
				'html' => '<script type="text/javascript">' . $string . '</script>'
			]);
		}
		
		if ((str_contains($string, $containedType) || str_contains($string, $containedTag)) || (str_contains($string, $containedSrc))) {
			// Get script in HTML
			$scriptsText = canvastack_script_html_element_value($string, 'script', 'src', false);
			$scriptsHTML = str_replace('</script>', '', canvastack_script_html_element_value($string, 'script', 'src'));
			
			// Add async/defer attribute if not already present and loadMode is specified
			if ($loadModeAttr && !str_contains($scriptsHTML, 'async') && !str_contains($scriptsHTML, 'defer')) {
				$scriptsHTML = str_replace('<script', '<script' . $loadModeAttr, $scriptsHTML);
			}
			
			$scriptsHTML .= '</script>';
		} else {
			// Get script in text
			$scriptsText = canvastack_script_check_string_path($string);
			$scriptsHTML = '<script type="text/javascript" src="' . canvastack_script_check_string_path($string) . '"' . $loadModeAttr . '></script>';
		}
		
		if (!empty($scriptsText)) {
			return canvastack_array_to_object_recursive([
				'url' => $scriptsText,
				'html' => $scriptsHTML
			]);
		}
	}
	private function check_css_strings($string, $as_script_code = false) {
		$containedType = 'rel="stylesheet"';
		$containedTag  = '<link';
		$containedSrc  = 'href=';
		
		$scriptsText   = [];
		$scriptsHTML   = [];
		
		if (true === $as_script_code) {
			return canvastack_array_to_object_recursive([
				'url'  => false,
				'html' => '<style>' . $string . '</style>'
			]);
		}
		
		if ((str_contains($string, $containedType) || str_contains($string, $containedTag)) || (str_contains($string, $containedSrc))) {
			// Get script in HTML
			$scriptsText = canvastack_script_html_element_value($string, 'link', 'href', false);
			$scriptsHTML = canvastack_script_html_element_value($string, 'link', 'href');
		} else {
			// Get script in text
			$scriptsText = $string;
			$scriptsHTML = '<link rel="stylesheet" href="' . canvastack_script_check_string_path($string) . '" />';
		}
		
		return canvastack_array_to_object_recursive([
			'url'  => $scriptsText,
			'html' => $scriptsHTML
		]);
	}
	
	/**
	 * Minify script object HTML content
	 * 
	 * Extracts JavaScript code from <script> tag, minifies it, and returns
	 * the script object with minified HTML.
	 * 
	 * @param object $scriptObj Script object with 'html' property containing <script> tag
	 * @return object Script object with minified HTML
	 */
	private function minifyScriptObject($scriptObj) {
		if (!isset($scriptObj->html) || empty($scriptObj->html)) {
			return $scriptObj;
		}
		
		$html = $scriptObj->html;
		
		// Extract script content from <script> tag
		if (preg_match('/<script[^>]*>(.*?)<\/script>/s', $html, $matches)) {
			$scriptContent = $matches[1];
			
			// Minify the content
			$minifiedContent = canvastack_minify_inline_script($scriptContent);
			
			// Replace original content with minified version
			$scriptObj->html = str_replace($matches[1], $minifiedContent, $html);
		}
		
		return $scriptObj;
	}
	
	/**
	 * Concatenate multiple inline scripts into a single script tag
	 * 
	 * Combines multiple inline JavaScript code blocks into one script tag to reduce
	 * the number of script tags in the HTML output. This improves page load performance
	 * by reducing DOM parsing overhead.
	 * 
	 * Features:
	 * - Only concatenates inline scripts (url === false)
	 * - Preserves external scripts (with src attribute)
	 * - Wraps each script in IIFE to prevent variable conflicts
	 * - Adds semicolons between scripts for safety
	 * - Respects minification settings
	 * - Configurable via canvastack.controller.script_management.enable_concatenation
	 * 
	 * @param array $scripts Array of script objects with 'url' and 'html' properties
	 * @param string $position Script position ('top' or 'bottom')
	 * @return array Modified array with concatenated inline scripts
	 * 
	 * @example
	 * // Before concatenation:
	 * // <script>console.log('A');</script>
	 * // <script>console.log('B');</script>
	 * // <script>console.log('C');</script>
	 * 
	 * // After concatenation:
	 * // <script>
	 * // (function(){console.log('A');})();
	 * // (function(){console.log('B');})();
	 * // (function(){console.log('C');})();
	 * // </script>
	 * 
	 * @performance Reduces number of script tags by ~70-90% for pages with many inline scripts
	 * @performance Reduces DOM parsing time and memory usage
	 */
	protected function concatenateScripts(array $scripts, string $position): array {
		// Check if concatenation is enabled
		$config = config('canvastack.controller.script_management', []);
		$concatenationEnabled = $config['enable_concatenation'] ?? false;
		
		error_log("=== concatenateScripts() called for position: {$position} ===");
		error_log("Concatenation enabled: " . ($concatenationEnabled ? 'YES' : 'NO'));
		error_log("Script count: " . count($scripts));
		
		// Debug logging
		if (config('app.debug', false)) {
			\Log::info('Scripts: concatenateScripts() called', [
				'position' => $position,
				'enabled' => $concatenationEnabled,
				'script_count' => count($scripts)
			]);
		}
		
		if (!$concatenationEnabled || empty($scripts)) {
			error_log("Returning early - enabled: {$concatenationEnabled}, empty: " . (empty($scripts) ? 'YES' : 'NO'));
			return $scripts;
		}
		
		$inlineScripts = [];
		$externalScripts = [];
		
		// Separate inline and external scripts
		foreach ($scripts as $scriptObj) {
			if (isset($scriptObj->url) && $scriptObj->url === false) {
				// Inline script
				$inlineScripts[] = $scriptObj;
				error_log("Found INLINE script");
			} else {
				// External script (has src attribute)
				$externalScripts[] = $scriptObj;
				error_log("Found EXTERNAL script: " . ($scriptObj->url ?? 'unknown'));
			}
		}
		
		error_log("Inline scripts: " . count($inlineScripts) . ", External scripts: " . count($externalScripts));
		
		// Debug logging
		if (config('app.debug', false)) {
			\Log::info('Scripts: Separated scripts', [
				'position' => $position,
				'inline_count' => count($inlineScripts),
				'external_count' => count($externalScripts)
			]);
		}
		
		// If no inline scripts or only one, no need to concatenate
		if (count($inlineScripts) <= 1) {
			error_log("Not enough inline scripts to concatenate (need > 1, have " . count($inlineScripts) . ")");
			return $scripts;
		}
		
		error_log("Starting concatenation of " . count($inlineScripts) . " inline scripts...");
		
		// Extract script content from each inline script
		$concatenatedContent = [];
		foreach ($inlineScripts as $scriptObj) {
			if (isset($scriptObj->html) && !empty($scriptObj->html)) {
				// Extract content between <script> tags
				if (preg_match('/<script[^>]*>(.*?)<\/script>/s', $scriptObj->html, $matches)) {
					$content = trim($matches[1]);
					if (!empty($content)) {
						// Wrap in IIFE to prevent variable conflicts
						// Add semicolon for safety
						$concatenatedContent[] = "(function(){{$content}})();";
						error_log("Extracted and wrapped script content (" . strlen($content) . " chars)");
					}
				}
			}
		}
		
		// If no content extracted, return original
		if (empty($concatenatedContent)) {
			error_log("No content extracted - returning original");
			return $scripts;
		}
		
		// Join all scripts with newlines
		$finalContent = implode("\n", $concatenatedContent);
		
		error_log("Concatenated " . count($concatenatedContent) . " scripts into " . strlen($finalContent) . " chars");
		
		// Debug logging
		if (config('app.debug', false)) {
			\Log::info('Scripts: Concatenated content', [
				'position' => $position,
				'original_scripts' => count($inlineScripts),
				'content_length' => strlen($finalContent)
			]);
		}
		
		// Minify if enabled
		if (function_exists('canvastack_minify_inline_script')) {
			$beforeMinify = strlen($finalContent);
			$finalContent = canvastack_minify_inline_script($finalContent);
			error_log("Minified: {$beforeMinify} -> " . strlen($finalContent) . " chars");
		}
		
		// Create single concatenated script object
		$concatenatedScript = canvastack_array_to_object_recursive([
			'url' => false,
			'html' => '<script type="text/javascript">' . $finalContent . '</script>'
		]);
		
		// Return external scripts + single concatenated inline script
		$result = $externalScripts;
		$result[] = $concatenatedScript;
		
		error_log("Final result: " . count($result) . " scripts (" . count($externalScripts) . " external + 1 concatenated)");
		
		// Debug logging
		if (config('app.debug', false)) {
			\Log::info('Scripts: Concatenation complete', [
				'position' => $position,
				'result_count' => count($result),
				'external' => count($externalScripts),
				'concatenated' => 1
			]);
		}
		
		return $result;
	}
}
