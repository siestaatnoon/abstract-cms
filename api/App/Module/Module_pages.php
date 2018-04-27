<?php

namespace App\Module;

use
App\Html\Form\AdminSortForm,
App\Html\ListPage\AdminListPage,
App\Model\Model_pages,
App\Exception\AppException;

/**
 * Module_pages class
 * 
 * Subclass of \App\Module\Abstract_module, this class provides custom definitions for the Pages module.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Module
 */
class Module_pages extends \App\Module\Abstract_module {

	/**
	 * @var int Number of rows to show before paginating, NOTE only if search filters used.
	 *
	 */
	private static $ITEMS_PER_PAGE = 4;
	
	
	/**
	 * Constructor
	 *
	 * Initializes the Users module.
	 * 
	 * @access public
     * @throws \App\Exception\AppException if an error occurs while loading module, rethrown and
     * handled by \App\App class
	 */
	public function __construct() {
	    try {
		    parent::__construct('pages');
        } catch (AppException $e) {
            throw $e;
        }
        $this->sort_params = array(
            'parent_id' => array(
                'name'      => 'Parent Page',
                'values'    => $this->model->pages_select_array(false, Model_pages::MAX_DEPTH - 1)
            ),
            AdminSortForm::ALL_SEGMENT => false
        );
	}
	
	
	/**
	 * add
	 *
	 * Inserts a module table row from an assoc array of fields => values.
	 *
	 * @access public
	 * @param array $data The fields and corresponding values to insert
	 * @return mixed The primary key of the inserted row OR an array of errors in format 
	 * array( 'errors' => (array) $errors) if insert unsuccessful
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
	 */
	public function add($data) {
		$return = parent::add($data);
		
		if ( ! empty($data['page_id']) ) {
			$cookie = $this->App->config('session_cms_cookie');
			$session =  $this->App->session($cookie);
			$params = $session->get_data($this->session_name);
			if ( empty($params) ) {
				$params = array();
			}
			
			$page_id = $data['page_id'];
			$top_level_id = $this->model->get_top_level_parent_id($page_id);
			$params['parent_show_id'] = $top_level_id;
			$params['stop_id'] = $page_id;
			$session->set_data($this->session_name, $params);
		}
		
		return $return;
	}


    /**
     * admin_func_subpages
     *
     * Custom API function to return the subpage data given a parent page ID from POST vars.
     *
     * @access public
     * @param array $params Assoc array of key => values to pass into the function
     * @param array $vars The assoc array of GET or POST vars
     * @return array The array of subpage data in format array(pages => array(...) )
     */
    public function admin_func_subpages($params, $vars) {
        if ( empty($vars['parent_id']) ) {
            $message = error_str('error.var.empty', 'POST[parent_id]');
            return array('errors' => array($message) );
        }

        $filter = array();
        $filter['equals']['parent_id'] = $vars['parent_id'];
        $where = array('where' => $filter);
        $rows = $this->model->get_rows($where);
        $this->pages_sort($rows);
        return array('pages' => $rows);
    }


    /**
     * form_field_module_id_form
     *
     * Creates the custom form field HTML to select a module for adding it's list page to a page.
     *
     * @access public
     * @param mixed $page_id The page ID
     * @param mixed $value The value of the field
     * @param \App\User\Permission $permission The current CMS user Permission object
     * @param array $params Additional parameters passed into function
     * @return string The module permissions form fields HTML
     * @throws \App\Exception\AppException if $permission parameter invalid, handled by \App\App class
     */
    public function form_field_module_id_form($page_id, $value, $permission, $params=array()) {
        if ($permission instanceof \App\User\Permission === false ) {
            $msg_part = error_str('error.param.type', array('$permission', '\\App\\User\\Permission'));
            $message = error_str('error.type.param.invalid', $msg_part);
            throw new AppException($message, AppException::ERROR_FATAL);
        }

        $module_id_form = empty($value) ? '' : $value;
        $modules = self::$MODULES;
        $values = array(0 => '-- None --');
        foreach ($modules as $name => $mod) {
            if ( Module::is_core($name) || empty($mod['use_frontend_form']) || empty($mod['use_model']) ) {
                continue;
            }
            $values[ $mod['id'] ] = $mod['label_plural'];
        }

        $params['name'] = 'module_id_form';
        $params['value'] = $module_id_form;
        $params['values'] = $values;
        $params['attr']['class'] = 'module-select';
        $params['use_template_vars'] = false;
        $params['is_readonly'] = $permission->has_add() === false && $permission->has_update() === false;
        return form_select($params);
    }


    /**
     * form_field_module_id_list
     *
     * Creates the custom form field HTML to select a module for adding it's list page to a page.
     *
     * @access public
     * @param mixed $page_id The page ID
     * @param mixed $value The value of the field
     * @param \App\User\Permission $permission The current CMS user Permission object
     * @param array $params Additional parameters passed into function
     * @return string The module permissions form fields HTML
     * @throws \App\Exception\AppException if $permission parameter invalid, handled by \App\App class
     */
    public function form_field_module_id_list($page_id, $value, $permission, $params=array()) {
        if ($permission instanceof \App\User\Permission === false ) {
            $msg_part = error_str('error.param.type', array('$permission', '\\App\\User\\Permission'));
            $message = error_str('error.type.param.invalid', $msg_part);
            throw new AppException($message, AppException::ERROR_FATAL);
        }

        $module_id_list = empty($value) ? '' : $value;
        $modules = self::$MODULES;
        $values = array(0 => '-- None --');
        foreach ($modules as $name => $mod) {
            if ( Module::is_core($name) || empty($mod['use_frontend_list']) ) {
                continue;
            }
            $values[ $mod['id'] ] = $mod['label_plural'];
        }

        $params['name'] = 'module_id_list';
        $params['value'] = $module_id_list;
        $params['values'] = $values;
        $params['attr']['class'] = 'module-select';
        $params['use_template_vars'] = false;
        $params['is_readonly'] = $permission->has_add() === false && $permission->has_update() === false;
        return form_select($params);
    }
	

	/**
	 * form_field_parent_id
	 *
	 * Creates the custom form field HTML for the module permissions fields for a user. Each
	 * field is a jQuery multiselect, for each module, which converts permission data into a
	 * JSON string savedto a submitted hidden form field.
	 * 
	 * @access public
     * @param mixed $page_id The page ID
     * @param mixed $value The value of the field
     * @param \App\User\Permission $permission The current CMS user Permission object
     * @param array $params Additional parameters passed into function
	 * @return string The module permissions form fields HTML
     * @throws \App\Exception\AppException if $permission parameter invalid, handled by \App\App class
	 */
	public function form_field_parent_id($page_id, $value, $permission, $params=array()) {
		if ($permission instanceof \App\User\Permission === false ) {
            $msg_part = error_str('error.param.type', array('$permission', '\\App\\User\\Permission'));
            $message = error_str('error.type.param.invalid', $msg_part);
			throw new AppException($message, AppException::ERROR_FATAL);
		}

		$parent_id = $value;
		$page_depth = 0;

		if ( ! empty($page_id) ) {
			$page = $this->model->get($page_id);
			if ( ! empty($page) ) {
				$page_depth = $this->model->get_depth($page_id);
			}
		} else {
			$param = current($params);
			if ( is_numeric($param) ) {
				$parent_id = $param;
			}
		}
		
		$max_levels = $this->App->config('pages_max_depth');
		$default_parent_id = $this->model->get_top_level_id();
		$values = array($default_parent_id => 'Top-level Page');
		$max_level = $max_levels - $page_depth;
		$pages = $this->model->pages_select_array($page_id, $max_levels - 1);
		$values = $values + $pages;

		$params['name'] = 'parent_id';
		$params['value'] = $parent_id;
		$params['values'] = $values;
		$params['use_template_vars'] = false;
		$params['is_readonly'] = $permission->has_add() === false && $permission->has_update() === false;
		return form_select($params);
	}


    /**
     * get_default_field_values
     *
     * Overwrites parent class method to update the parent_id ID value in a Pages module item.
     *
     * @access public
     * @param array $params Array of parameters passed in and used by subclass method overwrites
     * @return array The assoc array of default form fields and values
     * @see Abstract_module::get_default_field_values() for method implementation
     */
	public function get_default_field_values($params=array()) {
		$data = parent::get_default_field_values($params);
		if ( ! empty($params) ) {
			$data['parent_id'] = current($params);
		}
		return $data;
	}


    /**
     * get_cms_list
     *
     * Overwrites parent class method to return only top-level pages, not include uncategorized pages
     * and to include parent_id, is_permanent and url query parameters.
     *
     * @access public
     * @param array $get Assoc array of the query parameters for the list of rows
     * @param bool $is_archive True if list of rows returned are marked as archived
     * @param \App\User\Permission $permission The current logged in user permission object
     * @return array Assoc array of the admin list data
     * @throws \App\Exception\AppException if $permission invalid object, handled by \App\App class
     * @see Abstract_module::get_cms_list() for method implementation
     */
	public function get_cms_list($get, $is_archive, $permission) {
		if ( ! $this->module['use_model']) {
		//if module uses options table instead of model, can't retrieve a row
			return false;
		}
		
		$List = new AdminListPage($this->module_name, $is_archive, $permission);
		$filters = empty($get['filters']) ? array() : $get['filters'];
		$pk_field = $this->module['pk_field'];
		$fields = $this->module['field_data']['defaults'];
		
		//show non-archived by default
		$is_archive = array('is_archive' => 0);	
		$list_filters = empty($filters) ? $is_archive : ($filters + $is_archive);

		//if any filters are relations, retrieve those; note that separate
		//queries are used to retrieve the relations, not joins
		$relations = $this->get_relations();
        $rel_config = $this->module['field_data']['relations'];
		$row_ids = false;
		$has_rows = true;
		foreach ($relations as $field_name => $rel) {
            $module_name = empty($rel_config[$field_name]['module']) ? '' : $rel_config[$field_name]['module'];
			if ( ! empty($list_filters[$module_name]) ) {
				$ids = $rel->filter($list_filters[$module_name]);
				$row_ids = $row_ids === false ? $ids : array_intersect($row_ids, $ids);
				
				//remove relation values from db query params
				unset($list_filters[$module_name]);
			}
		}
		
		//set db query filters
		$db_filters = $List->db_query_params($list_filters);
		
		//OVERWRITTEN in parent method to show only
		//top level pages upon initial list page load
		$has_filters = false;
		foreach ($filters as $name => $val) {
			if ($val !== '') {
				$has_filters = true;
				break;
			}
		}
		if ($has_filters === false) {
			$parent_id_field = 'parent_id';
			$use_field = true;
			foreach ($db_filters as $op => $ftr) {
				foreach ($ftr as $name => $val) {
					if ($name === $parent_id_field) {
						$use_field = false;
						break;
					}
				}
			}
			if ($use_field) {
				$db_filters['equals'][$parent_id_field] = $this->model->get_top_level_id();
			}
		} else {
			//OVERWRITTEN to not include default, uncategorized pages
			$not_in = array();
			$not_in[] = $this->model->get_default_id();
			$not_in[] = $this->model->get_uncategorized_id();
			$db_filters['not_in'] = array('_outer_cnd' => 'AND');
			$db_filters['not_in'][$pk_field] = $not_in;
		}

		if ( is_array($row_ids) ) {
			if ( count($row_ids) > 0 ) {
			//add filtered relations to db query params
				if ( empty($db_filters['in']) ) {
					$db_filters['in'] = array();
				}
				$db_filters['in'][$pk_field] = $row_ids;
			} else {
			//relations fail filtering for common rows
				$has_rows = false;
			}
		}

		//OVERWRITTEN to include parent_id, is_permanent and url params
        $fields = array($pk_field);
		$ff = parent::get_form_fields();
		foreach ($ff as $field) {
			if ( $field->get_data('is_list_col') ) {
				$fields[] = $field->get_data('name');
			}
		}
		$where = array(
			'fields' => $fields,
			'where'  => $db_filters
		);
		$row_count = $has_rows ? $this->model->get_rows( ($where + array('count' => true) ) ) : 0;
		
		//table columns, filter params, bulk update params
		$list_params = array();
		$list_params['columns'] = $List->columns();
		$list_params['query_params'] = $List->filter_query_params($filters);
		$list_params['bulk_update'] = $List->bulk_update_params();
		
		//pagination parameters
		$page = $List->pagination_params();
		foreach ($page as $key => $value) {
			if ( ! empty($get[$key]) ) {
				if ($key === 'sort_by') {
					$val = $get[$key];
					if ($val === $pk_field || array_key_exists($val, $fields) ) {
						$page[$key] = $val;
					}
				} else {
					$page[$key] = is_numeric($get[$key]) ? (int) $get[$key] : $get[$key];
				}
			}
		}
		
		//OVERWRITTEN to remove pagination from initial list page
		$page['per_page'] = $has_filters ? self::$ITEMS_PER_PAGE : (int) $row_count;
		
		$page['total_entries'] = (int) $row_count;
		$page['total_pages'] = empty($row_count) || empty($page['per_page']) ? 
							   1 : 
							   ceil($row_count / $page['per_page']);
		$list_params['state'] = $page;
	
		//db query
		$items = array();
		if ($has_rows) {
			$query = $where;
			$query['_condition'] = 'AND';
			$query['order_by'] = 'short_title';
			$query['is_asc'] = true;
			$query['limit'] = $page['per_page'];
			$query['offset'] = ($page['page'] - 1) * $page['per_page'];
			$items = $this->model->get_rows($query);
			$items = $List->row_boolean_vals($items);
		}
		
		//OVERWRITTEN to sort pages in alpha order
		//
		$this->pages_sort($items);
		
		$list_params['items'] = $items;
		return $list_params;
	}
	

	/**
	 * get_list_template
	 *
	 * Retrieves the module list page variables and template parameters loaded via AJAX.
	 * 
	 * @access public
	 * @param \App\User\Permission $permission The current CMS user Permission object
     * @param bool $is_archive True if list page is archived records page
	 * @return array The module variable and template parameters
	 * @see \App\Html\ListPage\AdminListPage::template() for return parameters
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
	 */
	public function get_list_template($permission, $is_archive) {
		$module = parent::get_module_data();
		$per_page = $this->App->config('admin_list_per_page');
		$web_base = WEB_BASE === '/' ? '' : WEB_BASE;
		$admin_segment = $this->App->config('admin_uri_segment');
		$pages_frag = $admin_segment.'/'.$module['slug'];
        $pages_api_url = $web_base.'/'.API_DIR.'/'.$admin_segment.'/'.$module['slug'];
		$template = parent::get_list_template($permission, false);
		
		$cookie = $this->App->config('session_cms_cookie');
		$session =  $this->App->session($cookie);
		$params = $session->get_data($this->session_name);

		$template['alt_list_tmpl'] = $this->App->load_view('admin/AdminList_pages');
		
		$template['alt_list_tmpl_data'] = array(
			'max_depth'			=> $this->App->config('pages_max_depth'),
			'has_add' 			=> $permission->has_add(),
			'has_update' 		=> $permission->has_update(),
			'has_delete' 		=> $permission->has_delete(),
			'add_new_frag'		=> $pages_frag.'/add/',
			'edit_frag'			=> $pages_frag.'/edit/',
			'arrange_frag'		=> $pages_frag.'/sort/parent_id/',
			'subpages_url'		=> $pages_api_url.'/subpages',
			'web_base'			=> $web_base,
			'uncategorized_id'	=> $this->model->get_uncategorized_id(),
			'default_id'		=> $this->model->get_default_id(),
			'parent_show_id'	=> empty($params['parent_show_id']) ? 0 : $params['parent_show_id'],
			'stop_id'			=> empty($params['stop_id']) ? 0 : $params['stop_id'],
			'li_template' 		=> $this->App->load_view('admin/AdminList_pages_li')
		);
		
		$template['scripts'] = array(
			'js' 	=> array(
				'src' => array('abstract/jquery.mobile.admin.pages.js')
			),
			'css'	=> array('plugins/abstract/jquery.mobile.admin.pages.css')
		);
		$template['no_cache'] = true;
		
		unset($params['parent_show_id']);
		unset($params['stop_id']);
		$session->set_data($this->session_name, $params);
		
		return $template;
	}


    /**
     * is_page
     *
     * Checks if a page exists by given slug.
     *
     * @access public
     * @param string The page slug.
     * @return boolean True if page exists.
     */
    public function is_page($slug) {
        if ( empty($slug) ) {
            return false;
        }

        $page = $this->model->get($slug, true, false);
        return ! empty($page);
    }


    /**
     * set_sort
     *
     * Overwrites parent class method. Saves the sorted rows of a pages by page parent id.
     *
     * @access public
     * @param array $ids The array of module IDs in sorted order
     * @param string $field_name The field name of module relation, if sorted by relation
     * @param int $relation_id The independent module relation id, if sorted by relation
     * @return mixed True if operation successful OR an array of errors in format
     * array( 'errors' => (array) $errors) if sort update unsuccessful
     */
    public function set_sort($ids, $field_name='', $relation_id=0) {
        if ( empty($this->module['use_sort']) ) {
            //module not configured for sorting
            return false;
        }

        $errors = array();
        $has_saved = $this->model->set_sort_order($ids);

        if ( ! $has_saved) {
            $msg_part = '';
            if ( ! empty($field_name) ) {
                $msg_part .= __('relation').'['.$field_name.']';
            }
            if ( ! empty($relation_id) ) {
                $msg_part .= '[ID: '.$relation_id.']';
            }
            $msg_part .= ' ID: '.(is_array($ids) ? implode(', ', $ids) : $ids);
            $errors[] = error_str('error.sort.save', $msg_part);
        }

        return empty($errors) ? true : array('errors' => $errors);
    }
	
	
	/**
	 * update
	 *
	 * Updates a module table row from an assoc array of fields => values.
	 *
	 * @access public
	 * @param array $data The fields and corresponding values to update
	 * @return mixed True if operation successful OR an array of errors in format 
	 * array( 'errors' => (array) $errors) if update unsuccessful
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
	 */
	public function update($data) {
		$return = parent::update($data);
		
		if ( ! empty($data['page_id']) ) {
			$cookie = $this->App->config('session_cms_cookie');
			$session =  $this->App->session($cookie);
			$params = $session->get_data($this->session_name);
			if ( empty($params) ) {
				$params = array();
			}
			
			$page_id = $data['page_id'];
			$top_level_id = $this->model->get_top_level_parent_id($page_id);
			$params['parent_show_id'] = $top_level_id;
			$params['stop_id'] = $page_id;
			$session->set_data($this->session_name, $params);
		}
		
		return $return;
	}
	
	
	/**
	 * module_form_data_to_row
	 *
	 * Converts CMS form data to an assoc array to INSERT/UPDATE a module table row. Removes
	 * any indexes that are not a module field and adds any fields with a default value if
	 * missing. Overwritten from parent class.
	 * 
	 * @access protected
	 * @param array $data The form field data from CMS form
	 * @return array The data converted to an assoc array for form_fields table row
	 */
	protected function module_form_data_to_row($data) {
		if ( empty($data) ) {
			return $data;
		}
		
		if ( empty( trim($data['url']) ) ) {
		//since url field used to generate slug and may be empty
		//so, if empty then use title field
			$module = parent::get_module_data();
			$title_field = $module['title_field'];
			$data['url'] = $data[$title_field];
		}

		if ( ! empty($data['module_id_list']) ) {
            $data['module_id_form'] = 0;
        } else if ( ! empty($data['module_id_form']) ) {
            $data['module_id_list'] = 0;
        }
		
		return $data;
	}


    /**
     * pages_sort
     *
     * Sorts an array of page items by short_title field.
     *
     * @access protected
     * @param array &$list The array of page items
     * @return array The array of page items sorted by short_title field
     */
	protected function pages_sort(&$list) {
		if ( empty($list) || is_array($list) === false ) {
			return $list;
		}
		
		$list = $this->model->sort_alpha($list);
		foreach ($list as &$item) {
			if ( ! empty($item['subpages']) ) {
				$item['subpages'] = $this->pages_sort($item['subpages']);
			}
		}
		
		return $list;
	}
	
	
	/**
	 * row_to_module_form_data
	 *
	 * Converts a module table row into an assoc array of data used for the CMS form 
	 * to add or update a module object. Overwritten from parent class.
	 * 
	 * @access protected
	 * @param array $data A module row
	 * @return array The row converted to CMS form data
	 */
	protected function row_to_module_form_data($data) {
		if ( empty($data) ) {
			return $data;
		}
		
		return $data;
	}
	
	
	/**
	 * validate
	 *
	 * Checks the given assoc array of form data for necessary indeces. Overwritten from parent class.
	 * 
	 * @access protected
	 * @param array $data The form field form data as an assoc array
	 * @param boolean $has_id True if $data param contains a non-empty id for the form field row
	 * @return mixed True if row data validated or an array of validation errors
	 */
	protected function validate($data, $has_id=false) {
		$errors = array();
		return empty($errors) ? true : $errors;
	}
	
}

/* End of file Module_pages.php */
/* Location: ./App/Module/Module_pages.php */