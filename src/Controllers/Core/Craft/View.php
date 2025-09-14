<?php

namespace Canvastack\Canvastack\Controllers\Core\Craft;

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
trait View
{
    use Action;

    public $pageType = false;
    private $pageView;
    private $viewAdmin;
    private $viewFront;
    private $dataOptions = [];
    protected $hideFields = [];
    protected $excludeFields = [];

    /**
     * Get Some Hidden Set Field(s)
     */
    private function getHiddenFields()
    {
        if (! empty($this->form)) {
            $this->form->hideFields = $this->hideFields;
        }
    }

    /**
     * Get Some Exclude Set / Drop Off Field(s)
     */
    private function getExcludeFields()
    {
        if (! empty($this->form)) {
            $this->form->excludeFields = $this->excludeFields;
        }
    }

    /**
     * Render the view with components, datatables, and merged data.
     *
     * @param mixed $data Optional data to merge into content_page
     * @return \Illuminate\View\View
     * @throws \InvalidArgumentException If component render fails
     */
    public function render($data = false)
    {
        try {
            $this->configViewIfNeeded();

            $this->setBasicData();

            $components = $this->renderComponents();

            $this->addScriptsFromElements();

            if ($result = $this->handleDatatables(request())) {
                return $result;
            }

            if ($result = $this->handleSpecialCases(request())) {
                return $result;
            }

            $this->setupLayout();

            if ($this->is_module_granted) {
                $this->data['breadcrumbs'] = $this->template->breadcrumbs ?? [];
                $this->data['content_page'] = $this->mergeContent($data, $components);
            } else {
                $this->data['breadcrumbs'] = null;
                $this->data['route_info'] = null;
            }

            if (empty($this->session['id'] ?? '')) {
                $this->data['content_page'] = $this->loginPage();
            }

            $this->checkIfAnyButtonRemoved();

            return view($this->pageView, $this->data, $this->dataOptions);
        } catch (\Throwable $e) {
            \Log::error('Render error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new \InvalidArgumentException('Failed to render view: ' . $e->getMessage());
        }
    }

    /**
     * Configure view if not already set.
     */
    private function configViewIfNeeded()
    {
        if (empty($this->pageView)) {
            $this->configView();
        }
    }

    /**
     * Set basic data like app name and logo.
     */
    private function setBasicData()
    {
        $this->data['appName'] = canvastack_config('app_name');
        $this->data['logo'] = $this->logo_path();
    }

    /**
     * Render form, table, and chart components.
     *
     * @return array Rendered elements
     */
    private function renderComponents()
    {
        $formElements = [];
        if (! empty($this->data['components']->form->elements)) {
            $this->form->setValidations($this->validations);
            $formElements = $this->form->render($this->data['components']->form->elements);
        }

        $tableElements = [];
        if (! empty($this->data['components']->table->elements)) {
            $tableElements = $this->table->render($this->data['components']->table->elements);
        }

        $chartElements = [];
        if (! empty($this->data['components']->chart->elements)) {
            $chartElements = $this->chart->render($this->data['components']->chart->elements);
        }

        return [
            'form' => $formElements,
            'table' => $tableElements,
            'chart' => $chartElements,
        ];
    }

    /**
     * Handle datatables rendering for POST and GET.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed Return early if datatables rendered
     */
    private function handleDatatables(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'renderDataTables' => 'nullable|in:true,false,1,0',
        ]);

        if (! empty($this->data['components']->table->method) && 'post' === strtolower($this->data['components']->table->method)) {
            // Handle POST method datatables - SAME PIPELINE AS GET METHOD
            if ($request->isMethod('post') && !empty($request->get('renderDataTables'))) {
                // Use the SAME pipeline as GET method to ensure consistent DT_RowAttr generation
                $filter_datatables = $this->model_filters ?? [];
                $method = $request->all();
                
                // Ensure difta key exists for POST requests
                if (!isset($method['difta'])) {
                    $method['difta'] = ['name' => array_keys($this->data['components']->table->model)[0] ?? '', 'source' => 'dynamics'];
                }
                
                return $this->initRenderDatatables($method, $this->data['components']->table, $filter_datatables);
            }
            
            $filter_datatables = $this->model_filters ?? [];
            $method = [
                'method' => 'post',
                'renderDataTables' => true,
                'difta' => ['name' => array_keys($this->data['components']->table->model)[0] ?? '', 'source' => 'dynamics'],
            ];

            $this->initRenderDatatables($method, $this->data['components']->table, $filter_datatables);

            return null;
        }

        if (! empty($validated['renderDataTables']) && 'false' != $validated['renderDataTables']) {
            $filter_datatables = $this->model_filters ?? [];
            $method = $request->all();
            
            // Ensure difta key exists for GET requests
            if (!isset($method['difta'])) {
                $method['difta'] = ['name' => array_keys($this->data['components']->table->model)[0] ?? '', 'source' => 'dynamics'];
            }
            
            return $this->initRenderDatatables($method, $this->data['components']->table, $filter_datatables);
        }

        return null;
    }

    /**
     * Handle special cases like AJAX.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed Return early if special case handled
     */
    private function handleSpecialCases(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'ajaxfproc' => 'nullable|in:true,false,1,0',
        ]);

        if (! empty($validated['ajaxfproc'])) {
            return $this->form->ajaxProcessing();
        }

        return null;
    }

    /**
     * Setup layout elements like sidebar and breadcrumbs.
     */
    private function setupLayout()
    {
        $this->template->render_sidebar_menu($this->menu ?? []);
        $this->data['menu_sidebar'] = $this->template->menu_sidebar ?? [];

        $this->template->render_sidebar_content();
        $this->data['sidebar_content'] = $this->template->sidebar_content ?? [];
    }

    /**
     * Merge content data with components.
     *
     * @param mixed $data
     * @param array $components
     * @return array Merged content
     */
    private function mergeContent($data, array $components)
    {
        return collect($this->data['content_page'] ?? [])
            ->merge($data ?? [])
            ->merge($components['form'])
            ->merge($components['table'])
            ->merge($components['chart'])
            ->toArray();
    }

    public function initRenderCharts($method, $data = [], $model_filters = [])
    {
        if (! empty($data)) {
            $dataChart = $data;
        } else {
            $dataChart = $this->data['components']->chart;
        }

        if (! empty($dataChart)) {
            //	dd($this->chart);
        }
    }

    public $initRenderDatatablePost = [];

    private function initRenderDatatables($method, $data = [], $model_filters = [])
    {
        if (! empty($data)) {
            $dataTable = $data;
        } else {
            $dataTable = $this->data['components']->table;
        }

        if (! empty($dataTable)) {
            $Datatables = [];
            $Datatables['datatables'] = $dataTable;
            $datatables = canvastack_array_to_object_recursive($Datatables);

            $filters = [];
            if (! empty($method['filters'])) {
                if ('true' === $method['filters']) {
                    $filters = $method;
                }
            }

            // Hybrid-compare: run pipeline preflight + legacy, return legacy result
            // CRITICAL FIX: Check hybrid mode BEFORE POST method handling to ensure POST also uses hybrid mode
            if (function_exists('config') && config('canvastack.datatables.mode') === 'hybrid') {
                try {
                    $result = \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\HybridCompare::run($method, $datatables, $filters, $model_filters);
                    if (function_exists('logger')) {
                        logger()->debug('[DT Hybrid] Diff', $result['diff'] ?? []);
                    }

                    return $result['legacy_result'];
                } catch (\Throwable $e) {
                    if (function_exists('logger')) {
                        logger()->warning('[DT Hybrid] Error '.$e->getMessage());
                    }
                    // fall through to legacy
                }
            }

            $DataTables = new Datatables();
            if (! empty($method['method']) && 'post' === $method['method']) {
                // CRITICAL FIX: Use the SAME pipeline as GET method for POST requests
                // This ensures DataTables parameters (draw, start, length, order, columns, search) are properly processed
                
                \Log::info('View::initRenderDatatables - POST method using GET pipeline', [
                    'method_keys' => array_keys($method),
                    'has_draw' => isset($method['draw']),
                    'has_order' => isset($method['order']),
                    'has_columns' => isset($method['columns']),
                    'has_search' => isset($method['search'])
                ]);
                
                // Use the SAME process() method as GET - this is the key fix!
                return $DataTables->process($method, $datatables, $filters, $model_filters);
            }

            return $DataTables->process($method, $datatables, $filters, $model_filters);
        }
    }

    private function checkIfAnyButtonRemoved()
    {
        if (! empty($this->removeButtons)) {
            $add = null;
            $view = null;
            $delete = null;
            $back = null;

            foreach ($this->removeButtons as $action) {
                if (canvastack_string_contained($action, 'add')) {
                    $add = $action;
                }
                if (canvastack_string_contained($action, 'view')) {
                    $view = $action;
                }
                if (canvastack_string_contained($action, 'delete')) {
                    $delete = $action;
                }
                if (canvastack_string_contained($action, 'back')) {
                    $back = $action;
                }
            }

            if (! empty($this->data['route_info'])) {
                foreach (array_keys($this->data['route_info']->action_page) as $key) {
                    if (! empty($add) && canvastack_string_contained($key, $add)) {
                        unset($this->data['route_info']->action_page[$key]);
                    }
                    if (! empty($view) && canvastack_string_contained($key, $view)) {
                        unset($this->data['route_info']->action_page[$key]);
                    }
                    if (! empty($delete) && canvastack_string_contained($key, $delete)) {
                        unset($this->data['route_info']->action_page[$key]);
                    }
                    if (! empty($back) && canvastack_string_contained($key, $back)) {
                        unset($this->data['route_info']->action_page[$key]);
                    }
                }
            }
        }
    }

    private function loginPage()
    {
        $page = new Preference();
        $obj = $page->first()->getAttributes();
        $data = [];

        if (! empty($obj)) {
            if (! empty($obj['logo'])) {
                $data['login_page']['logo'] = $obj['logo'];
            }
            if (! empty($obj['login_title'])) {
                $data['login_page']['title'] = $obj['login_title'];
            }
            if (! empty($obj['login_background'])) {
                $data['login_page']['background'] = $obj['login_background'];
            }
        }

        return $data;
    }

    public function setPageType($page_type = true)
    {
        if (false === $page_type || str_contains($page_type, 'front') || str_contains($page_type, 'index')) {
            $this->pageType = 'frontpage';
        } else {
            $this->pageType = 'adminpage';
        }
    }

    public $is_root = false;
    public $filter_page = [];
    public $page_name = null;

    /**
     * Set Page Attributes
     *
     * created @Apr 8, 2018
     * author: wisnuwidi
     *
     * @param  string  $page
     * @param  string  $url
     */
    protected function setPage($page = null, $path = false)
    {
        $this->page_name = $page;
        $this->set_session();
        if (! empty($this->session['user_group'] ?? '')) {
            $this->is_root = str_contains($this->session['user_group'], 'root');
        }
        $this->routeInfo();

        if (! empty($this->session['id'] ?? '')) {
            $this->filter_page = canvastack_mapping_page(intval($this->session['id']));
        }
        if (! empty($this->model_class)) {
            $this->model($this->model_class);
        }
        if (is_empty($page)) {
            $currentPage = last(explode('.', current_route()));
            if (str_contains(strtolower($currentPage), 'index')) {
                $currentModule = strtolower(str_replace('Controller', '', class_basename($this))).' Lists';
            } else {
                $currentModule = $currentPage.' '.strtolower(str_replace('Controller', '', class_basename($this)));
            }
        } else {
            $currentModule = $page;
        }

        $page_name = canvastack_underscore_to_camelcase($currentModule);
        $page_title = strtolower($currentModule);

        $this->meta->title($page_name);
        $this->template->set_breadcrumb(
            $page_name, [$page_title => url($this->template->currentURL), 'index'],
            $page_title, [$page_title, 'home']
        );
        $this->configView($path);
        $this->filterPage();
        canvastack_log_activity();
    }

    private function uriAdmin($uri = 'index')
    {
        $this->viewAdmin = canvastack_config('template').'.pages.admin';
        $this->pageView = $this->viewAdmin.'.'.$uri;
    }

    private function uriFront($uri = 'index')
    {
        $this->viewFront = canvastack_config('template').'.pages.front';
        $this->pageView = $this->viewFront.'.'.$uri;
    }

    /**
     * Configure View Path with spesification page type[ front page or admin page ]
     *
     * @param  string  $path
     */
    private function configView($path = false, $pageAdmin = true)
    {
        if (! empty($this->pageType) && 'login' !== current_route()) {
            $pageAdmin = false; // as user in frontpage
        }

        $this->setPageType($pageAdmin);
        $page_type = str_replace('page', '', $this->pageType);

        if (false !== $page_type) {
            if ('admin' === $page_type) {
                if ($path != false) {
                    $this->uriAdmin($path);
                } else {
                    $this->uriAdmin();
                }
            } else {
                if ($path != false) {
                    $this->uriFront($path);
                } else {
                    $this->uriFront();
                }
            }
        } else {
            if ($path != false) {
                $this->uriAdmin($path);
            } else {
                $this->uriAdmin();
            }
        }

        $this->data['page_type'] = $this->pageType;
        $this->data['page_view'] = $this->pageView;
    }

    private $preference;

    /**
     * Get All Web Preferences
     *
     * created @Aug 21, 2018
     * author: wisnuwidi
     */
    private function getPreferences()
    {
        $this->preference = canvastack_get_model_data(Preference::class);
    }

    public function logo_path($thumb = false)
    {
        $this->getPreferences();

        if (true === $thumb) {
            return $this->preference['logo_thumb'];
        } else {
            return $this->preference['logo'];
        }
    }
}
