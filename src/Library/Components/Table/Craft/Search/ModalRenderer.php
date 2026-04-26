<?php
namespace Canvastack\Canvastack\Library\Components\Table\Craft\Search;

use Canvastack\Canvastack\Library\Components\Table\Craft\Search\Config\SearchConfig;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\TableConstants;
use Canvastack\Canvastack\Library\Exceptions\Table\TableValidationException;

/**
 * ModalRenderer - HTML modal generation for Search component
 *
 * @filesource ModalRenderer.php
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 *
 * @security XSS Prevention - table name used as modal title is escaped via
 *           escapeHtml() before rendering. Final HTML output is marked with
 *           SafeHtml::mark() to prevent double-encoding downstream.
 */
class ModalRenderer {
	
	private SearchConfig $config;
	private string|bool $html = false;
	
	/**
	 * Constructor
	 *
	 * @param SearchConfig $config Configuration object
	 */
	public function __construct(SearchConfig $config) {
		$this->config = $config;
	}
	
	/**
	 * Generate modal HTML (SECURE - XSS protected)
	 *
	 * @security XSS Prevention - $tablename is escaped via escapeHtml() before
	 *           being used as the modal box title. The final HTML output is marked
	 *           with SafeHtml::mark() to prevent double-encoding downstream.
	 *
	 * @param string $info Component info
	 * @param string $tablename Table name
	 * @param array $formElements Form elements
	 * @param ScriptGenerator $scriptGenerator Script generator instance
	 * @param array $script_elements Script elements
	 * @param array $filterQuery Filter query
	 * @return void
	 */
	public function generateModalHTML(string $info, string $tablename, array $formElements, ScriptGenerator $scriptGenerator, array $script_elements, array $filterQuery): void {
		// FIX XSS: Escape tablename for display
		$boxTitle = $this->escapeHtml(ucwords(str_replace('-', ' ', canvastack_clean_strings($tablename))));
		$boxName = $info . 'modalBOX';
		
		// Generate scripts
		$scriptGenerator->generateScripts($script_elements, $tablename, $boxName, $filterQuery);
		
		// Generate modal HTML and mark as safe (already escaped)
		$rawHtml = canvastack_modal_content_html($boxName, $boxTitle, $formElements);
		$this->html = SafeHtml::mark($rawHtml);
	}
	
	/**
	 * Escape HTML to prevent XSS
	 *
	 * @security XSS Prevention - uses htmlspecialchars with ENT_QUOTES and UTF-8
	 *           to escape all special HTML characters in user-controllable values.
	 *
	 * @param string $value Value to escape
	 * @return string Escaped value
	 */
	public function escapeHtml(string $value): string {
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
	
	/**
	 * Get generated HTML
	 *
	 * @return string|false
	 */
	public function getHtml(): string|false {
		return $this->html;
	}
}
