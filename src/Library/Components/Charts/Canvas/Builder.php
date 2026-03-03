<?php
namespace Canvastack\Origin\Library\Components\Charts\Canvas;

use Canvastack\Origin\Library\Components\Charts\Canvas\Scripts;

/**
 * Created on May 23, 2023
 * 
 * Time Created : 4:31:19 PM
 *
 * @filesource  Builder.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 * @email       wisnuwidi@gmail.com
 */
class Builder {
	use Scripts;
	
	public function __construct() {}
	
	public $model;
	
	protected $canvas = [];
	public $chartURI  = [];
	public function chartCanvas($identity = [], $filters = []) {
		$this->chartURI[$identity] = url(canvas_current_route()->uri) . "?renderCharts=true";
		$dataAjax                  = [];
		$dataAjax['info']          = $this->identities[$identity];
		$dataAjax['params']        = $this->params[$identity];
		$methodValues              = json_encode([$this->chartPostData => canvas_encrypt(json_encode($dataAjax))]);
		
		$params = [];
		if (!empty($this->params[$identity])) {
			if (!empty($filters)) $this->params[$identity]['filter']['where'] = $filters;
			$params = $this->params[$identity];
		}
		
		$attributes = [];
		if (!empty($this->attributes[$identity])) {
			$attributes = $this->attributes[$identity];
		}
		
		$htmlCanvas         = "<div id=\"{$identity}\">CanvaStack Chart Canvas</div>";
		$chartscripts       = '<script type="text/javascript">';
		$chartscripts      .= $this->canvascipt($identity, $this->identities[$identity]['string'], $params, $attributes);
		$chartscripts      .= $this->ajaxProcess($this->identities[$identity]['string'], $this->chartURI[$identity], $methodValues, $this->chartPostData);
		$chartscripts      .= '</script>';
		$canvas             = $htmlCanvas . $chartscripts;
		
		return $this->draw($canvas);
	}
}