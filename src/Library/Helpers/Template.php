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

if (!function_exists('canvastack_template_config')) {
	
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
	function canvastack_template_config($string) {
		return canvastack_config("{$string}", 'templates');
	}
}

if (!function_exists('canvastack_current_template')) {
	
	/**
	 * Get Current Used Template
	 *
	 * created @Sep 28, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function canvastack_current_template() {
		return canvastack_config('template');
	}
}

if (!function_exists('canvastack_detect_templates')) {

	/**
	 * Auto-detect available templates from resources/views directory.
	 *
	 * A folder is considered a valid template if it contains the marker file:
	 *   template/admin/index.blade.php
	 *
	 * Excluded folders: errors, vendor (and any non-directory entry).
	 *
	 * Returns an array suitable for canvastack selectbox:
	 *   ['' => '', 'default' => 'Default', 'canvasign' => 'Canvasign']
	 *
	 * @return array<string, string>
	 */
	function canvastack_detect_templates(): array {
		$viewsPath = base_path('resources/views');
		$excluded  = ['errors', 'vendor'];
		$marker    = 'template/admin/index.blade.php';

		$options = ['' => ''];

		if (!is_dir($viewsPath)) {
			return $options;
		}

		$folders = array_filter(
			scandir($viewsPath),
			fn($entry) => $entry !== '.' &&
			              $entry !== '..' &&
			              !in_array($entry, $excluded, true) &&
			              is_dir($viewsPath . DIRECTORY_SEPARATOR . $entry)
		);

		foreach ($folders as $folder) {
			$markerFile = $viewsPath . DIRECTORY_SEPARATOR . $folder
			            . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $marker);

			if (file_exists($markerFile)) {
				// Convert folder name to display label: "canvasign" → "Canvasign"
				$options[$folder] = ucfirst($folder);
			}
		}

		return $options;
	}
}

if (!function_exists('canvastack_js')) {
	
	function canvastack_js($scripts, $position = 'bottom', $as_script_code = false) {
		$template = new Canvastack\Canvastack\Library\Components\Template();
		
		return $template->js($scripts, $position, $as_script_code);
	}
}

if (!function_exists('canvastack_css')) {
	
	function canvastack_css($scripts, $position = 'top', $as_script_code = false) {
		$template = new Canvastack\Canvastack\Library\Components\Template();
		
		return $template->js($scripts, $position, $as_script_code);
	}
}

if (!function_exists('canvastack_gird')) {
	
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
	function canvastack_gird($name = 'start', $set_column = false, $addHTML = false, $single = false) {
		$adapter = \Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver::resolve();
		
		$numberColumn = 12;
		if (!empty($set_column)) {
			$numberColumn = intval(12 - $set_column);
		}
		
		$col = ' ' . $adapter->getColumnClass($numberColumn);
		
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
				$containerClass = $adapter->getContainerClass();
				$rowClass = $adapter->getRowClass();
				
				if ('start' === $name || 'container' === $name) {
					return "<div class=\"{$containerClass}\"><div class=\"{$rowClass}\"><div class=\"col{$col}\">";
				} elseif ('container-fluid' === $name) {
					return "<div class=\"{$containerClass}\"><div class=\"{$rowClass}\"><div class=\"col{$col}\">";
				} else {
					return "<div class=\"{$rowClass}\"><div class=\"col{$col}\"><div class=\"{$name}\">";
				}
			}
		}
	}
}

if (!function_exists('canvastack_set_gird_column')) {
	
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
	function canvastack_set_gird_column($html, $set_column = false) {
		$adapter = \Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver::resolve();
		
		$numberColumn = 12;
		if (!empty($set_column)) {
			$numberColumn = intval(12 / $set_column);
		}
		$col = ' ' . $adapter->getColumnClass($numberColumn);
		
		return "<div class=\"col{$col}\">{$html}</div>";
	}
}

if (!function_exists('canvastack_breadcrumb')) {
    
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
    function canvastack_breadcrumb($title, $links = [], $icon_title = false, $icon_links = false, $type = false) {
        return \Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver::resolve()
            ->renderBreadcrumb($title, $links, $icon_title, $icon_links, $type);
    }
}

if (!function_exists('canvastack_sidebar_content')) {
	
	/**
	 * Create Sidebar Content
	 *
	 * @param string $media_title
	 * @param string $media_heading
	 * @param string $media_sub_heading
	 * @param bool $type
	 */
	function canvastack_sidebar_content($media_title, $media_heading = false, $media_sub_heading = false, $type = true) {
		return \Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver::resolve()
			->renderSidebarContent($media_title, $media_heading, $media_sub_heading, $type);
	}
}

if (!function_exists('canvastack_sidebar_menu_open')) {
	
	/**
	 * Sidebar Open
	 *
	 * created @May 8, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean $class_name
	 * @return string
	 */
	function canvastack_sidebar_menu_open($class_name = false) {
		$class = 'main-menu';//'sidebar-menu'
		if (false !== $class_name) $class = $class_name;
		
		return '<ul id="menu" class="' . $class . '">';
	}
}

if (!function_exists('canvastack_sidebar_menu')) {
	
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
	/**
	 * Create Sidebar Menu
	 *
	 * @param string $label Menu label
	 * @param string|array $links Menu URL or array of submenu items
	 * @param array $icon Icon configuration
	 * @param boolean $selected Whether menu is selected
	 *
	 * @return string HTML menu markup
	 * 
	 * @security CRITICAL - Escapes all user-controllable data to prevent XSS
	 */
	function canvastack_sidebar_menu($label, $links, $icon = [], $selected = false) {
		// Escape label for use in ID attribute (alphanumeric only)
		$escapedIdLabel = canvastack_clean_strings($label);
		
		// Escape label for display (preserve special chars but escape HTML)
		$escapedLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
		
		$o = '<li id="' . $escapedIdLabel . '" class="submenu">';
		
		$icons					= [];
		$icons['before']		= $icon;
		$icons['after']			= '';//'class="arrow fa-angle-double-right"';
		$icons['after_label']	= false;
		
		if (true === is_array($links)) {
			// Check if any child menu is active (matches current URL or is parent of current URL)
			$currentUrl = url()->current();
			$hasActiveChild = false;
			
			foreach ($links as $child_title => $child_url) {
				if (is_array($child_url)) {
					// Check third-level menu items
					foreach ($child_url as $thirdChild => $thirdURL) {
						// Match if current URL starts with menu URL (for nested routes like /user/1/edit)
						$normalizedCurrentUrl = rtrim($currentUrl, '/');
						$normalizedMenuUrl = rtrim($thirdURL, '/');
						
						if ($normalizedCurrentUrl === $normalizedMenuUrl || 
							str_starts_with($normalizedCurrentUrl, $normalizedMenuUrl . '/')) {
							$hasActiveChild = true;
							break 2; // Break out of both loops
						}
					}
				} else {
					// Check second-level menu items
					// Match if current URL starts with menu URL (for nested routes like /user/1/edit)
					$normalizedCurrentUrl = rtrim($currentUrl, '/');
					$normalizedMenuUrl = rtrim($child_url, '/');
					
					if ($normalizedCurrentUrl === $normalizedMenuUrl || 
						str_starts_with($normalizedCurrentUrl, $normalizedMenuUrl . '/')) {
						$hasActiveChild = true;
						break;
					}
				}
			}
			
			// Update parent submenu class if any child is active
			if ($hasActiveChild) {
				$o = '<li id="' . $escapedIdLabel . '" class="submenu active">';
			}
			
			$o .= '<a class="arrow-node" href="javascript:void(0);">';
			
			if (false !== $icon) {
				// Icon data is system-generated from base_module table, not user input
				// Preserve icon HTML markup (e.g., <i class="fa fa-home"></i>)
				$safeIcon = $icon['icon'];
				$o .= '<span class="icon">' . $safeIcon . '</span>';
			}
			
			$o .= '<span class="text">' . htmlspecialchars(canvastack_underscore_to_camelcase($label), ENT_QUOTES, 'UTF-8') . '</span>';
			$o .= '<span' . $icons['after'] . '">' . $icons['after_label'] . '</span>';
			if (true === $selected) {
				$o .= '<span class="selected"></span>';
			}
			$o .= '</a>';
			
			$o .= '<ul>';
			foreach ($links as $child_title => $child_url) {
				if (is_array($child_url)) {
					$o .= '<li class="submenu"><a href="javascript:void(0);">';
					$o .= '<span class="text">' . htmlspecialchars(canvastack_underscore_to_camelcase($child_title), ENT_QUOTES, 'UTF-8') . '</span>';
					$o .= '<span class="arrow open fa-angle-double-down"></span></a>';
					$o .= '<ul>';
					foreach ($child_url as $thirdChild => $thirdURL) {
						// Escape all parts of nested menu
						$escapedThirdChild = htmlspecialchars(canvastack_underscore_to_camelcase($thirdChild), ENT_QUOTES, 'UTF-8');
						$escapedThirdURL = htmlspecialchars($thirdURL, ENT_QUOTES, 'UTF-8');
						$escapedThirdId = clean_strings($label) . '-' . clean_strings($child_title) . '-' . clean_strings($thirdChild);
						
						// Only add menu-active-pointer class if current URL matches or starts with menu URL
						$currentUrl = url()->current();
						$normalizedCurrentUrl = rtrim($currentUrl, '/');
						$normalizedMenuUrl = rtrim($thirdURL, '/');
						
						$isActive = ($normalizedCurrentUrl === $normalizedMenuUrl || 
									str_starts_with($normalizedCurrentUrl, $normalizedMenuUrl . '/'));
						$activeClass = $isActive ? ' menu-active-pointer' : '';
						
						$o .= '<li id="' . $escapedThirdId . '" class="' . trim($activeClass) . '"><a class="menu-url" href="' . $escapedThirdURL . '">' . $escapedThirdChild . '</a></li>';
					}
					$o .= '</ul>';
					$o .= '</li>';
				} else {
					// Escape child menu items
					$escapedChildTitle = htmlspecialchars(canvastack_underscore_to_camelcase($child_title), ENT_QUOTES, 'UTF-8');
					$escapedChildUrl = htmlspecialchars($child_url, ENT_QUOTES, 'UTF-8');
					
					// Only add menu-active-pointer class if current URL matches or starts with menu URL
					$currentUrl = url()->current();
					$normalizedCurrentUrl = rtrim($currentUrl, '/');
					$normalizedMenuUrl = rtrim($child_url, '/');
					
					$isActive = ($normalizedCurrentUrl === $normalizedMenuUrl || 
								str_starts_with($normalizedCurrentUrl, $normalizedMenuUrl . '/'));
					$activeClass = $isActive ? ' menu-active-pointer' : '';
					
					$o .= '<li class="' . trim($activeClass) . '"><a class="menu-url" href="' . $escapedChildUrl . '">' . $escapedChildTitle . '</a></li>';
				}
			}
			$o .= '</ul>';
		} else {
			// Escape URL
			$escapedUrl = htmlspecialchars($links, ENT_QUOTES, 'UTF-8');
			
			$o .= '<a href="' . $escapedUrl . '">';
			if (false !== $icon) {
				if (isset($icon['icon']) && null !== $icon['icon']) {
					// Icon data is system-generated from base_module table, not user input
					// Preserve icon HTML markup (e.g., <i class="fa fa-home"></i>)
					$safeIcon = $icon['icon'];
					$o .= '<span class="icon">' . $safeIcon . '</span>';
				} else {
					$o .= '<span class="icon"><i class="fa fa-tags"></i></span>';
				}
			}
			$o .= '<span class="text">' . htmlspecialchars(canvastack_underscore_to_camelcase($label), ENT_QUOTES, 'UTF-8') . '</span>';
			if (true === $selected) {
				$o .= '<span class="selected"></span>';
			}
			$o .= '</a>';
		}
		
		$o .= '</li>';
		
		return $o;
	}
}

if (!function_exists('canvastack_sidebar_category')) {
	
	/**
	 * Create Sidebar Title
	 *
	 * @param string $title Category title
	 * @param string $icon Icon class
	 * @param string $icon_position Icon position (left/right)
	 *
	 * @return string HTML category markup
	 * 
	 * @security CRITICAL - Escapes title to prevent XSS
	 */
	function canvastack_sidebar_category($title, $icon = false, $icon_position = false) {
		$adapter = \Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver::resolve();
		
		$o  = '<li class="sidebar-category">';
		$o .= '<span>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</span>';
		if (false !== $icon) {
			$position = 'right';
			if (false !== $icon_position) {
				$position = $icon_position;
			}
			// Escape icon class to prevent XSS
			$safeIcon = htmlspecialchars($icon, ENT_QUOTES, 'UTF-8');
			
			// Use adapter to get the correct float class
			if ('right' === $position) {
				$floatClass = $adapter->getFloatRightClass();
			} else {
				$floatClass = $adapter->getFloatLeftClass();
			}
			
			$o .= '<span class="' . htmlspecialchars($floatClass, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-' . $safeIcon . '"></i></span>';
		}
		$o .= '</li>';
		
		return $o;
	}
}

if (!function_exists('canvastack_sidebar_menu_close')) {
	
	/**
	 * Sidebar Close Menu
	 *
	 * created @May 8, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function canvastack_sidebar_menu_close() {
		return '</ul>';
	}
}

if (!function_exists('canvastack_set_avatar')) {
	
	/**
	 * Create User Image Link
	 *
	 * @param string $username
	 * @param string $link_url
	 * @param string $image_src
	 * @param string $user_status : online[default]/offline
	 */
	function canvastack_set_avatar($username, $link_url = false, $image_src = false, $user_status = 'online', $type_old = false) {
		$adapter = \Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver::resolve();
		
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
			
			// Use adapter for float-left class
			$floatLeftClass = $adapter->getFloatLeftClass();
			
			$o  = "<a class=\"{$floatLeftClass} has-notif avatar\"{$linkURL}>";
			$o .= "<img src=\"{$src}\" alt=\"{$username}\" title=\"{$username}\" {$style}/>";
			if (false !== $user_status) {
				$o .= "<i class=\"{$user_status}\"></i>";
			}
			$o .= "</a>";
		} else {
			// Use adapter for float-left class
			$floatLeftClass = $adapter->getFloatLeftClass();
			
			$o  = "<div>";
			$o .= "<div class=\"{$floatLeftClass} image\">";
			$o .= "<img class=\"user-avatar\" src=\"{$src}\" alt=\"{$username}\" title=\"{$username}\" />";
			$o .= "</div>";
			$o .= "<div class=\"{$floatLeftClass} info\">";
			$o .= "<h6 class=\"font-weight-light mt-2 mb-1\">{$username}</h6>";
			$o .= "<a href=\"#\"><i class=\"fa fa-circle text-primary blink\"></i> {$user_status}</a>";
			$o .= "</div>";
			$o .= "</div>";
			$o .= "<div class=\"clearfix\"></div>";
		}
		return $o;
	}
}