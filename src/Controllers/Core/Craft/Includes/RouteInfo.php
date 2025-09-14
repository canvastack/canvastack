<?php

namespace Canvastack\Canvastack\Controllers\Core\Craft\Includes;

use Illuminate\Support\Facades\Route;

/**
 * Created on 9 Apr 2021
 * Time Created : 14:49:04
 *
 * @filesource	RouteInfo.php
 *
 * @author    wisnuwidi@canvastack.com - 2021
 * @copyright wisnuwidi
 *
 * @email     wisnuwidi@canvastack.com
 */
trait RouteInfo
{
    use Privileges;

    public $pageInfo;
    public $routeInfo;
    public $route_page;
    public $controllerName;
    public $currentRoute;
    public $actionButton = ['index', 'index', 'edit', 'show'];

    /**
     * Hide action button(s) in module page.
     *
     * created @Aug 10, 2018
     * author: wisnuwidi
     */
    public function hideActionButton()
    {
        $this->actionButton = [];
    }

    public function set_route_page($route)
    {
        $this->route_page = $route;
    }

    private $controllerPathName;

    /**
     * Get Current Page Information Data
     *
     * created @Nov 10, 2018
     * author: wisnuwidi
     */
    private function get_pageinfo()
    {
        $this->currentRoute = Route::getCurrentRoute();
        $action_route = (object) $this->currentRoute->getAction();
        $controller_path = $action_route->controller;

        if (! canvastack_string_contained($controller_path, 'Controllers@')) {
            $slice_controller = explode('Controllers', $controller_path);
            $slice_controller = explode('Controller', $slice_controller[1]);
            $this->pageInfo = str_replace('@', '', $slice_controller[1]);
        } else {
            $slice_controller = explode('@', $controller_path);
            $this->pageInfo = $slice_controller[1];
        }

        $slice_controller = explode('\\', $slice_controller[0]);
        $this->controllerName = last($slice_controller);
        $this->controllerPathName = explode('@', $controller_path)[0];
    }

    /**
     * Get Current Route Information
     *
     * Edit +/ Show = Back + Add
     * Create = Back
     * Index = Add
     */
    public function routeInfo()
    {

        if (strpos(php_sapi_name(), 'cli') === false) {
            $this->module_privileges();
            $this->get_pageinfo();

            $action_role = [];
            $action_role['show'] = false;
            $action_role['create'] = false;
            $action_role['edit'] = false;
            $action_role['delete'] = false;

            $actionPage = [];
            $actionPage['show'] = [];
            $actionPage['create'] = [];
            $actionPage['edit'] = [];
            $actionPage['delete'] = [];

            $action_page = [];
            $action_page['action_page'] = [];

            if (! empty($this->module_privilege['actions'])) {
                foreach ($this->module_privilege['actions'] as $role_action) {
                    $action_role[$role_action] = true;
                }
            }

            if (count($this->actionButton) >= 1) {
                $buttonLabel = $this->controllerName;
                if (! empty($this->page_name)) {
                    $buttonLabel = $this->page_name;
                }

                if ('index' === $this->pageInfo && true === $action_role['create']) {
                    if (in_array('index', get_class_methods($this->controllerPathName))) {
                        $action_page['action_page'] = ["warning|add {$buttonLabel}" => $this->routeReplaceURL('index', 'create')];
                    }

                } elseif ('create' === $this->pageInfo && true === $action_role['create']) {
                    if (in_array('create', get_class_methods($this->controllerPathName))) {
                        $action_page['action_page'] = ["info|back to {$buttonLabel} lists" => $this->routeReplaceURL('create', 'index')];
                    }

                } elseif ('edit' === $this->pageInfo) {

                    if (true === $action_role['delete']) {
                        if (true === $this->is_softdeleted) {
                            $actionPage['delete'] = ["secondary|restore {$buttonLabel}" => $this->routeReplaceURL('edit', 'destroy')];
                        } else {
                            $actionPage['delete'] = ["danger|delete {$buttonLabel}" => $this->routeReplaceURL('edit', 'destroy')];
                        }
                    }
                    if (true === $action_role['create']) {
                        if (in_array('create', get_class_methods($this->controllerPathName))) {
                            $actionPage['create'] = ["warning|add {$buttonLabel}" => $this->routeReplaceURL('edit', 'create')];
                        }
                    }
                    if (true === $action_role['show']) {
                        if (in_array('edit', get_class_methods($this->controllerPathName))) {
                            $actionPage['edit'] = ["success|view this {$buttonLabel}" => str_replace('/edit', '', url()->current())];
                        }

                        if (in_array('index', get_class_methods($this->controllerPathName))) {
                            $actionPage['show'] = ["info|back to {$buttonLabel} lists" => $this->routeReplaceURL('edit', 'index')];
                        }
                    }

                    $action_page['action_page'] = array_merge_recursive($actionPage['delete'], $actionPage['create'], $actionPage['edit'], $actionPage['show']);

                } elseif ('show' === $this->pageInfo && true === $action_role['show']) {
                    if (in_array('create', get_class_methods($this->controllerPathName))) {
                        $action_page['action_page']["warning|add {$buttonLabel}"] = $this->routeReplaceURL('show', 'create');
                    }
                    if (in_array('edit', get_class_methods($this->controllerPathName))) {
                        $action_page['action_page']["success|edit this {$buttonLabel}"] = url()->current().'/edit';
                    }
                    if (in_array('index', get_class_methods($this->controllerPathName))) {
                        $action_page['action_page']["info|back to {$buttonLabel} lists"] = $this->routeReplaceURL('show', 'index');
                    }
                }
            }

            $routeInfo = [
                'current_path' => $this->currentRoute->getName(),
                'module_name' => $this->controllerName,
                'page_info' => $this->pageInfo,
            ];

            $this->setDataValues('route_info', (object) array_merge($routeInfo, $action_page));
        }
    }

    private function routeReplaceURL($from, $to)
    {
        $routeUri = str_replace($from, $to, $this->currentRoute->getName());

        if ('destroy' !== $to) {
            return route($routeUri);
        } else {
            $routeURI = $routeUri;
            $routeUri = explode('/', canvastack_current_url());
            unset($routeUri[array_key_last($routeUri)]);

            return $routeURI.'::'.(int) last($routeUri);
        }
    }
}
