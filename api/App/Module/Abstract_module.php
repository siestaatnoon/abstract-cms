<?php

namespace App\Module;

use 
App\App,
App\Html\Form\AdminSortForm,
App\Html\Form\Form,
App\Html\Form\Field\Form_field,
App\Html\ListPage\ListPage,
App\Html\ListPage\AdminListPage,
App\Model\Model,
App\Model\Options,
App\Model\Relation,
App\Exception\AppException;

/**
 * Abstract_module class
 * 
 * An abstract which provides functions to add, edit and delete CMS modules rows in addition
 * to loading the module classes and any corresponding relations. Also provides functions to 
 * populate the CMS module form and list pages, and retrieves data for frontend data population 
 * of these module pages.<br/><br/>
 * 
 * Subclasses should be defined to provide custom definitions for validation, serializing and
 * converting CMS form data to database row data and vce versa.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Module
 */
abstract class Abstract_module {
	
    /**
     * @var array Cache of instances of \App\Module\Module, 
     * \App\Model\Model and \App\Model\Model so class instances
     * can be reused insead of reinstantiated
     */
	protected static $INSTANCES = array(
		'modules' 	=> array(),
		'models' 	=> array(),
		'options' => array(),
		'relations' => array()
	);
	
    /**
     * @var array Storage of \App\Module\Module for all modules
     */
	protected static $MODULES;
	
    /**
     * @var \App\Model\Model Model for all modules
     */
	protected static $MODULES_MODEL;

    /**
     * @var \App\App Reference to application instance
     */
	protected $App;
	
    /**
     * @var bool True if module definition is the "modules" module
     */
	protected $is_main_module;
	
    /**
     * @var \App\Model\Model The module database model
     */
	protected $model;
	
    /**
     * @var array Associative array of module configuration
     */
	protected $module;
	
    /**
     * @var string The module name (slug)
     */
	protected $module_name;
	
    /**
     * @var \App\Model\Options The module options model
     */
	protected $options;
	
    /**
     * @var string The cookie name holding list page data
     */
	protected $session_name;

    /**
     * @var array Parameters for module sort form, set by subclasses
     */
    protected $sort_params;
	
	
	/**
	 * Constructor
	 *
	 * Initializes the Module with a given module name.
	 * 
	 * @access public
	 * @param string $module_name The module name
	 * @throws \App\Exception\AppException if modules configuration
	 * in ./App/Config/modules.php contains invalid or missing data
	 */
	public function __construct($module_name='modules') {
		$this->App = App::get_instance();
		$this->App->load_util('form_jqm');
		$this->module_name = $module_name;
		
		$modules_config = $this->App->load_config('modules');
		if (empty($modules_config) || 
			empty($modules_config['modules']) || 
			empty($modules_config['modules_fields']) || 
			empty($modules_config['modules']['field_data']) || 
			empty($modules_config['modules']['field_data']['model']) || 
			empty($modules_config['modules']['field_data']['relations']) ) {
            $error = error_str('error.param.invalid', array('./App/Config/modules.php') );
			throw new AppException($error, AppException::ERROR_FATAL);
		}
		
		//Set the module model for this class
		$module_name = $modules_config['modules']['name'];
		$config = array(
			'module' 		=> $module_name,
			'fields' 		=> $modules_config['modules']['field_data']['model'],
			'pk_field' 		=> $modules_config['modules']['pk_field'],
			'title_field' 	=> $modules_config['modules']['title_field'],
			'slug_field' 	=> $modules_config['modules']['slug_field'],
            'use_active' 	=> $modules_config['modules']['use_active'],
            'use_archive' 	=> $modules_config['modules']['use_archive'],
			'use_sort' 		=> $modules_config['modules']['use_sort']
		);
		$model = $this->load_model($config);
		if ( is_array($model) && key($model) === 'errors') {
		    $args = array( ': '.implode("\n", $model['errors']) );
            $message = error_str('error.general.multi', $args);
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		self::$MODULES_MODEL = $model;
		self::$INSTANCES['models'][$module_name] = $model;
		
		//retrieve row data for all modules and populate the static var holding these
		$this->load_modules();

		//load the module for this class
		$this->load_module($this->module_name);

		$this->is_main_module = $this->module_name === 'modules';
		$this->session_name = $this->module_name.'_list';
        $this->sort_params = array();

		return $this;
	}
	
	
	/**
	 * add
	 *
	 * Inserts a module table row from an assoc array of fields => values.
	 *
	 * @access public
	 * @param array $data The fields and corresponding values to insert
	 * @return mixed The primary key of the inserted row OR an array of errors in format 
	 * array( 'errors' => (array) $errors) if insert unsuccessful OR an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
     * @throws AppException if an error occurs retrieving relational data
	 */
	public function add($data) {
		if ( empty($data) || ! $this->module['use_model'] ) {
		//no data to add or module uses options model instead
			return false;
		} else if (($result = $this->validate($data)) !== true) {
			//validate data
			return array('errors' => $result);
		}
		
		$errors = array();
		
		//format data for INSERT
		$row = $this->module_form_data_to_row($data);
		
		//add the module row
		$id = $this->model->insert($row);

		if ( empty($id) ) {
			$title_field = $this->model->get_property('title_field');
            $errors[] = error_str('error.sql.insert', array('"'.$row[$title_field].'"'));
			return array('errors' => $errors);
		}
		
		//add relational rows
        try {
            $relations = $this->get_relations();
            $rel_config = $this->module['field_data']['relations'];
            foreach ($relations as $field_name => $rel) {
                $module_name = empty($rel_config[$field_name]['module']) ? '' : $rel_config[$field_name]['module'];
                if (!empty($field_name) && !empty($row[$field_name])) {
                    $indep_ids = array();
                    $rel_data = $row[$field_name];
                    $rel_type = $rel->get_property('relation_type');
                    $is_1toN = $rel_type === $rel::RELATION_TYPE_1N;
                    $rel_module = $is_1toN ? Module::load($module_name) : NULL;

                    //set args to pass into relation add
                    $args = isset($row[$field_name.'_args']) ? $row[$field_name.'_args'] : false;

                    if (is_array($rel_data)) {
                        if ($is_1toN) {
                            //relation 1:n, indep object data
                            foreach ($rel_data as $rd) {
                                if (($result = $rel_module->validate($rd)) !== true) {
                                    //validate relational data
                                    $errors = array_merge($errors, $result);
                                    continue;
                                }
                                $indep_id = $rel_module->add($rd);
                                if (is_array($indep_id)) {
                                    $errors = array_merge($errors, $indep_id['errors']);
                                    continue;
                                }
                                $indep_ids[] = $indep_id;
                            }
                        } else {
                            //relation n:n, array of indep ids
                            $indep_ids = $rel_data;
                        }
                    } else {
                        //relation n:1, so single id
                        $indep_ids[] = $rel_data;
                    }

                    if ($rel->add($id, $indep_ids, $field_name, $args) === false) {
                        $msg_part = __('relation').' ['.$this->module_name.'] ID: '.implode(', ', $indep_ids);
                        $message = error_str('error.sql.insert', array($msg_part) );
                        $errors[] = $message;
                    }
                }
            }
        } catch (AppException $e) {
		    throw $e;
        }
		
		//reset the static modules list of this class
		if ( $this->is_main_module && empty($errors) ) {
			$this->load_modules();
		}

		return empty($errors) ? $id : array('errors' => $errors);
	}


    /**
     * bulk_update
     *
     * Handles an update to multiple module rows, either setting active/inactive, archive/unarchive or deleting.
     *
     * @access public
     * @param string $task The bulk task to perform (active, inactive, archive, unarchive,
     * delete)
     * @param array $ids The array of row IDs to update
     * @return mixed TRUE if update successful, FALSE if either parameter(s) invalid OR an array of
     * errors in format array( 'errors' => (array) $errors) if insert unsuccessful
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
     */
    public function bulk_update($task, $ids) {
        if ( empty($task) || empty($ids) || empty($this->module['use_model']) ) {
        //task not defined, ID array empty or module is options type
            return false;
        }

        $errors = array();
        switch($task) {
            case 'active':
                if ( $this->model->set_active($ids, true) === false ) {
                    $param = ' '.error_str('error.while.active');
                    $errors[] = error_str('error.general.single', array($param));
                }
                break;
            case 'inactive':
                if ( $this->model->set_active($ids, false) === false ) {
                    $param = ' '.error_str('error.while.inactive');
                    $errors[] = error_str('error.general.single', array($param));
                }
                break;
            case 'archive':
                if ( $this->model->set_archive($ids, true) === false ) {
                    $param = ' '.error_str('error.while.archive');
                    $errors[] = error_str('error.general.single', array($param));
                }
                break;
            case 'unarchive':
                if ( $this->model->set_archive($ids, false) === false ) {
                    $param = ' '.error_str('error.while.unarchive');
                    $errors[] = error_str('error.general.single', array($param));
                }
                break;
            case 'delete':
                $result = $this->delete($ids);
                if ( is_array($result) ) {
                    $errors = $result;
                } else if ($result === false) {
                    $param = ' '.error_str('error.while.delete');
                    $errors[] = error_str('error.general.single', array($param));
                }
                break;
            default:
                return false;
        }


        return empty($errors) ? true : (isset($errors['errors']) ? $errors : array('errors' => $errors) );
    }
	
	
	/**
	 * delete
	 *
	 * Deletes a module table row and corresponding relational rows given 
	 * the id or array of ids.
	 *
	 * @access public
	 * @param mixed $mixed The row id or array of ids to delete
	 * @return mixed True if delete successful OR an array of errors if delete 
	 * unsuccessful OR false if empty $mixed parameter
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
     */
	public function delete($mixed) {
		if ( empty($mixed) || ! $this->module['use_model'] || ! $this->module['use_delete'] ) {
		//no data to update or module uses options model, or delete not active for module
			return false;
		}
		
		$ids = is_numeric($mixed) ? array($mixed) : $mixed;
		$errors = array();
		
		//delete relational rows
		$relations = $this->get_relations();
        $rel_config = $this->module['field_data']['relations'];
		foreach ($relations as $field_name => $rel) {
            $module = empty($rel_config[$field_name]['module']) ? '' : $rel_config[$field_name]['module'];
            $rel_module = Module::load($module);
			$relation_type = $rel->get_property('relation_type');
			$indep_model = $rel->get_property('indep_model');
			foreach ($ids as $id) {
				$rel_ids = $rel->get_ids($id, $field_name);

                // delete relational files
                $rel_module->delete_files($rel_ids);

				if ($relation_type === $rel::RELATION_TYPE_1N) {
				//delete relation table rows if type 1:n
					if ( $indep_model->delete($rel_ids) === false ) {
                        $msg_part = __('module').' ['.$this->module_name.' ID: '.$id.'], ';
                        $msg_part .= __('relation').' ['.$module.'] ID: '.implode(', ', $rel_ids);
						$errors[] = error_str('error.sql.delete', array($msg_part) );
					}
				}
				
				if ( $rel->delete($id, $rel_ids, $field_name) === false ) {
				//delete relational rows
                    $msg_part = __('module').' ['.$this->module_name.' ID: '.$id.'], ';
                    $msg_part .= __('relation').' ['.$module.'] ID: '.implode(', ', $rel_ids);
                    $errors[] = error_str('error.sql.delete', array($msg_part) );
				}
			}
		}

        // delete associated files
        $this->delete_files($ids);
		
		//delete the module row
		if ( $this->model->delete($ids) === false ) {
            $errors[] = error_str('error.sql.delete', array('ID: '.implode(', ', $ids) ) );
		}
		
		//reset the static modules list of this class
		if ( $this->is_main_module && empty($errors) ) {
			$this->load_modules();
		}

		return empty($errors) ? true : array('errors' => $errors);
	}


    /**
     * get_all_modules_data
     *
     * Returns the module row data of all modules indexed by module slug.
     *
     * @access public
     * @return array The assoc array of module row data indexed by slug
     * @see \App\Module\Module subclass for module definitions
     */
    public function get_all_modules_data() {
        return self::$MODULES;
    }


	/**
	 * get_cms_form
	 *
	 * Generates the admin form for this module. The form returned will be read only if
     * the permission is insufficient to edit the form.
	 *
	 * @access public
	 * @param int $row_id The module row id
     * @param \App\User\Permission $permission The current logged in user permission object
	 * @return array Assoc array of the admin form data
     * @throws \App\Exception\AppException if $permission invalid object, handled by \App\App class
	 */
	public function get_cms_form($row_id, $permission) {
		if ($permission instanceof Permission === false ) {
			$msg_part = error_str('error.param.type', array('$permission', '\\App\\User\\Permission') );
            $message = error_str('error.type.param.invalid', array($msg_part) );
			throw new AppException($message, AppException::ERROR_FATAL);
		}

		$label = $this->module['label'];
		$title = 'New '.$label.' Item';
		$form_config = array();

		$model = array();
		if ( ! empty($row_id) && ($d = $this->get_data($row_id, false, true)) !== false ) {
			$model = Form::form_field_values($this->module_name, $d);
			$title = 'Update '.$label.': '.$d[ $this->module['title_field'] ];
		} else if ( ! $this->module['use_model'] && ($d = $this->get_options()) !== false ) {
			$model = $d;
			$title = 'Update '.$label;
		}
		if ( ! empty($model) ) {
			$form_config['model'] = $model;
		}

		$is_readonly = (empty($row_id) && $permission->has_add() === false) ||
					   ( ! empty($row_id) && $permission->has_update() === false);

		$form_fields = $this->get_form_fields();
		$config = array(
			'module_name' 	=> $this->module_name,
			'fields' 		=> $form_fields,
			'title'	 		=> $title,
			'is_cms' 		=> true,
			'use_delete'	=> ! empty($model) && $permission->has_delete(),
			'is_readonly' 	=> $is_readonly
		);

		$Form = new Form($config);
		$form_data = $Form->generate();

		$scripts = array(
			'js' => array(
				'src' 		=> $form_data['js_includes'],
				'onload' 	=> $form_data['js_load_block'],
				'unload'  => $form_data['js_unload_block'],
			),
			'css' => $form_data['css_includes']
		);

		$form_config['pk_field'] = $this->module['pk_field'];
		$form_config['form_id'] = $form_data['form_id'];
		$form_config['fields'] = $form_data['fields'];
		$form_config['scripts'] = $scripts;
		$form_config['template'] = $form_data['form'];
		if ( ! empty($form_data['subforms']) ) {
			$arr = array();
			foreach ($form_data['subforms'] as $subform) {
				$arr[] = array(
					'selector' => $this->App->config('page_content_id'),
					'pos_func' => 'insertAfter',
					'html'	   => $subform['html']
				);
			}
			$form_config['blocks'] = $arr;
		}

		return $form_config;
	}


    /**
     * get_cms_list
     *
     * Generates the admin list data for this module. User permission is necessary to determine
     * if able to edit or delete a module row item.
     *
     * @access public
     * @param array $get Assoc array of the query parameters for the list of rows
     * @param bool $is_archive True if list of rows returned are marked as archived
     * @param \App\User\Permission $permission The current logged in user permission object
     * @return array Assoc array of the admin list data
     * @throws \App\Exception\AppException if $permission invalid object, handled by \App\App class
     */
    public function get_cms_list($get, $is_archive, $permission) {
        $List = new AdminListPage($this->module_name, $is_archive, $permission);
        return $this->list_data($List, $get, $is_archive, true);
    }


    /**
     * get_cms_sort_form
     *
     * Generates the admin sort form for this module. The form returned will be read only if
     * the permission is insufficient to edit the form.
     *
     * @access public
     * @param string $field_name The relation field name if sorting by relational field
     * @param int $relation_id The relational row ID if sorting by relational field
     * @param \App\User\Permission $permission The current logged in user permission object
     * @return array Assoc array of module sort form data
     * @throws \App\Exception\AppException if $permission invalid object, handled by \App\App class
     */
    public function get_cms_sort_form($field_name, $relation_id, $permission) {
        $error = '';
        if ($permission instanceof \App\User\Permission === false ) {
            $msg_part = error_str('error.param.type', array('$permission', '\\App\\User\\Permission') );
            $error = error_str('error.type.param.invalid', array($msg_part) );
        } else if ( $this->has_sort() === false ) {
            $error = error_str('error.sort.activate', array($this->module_name) );
        }
        if ( ! empty($error) ) {
            throw new AppException($error, AppException::ERROR_FATAL);
        }

        $title = 'Arrange '.$this->module['label_plural'];
        $is_readonly = $permission->has_update() === false;
        $form_config = array();

        $model = false;
        $ids = array();
        if ( ! empty($field_name) ) {
            $model = array();
            $items = array();
            $pk_field = $this->module['pk_field'];
            $title_field = $this->module['title_field'];
            $relations = empty($this->module['field_data']['relations']) ? array() : $this->module['field_data']['relations'];
            $uploads = empty($this->module['field_data']['uploads']) ? array() : $this->module['field_data']['uploads'];
            $is_all = $field_name === AdminSortForm::ALL_SEGMENT;

            if ($is_all || empty($relations[$field_name]) ) {
                $params = array(
                    'order_by' => 'sort_order',
                    'is_asc'   => true
                );
                if ( ! $is_all && ! empty($relation_id) ) {
                    $params['where'] = array(
                        'equals' => array($field_name => $relation_id)
                    );
                }
                $items = $this->model->get_rows($params);
            } else if ( ! empty($relation_id) ) {
                $relation = $this->get_relations($field_name);
                $type = $relation->get_property('relation_type');
                if ($type === Relation::RELATION_TYPE_1N) {
                    $module = $relation->get_property('module');
                    $pk_field = $module['pk_field'];
                    $title_field = $module['title_field'];
                    $uploads = empty($module['field_data']['uploads']) ? array() : $module['field_data']['uploads'];
                    $items = $relation->get_indep($relation_id, $field_name);
                } else {
                    $items = $relation->get_dep($relation_id, $field_name);
                }
            }

            $img_path = false;
            $img_field = false;
            if ( ! empty($uploads) ) {
                foreach ($uploads as $f => $cfg) {
                    if ( ! empty($cfg['is_image']) ) {
                        $upload_cfg = $this->App->upload_config($cfg['config_name'], $cfg['is_image']);
                        if ( ! empty($upload_cfg) ) {
                            $img_path = $upload_cfg['upload_path'].'/';
                            $img_field = $f;
                            break;
                        }
                    }
                }
            }
            foreach ($items as $item) {
                $id = $item[$pk_field];
                $image = empty($img_path) || empty($item[$img_field]) ? '' :
                         $img_path.(is_array($item[$img_field]) ? $item[$img_field][0] : $item[$img_field]);
                $model[] = array(
                    'id'    => $id,
                    'name'  => $item[$title_field],
                    'image' => $image
                );
                $ids[] = (int) $id;
            }
        }
        $form_config['model'] = array(
            'ids'   => json_encode($ids),
            'items' => $model
        );

        $config = array(
            'module_name' 	=> $this->module_name,
            'title'	 		=> $title,
            'field_name' 	=> $field_name,
            'relation_id' 	=> $relation_id,
            'is_readonly' 	=> $is_readonly,
            'params' 	    => $this->sort_params
        );
        $Form = new AdminSortForm($config);
        $form_data = $Form->generate();

        $scripts = array(
            'js' => array(
                'src' 		=> $form_data['js_includes'],
                'onload' 	=> $form_data['js_load_block'],
                'unload'    => $form_data['js_unload_block']
            ),
            'css' => $form_data['css_includes']
        );

        $form_config['pk_field'] = '';
        $form_config['form_id'] = $form_data['form_id'];
        $form_config['fields'] = $form_data['fields'];
        $form_config['scripts'] = $scripts;
        $form_config['template'] = $form_data['form'];

        return $form_config;
    }
	

	/**
	 * get_data
	 *
	 * Retrieves a module row along with relations, from a given id or slug, and optionally
	 * formats it for use in a CMS form.
	 *
	 * @access public
	 * @param mixed $mixed The row id or slug
	 * @param bool $is_slug True to use slug to retrieve row, defaults to false
	 * @param bool $is_cms_form True to convert data for CMS module form
	 * @return mixed The associative array for the row data OR false if row not found
	 * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
	 */
	public function get_data($mixed, $is_slug=false, $is_cms_form=false) {
		if ( ! $this->module['use_model']) {
		//if module uses options table instead of model, can't retrieve a row
			return false;
		}
		
		$row = $this->model->get($mixed, $is_slug);
		if ( empty($row) ) {
			return false;
		}
		
		//convert table row to form data
		if ($is_cms_form) {
			$row = $this->row_to_module_form_data($row);
		}
		
		$pk_field = $this->module['pk_field'];
		$module_id = $row[$pk_field];
		$relations = $this->get_relations();
        $rel_config = $this->module['field_data']['relations'];
		foreach ($relations as $field_name => $rel) {
            $module_name = empty($rel_config[$field_name]['module']) ? '' : $rel_config[$field_name]['module'];
			$rel_type = $rel->get_property('relation_type');
			$data = false;
			switch ($rel_type) {
				case Relation::RELATION_TYPE_1N :
					$module = Module::load($module_name);
					$data = $rel->get($module_id, false, $field_name);
					if ($is_cms_form) {
						foreach ($data as &$r) {
							$r = $module->row_to_module_form_data($r);
						}
					}
					break;
				default :
					$data = $rel->get_ids($module_id, $field_name);
					break;
			}
			$row[$field_name] = $data;
		}

		return $row;
	}


    /**
     * get_default_field_values
     *
     * Returns an assoc array of fields and default field values, parsed according to field type, for a form.
     *
     * @access public
     * @param array $params Array of parameters passed in and used by subclass method overwrites
     * @return array The assoc array of default form fields and values
     */
	public function get_default_field_values($params=array()) {
		$defaults = $this->module['field_data']['defaults'];
		return Form::form_field_values($this->module_name, $defaults);
	}


    /**
     * get_field_values
     *
     * Returns an assoc array of fields and field values of a model row, parsed according to field type, for a form.
     *
     * @access public
     * @param int $id The id of the model row
     * @param array $params Array of parameters passed in and used by subclass method overwrites
     * @return array The assoc array of form fields and values
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
     */
	public function get_field_values($id, $params=array()) {
		$data = $this->get_data($id, false, true);
		return Form::form_field_values($this->module_name, $data);
	}


	/**
	 * get_form_fields
	 *
	 * Retrieves the form field objects corresponding to the admin module form of the subclassed
	 * \App\Module\Module.
	 *
	 * @access public
	 * @return array The array of \App\Form\Field\Form_field fields
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
     * @see \App\Module\Module subclass for module definitions
	 */
	public function get_form_fields() {
		$module = Module::load();
		$module_id = $this->module['id'];
        $pk_field = $this->module['pk_field'];
		$data = $module->get_data($module_id, false, false);
		$fields = $data['form_fields'];
		$ff= array();
	
		foreach ($fields as $field) {
			$ff[] = new Form_field($field);
		}

        if ( ! empty($this->module['use_archive']) ) {
            $cfg = array(
                'name'          => Model::MODEL_ARCHIVE_FIELD,
                'label'         => $this->App->lang('form.is_archive'),
                'module'        => $this->module_name,
                'pk_field'      => $pk_field,
                'default'       => '',
                'field_type'    => array(
                    'jqm_flipswitch' => array()
                )
            );
            $ff[] = new Form_field($cfg);
        }

        if ( ! empty($this->module['use_active']) ) {
            $cfg = array(
                'name'          => Model::MODEL_ACTIVE_FIELD,
                'label'         => $this->App->lang('form.is_active'),
                'module'        => $this->module_name,
                'pk_field'      => $pk_field,
                'default'       => '',
                'field_type'    => array(
                    'jqm_flipswitch' => array()
                )
            );
            $ff[] = new Form_field($cfg);
        }
		
		return $ff;
	}


    /**
     * get_front_list
     *
     * Generates the frontend list data for this module.
     *
     * @access public
     * @param array $get Assoc array of the query parameters for the list of rows
     * @param bool $is_archive True if list of rows returned are marked as archived
     * @return array Assoc array of the frontend list data
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
     */
    public function get_front_list($get, $is_archive) {
        $List = new ListPage($this->module_name, $is_archive);
        return $this->list_data($List, $get, $is_archive, false);
    }
	

	/**
	 * get_list_template
	 *
	 * Retrieves the module admin list page variables and template parameters loaded via AJAX.
     * User permission is necessary to determine if able to edit or delete a module row item.
	 * 
	 * @access public
	 * @param \App\User\Permission $permission The current admin user Permission object
     * @param bool $is_archive True if list page is list of records marked as archived
	 * @return array The assoc array of module variable and template parameters
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
	 * @see \App\Html\ListPage\AdminListPage::template() for return parameters
	 */
	public function get_list_template($permission, $is_archive) {
		$List = new AdminListPage($this->module_name, $is_archive, $permission);
		return $List->template();
	}
	

	/**
	 * get_model
	 *
	 * Returns the model class corresponding to the module of this class.
	 *
	 * @access public
	 * @return \App\Model\Model The Model class for the module
	 * @see \App\Module\Module subclass for module definitions
	 * @see \App\Model\Model for model definitions
	 */
	public function get_model() {
		return $this->model;
	}
	

	/**
	 * get_model_fields
	 *
	 * Returns the model fields and data type params corresponding to the module of this class.
	 *
	 * @access public
	 * @return array The assoc array of model field data in format 
	 * array( [field name] => (array) [field params] )
	 * @see \App\Module\Module subclass for module definitions
	 */
	public function get_model_fields() {
		return $this->module['field_data']['model'];
	}
	

	/**
	 * get_module_data
	 *
	 * Returns the module row data corresponding to the given module name or this module
     * if $module_name parameter empty.
	 *
	 * @access public
	 * @param string $module_name Name (slug) of module, if empty then uses the module for this subclass
	 * @return array The assoc array of module row data or false if $module_name does not correspond to a module
	 * @see \App\Module\Module subclass for module definitions
	 */
	public function get_module_data($module_name='') {
		$data = false;
		if ( empty($module_name) ) {
			$data = $this->module;
		} else {
			$data = isset(self::$MODULES[$module_name]) ? self::$MODULES[$module_name] : false;
		}
		return $data;
	}
	
	
	/**
	 * get_options
	 *
	 * Retrieves the var => value options for this module.
	 *
	 * @access public
	 * @return mixed The associative array of options OR false if module uses a model table instead
	 */
	public function get_options() {
		//if module uses model table instead of options, 
		//can't retrieve option data
		return $this->module['use_model'] ? false : $this->options->get();
	}
	
	
	/**
	 * get_options_model
	 *
	 * Returns the options class corresponding to the module of the subclassed 
	 * \App\Module\Module.
	 *
	 * @access public
	 * @return \App\Model\Options The Model class for the module of NULL 
	 * if module uses model table
	 * @see \App\Module\Module subclass for module definitions
	 * @see \App\Model\Options for model definitions
	 */
	public function get_options_model() {
		return $this->options;
	}


    /**
     * get_pk_field
     *
     * Returns the primary key field of this module.
     *
     * @access public
     * @return string The primary key field
     */
    public function get_pk_field() {
        return $this->module['pk_field'];
    }
	

	/**
	 * get_relations
	 *
	 * Returns an array of instances of \App\Model\Relation for the module instance
	 * of this class and corresponding model. Caches the Relation classes to $INSTANCES[relations]
     * property or returns the cached value.
	 * 
	 * @access private
	 * @param string $field_name The field name to return its relation, returns all
	 * relations for this module if empty
	 * @return array The array of \App\Model\Relation instances indexed by module name
	 * @throws \App\Exception\AppException if relation does not exist or config parameters missing/invalid
	 * @see \App\Model\Relation.__construct() for relation configuration
	 */
	public function get_relations($field_name='') {
        $module_name = $this->module_name;
		$relations = array();
		$errors = array();

        if ( isset(self::$INSTANCES['relations'][$module_name]) ) {
        // cached relations found, return those
            if ( ! empty($field_name) && ! isset(self::$INSTANCES['relations'][$module_name][$field_name]) ) {
                $message = error_str('error.type.relation', array($field_name, $module_name));
                throw new AppException($message, AppException::ERROR_FATAL);
            }
            return empty($field_name) ?
                   self::$INSTANCES['relations'][$module_name] :
                   self::$INSTANCES['relations'][$module_name][$field_name];
        }

        $module = self::$MODULES[$module_name];
        $rel_config = $module['field_data']['relations'];

        foreach($rel_config as $f_name => $config) {
            $error_start = ucfirst( __('relation') ).' config['.$module_name.']['.$f_name.']';
            if ( empty($config['type']) ) {
                $msg_part = 'Relation::RELATION_TYPE_N1, Relation::RELATION_TYPE_1N, Relation::RELATION_TYPE_NN';
                $errors[] = error_str('error.param.value', array($error_start.'[type]', $msg_part));
            }
            if ( empty($config['module']) || ! isset(self::$MODULES[ $config['module'] ]) ) {
                $errors[] = error_str('error.param.relation', array($error_start.'[module]'));
            }
            if ( ! empty($errors) ) {
                break;
            }

            $rel_module_name = $config['module'];
            $rel_module = self::$MODULES[$rel_module_name];
            $indep_model = $this->load_model($rel_module_name);
            if ( is_array($indep_model) && key($indep_model) === 'errors') {
                $errors = $indep_model['errors'];
                break;
            }
						
            $r_config = array(
                'module'		=> $rel_module,
                'dep_model' 	=> $this->model,
                'indep_model' 	=> $indep_model,
                'relation_type' => $config['type']
            );
					
            $rel = false;
            $path = APP_PATH.DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR;
            $path .= 'Relation_'.$this->module_name.'_'.$rel_module_name.'.php';
            if ( file_exists($path) ) {
                $class = '\App\Model\Relation_'.$this->module_name.'_'.$rel_module_name;
                $rel = new $class($r_config);
            } else {
                $rel = new Relation($r_config);
            }

            $relations[$f_name] = $rel;
        }

        if ( ! empty($field_name) && ! isset($relations[$field_name]) ) {
            $message = error_str('error.type.relation', array($field_name, $module_name));
            throw new AppException($message, AppException::ERROR_FATAL);
        }
		
		if ( empty($errors) ) {
            self::$INSTANCES['relations'][$module_name] = $relations;
        } else {
			$msg_part = error_str('error.while.load.relation').' '.__('module').' ['.$module_name.']): ';
            $msg_part .= implode("\n", $errors);
            $message = error_str('error.general.multi', array($msg_part));
			throw new AppException($message, AppException::ERROR_FATAL);
		}

        return empty($field_name) ? $relations : $relations[$field_name];
	}


    /**
     * get_session_name
     *
     * Returns the session cookie name holding admin/frontend list page data.
     *
     * @access public
     * @return string The cookie name
     */
	public function get_session_name() {
		return $this->session_name;
	}


    /**
     * get_slug_field
     *
     * Returns the slug field of this module or false if module does not use an ID slug.
     *
     * @access public
     * @return mixed The slug field or false if slug not in use
     */
    public function get_slug_field() {
        return $this->has_slug() ? $this->module['slug_field'] : false;
    }
	

	/**
	 * get_model_fields
	 *
	 * Returns the assoc array of admin form validation params corresponding to the module
	 * of the subclassed \App\Module\Module.
	 *
	 * @access public
	 * @return array The assoc array of admin form validation params in format
	 * array( [field name] => (array) [validation params] )
	 * @see \App\Module\Module subclass for module definitions
	 */
	public function get_validation_params() {
	    $valid = $this->module['field_data']['validation'];
	    $defaults = $this->module['field_data']['defaults'];
	    foreach ($valid as $field => &$data) {
            $data['is_multiple'] = isset($defaults[$field]) && is_array($defaults[$field]);
        }
		return $valid;
	}


    /**
     * has_cms_form
     *
     * Returns true if instance of this module has use of admin form activated.
     *
     * @access public
     * @return bool True if admin form activated
     */
    public function has_cms_form() {
        return ! empty($this->module['use_cms_form']);
    }


    /**
     * has_frontend_form
     *
     * Returns true if instance of this module has use of frontend form activated.
     *
     * @access public
     * @return bool True if frontend form activated
     */
    public function has_frontend_form() {
        return ! empty($this->module['use_frontend_form']);
    }


    /**
     * has_frontend_list
     *
     * Returns true if instance of this module has use of frontend list page activated.
     *
     * @access public
     * @return bool True if frontend list activated
     */
    public function has_frontend_list() {
        return ! empty($this->module['use_frontend_list']);
    }


    /**
     * has_slug
     *
     * Returns true if instance of this module has slug for row identifier activated.
     *
     * @access public
     * @return bool True if slug use activated
     */
    public function has_slug() {
        return ! empty($this->module['use_slug']);
    }


    /**
     * has_sort
     *
     * Returns true if instance of this module has sorting activated.
     *
     * @access public
     * @return bool True if sorting activated
     */
    public function has_sort() {
        return ! empty($this->module['use_sort']);
    }


    /**
     * is_main_module
     *
     * Returns true if instance of the subclassed \App\Module\Module.
     *
     * @access public
     * @return bool True if \App\Module\Module subclass
     */
	public function is_main_module() {
	    return $this->is_main_module;
    }


    /**
     * is_active
     *
     * Returns true if module is active.
     *
     * @access public
     * @return bool True if module active
     */
    public function is_active() {
        return ! empty($this->module['is_active']);
    }


    /**
     * is_options
     *
     * Returns true if module is of options type.
     *
     * @access public
     * @return bool True if options type
     */
    public function is_options() {
        return empty($this->module['use_model']);
    }


    /**
     * set_sort
     *
     * Saves the sorted rows of a module.
     *
     * @access public
     * @param array $ids The array of module IDs in sorted order
     * @param string $field_name The field name of module relation, if sorted by relation
     * @param int $relation_id The independent module relation id, if sorted by relation
     * @return mixed True if operation successful OR an array of errors in format
     * array( 'errors' => (array) $errors) if sort update unsuccessful
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
     */
    public function set_sort($ids, $field_name='', $relation_id=0) {
        if ( empty($this->module['use_sort']) ) {
         //module not configured for sorting
            return false;
        }

        $errors = array();
        $has_saved = false;

        //add/update relational rows and relational data
        if ( ! empty($field_name) && ! empty($relation_id) ) {
            $relation = $this->get_relations($field_name);
            $type = $relation->get_property('relation_type');
            $has_saved = $type === Relation::RELATION_TYPE_1N ?
                         $relation->set_sort_order($relation_id, $ids, $field_name) :
                         $relation->set_sort_order($ids, $relation_id, $field_name);
        } else {
            $has_saved = $this->model->set_sort_order($ids);
        }

        if ( ! $has_saved) {
            $msg_part = '';
            if ( ! empty($field_name) ) {
                $msg_part .= __('relation').'['.$field_name.']';
            }
            if ( ! empty($relation_id) ) {
                $msg_part .= '[ID: '.$relation_id.']';
            }

            $msg_part .= ' ID: '.(is_array($ids) ? implode(', ', $ids) : $ids);
            $errors[] = error_str('error.sort.save', array($msg_part) );
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
	 * @return mixed True if operation successful, false $data parameter empty or this module is "options" type
     * OR an array of errors in format array( 'errors' => (array) $errors) if update unsuccessful
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
	 */
	public function update($data) {
		if ( empty($data) || ! $this->module['use_model'] ) {
		//no data to update or module uses options model instead
			return false;
		} else if (($result = $this->validate($data, true)) !== true) {
			//validate data
			return $result;
		}
		
		$errors = array();
		
		//format data for UPDATE
		$row = $this->module_form_data_to_row($data);
		$pk_field = $this->module['pk_field'];
		$module_id = $row[$pk_field];
		
		//update the module row
		if ( $this->model->update($row) === false ) {
            $errors[] = error_str('error.sql.update', array('ID: '.$module_id));
			return array('errors' => $errors);
		}
		
		//add/update relational rows and relational data
		$relations = $this->get_relations();
        $rel_config = $this->module['field_data']['relations'];
		foreach ($relations as $field_name => $rel) {
            $module_name = empty($rel_config[$field_name]['module']) ? '' : $rel_config[$field_name]['module'];
			$indep_ids = array();
			$new_indep_ids = array();
			$update_ids = array();
			$old_indep_ids = empty($rel) ? array() : $rel->get_ids($module_id, $field_name);
			if ( empty($old_indep_ids) ) {
				$old_indep_ids = array();
			} else if ( ! is_array($old_indep_ids) ) {
				$old_indep_ids = array($old_indep_ids);
			}

			if ( ! empty($field_name) && ! empty($row[$field_name]) ) {
				$rel_type = $rel->get_property('relation_type');
				$is_1toN = $rel_type === $rel::RELATION_TYPE_1N;
				$rel_module = $is_1toN ? Module::load($module_name) : NULL;
				$rel_data = $row[$field_name];

				if ($is_1toN) {
				//relation 1:n, indep object data

					foreach ($rel_data as $rd) {
						$indep_pk_field = $rel->get_property('indep_model')->get_property('pk_field');
						$has_pk = ! empty($rd[$indep_pk_field]);
						if (($result = $rel_module->validate($rd, $has_pk)) !== true) {
						//validate relational data
							$errors = array_merge($errors, $result);
							continue;
						}
						if ($has_pk) {
						//update the relational row
							if ( ($result = $rel_module->update($rd)) !== true) {
								$errors = array_merge($errors, $result['errors']);
								continue;
							}
							$indep_ids[] = $rd[$indep_pk_field];
						} else {
						//add the relational row and relational data
							$indep_id = $rel_module->add($rd);
							if ( is_array($indep_id) ) {
								$errors = array_merge($errors, $indep_id['errors']);
								continue;
							}
								
							$new_indep_ids[] = $indep_id;
							$indep_ids[] = $indep_id;
						}
					}

					//delete relational rows
					$diff = array_diff($old_indep_ids, $indep_ids);
					if ( ! empty($diff) ) {
					    // first delete associated files
                        $rel_module->delete_files($diff);
					    // then delete the rows
						$rel_module->delete($diff);
					}
				} else {
					if ( is_array($rel_data) ) { 
					//relation n:n, array of indep ids
						$indep_ids = $rel_data;
					} else {
					//relation 1:n, single indep id
						$indep_ids[] = $rel_data;
					}
					
					$new_indep_ids = array_diff($indep_ids, $old_indep_ids);
				}
			}
			
			//set args to pass into relation update
			$args = isset($row[$module_name.'_args']) ? $row[$module_name.'_args'] : false;
			
			//add the new relations
			if ( ! empty($new_indep_ids) && $rel->add($module_id, $new_indep_ids, $field_name, $args) === false) {
                $msg_part = __('relation').' ['.$field_name.'] ID: '.implode(', ', $new_indep_ids);
                $errors[] = error_str('error.sql.insert', array($msg_part) );
				continue;
			}
			
			//update relations, if args is not empty
			$diff = array_diff($new_indep_ids, $indep_ids);
			if ( ! empty($args) && ! empty($diff) && $rel->update($module_id, $diff, $field_name, $args) === false) {
                $msg_part = __('relation').' ['.$field_name.'] ID: '.implode(', ', $diff);
                $errors[] = error_str('error.sql.update', array($msg_part) );
				continue;
			}
			
			//delete old relations
			$diff = array_diff($old_indep_ids, $indep_ids);
			if ( ! empty($diff) &&  $rel->delete($module_id, $diff, $field_name) === false) {
                $msg_part = __('relation').' ['.$field_name.'] ID: '.implode(', ', $diff);
                $errors[] = error_str('error.sql.delete', array($msg_part) );
				continue;
			}

            //set sorting
            $diff = array_diff($indep_ids, $diff); //remove deleted relations
            if ( ! empty($diff) &&  $rel->set_sort_order($module_id, $diff, $field_name) === false) {
                $msg_part = __('relation').' ['.$field_name.'] ID: '.implode(', ', $diff);
                $errors[] = error_str('error.sort.save', array($msg_part) );
                continue;
            }
		}
	
		//reset the static modules list of this class
		if ( $this->is_main_module && empty($errors) ) {
			$this->load_modules();
		}
		
		return empty($errors) ? true : array('errors' => $errors);
	}
	
	
	/**
	 * update_options
	 *
	 * Updates the module fields in the options table from an assoc array of fields => values.
	 *
	 * @access public
	 * @param array $data The fields and corresponding values to update
	 * @return mixed True if operation successful OR an array of errors in format 
	 * array( 'errors' => (array) $errors) if update unsuccessful
	 */
	public function update_options($data) {
		if ( empty($data) || $this->module['use_model'] ) {
		//no options to update or module uses model table instead
			return false;
		} else if (($result = $this->validate($data, true)) !== true) {
			//validate data
			return $result;
		}
		
		$errors = array();
		
		//format data for UPDATE
		$fields = $this->module_form_data_to_row($data);

		//update the module row
		if ( $this->options->update($fields) === false ) {
            $msg_part = __('module').' ['.$this->module_name.']';
            $errors[] = error_str('error.sql.update', array($msg_part));
		}
		
		return empty($errors) ? true : array('errors' => $errors);
	}


    /**
     * delete_files
     *
     * Deletes files and images corresponding to module rows given by the $id parameter. Note that this function
     * can be safely called if no files exist in module rows or module does not utilize files.
     *
     * @access protected
     * @param mixed $mixed The row id or array of ids to delete corresponding files
     * @return void
     */
    protected function delete_files($mixed) {
        if ( empty($mixed) || empty($this->module['field_data']['uploads']) ) {
            return;
        }

        $uploads = $this->module['field_data']['uploads'];
        $ids = is_array($mixed) ? $mixed : array($mixed);
        $query_params = array(
            'where' => array(
                'in' => array(
                    $this->module['pk_field'] => $ids
                )
            )
        );
        $rows = $this->model->get_rows($query_params);

        foreach ($uploads as $field => $cfg) {
            $is_image = ! empty($cfg['is_image']);
            $upload_cfg = $this->App->upload_config($cfg['config_name'], $is_image);
            if ( ! empty($upload_cfg) ) {
                $filepath = DOC_ROOT.$upload_cfg['upload_path'].DIRECTORY_SEPARATOR;
                foreach ($rows as $row) {
                    if ( ! empty($row[$field]) ) {
                        $files = is_array($row[$field]) ? $row[$field] : array($row[$field]);
                        foreach ($files as $file) {
                            if ( @is_file($filepath.$file) ) {
                                unlink($filepath.$file);
                            }
                            if ($is_image && $upload_cfg['create_thumb']) {
                                // check and delete corresponding thumb if image
                                $ext_pos = strrpos($file, '.');
                                $ext = substr($file, $ext_pos);
                                $file = substr($file, 0, $ext_pos).$upload_cfg['thumb_ext'].$ext;

                                if ( @is_file($filepath.$file) ) {
                                    unlink($filepath.$file);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
	
	
	/**
	 * load_model
	 *
	 * Returns an instance of the module Model by first checking if a Model_[module name].php 
	 * subclass file exists or else creates an instance of the \App\Model\Model class.
	 * 
	 * @access protected
	 * @param mixed $mixed The model config array or module name
	 * @return mixed The \App\Model\Model model instance or array of errors if $mixed 
	 * param contains missing or invalid parameters
	 * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
	 */
	protected function load_model($mixed) {
		$errors = array();
		
		if ( is_string($mixed) === false ) {
			if ( empty($mixed['module']) ) {
                $msg_part = error_str('error.module.slug', array('$mixed[module]'));
				$errors[] = error_str('error.type.param.invalid', array($msg_part));
			}
			if ( empty($mixed['fields']) ) {
                $msg_part = error_str('error.module.form_fields', array('$mixed[fields]'));
                $errors[] = error_str('error.type.param.invalid', array($msg_part));
			}
			if ( empty($mixed['pk_field']) ) {
                $msg_part = error_str('error.module.pk_field', array('$mixed[pk_field]'));
                $errors[] = error_str('error.type.param.invalid', array($msg_part));
			}
			if ( empty($mixed['title_field']) ) {
                $msg_part = error_str('error.module.title_field', array('$mixed[title_field]'));
                $errors[] = error_str('error.type.param.invalid', array($msg_part));
			}
		} else if ( ! isset(self::$MODULES[$mixed]) ) {
            $msg_part = '$mixed '.error_str('error.param.module.missing', array($mixed));
            $errors[] = error_str('error.type.param.invalid', array($msg_part));
		}
		if ( ! empty($errors) ) {
			return array('errors' => $errors);
		}
		
		$is_module = is_string($mixed);
		$data = $is_module ? self::$MODULES[$mixed] : $mixed;
		$module_name = $is_module ? $data['name'] : $data['module'];
		if ( isset($data['use_model']) && ! $data['use_model']) {
			//module uses options model instead of model table, return empty array
			return array();
		}

		//check and return if instance of model already created
		if ( isset(self::$INSTANCES['models'][$module_name]) ) {
			return self::$INSTANCES['models'][$module_name];
		}

		$config = array(
			'module' 		=> $module_name,
			'fields' 		=> $is_module ? $data['field_data']['model'] : $data['fields'],
			'pk_field' 		=> $data['pk_field'],
			'title_field' 	=> $data['title_field'],
			'slug_field' 	=> $data['slug_field'],
            'use_active' 	=> $data['use_active'],
            'use_archive' 	=> $data['use_archive'],
			'use_sort' 		=> $data['use_sort']
		);
	
		$model = false;
		if ( file_exists(APP_PATH.DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Model_'.$module_name.'.php') ) {
			$class = '\App\Model\Model_'.$module_name;
			$model = new $class($config);
		} else {
			$model = new Model($config);
		}
		
		self::$INSTANCES['models'][$module_name] = $model;
		return $model;
	}
	
	
	/**
	 * load_options
	 *
	 * Returns an instance of the module Options by first checking if a Options_[module name].php 
	 * subclass file exists or else creates an instance of the \App\Model\Options class.
	 * 
	 * @access protected
	 * @param mixed $mixed The options config array or module name
	 * @return mixed The \App\Model\Options instance or array of errors if $mixed 
	 * param contains missing or invalid parameters
	 * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
	 */
	protected function load_options($mixed) {
		$errors = array();
		
		if ( is_string($mixed) === false ) {
			if ( empty($mixed['module']) ) {
                $msg_part = error_str('error.module.slug', array('$mixed[module]'));
                $errors[] = error_str('error.type.param.invalid', array($msg_part));
			}
			if ( empty($mixed['fields']) ) {
                $msg_part = error_str('error.module.form_fields', array('$mixed[fields]'));
                $errors[] = error_str('error.type.param.invalid', array($msg_part));
			}
		} else if ( ! isset(self::$MODULES[$mixed]) ) {
            $msg_part = '$mixed '.error_str('error.param.module.missing', array($mixed));
            $errors[] = error_str('error.type.param.invalid', array($msg_part));
		} 
		if ( ! empty($errors) ) {
			return array('errors' => $errors);
		}
		
		$is_module = is_string($mixed);
		$data = $is_module ? self::$MODULES[$mixed] : $mixed;
		$module_name = $is_module ? $data['name'] : $data['module'];
		if ( ! empty($data['use_model']) ) {
			//module uses a model table instead of options model, return empty array
			return array();
		}
		
		//check and return if instance of model already created
		if ( isset(self::$INSTANCES['options'][$module_name]) ) {
			return self::$INSTANCES['options'][$module_name];
		}
		
		$options = false;
		$config = array(
			'module' => $module_name,
			'fields' => $is_module ? $data['field_data']['model'] : $data['fields']
		);
		if ( file_exists(APP_PATH.DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Options_'.$module_name.'.php') ) {
			$class = '\App\Model\Options_'.$module_name;
			$options = new $class($config);
		} else {
			$options = new Options($config);
		}
		
		self::$INSTANCES['options'][$module_name] = $options;
		return $options;
	}
	
	
	/**
	 * module_form_data_to_row
	 *
	 * This function is to be overridden by a module subclass which converts
	 * form data to database data specific to that module's CMS form. This function 
	 * is called prior to Module::create() or Module::modify() to add or modify a module.
	 * 
	 * @access protected
	 * @param array $data The CMS form data
	 * @return array The CMS form data modified for row INSERT
	 */
	abstract protected function module_form_data_to_row($data);
	
	
	/**
	 * row_to_module_form_data
	 *
	 * This function is to be overridden by a module subclass which converts
	 * table row data to CMS module form data specific to that module. This function 
	 * is called after a SELECT to the model database.
	 * 
	 * @access protected
	 * @param array $row A module row
	 * @return array The module row modified for CMS form data
	 */
	abstract protected function row_to_module_form_data($row);
	
	
	/**
	 * safe_module_name
	 *
	 * Converts a module name to a lowercase string non-alphabetic chars
	 * stripped (underscore not included), if ending with an underscore, 
	 * strips that. 
	 * 
	 * @access protected
	 * @param string $name The module name
	 * @return mixed The converted module name or false if string is empty
	 */
	protected function safe_module_name($name) {
		if ( empty($name) ) {
			return $name;
		}
		$name = preg_replace('/\W+/', '', strtolower( trim($name) ) );
		return rtrim($name, '_');
	}
	
	
	/**
	 * validate
	 *
	 * Subclass implementation validates the given assoc array of form data.
	 * 
	 * @access protected
	 * @param array $data The module row data as an assoc array
	 * @param bool $has_id True if $data param contains a non-empty id for the module row
	 * @return mixed True if row data validated or an array of validation errors in 
	 * format array( 'errors' => (array) $errors)
	 */
	abstract protected function validate($data, $has_id=false);


    /**
     * list_data
     *
     * Retrieves module rows for list pages given with optional database query params. Utilized
     * in both admin and frontend.
     *
     * @access private
     * @param \App\Html\ListPage\ListPage $List The ListPage object
     * @param array $get Assoc array of database query parameters
     * @param bool $is_archive True if items returned are marked as archived
     * @param bool $is_admin True if list data for admin list page
     * @return mixed False if module is of "options" type or assoc array of list page data
     * @throws \App\Exception\AppException if $List not a valid object
     */
    private function list_data($List, $get, $is_archive, $is_admin) {
        if ( ! $this->module['use_model']) {
            //if module uses options table instead of model, can't retrieve a row
            return false;
        } else if ($List instanceof \App\Html\ListPage\ListPage === false ) {
            $msg_part = error_str('error.param.type', array('$List', '\\App\\Html\\ListPage\\ListPage'));
            $error = error_str('error.type.param.invalid', array($msg_part));
            throw new AppException($error, AppException::ERROR_FATAL);
        }

        $filters = empty($get['filters']) ? array() : $get['filters'];
        $pk_field = $this->module['pk_field'];

        //show archived or non-archived
        $list_filters = $filters;
        if ( ! empty($this->module['use_archive']) ) {
            $archive = array('is_archive' => ($is_archive ? 1 : 0) );
            $list_filters = empty($filters) ? $archive : ($filters + $archive);
        }


        //if any filters are relations, retrieve those; note that separate
        //queries are used to retrieve the relations, not joins
        $relations = $this->get_relations();
        $row_ids = false;
        $has_rows = true;
        foreach ($relations as $field_name => $rel) {
            if ( ! empty($list_filters[$field_name]) ) {
                $ids = $rel->filter($list_filters[$field_name], $field_name);
                $row_ids = $row_ids === false ? $ids : array_intersect($row_ids, $ids);

                //remove relation values from db query params
                unset($list_filters[$field_name]);
            }
        }

        //set db query filters
        $db_filters = $List->db_query_params($list_filters);
        if ( is_array($row_ids) ) {
            if ( count($row_ids) > 0 ) {
                //add filtered relations to db query params
                if ( empty($db_filters['in']) ) {
                    $db_filters['in'] = array('_outer_cnd' => 'AND');
                }
                $db_filters['in'][$pk_field] = $row_ids;
            } else {
                //relations fail filtering for common rows
                $has_rows = false;
            }
        }

        $columns = $List->columns();
        $fields = array();
        foreach ($columns as $col) {
            $fields[] = $col['name'];
        }
        $where = array(
            'where'  => $db_filters
        );
        if ($is_admin) {
            $where['fields'] = $fields;
        }
        $row_count = $has_rows ? $this->model->get_rows( ($where + array('count' => true) ) ) : 0;

        //table columns, filter params, bulk update params
        $list_params = array();
        $list_params['pk_field'] = $pk_field;
        $list_params['columns'] = $columns;
        $list_params['query_params'] = $List->filter_query_params($filters);
        if ( method_exists($List, 'bulk_update_params') ) {
            $list_params['bulk_update'] = $List->bulk_update_params();
        }

        //pagination parameters
        $page = $List->pagination_params();
        foreach ($page as $key => $value) {
            if ( ! empty($get[$key]) ) {
                if ($key === 'sort_by') {
                    $val = $get[$key];
                    if ($val === $pk_field || in_array($val, $fields) ) {
                        $page[$key] = $val;
                    }
                } else {
                    $page[$key] = is_numeric($get[$key]) ? (int) $get[$key] : $get[$key];
                }
            }
        }
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
            $query['order_by'] = $page['sort_by'];
            $query['is_asc'] = $page['order'] !== 'desc';
            $query['limit'] = $page['per_page'];
            $query['offset'] = ($page['page'] - 1) * $page['per_page'];
            $items = $this->model->get_rows($query);
            if ($is_admin) {
                $items = $List->row_boolean_vals($items);
            }
        }
        $list_params['items'] = $items;

        return $list_params;
    }

	
	/**
	 * load_module
	 *
	 * Loads the module and sets the member variables of this class.
	 * 
	 * @access private
	 * @param string $module_name The module name/slug
	 * @return void
     * @throws \App\Exception\AppException if $module_name parameter invalid or errors occurred loading
     * a module's model or, if a module is "options" type, an error occurred while loading
	 */
	private function load_module($module_name) {
		if ( ! isset(self::$MODULES[$module_name]) ) {
            $msg_part = '$module_name '.error_str('error.param.module.missing', array($module_name));
            $error = error_str('error.type.param.invalid', array($msg_part));
			throw new AppException($error, AppException::ERROR_FATAL);
		}
		
		$this->module = self::$MODULES[$this->module_name];
		
		if ($this->module['use_model']) {
		//load the module model
			$model = NULL;
			if ($this->is_main_module) {
				$model = self::$MODULES_MODEL;
			} else if ( isset(self::$INSTANCES['models'][$module_name]) ) {
				$model = self::$INSTANCES['models'][$module_name];
			} else {
				$model = $this->load_model($module_name);
				if ( is_array($model) && key($model) === 'errors') {
                    $msg_part = ' '.error_str('error.while.load.model');
                    $msg_part .= __('module').' ['.$module_name.']: ';
                    $msg_part .= implode("\n", $model['errors']);
                    $message = error_str('error.general.multi', array($msg_part));
					throw new AppException($message, AppException::ERROR_FATAL);
				}
				self::$INSTANCES['models'][$module_name] = $model;
			}
			$this->model = $model;
		} else {
		//load the module options model
			$options = $this->load_options($module_name);
			if ( is_array($options) && key($options) === 'errors') {
                $msg_part = ' '.error_str('error.while.load.options');
                $msg_part .= __('module').' ['.$module_name.']: ';
                $msg_part .= implode("\n", $options['errors']);
                $message = error_str('error.general.multi', array($msg_part));
				throw new AppException($message, AppException::ERROR_FATAL);
			}
			self::$INSTANCES['options'][$module_name] = $options;
			$this->options = $options;
		}
	}
	
	
	/**
	 * load_modules
	 *
	 * Populates the static $MODULES property of this class with an array of all modules
	 * indexed by the module name. Note that a validity check on the [modules] and [form]
	 * config is done in the constructor of this class.
	 * 
	 * @access private
	 * @return void
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
	 */
	private function load_modules() {
		$modules_config = $this->App->load_config('modules');
		$form_config = $this->App->load_config('form_fields');
		
		self::$MODULES = array();
		unset($modules_config['modules']['form_fields']);
		self::$MODULES['modules'] = $modules_config['modules'];			//Data from
		self::$MODULES['form_fields'] = $form_config['form_fields'];	//config files
			
		$modules = self::$MODULES_MODEL->get_rows();
		foreach ($modules as $m) {
			self::$MODULES[ $m['name'] ]= $m;
		}
	}

}

/* End of file Abstract_module.php */
/* Location: ./App/Module/Abstract_module.php */