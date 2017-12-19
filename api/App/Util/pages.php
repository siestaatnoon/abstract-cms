<?php



/**
 * pages_tree_menu_html
 *
 * Creates the initial HTML used for the Pages tree menu selector in the CMS.
 * 
 * @param int The page id to show it's parent pages and subpages
 * @return string The menu HTML
 */
if ( ! function_exists('pages_tree_menu'))
{
	function pages_tree_menu_html($page_id=false) {
		$module = Module::load('pages');
		$model = $module->get_model();
		
		$pages = $model->get_parent_pages();
		$pages = $model->sort_alpha($pages);	
		$html = '<ul id="pages-tree">'."\n";
		
		if ( empty($page_id) ) {
			$html .= _pages_tree_item_html($pages);
		} else {
			$page = $model->get($page_id);
			$top_level_id = $page['top_level_id'];
			
			foreach ($pages as $page) {
				$html .= $page['page_id'] === $top_level_id ? 
						 _pages_tree_item_html( array($page), true, $page_id) : 
						 _pages_tree_item_html( array($page) );
			}
		}
		
		$html .= '</ul>'."\n";
		return $html;
	}
}

/**
 * pages_tree_menu_search_html
 *
 * Creates the menu HTML, from a search query, used for the Pages tree menu selector in the CMS.
 * 
 * @param string The search query
 * @param boolean True to include outer UL container
 * @param int The page number for pagination
 * @param int The number of items to show per page
 * @return array Associative array:
 *            page_count => The number of pages contained in the pagination
 *            html       => The tree menu HTML from search query
 */
if ( ! function_exists('pages_tree_menu_search_html'))
{
	function pages_tree_menu_search_html($query, $include_ul_cnt=false, $page_num=1, $per_page=20) {
		$return['page_count'] = 0;
		$return['html'] = '';
		
		if ( empty($query) ) {
			return $return;
		}
		if ( empty($page_num) || ! is_numeric($page_num) ) {
			$page_num = 1;
		}
		if ( empty($per_page) || ! is_numeric($per_page) ) {
			$per_page = 20;
		}

		$module = Module::load('pages');
		$model = $module->get_model();
		$filters = array();
		
		if ( ! empty($query) ) {
			$App = App::get_instance();
			$config = $App->load_config('pages');
			$equals = array();
			$like = array('_condition' => 'OR');
			foreach ($config['pages_fields'] as $field => $data) {
				if ($field === $data['module_pk']) {
					$equals[$field] = $query;
				} else if ($data['is_filter']) {
					$like[$field] = $query;
				}
			}
			$filters['equals'] = $equals;
			$filters['%like%'] = $like;
			$filters['_condition'] = 'OR';
		}
		$pages = $model->get_rows($filters);
		$item_count = count($pages);
		$page_count = ceil( ($item_count / $per_page) );
		if ( $page_num < 1 || $page_num > $page_count ) {
			return $return;
		}
		
		$filters['offset'] = ($page_num - 1) * $per_page;
		$filters['limit'] = $per_page;
		$pages = $model->get_rows($filters);
		$pages = $model->pages_sort_alpha($pages);
		$html = '';
		
		if ( ! empty($pages)) {
			$html = _pages_tree_item_html($pages);
			
			if ($include_ul_cnt) {
				$html = '<ul id="pages-tree">'."\n".$html.'</ul>'."\n";
			}
		}
		
		$return['page_count'] = $page_count;
		$return['html'] = $html;
		
		return $return;
	}
}

/**
 * pages_tree_submenu_html
 *
 * Creates the subpages HTML used for the Pages tree menu selector in the CMS.
 * Used with jQuery to load via AJAX.
 * 
 * @param int The parent page ID to generate subpage items
 * @return string The submenu HTML
 */
if ( ! function_exists('pages_tree_submenu_html'))
{
	function pages_tree_submenu_html($parent_id) {
		$module = Module::load('pages');
		$model = $module->get_model();
		$pages = $model->get_subpages($parent_id);
		$pages = $model->pages_sort_alpha($pages);
		$html = '';
		
		if ( ! empty($pages)) {
			$html = '<ul>'."\n";
			$html .= _pages_tree_item_html($pages);
			$html .= '</ul>'."\n";
		}
		
		return $html;
	}
}

/**
 * _pages_tree_item_html
 *
 * Creates the HTML used for each item of the Pages tree menu selector in the CMS. 
 * Not meant to be called outside of this helper.
 * 
 * @param array The array of Page objects
 * @param boolean True to recursively add subpages
 * @param int The parent page ID to stop recursion (loads this page's subpages and not beyond)
 * @return string The menu item HTML
 */
if ( ! function_exists('_pages_tree_item_html'))
{
	function _pages_tree_item_html($pages, $is_recursive=false, $stop_id=false) {
		if ( empty($pages) ) {
			return '';
		}
		
		$App = App::get_instance();
		$module = Module::load('pages');
		$model = $module->get_model();
		
		$admin_uri = (empty(WEB_BASE) ? '/' : WEB_BASE).$App->config('admin_uri_segment');
		$uncategorized_id = $model->get_uncategorized_id();
		$module_base_url = $admin_uri.'/pages/';
		$add_base_url = $module_base_url.'add/';
		$edit_base_url = $module_base_url.'edit/';
		$version_base_url = $module_base_url.'version/';
		$arrange_base_url = $module_base_url.'arrange/';
		$html = '';
		
		$page = current($pages);
		$level = $model->get_page_depth($page['page_id']);
		$max_levels = $App->config('pages_max_levels');
		$is_subpage_level = $level < $max_levels;
		
		foreach ($pages as $page) {
			$page_id = $page['page_id'];
			$parent_id = $page['parent_id'];
			$title = addslashes($page['short_title']);
			$is_permanent = $page['is_permanent'];
			$is_uc_page = $page_id == $uncategorized_id;
			$subpages = isset($page['subpages']) ? $page['subpages'] : array();
			$subpages = $model->pages_sort_alpha($subpages);
			$is_node = empty($subpages);
			$is_visible = $is_recursive && ! empty($subpages) && $page['parent_id'] != $stop_id;
			$class = $is_visible ? '  toggle-active' : ' toggle-inactive';
			$class .= ! $page['is_active'] ? ' page-inactive' : '';
			$class .= $is_node ? ' page-node' : '';
			
			$html .= '<li>'."\n";
			$html .= '  <div class="toggle-page">'."\n";
			$html .= '    <div id="toggle-'.$page_id.'" class="toggle-hitarea'.$class.'">'.$page['short_title'].'</div>'."\n";
			
			if ( count($subpages) > 0 ) {
				$html .= '    <div class="subpage-count">'.count($subpages).'</div>'."\n";
			}
			
			$html .= '    <div class="toggle-links '.($is_visible ? 'toggle-links-show' : 'toggle-links-hide').'">'."\n";
			
			if ( ! $is_uc_page) {
				$html .= '      <div><a href="/'.$page['slug'].'" target="_blank">View</a></div>'."\n";
			}
			
			if ( ! $is_permanent && $is_node) {
				$html .= '      <div><a class="toggle-delete" rel="'.$page_id.'" title="'.$title.'">Delete</a></div>'."\n";
			}
			
			if ( ! $is_node) {
				$html .= '      <div><a href="'.$arrange_base_url.$page_id.'">Arrange Subpages</a></div>'."\n";
			}
			
			if ($parent_id != $uncategorized_id && $is_subpage_level ) {
				$html .= '      <div><a href="'.$add_base_url.$page_id.'">Add Subpage</a></div>'."\n";
			}
			
			if ( ! $is_uc_page) {
				$html .= '      <div><a href="'.$edit_base_url.$page_id.'">Edit</a></div>'."\n";
				$html .= '    </div>'."\n";
			} else {
				$html .= '    </div>'."\n";
			}
			
			$html .= '  </div>'."\n";
			
			if ($is_visible) {
				$html .= '  <ul>'."\n";
				$html .= _pages_tree_item_html($subpages, true, $stop_id);
				$html .= '  </ul>'."\n";
			}
			
			$html .= '</li>'."\n";
		}
		
		return $html;
	}
}

/* End of file pages.php */
/* Location: ./App/Functions/pages.php */