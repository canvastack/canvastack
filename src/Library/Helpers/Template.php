<?php
/**
 * Created on 15 Mar 2021
 * Time Created	: 00:44:02
 *
 * @filesource	Template.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
if (! function_exists('canvastack_template_config')) {

    /**
     * Get Template Config Data
     *
     * created @Sep 28, 2018
     * author: wisnuwidi
     *
     * @param  string  $string
     * @return string
     */
    function canvastack_template_config($string)
    {
        return canvastack_config("{$string}", 'templates');
    }
}

if (! function_exists('canvastack_current_template')) {

    /**
     * Get Current Used Template
     *
     * created @Sep 28, 2018
     * author: wisnuwidi
     *
     * @return string
     */
    function canvastack_current_template()
    {
        return canvastack_config('template');
    }
}

if (! function_exists('canvastack_js')) {

    function canvastack_js($scripts, $position = 'bottom', $as_script_code = false)
    {
        // Delegate to Utility Html TemplateUi
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::js($scripts, $position, $as_script_code);
    }
}

if (! function_exists('canvastack_css')) {

    function canvastack_css($scripts, $position = 'top', $as_script_code = false)
    {
        // Delegate to Utility Html TemplateUi (legacy kept: same API)
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::css($scripts, $position, $as_script_code);
    }
}

if (! function_exists('canvastack_gird')) {

    /**
     * Draw HTML Gird Container
     *
     * created @Mar 16, 2021
     * author: wisnuwidi
     *
     * @param  string  $name
     * 		: [start|container|container-fluid|end|bootstrap classname element]
     * @param  bool|string|mixed  $addHTML
     * @param  bool  $single
     */
    function canvastack_gird($name = 'start', $set_column = false, $addHTML = false, $single = false)
    {
        // Delegate to Utility Html TemplateUi
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::grid($name, $set_column, $addHTML, $single);
    }
}

if (! function_exists('canvastack_set_gird_column')) {

    /**
     * Draw HTML With Gird Column Setting
     *
     * created @Mar 16, 2021
     * author: wisnuwidi
     *
     * @param  string  $html
     * @param  bool  $set_column
     * @return string
     */
    function canvastack_set_gird_column($html, $set_column = false)
    {
        // Delegate to Utility Html TemplateUi
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::gridColumn($html, $set_column);
    }
}

if (! function_exists('canvastack_breadcrumb')) {

    /**
     * Create Breadcrumb Tag (delegated to Utility TemplateUi; behavior-preserving)
     *
     * @param  string  $title
     * @param  array   $links
     * @param  string  $icon_title
     * @param  array   $icon_links
     * @param  string  $type
     * @return string
     */
    function canvastack_breadcrumb($title, $links = [], $icon_title = false, $icon_links = false, $type = false)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::templateBreadcrumb($title, (array) $links, $icon_title, $icon_links, $type);
    }
}

if (! function_exists('canvastack_sidebar_content')) {

    /**
     * Create Sidebar Content
     *
     * @param  string  $media_title
     * @param  string  $media_heading
     * @param  string  $media_sub_heading
     */
    function canvastack_sidebar_content($media_title, $media_heading = false, $media_sub_heading = false, $type = true)
    {
        // Delegate to Utility Html TemplateUi
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::sidebarContent($media_title, $media_heading, $media_sub_heading, $type);
    }
}

if (! function_exists('canvastack_sidebar_menu_open')) {

    /**
     * Sidebar Open
     *
     * created @May 8, 2018
     * author: wisnuwidi
     *
     * @param  bool  $class_name
     * @return string
     */
    function canvastack_sidebar_menu_open($class_name = false)
    {
        // Delegate to Utility Html TemplateUi
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::sidebarMenuOpen($class_name);
    }
}

if (! function_exists('canvastack_sidebar_menu')) {

    /**
     * Create Sidebar Menu
     *
     * @param  string  $label
     * @param  string  $links
     * @param  string  $icon
     *
     * @example:
     *	$this->theme->set_menu_sidebar('Dashboard', [
        'Basic'      => 'dashboard.html',
        'E-Commerce' => 'dashboard-ecommerce.html'
       ], 'home');
     */
    function canvastack_sidebar_menu($label, $links, $icon = [], $selected = false)
    {
        // Delegate to Utility Html TemplateUi
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::sidebarMenu($label, $links, $icon, $selected);
    }
}

if (! function_exists('canvastack_sidebar_category')) {

    /**
     * Create Sidebar Title
     *
     * @param  string  $title
     * @param  string  $icon
     * @param  string  $icon_position
     * @return string
     */
    function canvastack_sidebar_category($title, $icon = false, $icon_position = false)
    {
        // Delegate to Utility Html TemplateUi
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::sidebarCategory($title, $icon, $icon_position);
    }
}

if (! function_exists('canvastack_sidebar_menu_close')) {

    /**
     * Sidebar Close Menu
     *
     * created @May 8, 2018
     * author: wisnuwidi
     *
     * @return string
     */
    function canvastack_sidebar_menu_close()
    {
        // Delegate to Utility Html TemplateUi
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::sidebarMenuClose();
    }
}

if (! function_exists('canvastack_set_avatar')) {

    /**
     * Create User Image Link
     *
     * @param  string  $username
     * @param  string  $link_url
     * @param  string  $image_src
     * @param  string  $user_status : online[default]/offline
     */
    function canvastack_set_avatar($username, $link_url = false, $image_src = false, $user_status = 'online', $type_old = false)
    {
        // Delegate to Utility Html TemplateUi
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::avatar($username, $link_url, $image_src, $user_status, $type_old);
    }
}
