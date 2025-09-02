<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Html;

/**
 * Html\TemplateUi — centralized Template-related UI builders.
 *
 * H3 scope (extended): breadcrumb, grid, sidebar, and simple asset tag builders.
 */
final class TemplateUi
{
    /**
     * Create Breadcrumb HTML identical to legacy helper canvastack_breadcrumb.
     */
    public static function breadcrumb($title, $links = [], $icon_title = false, $icon_links = false, $type = false): string
    {
        if ($type === 'blankon') {
            $n = 0;
            $linkIcons = false;
            if ($icon_links !== false) {
                foreach ($icon_links as $link_icon) {
                    $linkIcons[] = '<i class="fa fa-' . $link_icon . '"></i> ';
                }
            }

            $o = '<div class="header-content">';
            $o .= '<h4 style="margin:3px 6px !important">';
            if ($icon_title !== false) {
                $o .= '<i class="fa fa-' . $icon_title . '"></i> ';
            }
            $o .= $title;
            $o .= '</h4>';
            $o .= '<div class="breadcrumb-wrapper hidden-xs">';
            if ($links) {
                $o .= '<ol class="breadcrumb">';
                foreach ($links as $link_title => $link_url) {
                    $n++;

                    $index = $n - 1;
                    $linkTitle = function_exists('canvastack_underscore_to_camelcase')
                        ? \canvastack_underscore_to_camelcase($link_title)
                        : ucwords(str_replace('_', ' ', (string) $link_title));

                    $o .= '<li>';
                    if ($linkIcons && isset($linkIcons[$index])) {
                        $o .= $linkIcons[$index];
                    }
                    if ($link_title !== 0) {
                        $o .= '<a href="' . $link_url . '">' . $linkTitle . '</a>';
                    } else {
                        $linkTitle = ucwords($link_url);
                        $o .= '<a>' . $linkTitle . '</a>';
                    }
                    $o .= '<i class="fa fa-angle-right"></i>';
                    $o .= '</li>';
                }
                $o .= '</ol>';
            }
            $o .= '</div>';
            $o .= '</div>';
        } else {
            $o = '<div class="page-title-area shadow blury blury-blue">';
            $o .= '<div class="row align-items-center">';
            $o .= '<div class="col-sm-12">';
            $o .= '<div class="breadcrumbs-area clearfix">';
            $o .= '<h4 class="page-title pull-left">' . $title . '</h4>';

            $n = 0;
            $linkIcons = false;
            if ($icon_links !== false) {
                foreach ($icon_links as $link_icon) {
                    $linkIcons[] = '<i class="fa fa-' . $link_icon . '"></i> ';
                }
            }

            if ($links) {
                $o .= '<ul class="breadcrumbs pull-right">';
                foreach ($links as $link_title => $link_url) {
                    $n++;

                    $index = $n - 1;
                    $linkTitle = function_exists('canvastack_underscore_to_camelcase')
                        ? \canvastack_underscore_to_camelcase($link_title)
                        : ucwords(str_replace('_', ' ', (string) $link_title));

                    $o .= '<li>';
                    if ($link_title !== 0) {
                        $o .= '<a href="' . $link_url . '">' . $linkTitle . '</a>';
                    } else {
                        $linkTitle = ucwords($link_url);
                        $o .= '<span>' . $linkTitle . '</span>';
                    }
                    $o .= '</li>';
                }
                $o .= '</ul>';
            }

            $o .= '</div></div></div></div>';
        }

        return $o;
    }

    /**
     * Grid container builder identical to legacy canvastack_gird
     */
    public static function grid($name = 'start', $set_column = false, $addHTML = false, $single = false): string
    {
        $numberColumn = 12;
        if (!empty($set_column)) {
            $numberColumn = intval(12 - $set_column);
        }
        $col = ' col-' . $numberColumn;

        if ($name === 'end') {
            $single = false;
            $addHTML = false;
            return '</div></div></div>';
        }

        if (!empty($addHTML)) {
            $single = true;
        }

        if ($single === true) {
            return '<div class="' . $name . '">' . $addHTML . '</div>';
        }

        if ($name === 'start' || $name === 'container') {
            return '<div class="container"><div class="row"><div class="col' . $col . '">';
        }
        if ($name === 'container-fluid') {
            return '<div class="container-fluid"><div class="row"><div class="col' . $col . '">';
        }
        return '<div class="row"><div class="col' . $col . '"><div class="' . $name . '">';
    }

    /**
     * Column wrapper identical to legacy canvastack_set_gird_column
     */
    public static function gridColumn($html, $set_column = false): string
    {
        $numberColumn = 12;
        if (!empty($set_column)) {
            $numberColumn = intval(12 / $set_column);
        }
        $col = ' col-' . $numberColumn;
        return '<div class="col' . $col . '">' . $html . '</div>';
    }

    /**
     * Sidebar content identical to legacy canvastack_sidebar_content
     */
    public static function sidebarContent($media_title, $media_heading = false, $media_sub_heading = false, $type = true): string
    {
        $base_url = function_exists('canvastack_config') ? \canvastack_config('baseURL') : '';
        if ($type === false) {
            $mediaHeading = false;
            $mediaSubHeading = false;
            if ($media_heading !== false) {
                $mediaHeading = '<h4 class="media-heading">' . $media_heading . '</h4>';
            }
            if ($media_sub_heading !== false) {
                $mediaSubHeading = '<small>' . $media_sub_heading . '</small>';
            }
            $o = '<div class="sidebar-content">';
            $o .= '<div class="media">';
            $o .= $media_title;
            $o .= '<div class="media-body">';
            $o .= $mediaHeading ?: '';
            $o .= $mediaSubHeading ?: '';
            $o .= '</div>';
            $o .= '</div>';
            $o .= '</div>';
            return $o;
        }

        $sessions = function_exists('canvastack_sessions') ? \canvastack_sessions() : [];
        $userId = $sessions['id'] ?? '';
        $o = '<div class="relative">';
        $o .= '<a data-toggle="collapse" href="#userInfoBox" role="button" aria-expanded="false" aria-controls="userInfoBox" class="btn-sets btn-sets-sm absolute sets-right-bottom sets-top btn-primary shadow1 collapsed"><i class="ti-settings"></i></a>';
        $o .= '<div class="user-panel light">';
        $o .= $media_title;
        $o .= '<div class="multi-collapse collapse" id="userInfoBox">';
        $o .= '<div class="list-group mt-3 shadow">';
        $o .= '<a href="' . $base_url . '/system/accounts/user/' . $userId . '" class="list-group-item list-group-item-action ">';
        $o .= '<i class="mr-2 ti-user text-blue"></i>Profile';
        $o .= '</a>';
        $o .= '<a href="' . $base_url . '/system/accounts/user/' . $userId . '/edit" class="list-group-item list-group-item-action"><i class="mr-2 ti-settings text-yellow"></i>Edit</a>';
        $o .= '<a href="' . $base_url . '/logout" class="list-group-item list-group-item-action"><i class="mr-2 ti-panel text-purple"></i>Log Out</a>';
        $o .= '</div>';
        $o .= '</div>';
        $o .= '</div>';
        $o .= '</div>';
        return $o;
    }

    /**
     * Sidebar open/close and menu identical to legacy
     */
    public static function sidebarMenuOpen($class_name = false): string
    {
        $class = 'main-menu';
        if ($class_name !== false) {
            $class = $class_name;
        }
        return '<ul id="menu" class="' . $class . '">';
    }

    public static function sidebarMenuClose(): string
    {
        return '</ul>';
    }

    public static function sidebarMenu($label, $links, $icon = [], $selected = false): string
    {
        $clean = function_exists('canvastack_clean_strings') ? \canvastack_clean_strings($label) : preg_replace('/[^a-z0-9_\-]+/i', '-', (string) $label);
        $o = '<li id="' . $clean . '" class="submenu">';

        $icons = [];
        $icons['before'] = $icon;
        $icons['after'] = '';
        $icons['after_label'] = false;

        if (is_array($links)) {
            $o .= '<a class="arrow-node" href="javascript:void(0);">';
            if ($icon !== false) {
                $iconHtml = $icon['icon'] ?? null;
                if ($iconHtml !== null) {
                    $o .= '<span class="icon">' . $iconHtml . '</span>';
                }
            }
            $text = function_exists('canvastack_underscore_to_camelcase') ? \canvastack_underscore_to_camelcase($label) : ucwords(str_replace('_', ' ', (string) $label));
            $o .= '<span class="text">' . $text . '</span>';
            $o .= '<span' . $icons['after'] . '">' . $icons['after_label'] . '</span>';
            if ($selected === true) {
                $o .= '<span class="selected"></span>';
            }
            $o .= '</a>';

            $o .= '<ul>';
            foreach ($links as $child_title => $child_url) {
                if (is_array($child_url)) {
                    $o .= '<li class="submenu"><a href="javascript:void(0);">';
                    $text = function_exists('canvastack_underscore_to_camelcase') ? \canvastack_underscore_to_camelcase($child_title) : ucwords(str_replace('_', ' ', (string) $child_title));
                    $o .= '<span class="text">' . $text . '</span>';
                    $o .= '<span class="arrow open fa-angle-double-down"></span></a>';
                    $o .= '<ul>';
                    foreach ($child_url as $thirdChild => $thirdURL) {
                        $text3 = function_exists('canvastack_underscore_to_camelcase') ? \canvastack_underscore_to_camelcase($thirdChild) : ucwords(str_replace('_', ' ', (string) $thirdChild));
                        $o .= '<li id="' . $clean . '-' . $child_title . '-' . $text3 . '"><a class="menu-url" href="' . $thirdURL . '">' . $text3 . '</a></li>';
                    }
                    $o .= '</ul>';
                    $o .= '</li>';
                } else {
                    $text = function_exists('canvastack_underscore_to_camelcase') ? \canvastack_underscore_to_camelcase($child_title) : ucwords(str_replace('_', ' ', (string) $child_title));
                    $o .= '<li class="menu-active-pointer"><a class="menu-url" href="' . $child_url . '">' . $text . '</a></li>';
                }
            }
            $o .= '</ul>';
        } else {
            $o .= '<a href="' . $links . '">';
            if ($icon !== false) {
                if (isset($icon['icon']) && $icon['icon'] !== null) {
                    $o .= '<span class="icon">' . $icon['icon'] . '</span>';
                } else {
                    $o .= '<span class="icon"><i class="fa fa-tags"></i></span>';
                }
            }
            $text = function_exists('canvastack_underscore_to_camelcase') ? \canvastack_underscore_to_camelcase($label) : ucwords(str_replace('_', ' ', (string) $label));
            $o .= '<span class="text">' . $text . '</span>';
            if ($selected === true) {
                $o .= '<span class="selected"></span>';
            }
            $o .= '</a>';
        }

        $o .= '</li>';
        return $o;
    }

    /**
     * Sidebar category identical to legacy canvastack_sidebar_category
     */
    public static function sidebarCategory($title, $icon = false, $icon_position = false): string
    {
        $o = '<li class="sidebar-category">';
        $o .= '<span>' . $title . '</span>';
        if ($icon !== false) {
            $position = 'right';
            if ($icon_position !== false) {
                $position = $icon_position;
            }
            $o .= '<span class="pull-' . $position . '"><i class="fa fa-' . $icon . '"></i></span>';
        }
        $o .= '</li>';
        return $o;
    }

    /**
     * Simple JS/CSS tag builder matching legacy helper usage
     */
    public static function js($scripts, $position = 'bottom', $as_script_code = false)
    {
        // Preserve legacy behavior: if $as_script_code === true, wrap raw code; otherwise treat as src list/string
        $out = '';
        $emit = function ($src) use (&$out) {
            $out .= '<script src="' . $src . '"></script>';
        };
        if ($as_script_code === true) {
            // raw inline script
            $out .= '<script>' . (string) $scripts . '</script>';
        } else {
            if (is_array($scripts)) {
                foreach ($scripts as $src) {
                    $emit($src);
                }
            } elseif ($scripts) {
                $emit((string) $scripts);
            }
        }
        return $out;
    }

    public static function css($scripts, $position = 'top', $as_script_code = false)
    {
        // Preserve legacy: css() also used js() in old helper; keep same API but output <link> for files, inline <style> if as_script_code
        $out = '';
        $emit = function ($href) use (&$out) {
            $out .= '<link rel="stylesheet" href="' . $href . '">';
        };
        if ($as_script_code === true) {
            $out .= '<style>' . (string) $scripts . '</style>';
        } else {
            if (is_array($scripts)) {
                foreach ($scripts as $href) {
                    $emit($href);
                }
            } elseif ($scripts) {
                $emit((string) $scripts);
            }
        }
        return $out;
    }

    /**
     * Avatar block identical to legacy canvastack_set_avatar
     */
    public static function avatar($username, $link_url = false, $image_src = false, $user_status = 'online', $type_old = false): string
    {
        $src = ($image_src === false || $image_src === null)
            ? (function_exists('asset') ? \asset('assets/templates/default/images/user-m.png') : 'assets/templates/default/images/user-m.png')
            : $image_src;

        if ($type_old === true) {
            $style = 'style="width:50px;height:50px;display:block;text-align:center;vertical-align:middle;"';
            $linkURL = '';
            if ($link_url !== false) {
                $linkURL = ' href="' . $link_url . '"';
            }
            $o = '<a class="pull-left has-notif avatar"' . $linkURL . '>';
            $o .= '<img src="' . $src . '" alt="' . $username . '" title="' . $username . '" ' . $style . '/>';
            if ($user_status !== false) {
                $o .= '<i class="' . $user_status . '"></i>';
            }
            $o .= '</a>';
            return $o;
        }

        $o = '<div>';
        $o .= '<div class="float-left image">';
        $o .= '<img class="user-avatar" src="' . $src . '" alt="' . $username . '" title="' . $username . '" />';
        $o .= '</div>';
        $o .= '<div class="float-left info">';
        $o .= '<h6 class="font-weight-light mt-2 mb-1">' . $username . '</h6>';
        $o .= '<a href="#"><i class="fa fa-circle text-primary blink"></i> ' . $user_status . '</a>';
        $o .= '</div>';
        $o .= '</div>';
        $o .= '<div class="clearfix"></div>';
        return $o;
    }
}