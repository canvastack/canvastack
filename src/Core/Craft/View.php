<?php

namespace Canvastack\Canvastack\Core\Craft;

use Canvastack\Canvastack\Core\Craft\Rendering\ViewRenderer;
use Canvastack\Canvastack\Core\Craft\Rendering\ComponentRenderer;
use Canvastack\Canvastack\Core\Craft\Rendering\DatatableRenderer;
use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;
use Canvastack\Canvastack\Models\Admin\System\Preference;

/**
 * Created on 25 Mar 2021
 * Time Created	: 12:53:25
 *
 * @filesource	View.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
/**
 * Trait untuk operasi View di CanvaStack.
 * Menangani rendering, komponen, dan datatables.
 * 
 * Trait ini telah direfactor menjadi beberapa trait yang lebih fokus:
 * - ViewRenderer: Main render logic, view configuration, content merging
 * - ComponentRenderer: Form, table, chart component rendering
 * - DatatableRenderer: Datatables processing dan handling
 */
trait View
{
    use Action;
    use ViewRenderer;
    use ComponentRenderer;
    use DatatableRenderer;

    /**
     * Initialize View trait dengan dependencies yang diperlukan.
     * Method ini dapat dipanggil dari controller untuk setup awal.
     */
    protected function initializeView()
    {
        // Initialize shared properties jika diperlukan
        if (!isset($this->pageType)) {
            $this->pageType = false;
        }
        
        if (empty($this->hideFields)) {
            $this->hideFields = [];
        }
        
        if (empty($this->excludeFields)) {
            $this->excludeFields = [];
        }
        
        if (empty($this->dataOptions)) {
            $this->dataOptions = [];
        }
    }

    /**
     * Get current page type.
     *
     * @return string|false
     */
    public function getCurrentPageType()
    {
        return $this->pageType;
    }

    /**
     * Get current page view path.
     *
     * @return string|null
     */
    public function getCurrentPageView()
    {
        return $this->pageView ?? null;
    }

    /**
     * Set hidden fields for form rendering.
     *
     * @param array $fields
     * @return $this
     */
    public function setHideFields(array $fields)
    {
        $this->hideFields = $fields;
        return $this;
    }

    /**
     * Set exclude fields for form rendering.
     *
     * @param array $fields
     * @return $this
     */
    public function setExcludeFields(array $fields)
    {
        $this->excludeFields = $fields;
        return $this;
    }

    /**
     * Add data options for view rendering.
     *
     * @param array $options
     * @return $this
     */
    public function addDataOptions(array $options)
    {
        $this->dataOptions = array_merge($this->dataOptions, $options);
        return $this;
    }

    /**
     * Check if current user is root.
     *
     * @return bool
     */
    public function isRoot()
    {
        return $this->is_root;
    }

    /**
     * Get current page name.
     *
     * @return string|null
     */
    public function getPageName()
    {
        return $this->page_name;
    }

    /**
     * Get filter page configuration.
     *
     * @return array
     */
    public function getFilterPage()
    {
        return $this->filter_page;
    }

    /**
     * Reset view state untuk cleanup setelah rendering.
     */
    public function resetViewState()
    {
        $this->pageType = false;
        $this->pageView = null;
        $this->viewAdmin = null;
        $this->viewFront = null;
        $this->dataOptions = [];
        $this->page_name = null;
        $this->preference = null;
    }
}