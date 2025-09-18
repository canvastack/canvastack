<?php

namespace Canvastack\Canvastack\Core\Craft\Rendering;

use Canvastack\Canvastack\Models\Admin\System\Preference;

/**
 * ViewRenderer Trait
 * 
 * Menangani logika rendering utama, konfigurasi view, dan content merging.
 * Extracted from View.php untuk better separation of concerns.
 * 
 * Responsibilities:
 * - Main render logic dan orchestration
 * - View configuration (admin/front page)
 * - Content merging dan data preparation
 * - Layout setup (sidebar, breadcrumbs)
 * - Page type management
 * - Login page handling
 * - Preferences management
 * 
 * @author wisnuwidi@canvastack.com - 2021
 */
trait ViewRenderer
{
    public $pageType = false;
    private $pageView;
    private $viewAdmin;
    private $viewFront;
    private $dataOptions = [];
    protected $hideFields = [];
    protected $excludeFields = [];
    public $is_root = false;
    public $filter_page = [];
    public $page_name = null;
    private $preference;

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

            // Debug session - Environment aware logging
            if (app()->environment(['local', 'testing'])) {
                \Log::debug('ViewRenderer session debug', [
                    'session_id' => $this->session['id'] ?? 'NOT SET',
                    'auth_check' => \Illuminate\Support\Facades\Auth::check(),
                    'has_session_data' => !empty($this->session),
                ]);
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

    /**
     * Handle login page rendering.
     *
     * @return array Login page data
     */
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

    /**
     * Set page type (admin or front page).
     *
     * @param mixed $page_type
     */
    public function setPageType($page_type = true)
    {
        if (false === $page_type || str_contains($page_type, 'front') || str_contains($page_type, 'index')) {
            $this->pageType = 'frontpage';
        } else {
            $this->pageType = 'adminpage';
        }
    }

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

    /**
     * Set admin URI configuration.
     *
     * @param string $uri
     */
    private function uriAdmin($uri = 'index')
    {
        $this->viewAdmin = canvastack_config('template').'.pages.admin';
        $this->pageView = $this->viewAdmin.'.'.$uri;
    }

    /**
     * Set front URI configuration.
     *
     * @param string $uri
     */
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

    /**
     * Get logo path with optional thumbnail.
     *
     * @param bool $thumb
     * @return string Logo path
     */
    public function logo_path($thumb = false)
    {
        $this->getPreferences();

        if (true === $thumb) {
            return $this->preference['logo_thumb'];
        } else {
            return $this->preference['logo'];
        }
    }

    /**
     * Check and remove buttons based on configuration.
     */
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
}