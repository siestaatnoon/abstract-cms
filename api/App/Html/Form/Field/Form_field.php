<?php

namespace App\Html\Form\Field;

use 
App\App,
App\Database\Database,
App\Exception\AppException,
App\Html\Form\Form,
App\Model\Model,
App\Module\Module,
App\Model\Relation;

/**
 * Form_field class
 * 
 * This class creates an HTML form field with a label tag and formatted for values to be 
 * populated with the Underscore javascript library's template() function. This class can be used 
 * for forms in the CMS as well as frontend forms.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App
 */
class Form_field {
	
    /**
     * @var string Prefix for all field containers (div)
     */
	const CONTAINER_ID_PREFIX = 'ct-';

    /**
     * @var string Prefix for all field id attributes
     */
	const FIELD_ID_PREFIX = 'field-';
	
	/**
     * @var bool True add Underscore template variables for population 
	 * of values, false to not add, defaults to true
     */
	protected static $ADD_TEMPLATE_VARS = true;
	
    /**
     * @var array Button field attribute names/values that are restricted to form
     * submit, cancel and delete buttons
     */
	protected static $BUTTON_RESERVED_ATTR = array(
		'id' => array(
			'submit' => 'submit-save',
			'cancel' => 'button-cancel',
			'delete' => 'button-delete'
		),
		'class' => array(
			'submit' => 'form-panel-save',
			'cancel' => 'form-panel-close'
		),
		'class2' => array(
			'submit' => 'form-save ui-icon-action',
			'cancel' => 'form-cancel ui-icon-minus',
			'delete' => 'form-delete ui-icon-delete'
		),
		'name' => array(
			'submit' => 'submit-save',
			'cancel' => 'button-cancel',
			'delete' => 'button-delete'
		)
	);
	
    /**
     * @var int The number of columns (out of 12) widths for a form field
     * label in the Bootstrap horizontal form layout
     */
	protected static $COLS_LABEL = 3;
	
	/**
     * @var int The number of columns (out of 12) widths for a form field
     * in the Bootstrap horizontal form layout
     */
	protected static $COLS_FIELD = 9;
	
	/**
     * @var bool True if this form uses the Bootstrap horizontal layout, defaults to false
     */	
	protected static $IS_HORIZONTAL = false;
	
	/**
     * @var bool True if field is readonly
     */	
	protected static $IS_READONLY = false;
	
	/**
     * @var bool True if form uses custom AJAX field
     */	
	protected static $has_custom = false;

    /**
     * @var bool True if any already created instance uses the CodeMirror plugin
     */
    protected static $has_code = false;
	
	/**
     * @var bool True if any already created instance uses a field for a date
     */	
	protected static $has_date = false;
	
	/**
     * @var bool True if any already created instance uses TinyMCE editor
     */	
	protected static $has_editor = false;
	
	/**
     * @var bool True if form uses name-value pairs widget
     */	
	protected static $has_name_value_widget = false;
	
	/**
     * @var bool True if form uses serialized data or relational data
     * required in another form
     */	
	protected static $has_subform = false;
	
	/**
     * @var bool True if any already created instance uses a field for a time
     */	
	protected static $has_time = false;
	
	/**
     * @var bool True if any already created instance uses a tooltip for field information
     */	
	protected static $has_tooltip = false;
	
	/**
     * @var bool True if any already created instance uses a file upload
     */	
	protected static $has_upload = false;
	
	/**
     * @var bool True if form uses form values widget
     */	
	protected static $has_values_widget = false;
	
	/**
     * @var string Prefix used for id attribute in form field
     */	
	protected static $id_prefix = '';

	/**
     * @var \App\App Instance of the main App class
     */
	protected $App;
	
	/**
     * @var array Entire row data of form field
     */	
	protected $data;
	
	/**
     * @var array Configuration array of form field types in ./App/Config/field_types.php
     */	
	protected $field_types;
	
	/**
     * @var string Label tag text for field
     */
	protected $label;
	
	/**
     * @var string The field name attribute
     */	
	protected $name;
	
	/**
     * @var array The form field attributes/parameters
     */	
	protected $params;
	
	/**
     * @var array Array of \App\Model\Relation used to populate select, multiselect
     * or relational subforms
     */	
	protected $relations;
	
	/**
     * @var string The form field type
     */	
	protected $type;
	
	
	/**
	 * Constructor
	 *
	 * Initializes a form field with the given configuration parameters in assoc array:<br/><br/>
	 * <ul>
	 * <li>label => Text for the field label tag</li>
	 * <li>name => The field name attribute</li>
	 * <li>field_type => Array of [type] => [field parameters] used generate field HTML</li>
	 * </ul>
	 * 
	 * @access public
	 * @param array $config The form configuration array
	 * @throws \App\Exception\AppException if $config assoc array missing required parameters
	 */
	public function __construct($config) {
		$this->App = App::get_instance();
		$this->App->load_util('form_jqm');
		$ft = $this->App->load_config('field_types');
		$this->field_types = $ft['field_types'];
		$errors = array();
	
		if ( empty($config['field_type']) || ! is_array($config['field_type']) ) {
			$errors[] = '$config[field_type] empty, must be array of field parameters';
		} else if ( $this->validate_params($config) === false) {
			$errors[] = '$config[field_type] contains invalid field parameters';
		} else {
			$this->type = key($config['field_type']);
			$this->params = current($config['field_type']);
		}

		if ( empty($config['label']) && ! in_array($this->type, array('hidden', 'object') ) ) {
			$errors[] = '$config[label] empty, must be label tag text for field';
		}
		if ( empty($config['name']) ) {
			$errors[] = '$config[name] empty, must be field name attribute';
		}
		if ( empty($config['module']) ) {
			$errors[] = '$config[module] empty, must be module name (slug) for field';
		}
		
		$label = empty($config['label']) ? '' : $config['label'];
		if ( ! empty($config['lang']) ) {
			$label = $this->App->lang($config['lang']);
			$config['label'] = $label;
		}
		$this->label = $label;
		$this->name = $config['name'];
		$this->data = $config;
	}
	
	
	/**
	 * field_hidden_id
	 *
	 * Creates a hidden input field used for the id of a module row. 
	 * 
	 * @access public
     * @param string $pk_field The primary key field name
	 * @return string The hidden input id field
	 */
	public function field_hidden_id($pk_field='') {
	    $pk_field = empty($this->data['module_pk']) ? (empty($pk_field) ? '' : $pk_field) : $this->data['module_pk'];
		$value = self::$ADD_TEMPLATE_VARS && ! empty($pk_field) ? '<%= '.$pk_field.' %>' : '';
		return '<input type="hidden" id="'.$this->field_id().'" name="'.$pk_field.'" value="'.$value.'" />'."\n";
	}
	

	/**
	 * filter
	 *
	 * For boolean, checkbox, jqm_flipswitch, multiselect, radio, relation, and 
	 * select field types, creates a select menu dropdown used a filter on CMS
	 * list view pages.
	 * 
	 * @access public
	 * @param mixed $class Class attributes as a string or array of values
	 * @return string The filter select menu HTML 
	 */
	public function filter($class=array()) {
		if ( $this->is_select_filter() === false ) {
			return '';
		}
		
		$values = array();
		$sort = ! isset($this->params['sort']) || $this->params['sort'] ? true : false;
		if ( in_array($this->type, array('boolean', 'jqm_flipswitch') ) ) {
			$yes_text = empty($this->params['data-on-text']) ? 'Yes' : $this->params['data-on-text'];
			$no_text = empty($this->params['data-off-text']) ? 'No' : $this->params['data-off-text'];
			$values = array(
				1 => $yes_text,
				0 => $no_text
			);
		} else if ( isset($this->params['values']) ) {
			$values = $sort ? asort($this->params['values']) : $this->params['values'];
		} else if ( isset($this->params['config_file']) ) {
			$values = $this->values_config($this->params['config_file'], $this->params['config_key'], $sort);
		} else if ( isset($this->params['dir']) ) {
            $show_ext = ! isset($this->params['show_ext']) || ! empty($this->params['show_ext']);
            if ( empty($this->params['filename_only']) ) {
                $values = $this->values_dir($this->params['dir'], $sort, $show_ext);
            } else {
                $values = $this->values_dir_filename($this->params['dir'], $sort, $show_ext);
            }
		} else if ( isset($this->params['lang_config']) ) {
			$values = $this->values_lang($this->params['lang_config'], $sort);
		} else if ( isset($this->params['module']) ) {
			$module = Module::load($this->params['module']);
			$model = $module->get_model();
			$values = $this->values_model($model, $sort);
		} else if ( isset($this->params['relation']) ) {
			$relation_name = $this->params['relation'];
			$module = Module::load($this->data['module']);
			$relations = $module->get_relations();
			if ( empty($relations[$relation_name]) ) {
				$message = 'Invalid relation ['.$relation_name.'] in module ['.$this->params['module'].'] ';
				$message .= 'for field type "relation" ['.$this->name.']';
				throw new AppException($message, AppException::ERROR_FATAL);
			}

			$relation = $relations[$relation_name];
			$relation_data = $relation->get_property('module');
			$this->label = $relation_data['label'];
			$this->name = $relation_name;
			$this->params['values'] = $relation->get_property('indep_model')->get_id_list();
		}
		
		$class_attr = '';
		if ( ! empty($class) ) {
			$class_attr = ' class="'.(is_array($class) ? implode(' ', $class) : $class).'"';
		}
		$html = '<select name="'.$this->name.'"'.$class_attr.'>'."\n";
		$html .= '  <option value="">'.$this->label.'</option>'."\n";
		
		foreach ($values as $val => $name) {
            $val = str_replace('"', '&quot;', $val); //escape double quotes for value attr
			$html .= '  <option value="'.$val.'">'.$this->label.': '.$name.'</option>'."\n";
		}
		
		$html .= '</select>'."\n";
		return $html;
	}
	
	
	/**
	 * form_button
	 *
	 * Creates a form submit, cancel or delete HTML button.
	 * 
	 * @access public
	 * @param string $type The button type, [submit|cancel|delete]
	 * @param array $default_attr The keys from self::$BUTTON_RESERVED_ATTR to use
	 * as attributes
	 * @param array $attributes Additional attributes to use with keys being the attribute
	 * name, note that "class" may be a a string or array
     * @param array $ct_attr Attributes for enclosing div container
	 * @return string The form button HTML and enclosing container
	 */
	public static function form_button($type, $default_attr=array(), $attributes=array(), $ct_attr=array()) {
		$lang = array(
			'submit' => 'form.save', 
			'cancel' => 'form.cancel',
			'delete' => 'form.delete'
		);
		if ( empty($type) || ! isset($lang[$type]) ) {
			return false;
		}
		
		$class = array(
			'btn',
			'btn-primary',
			'ui-btn',
			'ui-corner-all',
			'ui-btn-icon-right'
		);
		$attr = array();
		$atr_str = '';
		$reserved = self::$BUTTON_RESERVED_ATTR;
		if ( ! empty($default_attr) ) {
			foreach ($default_attr as $def) {
				if ( isset($reserved[$def][$type]) ) {
					if ( substr($def, 0, 5) === 'class') {
						array_unshift($class, $reserved[$def][$type]);
					} else {
						$attr[$def] = $reserved[$def][$type];
					}
				}
			}
		}
		if ( is_array($attributes) ) {
			if ( isset($attributes['class']) ) {
				$cls = is_array($attributes['class']) ? $attributes['class'] : array($attributes['class']);
				foreach ($cls as $c) {
					$class[] = $c;
				}
				unset($attributes['class']);
			}
			$attr['class'] = implode(' ', $class);
			$attr = $attr + $attributes;
		}
		
		foreach ($attr as $name => $value) {
			$is_data = substr($value, 0, 5) === 'data-';
			$atr_str .= ' '.$name.($is_data ? "='".str_replace('"', '&quot;', $value)."'" : '="'.$value.'"');
		}
		
		if (self::$IS_READONLY && $type !== 'cancel') {
			$atr_str .= ' readonly="readonly"';
		}
		
		$App = App::get_instance();
		$label = $App->lang($lang[$type]);
		$html = '  <button'.$atr_str.'>'.$label.'</button>'."\n";
		return form_field_wrap($html, $ct_attr);
	}
	
	
	/**
	 * get_data
	 *
	 * Returns a field from the form_fields table row passed into __construct()
	 * 
	 * @access public
	 * @param mixed $field The name of field whose data to retrieve or false to retrieve
	 * the entire row
	 * @return mixed The field data or false if $field key does not exist
	 */
	public function get_data($field=false) {
		if ( empty($field) ) {
			return $this->data;
		}
		return isset($this->data[$field]) ? $this->data[$field] : false;
	}
	
	
	/**
	 * get_label
	 *
	 * Return the form field label
	 * 
	 * @access public
	 * @return string The field label
	 */
	public function get_label() {
		return $this->label;
	}
	
	
	/**
	 * get_name
	 *
	 * Return the form field name attribute
	 * 
	 * @access public
	 * @return string The field name attribute
	 */
	public function get_name() {
		return $this->name;
	}
	
	
	/**
	 * get_param
	 *
	 * Return a form field parameter from $this->params
	 * 
	 * @access public
	 * @param string $param The parameter key
	 * @return string The field param or false if does not exist
	 */
	public function get_param($param) {
		if ( empty($param) ) {
			return false;
		}
		return isset($this->params[$param]) ? $this->params[$param] : false;
	}
	
	
	/**
	 * get_type
	 *
	 * Return the form field type
	 * 
	 * @access public
	 * @return string The field type
	 */
	public function get_type() {
		return $this->type;
	}
	
	
	/**
	 * has_custom
	 *
	 * Returns true if field is a custom AJAX field
	 * 
	 * @access public
	 * @return bool True if field is custom AJAX
	 */
	public static function has_custom() {
		return self::$has_custom;
	}


    /**
     * has_code
     *
     * Returns true if field uses CodeMirror plugin for code edit
     *
     * @access public
     * @return bool True if field is for CodeMirror plugin
     */
    public static function has_code() {
        return self::$has_code;
    }
	
	
	/**
	 * has_date
	 *
	 * Returns true if field input is for a date
	 * 
	 * @access public
	 * @return bool True if field is for a date
	 */
	public static function has_date() {
		return self::$has_date;
	}
	
	
	/**
	 * has_editor
	 *
	 * Returns true if field is for a content editor (e.g. TinyMCE)
	 * 
	 * @access public
	 * @return bool True if field is for a content editor
	 */
	public static function has_editor() {
		return self::$has_editor;
	}
	
	
	/**
	 * has_name_value_widget
	 *
	 * Returns true if field input is for name-value pairs widget
	 * 
	 * @access public
	 * @return bool True if field is for a time
	 */
	public static function has_name_value_widget() {
		return self::$has_name_value_widget;
	}
	
	
	/**
	 * has_time
	 *
	 * Returns true if field input is for a time
	 * 
	 * @access public
	 * @return bool True if field is for a time
	 */
	public static function has_time() {
		return self::$has_time;
	}
	
	
	/**
	 * has_subform
	 *
	 * Returns true if field uses relational data (1 -> n) for a module
	 * which adds an additional form to add or edit values
	 * 
	 * @access public
	 * @return bool True if field is for a time
	 */
	public static function has_subform() {
		return self::$has_subform;
	}
	
	
	/**
	 * has_tooltip
	 *
	 * Returns true if field uses a tooltip
	 * 
	 * @access public
	 * @return bool True if field is for a time
	 */
	public static function has_tooltip() {
		return self::$has_tooltip;
	}
	
	
	/**
	 * has_upload
	 *
	 * Returns true if field is an image/file upload
	 * 
	 * @access public
	 * @return bool True if field is an image/file upload
	 */
	public static function has_upload() {
		return self::$has_upload;
	}
	
	
	/**
	 * has_values_widget
	 *
	 * Returns true if field input is for form values widget
	 * 
	 * @access public
	 * @return bool True if field is for a time
	 */
	public static function has_values_widget() {
		return self::$has_values_widget;
	}
	
	
	/**
	 * html
	 *
	 * Generates the form field HTML with label tag and enclosing wrapper using
	 * the properties of an \App\Html\Form\Field\Form_field passed into the
	 * constructor.
	 * 
	 * @access public
	 * @return string The field HTML
	 * @see \App\Html\Form\Field\Form_field for form field object structure
	 */
	public function html() {
		$html = '';

		switch ($this->type) {
			case 'hidden' :
				$html = $this->field_hidden();
				break;
			case 'button' :
				$html = $this->field_button();
				break;
			case 'checkbox' :
				$html = $this->field_checkbox();
				break;
			case 'multiselect' :
				$html = $this->field_multiselect();
				break;
			case 'password' :
				$html = $this->field_password();
				break;
			case 'radio' :
				$html = $this->field_radio();
				break;
			case 'select' :
				$html = $this->field_select();
				break;
			case 'text' :
				$html = $this->field_text();
				break;
			case 'textarea' :
				$html = $this->field_textarea();
				break;
			case 'bool' :
				$html = $this->field_boolean();
				break;
            case 'code' :
                $html = $this->field_code();
                break;
			case 'custom' :
				$html = $this->field_custom();
				break;
			case 'countries' :
				$html = $this->field_countries();
				break;
			case 'date' :
				$html = $this->field_date();
				break;
			case 'editor' :
				$html = $this->field_editor();
				break;
			case 'jqm_flipswitch' :
				$html = $this->field_jqm_flipswitch();
				break;
			case 'file' :
			case 'image' :
				$html = $this->field_upload();
				break;
			case 'info' :
				$html = $this->field_info();
				break;
			case 'name_value_widget' :
				$html = $this->field_name_value_widget();
				break;
            case 'object' :
                $html = $this->field_object();
                break;
			case 'regions' :
				$html = $this->field_regions();
				break;
			case 'relation' :
				$html = $this->field_relation();
				break;
			case 'time' :
				$html = $this->field_time();
				break;
			case 'values_widget' :
				$html = $this->field_values_widget();
				break;
			default:
				break;
		}
		
		return $html;
	}
	
	
	/**
	 * is_filter
	 *
	 * Returns true if field is set to be a list page filter.
	 * 
	 * @access public
	 * @return bool True if field is filter
	 */
	public function is_filter() {
		return ! empty($this->data['is_filter']);
	}
	
	
	/**
	 * is_select_filter
	 *
	 * Returns true if field is a select menu for list page filter.
	 * 
	 * @access public
	 * @return bool True if field is select menu filter
	 */
	public function is_select_filter() {
		$types = array('boolean', 'checkbox', 'jqm_flipswitch', 'multiselect', 'radio', 'relation', 'select');
		return $this->is_filter() && in_array($this->type, $types);
	}
	
	
	/**
	 * set_horizontal
	 *
	 * Sets the field and it's label to horizontally align in Bootstrap. NOTE:
	 * currently not used for this class which depends on jQuery Mobile layout.
	 * 
	 * @access public
	 * @param bool $is_horiz True to align label and field (default)
	 * @return void
	 */
	public static function set_horizontal($is_horiz=true) {
		self::$IS_HORIZONTAL = $is_horiz;
	}
	
	
	/**
	 * set_id_prefix
	 *
	 * Updates the id attribute prefix by adding a string to ensure a
	 * more unique value
	 * 
	 * @access public
	 * @param string $prefix The string added to current id attribute prefix
	 * @return void
	 */
	public static function set_id_prefix($prefix) {
		if ( ! empty($prefix) ) {
			self::$id_prefix = self::FIELD_ID_PREFIX.$prefix.'-';
		}
	}
	
	
	/**
	 * set_readonly
	 *
	 * Sets the field to readonly
	 * 
	 * @access public
	 * @param bool $is_readonly True to set field as readonly
	 * @return void
	 */
	public static function set_readonly($is_readonly=true) {
		self::$IS_READONLY = $is_readonly;
	}
	
	
	/**
	 * set_template_values
	 *
	 * Sets flag to allow Underscore template tokens to be added to form field.
	 * 
	 * @access public
	 * @param bool $toggle True to add template values, false to not add
	 * @return void
	 */
	public static function set_template_values($toggle=true) {
		self::$ADD_TEMPLATE_VARS = ! empty($toggle);
	}
	
	
	/**
	 * field_bool
	 *
	 * Creates a single checkbox field and enclosing div container for a field corresponding to
	 * a true/false or yes/no type input.
	 * 
	 * @access protected
	 * @return string The single checkbox field and enclosing div container
	 * @see \App\Html\Form\Field\Form_field for form field object structure
	 */
	protected function field_boolean() {
		if ($this->type !== 'boolean') {
			return '';
		}
		
		$label = empty($this->params['lang']) ? $this->label : $this->App->lang($this->params['lang']);
		unset($this->params['values']);
		$this->params['values'] = array(1 => $label);
		return $this->field_checkbox(false);
	}
	
	
	/**
	 * field_button
	 *
	 * Creates a form <button> and enclosing div container. 
	 * 
	 * @access protected
	 * @return string The button field and enclosing div container
	 */
	protected function field_button() {
		if ($this->type !== 'button') {
			return '';
		}
		
		$reserved = self::$BUTTON_RESERVED_ATTR;
		$res_ids = array_values($reserved['id']);
		$res_name = array_values($reserved['name']);
		$res_class = array_values($reserved['class']);
		
		if ( isset($this->params['attr']['id']) ) {
			$id = $this->params['attr']['id'];
			if ( in_array($id, $res_ids) ) {
				$this->params['attr']['id'] = $this->field_id();
			}
		}
		
		if ( in_array($this->name, $res_name) ) {
			$this->name .= '-1';
		}
		
		$class = array();
		if ( isset($this->params['attr']['class']) ) {
			$class = $this->params['attr']['class'];
			if ( is_string($class) ) {
				$class = explode(' ', $class);
			}
			foreach ($class as $i => $c) {
				if ( in_array($c, $res_class) ) {
					unset($class[$i]);
				}
			}
			$class = array_values($class);
		}
		$this->params['attr']['class'] = $class;
		
		$params = $this->params;
		$params['label'] = $this->label;
		$params['name'] = $this->name;
		$params['attr']['id'] = $this->field_id();
		$params['is_readonly'] = self::$IS_READONLY;
        $attr = array('id' => $this->field_container_id() );
        if ( ! empty($params['ct_attr']) ) {
            $attr = $attr + $params['ct_attr'];
        }
		return form_button($params, $attr);
	}
	

	/**
	 * field_checkbox
	 *
	 * Creates one or more checkbox fields and enclosing div container. Note that
	 * the following field attributes are available to configure the checkboxes 
	 * contained within the parameter $params[field_type][checkbox]:<br/><br/>
	 * <ul>
	 * <li>is_inline => True to display the checkboxes inline instead of stacked</li>
	 * <li>values => Array of value => label corresponding to each checkbox <strong>OR</strong></li>
	 * <li>config => Name of the config file in ./App/Config (without .php ext) whose data populates 
	 * the values and labels of the select options <strong>OR</strong></li>
	 * <li>lang_config => Name of the language file, in the current locale directory (without .php ext), 
	 * whose data populate the values and labels <strong>OR</strong></li>
	 * <li>module => Calls the module model get_id_list() function to populate the values and labels
	 * of the checkboxes</li>
	 * </ul><br/><br/>
	 * Note that if there are more than one checkboxes, the checkbox field name will
	 * automatically be appended with "[]" and will be a POST array var.
	 * 
	 * @access protected
	 * @param bool $has_label True if the checkbox(es) have a label for the group as a whole
	 * @return string The checkbox fields and enclosing div container
	 * @throws \App\Exception\AppException if missing one fo the above required parameters
	 * @see \App\Model\Model::get_id_list() for function description
	 */
	protected function field_checkbox($has_label=true) {
		$valid_types = array('checkbox', 'boolean');
		if ( ! in_array($this->type, $valid_types) ) {
			return '';
		} else if (empty($this->params['values']) && 
			( empty($this->params['config_file']) ||
			empty($this->params['config_key']) ) &&
			empty($this->params['lang_config']) && 
			empty($this->params['module']) ) {
			$message = 'Param $config[type] in __construct() missing one of the following for field type ';
			$message .= '"'.$this->type.'": (array) values, (string) config_file + (string) config_key, ';
			$message .= '(string) lang_config, (string) module ['.$this->name.']';
			throw new AppException($message, AppException::ERROR_FATAL);
		}

		$values = array();
		$sort = empty($this->params['sort']) ? false : true;
		if ( isset($this->params['values']) ) {
			$values = $this->params['values'];
			if ($sort) {
				asort($values);
			}
		} else if ( isset($this->params['config_file']) ) {
			$values = $this->values_config($this->params['config_file'], $this->params['config_key'], $sort);
		} else if ( isset($this->params['lang_config']) ) {
			$values = $this->values_lang($this->params['lang_config'], $sort);
		} else if ( isset($this->params['module']) ) {
			$module = Module::load($this->params['module']);
			$model = $module->get_model();
			$values = $this->values_model($model, $sort);
		}

		$params = $this->params;
		$params['label'] = $this->label;
		$params['name'] = $this->name;
		$params['values'] = $values;
		$params['tooltip'] = $this->get_tooltip();
		$params['use_template_vars'] = self::$ADD_TEMPLATE_VARS;
		$params['is_readonly'] = self::$IS_READONLY;
		$params['attr']['id'] = $this->field_id();
        $attr = array('id' => $this->field_container_id() );
        if ( ! empty($params['ct_attr']) ) {
            $attr = $attr + $params['ct_attr'];
        }
        if ( ! empty($params['is_inline']) ) {
            $attr = $attr + array('data-type' => 'horizontal');
        }
		return form_checkbox($params, $attr);
	}


    /**
     * field_code
     *
     * Creates a textarea used as a code editor (CodeMirror).
     *
     * @access protected
     * @return string The textarea enabled as a code editor
     */
    protected function field_code() {
        if ($this->type !== 'code') {
            return '';
        }

        if ( empty($this->params['attr']['class']) || is_array($this->params['attr']['class']) ) {
            $this->params['attr']['class'][] = 'codemirror';
        } else {
            $this->params['attr']['class'] .= ' codemirror';
        }

        $this->params['attr']['data-enhance'] = 'false'; //don't allow jQM styling
        self::$has_code = true;
        return $this->field_textarea();
    }
	

	/**
	 * field_countries
	 *
	 * Creates a select dropdown menu of all countries of the world in
	 * [ISO country code] => [country name] format. This function uses the countries.php
	 * file in the current locale directory in ./App/Lang. Note that the country list
	 * is sorted by name before populating the select list.
	 * 
	 * @access protected
	 * @return string The countries select list and enclosing div container
	 * @see \App\Html\Form\Field\Form_field for form field object structure
	 */
	protected function field_countries() {
		if ($this->type !== 'countries') {
			return '';
		}
		
		if ( isset($this->params['values']) ) {
			unset($this->params['values']);
		}
		$this->params['lang_config'] = 'countries';
		return $this->field_select();
	}
	
	
	/**
	 * field_custom
	 *
	 * Creates a form field with custom HTML. 
	 * 
	 * @access protected
	 * @return string The 
	 */
	protected function field_custom() {
		$valid_types = array('custom', 'relation');
		if ( ! in_array($this->type, $valid_types) ) {
			return '';
		}
		
		$errors = array();
		if ( ! isset($this->params['is_ajax']) ) {
			$errors[] = '[is_ajax] var not defined ['.$this->name.']';
		} else if ( empty($this->params['is_ajax']) && empty($this->params['html']) && empty($this->params['template']) ) {
			$errors[] = '[html] or [template] param HTML must be defined and not empty ['.$this->name.']';
		} 
		if ( ! empty($errors) ) {
			$message = 'Invalid param $config[type] in __construct() for field type "custom": ';
			$message .= implode("\n", $errors).' ['.$this->name.']';
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$is_ajax = ! empty($this->params['is_ajax']);
        $is_template = ! empty($this->params['template']);
		$class = 'field-custom-'.($is_ajax ? 'ajax field-custom-loading' : ($is_template ? 'template' : 'html') );
		$data = '';
        $html = '';
		if ($is_ajax) {
			$data_id = self::$ADD_TEMPLATE_VARS && ! empty($this->data['module_pk']) ? '<%= '.$this->data['module_pk'].' %>' : '';
			$data_value = self::$ADD_TEMPLATE_VARS ? "<%= ".$this->name." %>" : '';
			$data .= ' data-field="'.$this->name.'"';
            $data .= ' data-module="'.$this->data['module'].'"';
			$data .= ' data-id="'.$data_id.'"';
			$data .= " data-value='".$data_value."'";
			self::$has_custom = true;
		}
        $data .= " data-readonly='".(self::$IS_READONLY ? 1 : 0)."'";

		$html = '  '.form_label_tt($this->label, $this->field_id(), $this->get_tooltip() )."\n";
		$html .= '  <div class="'.$class.'"'.$data.'>'."\n";
		if ($is_ajax === false) {
		    if ($is_template) {
                $html .= $this->App->load_view($this->params['template'], array('is_readonly' => self::$IS_READONLY) )."\n  ";
            } else {
                $html .= $this->params['html']."\n  ";
            }
		}
		$html .= "</div>\n";
        $attr = array('id' => $this->field_container_id() );
        if ( ! empty($this->params['ct_attr']) ) {
            $attr = $attr + $this->params['ct_attr'];
        }
		return form_field_wrap($html, $attr);
	}
	
	
	/**
	 * field_date
	 *
	 * Creates a text input field enabled for entering a date value by
	 * an external plugin.
	 * 
	 * @access protected
	 * @return string The text field enabled for date input
	 */
	protected function field_date() {
		if ($this->type !== 'date') {
			return '';
		}
		
		if ( empty($this->params['attr']['class']) || is_array($this->params['attr']['class']) ) {
			$this->params['attr']['class'][] = 'datebox';
		} else {
			$this->params['attr']['class'] .= ' datebox';
		}
		
		self::$has_date = true;
		return $this->field_text();
	}
	
	
	/**
	 * field_editor
	 *
	 * Creates a textarea used as a rich text editor (TinyMCE).
	 * 
	 * @access protected
	 * @return string The textarea enabled as a content editor
	 */
	protected function field_editor() {
		if ($this->type !== 'editor') {
			return '';
		}
		
		if ( empty($this->params['attr']['class']) || is_array($this->params['attr']['class']) ) {
			$this->params['attr']['class'][] = 'tinymce';
		} else {
			$this->params['attr']['class'] .= ' tinymce';
		}

		self::$has_editor = true;
		return $this->field_textarea();
	}
	
	
	/**
	 * field_hidden
	 *
	 * Creates a hidden input field.
	 * 
	 * @access protected
	 * @return string The hidden input field
	 */
	protected function field_hidden() {
		$valid_types = array('hidden', 'relation');
		if ( ! in_array($this->type, $valid_types) ) {
			return '';
		}

		$params = $this->params;
		$params['name'] = $this->name;
		$params['use_template_vars'] = self::$ADD_TEMPLATE_VARS;
		$params['attr']['id'] = $this->field_id();
		return form_hidden($params);
	}
	
	
	/**
	 * field_container_id
	 *
	 * Creates an id attribute value for the div container containing a label and/or form field.
	 * 
	 * @access protected
	 * @return string The form field container id
	 */
	protected function field_container_id() {
		$field = str_replace('[]', '', $this->name);
		$field = $this->field_id($field);
		return str_replace(self::FIELD_ID_PREFIX, self::CONTAINER_ID_PREFIX, $field);
	}
	

	/**
	 * field_id
	 *
	 * Creates a form field id given the field name attribute and index, if the
	 * field is an array. The id is prefixed with self::FIELD_ID_PREFIX and fields with an index
	 * an index postfixed with "_[index]" (e.g. field_email, field_options_0).
	 * 
	 * @access protected
	 * @param string $field The form field name
	 * @param int $index The zero-based index of the form field, if an array
	 * @return string The form field id
	 */
	protected function field_id($field='', $index=false) {
		if ( empty($field) ) {
			$field = $this->name;
		}
		
		$prefix = empty(self::$id_prefix) ? self::FIELD_ID_PREFIX : self::$id_prefix;
		$field = $prefix.str_replace(array('[', ']'), '-', $field);
		if ( substr($field, (strlen($field) - 1) ) === '-' ) {
			$field = substr($field, 0, (strlen($field) - 1) );
		}
		$field .= is_numeric($index) ? '-'.$index : '';
		return $field;
	}
	

	/**
	 * field_info
	 *
	 * Creates an area within the form which can be used for information or other HTML content.
	 * 
	 * @access protected
	 * @return string The info area and enclosing div container
	 * @throws \App\Exception\AppException if missing [content] param in field config
	 */
	protected function field_info() {
		if ($this->type !== 'info') {
			return '';
		} else if ( ! isset($this->params['content']) ) {
			$message = 'Param $config[type] in __construct() missing parameter [content] for field type "info" ['.$this->name.']';
			throw new AppException($message, AppException::ERROR_FATAL);
		}

        $attr = array('id' => $this->field_container_id() );
        if ( ! empty($this->params['ct_attr']) ) {
            $attr = $attr + $this->params['ct_attr'];
        }
		return form_field_wrap($this->params['content'], $attr);
	}
	
	
	/**
	 * field_jqm_flipswitch
	 *
	 * Creates a jQuery Mobile Flipswitch using an html select and corresponding to
	 * a true/false or yes/no type input.
	 * 
	 * @access protected
	 * @param string $field The form field name (attribute)
	 * @param array $params The parameters of the form field in a Form_field object
	 * @return string The single checkbox field and enclosing div container
	 * @see \App\Html\Form\Field\Form_field for form field object structure
	 */
	protected function field_jqm_flipswitch() {
		if ($this->type !== 'jqm_flipswitch') {
			return '';
		}

		$label = empty($this->params['lang']) ? $this->label : $this->App->lang($this->params['lang']);
		$params = $this->params;
		$params['label'] = $label;
		$params['name'] = $this->name;
		$params['tooltip'] = $this->get_tooltip();
		$params['use_template_vars'] = self::$ADD_TEMPLATE_VARS;
		$params['attr']['id'] = $this->field_id();
		$params['is_readonly'] = self::$IS_READONLY;
        $attr = array('id' => $this->field_container_id() );
        if ( ! empty($params['ct_attr']) ) {
            $attr = $attr + $params['ct_attr'];
        }
		return form_flipswitch($params, $attr);
	}
	

	/**
	 * field_label
	 *
	 * Creates a form field label and sets the field tooltip.
	 * 
	 * @access protected
	 * @param string $field_id The form field id used in the for attribute for the label
	 * @return string The form field label
	 */
	protected function field_label($field_id='') {
		return form_label_tt($this->label, $field_id, $this->get_tooltip() );
	}
	

	/**
	 * field_multiselect
	 *
	 * Creates a multiple select dropdown field and enclosing div container. Note that
	 * the following field attributes are available to configure the select field
	 * contained within the parameter $params[field_type][multiselect]:<br/><br/>
	 * <ul>
	 * <li>values => Array of value => label corresponding to each select option <strong>OR</strong></li>
	 * <li>config => Name of the config file in ./App/Config (without .php ext) whose data populates 
	 * the values and labels of the select options <strong>OR</strong></li>
	 * <li>lang_config => Name of the language file, in the current locale directory (without .php ext), 
	 * whose data populate the values and labels <strong>OR</strong></li>
	 * <li>module => Calls the module model get_id_list() function to populate the values and labels
	 * of the select options</li>
	 * </ul>
	 * 
	 * @access protected
	 * @return string The multiselect dropdown field and enclosing div container
	 * @see \App\Model\Model::get_id_list() for function description
	 */
	protected function field_multiselect() {
		$valid_types = array('multiselect', 'relation');
		if ( ! in_array($this->type, $valid_types) ) {
			return '';
		}

		$this->params['is_multiple'] = true;
		$this->params['multiple'] = 'multiple';
		$this->params['attr']['data-native-menu'] = 'false'; //for JQM enhancement
		return $this->field_select();
	}
	
	
	/**
	 * field_name_value_widget
	 *
	 * Creates a form widget using jQuery Mobile to add name => value pairs (e.g. 
	 * an associative array) with the option for sorting.
	 * 
	 * @access protected
	 * @return string The name-value pairs widget
	 */
	protected function field_name_value_widget() {
		if ($this->type !== 'name_value_widget') {
			return '';
		}

		$attr = array();
		$name_label = empty($this->params['name_label']) ? 
					  $this->App->lang('form.field_type.values.name') :
					  $this->params['name_label'];
		$value_label = empty($this->params['value_label']) ? 
					  $this->App->lang('form.field_type.values.value') :
					  $this->params['value_label'];
		$max_items = isset($this->params['max_items']) && is_numeric($this->params['max_items']) ? 
					 $this->params['max_items'] :
					 '';
		$id_prefix = str_replace( array(self::FIELD_ID_PREFIX, '-'), '', self::$id_prefix);
		$id_postfix = (empty($id_prefix) ? '' : $id_prefix.'-').$this->name;
		$widget_id = 'name-value-pairs-'.$id_postfix;
        $field_name = $this->name.'[]';
		
		$attr['id'] = $widget_id;
		$attr['class'] = 'name-value-pairs';
		$attr['data-field'] = $field_name;
		$attr['data-max-items'] = $max_items;
		$attr['data-sort'] = isset($this->params['has_sort']) && empty($this->params['has_sort']) ? 0 : 1;
		$attr['data-visible'] = isset($this->params['open_on_init']) && empty($this->params['open_on_init']) ? 0 : 1;
		$attr['data-required'] = isset($this->params['value_required']) && 
								 empty($this->params['value_required']) ? 0 : 1;
        $attr['data-readonly'] = self::$IS_READONLY ? 1 : 0;

		$html = <<<NVP
<div class="name-value-pairs-btn">
  <a href="#{$widget_id}" class="name-value-pairs-list-open ui-btn ui-corner-all ui-shadow ui-icon-carat-d ui-btn-icon-right ui-btn-icon-right closed">{$this->label}</a>
</div>
NVP;

		$html .= '<div';
		foreach ($attr as $name => $val) {
			$html .= ' '.$name.'="'.$val.'"';
		}
		$html .= '>'."\n";
		
		$html .= "  <input type=\"hidden\" name=\"".$field_name."\"";
		if (self::$ADD_TEMPLATE_VARS) {
			$html .= " value='<%= JSON.stringify(".$this->name.") %>'";
		}
		$html .= " class=\"name-value-pairs-hidden\" />\n";
		
		$html .= <<<NVP
  <div class="name-value-pairs-form">
    <ul id="name-value-list-{$id_postfix}" class="name-value-pairs-list ui-mini" data-role="listview" data-split-icon="delete" data-split-theme="a"></ul>
    <input class="name-value-field-index" type="hidden" value="" />
NVP;
        if (self::$IS_READONLY === false) {
            $html .= <<<NVP
    <div class="form-group">
      <input class="name-value-field-label" type="text" class="form-control" placeholder="{$name_label}" />
    </div>
    <div class="form-group">
      <input class="name-value-field-value" type="text" class="form-control" placeholder="{$value_label}" />
    </div>
    <div class="form-group">
      <button class="name-value-pairs-save btn btn-primary ui-mini ui-btn ui-corner-all ui-icon-action ui-btn-icon-right">Add</button>
    </div>
    <div class="form-group name-value-pairs-cancel-cnt">
      <button class="name-value-pairs-cancel btn btn-primary ui-mini ui-btn ui-corner-all ui-icon-minus ui-btn-icon-right">Cancel</button>
    </div>
NVP;
        }
        $html .= <<<NVP
  </div>
</div>

NVP;

        $ct_attr = array('id' => $this->field_container_id() );
        if ( ! empty($this->params['ct_attr']) ) {
            $ct_attr = $ct_attr + $this->params['ct_attr'];
        }
        self::$has_name_value_widget = true;
        return form_field_wrap($html, $ct_attr);
	}


    /**
     * field_object
     *
     * Creates a hidden input field with a stringified object for the value attribute.
     *
     * @access protected
     * @return string The hidden input field
     */
    protected function field_object() {
        if ($this->type !== 'object') {
            return '';
        }

        $params = $this->params;
        $params['name'] = $this->name;
        $params['use_template_vars'] = self::$ADD_TEMPLATE_VARS;
        $params['attr']['id'] = $this->field_id();
        $params['is_json'] = true;
        return form_hidden($params);
    }
	

	/**
	 * field_password
	 *
	 * Creates a password input field and enclosing div container. Note that,
	 * by default, the value attribute is not populated.
	 * 
	 * @access protected
	 * @return string The password input field and enclosing div container
	 */
	protected function field_password() {
		if ($this->type !== 'password') {
			return '';
		}
		
		$params = $this->params;
		$params['type'] = $this->type;
		$params['label'] = $this->label;
		$params['name'] = $this->name;
		$params['tooltip'] = $this->get_tooltip();
		$params['use_template_vars'] = self::$ADD_TEMPLATE_VARS;
		$params['attr']['id'] = $this->field_id();
		$params['is_readonly'] = self::$IS_READONLY;
        $attr = array('id' => $this->field_container_id() );
        if ( ! empty($params['ct_attr']) ) {
            $attr = $attr + $params['ct_attr'];
        }
		return form_input($params, $attr);
	}
	

	/**
	 * field_radio
	 *
	 * Creates the radio button fields and enclosing div container. Note that
	 * the following field attributes are available to configure the radio buttons 
	 * contained within the parameter $params[field_type][radio]:<br/><br/>
	 * <ul>
	 * <li>is_inline => True to display the radio buttons inline instead of stacked</li>
	 * <li>values => Array of value => label corresponding to each radio button <strong>OR</strong></li>
	 * <li>config => Name of the config file in ./App/Config (without .php ext) whose data populates 
	 * the values and labels of the select options <strong>OR</strong></li>
	 * <li>dir => Name of the directory relative to the web root whose data populate the values
	 * and labels <strong>OR</strong></li>
	 * <li>lang_config => Name of the language file, in the current locale directory (without .php ext), 
	 * whose data populate the values and labels <strong>OR</strong></li>
	 * <li>module => Calls the module model get_id_list() function to populate the values and labels
	 * of the radio buttons</li>
	 * </ul>
	 * 
	 * @access protected
	 * @return string The radio button fields and enclosing div container
	 * @throws \App\Exception\AppException if missing one fo the above required parameters
	 * @see \App\Model\Model::get_id_list() for function description
	 */
	protected function field_radio() {
		if ($this->type !== 'radio') {
			return '';
		} else if (empty($this->params['values']) && 
			( empty($this->params['config_file']) ||
			empty($this->params['config_key']) ) &&
			empty($this->params['dir']) &&
			empty($this->params['lang_config']) && 
			empty($this->params['module']) ) {
			$message = 'Param $config[type] in __construct() missing one of the following for field type "radio": ';
			$message .= '(array) values, (string) config_file + (string) config_key, (string) dir, ';
			$message .= '(string) lang_config, (string) module ['.$this->name.']';
			throw new AppException($message, AppException::ERROR_FATAL);
		}

		$values = array();
		$sort = empty($this->params['sort']) ? false : true;
		if ( isset($this->params['values']) ) {
			$values = $this->params['values'];
			if ($sort) {
				asort($values);
			}
		} else if ( isset($this->params['config_file']) ) {
			$values = $this->values_config($this->params['config_file'], $this->params['config_key'], $sort);
		} else if ( isset($this->params['dir']) ) {
            $show_ext = ! isset($this->params['show_ext']) || ! empty($this->params['show_ext']);
            if ( empty($this->params['filename_only']) ) {
                $values = $this->values_dir($this->params['dir'], $sort, $show_ext);
            } else {
                $values = $this->values_dir_filename($this->params['dir'], $sort, $show_ext);
            }
		} else if ( isset($this->params['lang_config']) ) {
			$values = $this->values_lang($this->params['lang_config'], $sort);
		} else if ( isset($this->params['module']) ) {
			$module = Module::load($this->params['module']);
			$model = $module->get_model();
			$values = $this->values_model($model, $sort);
		} 

		$params = $this->params;
		$params['label'] = $this->label;
		$params['name'] = $this->name;
		$params['values'] = $values;
		$params['tooltip'] = $this->get_tooltip();
		$params['use_template_vars'] = self::$ADD_TEMPLATE_VARS;
		$params['is_readonly'] = self::$IS_READONLY;
		$params['attr']['id'] = $this->field_id();
        $attr = array('id' => $this->field_container_id() );
        if ( ! empty($params['ct_attr']) ) {
            $attr = $attr + $params['ct_attr'];
        }
        if ( ! empty($params['is_inline']) ) {
            $attr = $attr + array('data-type' => 'horizontal');
        }
		return form_radio($params, $attr);
	}
	

	/**
	 * field_regions
	 *
	 * Creates a select dropdown menu of states, provinces or regions in
	 * [region code] => [region name] format. This function uses the regions.php
	 * file in the current locale directory in ./App/Lang. Note that the region list
	 * is sorted by name before populating the select list.
	 * 
	 * @access protected
	 * @return string The region select list and enclosing div container
	 */
	protected function field_regions() {
		if ($this->type !== 'regions') {
			return '';
		}
		
		if ( isset($this->params['values']) ) {
			unset($this->params['values']);
		}
		$this->params['lang_config'] = 'regions';
		return $this->field_select();
	}
	
	
	/**
	 * field_relation
	 *
	 * Creates a .
	 * 
	 * @access protected
	 * @return string The 
	 */
	protected function field_relation() {
		if ($this->type !== 'relation') {
			return '';
		}

        $errors = array();
		if (empty($this->params['module']) ) {          //note: module of relation
			$errors[] = '[module] relation name not defined';
		}
		if ( ! empty($errors) ) {
			$message = 'Invalid param $config[type] in __construct() for field type "relation": ';
			$message .= implode("\n", $errors).' ['.$this->name.']';
			throw new AppException($message, AppException::ERROR_FATAL);
		}

		$module = Module::load($this->data['module']); //note: module containing this field
		$relations = $module->get_relations();
		if ( empty($relations[$this->name]) ) {
			$message = 'Invalid relation ['.$this->name.'] in module ['.$this->data['module'].'] ';
			$message .= 'for field type "relation" ['.$this->name.']';
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$html = '';
		$relation = $relations[$this->name];
		$relation_data = $relation->get_property('module');
		$type = $relation->get_property('relation_type');

		if ( ! empty($this->params['is_custom']) ) {
			$html = $this->field_custom();
		} else if ($type === Relation::RELATION_TYPE_N1 || $type === Relation::RELATION_TYPE_NN) {
            $this->params['placeholder'] = 'Select '.$relation_data['label_plural'];
			$this->params['values'] = $relation->get_property('indep_model')->get_id_list();
			
			if ($type === Relation::RELATION_TYPE_NN) {
				$html = $this->field_multiselect();
			} else {
				$html = $this->field_select();
			}
		} else if ($type === Relation::RELATION_TYPE_1N) {
			$this->label = $relation_data['label'];
            $relation_name = $this->params['module'];
			$rel_module = Module::load($relation_name);
			$data = $rel_module->get_module_data();
			$fields = $rel_module->get_form_fields();
			$field_id = $this->field_id();
            $field_name = $this->name.'[]';
			
			$valid_data = $relation_data['field_data']['validation'];
			$validation = array();
			foreach ($valid_data as $field => $vd) {
				if ( ! empty($vd['valid']) ) {
					$validation[$field] = $vd['valid'];
				}
			}

			$config = array();
			$config['module_name'] = $relation_name;
			$config['fields'] = $fields;
			$config['is_cms'] = true;
			$config['is_relation'] = true;
			$config['is_readonly'] = self::$IS_READONLY;
			$config['data'] = array(
				'object-id' 	=> '#'.$field_id,
				'pk-field' 		=> $relation_data['pk_field'],
				'title-field' 	=> $relation_data['title_field'],
				'defaults' 		=> json_encode($relation_data['field_data']['defaults']),
				'validation'	=> json_encode($validation),
				'sort'			=> $relation_data['use_sort'] ? true : false,
                'readonly'      => self::$IS_READONLY ? 1 : 0,
				'index'			=> ''
			);
			
			$old_id_prefix = str_replace( array(self::FIELD_ID_PREFIX, '-'), '', self::$id_prefix);
			$this->set_template_values(false);
			$Form = new Form($config);
			$form_data = $Form->generate();
			$this->set_id_prefix($old_id_prefix);
			$this->set_template_values(true);
			$hidden = '<input type="hidden" id="'.$field_id.'" name="'.$field_name.'" ';
			$hidden .= 'value=\'<%= JSON.stringify('.$this->name.') %>\' />'."\n";
			$panel_id = 'form-panel-'.$relation_name;

			$html = array();
			$html['hidden'] = $hidden;
			$html['panel_id'] = $panel_id;
			$html['sidebar'] = $this->relation_list_html($data);
			$html['subform'] = array(
				'module' => $relation_name,
				'label'  => $this->label,
				'html'   => $this->relation_subform_html($this->label, $panel_id, $form_data['form'])
			);
			self::$has_subform = true;
		}

		return $html;
	}
	

	/**
	 * field_select
	 *
	 * Creates a select dropdown field and enclosing div container. Note that
	 * the following field attributes are available to configure the select field
	 * contained within the parameter $this->params[select]:<br/><br/>
	 * <ul>
	 * <li>values => Array of value => label corresponding to each select option <strong>OR</strong></li>
	 * <li>config => Name of the config file in ./App/Config (without .php ext) whose data populates 
	 * the values and labels of the select options <strong>OR</strong></li>
	 * <li>dir => Name of the directory relative to the web root whose data populate the values
	 * and labels <strong>OR</strong></li>
	 * <li>lang_config => Name of the language file, in the current locale directory (without .php ext), 
	 * whose data populate the values and labels <strong>OR</strong></li>
	 * <li>module => Calls the module model get_id_list() function to populate the values and labels
	 * of the select options</li>
	 * </ul>
	 * 
	 * @access protected
	 * @return string The select dropdown field and enclosing div container
	 * @throws \App\Exception\AppException if missing one fo the above required parameters
	 * @see \App\Model\Model::get_id_list() for function description
	 */
	protected function field_select() {
		$valid_types = array('select', 'countries', 'multiselect', 'regions', 'relation');
		if ( ! in_array($this->type, $valid_types) ) {
			return '';
		} else if (empty($this->params['values']) && 
			( empty($this->params['config_file']) ||
			empty($this->params['config_key']) ) &&
			empty($this->params['dir']) && 
			empty($this->params['lang_config']) && 
			empty($this->params['module']) ) {
			$message = 'Param $config[type] in __construct() missing one of the following for field type ';
			$message .= '"'.$this->type.'": (array) values, (string) config_file + (string) config_key, ';
			$message .= '(string) dir, (string) lang_config, (string) module ['.$this->name.']';
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$values = array();
		$sort = empty($this->params['sort']) ? false : true;
		if ( isset($this->params['values']) ) {
			$values = $sort ? asort($this->params['values']) : $this->params['values'];
		} else if ( isset($this->params['config_file']) ) {
			$values = $this->values_config($this->params['config_file'], $this->params['config_key'], $sort);
		} else if ( isset($this->params['dir']) ) {
            $show_ext = ! isset($this->params['show_ext']) || ! empty($this->params['show_ext']);
            if ( empty($this->params['filename_only']) ) {
                $values = $this->values_dir($this->params['dir'], $sort, $show_ext);
            } else {
                $values = $this->values_dir_filename($this->params['dir'], $sort, $show_ext);
            }
		} else if ( isset($this->params['lang_config']) ) {
			$values = $this->values_lang($this->params['lang_config'], $sort);
		} else if ( isset($this->params['module']) ) {
			$module = Module::load($this->params['module']);
			$model = $module->get_model();
			$values = $this->values_model($model, $sort);
		}  
		
		$is_multiple= ! empty($this->params['is_multiple']);
		if ( ! $is_multiple) {
			$values = array('' => '-- Select --') + $values;
		}
		
		$params = $this->params;
		$params['label'] = $this->label;
		$params['name'] = $this->name;
		$params['values'] = $values;
		$params['tooltip'] = $this->get_tooltip();
		$params['use_template_vars'] = self::$ADD_TEMPLATE_VARS;
		$params['is_readonly'] = self::$IS_READONLY;
		$params['attr']['id'] = $this->field_id();
        $attr = array('id' => $this->field_container_id() );
        if ( ! empty($params['ct_attr']) ) {
            $attr = $attr + $params['ct_attr'];
        }
		return form_select($params, $attr);
	}
	

	/**
	 * field_text
	 *
	 * Creates a text input field and enclosing div container. Note that
	 * the following field attribute is available to configure the select field
	 * contained within the parameter $this->params:<br/><br/>
	 * <ul>
	 * <li>placeholder => Placeholder text for the text input</li>
	 * </ul>
	 * 
	 * @access protected
	 * @return string The text input field and enclosing div container
	 */
	protected function field_text() {
		$valid_types = array('text', 'date', 'time');
		if ( ! in_array($this->type, $valid_types) ) {
			return '';
		}
		
		$params = $this->params;
		$params['type'] = 'text';
		$params['label'] = $this->label;
		$params['name'] = $this->name;
		$params['tooltip'] = $this->get_tooltip();
		$params['use_template_vars'] = self::$ADD_TEMPLATE_VARS;
		$params['is_readonly'] = self::$IS_READONLY;
		$params['attr']['id'] = $this->field_id();
        $attr = array('id' => $this->field_container_id() );
        if ( ! empty($params['ct_attr']) ) {
            $attr = $attr + $params['ct_attr'];
        }
		return form_input($params, $attr);
	}
	

	/**
	 * field_textarea
	 *
	 * Creates a form textarea and enclosing div container. Note that
	 * the following field attribute is available to configure the textarea
	 * contained within the parameter $params[field_type][textarea]:<br/><br/>
	 * <ul>
	 * <li>placeholder => Placeholder text for the textarea</li>
	 * </ul>
	 * 
	 * @access protected
	 * @return string The textarea field and enclosing div container
	 */
	protected function field_textarea() {
		$valid_types = array('textarea', 'code', 'editor');
		if ( ! in_array($this->type, $valid_types) ) {
			return '';
		}
		
		$params = $this->params;
		
		//add readonly (if applicable) to textarea
		if ($this->type !== 'editor') {
			$params['is_readonly'] = self::$IS_READONLY;
		} 

		$params['label'] = $this->label;
		$params['name'] = $this->name;
		$params['tooltip'] = $this->get_tooltip();
		$params['use_template_vars'] = self::$ADD_TEMPLATE_VARS;
		$params['attr']['id'] = $this->field_id();
        $attr = array('id' => $this->field_container_id() );
        if ( ! empty($params['ct_attr']) ) {
            $attr = $attr + $params['ct_attr'];
        }
		return form_textarea($params, $attr);
	}
	
	
	/**
	 * field_time
	 *
	 * Creates a text input field enabled for entering a time value by
	 * an external plugin
	 * 
	 * @access protected
	 * @return string The text field enabled for time input
	 */
	protected function field_time() {
		if ($this->type !== 'time') {
			return '';
		}
		
		if ( empty($this->params['attr']['class']) || is_array($this->params['attr']['class']) ) {
			$this->params['attr']['class'][] = 'timebox';
		} else {
			$this->params['attr']['class'] .= ' timebox';
		}

		$format = 12;
		$output = '%l:%M %p';
		if ( ! empty($this->params['is_24hr']) ){
			$format = 24;
			$output = '%k:%M';
		} 
		$this->params['attr']['data-time-format'] = $format;
		$this->params['attr']['data-time-output'] = $output;
		self::$has_time = true;
		return $this->field_text();
	}
	
	
	/**
	 * field_upload
	 *
	 * Creates a form widget using jQuery Mobile to upload single or multiple
	 * images and files. Also provides option to delete uploaded files.
	 * 
	 * @access protected
	 * @return string The form upload widget
	 * @throws \App\Exception\AppException if missing config name param or upload configuration
	 * not setup in ./App/Config/uploads.php
	 */
	protected function field_upload() {
		$valid_types = array('image', 'file');
		if ( ! in_array($this->type, $valid_types) ) {
			return '';
		} else if ( empty($this->params['config_name']) ) {
			$message = 'Param $config[type] in __construct() missing [config_name] for field type "'.$this->type.'" ['.$this->name.']';
			throw new AppException($message, AppException::ERROR_FATAL);
		}
	
		$is_image = $this->type === 'image';
		$cfg_name = $this->params['config_name'];
		$config = $this->App->upload_config($cfg_name, $is_image);
		if ( empty($config) ) {
			$message = 'Upload configuration ['.$this->type.'] missing for field "'.$this->name.'" ['.$this->name.']';
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$attr = array();
		$is_multi = true; //$config['max_uploads'] > 1;
		$hide_buttons = self::$IS_READONLY ? ' style="display:none;"' : '';
		$attr['class'] = 'plupload ui-mini';
		$attr['data-module'] = empty($this->data['module']) ? '' : $this->data['module'];
		$attr['data-field'] = $this->name.($is_multi ? '[]' : '');
		$attr['data-upload-dir'] = $config['upload_path'];
		$attr['data-file-ext'] = $config['allowed_types'];
		$attr['data-max-files'] = $config['max_uploads'];
        $attr['data-max-size'] = $config['max_size'];
		$attr['data-is-image'] = $is_image ? 1 : 0;
		$attr['data-is-multi'] = $is_multi ? 1 : 0;
		$attr['data-cfg'] = $cfg_name;
        $attr['data-pk'] = self::$ADD_TEMPLATE_VARS ? '<%= '.$this->data['module_pk'].' %>' : '';
        $attr['data-readonly'] = self::$IS_READONLY ? 1 : 0;
		
		$html = '  '.form_label_tt($this->label, $this->field_id(), $this->get_tooltip() )."\n";
		$html .= '  <div class="field-block plupload-cnt">'."\n";
		$html .= '    <div';
		foreach ($attr as $name => $val) {
			$html .= ' '.$name.'="'.$val.'"';
		}
		
		$html .= " data-files='".(self::$ADD_TEMPLATE_VARS ? "<%= JSON.stringify(uploads.".$this->name.") %>" : '');
		$html .= "'>\n";
		
		$html .= <<<UPLOAD
      <div class="plupload-uploaded-list">
        <p>Uploaded Files</p>
        <ul data-role="listview" data-split-icon="delete" data-split-theme="a" class="plupload-list"></ul>
      </div>
      <div class="plupload-queued-list">
		<p>Queued for Upload: <em>click Upload to save</em></p>
        <ul data-role="listview" data-split-icon="delete" data-split-theme="a" class="plupload-queue"></ul>
      </div>
      <div class="plupload-status"><span class="ui-corner-all"></span></div>
      <div class="plupload-buttons"{$hide_buttons}>
        <button class="btn btn-primary ui-btn plupload-btn plupload-add-btn">+ Add Files</button>
        <button class="btn btn-primary ui-btn plupload-btn plupload-upload-btn" disabled="disabled">Upload</button>
      </div>
      <div class="plupload-hidden">
        <input type="hidden" name="{$attr['data-field']}" value="" />
      </div>

UPLOAD;

		$html .= '    </div>'."\n";	
		$html .= '  </div>'."\n";
        $ct_attr = array('id' => $this->field_container_id() );
        if ( ! empty($this->params['ct_attr']) ) {
            $ct_attr = $ct_attr + $this->params['ct_attr'];
        }
		self::$has_upload = true;
		return form_field_wrap($html, $ct_attr);
	}
	
	
	/**
	 * field_values_widget
	 *
	 * Creates a form widget using jQuery Mobile to add name => value pairs (e.g. 
	 * a numeric array of values) with the option for sorting.
	 * 
	 * @access protected
	 * @return string The values widget
	 */
	protected function field_values_widget() {
		if ($this->type !== 'values_widget') {
			return '';
		}

		$attr = array();
		$value_label = empty($this->params['value_label']) ? 'Value' : $this->params['value_label'];
		$max_items = isset($this->params['max_items']) && is_numeric($this->params['max_items']) ? 
					 $this->params['max_items'] :
					 '';
		$id_prefix = str_replace( array(self::FIELD_ID_PREFIX, '-'), '', self::$id_prefix);
		$id_postfix = (empty($id_prefix) ? '' : $id_prefix.'-').$this->name;
		$widget_id = 'widget-values-'.$id_postfix;
        $field_name = $this->name.'[]';
		
		$attr['id'] = $widget_id;
		$attr['class'] = 'widget-values';
		$attr['data-field'] = $field_name;
		$attr['data-max-items'] = $max_items;
		$attr['data-sort'] = isset($this->params['has_sort']) && empty($this->params['has_sort']) ? 0 : 1;
		$attr['data-visible'] = isset($this->params['open_on_init']) && empty($this->params['open_on_init']) ? 0 : 1;
		$attr['data-required'] = isset($this->params['value_required']) && 
								 empty($this->params['value_required']) ? 0 : 1;
        $attr['data-readonly'] = self::$IS_READONLY ? 1 : 0;

		$html = <<<NVP
<div class="widget-values-btn">
  <a href="#{$widget_id}" class="widget-values-list-open ui-btn ui-corner-all ui-shadow ui-icon-carat-d ui-btn-icon-right ui-btn-icon-right closed">{$this->label}</a>
</div>

NVP;

		$html .= '<div';
		foreach ($attr as $name => $val) {
			$html .= ' '.$name.'="'.$val.'"';
		}
		$html .= '>'."\n";
		
		$html .= "  <input type=\"hidden\" name=\"".$field_name."\"";
		if (self::$ADD_TEMPLATE_VARS) {
			$html .= " value='<%= JSON.stringify(".$this->name.") %>'";
			
		}
		$html .= " class=\"widget-values-hidden\" />\n";
		
		$html .= <<<NVP
  <div class="widget-values-form">
    <ul id="widget-values-list-{$id_postfix}" class="widget-values-list ui-mini" data-role="listview" data-split-icon="delete" data-split-theme="a"></ul>
    <input class="widget-values-field-index" type="hidden" value="" />
NVP;
        if (self::$IS_READONLY === false) {
            $html .= <<<NVP
    <div class="form-group">
      <input class="widget-values-field-value" type="text" class="form-control" placeholder="{$value_label}" />
    </div>
    <div class="form-group">
      <button class="widget-values-save btn btn-primary ui-mini ui-btn ui-corner-all ui-icon-action ui-btn-icon-right">Add</button>
    </div>
    <div class="form-group widget-values-cancel-cnt">
      <button class="widget-values-cancel btn btn-primary ui-mini ui-btn ui-corner-all ui-icon-minus ui-btn-icon-right">Cancel</button>
    </div>
NVP;
        }
        $html .= <<<NVP
  </div><!--close .widget-values-form-->
</div>

NVP;
        $ct_attr = array('id' => $this->field_container_id() );
        if ( ! empty($this->params['ct_attr']) ) {
            $ct_attr = $ct_attr + $this->params['ct_attr'];
        }
        self::$has_values_widget = true;
        return form_field_wrap($html, $ct_attr);
	}
	
	
	/**
	 * get_tooltip
	 *
	 * Returns the tooltip HTML/text content for this form field.
	 * 
	 * @access protected
	 * @return string The tooltip content
	 */
	protected function get_tooltip() {
		$tt = empty($this->data['tooltip']) ? '' : $this->data['tooltip'];
		$tt_lang = empty($this->data['tooltip_lang']) ? '' : $this->data['tooltip_lang'];
		$tooltip = empty($tt_lang) ? (empty($tt) ? '' : $tt) : $this->App->lang($tt_lang);
		if ( ! empty($tooltip) ){
			self::$has_tooltip = true;
		}
		return $tooltip;
	}
	
	
	/**
	 * relation_list_html
	 *
	 * Creates the HTML to select and edit form relational data to edit or delete.
	 * 
	 * @access protected
	 * @param array $relation_data Associative array of the relation module data
	 * @return string The relational list HTML
	 */
	protected function relation_list_html($relation_data) {
		if ( empty($relation_data['name']) ) {
			return '';
		}
		
		$name = $relation_data['name'];
		$label = empty($relation_data['label_plural']) ? '' : $relation_data['label_plural'];
		
		$html = <<<HTML
		
<div id="relation-{$name}" class="relation">
  <a href="#relation-list-{$name}" class="relation-list-open ui-btn ui-corner-all ui-shadow ui-icon-carat-d ui-btn-icon-right ui-btn-icon-right">{$label}</a>
  <div id="relation-list-{$name}" class="relation-list">
HTML;
        if (self::$IS_READONLY === false) {
            $html .= <<<HTML
    <a href="#form-panel-{$name}" data-transition="fade" class="form-panel-add ui-btn ui-corner-all ui-shadow ui-icon-plus ui-mini ui-btn-icon-right">Add New</a>
HTML;
        }
        $html .= <<<HTML
    <ul id="form-panel-list-{$name}" class="form-panel-list ui-mini" data-role="listview" data-split-icon="delete" data-split-theme="a"></ul>
  </div>
</div>

HTML;
		return $html;
	}
	

	/**
	 * relation_subform_html
	 *
	 * Encloses a relational subform in a jQuery Mobile panel container to
	 * enhance the form.
	 * 
	 * @access protected
	 * @param string $label The subform label
	 * @param string $panel_id The subform panel id attribute
	 * @param string $form_html The subform form HTML
	 * @return string The relational subform HTML or empty string if any parameter is empty
	 */
	protected function relation_subform_html($label, $panel_id, $form_html) {
		if ( empty($label) || empty($panel_id) || empty($form_html) ) {
			return '';
		}
		
		$html = <<<HTML
		
<div id="{$panel_id}" class="form-panel" data-role="panel" data-position="right" data-display="push" data-swipe-close="false" data-dismissible="false">

  <div class="ui-corner-all" data-role="header" data-theme="b">
    <a href="#{$panel_id}" class="form-panel-close ui-btn-right" data-icon="delete" data-iconpos="notext" data-shadow="false" data-icon-shadow="false">Close</a>
    <h1>{$label}</h1>
  </div><!--role:header -->
      
  <div class="ui-content">

HTML;
		
		$html .= $form_html;
		$html .= "  </div><!-- role:ui-content -->\n";
		$html .= "</div><!-- role:panel -->\n";
		return $html;
	}
	
	
	/**
	 * validate_params
	 *
	 * Validates a Form_field object to contain valid form field parameters.
	 * 
	 * @access protected
	 * @param array $params The parameters of a form field in a Form_field object
	 * @return bool True if form field parameters are valid
	 * @see \App\Html\Form\Field\Form_field for form field object structure
	 */
	protected function validate_params($params) {
		$field_type = empty($params['field_type']) ? array(false) : $params['field_type'];
		$type = is_array($field_type) ? strtolower( key($field_type) ) : '';
		$field_params = current($field_type);
		return ! empty($type) && isset($this->field_types[$type]) && is_array($field_params) ? true : false;
	}
	
	
	/**
	 * values_config
	 *
	 * Returns an array of value => name pairs used to populate checkboxes, radio buttons or
	 * a select list. Note that the config file may contain an array of value => array, in which case,
	 * it will look for the following indeces for the name value (in order):<br/><br/>
	 * <ul>
	 * <li>lang => Lang index of text in language file of current locale</li>
	 * <li>label => Text for name value</li>
	 * </ul>
	 * 
	 * @access protected
	 * @param string $config_name The config file in ./App/Config directory (minus .php ext)
	 * @param string $config_index Key from $config var for values to use within a config file, 
	 * false to use entire config file
	 * @param bool $sort True to sort the name values, false is default
	 * @return array The config file value => name pair array
	 * @throws \App\Exception\AppException if $config_param param empty
	 */
	protected function values_config($config_name, $config_index=false, $sort=false) {
		if ( empty($config_name) ) {
		    $message = 'Param $config_name empty, must be name of config file ['.$this->name.']';
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$config = $this->App->load_config($config_name);
		if ($config_index !== false && isset($config[$config_index]) ) {
			$config = $config[$config_index];
		}
	
		$values = array();
		if ( ! empty($config) ) {
			foreach ($config as $val => $param) {
				$name = "";
				if ( is_string($param) ) {
					$name = $param;
				} else if ( isset($param['lang']) ) {
					$name = $this->App->lang($param['lang']);
				} else if ( isset($param['label']) ) {
					$name = $param['label'];
				}
				$values[$val] = $name;
			}
		}
		
		if ($sort &&  ! empty($values) ) {
			asort($values);
		}

		return $values;
	}
	
	
	/**
	 * values_dir
	 *
	 * Returns an array of filepath => file used to populate a select list given the
	 * root relative path to the directory. The filepath value will consist of:<br/><br/>
	 * $dir.DIRECTORY_SEPARATOR.[filename]<br/><br/>
	 * Note that this function will recurse into subdirectories.
	 * 
	 * @access protected
	 * @param string $dir The root relative path to the directory
	 * @param bool $sort True to sort the name values, true is default
     * @param string $orig_dir The originating directory, used in recursion
     * @param bool $show_ext True to show the file extension wth the filename
	 * @return array The filepath => file pair array
	 * @throws \App\Exception\AppException if $dir param empty or directory not found
	 */
	protected function values_dir($dir, $sort=true, $show_ext, $orig_dir='') {
		if ( empty($dir) ) { 
			throw new AppException('Param $dir empty, must be name of directory ['.$this->name.']', AppException::ERROR_FATAL);
		} else if ( empty($orig_dir) ) {
            $orig_dir = $dir;
        }
		
		$dir = rtrim($dir, '/');
		if ( substr($dir, 0, 1) !== '/') {
			$dir = '/'.$dir;
		}
		
		if ( ! is_dir(WEB_ROOT.$dir) ) {
			$error = 'Param $dir directory not found ['.$dir.'], must be web root relative path ['.$this->name.']';
			throw new AppException($error, AppException::ERROR_FATAL);
		}

		$values = array();
		if (($handle = @opendir(WEB_ROOT.$dir)) !== false) {
			while ( ($file_or_dir = readdir($handle)) !== false) {
				if ($file_or_dir !== '.' && $file_or_dir !== '..') {
					if (@is_file(WEB_ROOT.$dir.'/'.$file_or_dir) ) {
					    $rel_path = str_replace('/'.$orig_dir.'/', '', $dir.'/'.$file_or_dir);
                        if ($show_ext === false) {
                            $rel_path = substr($rel_path, 0, strrpos($rel_path, '.') );
                        }
						$values[$rel_path] = $rel_path;
					} else {
						$recursion = $this->values_dir($dir.'/'.$file_or_dir, $sort, $show_ext, $orig_dir);
						if ( ! empty($recursion) ) {
							$values = array_merge($values, $recursion);
						}
					}
				}
			}
			closedir($handle);
		}
		
		if ($sort &&  ! empty($values) ) {
			asort($values);
		}
		
		return $values;
	}


    /**
     * values_dir_filename
     *
     * Similar to values_dir but returns an array of filename => filename used to populate a
     * select list given the root relative path to the directory. The file extension can
     * optionally be excluded by setting the $show_ext param to false<br/><br/>
     * Note that this function WILL NOT recurse into subdirectories.
     *
     * @access protected
     * @param string $dir The root relative path to the directory
     * @param bool $sort True to sort the name values, true is default
     * @param bool $show_ext True to show the file extension wth the filename
     * @return array The filename => file filename array
     * @throws \App\Exception\AppException if $dir param empty or directory not found
     */
    protected function values_dir_filename($dir, $sort=true, $show_ext=true) {
        if ( empty($dir) ) {
            throw new AppException('Param $dir empty, must be name of directory ['.$this->name.']', AppException::ERROR_FATAL);
        } else if ( empty($orig_dir) ) {
            $orig_dir = $dir;
        }

        $dir = rtrim($dir, '/');
        if ( substr($dir, 0, 1) !== '/') {
            $dir = '/'.$dir;
        }

        if ( ! is_dir(WEB_ROOT.$dir) ) {
            $error = 'Param $dir directory not found ['.$dir.'], must be web root relative path ['.$this->name.']';
            throw new AppException($error, AppException::ERROR_FATAL);
        }

        $values = array();
        if (($handle = @opendir(WEB_ROOT.$dir)) !== false) {
            while ( ($file_or_dir = readdir($handle)) !== false) {
                if ($file_or_dir !== '.' && $file_or_dir !== '..') {
                    if (@is_file(WEB_ROOT.$dir.'/'.$file_or_dir) ) {
                        $filename = $file_or_dir;
                        if ($show_ext === false) {
                            $filename = substr($filename, 0, strrpos($filename, '.') );
                        }
                        $values[$filename] = $filename;
                    }
                }
            }
            closedir($handle);
        }

        if ($sort &&  ! empty($values) ) {
            asort($values);
        }

        return $values;
    }
	
	
	/**
	 * values_lang
	 *
	 * Returns an array of value => name pairs from a language file in the current locale 
	 * in ./App/Lang to populate checkboxes, radio buttons or a select list.
	 * 
	 * @access protected
	 * @param string $lang_file The lang file in current locale in ./App/Lang directory (minus .php ext)
	 * @param bool $sort True to sort the name values, false is default
	 * @return array The lang file value => name pair array
	 * @throws \App\Exception\AppException if $lang_file param empty
	 */
	protected function values_lang($lang_file, $sort=false) {
		if ( empty($lang_file) ) { 
			$error = 'Param $lang_file empty, must be name of locale lang file ['.$this->name.']';
			throw new AppException($error, AppException::ERROR_FATAL);
		}
		
		$values = $this->App->load_lang($lang_file);
		if ($sort &&  ! empty($values) ) {
			asort($values);
		}
		return $values;
	}
	

	/**
	 * values_model
	 *
	 * Returns a value => name pair array from a model table used for populating 
	 * checkboxes, radio buttons or select lists. Calls the module model's get_id_list()
	 * function.
	 * 
	 * @access protected
	 * @param \App\Model\Model Instance of the database model
	 * @param bool $sort True to sort the name values, false is default
	 * @return array The module value => name pair array
	 * @throws \App\Exception\AppException if $model param not a \App\Model\Model instance
	 * @see \App\Model\Model::get_id_list() for function description
	 */
	protected function values_model($model, $sort=false) {
		$error = '';
		if ($model instanceof \App\Model\Model === false) { 
			$error = 'Param $model must be of type \\App\\Model\\Model ['.$this->name.']';
			throw new AppException($error, AppException::ERROR_FATAL);
		}

		$values = $model->get_id_list();
		if ($sort && ! empty($values) ) {
			asort($values);
		}
		return $values;
	}
	
}

/* End of file Form_field.php */
/* Location: ./App/Html/Form/Field/Form_field.php */