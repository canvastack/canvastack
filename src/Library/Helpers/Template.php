<?php
/**
 * Created on 15 Mar 2021
 * Time Created	: 00:44:02
 *
 * @filesource	Template.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */

if (!function_exists('canvas_template_config')) {
	
	/**
	 * Get Template Config Data
	 *
	 * created @Sep 28, 2018
	 * author: wisnuwidi
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function canvas_template_config($string) {
		return canvas_config("{$string}", 'templates');
	}
}

if (!function_exists('canvas_current_template')) {
	
	/**
	 * Get Current Used Template
	 *
	 * created @Sep 28, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function canvas_current_template() {
		return canvas_config('template');
	}
}

if (!function_exists('canvas_js')) {
	
	function canvas_js($scripts, $position = 'bottom', $as_script_code = false) {
		$template = new Canvastack\Origin\Library\Components\Template();
		
		return $template->js($scripts, $position, $as_script_code);
	}
}

if (!function_exists('canvas_css')) {
	
	function canvas_css($scripts, $position = 'top', $as_script_code = false) {
		$template = new Canvastack\Origin\Library\Components\Template();
		
		return $template->js($scripts, $position, $as_script_code);
	}
}

if (!function_exists('canvas_gird')) {
	
	/**
	 * Draw HTML Gird Container
	 *
	 * created @Mar 16, 2021
	 * author: wisnuwidi
	 *
	 * @param string $name
	 * 		: [start|container|container-fluid|end|bootstrap classname element]
	 * @param bool|string|mixed $addHTML
	 * @param bool $single
	 */
	function canvas_gird($name = 'start', $set_column = false, $addHTML = false, $single = false) {
		$numberColumn = 12;
		if (!empty($set_column)) {
			$numberColumn = intval(12 - $set_column);
		}
		
		$col = " col-{$numberColumn}";
		
		if ('end' === $name) {
			$single  = false;
			$addHTML = false;
			
			return '</div></div></div>';
		} else {
			if (!empty($addHTML)) {
				$single = true;
			}
			
			if (true === $single) {
				return "<div class=\"{$name}\">{$addHTML}</div>";
			} else {
				if ('start' === $name || 'container' === $name) {
					return '<div class="container"><div class="row"><div class="col' . $col . '">';
				} elseif ('container-fluid' === $name) {
					return '<div class="container-fluid"><div class="row"><div class="col' . $col . '">';
				} else {
					return "<div class=\"row\"><div class=\"col' . $col . '\"><div class=\"{$name}\">";
				}
			}
		}
	}
}

if (!function_exists('canvas_set_gird_column')) {
	
	/**
	 * Draw HTML With Gird Column Setting
	 *
	 * created @Mar 16, 2021
	 * author: wisnuwidi
	 *
	 * @param string $html
	 * @param boolean $set_column
	 *
	 * @return string
	 */
	function canvas_set_gird_column($html, $set_column = false) {
		$numberColumn = 12;
		if (!empty($set_column)) {
			$numberColumn = intval(12 / $set_column);
		}
		$col = " col-{$numberColumn}";
		
		return "<div class=\"col{$col}\">{$html}</div>";
	}
}

if (!function_exists('canvas_breadcrumb')) {
    
    /**
     * Create Breadcrumb Tag
     *
     * @param string $title
     * @param array $links
     * @param string $icon_title
     * @param string $icon_links
     *
     * @return string
     */
    function canvas_breadcrumb($title, $links = [], $icon_title = false, $icon_links = false, $type = false) {
        if ('blankon' === $type) {
            $n = 0;
            $linkIcons = false;
            if (false !== $icon_links) {
                foreach ($icon_links as $link_icon) {
                    $linkIcons[] = "<i class=\"fa fa-{$link_icon}\"></i> ";
                }
            }
            
            $o  = "<div class=\"header-content\">";
            $o .= "<h4 style=\"margin:3px 6px !important\">";
            if (false !== $icon_title) {
                $o .= "<i class=\"fa fa-{$icon_title}\"></i> ";
            }
            $o .= $title;
            $o .= "</h4>";
            $o .= "<div class=\"breadcrumb-wrapper hidden-xs\">";
            if ($links) {
                $o .= "<ol class=\"breadcrumb\">";
                foreach ($links as $link_title => $link_url) {
                    $n++;
                    
                    $index		= $n - 1;
                    $linkTitle	= canvas_underscore_to_camelcase($link_title);
                    
                    $o .= "<li>";
                    if ($linkIcons[$index]) $o .= $linkIcons[$index];
                    if (0 !== $link_title) {
                        $o .= "<a href=\"{$link_url}\">{$linkTitle}</a>";
                    } else {
                        $linkTitle	= ucwords($link_url);
                        $o .= "<a>{$linkTitle}</a>";
                    }
                    $o .= "<i class=\"fa fa-angle-right\"></i>";
                    $o .= "</li>";
                }
                $o .= "</ol>";
            }
            $o .= "</div>";
            $o .= "</div>";
        } else {
            
            $o  = "<div class=\"page-title-area shadow blury blury-blue\">";
            $o .= "<div class=\"row align-items-center\">";
            $o .= "<div class=\"col-sm-12\">";
            $o .= "<div class=\"breadcrumbs-area clearfix\">";
            $o .= "<h4 class=\"page-title pull-left\">{$title}</h4>";
            
            $n = 0;
            $linkIcons = false;
            if (false !== $icon_links) {
                foreach ($icon_links as $link_icon) {
                    $linkIcons[] = "<i class=\"fa fa-{$link_icon}\"></i> ";
                }
            }
            
            if ($links) {
                $o .= "<ul class=\"breadcrumbs pull-right\">";
                foreach ($links as $link_title => $link_url) {
                    $n++;
                    
                    $index     = $n - 1;
                    $linkTitle = canvas_underscore_to_camelcase($link_title);
                    
                    $o .= "<li>";
                    if (0 !== $link_title) {
                        $o .= "<a href=\"{$link_url}\">{$linkTitle}</a>";
                    } else {
                        $linkTitle	= ucwords($link_url);
                        $o .= "<span>{$linkTitle}</span>";
                    }
                    $o .= "</li>";
                }
                $o .= "</ul>";
            }
            
            $o .= "</div></div></div></div>";
        }
        
        return $o;
    }
}

if (!function_exists('canvas_sidebar_content')) {
	
	/**
	 * Create Sidebar Content
	 *
	 * @param string $media_title
	 * @param string $media_heading
	 * @param string $media_sub_heading
	 */
	function canvas_sidebar_content($media_title, $media_heading = false, $media_sub_heading = false, $type = true) {
		$base_url = canvas_config('baseURL');
		if (false === $type) {
			$mediaHeading		= false;
			$mediaSubHeading	= false;
			
			if (false !== $media_heading)     $mediaHeading    = "<h4 class=\"media-heading\">{$media_heading}</h4>";
			if (false !== $media_sub_heading) $mediaSubHeading = "<small>{$media_sub_heading}</small>";
			
			$o  = "<div class=\"sidebar-content\">";
			$o .= "<div class=\"media\">";
			$o .= "{$media_title}";
			$o .= "<div class=\"media-body\">";
			$o .= "{$mediaHeading}";
			$o .= "{$mediaSubHeading}";
			$o .= "</div>";
			$o .= "</div>";
			$o .= "</div>";
		} else {
			$sessions = canvas_sessions();
			$o  = "<div class=\"relative\">";
			$o .= "<a data-toggle=\"collapse\" href=\"#userInfoBox\" role=\"button\" aria-expanded=\"false\" aria-controls=\"userInfoBox\" class=\"btn-sets btn-sets-sm absolute sets-right-bottom sets-top btn-primary shadow1 collapsed\"><i class=\"ti-settings\"></i></a>";
			$o .= "<div class=\"user-panel light\">";
			$o .= "{$media_title}";
			$o .= "<div class=\"multi-collapse collapse\" id=\"userInfoBox\">";
			$o .= "<div class=\"list-group mt-3 shadow\">";
			$o .= "<a href=\"{$base_url}/system/accounts/user/{$sessions['id']}\" class=\"list-group-item list-group-item-action \">";
			$o .= "<i class=\"mr-2 ti-user text-blue\"></i>Profile";
			$o .= "</a>";
			$o .= "<a href=\"{$base_url}/system/accounts/user/{$sessions['id']}/edit\" class=\"list-group-item list-group-item-action\"><i class=\"mr-2 ti-settings text-yellow\"></i>Edit</a>";
			$o .= "<a href=\"{$base_url}/logout\" class=\"list-group-item list-group-item-action\"><i class=\"mr-2 ti-panel text-purple\"></i>Log Out</a>";
			$o .= "</div>";
			$o .= "</div>";
			$o .= "</div>";
			$o .= "</div>";
		}
		
		return $o;
	}
}

if (!function_exists('canvas_sidebar_menu_open')) {
	
	/**
	 * Sidebar Open
	 *
	 * created @May 8, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean $class_name
	 * @return string
	 */
	function canvas_sidebar_menu_open($class_name = false) {
		$class = 'main-menu';//'sidebar-menu'
		if (false !== $class_name) $class = $class_name;
		
		return '<ul id="menu" class="' . $class . '">';
	}
}

if (!function_exists('canvas_sidebar_menu')) {
	
	/**
	 * Create Sidebar Menu
	 *
	 * @param string $label
	 * @param string $links
	 * @param string $icon
	 *
	 * @example:
	 *	$this->theme->set_menu_sidebar('Dashboard', [
	    'Basic'      => 'dashboard.html',
	    'E-Commerce' => 'dashboard-ecommerce.html'
	   ], 'home');
	 */
	function canvas_sidebar_menu($label, $links, $icon = [], $selected = false) {
		$o = '<li id="' . canvas_clean_strings($label) . '" class="submenu">';
		
		$icons					= [];
		$icons['before']		= $icon;
		$icons['after']			= '';//'class="arrow fa-angle-double-right"';
		$icons['after_label']	= false;
		
		if (true === is_array($links)) {
			$o .= '<a class="arrow-node" href="javascript:void(0);">';
			
			if (false !== $icon) {
				$o .= '<span class="icon">' . $icon['icon'] . '</span>';
			}
			
			$o .= '<span class="text">' . canvas_underscore_to_camelcase($label) . '</span>';
			$o .= '<span' . $icons['after'] . '">' . $icons['after_label'] . '</span>';
			if (true === $selected) {
				$o .= '<span class="selected"></span>';
			}
			$o .= '</a>';
			
			$o .= '<ul>';
			foreach ($links as $child_title => $child_url) {
				if (is_array($child_url)) {
					$o .= '<li class="submenu"><a href="javascript:void(0);">';
					$o .= '<span class="text">' . canvas_underscore_to_camelcase($child_title) . '</span>';
					$o .= '<span class="arrow open fa-angle-double-down"></span></a>';
					$o .= '<ul>';
					foreach ($child_url as $thirdChild => $thirdURL) {
						$o .= '<li id="' . clean_strings($label) . '-' . $child_title . '-' . canvas_underscore_to_camelcase($thirdChild) . '"><a class="menu-url" href="' . $thirdURL . '">' . canvas_underscore_to_camelcase($thirdChild) . '</a></li>';
					}
					$o .= '</ul>';
					$o .= '</li>';
				} else {
					$o .= '<li class="menu-active-pointer"><a class="menu-url" href="' . $child_url . '">' . canvas_underscore_to_camelcase($child_title) . '</a></li>';
				}
			}
			$o .= '</ul>';
		} else {
			$o .= '<a href="' . $links . '">';
			if (false !== $icon) {
				if (isset($icon['icon']) && null !== $icon['icon']) {
					$o .= '<span class="icon">' . $icon['icon'] . '</span>';
				} else {
					$o .= '<span class="icon"><i class="fa fa-tags"></i></span>';
				}
			}
			$o .= '<span class="text">' . canvas_underscore_to_camelcase($label) . '</span>';
			if (true === $selected) {
				$o .= '<span class="selected"></span>';
			}
			$o .= '</a>';
		}
		
		$o .= '</li>';
		
		return $o;
	}
}

if (!function_exists('canvas_sidebar_category')) {
	
	/**
	 * Create Sidebar Title
	 *
	 * @param string $title
	 * @param string $icon
	 * @param string $icon_position
	 *
	 * @return string
	 */
	function canvas_sidebar_category($title, $icon = false, $icon_position = false) {
		$o  = '<li class="sidebar-category">';
		$o .= '<span>' . $title . '</span>';
		if (false !== $icon) {
			$position = 'right';
			if (false !== $icon_position) {
				$position = $icon_position;
			}
			$o .= '<span class="pull-' . $position . '"><i class="fa fa-' . $icon . '"></i></span>';
		}
		$o .= '</li>';
		
		return $o;
	}
}

if (!function_exists('canvas_sidebar_menu_close')) {
	
	/**
	 * Sidebar Close Menu
	 *
	 * created @May 8, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function canvas_sidebar_menu_close() {
		return '</ul>';
	}
}

if (!function_exists('canvas_set_avatar')) {
	
	/**
	 * Create User Image Link
	 *
	 * @param string $username
	 * @param string $link_url
	 * @param string $image_src
	 * @param string $user_status : online[default]/offline
	 */
	function canvas_set_avatar($username, $link_url = false, $image_src = false, $user_status = 'online', $type_old = false) {
		if (false === $image_src || null === $image_src) {
			$src = asset('assets/templates/default/images/user-m.png');
		} else {
			$src = $image_src;
		}
		
		if (true === $type_old) {
			$style   = 'style="width:50px;height:50px;display:block;text-align:center;vertical-align:middle;"';
			$linkURL = false;
			if (false !== $link_url) {
				$linkURL = " href=\"{$link_url}\"";
			}
			$o  = "<a class=\"pull-left has-notif avatar\"{$linkURL}>";
			$o .= "<img src=\"{$src}\" alt=\"{$username}\" title=\"{$username}\" {$style}/>";
			if (false !== $user_status) {
				$o .= "<i class=\"{$user_status}\"></i>";
			}
			$o .= "</a>";
		} else {
			$o  = "<div>";
			$o .= "<div class=\"float-left image\">";
			$o .= "<img class=\"user-avatar\" src=\"{$src}\" alt=\"{$username}\" title=\"{$username}\" />";
			$o .= "</div>";
			$o .= "<div class=\"float-left info\">";
			$o .= "<h6 class=\"font-weight-light mt-2 mb-1\">{$username}</h6>";
			$o .= "<a href=\"#\"><i class=\"fa fa-circle text-primary blink\"></i> {$user_status}</a>";
			$o .= "</div>";
			$o .= "</div>";
			$o .= "<div class=\"clearfix\"></div>";
		}
		return $o;
	}
}