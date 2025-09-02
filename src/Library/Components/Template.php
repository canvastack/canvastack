<?php

namespace Canvastack\Canvastack\Library\Components;

use Illuminate\Support\Facades\Auth;

/**
 * Created on 10 Mar 2021
 * Time Created	: 11:11:53
 *
 * @filesource	Template.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Template extends Scripts
{
    /**
     * 'assets/templates/default/'
     */
    public $currentTemplate;

    public $menu_sidebar = [];

    public $sidebar_content;

    public function __construct()
    {
        parent::__construct();

        $this->currentTemplate = canvastack_config('template');

        $this->templateScripts('js');
        $this->templateScripts('css');
    }

    private function templatePath($scriptPath)
    {
        return "{$this->assetPath}/{$scriptPath}";
    }

    private function templateScripts($type)
    {
        $type = strtolower($type);
        $scriptConfig = [];
        $scriptConfig[$type]['top'] = canvastack_config("admin.default.position.top.{$type}", 'templates');
        $scriptConfig[$type]['bottom'] = canvastack_config("admin.default.position.bottom.{$type}", 'templates');
        $scriptConfig[$type]['bottom']['first'] = canvastack_config("admin.default.position.bottom.first.{$type}", 'templates');
        $scriptConfig[$type]['bottom']['last'] = canvastack_config("admin.default.position.bottom.last.{$type}", 'templates');

        foreach ($scriptConfig as $scriptPositions) {
            foreach ($scriptPositions as $position => $scriptPath) {
                foreach ($scriptPath as $pos => $scriptCheck) {
                    if (is_array($scriptCheck)) {
                        foreach ($scriptCheck as $scriptURL) {
                            $scriptURL = $this->templatePath($scriptURL);
                            $this->{$type}($scriptURL, "{$position}_{$pos}");
                        }
                    } else {
                        $scriptURL = $this->templatePath($scriptCheck);
                        $this->{$type}($scriptURL, $position);
                    }
                }
            }
        }
    }

    /**
     * Create Breadcrumb
     *
     * @param  string  $title
     * @param  array  $links
     * @param  string  $icon_title
     * @param  string  $icon_links
     */
    public function set_breadcrumb($title, $links = [], $icon_title = false, $icon_links = false)
    {
        $this->breadcrumbs = canvastack_breadcrumb($title, $links, $icon_title, $icon_links);
    }

    public function set_menu_sidebar_open($class = false)
    {
        $this->menu_sidebar[] = canvastack_sidebar_menu_open($class);
    }

    public function set_sidebar_category($title, $icon = [], $icon_position = false)
    {
        $this->menu_sidebar[] = canvastack_sidebar_category($title, $icon, $icon_position);
    }

    public function set_menu_sidebar($label, $links, $icon = [], $selected = false)
    {
        $this->menu_sidebar[] = canvastack_sidebar_menu($label, $links, $icon, $selected);
    }

    public function set_menu_sidebar_close()
    {
        $this->menu_sidebar[] = canvastack_sidebar_menu_close();
    }

    /**
     * Rendering Sidebar Menu.
     *
     * This function will draw menu based on active modules with spesific user role
     *
     * @param  array  $module
     */
    public function render_sidebar_menu($module = [])
    {
        $menu_data = [];
        $menu_lists = [];
        $menu_label = [];
        $icon_lists = [];
        $routes = [];
        $routelists = [];
        $routelabel = [];
        $data_icons = [];
        $routeURL = [];

        foreach ($module as $menu_list) {
            $menu_lists[$menu_list['route_path']] = "{$menu_list['route_path']}.index";
            $menu_label[$menu_list['route_path']] = $menu_list['module_name'];
            if (null !== $menu_list['icon']) {
                $icon_lists[$menu_list['route_path'].'.index'] = "{$menu_list['route_path']}.<i class=\"{$menu_list['icon']}\"></i>";
            }
        }

        $labelData = [];
        foreach ($menu_label as $label_path => $label_menu) {
            $label_paths = explode('.', $label_path);
            foreach ($label_paths as $labelPath) {
                $labelData[$labelPath] = $label_menu;
            }
        }

        foreach ($menu_lists as $i => $list) {
            $route_name = $list;
            $routeObj = explode('.', $route_name);
            $icons = false;
            $iconset = false;
            if (isset($icon_lists[$list])) {
                $icons = explode('.', $icon_lists[$list]);
            }//dump($routeObj, $menu_label[$i]);

            if (count($routeObj) > 1) {
                if (in_array('index', $routeObj)) {
                    $route_cat = count($routeObj);

                    if (5 === $route_cat) {
                        $routelabel[$routeObj[0]][$routeObj[1]][$routeObj[2]][$routeObj[3]] = $menu_label[$i];
                        $routelists[$routeObj[0]][$routeObj[1]][$routeObj[2]][$routeObj[3]] = $routeObj[4];
                        if (isset($icons[1])) {
                            $iconset = $icons[4];
                        }
                        $routeURL[$routeObj[0]][$routeObj[1]][$routeObj[2]][$routeObj[3]] = $iconset;
                    }
                    if (4 === $route_cat) {
                        $routelabel[$routeObj[0]][$routeObj[1]][$routeObj[2]] = $menu_label[$i];
                        $routelists[$routeObj[0]][$routeObj[1]][$routeObj[2]] = $routeObj[3];
                        if (isset($icons[1])) {
                            $iconset = $icons[3];
                        }
                        $routeURL[$routeObj[0]][$routeObj[1]][$routeObj[2]] = $iconset;
                    }
                    if (3 === $route_cat) {
                        $routelabel[$routeObj[0]][$routeObj[1]][$routeObj[2]] = $menu_label[$i];
                        $routelists[$routeObj[0]][$routeObj[1]][$routeObj[2]] = $routeObj[2];
                        if (isset($icons[1])) {
                            $iconset = $icons[2];
                        }
                        $routeURL[$routeObj[0]][$routeObj[1]][$routeObj[2]] = $iconset;
                    }
                    if (2 === $route_cat) {
                        $routelabel[$routeObj[0]][$routeObj[1]] = $menu_label[$i];
                        $routelists[$routeObj[0]][$routeObj[1]] = $routeObj[1];
                        if (isset($icons[1])) {
                            $iconset = $icons[1];
                        }
                        $routeURL[$routeObj[0]][$routeObj[1]] = $iconset;
                    }
                }
            }
        }

        $child_menu = 'child';
        $links = [];
        foreach ($routelists as $parent => $category) {
            foreach ($category as $child => $route_data) {
                if ('index' !== $child) {
                    foreach ($route_data as $model => $index) {
                        if (is_array($index)) {
                            foreach ($index as $thid_key => $third_value) {
                                $routes[$parent][$child][$model][$thid_key][$child_menu] = $thid_key;
                                $links[$parent][$child][$model][$thid_key]['icon'] = $routeURL[$parent][$child][$model][$thid_key];
                            }
                        } else {
                            if ($index !== $model) {
                                $routes[$parent][$child][$model][$child_menu] = $model;
                                $links[$parent][$child][$model]['icon'] = $routeURL[$parent][$child][$model];
                            } else {
                                $routes[$parent][$child][$child_menu] = $model;
                                $links[$parent][$child]['icon'] = $routeURL[$parent][$child][$model];
                            }
                        }

                    }
                } else {
                    $routes[$parent][$child_menu] = $child;
                    $links[$parent]['icon'] = $routeURL[$parent][$child];
                }
            }
        }

        $data_icon = [];
        foreach ($routes as $base_group => $base_model) {
            foreach ($base_model as $model_name => $data_model) {
                $modelNameLabel = $model_name;

                if ($child_menu !== $model_name) {
                    foreach ($data_model as $model => $value) {
                        $labelModel = $model_name;
                        if ('child' !== $model_name && ! empty($routelabel[$base_group][$model_name])) {
                            $labelModel = $routelabel[$base_group][$model_name][$model];
                        }

                        if ($child_menu === $model) {
                            $menu_data[$base_group][$child_menu][$modelNameLabel] = route("{$base_group}.{$model_name}.{$value}");
                            $data_icon[$base_group][$modelNameLabel]['icon'] = $links[$base_group][$model_name]['icon'];
                        } else {
                            //	foreach ($value as $next_model => $next_val) {
                            foreach ($value as $next_val) {
                                if (is_array($next_val)) {
                                    //	foreach ($next_val as $thirdkey => $thirdval) {
                                    foreach ($next_val as $thirdval) {
                                        $menu_data[$base_group][$child_menu][$modelNameLabel][$labelModel][$thirdval] = route("{$base_group}.{$model_name}.{$model}.{$thirdval}.index");
                                        $data_icons[$base_group][$modelNameLabel][$labelModel][$thirdval]['icon'][] = $links[$base_group][$model_name][$model][$thirdval]['icon'];
                                    }
                                } else {
                                    $menu_data[$base_group][$child_menu][$modelNameLabel][$labelModel] = route("{$base_group}.{$model_name}.{$next_val}.index");
                                    $data_icons[$base_group][$modelNameLabel]['icon'][] = $links[$base_group][$model_name][$model]['icon'];
                                }
                            }
                        }
                    }
                } else {
                    $menu_data[$base_group] = route("{$base_group}.index");
                    $data_icon[$base_group]['icon'] = $links[$base_group]['icon'];
                }
            }
        }

        foreach ($data_icons as $parent => $child) {
            foreach ($child as $child_name => $icons) {
                $filter_icon = ['<i class="fa fa-tags"></i>'];
                if (count(array_filter($icons['icon'])) >= 1) {
                    $filter_icon = array_filter($icons['icon']);
                }

                $data_icon[$parent][$child_name]['icon'] = end($filter_icon);
            }
        }

        $this->set_menu_sidebar_open();
        foreach ($menu_data as $parent => $menu) {
            if (is_array($menu)) {
                //	foreach ($menu as $sub_menu => $data) {
                foreach ($menu as $data) {
                    $this->set_sidebar_category($parent, 'bookmark');
                    foreach ($data as $key => $val) {
                        $icon = $data_icon[$parent][$key];
                        $this->set_menu_sidebar(ucwords($key), $val, $icon);
                    }
                }
            } else {
                $icon = $data_icon[$parent];
                $this->set_menu_sidebar(ucwords($parent), $menu, $icon);
            }
        }
        $this->set_menu_sidebar_close();
    }

    public function set_sidebar_content($media_title, $media_heading = false, $media_sub_heading = false)
    {
        $this->sidebar_content = canvastack_sidebar_content($media_title, $media_heading, $media_sub_heading);
    }

    public function render_sidebar_content()
    {
        $table_group = 'base_group';
        $table_user_group = 'base_user_group';

        if (null !== Auth::user()) {
            $user = Auth::user();
            $user_group = canvastack_query($table_user_group)
                ->leftJoin($table_group, "{$table_user_group}.group_id", '=', "{$table_group}.id")
                ->where('user_id', $user->id)
                ->value("{$table_group}.group_info");
            $group_name = $user_group;

            $this->set_sidebar_content(canvastack_set_avatar($user->username, false, $user->photo_thumb), "Hi, <span>{$user->name}</span>", $group_name);
        }
    }
}
