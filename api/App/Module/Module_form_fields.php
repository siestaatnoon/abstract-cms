<?php

namespace App\Module;

use App\Exception\AppException;
use
App\Model\Relation;

/**
 * Module_form_fields class
 * 
 * Subclass of \App\Module\Abstract_module, this class provides custom definitions for 
 * validation, serializing and converting CMS form fields data to database row data and vce versa.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Module
 */
class Module_form_fields extends \App\Module\Abstract_module {

	/**
     * @var array Assoc array used to convert database values to form field values. 
     * Keys are database fields which are not used in corresponding form
     */
	protected $form_data = array(
		'field_type' => array(
			'_vals' 						=> array('function' => 'current'),
			'field_type_type' 				=> array('index' => 'field_type', 'function' => 'key'),
            'field_type_value_select'       => array('index' => 'value_select'),
			'field_type_content' 			=> array('index' => 'content'),
			'field_type_placeholder' 		=> array('index' => 'placeholder'),
			'field_type_is_inline' 			=> array('index' => 'is_inline'),
            'field_type_ct_attr' 		    => array('index' => 'ct_attr'),
			'field_type_attributes' 		=> array('index' => 'attr'),
			'field_type_values' 			=> array('index' => 'values'),
			'field_type_config_file' 		=> array('index' => 'config_file'),
			'field_type_config_key' 		=> array('index' => 'config_key'),
			'field_type_dir' 				=> array('index' => 'dir'),
            'field_type_filename_only' 		=> array('index' => 'filename_only'),
            'field_type_show_ext' 		    => array('index' => 'show_ext'),
			'field_type_module' 			=> array('index' => 'module'),
	//ADD?	'field_type_lang_config' 		=> array('index' => 'lang_config'),
			'field_type_format' 			=> array('index' => 'format'),
			'field_type_is_24hr' 			=> array('index' => 'is_24hr'),
			'field_type_upload_config' 		=> array('index' => 'config_name'),
			'field_type_config_mce' 		=> array('index' => 'config_mce'),
			'field_type_name_label' 		=> array('index' => 'name_label'),
			'field_type_value_label' 		=> array('index' => 'value_label'),
			'field_type_max_items' 			=> array('index' => 'max_items'),
			'field_type_value_required'	 	=> array('index' => 'value_required'),
			'field_type_open_on_init' 		=> array('index' => 'open_on_init'),
			'field_type_sort' 			    => array('index' => 'sort'),
			'field_type_relation_name' 		=> array('index' => 'module'),
			'field_type_relation_type' 		=> array('index' => 'type'),
            'field_type_is_json' 			=> array('index' => 'is_json'),
			'field_type_is_custom' 			=> array('index' => 'is_custom'),
			'field_type_is_ajax' 			=> array('index' => 'is_ajax'),
			'field_type_html' 				=> array('index' => 'html'),
            'field_type_template' 			=> array('index' => 'template')
		),
		'data_type' => array(
			'data_type_type' 				=> array('index' => 'type'),
			'data_type_length' 				=> array('index' => 'length'),
			'data_type_values' 				=> array('index' => 'values')
		)
	);
	
	/**
     * @var array Assoc array used to convert form field values to database values. 
     * Keys are database fields which are not used in corresponding form
     */
	protected $row_data = array(
		'field_type' => array(
            'value_select' 	=> NULL,	//+ grouped options for select, checkbox, radio field types
            'ct_attr' 		=> NULL,	//+ form field container attributes, including data-XXX
			'attr' 			=> NULL,	//+ form field attributes, including data-XXX
			'config_file' 	=> NULL,	//+ config file in ./App/Config dir
			'config_key' 	=> NULL,	//+ key of $config var in config file
			'values' 		=> NULL,	//+ assoc array of value => label for radio, checkbox and select values
			'dir' 			=> NULL,	//+ directory name, where file contents populate radio, checkbox, select values
            'filename_only' => NULL,	//+ show filename only for directory file names instead of with path
            'show_ext'      => NULL,	//+ show file extension for directory file names
			'module' 		=> NULL,	//+ relation module name
			'type' 			=> NULL,	// + relation type
			'name_label' 	=> NULL,	//+ Name-Value Pairs widget name field label
			'value_label' 	=> NULL,	//+ Name-Value Pairs/Values widget value field label
			'max_items' 	=> NULL,	//+ Name-Value Pairs/Values widget max number of items
			'value_required'=> NULL,	//+ Name-Value Pairs/Values widget value field required?
			'open_on_init' 	=> NULL,	//+ Name-Value Pairs/Values widget open the widget on form show?
			'sort' 		    => NULL,	//+ Name-Value Pairs/Values widget, chackbox, radio, select allow sorting/sort lists?
			'format' 		=> NULL,	//+ Date format (see PHP date() function)
			'is_24hr' 		=> NULL,	//+ 24 hour format for [time] field type
			'lang_config' 	=> NULL,	//+ Lang file in ./App/Lang that populate radio, checkbox and select values
			'config_name' 	=> NULL,	//+ The image/file upload config name in ./App/Config/uploads.php
			'config_mce' 	=> NULL,	//+ TinyMCE configuration name
			'content' 		=> NULL,	//+ Text/HTML content for info field type
			'placeholder' 	=> NULL,	//+ Text for placeholder attribute of form fields
			'is_inline' 	=> NULL,	//+ True or false to display checkbox/radio fields horizontally
            'is_json'    	=> NULL,	//+ True or false if hidden field storing JSON string
			'is_custom' 	=> NULL,	//+ True or false if custom field
			'is_ajax' 		=> NULL,	//+ True or false to load custom field by AJAX
            'template' 		=> NULL,	//+ Custom field template
			'html' 			=> NULL		//+ Custom field HTML
		),
		'data_type' => array(
			'type' 		=> NULL,		//+ MySQL data type (e.g. VARCHAR, INT)
			'length' 	=> NULL,		//+ Optional field length
			'values' 	=> NULL			//+ Array of values for enumeration field type
		)
	);
	
	
	/**
	 * Constructor
	 *
	 * Initializes the Module.
	 * 
	 * @access public
     * @throws \App\Exception\AppException if an error occurs while loading module, rethrown and
     * handled by \App\App class
	 */
	public function __construct() {
	    try {
            parent::__construct('form_fields');
            $this->App->load_util('form_fields');
        } catch (AppException $e) {
	        throw $e;
        }
	}
	
	
	/**
	 * form_field_field_type_module
	 *
	 * Creates the custom form select field HTML for a module list dropdown.
	 * 
	 * @access public
	 * @param mixed $user_id The user ID (int) or empty if not used
	 * @param mixed $value The value of the field
	 * @param \App\User\Permission $permission The current CMS user Permission object
	 * @return string The module permissions form fields HTML
     * @throws \App\Exception\AppException if $permission parameter invalid
	 */
	public function form_field_field_type_module($ff_id, $value, $permission, $params=array()) {
		if ($permission instanceof \App\User\Permission === false ) {
            $msg_part = error_str('error.param.type', array('$permission', '\\App\\User\\Permission'));
            $message = error_str('error.type.param.invalid', $msg_part);
			throw new AppException($message, AppException::ERROR_FATAL);
		}

		$modules = self::$MODULES;
		$values = array();
		foreach ($modules as $name => $data) {
            if ( ! empty($data['use_model']) ) { // module cannot be of "options" type
                $values[$name] = $data['label_plural'];
            }
		}
		$values = array('' => '-- Select --') + $values;

		$params['name'] = 'field_type_module';
		$params['value'] = $value;
		$params['values'] = $values;
		$params['use_template_vars'] = false;
		$params['is_readonly'] = $permission->has_add() === false && $permission->has_update() === false;
		return form_select($params);
	}


    /**
     * form_field_field_type_relation_name
     *
     * Creates the custom form select field HTML for a module list dropdown to select a relation module.
     *
     * @access public
     * @param mixed $ff_id The form field ID (int) or empty if not used
     * @param mixed $value The value of the field
     * @param \App\User\Permission $permission The current CMS user Permission object
     * @return string The module permissions form fields HTML
     * @throws \App\Exception\AppException if $permission parameter invalid
     */
    public function form_field_field_type_relation_name($ff_id, $value, $permission, $params=array()) {
        if ($permission instanceof \App\User\Permission === false ) {
            $msg_part = error_str('error.param.type', array('$permission', '\\App\\User\\Permission'));
            $message = error_str('error.type.param.invalid', $msg_part);
            throw new AppException($message, AppException::ERROR_FATAL);
        }

        $modules = self::$MODULES;
        $core_modules = Module::get_core_modules(false);
        $values = array();
        $option_attr = array();
        foreach ($modules as $name => $data) {
            $relations = $data['field_data']['relations'];
            $op_attr = array();
            foreach ($relations as $rel_params) {
                if ($rel_params['type'] === Relation::RELATION_TYPE_1N) {
                    $op_attr['data-has-1n'] = 1;
                    break;
                }
            }
            if ( in_array($name, $core_modules) ) {
                $op_attr['data-is-core'] = 1;
            }
            if ( ! empty($data['use_model']) ) {
            // module cannot be of "options" type
                $values[$name] = $data['label_plural'];
            }
            if ( ! empty($op_attr) ) {
                $option_attr[$name] = $op_attr;
            }
        }
        $values = array('' => '-- Select --') + $values;
        if ( ! empty($option_attr) ) {
            $params['option_attr'] = $option_attr;
        }

        $params['name'] = 'field_type_relation_name';
        $params['value'] = $value;
        $params['values'] = $values;
        $params['use_template_vars'] = false;
        $params['is_readonly'] = $permission->has_add() === false && $permission->has_update() === false;
        return form_select($params);
    }
	
	
	/**
	 * module_form_data_to_row
	 *
	 * Converts CMS form data to an assoc array to INSERT/UPDATE a module table row. Removes
	 * any indexes that are not a module field and adds any fields with a default value if
	 * missing.
	 * 
	 * @access protected
	 * @param array $data The form field data from CMS form
	 * @return array The data converted to an assoc array for form_fields table row
	 */
	protected function module_form_data_to_row($data) {
		if ( empty($data) ) {
			return $data;
		}
		
		$row_obj = $this->row_data;
		$form_data = $this->form_data;
		$keys = array();
		
		//create an array without keys that do not correspond to module table fields
		$row = array_intersect_key($data, parent::$MODULES['form_fields']['field_data']['model']);
        $row['field_id'] = empty($data['field_id']) ? NULL : $data['field_id'];
		$row['name'] = parent::safe_module_name( trim($row['name']) );
		
		foreach ($form_data as $key => $fields) {
			if ( ! empty($fields['_vals']) ) {
				unset($fields['_vals']);
			}
			
			foreach ($fields as $field => $params) {
				$index = $params['index'];
				if ($index === $key) {
					if ( ! empty($data[$field]) ) {
						$keys[$key] = $data[$field];
					}
				} else if ( isset($data[$field]) && $data[$field] !== '' && $data[$field] !== array() ) {
					if ( isset($params['if']) ) {
						$if = $params['if'];
						$to_check = key($if);
						$vals = current($if);
						if ( isset($data[$to_check]) && in_array($data[$to_check], $vals) ) {
							$row_obj[$key][$index] = $data[$field];
						}
					} else if ( isset($params['not']) ) {
						$not = $params['not'];
						$to_check = key($not);
						$vals = current($not);
						if ( isset($data[$to_check]) && in_array($data[$to_check], $vals) === false ) {
							$row_obj[$key][$index] = $data[$field];
						}
					} else {
						$row_obj[$key][$index] = $data[$field];
					}
				}
			}
		}

		foreach ($row_obj as $index => $vals) {
			foreach ($vals as $name => $val) {
				if ($val === NULL) {
					unset($row_obj[$index][$name]);
				}
			}
			if ( isset($keys[$index]) ) {
				$row_obj[$index] = array($keys[$index] => $row_obj[$index]);
			}
		}
	
		$data = $row_obj + $data;
        $field_type = key($data['field_type']);
        $type_data = $data['field_type'][$field_type];
        $use_module_model = ! empty($data['module_pk']);

        // Below cleans up the data attached to the form field type,
        // not 100% necessary but saves some space in the db and can
        // help when debugging

        // determine default field array value (with text data type) based on field type
        $rel_type = isset($type_data['type']) ? $type_data['type'] : false;
        $is_relation = $field_type === 'relation' && ! empty($rel_type);
        $multi_types = array('checkbox', 'file', 'image', 'multiselect', 'name_value_widget', 'values_widget');
        if ( $use_module_model && ( in_array($field_type, $multi_types) || $is_relation) ) {
            $data['default'] = $is_relation && $rel_type === Relation::RELATION_TYPE_N1 ? '' : array();
            $data['data_type'] = $is_relation ? array() : array('type' => 'text');
        }

        //make sure date/time fields have proper data types and NULL defaults
        if ( $use_module_model && in_array($field_type, array('date', 'time') ) ) {
            $data['default'] = NULL;
            $data['data_type'] = array('type' => $field_type);
        }

        //remove unused field boolean parameters
        if ( ! in_array($field_type, array('checkbox', 'radio', 'select', 'name_value_widget', 'values_widget') ) ) {
            unset($type_data['sort']);
        }
        if ( ! in_array($field_type, array('name_value_widget', 'values_widget') ) ) {
            unset($type_data['value_required']);
            unset($type_data['open_on_init']);
        }
        if ( ! in_array($field_type, array('checkbox', 'radio') ) ) {
            unset($type_data['is_inline']);
        }
        if ( ! in_array($field_type, array('custom', 'relation') ) ) {
            unset($type_data['is_custom']);
            unset($type_data['is_ajax']);
        }
        if ($field_type !== 'hidden') {
            unset($type_data['is_json']);
        }
        if ($field_type !== 'time') {
            unset($type_data['is_24hr']);
        }
        if ( ! isset($type_data['dir']) ) {
            unset($type_data['filename_only']);
            unset($type_data['show_ext']);
        }

        // if custom or relation type uses AJAX, unset template/html options
        if ( ! empty($type_data['is_ajax']) ) {
            unset($type_data['template']);
            unset($type_data['html']);
        }

        // if is relation and of type 1:n, make sure module is not core
        // or module with 1:n relation already, otherwise change type to n:1
        if ($field_type === 'relation') {
            $rel_module = $type_data['module'];
            $type = $type_data['type'];
            $has_invalid = false;
            if ($type === Relation::RELATION_TYPE_1N) {
                if ( in_array($rel_module, Module::$CORE_MODULES) ) {
                    $has_invalid = true;
                } else {
                    $rel_mod_rel = self::$MODULES[$rel_module]['field_data']['relations'];
                    foreach ($rel_mod_rel as $params2) {
                        if ($params2['type'] === Relation::RELATION_TYPE_1N) {
                            $has_invalid = true;
                            break;
                        }
                    }
                }
            }
            if ($has_invalid) {
                $type_data['type'] = Relation::RELATION_TYPE_N1;
            }
        }

        $data['field_type'][$field_type] = $type_data;

        //if field type is info then default attributes need to be set
        if ($field_type === 'info') {
            $data['is_model'] = 0;
            $data['data_type'] = array();
            $data['validation'] = array();
            $data['default'] = '';
            $data['tooltip'] = '';
            $data['tooltip_lang'] = '';
            $data['is_list_col'] = false;
            $data['is_filter'] = false;
        }

        // If module an "options" type then default attributes need to be set,
        // Note that if form field is to a module being created, this is done
        // in the Module::create() method
        //
        // For  now, the only way to check if the form field's module is an
        // "options" type is if the "module_pk" parameter is empty... not the
        // best but not another way at the moment
        $mod_name = $data['module'];
        if ( isset(self::$MODULES[$mod_name]) ) {
            $module = self::$MODULES[$mod_name];
            if ( isset($module['use_model']) && empty($module['use_model']) ) {
                $data['module_pk'] = '';
                $data['data_type'] = array(); //option fields are already a set data type
                if ($field_type === 'relation') {
                    //option module fields cannot be relations for now,
                    //if set then change to text field type
                    unset($data['field_type'][$field_type]);
                    $data['field_type']['text'] = array();
                }
            }
        }

        // if form field nota model field, set data type empty
        if ( empty($data['is_model']) ) {
            $data['data_type'] = array();
        }

		return $data;
	}
	
	
	/**
	 * row_to_module_form_data
	 *
	 * Converts a module table row into an assoc array of data used for the CMS form 
	 * to add or update a module object. 
	 * 
	 * @access protected
	 * @param array $data A module row
	 * @return array The row converted to CMS form data
	 */
	protected function row_to_module_form_data($data) {
		if ( empty($data) ) {
			return $data; 
		}

		$defaults = parent::$MODULES['form_fields']['field_data']['defaults'];
		$form_data = $this->form_data;
		
		foreach ($form_data as $key => $fields) {
			if ( empty($data[$key]) ){
				continue;
			} 
			
			$row_vals = array();
			if ( ! empty($fields['_vals']['function']) ) {
				$function = $fields['_vals']['function'];
				$row_vals = $function($data[$key]);
				unset($fields['_vals']);
			} else {
				$row_vals = $data[$key];
			}
			
			foreach ($fields as $field => $params) {
				$index = $params['index'];
				$value = isset($row_vals[$index]) ? 
						 $row_vals[$index] : 
						 ($key === $index ? $data[$index] : $defaults[$field]);
				if ( ! empty($value) ) {
					if ( isset($params['function']) ) {
						$value = $params['function']($value);
					} else if ( isset($params['if']) ) {
						$if = $params['if'];
						$to_check = key($if);
						$vals = current($if);
						if ( isset($data[$to_check]) && in_array($data[$to_check], $vals) === false ) {
							$value = $defaults[$field];
						}
					} else if ( isset($params['not']) ) {
						$not = $params['not'];
						$to_check = key($not);
						$vals = current($not);
						if ( isset($data[$to_check]) && in_array($data[$to_check], $vals) ) {
							$value = $defaults[$field];
						}
					}
				}
				$data[$field] = $value;
			}
			
			unset($data[$key]);
		}
		
		return $data;
	}
	
	
	/**
	 * validate
	 *
	 * Checks the given assoc array of form data for necessary indeces. 
	 * 
	 * @access protected
	 * @param array $data The form field form data as an assoc array
	 * @param boolean $has_id True if $data param contains a non-empty id for the form field row
	 * @return mixed True if row data validated or an array of validation errors
     * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
	 */
	protected function validate($data, $has_id=false) {
		$field_types = $this->App->load_config('field_types');
        $field_types = $field_types['field_types'];
        $reserved_names = $this->model->get_reserved_fields();
        $errors = array();

        $field_name = empty($data['name']) ? '' : $data['name'];
        if ( empty($data['name']) ) {
            $errors[] = error_str('error.form_field.name', '$data[name]');
        } else if ( in_array($field_name, $reserved_names) ) {
            $errors[] = error_str('error.form_field.reserved', '$data[name]');
        } else if ($field_name === $data['module_pk']) {
            $errors[] = error_str('error.form_field.pk_dupe', '$data[name]');
        }

		if ($has_id) {
			if ( empty($data['field_id']) ) {
                $errors[] = error_str('error.form_field.id', '$data[field_id]');
			} else {
				$old_field = parent::get_data($data['field_id'], false, true);
				if ( empty($old_field) ) {
                    $errors[] = error_str('error.general.missing', 'ID: '.$data['field_id']);
				} 
			}
		}
		if ( empty($data['label']) && empty($data['lang']) &&
            ! in_array($data['field_type_type'], array('hidden', 'info', 'object') ) ) {
            $errors[] = error_str('error.form_field.label', array('$data[label]', '$data[lang]'));
		}

		if ( empty($data['field_type_type']) ) {
            $errors[] = error_str('error.general.set', '$data[field_type_type]');
		} else if ( ! isset($field_types[ $data['field_type_type'] ]) ) {
            $args = array(
                '$data[field_type_type] "'.$data['field_type_type'].'"',
                implode(', ', $field_types)
            );
            $errors[] = error_str('error.form_field.type', $args);
        }
		if ( empty($data['module']) ) {
            $errors[] = error_str('error.form_field.slug', '$data[module]');
		}
		/*
        if ( empty($data['module_pk']) ) {
            $errors[] = 'Form field ['.$data['name'].'] param [module_pk] empty, must be name of primary key field of module for field';
        }
		if ( ! empty($data['is_model']) && empty($data['data_type_type']) && $data['field_type_type'] !== 'relation' ) {
			$errors[] = 'Form field ['.$data['name'].'] param [data_type_type] empty, must be MySQL data type';
		}
		*/
		
		return empty($errors) ? true : $errors;
	}

}

/* End of file Module_form_fields.php */
/* Location: ./App/Module/Module_form_fields.php */