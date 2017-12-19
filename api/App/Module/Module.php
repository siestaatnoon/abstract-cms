<?php

namespace App\Module;

use 
App\App,
App\Model\Model,
App\Model\Options,
App\Model\Relation,
App\Exception\AppException;

/**
 * Module class
 * 
 * The central class of the Abstract app which provides functions to add, edit and delete
 * CMS modules which, in turn, creates, updates and drops the module tables respectively.
 * In addition, provides functions to populate the CMS module form and list pages, and
 * retrieves data for frontend data population of these module pages.
 * 
 * [EXPLAIN MODULE DATA]
 * 
 * [EXPLAIN MODULE FORM DATA]
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Module
 */
class Module extends \App\Module\Abstract_module {

    /**
     * @const string URI used for module add page in CMS
     */
    const ADD_URI = 'add';

    /**
     * @const string URI used for module edit page in CMS
     */
    const EDIT_URI = 'edit';

    /**
     * @const string URI used for module list page in CMS
     */
    const LIST_URI = 'list';

    /**
     * @const string URI used for module sort page in CMS
     */
    const SORT_URI = 'sort';

    /**
     * @const string URI used for options type module update page in CMS
     */
    const UPDATE_URI = 'update';

    /**
     * @var array Core modules contained in the App
     */
    public static $CORE_MODULES = array('modules', 'form_fields', 'pages', 'users');


	/**
	 * Constructor
	 *
	 * Initializes the Module.
	 * 
	 * @access public
	 */
	public function __construct($module_name='modules') {
		parent::__construct($module_name);
	}
	

	/**
	 * add
	 *
	 * Overrides the parent method by throwing an \App\Exception\AppException
	 * if this method called from the "modules" module.
	 *
	 * @access public
	 * @param array $data The fields and corresponding values to insert
	 * @return mixed The data assoc array with primary key field added with the id 
	 * of the inserted row OR an array of errors in format 
	 * array( 'errors' => (array) $errors) if insert unsuccessful
	 * @throws \App\Exception\AppException if this method called from an instance of "modules" module
	 * @see \App\Module\Abstract_module.add() for method definition
	 */
	public function add($data) {
		if ($this->is_main_module && $this->is_called_by('create') === false ) {
			$error = '\App\Module\Module.add() cannot be called for module [modules], use static ';
			$error .= '\App\Module\Module::create() instead';
			throw new AppException($error, AppException::ERROR_FATAL);
		}

		return parent::add($data);
	}
	
	
	/**
	 * create
	 *
	 * Creates the module database table and relational tables and inserts 
	 * module to the modules table and relational form fields for the new module.
	 * 
	 * @access public
	 * @param array $data The module definitions in an assoc array
	 * @return mixed The Module object of the new module OR an array of 
	 * errors in format array( 'errors' => (array) $errors) if the 
	 * module was not properly created OR an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 */
	public static function create($data) {
		$module = self::load();
		$errors = array();
		$row = $module->module_form_data_to_row($data);
		$new_module_name = $row['name'];
		$use_model = $row['use_model'];
		$new_model = false;
		
		//if module an "options" type then default attributes need to be set
        //for each form field, Note that if form field is to a module being 
        //updated, this is done in Module_form_fields->module_form_data_to_row()
        //method
		if ( empty($use_model) && ! empty($data['form_fields']) ) {
			foreach ($data['form_fields'] as &$ff) {
				$ff['module_pk'] = '';
		        $ff['data_type_type'] = ''; //option fields are already a set data type
                $ff['data_type_length'] = '';
                $ff['data_type_values'] = '';
		        if ($ff['field_type_type'] === 'relation') {
		        //option module fields cannot be relations for now,
		        //if set then change to text field type
		            $ff['field_type_type'] = 'text';
		            $ff['field_type_relation_name'] = '';
		            $ff['field_type_relation_type'] = '';
		            $ff['field_type_html'] = '';
		            $ff['field_type_template'] = '';
		        }
			}
		}
		
		if ( ! empty($use_model) ) {
			//create the module table
			$model_config = array(
				'module' 		=> $new_module_name,
				'pk_field' 		=> $row['pk_field'],
				'title_field' 	=> $row['title_field'],
				'slug_field' 	=> $row['slug_field']
			);
			$fields_config = $row['field_data']['data_type'];
			$new_model = Model::create_table($model_config, $fields_config, true);
			if ($new_model === false) {
				$errors[] = 'Module ['.$new_module_name.'] CREATE TABLE failed';
			} else {
				self::$INSTANCES['models'][$new_module_name] = $new_model;
			}
		} else {
			//if modules does not use a model then create options
			$o_config = array(
				'module' => $new_module_name,
				'fields' => $row['field_data']['model']
			);
			$options = Options::create($o_config);
			if ($options  === false) {
				$errors[] = 'Options for module ['.$new_module_name.'] create failed';
			} else {
			//add cached options object
				self::$INSTANCES['options'][$new_module_name] = $options;
			}
		}
		
		if ( ! empty($errors) ) {
			return array('errors' => $errors);
		}

		//create the relational tables
		if ( ! empty($row['field_data']['relations']) ) {
		    $relations = array();
			$new_rel = $row['field_data']['relations'];
			foreach($new_rel as $f => $config) {
			    $mod = $config['module'];
				$indep_model = $module->load_model($mod);
				if ( is_array($indep_model) && key($indep_model) === 'errors') {
					$errors = array_merge($errors, $indep_model['errors']);
					continue;
				}
				
				$r_config = array(
					'module' 		=> $module->get_module_data($mod),
					'dep_model' 	=> $new_model,
					'indep_model' 	=> $indep_model,
					'relation_type' => $config['type']
				);

                $rel = NULL;
                try {
                    $rel = Relation::create_table($r_config, false);
                } catch (AppException $ae) {
                    $error = 'Relational table, module ['.$mod.'], for module ['.$new_module_name.'] CREATE TABLE failed';
                    $errors[] = $error;
                    continue;
                }
                $relations[$f] = $rel;
			}
            self::$INSTANCES['relations'][$new_module_name] = $relations;
		}

        if ( empty($errors) ) {
        // insert module to module table
            $add = $module->add($data);
            if ( is_array($add) ) {
                $errors = array_merge($errors, $add['errors']);
            }
        }

        /*
        if ( ! empty($errors) ) {
        // "rollback" module creation
            $drop = self::drop($new_module_name);
            if ( is_array($drop) ) {
                $errors = array_merge($errors, $drop['errors']);
            }
        }
        */

		return empty($errors) ? Module::load($new_module_name) : array('errors' => $errors);
	}
	

	/**
	 * delete
	 *
	 * Overrides the parent method by throwing an \App\Exception\AppException
	 * if this method called from the "modules" module.
	 *
	 * @access public
	 * @param mixed $mixed The row id or array of ids to delete
	 * @return mixed True if delete successful OR an array of errors in format
	 * array( 'errors' => (array) $errors) if delete unsuccessful OR false if empty
	 * $mixed parameter OR an App\Exception\SQLException is passed and to be handled by 
	 * \App\App class if an SQL error occurred
	 * @throws \App\Exception\AppException if this method called from an instance of "modules" module
	 * @see \App\Module\Abstract_module for method definition 
	 */
	public function delete($mixed) {
        if ($this->is_main_module && $this->is_called_by('drop') === false ) {
        // modules to delete need to be verified they are not used as a relation in another module
        //
        // NOTE: the self::drop() method is called to delete the module which will call back this method
        // and will only pass through this block once
            $ids = is_array($mixed) ? $mixed : array($mixed); // can be multiple modules for delete
            $modules = array();
            $errors = array();

            // check if module(s) are used as relations in other modules
            // if so, return error(s)
            foreach ($ids as $id) {
                $module_name = '';
                $mods = array();
                foreach (self::$MODULES as $module_name => $data) { // find module corresponding to delete ID
                    if ( (int) $id === (int) $data['id'] ) {
                        foreach (self::$MODULES as $mod_name => $d) { // search each module for relations that uses module
                            if ($mod_name === $module_name) {         // except for module being deleted
                                continue;
                            }
                            $relations = $d['field_data']['relations'];
                            foreach ($relations as $field => $params) { // search each relation for module
                                if ( $params['module'] === $module_name && ! in_array($mod_name, $mods) ) {
                                 // relation that uses module to delete found
                                    $mods[] = $mod_name;
                                }
                            }
                        }
                        break;
                    }
                }
                if ( ! empty($mods) ) {
                    $error = 'Module ['.$module_name.'] cannot be delete since it is a relation for module';
                    $error .= (count($mods) === 1 ? '' : 's').' ['.implode('], [', $mods).']';
                    $errors[] = $error;
                }
                $modules[$id] = $module_name;
            }

            if ( ! empty($errors) ) {
                return array('errors' => $errors);
            }

            foreach ($modules as $module_name) {
                $result = self::drop($module_name);
                if ( ! empty($result['errors']) ) {
                    $errors = $errors + $result['errors'];
                }
            }
            return empty($errors) ? true : array('errors' => $errors);
        }

		return parent::delete($mixed);
	}
	

	/**
	 * drop
	 *
	 * Drops the module database table, drops relational tables and deletes the relational
	 * form fields rows.
	 * 
	 * @access public
	 * @param int $id The id of the module
	 * @return mixed True if drop successful OR an array of errors that occurred OR an 
	 * App\Exception\SQLException is passed and to be handled by \App\App class if an SQL error occurred
	 */
	public static function drop($module_name) {
		$module = self::load();
		$module_drop = self::load($module_name);
		$module_data = $module_drop->get_module_data();
        $model = $module_drop->get_model();
		$errors = array();
		
		if ( empty($module_name) ) {
		 	$errors[] = 'Param $module_name empty, must be module name';
		 	return $errors;
		} 

		$use_model = $module_data['use_model'];
		$relations = $module_data['field_data']['relations'];
		
		//delete module from modules table plus relations
		$result = $module->delete($module_data['id']);
		if ( is_array($result) ) {
			return $result;
		}

		//drop relational tables
		if ( ! empty($relations) ) {
			foreach($relations as $field => $config) {
			    $mod = $config['module'];
				$indep_model = $module->load_model($mod);
				$r_config = array(
					'module' 		=> $module->get_module_data($mod),
					'dep_model' 	=> $model,
					'indep_model' 	=> $indep_model,
					'relation_type' => $config['type']
				);
				if ( Relation::drop_table($r_config) === false ) {
					$errors[] = 'Relational table, module ['.$mod.'], for module ['.$module_name.'] DROP TABLE failed';
				} else {
					//delete cached relation instance
					unset(self::$INSTANCES['relations'][$module_name][$field]);
				}
			}
		}
		
		if ($use_model) {
		//drop module table
			$model_config = array(
				'module' 		=> $module_name,
				'pk_field' 		=> $module_data['pk_field'],
				'title_field' 	=> $module_data['title_field'],
				'slug_field' 	=> $module_data['slug_field'],
				'fields'		=> $module_data['field_data']['data_type']
			);
			if ( Model::drop_table($model_config) === false) {
				$errors[] = 'Module ['.$module_name.'] DROP TABLE failed';
			} else {
				//delete module instance from cache
				unset(self::$INSTANCES['modules'][$module_name]);
			}
		} else {
		//delete from options
			$o_config = array(
				'module' => $module_name,
				'fields' => $module_data['field_data']['model']
			);
			$options = Options::destroy($o_config);
			if ($options  === false) {
				$errors[] = 'Options for module ['.$module_name.'] destroy failed';
			} else {
			//delete cached options object
				unset(self::$INSTANCES['options'][$module_name]);
			}
		}

		// delete module from user permissions
        $module->get_model()->delete_user_module_relations($module_data['id']);

		return empty($errors) ? true : array('errors' => $errors);
	}


    /**
     * form_field_slug_field
     *
     * Creates a custom form field select menu HTML for the module slug field with
     * the module's form felds as select options.
     *
     * @access public
     * @param int $module_id The module ID to retrieve its form fields
     * @param string $value The value for the form field
     * @param \App\User\Permission $permission The current CMS user Permission object
     * @return string The form field select HTML
     */
    public function form_field_slug_field($module_id, $value, $permission, $params=array()) {
        return $this->render_form_field_select('slug_field', $module_id, $value, $permission);
    }


    /**
     * form_field_title_field
     *
     * Creates a custom form field select menu HTML for the module title field with
     * the module's form felds as select options.
     *
     * @access public
     * @param int $module_id The module ID to retrieve its form fields
     * @param string $value The value for the form field
     * @param \App\User\Permission $permission The current CMS user Permission object
     * @return string The form field select HTML
     */
    public function form_field_title_field($module_id, $value, $permission, $params=array()) {
        return $this->render_form_field_select('title_field', $module_id, $value, $permission);
    }


    /**
     * get_core_modules
     *
     * Returns an array of core Abstract module slugs or assoc array of core module
     * data indexed by slug.
     *
     * @access public
     * @param bool $include_data True to include module data (default)
     * @return array The array of core module slugs or assoc array of module data indexed by slug
     */
    public static function get_core_modules($include_data=true) {
        if ($include_data === false) {
            return self::$CORE_MODULES;
        }

        $modules = array();
        foreach (self::$CORE_MODULES as $module_name) {
            $modules[$module_name] = self::$MODULES[$module_name];
        }
        return $modules;
    }


    /**
     * get_default_field_values
     *
     * Overwrites parent class method by adding reserved fields JSON to form.
     *
     * @access public
     * @param array $params Array of parameters passed in and used by subclass method overwrites
     * @return array The array of default form fields and values
     * @see Abstract_module::get_default_field_values() for method definition
     */
    public function get_default_field_values($params=array()) {
        $values = parent::get_default_field_values($params);
        $values['reserved_fields'] = $this->model->get_reserved_fields();
        return $values;
    }

    /**
     * is_core
     *
     * Checks a given string to verify it is a core application module slug.
     *
     * @access public
     * @param string $module_name The module name
     * @return bool True if string is a core module
     */
    public static function is_core($module_name) {
        if ( empty($module_name) ) {
            return false;
        }

        return in_array($module_name, self::$CORE_MODULES);
    }
	
	
	/**
	* is_module
	* 
	* Checks a given string to verify it is a CMS module slug name.
	*
	* @access public
	* @param string $module_name The module name
	* @return bool True if string is a module slug
	*/
	public static function is_module($module_name) {
		if ( empty($module_name) ) {
			return false;
		} else if ( empty(self::$MODULES) ) {
			self::load();
		}
		
		return isset(self::$MODULES[$module_name]);
	}
	

	/**
	* load
	* 
	* Returns a Module class instance given the module name or an instance
	* of \App\Module\Module_[module name] as a defined subclass of 
	* \App\Module\Abstract_module. If a module class instance is already
	* loaded, that will be returned instead of recreated.
	*
	* @access public
	* @param string The module name
	* @return \App\Module\Abstract_module The module instance
	* @throws \App\Exception\AppException if an error occurs while loading module
	*/
	public static function load($module_name='modules') {
		if ( empty($module_name) ) {
			return false;
		} else if ( isset(self::$INSTANCES['modules'][$module_name]) ) {
			return self::$INSTANCES['modules'][$module_name];
		}
		
		$module = false;
		try {
			if ( file_exists(APP_PATH.DIRECTORY_SEPARATOR.'Module'.DIRECTORY_SEPARATOR.'Module_'.$module_name.'.php') ) {
				$class = '\App\Module\Module_'.$module_name;
				$module = new $class($module_name);
			} else {
				$module = new self($module_name);
			}
			self::$INSTANCES['modules'][$module_name] = $module;
		} catch (AppException $e) {
			throw $e;
		}

		return $module;
	}
	

	/**
	 * modify
	 *
	 * Modifies the module definition by altering the module database table and/or
	 * creating/dropping relations and insert/update/delete related form fields.
	 * 
	 * @access public
	 * @param array $data The updated module definitions in an assoc array
	 * @return mixed The Module object of the updated module OR an array of 
	 * validation errors in format array( 'errors' => (array) $errors) if the module 
	 * was not properly modified OR an App\Exception\SQLException is passed and to be 
	 * handled by \App\App class if an SQL error occurred
	 */
	public static function modify($data) {
		$main_module = self::load();
        $App = App::get_instance();
		$module_id = empty($data['id']) ? false : $data['id'];
		$old_row = empty($module_id) ? false : $main_module->get_data($module_id, false, true);
		$can_update_immutable = $App->config('modules_immutable_updates');
        $errors = array();

		if ( empty($module_id) ) {
			$errors[] = 'Param $data[id] empty value, must be module id';
		}
		if ( empty($old_row) ) {
			$errors[] = 'Module [ID: '.$module_id.'] does not exist';
		}
		if ( ! empty($errors) ) {
			return $errors;
		}

		$old_module_name = $old_row['name'];
		$module = self::load($old_module_name);
		$old_module_data = $module->get_module_data();
		$modify_params = array();
		
		$use_model = $data['use_model'];
		if ($use_model !== $old_module_data['use_model']) {
            if ($can_update_immutable === false) {
                $error = 'Module "create database table" cannot be updated';
                return array('errors' => array($error) );
            } else {
            //module switched from using model to options, drop and return newly created
                self::drop($old_module_name);
                return self::create($data);
            }
		}
		
		//update module and relational rows
		$result = $main_module->update($data);
		if ( is_array($result) ) {
			$errors = array_merge($errors, $result['errors']);
			return array('errors' => $errors);
		}

        //delete old cached module
        unset(self::$INSTANCES['modules'][$old_module_name]);

		//reload module to modify
		$module_name = $module->safe_module_name($data['name']);
		$module = NULL;
        try {
            $module = self::load($module_name);
        } catch (AppException $ae) {
        // modules set to not allow change in name/slug then, return error
            $error = 'Module ['.$module_name.'] cannnot be renamed from ['.$old_module_name.']';
            return array('errors' => array($error) );
        }

        //retrieve updated module data
		$new_module_data = $module->get_module_data();
		
		//if modules does not use a model then update options
		if ( ! $use_model) {
			//update options for module
			$options = $module->load_options($module_name);
			$options = $options->update_fields($new_module_data['field_data']['model']);
			if ($options  === false) {
				$errors[] = 'Options for module ['.$old_module_name.'] update failed';
			} else {
			//update cached options object, return module object
				self::$INSTANCES['options'][$module_name] = $options;
			}

			//return errors or updated module, no need to continue
			return empty($errors) ? $module : array('errors' => $errors);
		}
		
		//updates for relations
        $old_relations = $old_module_data['field_data']['relations'];
		$new_relations = $new_module_data['field_data']['relations'];
        $rel_diff = self::field_relation_diff($old_relations, $new_relations, $old_row['form_fields'], $data['form_fields']);
        $rel_add = $rel_diff['rel_add'];
        $rel_delete = $rel_diff['rel_delete'];
        $add_tables = $rel_diff['add_tables'];
        $delete_tables = $rel_diff['delete_tables'];
        $changed_fields = $rel_diff['changed_fields'];
		$dep_model = $module->get_model();

		if ( ! empty($rel_add) ) {
		//add new relational tables
			foreach($rel_add as $f => $config) {
                $mod = $config['module'];
                if ( in_array($mod, $add_tables, true) ) {
                    $indep_model = $module->load_model($mod);
                    if (is_array($indep_model) && key($indep_model) === 'errors') {
                        $errors = array_merge($errors, $indep_model['errors']);
                        continue;
                    }
                    $r_config = array(
                        'module' => $module->get_module_data($mod),
                        'dep_model' => $dep_model,
                        'indep_model' => $indep_model,
                        'relation_type' => $config['type']
                    );
                    try {
                        Relation::create_table($r_config, false);
                    } catch (AppException $ae) {
                        $error = 'Relation, module [' . $mod . '], for module [' . $module_name;
                        $error .= '] CREATE TABLE failed';
                        $errors[] = $error;
                        continue;
                    }
                }
			}
		}

		if ( ! empty($rel_delete) ) {
		//drop old relational tables
			foreach($rel_delete as $f => $config) {
				$mod = $config['module'];
                if ( in_array($mod, $delete_tables, true) ) {
                    $indep_model = $module->load_model($mod);
                    if (is_array($indep_model) && key($indep_model) === 'errors') {
                        $errors = array_merge($errors, $indep_model['errors']);
                        continue;
                    }
                    $r_config = array(
                        'module' => $module->get_module_data($mod),
                        'dep_model' => $dep_model,
                        'indep_model' => $indep_model,
                        'relation_type' => $config['type']
                    );
                    try {
                        Relation::drop_table($r_config);
                    } catch (AppException $ae) {
                        $error = 'Relational table, module [' . $mod . '], for module [' . $old_module_name;
                        $error .= '] DROP TABLE failed';
                        $errors[] = $error;
                        continue;
                    }
                }
			}
		}

        if ( ! empty($changed_fields) ) {
        //update field names and/or truncate relation tables if relation type changed
            foreach($changed_fields as $field_name => $params) {
                $cfg = $params['config'];
                $mod = $cfg['module'];
                $indep_model = $module->load_model($mod);
                if (is_array($indep_model) && key($indep_model) === 'errors') {
                    $errors = array_merge($errors, $indep_model['errors']);
                    continue;
                }
                $r_config = array(
                    'module' => $module->get_module_data($mod),
                    'dep_model' => $dep_model,
                    'indep_model' => $indep_model,
                    'relation_type' => $cfg['type']
                );
                try {
                    $rel = new Relation($r_config);
                    if ( ! empty($params['truncate']) ) {
                        $rel->delete(false, false, $field_name);
                    } else if ( ! empty($params['field_name']) ) {
                        $rel->update_field_name($field_name, $params['field_name']);
                    }
                } catch (AppException $ae) {
                    $error = 'Relational table, module [' . $mod . '], for module [' . $old_module_name;
                    $error .= '] DROP TABLE failed';
                    $errors[] = $error;
                    continue;
                }
            }
        }

        //resets the cached relations
        unset(self::$INSTANCES['relations'][$module_name]);
        $module->get_relations();
		
		//check for module table rename
		if ($module_name !== $old_module_name) {
			$modify_params['rename'] = $module_name;
		}
		
		//retrieve pre-update model data 
		$old_fields = array();
		$of = $old_row['form_fields'];
		foreach ($of as $f) {
			$field = $f['name'];
			if ( ! empty($f['is_model']) && ! array_key_exists($field, $old_relations) ) {
				$old_fields[$field] = $f;
			}
		}
		
		//retrieve updated model fields 
		$new_fields = array();
		$form_fields = $data['form_fields'];
		foreach ($form_fields as $f) {
			$field = $f['name'];
			if ( ! empty($f['is_model']) && ! array_key_exists($field, $new_relations) ) {
				$new_fields[$field] = $f;
			}
		}
		
		//check for updates to module table fields
		$old_data_type = $old_module_data['field_data']['data_type'];
		$new_data_type = $new_module_data['field_data']['data_type'];
		$prev_field = array();
		$new_fields_ids = array();
		foreach ($new_fields as $field => $ff) {
			$id = empty($ff['field_id']) ? false : $ff['field_id'];
			$params = array_key_exists($field, $new_data_type) ? $new_data_type[$field] : array();
			$after = empty($prev_field) ? array() : array('after' => $prev_field['name']);
			if ( ! empty($id) ) {
				$new_fields_ids[] = $id;
			}
			
			if ( empty($id) || (array_key_exists($field, $old_relations) && ! array_key_exists($field, $new_relations) ) ){
			//column is new or changed from relation to other type, add the module table column
				$params = $params + $after;
				$modify_params['add'][$field] = $params;
			} else if ( array_key_exists($field, $old_data_type) ) {
			//column exists, check for type updates
				$diff = array_diff_key($params, $old_data_type[$field]);
				if ( ! empty($diff) ) {
					$modify_params['modify'][$field] = $params;
				}
			} else {
			//check if column name changed
				foreach ($old_fields as $old_field) {
					if ($id == $old_field['field_id'] && $field !== $old_field['name']) {
					//column name changed
						$params['new_name'] = $field;
						$modify_params['change'][ $old_field['name'] ] = $params;
						break;
					}
				}
			}
			
			$prev_field = $ff;
		}
		//drop fields that no longer exist
		foreach ($old_fields as $field => $ff) {
			if ( ! in_array($ff['field_id'], $new_fields_ids) ) {
				$modify_params['drop'][$field] = true;
			}
		}
		
		//update module table fields
		if ( ! empty($modify_params) ) {
			$model_config = array(
				'module' 		=> $old_module_name,
				'pk_field' 		=> $old_module_data['pk_field'],
				'title_field' 	=> $old_module_data['title_field'],
				'slug_field' 	=> $old_module_data['slug_field'],
				'fields'		=> $old_module_data['field_data']['data_type']
			);

			if ( Model::alter_table($model_config, $modify_params) === false ) {
				$errors[] = 'Module ['.$old_module_name.'] ALTER TABLE failed';
			} else {
				//delete cached model instance since it doesn't reflect updates
				unset(self::$INSTANCES['models'][$old_module_name]);
			}
		}

		return empty($errors) ? $module : array('errors' => $errors);
	}


    /**
     * reset
     *
     * Resets the application modules to an initial "clean" state deleting all added
     * modules.
     *
     * @access public
     * @param bool $clear_all True to clear all module tables and slugs
     * @return mixed True if reset successful, false if this function not called from "modules"
     * module or an array of errors in format array( 'errors' => (array) $errors) if errors
     * occurred during the operation
     */
	public function reset($clear_all=false) {
		if ($this->is_main_module === false) {
		//can only be used by modules module
			return false;
		}

		$main_modules = self::$CORE_MODULES;
        $ff_module = Module::load('form_fields');
        $modules = array();
        $errors = array();

        foreach ($main_modules as $mm) {
            $cfg = $this->App->load_config($mm);
            $modules[$mm] = $cfg[$mm];
        }
		
		//temporarily disable flag to bypass validation
		$this->is_main_module = false;

		if ($clear_all) {
            //clear the module tables and slugs
			$this->model->clear_modules();
		}
		
		//update each main module
		foreach ($modules as $module_name => $data) {
			//convert config data to form data
			$data = $this->row_to_module_form_data($data);
			foreach ($data['form_fields'] as &$field) {
				$field = $ff_module->row_to_module_form_data($field);

                //add lang label for empty labels
                if ( empty($field['label']) && ! empty($field['lang']) ) {
                    $field['label'] = $this->App->lang($field['lang']);
                }
			}
			
			//check if module exists
			$module = $clear_all ? array() : parent::get_data($module_name, true);

			if ( empty($module) ) {
			//insert modules module to module table
				$module_id = parent::add($data);
				if ( is_array($module_id) ) {
					$errors = array_merge($errors, $module_id['errors']);
				}
			} else {
			//update modules module and relational rows
				
				//merge IDs of form fields with config values
				$d_form_fields = $data['form_fields'];
				foreach ($module['form_fields'] as &$mff) {
					$mff = $ff_module->row_to_module_form_data($mff);
					foreach($d_form_fields as $dff) {
						if ($mff['name'] === $dff['name']) {
							$mff = $dff + $mff;
						}
					}
				}
				unset($data['form_fields']);
				$data = $data + $module;

				$result = parent::update($data);
				if ( is_array($result) ) {
					$errors = array_merge($errors, $result['errors']);
				}
			}
		}

		if ( empty($errors) ) {
			//reset module table auto increments
			if ($clear_all) {
				$this->model->reset_auto_increment();
			}
			
			//reload modules
			foreach ($modules as $module_name => $data) {
				unset(parent::$MODULES[$module_name]);
				unset(parent::$INSTANCES['models'][$module_name]);
				unset(parent::$INSTANCES['relations'][$module_name]);
				self::load($module_name);
			}
		}

		//reset module flag/ caching
		$this->is_main_module = true;
		
		return empty($errors) ? true : array('errors' => $errors);
	}
	

	/**
	 * update
	 *
	 * Overrides the parent method by throwing an \App\Exception\AppException
	 * if this method called from the "modules" module.
	 *
	 * @access public
	 * @param array $data The fields and corresponding values to update
	 * @return mixed The data assoc array OR an array of errors in format 
	 * array( 'errors' => (array) $errors) if update unsuccessful
	 * @throws \App\Exception\AppException if this method called from an instance of "modules" module
	 * @see \App\Module\Abstract_module.update() for method definition
	 */
	public function update($data) {
		if ($this->is_main_module && $this->is_called_by('modify') === false ) {
			$error = '\App\Module\Module.update() cannot be called for module [modules], use static ';
			$error .= '\App\Module\Module::modify() instead';
			throw new AppException($error, AppException::ERROR_FATAL);
		}
		
		return parent::update($data);
	}
	
	
	/**
	 * module_form_data_to_row
	 *
	 * Converts CMS form data to an assoc array to INSERT/UPDATE a module table row. Removes
	 * any indexes that are not a module field and adds any fields with a default value if
	 * missing. NOTE: only converts data from the "modules" module. To execute this method
	 * for other modules, a method override should be created in a subclass.
	 * 
	 * @access protected
	 * @param array $data The CMS form data
	 * @return array The data converted to an assoc array for module table row
	 */
	protected function module_form_data_to_row($data) {
        if (empty($data) || ! $this->is_main_module) {
            return $data;
        }

        $ff_module = Module::load('form_fields');

        //if editing a module, make sure certain fields stay unchanged if not allowed in config
        if ( $this->App->config('modules_immutable_updates') === false && ! empty($data['id']) ) {
            foreach (self::$MODULES as $name => $mod_data) {
                if ((int) $mod_data['id'] === (int) $data['id']) {
                    $nochange = array();
                    $nochange['name'] = $mod_data['name'];
                    $nochange['pk_field'] = $mod_data['pk_field'];
                    $nochange['use_model'] = $mod_data['use_model'];
                    $data = $nochange + $data;
                    break;
                }
            }
        }

        //create an array without keys that do not correspond to module table fields
        $row = array_intersect_key($data, parent::$MODULES['modules']['field_data']['model']);
        $row['form_fields'] = $data['form_fields'];
        $row['is_active'] = isset($data['is_active']) ? $data['is_active'] : 1;
        $row['id'] = empty($data['id']) ? NULL : $data['id'];
        $pk_field = $row['pk_field'];

		$row['name'] = parent::safe_module_name($row['name']);
		$data_type = array();
		$field_type = array();
		$defaults = array();
		$filters = array();
		$model = array();
		$relations = array();
		$uploads = array();
		$validation = array();

        if ( ! empty($row['use_model']) ) {
            $defaults[$pk_field] = '';
            $model[$pk_field] = '';
        }

		foreach ($data['form_fields'] as $index => $params) {
			$params = $ff_module->module_form_data_to_row($params);
			$field = $params['name'];
			if ($field === $row['pk_field']) {
			//do not save primary key field as form field
				continue;
			}
			
			$defaults[$field] = $params['default'];
			$field_type[$field] = key($params['field_type']);

			if ( ! empty($params['field_type']['relation']) ) {
				$rel = $params['field_type']['relation'];
				$relations[$field] = $rel;
			}
			
			if ( ! empty($params['field_type']['image']) ) {
				$uploads[$field] = array(
					'config_name' => $params['field_type']['image']['config_name'],
					'is_image'	  => true
				);
			}
			
			if ( ! empty($params['field_type']['file']) ) {
				$uploads[$field] = array(
					'config_name' => $params['field_type']['file']['config_name'],
					'is_image'	  => false
				);
			}
			
			if ($params['is_filter']) {
				$filters[] = $field;
			}
			
			if ($params['is_model'] && empty($params['field_type']['relation']) ) {
				$model[$field] = $params['default'];
				$data_type[$field] = $params['data_type'];
				$data_type[$field]['default'] = $params['default'];
			}

			if ( ! empty($params['validation']) ) {
				$a = array();
				foreach ($params['validation'] as $r => $v) {
                    if ( ! empty($v['param']) && ! empty($v['rules']) && ! empty($v['message']) ) {
                        $a[$r] = $v;
                    } else if ( empty($v['param']) && empty($_v['message']) ) {
						$a[$r] = true;
					} else {
                        if ( ! empty($v['param']) ) {
                            $a[$r]['param'] = $v['param'];
                        }
                        if ( ! empty($v['message']) ) {
                            $a[$r]['message'] = $v['message'];
                        }
                    }
				}
				if ( ! empty($a) ) {
					$validation[$field] = array('valid' => $a);
				}
			}
		} //end foreach

		if ( empty($row['use_model']) ) {
            $row['label_plural'] = $row['label'];
            $row['pk_field'] = '';
            $row['title_field'] = '';
            $row['slug_field'] = '';
			$row['use_add'] = 0;
			$row['use_edit'] = 0;
			$row['use_delete'] = 0;
			$row['use_active'] = 0;
			$row['use_sort'] = 0;
			$row['use_archive'] = 0;
			$row['use_slug'] = 0;
            $row['use_cms_form'] = 1;
            $row['use_frontend_list'] = 0;
            $row['use_frontend_form'] = 0;
			$data_type = array();
			$filters = array();
			$relations = array();
		}
		
		$row['field_data']	= array(
			'data_type' 	=> $data_type,
			'field_type' 	=> $field_type,
			'filters' 		=> $filters,
			'defaults' 		=> $defaults,
			'model' 		=> $model,
			'uploads' 		=> $uploads,
			'validation' 	=> $validation,
			'relations'		=> $relations
		);
	
		return $row;
	} 
	
	
	/**
	 * row_to_module_form_data
	 *
	 * Converts a module table row into an assoc array of data used for the CMS form 
	 * to add or update a module object. NOTE: only converts data from the "modules" 
	 * module. To execute this method for other modules, a method override should be 
	 * created in a subclass.
	 * 
	 * @access protected
	 * @param array $row A module row
	 * @return array The row converted to CMS form data
	 */
	protected function row_to_module_form_data($row) {
		if ( empty($row) || ! $this->is_main_module) {
			return $row;
		}

		$reserved_fields = $this->model->get_reserved_fields();
        $reserved_fields[] = $row['pk_field'];
        $row['reserved_fields'] = $reserved_fields;
		unset($row['field_data']);
		return $row;
	} 
	
	
	/**
	 * validate
	 *
	 * Checks the given assoc array of form data for necessary indeces. NOTE: only validates 
	 * data from the "modules" module. To execute this method or other modules, a method override 
	 * should be created in a subclass.
	 * 
	 * @access protected
	 * @param array $data The module row data as an assoc array
	 * @param boolean $has_id True if $data param contains a non-empty id for the module row
	 * @return mixed True if row data validated or an array of validation errors
	 */
	protected function validate($data, $has_id=false) {
		if ( ! $this->is_main_module) {
			return true;
		}
		
		$errors = array();
		if ($has_id) {
			if ( empty($data['id']) ) {
				$errors[] = 'Module param [id] empty, must be module ID';
			} else {
				$old_module = parent::get_data($data['id'], false, true);
				if ( empty($old_module) ) {
					$errors[] = 'Module "'.$data['name'].'" does not exist and cannot be updated';
				} else if ($data['name'] !== $old_module['name'] && array_key_exists($data['name'], parent::$MODULES) ) {
					$errors[] = 'Module param [name] "'.$data['name'].'", already in use, must be unique name';
				}
			}
		} else if ( array_key_exists($data['name'], parent::$MODULES) ) {
			$errors[] = 'Module param [name] "'.$data['name'].'", already in use, must be unique name';
		}
		if ( empty($data['name']) ) {
			$errors[] = 'Module ['.$data['name'].'] param [name] empty, must be name of module';
		}
        if ( ! isset($data['use_model']) ) {
            $errors[] = 'Module ['.$data['name'].'] param [use_model] not set';
        } else if ( ! empty($data['use_model']) ) {
            if ( empty($data['pk_field']) ) {
                $errors[] = 'Module ['.$data['name'].'] param [pk_field] empty, must be name of module primary key field';
            }
            if ( empty($data['title_field']) ) {
                $errors[] = 'Module ['.$data['name'].'] param [title_field] empty, must be name of module title reference field';
            }
            if ( empty($data['label_plural']) ) {
                $errors[] = 'Module ['.$data['name'].'] param [label_plural] empty, must be plural display name of module';
            }
        }
		if ( empty($data['label']) ) {
			$errors[] = 'Module ['.$data['name'].'] param [label] empty, must be display name of module';
		}
		if ( empty($data['form_fields']) ) {
			$errors[] = 'Module ['.$data['name'].'] param [form_fields] empty, must be array of \App\Form\Field\Form_field';
		}

		
		return empty($errors) ? true : $errors;
	}


    /**
     * array_value_delete
     *
     * Utility function to delete a value in a numeric array and reset the indeces. For
     * use in field_relation_diff() function.
     *
     * @access private
     * @param string $value The value to search for and delete
     * @param array The array to search
     * @return return The array with value deleted if found
     */
    private static function array_value_delete($value, &$arr) {
        if ( in_array($value, $arr, true) ) {
            $index = array_search($value, $arr, true);
            unset($arr[$index]);
        }
        return array_values($arr);
    }


    /**
     * field_relation_diff
     *
     * Compares old fields of type relation config with new fields of type relations and determines
     * if new relation db tables should be added or deleted. Returns an array of<br/></br>
     * <ul>
     *  <li>rel_add => assoc array of [field name] => [relation config] of relations to add</li>
     *  <li>rel_delete => assoc array of [field name] => [relation config] of relations to delete</li>
     *  <li>add_tables => array of relation module names to add tables</li>
     *  <li>delete_tables => array of relation module names to delete tables</li>
     * </ul>
     *
     * @access private
     * @param array $old_config The old field relations config
     * @param array $new_config The new field relations config
     * @param array $old_fields The old module form field object
     * @param array $new_fields The new module form field objects
     * @return array The relations config and tables to add and/or delete
     */
    private static function field_relation_diff($old_config, $new_config, $old_fields, $new_fields) {
        if ( empty($old_config) ) {
            $old_config = array();
        }

        $old_rel = array();
        $old_ids = array();
        $new_rel = array();
        $new_ids = array();
        $rel_add = array();
        $rel_delete = array();
        $add_tables = array();
        $delete_tables = array();
        $nomodify_tables = array();
        $changed_fields = array();

        // set up module name and field id lists indexed by field name
        foreach ($old_config as $field_name => $params) {
            $old_rel[$field_name] = $params['module'];
        }
        foreach ($new_config as $field_name => $params) {
            $new_rel[$field_name] = $params['module'];
        }
        foreach ($old_fields as $field) {
            if ( array_key_exists($field['name'], $old_rel) ) {
                $old_ids[ $field['name'] ] = $field['field_id'];
            }
        }
        foreach ($new_fields as $field) {
            if ( array_key_exists($field['name'], $new_rel) ) {
                $new_ids[ $field['name'] ] = $field['field_id'];
            }
        }

        // compare new relation modules with old relation modules, search for:
        // - new fields (add module)
        // - fields with changed name (update relation field name field)
        // - fields with changed module (delete old, add new module)
        // - fields with no modifications (do not modify module)
        // - relations with changed relation type (truncate relation table)
        //
        // Note that this checks:
        // - if a field uses a relational table then table not added or delete
        // - a relational table is added only if it is not already in use by another field
        // - a relational table is deleted if it is not already in use and table not being added
        foreach ($new_rel as $field_name => $module) {
            $old_module = false;
            if ( array_key_exists($field_name, $old_rel) ) {
            //field name has not changed
                $old_module = $old_rel[$field_name];

                //check if relation type changed
                if ($old_config[$field_name]['type'] !== $new_config[$field_name]['type']) {
                    $changed_fields[$field_name] = array(
                        'config'    => $old_config[$field_name],
                        'truncate'  => true
                    );
                }
            } else if ( isset($new_ids[$field_name]) && in_array($new_ids[$field_name], $old_ids, true) ) {
            //field name has changed
                $field = array_search($new_ids[$field_name], $old_ids, true);
                if ( array_key_exists($field, $old_rel) ) {
                    $old_module = $old_rel[$field];
                }

                //field name has changed, check if relation type changed
                $changed_fields[$field] = array(
                    'config'    => $new_config[$field_name],
                    'field_name'=> $field_name
                );
                if ($old_config[$field]['type'] !== $new_config[$field_name]['type']) {
                    $changed_fields[$field]['truncate'] = true;
                }
            }

            if ($old_module === false) {
             // module relation added
                $rel_add[$field_name] = $new_config[$field_name];
                if (  ! in_array($module, $nomodify_tables, true) ) {
                    $add_tables[] = $module;
                }
                self::array_value_delete($module, $delete_tables);
            } else if ($old_module !== $module) {
             // module relation changed to another module
                $rel_add[$field_name] = $new_config[$field_name];
                if (  ! in_array($module, $nomodify_tables, true) ) {
                    $add_tables[] = $module;
                }

                if ( array_key_exists($field_name, $old_config) ) {
                    $rel_delete[$field_name] = $old_config[$field_name];
                    if (!in_array($old_module, $add_tables, true) &&
                        !in_array($old_module, $nomodify_tables, true) &&
                        !in_array($old_module, $delete_tables, true) ) {
                        $delete_tables[] = $old_module;
                    }
                }
                self::array_value_delete($module, $delete_tables);
            } else {
             // module relation unmodified
                if (  ! in_array($module, $nomodify_tables, true) ) {
                    $nomodify_tables[] = $module;
                }
                self::array_value_delete($module, $add_tables);
                self::array_value_delete($module, $delete_tables);
            }
        }

        // compares old relations to new relations,
        // any modules found are checked with modules to add tables
        // and modules unmodified and, then, added to delete tables if
        // not found in those
        foreach ($old_rel as $field_name => $module) {
            if ( ! array_key_exists($field_name, $new_rel) ) {
                $rel_delete[$field_name] = $old_config[$field_name];
                if ( ! in_array($module, $add_tables, true) &&
                    ! in_array($module, $nomodify_tables, true) &&
                    ! in_array($module, $delete_tables, true) ) {
                    $delete_tables[] = $module;
                }
            }
        }

        return array(
            'rel_add'       => $rel_add,
            'rel_delete'    => $rel_delete,
            'add_tables'    => $add_tables,
            'delete_tables' => $delete_tables,
            'changed_fields'=> $changed_fields
        );
    }
	

	/**
	 * is_called_by
	 *
	 * Checks a function's calling class and method to insure the call comes from this class.
	 * 
	 * @access private
	 * @param string $function The method name from this class
	 * @return bool True if called by valid function of this class
	 */
	private function is_called_by($function) {
		$caller = debug_backtrace();
		$caller = $caller[2];
		return $caller['function'] === $function && $caller['class'] === get_class();
	}


    /**
     * render_form_field
     *
     * Creates a select menu HTML for a module's form fields.
     *
     * @access private
     * @param string $field_name The name of the form field
     * @param int $module_id The module ID to retrieve its form fields
     * @param string $value The value for the form field
     * @param \App\User\Permission $permission The current CMS user Permission object
     * @return string The form field select HTML
     *
     */
    private function render_form_field_select($field_name, $module_id, $value, $permission) {
        if ($permission instanceof \App\User\Permission === false ) {
            $message = 'Invalid param $permission: must be instance of \\App\\User\\Permission';
            throw new AppException($message, AppException::ERROR_FATAL);
        } else if ( empty($field_name) ) {
            return '';
        }

        $form_fields = array();
        if ( ! empty($module_id) ) {
            $data = $this->get_data($module_id);
            if ( ! empty($data) ) {
                $form_fields = $data['form_fields'];
            }
        }

        $values = array();
        foreach ($form_fields as $data) {
            $field_type = key($data['field_type']);
            if ( ! empty($data['is_model']) && in_array($field_type, array('countries', 'regions', 'select', 'text') ) ) {
                $values[ $data['name'] ] = $data['label'];
            }
        }
        asort($values);
        $values = array('' => '-- Select --') + $values;

        $params['name'] = $field_name;
        $params['value'] = $value;
        $params['values'] = $values;
        $params['use_template_vars'] = false;
        $params['is_readonly'] = $permission->has_add() === false && $permission->has_update() === false;
        return form_select($params);
    }
}

/* End of file Module.php */
/* Location: ./App/Module/Module.php */