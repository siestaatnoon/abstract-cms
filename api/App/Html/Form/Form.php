<?php

namespace App\Html\Form;

use 
App\App,
App\Exception\AppException,
App\Html\Form\Field\Form_field,
App\Model\Model,
App\Model\Relation,
App\Module\Module;

/**
 * Form class
 * 
 * This class creates an HTML form utilizing the Bootstrap framework and formatted for values to be 
 * populated with the Underscore javascript library's template() function. This class can be used 
 * for forms in the CMS as well as the frontend.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Html\Form
 */
class Form {
	
	/**
     * @var string Reserved field name holding uploaded file information for
     * upload image/file fields
     */
	protected static $FIELD_UPLOADS_INFO = 'uploads';
	
	/**
     * @var \App\App Instance of the main App class
     */
	protected $App;
	
	/**
     * @var array List of CSS files to include for the form
     */
	protected $css_includes;
	
	/**
     * @var array Array of \App\Html\Form\Field\Field form label/field configurations
     * of each form field
     */
	protected $fields;
	
	/**
     * @var array Array of name => value attributes for form tag
     */
	protected $form_attr;
	
	/**
     * @var mixed A string or array of class attributes for form tag
     */
	protected $form_class;
	
	/**
     * @var array Array of name => value for data attributes in form tag
     */
	protected $form_data;
	
	/**
     * @var string The form id attribute
     */
	protected $form_id;
	
	/**
     * @var array Array of hidden fields of the form
     */	
	protected $hidden_fields;
	
	/**
     * @var bool True if this form is used in the CMS
     */	
	protected $is_cms;
	
	/**
     * @var bool True if this form uses the Bootstrap horizontal layout
     */	
	protected $is_horizontal;
	
	/**
     * @var bool True if this form's fields are readonly
     */	
	protected $is_readonly;
	
	/**
     * @var bool True if form is for relation data of main form, only valid for CMS
     */	
	protected $is_relation;
	
	/**
     * @var array List of javascript files to include for the form
     */
	protected $js_includes;
	
	/**
     * @var string Javascript code to run on document onload
     */	
	protected $js_load_block;
	
	/**
     * @var string Javascript code to run on document unload
     */	
	protected $js_unload_block;
	
	/**
     * @var string Current language of application (e.g. en, es)
     */	
	protected $lang;
	
	/**
     * @var string Name of the CMS module or frontend form
     */	
	protected $module_name;
	
	/**
     * @var array Array of \App\Model\Relation used to populate select, multiselect
     * or relational subforms
     */	
	protected $relations;
	
	/**
     * @var array Array of relational data forms (string) of the main form (CMS only)
     */	
	protected $subforms;
	
	/**
     * @var string Title header for the form
     */	
	protected $title;
	
	/**
     * @var bool Flag to us delete button in form
     */	
	protected $use_delete;
	
	
	/**
	 * Constructor
	 *
	 * Initializes the Form with the given configuration parameters in assoc array:<br/><br/>
	 * <ul>
	 * <li>module_name => The module creating this form</li>
	 * <li>attr => Array of name => value attributes to add to form tag</li>
	 * <li>class => String or array of class attributes to add to form tag</li>
	 * <li>data => Array of data-[name] => value attributes to add to form tag, 
	 * valid JSON strings can be used as well. Note that array keys do not have
	 * to be prefixed with "data-" but are accepted if so.</li>
	 * <li>fields => Array of \App\Html\Form\Field\Form_field containing configuration for form fields</li>
	 * <li>is_cms => True if form is a CMS module form</li>
	 * <li>is_horizontal => True to use Bootstrap horizontal form layout, false for default layout</li>
     * <li>is_readonly => True if form is readonly (disallows edits)</li>
	 * <li>is_relation => True if form is for relation data of main form, only valid for CMS</li>
	 * <li>redirect => URL fragment to route to after form submitted</li>
	 * <li>title => The title, appearing above the form</li>
	 * <li>use_delete => True to add delete button to form (default false)</li>
	 * </ul>
	 * 
	 * @access public
	 * @param array $config The form configuration array
	 * @throws \App\Exception\AppException if $config assoc array missing required parameters
	 * @see \App\Html\Form\Field\Form_field for form field object structure
	 */
	public function __construct($config) {
		$this->App = App::get_instance();
		$errors = array();
		
		if ( empty($config['module_name']) ) {
			$errors[] = error_str('error.module.slug', array('$config[module_name]'));
		}
		if ( empty($config['fields']) || ! is_array($config['fields']) ) {
			$errors[] = error_str('error.param.array', array('$config[fields]', '\\App\\Form\\Field\\Form_field'));
		}
		if ( ! empty($errors) ) {
		    $message = error_str('error.type.param.invalid', array('(array) $config: '));
			$message .= implode(",\n", $errors);
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$locale = $this->App->config('locale');
		$parts = explode('_', $locale);
		$this->locale = empty($parts[0]) ? 'en' : $parts[0];

		$this->module_name = $config['module_name'];
		$this->fields = $config['fields'];
		$this->title = empty($config['title']) ? '' : $config['title'];
		$this->is_cms = ! empty($config['is_cms']);
		$this->is_horizontal = ! empty($config['is_horizontal']);
		$this->is_readonly = ! empty($config['is_readonly']);
		$this->is_relation = $this->is_cms && ! empty($config['is_relation']);
		$this->form_attr = empty($config['attr']) ? array() : $config['attr'];
		$this->form_class = empty($config['class']) ? array() : $config['class'];
		$this->form_data = empty($config['data']) ? array() : $config['data'];
		$this->form_id = 'form-'.$this->module_name;
		$this->use_delete = empty($config['use_delete']) ? false : $config['use_delete'];
		$this->css_includes = array();
		$this->js_includes = array();
		$this->js_load_block = '';
		$this->js_unload_block = '';
		$this->redirect = empty($config['redirect']) ? '' : $config['redirect'];
		$this->subforms = array();
	}
	
	
	/**
	 * entity_decode
	 *
	 * Converts special characters as HTML entities back into their string values. Note 
	 * that if an array is passed as a parameter, this function will recursively escape the
	 * values for multidimensional arrays.
	 * 
	 * @access public
	 * @param mixed $mixed A string or array of values to convert special chars
	 * @return mixed The converted string or array param
	 */
	public static function entity_decode($mixed) {
		$is_array = true;
		if ( empty($mixed) ) {
			return $mixed;
		} else if ( ! is_array($mixed) ) {
			$mixed = array($mixed);
			$is_array = false;
		}

		foreach($mixed as &$item) {
			$item = is_array($item) ? self::entity_decode($item) : htmlspecialchars_decode($item, ENT_QUOTES);
		}
		
		return $is_array ? $mixed : $mixed[0];
	}
	

	/**
	 * entity_values
	 *
	 * Converts special characters in string values to HTML entities. Note that if
	 * an array is passed as a parameter, this function will recursively escape the
	 * values for multidimensional arrays. Note that double quotes are not escaped 
	 * while single quotes are escaped for ease of use use with JSON, AJAX and jQuery.
	 * 
	 * @access public
	 * @param mixed $mixed A string or array of values to convert special chars
	 * @return mixed The converted string or array param
	 */
	public static function entity_values($mixed) {
		$is_array = true;
		if ( empty($mixed) ) {
			return $mixed;
		} else if ( ! is_array($mixed) ) {
			$mixed = array($mixed);
			$is_array = false;
		}

		foreach($mixed as &$item) {
			$item = is_array($item) ? 
					self::entity_values($item) : 
					htmlspecialchars($item, ENT_QUOTES & ~ENT_COMPAT, 'UTF-8', false);
		}
		
		return $is_array ? $mixed : $mixed[0];
	}
	
	
	/**
	 * escape_single_quotes
	 *
	 * Since some form field values may contain JSON data in the form value='[{...}]', single
	 * quotes may pose an issue when rendering the field on the page. This converts all single
	 * quotes into ASCII hex \u0027.
	 * 
	 * @access public
	 * @param mixed $mixed A string or array of values to convert single quotes
	 * @return mixed The converted string or array param
	 */
	public static function escape_single_quotes($mixed) {
		$is_array = true;
		if ( empty($mixed) ) {
			return $mixed;
		} else if ( ! is_array($mixed) ) {
			$mixed = array($mixed);
			$is_array = false;
		}

		foreach($mixed as &$item) {
			$item = is_array($item) ? self::escape_single_quotes($item) : str_replace("'", '\u0027', $item);
		}
		
		return $is_array ? $mixed : $mixed[0];
	}


	/**
	 * form_data_values
	 *
	 * Sanitizes, trims and converts (if necessary) form values of a module. Also
	 * eliminates extraneous input variables submitted. Note that the id field
	 * of the Backbone form is converted to it's respective primary key.
	 *
	 * @access public
	 * @param string $module_name The module name
	 * @param array $data The form data as an assoc array
	 * @return array The sanitized and converted form values
	 */
	public static function form_data_values($module_name, $data) {
		$module = new Module($module_name);
		$relations = $module->get_relations();
		$module_data = $module->get_module_data();
		$field_data = $module_data['field_data'];
		$field_types = $field_data['field_type'];
		$defaults = $field_data['defaults'];
        $use_model = ! empty($module_data['use_model']);
		$do_strip_tags = App::get_instance()->config('strip_post_tags');

		$form_vals = array();

		//set primary key field to "id" value from Backbone model or primary key field
        if ($use_model) {
            $pk_field = empty($module_data['pk_field']) ? 'id' : $module_data['pk_field'];
            $form_vals[$pk_field] = empty($data['id']) ? (empty($data[$pk_field]) ? '' : $data[$pk_field]) : $data['id'];
        }

        //build assoc array of field name => modules for relations for easier access
        $rel_fields = array();
        $rel_config = empty($field_data['relations']) ? array() : $field_data['relations'];
        foreach ($rel_config as $field => $rc) {
            $rel_fields[$field] = $rc['module'];
        }

		foreach ($defaults as $field => $default_val) {
			if ($field === self::$FIELD_UPLOADS_INFO) {
			//uploaded file info, not saved as data
				continue;
			}

			$val = $default_val;
			$field_type = empty($field_types[$field]) ? '' : $field_types[$field];

			if ( isset($data[$field]) ) {
				$val = is_array($data[$field]) ? $data[$field] : trim($data[$field]);

				if ( self::is_json_obj($val) ) {
				//check for JSON object and convert to PHP array
					$val = json_decode($val, true);
				}

				if ($field_type === 'name_value_widget') {
					if (is_array($val) ) {
					//convert numeric array of label => value to assoc
						$aux = array();
						foreach ($val as $arr) {
							$k = self::unescape_single_quotes( current($arr) );
							$v = self::unescape_single_quotes( key($arr) );
							$aux[$k] = $v;
						}
						$val = $aux;
					} else {
						$val = $default_val;
					}
				} else if ($field_type === 'values_widget' && is_array($val) ) {
				//convert ASCII hex single quotes to single quote
					foreach ($val as &$av) {
						$av = self::unescape_single_quotes($av);
					}
				} else if ($field_type === 'time') {
				//convert time to mysql format
					$parts = empty($val) ? array() : explode(':', $val);
					if ( count($parts) > 1 ) {
						$hour = (int) $parts[0];
						$min = $parts[1];
						if ( stripos($min, 'pm') !== false && $hour < 12 ) {
							$hour += 12;
						} else if ( stripos($min, 'am') !== false && $hour === 12 ) {
							$hour = "00";
						} else if ($hour < 10) {
							$hour = "0".$hour;
						}
						$min = str_replace( array(' ', 'a', 'p', 'm'), '', strtolower($min) );
						$val = $hour.":".$min.":00";
					} else {
						$val = NULL;
					}
				} else if ($field_type === 'relation') {
                    $rel_module = isset($rel_fields[$field]) ? $rel_fields[$field] : '';
                    $relation = isset($relations[$field]) ? $relations[$field] : array();
					if ( ! empty($relation) &&
						$relation->get_property('relation_type') === Relation::RELATION_TYPE_1N ) {
						if ( is_array($val) )  {
						//1:n relational form data, recursively convert data
							foreach ($val as &$v) {
								$v = self::form_data_values($rel_module, $v);

								//single quotes that were escaped when rendering
								//form are return back to single quotes
								$v = self::unescape_single_quotes($v);
							}
						} else {
							$val = self::form_data_values($rel_module, $val);

							//single quotes that were escaped when rendering
							//form are return back to single quotes
							$val = self::unescape_single_quotes($val);
						}
						$do_strip_tags = false;
					}
				} else if ($field_type === 'editor') {
					$do_strip_tags = false;
				}

				if ($do_strip_tags) {
				//strip tags from input, EXCEPT for TinyMCE editor field
					$val = self::sanitize_input($val);
				}

				//convert HTML entities to string chars
				$val = self::entity_decode($val);

				if ($val === false) {
				//failed sanitization so revert to default value
					$val = $default_val;
				}
			}

			$form_vals[$field] = $val;
		}

        if ($use_model) {
            $active_field = Model::MODEL_ACTIVE_FIELD;
            $archive_field = Model::MODEL_ARCHIVE_FIELD;
            if ( ! empty($module_data['use_active'])) {
                $form_vals[$active_field] = isset($data[$active_field]) ? $data[$active_field] : 1;
            }
            if ( ! empty($module_data['use_archive'])) {
                $form_vals[$archive_field] = isset($data[$archive_field]) ? $data[$archive_field] : 0;
            }
        }

		return $form_vals;
	}


	/**
	 * form_field_values
	 *
	 * Converts database field data into form field values according to field type.
	 *
	 * @access public
	 * @param string $module_name The module name
	 * @param array $data The field data as an assoc array
	 * @return array The data to be used by the form
	 */
	public static function form_field_values($module_name, $data) {
	    $App = App::get_instance();
		$module = new Module($module_name);
		$relations = $module->get_relations();
		$module_data = $module->get_module_data();
		$field_data = $module_data['field_data'];
		$field_types = $field_data['field_type'];
		$defaults = $field_data['defaults'];
        $use_model = ! empty($module_data['use_model']);
		$do_entities = true;
		$form_vals = array();

		//set Backbone model "id" field to primary key of data
        if ($use_model) {
            $pk_field = $module_data['pk_field'];
            $form_vals[$pk_field] = empty($data[$pk_field]) ? (empty($data['id']) ? '' : $data['id']) : $data[$pk_field];
        }

        //build assoc array of field name => modules for relations for easier access
        $rel_fields = array();
        $rel_config = empty($field_data['relations']) ? array() : $field_data['relations'];
        foreach ($rel_config as $field => $rc) {
            $rel_fields[$field] = $rc['module'];
        }

		foreach ($defaults as $field => $default_val) {
			if ($field === $pk_field) {
				continue;
			}
			$field_type = empty($field_types[$field]) ? '' : $field_types[$field];

			if ( isset($data[$field]) ) {
				$val = $data[$field];

				if ( in_array($field_type, array('image', 'file') ) ) {
				//add uploaded file information to each image/file upload field
				//
				//NOTE: if file does not exist, it will not be added to form upload
				//to prevent orphaned file data
					$uploads_cfg = $field_data[self::$FIELD_UPLOADS_INFO][$field];
					$config_name = empty($uploads_cfg['config_name']) ? false : $uploads_cfg['config_name'];
					$is_image = ! empty($uploads_cfg['is_image']);
					$uploads = array();
					if ( ! empty($val) && ! empty($config_name) ) {
						$is_multi = is_array($val);
						$vals = $is_multi ? $val : ( empty($val) ? array() : array($val) );
						foreach ($vals as $i => $fn) {
							$info = $App->fileinfo($config_name, $fn, $is_image);
							if ($info === false) {
								unset($vals[$i]);
							} else if ($is_multi) {
								$uploads[] = $info;
							} else {
								$uploads = $info;
							}
						}
                        $vals = array_values($vals);
						$val = $is_multi ? $vals : (isset($vals[0]) ? $vals[0] : '');
					}
					$form_vals[self::$FIELD_UPLOADS_INFO][$field] = $uploads;
				} else if ($field_type === 'name_value_widget') {
				//convert assoc array of value => label to numeric array of label => value
					if ( is_array($val) ) {
						$aux = array();
						foreach ($val as $v => $L) {
                            $L = self::escape_single_quotes($L);
						    if ( is_array($L) ) {
                                $L = implode(' ', $L);
                            }
							$aux[] = array( $L => self::escape_single_quotes($v) );
						}
						$val = $aux;
					} else {
						$val = $default_val;
					}
				} else if ($field_type === 'values_widget' && is_array($val) ) {
				//escape single quotes contained in array values
					foreach ($val as &$av) {
						$av = self::escape_single_quotes($av);
					}
				} else if ($field_type === 'time') {
				//convert time to 12 hour format for widget
					$parts = empty($val) ? array() : explode(':', $val);

					if ( count($parts) > 1 ) {
						$hour = (int) $parts[0];
						$min = $parts[1];
						$am_pm = " AM";
						if ($hour >= 12) {
							if ($hour > 12) {
								$hour -= 12;
							}
							$am_pm = " PM";
						}
						$val = ($hour < 10 ? "0".$hour : $hour).":".$min.$am_pm;
					} else {
						$val = '';
					}
				} else if ($field_type === 'relation') {
                    $rel_module = isset($rel_fields[$field]) ? $rel_fields[$field] : '';
					$relation = isset($relations[$field]) ? $relations[$field] : array();
					if ( ! empty($relation) &&
						$relation->get_property('relation_type') === Relation::RELATION_TYPE_1N ) {
						if ( is_array($val) )  {
                        // 1:n relational form data, recursively convert data
							foreach ($val as &$v) {
								$v = self::form_field_values($rel_module, $v);

								//single quotes cause issues since relational data
								//saved in an input value attribute so escape them here
								$v = self::escape_single_quotes($v);
							}
						} else {
							$val = self::form_field_values($rel_module, $val);

							//single quotes cause issues since relational data
							//saved in an input value attribute so escape them here
							$val = self::escape_single_quotes($val);
						}
						$do_entities = false;
					}
				} else if ($field_type === 'editor') {
				//entity escape all values except TinyMCE HTML content
					$do_entities = false;
				} else if ( is_bool($val)) {
				 // boolean values need to be zero or one
                    $val = $val ? 1 : 0;
                    $do_entities = false;
                }

				if ($do_entities) {
					//$val = self::entity_values($val);
				}
			} else {
				$val = $default_val;
			}

			$form_vals[$field] = $val;
		}

        if ($use_model) {
            $active_field = Model::MODEL_ACTIVE_FIELD;
            $archive_field = Model::MODEL_ARCHIVE_FIELD;
            if ( ! empty($module_data['use_active'])) {
                $form_vals[$active_field] = isset($data[$active_field]) ? $data[$active_field] : 1;
            }
            if ( ! empty($module_data['use_archive'])) {
                $form_vals[$archive_field] = isset($data[$archive_field]) ? $data[$archive_field] : 0;
            }
        }

		return $form_vals;
	}
	

	/**
	 * generate
	 *
	 * Builds the form HTML template to be used with Underscore.template() javascript function.
	 * Note that form values will be populated in the frontend javascript libs. Returns an
	 * associative array with the following indeces:<br/><br/>
	 * <ul>
	 * <li>form => The form HTML</li>
	 * <li>css_includes => Array of CSS stylesheets to include withthe form</li>
	 * <li>js_includes => Array of javascript files to include with the form</li>
	 * <li>js_load_block => Javascript code to execute on the form upon page load</li>
	 * <li>js_unload_block => Javascript code to destroy objects or perform other cleanup upon page unload</li>
	 * </ul>
	 * 
	 * @access public
	 * @return array Associative array containing the form HTML, CSS/javascript includes and 
	 * javascript onload/unload to excecute
	 */
	public function generate() {
		$module = Module::load($this->module_name);
		$module_data = $module->get_module_data();
        $module_name = $module_data['name'];
		$is_model = $module_data['use_model'] ? true : false;
		$pk_field = $is_model ? $module_data['pk_field'] : false;
		$is_main_cms = $this->is_cms && ! $this->is_relation;
		$is_relation_cms = $this->is_cms && $this->is_relation;
		$class = $this->class_attr();
		$attr = $this->attributes();
		$data = $this->data_attr();
		
		$form_start = '<form id="'.$this->form_id.'"'.$class.$attr.$data.' role="form">'."\n";
		$form_html = '';
		$hidden_html = '';
		$sidebar = '';
		$after_submit = '';
		$after_cancel = '';
		$after_delete = '';
		$validation = array();
		
		Form_field::set_id_prefix($this->module_name);
		Form_field::set_horizontal($this->is_horizontal);
		Form_field::set_readonly($this->is_readonly);
		
		if ($is_main_cms) {
            $form_start .= '<div id="form-main">'."\n";
		}
		
		//TODO: allow tag for title to be chosen
		//
		if ( ! empty($this->title) ) {
            $form_start .= '<h1>'.$this->title.'</h1>'."\n";
		}

		if ($pk_field !== false) {
			$cfg = array(
				'name'          => $pk_field,
                'module'        => $this->module_name,
                'pk_field'      => $pk_field,
				'field_type'    => array(
					'hidden' => array()
				)
				 
			);
			$field = new Form_field($cfg);
			$hidden_html .= $field->field_hidden_id($pk_field);
		}
		
		foreach ($this->fields as $field) {
			$field_name = $field->get_name();
			if ($field_name  === $pk_field) {
				continue;
			}
			$field_type = $field->get_type();
			if ( in_array($field_type, array('hidden', 'object') ) ) {
				$hidden_html .= $field->html();
			} else if ($field_type === 'relation') {
				$ff_rel = $field->html();
				if ( is_array($ff_rel) ) {
					$hidden_html .= $ff_rel['hidden'];
					$sidebar .= $ff_rel['sidebar'];
					$this->subforms[] = $ff_rel['subform'];
				} else {
					$form_html .= $ff_rel;
				}
			} else if ($field_type === 'button') {
				$html = $field->html();
				$after = $field->get_param('after');
				switch ($after) {
					case 'submit':
						$after_submit = $html;
						break;
					case 'cancel':
						$after_cancel = $html;
						break;
					case 'delete':
						$after_delete = $html;
						break;
					default: 
						$form_html .= $html;
						break;
				}
			} else {
				$form_html .= $field->html();
			}
			
			$ff_data = $field->get_data();
			$data = array();
			if ( ! empty($ff_data['validation']) ) {
                $data['valid'] = $ff_data['validation'];
			}
            if ( is_array($ff_data['default']) ) {
                $data['is_multiple'] = true;
            }
			$validation[$field_name] = $data;
		}

		if ($is_main_cms) {
			$form_html .= "\n</div><!-- #form-main -->\n";
		}
		
		//sidebar for relations, serialized and Submit/Cancel/Delete buttons
		if ($is_main_cms) {
			$form_html .= '<div id="form-sidebar">'."\n";
		}
		$form_html .= $sidebar;
		
		$default = $is_relation_cms ? array('name', 'class2', 'class') : array('id', 'name', 'class2');
		$redirect = $is_main_cms ? ($is_model ? 'admin/'.$this->module_name.'/list' : 'admin/home') : $this->redirect;
		$btn_attr = array('data-redirect' => $redirect);
		if ($this->is_readonly === false) {
		    $id_attr = array('id' => Form_field::CONTAINER_ID_PREFIX.$module_name.'-submit');
			$form_html .= Form_field::form_button('submit', $default, $btn_attr, $id_attr);
		}
		$form_html .= $after_submit;
		
		if ($is_relation_cms) {
			$btn_attr = array('data-panel-id' => '');
		}
        $id_attr = array('id' => Form_field::CONTAINER_ID_PREFIX.$module_name.'-cancel');
		$form_html .= Form_field::form_button('cancel', $default, $btn_attr, $id_attr);
		$form_html .= $after_cancel;
		
		if ($this->use_delete && $is_main_cms && $is_model && $module_data['use_delete']) {
			$btn_attr['data-title-field'] = $module_data['title_field'];
            $id_attr = array('id' => Form_field::CONTAINER_ID_PREFIX.$module_name.'-delete');
			$form_html .= Form_field::form_button('delete', $default, $btn_attr, $id_attr);
		}
		$form_html .= $after_delete;
		
		if ($is_main_cms) {
			$form_html .= '</div><!-- #form-sidebar -->'."\n";
		}
		
		
		$form_html .= '</form>';

        $hidden_html = '  <div class="form-group form-hidden">'."\n".$hidden_html."\n  </div>\n";
		
		$form_html = $form_start.$hidden_html.$form_html;
		
		//add css/js includes
		$this->includes_cms_form();
		if ( Form_field::has_subform() ) {
			$this->includes_subform();
		}
		if ( Form_field::has_upload() ) {
			$this->includes_upload();
		}
		if ( Form_field::has_editor() ) {
			$this->includes_editor();
		}
        if ( Form_field::has_code() ) {
            $this->includes_code();
        }
		if ( Form_field::has_custom() ) {
			$this->includes_custom();
		}
		if ( Form_field::has_date() ) {
			$this->includes_date_chooser();
		}
		if ( Form_field::has_name_value_widget() ) {
			$this->includes_name_value_widget();
		}
		if ( Form_field::has_time() ) {
			$this->includes_time_chooser();
		}
		if ( Form_field::has_tooltip() ) {
			$this->includes_tooltip();
		}
		if ( Form_field::has_values_widget() ) {
			$this->includes_values_widget();
		}
        if ( ! empty($module_data['css_includes']) ) {
            $this->css_includes = array_merge($this->css_includes, $module_data['css_includes']);
        }
		if ( ! empty($module_data['js_includes']) ) {
            $this->js_includes = array_merge($this->js_includes, $module_data['js_includes']);
        }
        if ( ! empty($module_data['js_load_block']) ) {
            $this->js_load_block .= "\n".$module_data['js_load_block']."\n";
        }
        if ( ! empty($module_data['js_unload_block']) ) {
            $this->js_unload_block .= "\n".$module_data['js_unload_block']."\n";
        }
		
		$ret = array();
		$ret['form'] = $form_html;
		if ( ! empty($this->subforms) ) {
			$ret['subforms'] = $this->subforms;
		}
		$ret['fields'] = $validation;
		$ret['form_id'] = $this->form_id;
		$ret['css_includes'] = $this->css_includes;
		$ret['js_includes'] = $this->js_includes;
		$ret['js_load_block'] = $this->js_load_block;
		$ret['js_unload_block'] = $this->js_unload_block;
		
		return $ret;
	}
	
	
	/**
	 * unescape_single_quotes
	 *
	 * This converts single quotes in ASCII hex \u0027 to the single quote char.
	 * 
	 * @access public
	 * @param mixed $mixed A string or array of values to convert single quotes
	 * @return mixed The converted string or array param
	 */
	public static function unescape_single_quotes($mixed) {
		$is_array = true;
		if ( empty($mixed) ) {
			return $mixed;
		} else if ( ! is_array($mixed) ) {
			$mixed = array($mixed);
			$is_array = false;
		}

		foreach($mixed as &$item) {
			$item = is_array($item) ? self::unescape_single_quotes($item) : str_replace('\u0027', "'", $item);
		}
		
		return $is_array ? $mixed : $mixed[0];
	}
	

	/**
	 * attributes
	 *
	 * Generates the name/value attributes, aside from the "class" and "id"
	 * attributes, for the form tag
	 * 
	 * @access protected
	 * @return string The attribute string
	 */
	protected function attributes() {
		if ( empty($this->form_attr) ) {
			return '';
		}
		
		$attr = '';
		foreach ($this->form_attr as $name => $value) {
			$name = strtolower($name);
			if ($name === 'class' || $name === 'id') {
				continue;
			}
			$attr .= ' '.$name.'="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'"';
		}
		return $attr;
	}
	

	/**
	 * class_attr
	 *
	 * generates the class attribute for the form tag
	 * 
	 * @access protected
	 * @return string The class attribute string
	 */
	protected function class_attr() {
		if ( is_string($this->form_class) ) {
			$this->form_class = array($this->form_class);
		}
		$this->form_class[] = 'abstract-form';
		if ($this->is_horizontal) {
			$this->form_class[] = 'form-horizontal';
		}
		if ( isset($this->form_attr['class']) ) {
			$attr = is_string($this->form_attr['class']) ? array($this->form_attr['class']) : $this->form_attr['class'];
			$this->form_class = array_merge($this->form_class + $attr);
		}
		return empty($this->form_class) ? '' : ' class="'.implode(' ', $this->form_class).'"';
	}
	

	/**
	 * class_attr
	 *
	 * Generates the data attributes, name prefixed with "data-" for the form tag.
	 * The values are enclose in single quotes to accomodate JSON strings.
	 * 
	 * @access protected
	 * @return string The class attribute string
	 */
	protected function data_attr() {
		if ( empty($this->form_data) ) {
			return '';
		}
		
		$data = '';
		foreach ($this->form_data as $name => $value) {
			$name = strtolower($name);
			if ( substr($name, 0, 5) !== 'data-') {
				$name = 'data-'.$name;
			}
			if ( is_bool($value) ) {
				$value = $value ? 1 : 0;
			}
			$data .= ' '.$name."='".$value."'";
		}
		return $data;
	}
	

	/**
	 * include_css
	 *
	 * Adds a link to the class array of CSS includes to load
	 * 
	 * @access protected
	 * @param string $css The CSS link
	 * @return void
	 */
	protected function include_css($css) {
		if ( empty($css) ) {
			return;
		}
		if ( ! in_array($css, $this->css_includes) ) {
			$this->css_includes[] = $css;
		}
	}
	

	/**
	 * include_js
	 *
	 * Adds a script src to the class array of Javascript includes to load
	 * 
	 * @access protected
	 * @param string $script The Javascript src
	 * @return void
	 */
	protected function include_js($script) {
		if ( empty($script) ) {
			return;
		}
		if ( ! in_array($script, $this->js_includes) ) {
			$this->js_includes[] = $script;
		}
	}


    /**
     * includes_code
     *
     * Loads the css/js includes for a code editor
     *
     * @access protected
     * @return void
     */
    protected function includes_code() {
        $this->include_css('plugins/codemirror/codemirror.min.css');
        $this->include_css('plugins/codemirror/jquery.codemirror.css');
        $this->include_css('plugins/codemirror/theme/night.css');
        $this->include_js('codemirror/jquery.codemirror.js');
    }
	
	
	/**
	 * includes_date_chooser
	 *
	 * Loads the css/js includes for a date chooser
	 * 
	 * @access protected
	 * @return void
	 */
	protected function includes_date_chooser() {
		if ($this->is_readonly) {
			return;
		}
		$this->include_css('plugins/datebox/jquery.mobile.datebox.min.css');
		$this->include_js('datebox/jquery.mobile.datebox.min.js');
		if ($this->locale !== 'en') {
			$this->include_js('datebox/i18n/jquery.mobile.datebox.i18n.'.$this->locale.'.min.js');
		}
		
		$this->js_load_block .= <<<ONLOAD
		
$('.datebox').datebox({
    mode: 				'datebox', //or "calbox", "slidebox"
    overrideDateFormat:	'%Y-%m-%d',
    useLang:			'{$this->locale}',
	useClearButton:		true,
	repButton:			false
});

ONLOAD;
		$this->js_unload_block .= <<<UNLOAD

//$('.datebox').datebox('destroy');

UNLOAD;
	}
	

	/**
	 * includes_editor
	 *
	 * Loads the css/js includes for a text editor form field
	 * 
	 * @access protected
	 * @return void
	 */
	protected function includes_editor() {
		$this->include_js('tinymce/tinymce.min.js');
		$this->include_js('tinymce/jquery.tinymce.min.js');
		$web_base = WEB_BASE;
		$readonly = $this->is_readonly ? ",\n\treadonly: 1" : "";
		$this->js_load_block .= <<<ONLOAD

$('.tinymce').tinymce({
    selector:   		'.tinymce',
    language:			'{$this->locale}',
    width:      		'100%',
    height:     		270,
    theme: 				'modern',
    resize: 			'both',
    relative_urls : 	false,
	remove_script_host: true,
    statusbar:  		false,
	menubar:    		false,
	plugins:    		'advlist,anchor,code,image,link,nonbreaking',
    toolbar: 'styleselect,|,bold,italic,|,link,unlink,anchor,|,bullist,numlist,|,blockquote,|,nonbreaking,|,insertfile,|,image,|,code',
    extended_valid_elements: 'div[id|class|style],iframe[id|src|width|height|name|align|frameborder|scrolling|marginwidth|marginheight|style],script[src|type],object[id|name|height|width|type|data|style],param[name|value]',
	valid_children: 	'+body[script],+div[script]',
	content_css: 		'{$web_base}/css/tinymce.css',
    setup : function(editor) {
		editor.on('change', function(e) {
			if (e.target.id) {
				$('#' + e.target.id).trigger('change');
			}
		});
	}{$readonly}
});

ONLOAD;
		$this->js_unload_block .= <<<UNLOAD

if ( $('.tinymce').length ) {
    $('.tinymce').tinymce().remove();
}

UNLOAD;
	}
	
	
	/**
	 * includes_form
	 *
	 * Loads the css/js includes for the main form itself
	 * 
	 * @access protected
	 * @return void
	 */
	protected function includes_cms_form() {
		if ( ! $this->is_cms) {
			return;
		}
        $content_id = $this->App->config('page_content_id');
		$this->include_js('abstract/jquery.mobile.cms-forms.js');
		$this->include_js('jquery.sticky-kit.min.js');
		
		$this->js_load_block .= <<<ONLOAD

var \$sidebar = $('#form-sidebar');
\$sidebar.stick_in_parent({parent: '{$content_id}', inner_scrolling: true, recalc_every: 1});
$('.relation-list-open').on('click', function() {
	\$sidebar.trigger('sticky_kit:tick');
});

ONLOAD;

		$this->js_unload_block .= <<<UNLOAD
		
$('#form-sidebar').trigger('sticky_kit:detach');
$('.relation-list-open').off('click');

UNLOAD;
	}
	
	
	/**
	 * includes_custom
	 *
	 * Loads the css/js includes for custom form fields.
	 * 
	 * @access protected
	 * @return void
	 */
	protected function includes_custom() {
		if ( ! $this->is_cms) {
			return;
		}
		$this->include_js('abstract/jquery.mobile.custom-fields.js');
	}
	
	
	/**
	 * includes_name_value_widget
	 *
	 * Loads the css/js includes for a form name-value pairs widget.
	 * 
	 * @access protected
	 * @return void
	 */
	protected function includes_name_value_widget() {
		if ( ! $this->is_cms) {
			return;
		}
		$this->include_css('plugins/abstract/jquery.mobile.nv-pairs.css');
		$this->include_js('abstract/jquery.mobile.nv-pairs.js');
	}
	
	
	/**
	 * includes_subform
	 *
	 * Loads the css/js includes for an external form for serialized or
	 * relational data
	 * 
	 * @access protected
	 * @return void
	 */
	protected function includes_subform() {
		if ( ! $this->is_cms) {
			return;
		}
		$this->include_css('plugins/abstract/jquery.mobile.subforms.css');
		$this->include_js('abstract/jquery.mobile.subforms.js');
	}
	
	
	/**
	 * includes_time_chooser
	 *
	 * Loads the css/js includes for a time chooser. Note format
	 * can be %k:%M for 24 hour or %l:%M %P for 12 hour.
	 * 
	 * @access protected
	 * @return void
	 */
	protected function includes_time_chooser() {
		if ($this->is_readonly) {
			return;
		}
		$this->include_css('plugins/datebox/jquery.mobile.datebox.min.css');
		$this->include_js('datebox/jquery.mobile.datebox.min.js');
		if ($this->locale !== 'en') {
			$this->include_js('datebox/i18n/jquery.mobile.datebox.i18n.'.$this->locale.'.min.js');
		}
		
		$this->js_load_block .= <<<ONLOAD

$('.timebox').on('timebox:init', function() {
	var \$datebox = $(this);
	var format = parseInt( \$datebox.attr('data-time-format') );
	var val = \$datebox.val().toLowerCase();
	if (format === 24 && (val.indexOf('am') !== -1 || val.indexOf('pm') !== -1) ) {
	//convert AM/PM to 24 hours
		var is_pm = val.indexOf('pm') !== -1;
		val = val.replace(' ', '').replace('am', '').replace('pm', '');
		var parts = val.split(':');
		if (parts.length === 2) {
			var hour = parseInt(parts[0]);
			if (is_pm && hour < 12) {
				hour += 12;
			}
			val = (hour < 10 ? '0' : '') + hour + ':' + parts[1];
			\$datebox.val(val);
		}
	}
	\$datebox.datebox({
		mode: 				'timebox',
		overrideTimeFormat:	format,
		overrideTimeOutput:	\$datebox.attr('data-time-output'),
		useLang:			'{$this->locale}',
		useClearButton:		true,
		repButton:			false
    });
}).trigger('timebox:init');

ONLOAD;

		$this->js_unload_block .= <<<UNLOAD

$('.timebox').off('timebox:init');

UNLOAD;
	}
	
	
	/**
	 * includes_tooltip
	 *
	 * Loads the css/js includes for a field tooltip
	 * 
	 * @access protected
	 * @return void
	 */
	protected function includes_tooltip() {
		$this->js_load_block .= <<<ONLOAD
		
$('.tooltip').click(function() {
	$('.tooltip-content').slideUp(500);
	var \$tt = $(this);
	var \$content = \$tt.parent('label').next('.tooltip-content');
	if ( \$tt.hasClass('open') === false ) {
		$('.tooltip').removeClass('open');
		\$tt.addClass('open');
		\$content.slideDown('500');
	} else {
		$('.tooltip').removeClass('open');
	}
});
$('.tooltip-info').click(function() {
	$('.tooltip').removeClass('open');
	$('.tooltip-content').slideUp(500);
});

ONLOAD;
		$this->js_unload_block .= <<<UNLOAD
		
$('.tooltip').off('click');
$('.tooltip-info').off('click');

UNLOAD;
	}
	

	/**
	 * includes_upload
	 *
	 * Loads the css/js includes for an image/file upload
	 * 
	 * @access protected
	 * @return void
	 */
	protected function includes_upload() {
		$this->include_css('plugins/abstract/jquery.mobile.plupload.css');
		$this->include_css('plugins/abstract/octicons/octicons.css');
		$this->include_js('plupload/plupload.full.min.js');
		$this->include_js('abstract/jquery.mobile.plupload.js');
	}
	
	
	/**
	 * includes_values_widget
	 *
	 * Loads the css/js includes for a form values widget.
	 * 
	 * @access protected
	 * @return void
	 */
	protected function includes_values_widget() {
		if ( ! $this->is_cms) {
			return;
		}
		$this->include_css('plugins/abstract/jquery.mobile.values.css');
		$this->include_js('abstract/jquery.mobile.values.js');
	}
	

	/**
	 * is_json_obj
	 *
	 * Checks if a string is a valid JSON array or object.
	 * 
	 * @access protected
	 * @param string $var The JSON string
	 * @return bool True if string is valid JSON array or object
	 */
	public static function is_json_obj($var) {
		if ( empty($var) || is_array($var) || is_object($var) ) {
			return false;
		}
		
		$result = json_decode($var, true);
		return is_array($result);
	}
	
	
	/**
	 * sanitize_input
	 *
	 * Sanitizes a given string or array by stripping HTML, script and style
	 * tags. Uses the PHP filter_var() function with FILTER_SANITIZE_STRING filter.
	 * Note that this will sanitize multi-dimension arrays with scalar values and 
	 * will sanitize array indexes if non-numeric.
	 * 
	 * @access protected
	 * @param mixed $input The string or array input to sanitize
	 * @return mixed The input, sanitized
	 */
	protected static function sanitize_input($input) {
		if ( empty($input) ) {
			return $input;
		}
		
		$sanitized = false;
		if ( is_array($input) ) {
			$sanitized = array();
			foreach ($input as $index => $val) {
				if ( is_numeric($index) === false ) {
					$index = filter_var($index, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
					if ($index === false) {
						continue;
					}
				}
				if ( is_array($val) ) {
					$val = self::sanitize_input($val);
				} else {
					$val = filter_var($val, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
				}
				$sanitized[$index] = $val;
			}
		} else {
			$sanitized = filter_var($input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		}
		
		return $sanitized;
	}
}

/* End of file Form.php */
/* Location: ./App/Form/Form.php */