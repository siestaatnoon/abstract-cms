<?php
namespace App\Model;

use
App\App,
App\Model\Model,
App\Exception\AppException;

/**
 * Model_pages class
 * 
 * 
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Model
 */
class Model_pages extends \App\Model\Model {
	
	const DEFAULT_ID = 1;
	const TOP_LEVEL_PARENT_ID = 1;
	const UNCATEGORIZED_ID = 2;
	const MAX_DEPTH = 10;
	const INDEX_BY_ID = 101;
	const INDEX_BY_ORDER = 102;
	const INDEX_BY_SLUG = 103;
	
	/**
	 * Array of pages retrieved in the database and used create sorted lists of page -> subpages.
	 *
	 */
	private static $pages = NULL;
	

	/**
	 * Constructor
	 *
	 * Calls the Model constructor.
	 */
    public function __construct($config) {
        parent::__construct($config);
		self::$pages = $this->query_pages();
    }
	
	
	/**
	 * Can Delete
	 *
	 * Checks if a row to be deleted has (sub)pages linked to it.
	 *
	 * @access public
	 * @see Admin_controller::delete calls this function to verify row can be deleted
	 * @param int The page id to check if is deleteable (has no subpages).
	 * @return boolean True if row can be deleted.
	 */
	public function can_delete($page_id) {
		if (empty($page_id)) {
			return false;
		}
		
		$page = $this->page_branch($page_id);
		return empty($page['subpages']);
	}
	

	/**
	 * Get by ID
	 *
	 * Returns a section object from the given row ID.
	 *
	 * @access public
	 * @param int The row ID.
	 * @param boolean True to include subpages of the page.
	 * @return array The section object or false if row not found.
	 */
	public function get($id, $is_slug=false, $include_subpages=true) {
	    if ( ! $is_slug) {
            $id = (int) $id;
        }
		if ( empty($id) || $id === self::TOP_LEVEL_PARENT_ID || $id === self::UNCATEGORIZED_ID) {
			return false;
		}

		$page = parent::get($id, $is_slug);
		if ( empty($page) ) {
			return false;
		}

		if ($include_subpages) {
			$subpages = $this->page_branch($page['page_id']);
			$page['subpages'] = $subpages;
		}
		
		return $page;
	}
	
	
	/**
	 * Get All
	 *
	 * Returns all rows or all active rows as section objects.
	 *
	 * @access public
	 * @return array The section objects.
	 */
	public function get_all($id_indexed=false) {
		$index = $id_indexed ? self::INDEX_BY_ID : self::INDEX_BY_ORDER;
		return $this->page_list(self::TOP_LEVEL_PARENT_ID, $index);
	}


    /**
     * Get by ID
     *
     * Returns a section object from the given row ID.
     *
     * @access public
     * @param int The row ID.
     * @param boolean True to include subpages of the page.
     * @return array The section object or false if row not found.
     */
    public function get_branch($id, $include_subpages=false) {
        $id = (int) $id;
        if ( empty($id) || $id === self::TOP_LEVEL_PARENT_ID || $id === self::UNCATEGORIZED_ID) {
            return false;
        }

        $page = array();
        if ($include_subpages) {
            $page = $this->page_branch($id);
        } else {
            $pages = $this->page_list(self::_PAGES_TOP_LEVEL_PARENT_ID, self::_PAGES_INDEX_BY_ID);
            $page = $pages[$id];
        }
        return $page;
    }
	
	
	/**
	 * Get Depth
	 *
	 * Returns the depth of a page or levels of a page's subpages + 1.
	 *
	 * @access public
	 * @param int The page ID
	 * @param object The page object used in recursion
	 * @return int The depth
	 */
	public function get_depth($page_id, $page=NULL) {
		if ($page === NULL) {
			$page = $this->get_branch($page_id, true);
			if ( empty($page) ) {
				return false;
			}
		}
		
		$max_depth = 0;
		$subpages = $page['subpages'];
		foreach ($subpages as $page) {
			$depth = 0;
			$depth += $this->get_depth($page_id, $page);
			if ($depth > $max_depth) {
				$max_depth = $depth;
			}
		}
		
		return $max_depth + 1;
	}
	
	
	public static function get_default_id() {
		return self::DEFAULT_ID;
	}
	

	/**
	 * get_top_level_id
	 *
	 * Returns the parent id of all top-level pages.
	 *
	 * @access public
	 * @return int The ID.
	 */
	public static function get_top_level_id() {
		return self::TOP_LEVEL_PARENT_ID;
	}
	
	
	/**
	 * get_uncategorized_id
	 *
	 * Returns the parent id of all pages not within the normal tree structure.
	 *
	 * @access public
	 * @return int The ID.
	 */
	public static function get_uncategorized_id() {
		return self::UNCATEGORIZED_ID;
	}
	
	
	/**
	 * Get For Breadcrumbs
	 *
	 * Returns an array of pages within the hierarchy of a given page ID 
	 * indexed by slug => title. Used for generating breadcrumbs.
	 *
	 * @access public
	 * @param int The page ID of current page
	 * @return array The array of pages
	 */
	public function get_for_breadcrumbs($page_id, $pages=NULL) {
		if (empty($page_id)) {
			return false;
		} else if ($pages == NULL) {
			$pages = $this->page_tree(self::INDEX_BY_ID);
		}
		
		$items = array();
		foreach ($pages as $id => $page) {
			$arr = array($page['slug'] => $page['short_title']);
			if ($id == $page_id) {
				$items = $arr;
				break;
			} else if ( ! empty($page['subpages'])) {
				$arr2 = $this->get_for_breadcrumbs($page_id, $page['subpages']);
				if ( ! empty($arr2)) {
					if ($id == self::UNCATEGORIZED_ID) {
						$items = $arr2;
					} else {
						$items = $arr + $arr2;
					}
					break;
				}
			}
		}
		
		return $items;
	}
	
	
	/**
	 * Get Level
	 *
	 * Returns the level of a page or position from the top hierarchy (1 is top).
	 *
	 * @access public
	 * @param int The page ID
	 * @param object The page object used in recursion
	 * @return int The depth or false if page ID invalid
	 */
	public function get_page_depth($page_id) {
		$page = $this->get_branch($page_id);
		$parent_id = (int) $page['parent_id'];
		if ( $parent_id === self::TOP_LEVEL_PARENT_ID || empty($page) ) {
			return false;
		} else if ($parent_id === self::UNCATEGORIZED_ID) {
			return 2;
		}
		
		$level = 1;
		while (true) {
			$page = $this->get_branch($page['parent_id']);
			$level += 1;
			if ($page['parent_id'] === self::TOP_LEVEL_PARENT_ID) {
				break;
			}
		}
		
		return $level;
	}
	
	
	/**
	 * Get Linked Rows
	 *
	 * Retrieves a list of subpages (and corresponding admin view links) linked to a page ID.
	 *
	 * @access public
	 * @see Admin_controller::delete calls this function to list pages to delete in order to delete row
	 * @param int The page id.
	 * @param int The current page level (used in recursion).
	 * @param boolean Flag to use on first iteration of recursion.
	 * @return array List of pages to delete.
	 */
	public function get_linked_rows($page_id, $level=0, $is_first_level=true) {
		$links = array();
		if (empty($page_id)) {
			return $links;
		}
		$page = $this->page_branch($page_id);
		if (count($page['subpages']) > 0) {
			foreach ($page['subpages'] as $subpage) {
				$space = '';
				for ($i=0; $i < $level; $i++) {
					$space .= '--';
					if ($i == $level - 1) {
						$space .= ' ';
					}
				}
				$link['link'] = '/pages/edit/'.$subpage['page_id'];
				$link['title'] = $space.$subpage['short_title'];
				$links[] = $link;
				if (count($subpage['subpages']) > 0) {
					$sublinks = $this->get_linked_rows($subpage['page_id'], $level+1, false);
					$links = array_merge($links, $sublinks);
				}
			}
		}
		
		if ($is_first_level) {
			return array('Pages' => $links);
		}
		
		return $links;
	}
	
	
	/**
	 * Get Page ID by Slug
	 *
	 * Returns the page ID from a given slug or false if slug to a non existant page.
	 *
	 * @access public
	 * @see M_Slugs for more about row slugs
	 * @param string The row slug.
	 * @return mixed The page ID or false if no page associated
	 */
	public function get_page_id_by_slug($slug) {
		if (empty($slug)) {
			return false;
		}
		$page_id = false;
		foreach (self::$pages as $parent) {
			foreach ($parent as $page) {
				if ($page['slug'] == $slug) {
					$page_id = $page['page_id'];
					break 2;
				}
			}
		}
		return $page_id;
	}
	

	/**
	 * Get Parent Pages
	 *
	 * Returns the top-level pages and subpages to a given depth.
	 *
	 * @access public
	 * @param int The depth (level - 1) of the subpages to be returned.
	 * @return array The array of top level pages.
	 */
	public function get_parent_pages($depth=self::MAX_DEPTH) {
		return $this->page_tree(self::INDEX_BY_ORDER, $depth);
	}
	

	/**
	 * get_rows
	 *
	 * Retrieves a result set from an optional assoc array of parameters. Note
	 * that the following parameters are unique to this class:<br/><br/>
	 * <ul>
	 * <li>is_tree => (Optional) True to retrieve subpages in their tree structure (default false)</li>
	 * <li>depth => (Optional) Maximum subpage depth of subpages to retrieve (default zero)</li>
	 * <li>
	 * </ul>
	 *
	 * @access public
	 * @param array $params The parameters for the query
	 * @return mixed The query result in an assoc array or false if query failed an
	 * App\Exception\SQLException is passed and to be handled by \App\App class if an 
	 * SQL error occurred
	 * @see Model::get_rows for function definition
	 */
	public function get_rows($params=array()) {
		$depth = empty($params['is_tree']) ? (empty($params['depth']) ? 0 : $params['depth']) : -1;
        if ( ! empty($params['fields']) && ! in_array('page_id', $params['fields']) ) {
        // page ID PK needed for adding subpages, sorting and other functions
            $params['fields'][] = 'page_id';
        }
		$result = parent::get_rows($params);
		if ( is_array($result) ) {
			foreach ($result as $i => &$row) {
                if ( empty($row['page_id']) ) {
                    continue;
                }
				$row['subpages'] = $this->get_subpages($row['page_id'], $depth);
			}
            $result = array_values($result);
		}

		return $result;
	}
	

	/**
	 * Get Subpages
	 *
	 * Returns the subpages with a given parent page ID.
	 *
	 * @access public
	 * @param int The parent page ID
	 * @return array The array of top level pages.
	 */
	public function get_subpages($parent_id) {
		if (empty($parent_id) || $parent_id <= self::TOP_LEVEL_PARENT_ID) {
			return false;
		}

		return $this->page_subpages($parent_id);
	}
	
	
	public function get_top_level_parent_id($page_id, $pages=NULL) {
		if ( empty($page_id) ) {
			return false;
		} else if ( is_array($pages) === false ) {
			$pages = $this->page_list(self::TOP_LEVEL_PARENT_ID, self::INDEX_BY_ID);
		}
		
		$top_level_id = false;
		$default_id = $this->get_default_id();

		if ( ! empty($pages[$page_id]) ) {
			$page = $pages[$page_id];
			if ( (int) $page['parent_id'] === $default_id) {
				$top_level_id = $page['page_id'];
			} else {
				$top_level_id = $this->get_top_level_parent_id($page['parent_id'], $pages);
			}
		}
		
		return $top_level_id;
	}
	

	/**
	 * Get Tree
	 *
	 * Returns all pages within their proper tree structure and indexed by page ID (except subpages).
	 *
	 * @access public
	 * @return array The section objects.
	 */
	public function get_tree() {
		return $this->page_tree(self::INDEX_BY_ID);
	}
	

	/**
	 * Has Inactive Parent
	 *
	 * Checks if a page id has an inactive page within it's parent page hierarchy.
	 *
	 * @access public
	 * @param int The page id.
	 * @return boolean True if page has inactive parent page.
	 */
	public function has_inactive_parent($page_id) {
		$page = $this->get_branch($page_id);
		if (empty($page) ||
			$page['parent_id'] == self::TOP_LEVEL_PARENT_ID || 
			$page['parent_id'] == self::UNCATEGORIZED_ID) {
			return false;
		}
		
		$has_inactive = false;
		$parent_id = $page['parent_id'];
		while ($parent_id != self::TOP_LEVEL_PARENT_ID) {
			$page = $this->get_branch($parent_id);
			if ( ! $page['is_active']) {
				$has_inactive = true;
				break;
			}
			$parent_id = $page['parent_id'];
		}
		
		return $has_inactive;
	}
	

	/**
	 * In Tree
	 *
	 * Checks if a page id is a subpage of a parent page id.
	 *
	 * @access public
	 * @param int The parent id.
	 * @param int The page id to check if subpage of parent id.
	 * @return boolean True if page is a subpage of parent page.
	 */
	public function in_tree($parent_id, $id) {
		if ( empty($parent_id) || empty($id) ) {
			return false;
		} else if ( (int) $parent_id === (int) $id) {
			return true;
		}
		
		$in_branch = false;
		$parent = $this->get_branch($parent_id, true);
		$to_check = $this->get_branch($id, true);
		if ( empty($parent) || empty($to_check) ) {
			return false;
		} else if ($to_check['page_id'] === $parent['page_id']) {
			return true;
		}

		foreach ($parent['subpages'] as $item) {
			if ($item['page_id'] === $to_check['page_id']) {
				$in_branch = true;
				break;
			}
		
			if ( count($item['subpages']) > 0 && $this->in_tree($item['page_id'], $id) ) {
				$in_branch = true;
				break;
			}
		}

		return $in_branch;
	}
 

	/**
	 * Insert
	 *
	 * Inserts a row with the given array of fields and corresponding values.
	 *
	 * @access public
	 * @param array The array of fields and values.
	 * @return boolean True if insert was successful, false if not.
	 */
	public function insert($data) {
		if ( empty($data) ) {
			return false;
		}
		
		//set sort order as last for pages with same depth
		$query = "SELECT MAX(sort_order) AS m FROM ".$this->db->escape_identifier($this->table_name)." ";
		$query .= "WHERE ".$this->db->escape_identifier('parent_id')."=".$this->db->escape($data['parent_id']);
		$result = $this->db->query($query);
		$rows = $result->result_assoc();
		$row = $rows[0];
		$data['sort_order'] = $row['m'] + 1;

		$pk = parent::insert($data);
		
		//update url field to same as slug
		$query = "UPDATE ".$this->db->escape_identifier($this->table_name)." ";
		$query .= "SET ".$this->db->escape_identifier('url')."=".$this->db->escape_identifier('slug')." ";
		$query .= "WHERE ".$this->db->escape_identifier('page_id')."=".$this->db->escape($pk);
		$this->db->query($query);

		return $pk;
	}
	
	
	/**
	 * Pages Select Array
	 *
	 * Returns an array of pages (not in hierarchy) used for dropdown/jump menus or other select.
	 * Dashes or other dividers from param are used to designate levels of pages.
	 *
	 * @access public
	 * @param int/array The page ID to omit.
	 * @param int Max 
	 * @param string Prefix added before ID index in return array.
	 * @param int Current depth of pages (used in recursion).
	 * @param string Dashes used to prefix/indent subpages (used in recursion).
	 * @param array Array of pages/subpages (used in recursion).
	 * @return array The array of pages for select menus.
	 */
	public function pages_select_array($omit_id=array(), $max_levels=self::MAX_DEPTH, $value_pref='', $depth=1, $dashes='', $pages=array()) {
		if (count($pages) == 0) {
			$pages = $this->page_tree(self::INDEX_BY_ORDER, $max_levels);
			$pages = $this->sort_alpha($pages);
		}
	
		$options = array();
		$skip = is_array($omit_id) ? $omit_id : array($omit_id);
		
		for ($i=0; $i < count($pages); $i++) {
			$pp = $pages[$i];
			
			if ($pp['parent_id'] !== self::UNCATEGORIZED_ID) {
				$subpages = $pp['subpages'];
				$subpages = $this->sort_alpha($subpages);

				if (in_array($pp['page_id'], $skip) === false) {
					/*
					$option = array();
					$option['name'] = ( empty($dashes) ? '' : $dashes.' ' ).$pp['short_title'];
					$option['value'] = $value_pref.$pp['page_id'];
					$option['depth'] = $depth;
					*/
					$name = ( empty($dashes) ? '' : $dashes.' ' ).$pp['short_title'];
					$value = $value_pref.$pp['page_id'];
					$options[$value] = $name;
					
					if (count($subpages) > 0 && $depth < $max_levels) {
						$suboptions = $this->pages_select_array(
							$omit_id, 
							$max_levels, 
							$value_pref, 
							($depth+1), 
							$dashes.'&mdash;', 
							$subpages
						);
						$options = $options + $suboptions;
					}
				}
			}	
		}

		return $options;
	}
	
	
	/**
	 * Pages Sort Alpha
	 *
	 * Accepts an array of page objects and sorts it by page title (short_title).
	 * 
	 * @access public
	 * @param array The Pages array
	 * @return array The sorted pages array, or the array passed in if empty
	 */
	public static function sort_alpha($pages) {
		if ( empty($pages) ) {
			return $pages;
		}
		$uncategorized_id = self::get_uncategorized_id();
		$order = array();
		$aux = array();
		$sorted = array();
		$uc_page = array();
		$has_uc = false;
		
		foreach ($pages as $i => $page) {
			if ($page['page_id'] == $uncategorized_id) {
				$uc_page = $page;
				$has_uc = true;
				continue;
			}
			$order[] = $page['short_title'];
			$aux[] = $page;
		}
		
		asort($order, SORT_STRING);
		if ($has_uc) {
			$sorted[] = $uc_page;
		}
		foreach ($order as $i => $value) {
			$sorted[] = $aux[$i];
		}
		
		return $sorted;
	}
	
	
	/**
	 * Parent Select Array
	 *
	 * Returns an array of parent pages with subpages ([prefix +]page ID => page name) used 
	 * for dropdown/jump menus.Pages names appear with parent pages (e.g. Page1 >> Page2 >> Page3).
	 *
	 * @access public
	 * @param int The page ID to omit.
	 * @param string Prefix added before ID index in return array.
	 * @param int Current depth of pages (used in recursion).
	 * @param string Dashes used to prefix/indent subpages (used in recursion).
	 * @param array Array of pages/subpages (used in recursion).
	 * @return array The array of ([prefix +]page ID => page names).
	 */
	public function parent_select_list($omit_id=array(), $value_pref='', $depth=1, $dashes='', $pages=array()) {
		if (count($pages) == 0) {
			$pages = $this->page_tree(self::INDEX_BY_ORDER, self::MAX_DEPTH);
		}
	
		$options = array();
		$skip = is_array($omit_id) ? $omit_id : array($omit_id);
		
		for ($i=0; $i < count($pages); $i++) {
			$pp = $pages[$i];
			
			if ($pp['parent_id'] <> self::UNCATEGORIZED_ID) {
				$subpages = $pp['subpages'];
		
				if (count($subpages) > 0 && $depth < self::MAX_DEPTH - 1 && in_array($pp['page_id'], $skip) === false) {
					$options[$value_pref.$pp['page_id']] = ( empty($dashes) ? '' : $dashes.' ' ).$pp['short_title'];
					$options = $options + $this->parent_select_list($omit_id, $value_pref, ($depth+1), $dashes.'&mdash;', $subpages);
				}
			}	
		}
	
		return $options;
	}
	
	
	/**
	 * Update
	 *
	 * Updates a row with the given array of fields and corresponding values, including row ID.
	 *
	 * @access public
	 * @param array The array of fields and values.
	 * @return boolean True if update was successful, false if not.
	 */
	public function update($data) {
		if ( empty($data) ) {
			return false;
		}

        $page = $this->get($data[$this->pk_field], false, false);
		$has_updated = parent::update($data);

        // if parent ID has changed, update sort for
        // page to be last for new parent page
        if ($has_updated && (int) $page['parent_id'] !== (int) $data['parent_id'] ) {
            $ids = array();
            $subpages = $this->get_subpages($data['parent_id']);
            foreach ($subpages as $sp) {
                $ids[] = $sp['page_id'];
            }
            $ids[] = $data['page_id'];
            $this->set_sort_order($ids);
        }
		
		//update url field to same as slug
		$query = "UPDATE ".$this->db->escape_identifier($this->table_name)." ";
		$query .= "SET ".$this->db->escape_identifier('url')."=".$this->db->escape_identifier('slug')." ";
		$query .= "WHERE ".$this->db->escape_identifier('page_id')."=".$this->db->escape($data[$this->pk_field]);
		$this->db->query($query);
		
		return $has_updated;
	}
	
	
	/**
	 * Get Pages List
	 *
	 * Returns the subpages from a given parent page ID in a single dimensioned array.
	 *
	 * @access private
	 * @param int The parent page ID to retrieve subpages.
	 * @param int Index type of return array (numeric, id or slug).
	 * @param int Top level page ID of subpages tree (used in recursion).
	 * @return array The array of subpages.
	 */
	private function page_list($parent_id, $index_type=self::INDEX_BY_ORDER, 
	$top_level_id=self::TOP_LEVEL_PARENT_ID) {
		if (count(self::$pages) == 0) {
			return false;
		}
		$pages = array();
		
		if (isset(self::$pages[$parent_id])) {
			$arr = self::$pages[$parent_id];
	
			for ($i=0; $i < count($arr); $i++) {
				$page = $arr[$i];
				$page['top_level_id'] = $page['parent_id'] == self::TOP_LEVEL_PARENT_ID ? $page['page_id'] : $top_level_id;
				
				if ($index_type == self::INDEX_BY_ID) {
					$pages[$page['page_id']] = $page;
				} else if ($index_type == self::INDEX_BY_SLUG) {
					$pages[$page['slug']] = $page;
				} else if ($index_type == self::INDEX_BY_ORDER) {
					$pages[] = $page;
				}
				
				if (isset(self::$pages[$page['page_id']])) {
					if ($index_type == self::INDEX_BY_ID || $index_type == self::INDEX_BY_SLUG) {
						$pages = $pages + $this->page_list($page['page_id'], $index_type, $page['top_level_id']);
					} else {
						$pages = array_merge($pages, $this->page_list($page['page_id'], $index_type, $page['top_level_id']));
					}
				}
			}
		}

		return $pages;
	}
	

	/**
	 * page_branch
	 *
	 * Returns the subpages from a given parent page ID or slug in a single dimensioned array.
	 * Differs from the page_subpages() function by allowing the page slug as a parameter
	 * (in addition to the page ID) to retrieve it's subpages and returns the parent page
	 * in addition to it's subpages in the page tree structure.
	 *
	 * @access private
	 * @param int The parent page ID or slug to retrieve subpages.
	 * @param array The pages to check for the ID or slug match (used in recursion).
	 * @return array The page with subpages.
	 */
	private function page_branch($page_id, $pages=NULL) {
		if (empty($page_id)) {
			return false;
		}

		if ($pages == NULL) {
			$pages = $this->page_tree(self::INDEX_BY_ID, self::MAX_DEPTH);
		}
	
		$page = array();

		foreach ($pages as $id => $arr) {
			if ($arr['slug'] === $page_id || $id === (int) $page_id) { //check for page_id or slug match
				$page = $arr;
				break;
			} else if ( ! empty($arr['subpages']) ) {
				$page = $this->page_branch($page_id, $arr['subpages']);
				if ( ! empty($page) ) {
					break;
				}
			}
		}
		
		return $page;
	}
	
	
	/**
	 * page_subpages
	 *
	 * Returns the subpages from a given parent page ID in it's tree structure.
	 *
	 * @access private
	 * @param int The parent page ID to retrieve subpages.
	 * @param int Number of levels of subpages to retrieve.
	 * @param int Top level page ID of subpages tree (used in recursion).
	 * @param int Index type of return array (numeric, id or slug).
	 * @return array The array of subpages and child pages.
	 */
	private function page_subpages($parent_id, $depth=-1, $top_level_id=self::TOP_LEVEL_PARENT_ID, $index_type=self::INDEX_BY_ORDER) {
		if ( empty(self::$pages) ) {
			return false;
		}
		$pages = array();
		
		if ($depth > 0) {
			$depth -= 1;
		}
		
		if ( isset(self::$pages[$parent_id]) ) {
			$arr = self::$pages[$parent_id];
			
			foreach ($arr as $item) {
				$item['top_level_id'] = $top_level_id == self::TOP_LEVEL_PARENT_ID ? $parent_id : $top_level_id;
				
				if ($depth === -1 || $depth > 0) {
					$item['subpages'] = $this->page_subpages($item['page_id'], $depth, $item['top_level_id'], $index_type);
				} else {
					$item['subpages'] = array();
				}
				
				if ($index_type == self::INDEX_BY_ID) {
					$pages[$item['page_id']] = $item;
				} else if ($index_type == self::INDEX_BY_SLUG) {
					$pages[$item['slug']] = $item;
				} else if ($index_type == self::INDEX_BY_ORDER) {
					$pages[] = $item;
				}
			}
		}
		
		return $pages;
	}
	

	/**
	 * Get Pages Tree
	 *
	 * Returns the entire page tree structure to a given depth.
	 *
	 * @access private
	 * @param int Index type of return array (numeric, id or slug).
	 * @param int Number of levels of subpages to retrieve (depth).
	 * @return array The page tree structure.
	 */
	private function page_tree($index_type=self::INDEX_BY_ORDER, $depth=-1) {
		$pages = array();
		
		if ($depth <> -1) {
			$depth -= 1;
		}
		
		if ( isset(self::$pages[self::TOP_LEVEL_PARENT_ID]) ) {
			$arr = self::$pages[self::TOP_LEVEL_PARENT_ID];
			
			for ($i=0; $i < count($arr); $i++) {
				$top_page = $arr[$i];
				$top_level_id = $top_page['page_id'];
				$top_page['top_level_id'] = $top_level_id;
				$top_page['subpages'] = $this->page_subpages($top_page['page_id'], $depth, $top_level_id, $index_type);
				
				if ($index_type == self::INDEX_BY_ID) {
					$pages[$top_page['page_id']] = $top_page;
				} else if ($index_type == self::INDEX_BY_SLUG) {
					$pages[$top_page['slug']] = $top_page;
				} else if ($index_type == self::INDEX_BY_ORDER) {
					$pages[] = $top_page;
				}

			}
		}
		
		return $pages;
	}
	

	/**
	 * Query Pages
	 *
	 * Retrieves the db pages for use in all functions of this class.
	 *
	 * @access private
	 * @return array The db pages.
	 */
	private function query_pages() {
		$query = "SELECT ";
		$query .= $this->db->escape_identifier('page_id').", ";
		$query .= $this->db->escape_identifier('parent_id').", ";
		$query .= $this->db->escape_identifier('short_title').", ";
		$query .= $this->db->escape_identifier('slug').", ";
		$query .= $this->db->escape_identifier('is_permanent').", ";
		$query .= $this->db->escape_identifier('is_active')." ";
		$query .= "FROM ".$this->db->escape_identifier($this->table_name)." ";
		$query .= "WHERE ".$this->db->escape_identifier('parent_id')."!=0 ";
		$query .= "ORDER BY ".$this->db->escape_identifier('parent_id')." ASC, ";
		$query .= $this->db->escape_identifier('sort_order')." ASC;";
		$result = $this->db->query($query);
		$rows = parent::parse_result($result);
		$pages = array();
		$current_parent = 0;
		
		foreach ($rows as $row) {
			if ($current_parent !== $row['parent_id']) {
				$current_parent = $row['parent_id'];
			}
			if ( ! isset($pages[$current_parent])) {
				$pages[$current_parent] = array();
			}
			$pages[$current_parent][] = $row;
		}
		
		return $pages;
	}

}

/* End of file m_pages.php */
/* Location: ./application/models/m_pages.php */