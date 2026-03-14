<?php

namespace Canvastack\Canvastack\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as LaravelController;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Chart\ChartBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;

/**
 * BaseController - CanvaStack Base Controller
 * 
 * Provides unified rendering mechanism for all CanvaStack components.
 * Follows the same pattern as CanvaStack Origin.
 * 
 * Usage:
 * ```php
 * class MyController extends BaseController {
 *     public function index() {
 *         $this->setPage('My Page');
 *         $this->table->setModel(new User());
 *         $this->table->lists('users', ['id:ID', 'name:Name']);
 *         return $this->render();
 *     }
 * }
 * ```
 */
class BaseController extends LaravelController
{
    use AuthorizesRequests, ValidatesRequests;
    
    /**
     * Component instances
     */
    protected FormBuilder $form;
    protected TableBuilder $table;
    protected ChartBuilder $chart;
    protected MetaTags $meta;
    
    /**
     * Data array for view rendering
     */
    public array $data = [];
    
    /**
     * Page configuration
     */
    protected string $pageView = 'canvastack::admin.index';
    protected string $pageType = 'admin';
    protected ?string $pageTitle = null;
    
    /**
     * Model configuration
     */
    protected $model = null;
    protected ?string $connection = null;
    
    /**
     * Constructor - Initialize all components
     */
    public function __construct()
    {
        $this->initializeComponents();
        $this->data['content_page'] = [];
    }
    
    /**
     * Initialize all CanvaStack components
     */
    protected function initializeComponents(): void
    {
        $this->form = app(FormBuilder::class);
        $this->table = app(TableBuilder::class);
        $this->chart = app(ChartBuilder::class);
        $this->meta = app(MetaTags::class);
    }
    
    /**
     * Set page title and meta tags
     */
    protected function setPage(string $title): void
    {
        $this->pageTitle = $title;
        $this->meta->title($title);
    }
    
    /**
     * Set page view path
     */
    protected function setView(string $view): void
    {
        $this->pageView = $view;
    }
    
    /**
     * Render all components and return view
     * 
     * This is the main rendering method that:
     * 1. Renders all configured components (form, table, chart)
     * 2. Merges all component HTML into content_page
     * 3. Returns the view with all data
     * 
     * IMPORTANT: Only renders components that have been configured.
     * Empty/unconfigured components are skipped to avoid rendering empty charts, tables, etc.
     */
    protected function render(array $additionalData = []): \Illuminate\View\View
    {
        // If content_page already set manually (e.g., multi-table), use it
        if (!empty($this->data['content_page'])) {
            // Add meta tags
            $this->data['meta'] = $this->meta;
            $this->data['page_title'] = $this->pageTitle;
            
            // Merge additional data
            $this->data = array_merge($this->data, $additionalData);
            
            return view($this->pageView, $this->data);
        }
        
        // Collect all component HTML
        $components = [];
        
        // Render form component if configured
        if ($this->isFormConfigured()) {
            try {
                $components[] = $this->form->render();
            } catch (\Exception $e) {
                // Form render failed, skip
            }
        }
        
        // Render table component if configured  
        if ($this->isTableConfigured()) {
            try {
                \Log::debug('BaseController: Rendering table...');
                $tableHtml = $this->table->render();
                \Log::debug('BaseController: Table HTML length', ['length' => strlen($tableHtml)]);
                $components[] = $tableHtml;
            } catch (\Exception $e) {
                // Table render failed, skip
                \Log::error('BaseController: Table render failed', ['error' => $e->getMessage()]);
            }
        } else {
            \Log::debug('BaseController: Table not configured, skipping render');
        }
        
        // Render chart component if configured
        if ($this->isChartConfigured()) {
            try {
                $chartHtml = $this->chart->render();
                if (!empty(trim($chartHtml))) {
                    $components[] = $chartHtml;
                }
            } catch (\Exception $e) {
                // Chart render failed, skip
            }
        }
        
        // Set content_page with all component HTML
        $this->data['content_page'] = $components;
        
        // Add meta tags
        $this->data['meta'] = $this->meta;
        $this->data['page_title'] = $this->pageTitle;
        
        // Merge additional data
        $this->data = array_merge($this->data, $additionalData);
        
        return view($this->pageView, $this->data);
    }
    
    /**
     * Check if form component is configured
     */
    protected function isFormConfigured(): bool
    {
        if (empty($this->form)) {
            return false;
        }
        
        // Check if form has fields
        if (method_exists($this->form, 'getFields')) {
            return !empty($this->form->getFields());
        }
        
        return false;
    }
    
    /**
     * Check if table component is configured
     */
    protected function isTableConfigured(): bool
    {
        if (empty($this->table)) {
            \Log::debug('BaseController: Table is empty');
            return false;
        }
        
        // Check if table has tabs
        if (method_exists($this->table, 'hasTabNavigation') && $this->table->hasTabNavigation()) {
            \Log::debug('BaseController: Table has tabs');
            return true;
        }
        
        // Check if table has columns
        if (method_exists($this->table, 'getColumns')) {
            $columns = $this->table->getColumns();
            \Log::debug('BaseController: Table columns', ['columns' => $columns, 'count' => count($columns)]);
            return !empty($columns);
        }
        
        \Log::debug('BaseController: Table does not have getColumns method');
        return false;
    }
    
    /**
     * Check if chart component is configured
     */
    protected function isChartConfigured(): bool
    {
        if (empty($this->chart)) {
            return false;
        }
        
        // Check if chart has data
        // Chart component should have a method to check if it has data
        if (method_exists($this->chart, 'hasData')) {
            return $this->chart->hasData();
        }
        
        // If no hasData() method, assume not configured
        return false;
    }
}
