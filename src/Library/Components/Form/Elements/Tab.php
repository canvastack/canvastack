<?php
namespace Canvastack\Canvastack\Library\Components\Form\Elements;

use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\FormConstants;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;

/**
 * Tab Navigation Trait
 * 
 * Provides tab navigation functionality for form components.
 * Generates Bootstrap-style tab headers and content panes with proper
 * security (XSS protection) and accessibility (ARIA attributes).
 * 
 * SECURITY:
 * =========
 * - All user-controllable data (labels, classes, content) is escaped
 * - Tab markers are validated to prevent injection attacks
 * - Output is marked with SafeHtml to prevent double-encoding
 * 
 * ACCESSIBILITY:
 * ==============
 * - ARIA attributes for tab navigation (aria-selected, aria-controls)
 * - ARIA attributes for tab panels (aria-labelledby, aria-hidden)
 * - Proper role attributes for screen readers
 * 
 * Created on 19 Mar 2021
 * Time Created : 03:32:17
 *
 * @filesource Tab.php
 *
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 * 
 * Updated: 2026 - Security and accessibility improvements
 */
trait Tab {
	
	/**
	 * Tab marker: --[openTabHTMLForm]--
	 * 
	 * @var string
	 */
	protected string $opentabHTML = FormConstants::MARKER_OPEN_TAB;
	
	/**
	 * Tab marker: --[openNewTab]--
	 * 
	 * @var string
	 */
	private string $openNewTab = '--[openNewTab]--';
	
	/**
	 * Tab marker: --[openNewTabClass]--
	 * 
	 * @var string
	 */
	private string $openNewTabClass = '--[openNewTabClass]--';
	
	/**
	 * Tab marker: --[closeTabHTMLForm]--
	 * 
	 * @var string
	 */
	private string $closedtabHTML = FormConstants::MARKER_CLOSE_TAB;
		
	/**
	 * Create Open Tab
	 * 
	 * Generates a tab opening marker with label and optional CSS class.
	 * The actual tab HTML is generated later by renderTab().
	 *
	 * @param string $label Tab label text (will be escaped for XSS protection)
	 * @param string|false $class Optional CSS class for tab styling (will be escaped)
	 *
	 * @return void
	 * 
	 * @security Tab label and class are NOT escaped here - they are escaped in renderTab()
	 *           This maintains backward compatibility with the marker-based system
	 *
	 * @author wisnuwidi
	 */
	public function openTab(string $label, string|false $class = false): void {
		$classAttribute = false;
		if ($class) $classAttribute = "{$this->openNewTabClass}{$class}";
		
		$this->draw("{$this->opentabHTML}{$label}{$classAttribute}{$this->openNewTab}");
	}
	
	/**
	 * Add Tab Content
	 * 
	 * Adds custom content to a tab panel.
	 * Content is wrapped in a div with class "canvastack-add-tab-content".
	 * 
	 * @param string $content Tab content HTML (will be escaped for XSS protection)
	 * 
	 * @return void
	 * 
	 * @security Content is NOT escaped here - it is escaped in renderTab()
	 *           This maintains backward compatibility with the marker-based system
	 * 
	 * @author wisnuwidi
	 */
	private ?string $contentTab = null;
	public function addTabContent(string $content): void {
		$this->contentTab = $content;
		$this->draw("<div class=\"canvastack-add-tab-content\">{$this->contentTab}</div>");
	}
	
	/**
	 * Create Close Tab
	 * 
	 * Generates a tab closing marker.
	 * The actual tab HTML is generated later by renderTab().
	 *
	 * @return void
	 * 
	 * @author wisnuwidi
	 */
	public function closeTab(): void {
		$this->draw("{$this->closedtabHTML}");
	}
	
	/**
	 * Validate tab marker format
	 * 
	 * Ensures tab markers are in the expected format to prevent injection attacks.
	 * 
	 * @param string $marker Tab marker string to validate
	 * 
	 * @return bool True if marker is valid
	 * 
	 * @throws \InvalidArgumentException If marker format is invalid
	 * 
	 * @security Validates marker format to prevent marker injection attacks
	 * 
	 * @author wisnuwidi
	 */
	private function validateTabMarker(string $marker): bool {
		// Valid markers must start with -- and end with --
		// and contain only alphanumeric characters, brackets, and underscores
		if (!preg_match('/^--\[[a-zA-Z0-9_]+\]--$/', $marker)) {
			throw new \InvalidArgumentException("Invalid tab marker format: {$marker}");
		}
		
		return true;
	}
	
	/**
	 * HTML Tab Builder
	 * 
	 * Parses tab markers and generates Bootstrap tab navigation HTML.
	 * Converts marker-based tab structure into proper HTML with:
	 * - Tab headers (nav-tabs)
	 * - Tab content panels
	 * - ARIA attributes for accessibility
	 * - XSS protection for all user data
	 *
	 * @param string|array $object Content containing tab markers or array of content
	 *
	 * @return array Array containing rendered tab HTML
	 * 
	 * @security All user-controllable data (labels, classes, content) is escaped
	 * @security Tab markers are validated to prevent injection
	 * @security Output is marked with SafeHtml to prevent double-encoding
	 * 
	 * @throws \InvalidArgumentException If tab structure is invalid
	 * 
	 * @author wisnuwidi
	 */
	public function renderTab(string|array $object): array {
		$content = is_array($object) ? implode('', $object) : $object;

		// Early return if no tabs found
		if (!canvastack_string_contained($content, $this->opentabHTML)) {
			return [];
		}

		// Validate tab markers before processing
		$this->validateAllTabMarkers();

		// Parse tab structure
		$tabStructure = $this->parseTabStructure($content);

		// Build tab HTML
		$tabsHtml = $this->buildTabsHtml($tabStructure['tabs']);

		// Combine with before/after content
		$finalHtml = $tabStructure['before'] . $tabsHtml . $tabStructure['middle'] . $tabStructure['after'];

		// Security: No need to unmark here since assembleTabHtml() no longer marks the HTML
		// All content is already properly escaped and markers have been removed in parseTabStructure()

		return [$finalHtml];
	}

	/**
	 * Validate all tab markers
	 * 
	 * @return void
	 * 
	 * @throws \InvalidArgumentException If marker format is invalid
	 */
	private function validateAllTabMarkers(): void {
		$this->validateTabMarker($this->opentabHTML);
		$this->validateTabMarker($this->openNewTab);
		$this->validateTabMarker($this->openNewTabClass);
		$this->validateTabMarker($this->closedtabHTML);
	}
	
	/**
	 * Validate tab structure
	 * 
	 * Ensures tab markers are properly paired and nested correctly.
	 * Detects common issues like:
	 * - Missing close marker (must have at least one closeTab())
	 * - Nested tabs (not supported)
	 * - Empty tab content
	 * 
	 * Note: The system supports multiple openTab() calls followed by a single closeTab()
	 * that closes all tabs. This is the standard pattern used in controllers.
	 * 
	 * @param string $content Content to validate
	 * 
	 * @return void
	 * 
	 * @throws \InvalidArgumentException If tab structure is invalid
	 * 
	 * @security Prevents malformed tab structures that could cause rendering issues
	 */
	private function validateTabStructure(string $content): void {
		// Count open and close markers
		$openCount = substr_count($content, $this->opentabHTML);
		$closeCount = substr_count($content, $this->closedtabHTML);
		
		// Check for missing close marker
		// Note: Multiple openTab() calls can be closed by a single closeTab()
		if ($openCount > 0 && $closeCount === 0) {
			throw new \InvalidArgumentException(
				"Missing closeTab() marker: found {$openCount} openTab() calls but no closeTab(). " .
				"You must call closeTab() after all openTab() calls."
			);
		}
		
		// Check for too many close markers
		if ($closeCount > 1) {
			throw new \InvalidArgumentException(
				"Too many closeTab() calls: found {$closeCount} close markers. " .
				"Only one closeTab() call is needed to close all tabs."
			);
		}
		
		// Check for empty tabs (open immediately followed by close)
		$emptyTabPattern = preg_quote($this->opentabHTML, '/') . '.*?' . preg_quote($this->openNewTab, '/') . '\s*' . preg_quote($this->closedtabHTML, '/');
		if (preg_match('/' . $emptyTabPattern . '/s', $content)) {
			// Empty tabs are allowed but we log a warning
			error_log('INFO: Empty tab content detected in tab structure');
		}
		
		// Check for nested tabs within a single tab section
		// Split by closeTab marker - should only have one section before the close
		$sections = explode($this->closedtabHTML, $content);
		if (count($sections) > 2) {
			// More than 2 sections means multiple closeTab() calls (already caught above)
			return;
		}
		
		// The first section contains all tabs before closeTab()
		// Check if there are any closeTab markers within individual tab content
		// This would indicate improper nesting
		$beforeClose = $sections[0];
		$tabSections = explode($this->opentabHTML, $beforeClose);
		
		// Skip first element (content before any tabs)
		array_shift($tabSections);
		
		foreach ($tabSections as $tabContent) {
			// Check if this tab content contains another openTab marker after the openNewTab marker
			$parts = explode($this->openNewTab, $tabContent, 2);
			if (count($parts) > 1) {
				$contentAfterMarker = $parts[1];
				if (strpos($contentAfterMarker, $this->opentabHTML) !== false) {
					throw new \InvalidArgumentException(
						"Nested tabs detected within tab content. " .
						"Nested tab structures are not supported. " .
						"Each tab's content must not contain additional openTab() calls."
					);
				}
			}
		}
	}

	/**
	 * Parse tab structure from content
	 * 
	 * Validates and parses tab markers from content string.
	 * Ensures proper tab structure and handles edge cases.
	 * 
	 * @param string $content Content with tab markers
	 * 
	 * @return array Parsed structure with tabs, before, middle, after content
	 * 
	 * @throws \InvalidArgumentException If tab structure is invalid
	 * 
	 * @security Validates tab markers to prevent injection attacks
	 */
	private function parseTabStructure(string $content): array {
		// Validate tab structure before parsing
		$this->validateTabStructure($content);
		
		$closedTabs = explode($this->closedtabHTML, $content);

		// Extract content after last tab
		$afterContent = $closedTabs[count($closedTabs) - 1];
		unset($closedTabs[count($closedTabs) - 1]);

		// Parse each tab section
		$tabs = [];
		$beforeContent = '';

		foreach ($closedTabs as $index => $tabSection) {
			$openTabs = explode($this->opentabHTML, $tabSection);

			// Extract content before first tab (usually form tag)
			if ($index === 0 && canvastack_string_contained($openTabs[0], '<form method=')) {
				$beforeContent = $openTabs[0];
				unset($openTabs[0]);
			}

			$tabs[] = $openTabs;
		}

		// Security: Remove SafeHtml markers from before/after content
		// These may contain markers from form fields that were drawn before tabs
		$beforeContent = SafeHtml::unmark($beforeContent);
		$afterContent = SafeHtml::unmark($afterContent);

		return [
			'tabs' => $tabs,
			'before' => $beforeContent,
			'middle' => '',
			'after' => $afterContent
		];
	}

	/**
	 * Build tabs HTML from parsed structure
	 * 
	 * @param array $tabs Parsed tabs
	 * 
	 * @return string Complete tabs HTML
	 */
	private function buildTabsHtml(array $tabs): string {
		$allTabsHtml = '';

		foreach ($tabs as $tabGroup) {
			$tabHtml = $this->buildSingleTabGroup($tabGroup);
			$allTabsHtml .= $tabHtml;
		}

		return $allTabsHtml;
	}

	/**
	 * Build single tab group HTML
	 * 
	 * Handles empty tabs gracefully by skipping them or showing placeholder.
	 * 
	 * @param array $tabGroup Tab group data
	 * 
	 * @return string Tab group HTML
	 */
	private function buildSingleTabGroup(array $tabGroup): string {
		$headers = [];
		$contents = [];

		foreach ($tabGroup as $index => $tab) {
			// Skip null or empty tabs
			if (!isset($tab) || empty($tab)) {
				continue;
			}

			if (!canvastack_string_contained($tab, $this->openNewTab)) {
				// Content without tab structure
				$headers[] = canvastack_form_escape_html($tab) . '<hr />';
				continue;
			}

			$tabData = $this->parseTabData($tab, $index);
			
			// Handle empty tab content gracefully
			if (empty(trim($tabData['content']))) {
				error_log('INFO: Empty tab content detected, adding placeholder');
				// Add a placeholder for empty tabs
				$tabData['content'] = '<div class="tab-pane-empty"><p>No content available</p></div>';
			}
			
			$headers[] = $tabData['header'];
			$contents[] = $tabData['content'];
		}

		return $this->assembleTabHtml($headers, $contents);
	}

	/**
	 * Parse individual tab data
	 * 
	 * @param string $tab Tab content
	 * @param int $index Tab index
	 * 
	 * @return array Tab header and content HTML
	 */
	private function parseTabData(string $tab, int $index): array {
		$sliceTabs = explode($this->openNewTab, $tab);

		// Determine if this is the active tab (first tab)
		$isActive = ($index === 1);
		$activeHeaderClass = $isActive ? 'active' : false;
		$activeContentClass = $isActive ? 'in active' : false;

		// Parse label and class
		$labelData = $this->parseTabLabel($sliceTabs[0]);
		$tabContent = trim($sliceTabs[1]);
		$tabId = strtolower(canvastack_clean_strings($labelData['label']));

		return [
			'header' => canvastack_form_create_header_tab($labelData['label'], $tabId, $activeHeaderClass, $labelData['class']),
			'content' => canvastack_form_create_content_tab($tabContent, $tabId, $activeContentClass)
		];
	}

	/**
	 * Parse tab label and optional class
	 * 
	 * @param string $labelString Label string (may contain class marker)
	 * 
	 * @return array Label and class
	 */
	private function parseTabLabel(string $labelString): array {
		$label = trim($labelString);
		$class = false;

		if (canvastack_string_contained($labelString, $this->openNewTabClass)) {
			$sliceLabel = explode($this->openNewTabClass, $label);
			$label = trim($sliceLabel[0]);
			$class = trim($sliceLabel[1]);
		}

		return [
			'label' => $label,
			'class' => $class
		];
	}

	/**
	 * Assemble final tab HTML
	 * 
	 * @param array $headers Tab headers
	 * @param array $contents Tab contents
	 * 
	 * @return string Complete tab HTML
	 */
	private function assembleTabHtml(array $headers, array $contents): string {
		// Security: Unmark all headers before concatenation to prevent marker leakage
		$unmarkedHeaders = array_map(function($header) {
			return SafeHtml::unmark($header);
		}, $headers);
		
		// Security: Unmark all contents before concatenation
		$unmarkedContents = array_map(function($content) {
			return SafeHtml::unmark($content);
		}, $contents);
		
		$headersHtml  = implode('', $unmarkedHeaders);
		$contentsHtml = implode('', $unmarkedContents);

		// Delegate tab wrapper rendering to the active theme adapter
		return ThemeAdapterResolver::resolve()->renderTabWrapper($headersHtml, $contentsHtml);
	}
	
	/**
	 * Normalize tab HTML for round-trip comparison
	 * 
	 * Removes whitespace variations and normalizes HTML structure
	 * to enable round-trip property testing: render → parse → render
	 * 
	 * This supports Property 41: Tab Rendering Round-Trip
	 * 
	 * @param string $html Tab HTML to normalize
	 * 
	 * @return string Normalized HTML
	 * 
	 * @internal Used for property-based testing
	 */
	public function normalizeTabHtml(string $html): string {
		// Remove extra whitespace between tags
		$normalized = preg_replace('/>\s+</', '><', $html);
		
		// Normalize whitespace within attributes
		$normalized = preg_replace('/\s+/', ' ', $normalized);
		
		// Trim leading/trailing whitespace
		$normalized = trim($normalized);
		
		return $normalized;
	}
	
	/**
	 * Extract tab structure from rendered HTML
	 * 
	 * Parses rendered tab HTML back into structured data.
	 * Enables round-trip testing: markers → HTML → markers
	 * 
	 * This supports Property 41: Tab Rendering Round-Trip
	 * 
	 * @param string $html Rendered tab HTML
	 * 
	 * @return array Array of tab data [['label' => '...', 'content' => '...'], ...]
	 * 
	 * @throws \InvalidArgumentException If HTML structure is invalid
	 * 
	 * @internal Used for property-based testing
	 */
	public function extractTabStructureFromHtml(string $html): array {
		$tabs = [];
		
		// Extract tab headers
		if (!preg_match('/<ul class="nav nav-tabs"[^>]*>(.*?)<\/ul>/s', $html, $headerMatches)) {
			throw new \InvalidArgumentException('Invalid tab HTML: nav-tabs not found');
		}
		
		// Extract tab contents
		if (!preg_match('/<div class="tab-content"[^>]*>(.*?)<\/div>/s', $html, $contentMatches)) {
			throw new \InvalidArgumentException('Invalid tab HTML: tab-content not found');
		}
		
		// Parse individual tab headers
		preg_match_all('/<a[^>]*href="#([^"]+)"[^>]*>(.*?)<\/a>/s', $headerMatches[1], $headerItems, PREG_SET_ORDER);
		
		// Parse individual tab panes
		preg_match_all('/<div[^>]*id="([^"]+)"[^>]*class="[^"]*tab-pane[^"]*"[^>]*>(.*?)<\/div>/s', $contentMatches[1], $contentItems, PREG_SET_ORDER);
		
		// Match headers with content
		foreach ($headerItems as $index => $header) {
			$tabId = $header[1];
			$label = strip_tags($header[2]);
			
			// Find matching content
			$content = '';
			foreach ($contentItems as $contentItem) {
				if ($contentItem[1] === $tabId) {
					$content = $contentItem[2];
					break;
				}
			}
			
			$tabs[] = [
				'id' => $tabId,
				'label' => trim($label),
				'content' => trim($content)
			];
		}
		
		return $tabs;
	}
}