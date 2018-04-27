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
 * AdminSortForm class
 *
 * This class creates an HTML admin form to sort rows of module records. If the modules contains n:1 or n:n
 * relations, the ability to sort by those will be available
 *
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Html\Form
 */
class AdminSortForm {

    /**
     * @const string URL segment in CMS used to sort by module
     */
    const SORT_URI_SEGMENT = 'sort';

    /**
     * @const  string URL segment to sort all records of a module
     */
    const ALL_SEGMENT = 'all';

    /**
     * @var int Minimum items to use jQuery UI grid styles instead of Mobile Listview styles
     */
    protected static $GRID_MIN_ITEMS = 5;

    /**
     * @var \App\App Instance of the main App class
     */
    protected $App;

    /**
     * @var string Field name of module relation to sort
     */
    protected $field_name;

    /**
     * @var array Array of name => value attributes for form tag
     */
    protected $form_attr;

    /**
     * @var mixed A string or array of class attributes for form tag
     */
    protected $form_class;

    /**
     * @var string The form id attribute
     */
    protected $form_id;

    /**
     * @var bool True if this form's fields are readonly
     */
    protected $is_readonly;

    /**
     * @var string Current language of application (e.g. en, es)
     */
    protected $lang;

    /**
     * @var \App\Module\Module module instance for form
     */
    protected $module;

    /**
     * @var string Name of the CMS module or frontend form
     */
    protected $module_name;

    /**
     * @var array Assoc array of params for sort form indexed by field name
     */
    protected $params;

    /**
     * @var int Independant relation row ID if sorting by relation
     */
    protected $relation_id;

    /**
     * @var array Array of \App\Model\Relation used to populate select, multiselect
     * or relational subforms
     */
    protected $relations;

    /**
     * @var string Title header for the form
     */
    protected $title;


    /**
     * Constructor
     *
     * Initializes the Sort form with the given configuration parameters in assoc array:<br/><br/>
     * <ul>
     * <li>module_name => The module creating this form (required)</li>
     * <li>field_name => The field name, if sorting by relation</li>
     * <li>relation_id => The independant relation row ID, if sorting by relation</li>
     * <li>attr => Array of name => value attributes to add to form tag</li>
     * <li>class => String or array of class attributes to add to form tag</li>
     * <li>is_readonly => True if form is readonly (disallows edits)</li>
     * <li>redirect => URL fragment to route to after form submitted</li>
     * <li>title => The title, appearing above the form</li>
     * <li>params => Optional parameters for sort form jumplists:<br/><br/>
     * [field name OR 'all'] => array(<br/>
     * &nbsp;&nbsp;&nbsp;name => Descriptive name<br/>
     * &nbsp;&nbsp;&nbsp;values => Assoc array of [value]=>[name] of fields to sort items<br/>
     * ) OR [true] to activate jumpmenu (default) OR [false] to deactivate</li>
     * </ul>
     *
     * @access public
     * @param array $config The form configuration array
     * @throws \App\Exception\AppException if $config assoc array missing required parameters
     * @see \App\Html\Form\Field\Form_field for form field object structure
     */
    public function __construct($config) {
        $this->App = App::get_instance();
        $this->App->load_util('form_jqm');
        $errors = array();

        if ( empty($config['module_name']) ) {
            $errors[] = error_str('error.module.slug', '$config[module_name]');
        }

        $this->module_name = $config['module_name'];
        $this->module = Module::load($this->module_name);
        if ( $this->module->has_sort() === false ) {
            $errors[] = error_str('error.module.sorting', '$config[module_name] "'.$this->module_name.'"');
        }
        if ( ! empty($errors) ) {
            $message = error_str('error.type.param.invalid', array('(array) $config: '));
            $message .= implode(",\n", $errors);
            throw new AppException($message, AppException::ERROR_FATAL);
        }

        $locale = $this->App->config('locale');
        $parts = explode('_', $locale);
        $this->locale = empty($parts[0]) ? 'en' : $parts[0];
        $form_class = 'form-sort';

        $this->title = empty($config['title']) ? '' : $config['title'];
        $this->is_readonly = ! empty($config['is_readonly']);
        $this->form_attr = empty($config['attr']) ? array() : $config['attr'];
        $this->form_id = $form_class.'-'.$this->module_name;
        $this->field_name = empty($config['field_name']) ? '' : $config['field_name'];
        $this->relation_id = empty($config['relation_id']) ? '' : $config['relation_id'];
        $this->redirect = empty($config['redirect']) ? '' : $config['redirect'];
        $this->form_class = empty($config['class']) ? array() : $config['class'];
        if ( is_array($this->form_class) && ! in_array($form_class, $this->form_class) ) {
            $this->form_class[] = $form_class;
        } else if ( is_string($this->form_class)  ) {
            $parts = explode(' ', $this->form_class);
            if ( ! in_array($form_class, $parts) ) {
                $this->form_class = $form_class.' '.$this->form_class;
            }
        }
        $this->params = empty($config['params']) ? array() : $config['params'];
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
     * @throws \App\Exception\AppException if an error occurs while loading module, handled by \App\App class
     */
    public function generate() {
        $module_data = $this->module->get_module_data();
        $relations = $this->module->get_relations();
        $module_name = $module_data['name'];
        $class = $this->class_attr();
        $attr = $this->attributes();
        $admin_uri_segment = $this->App->config('admin_uri_segment');
        $sort_all_records = 'Sort All Records';
        $is_all_records = $this->field_name === self::ALL_SEGMENT;
        $subheading = $is_all_records ? $sort_all_records : '';
        $sort_uri = $this->module_name.'/'.self::SORT_URI_SEGMENT;
        $admin_fragment = $admin_uri_segment.'/'.$sort_uri;
        $form_base_url = WEB_BASE.'/'.API_DIR.'/'.$admin_fragment;
        $param_list = array(self::ALL_SEGMENT);

        $action = ' action="'.$form_base_url.(empty($this->field_name) ? '' : '/'.$this->field_name);
        $action .= (empty($this->relation_id) ? '' : '/'.$this->relation_id).'"';
        $is_all = ' data-is-all="'.($is_all_records ? 1 : 0).'"';
        $readonly = ' data-readonly="'.($this->is_readonly ? 1 : 0).'"';
        $form_start = '<form id="'.$this->form_id.'"'.$action.$class.$attr.$readonly.$is_all.' role="form">'."\n";
        $form_html = '';
        $select_html_1n = '';
        $select_html_custom = '';

        $params = array(
            'name' => 'ids',
            'attr' => array('id' => 'sort-ids'),
            'is_json' => true,
            'use_template_vars' => true
        );
        $hidden_html = form_hidden($params);

        $form_start .= '<div id="form-main">'."\n";

        if ( ! empty($this->title) ) {
            $form_start .= '<h1>'.$this->title.'</h1>'."\n";
        }

        if ( ! empty($relations) ) {
            $Module = Module::load();
            $module_id = $module_data['id'];
            $data = $Module->get_data($module_id, false, false);
            $form_fields = $data['form_fields'];
            $rel_names = array();
            foreach ($relations as $field_name => $rel) {
                foreach ($form_fields as $ff) {
                    if ($field_name === $ff['name']) {
                        $rel_names[$field_name] = empty($ff['lang']) ? $ff['label'] : $this->App->lang($ff['lang']);
                        break;
                    }
                }
            }

            foreach ($relations as $field_name => $rel) {
                if ( isset($this->params[$field_name]) && $this->params[$field_name] === false ) {
                // sorting deactivated for relation field
                    continue;
                }
                $type = $rel->get_property('relation_type');
                $is_1n = $type === Relation::RELATION_TYPE_1N;
                $model = $is_1n ? 'dep_model' : 'indep_model';
                $rel_module = $rel->get_property('module');
                $list = empty($this->params[$field_name]['values']) ?
                        $rel->get_property($model)->get_id_list() :
                        $this->params[$field_name]['values'];
                $root_base = $admin_fragment.'/'.$field_name.'/';

                $title = $is_1n ? 'Sort ' : 'Sort by ';
                if ( empty($this->params[$field_name]['name']) ) {
                    $title .= isset($rel_names[$field_name]) ? $rel_names[$field_name] : $rel_module['label_plural'];
                } else {
                    $title .= $this->params[$field_name]['name'];
                }

                $values = array('' => $title);
                foreach ($list as $id => $name) {
                    $values[$root_base.$id] = $title.": ".$name;
                }
                $has_value = $field_name === $this->field_name;
                if ($has_value) {
                    $subheading = $title;
                }

                $params = array();
                $params['label'] = '';
                $params['name'] = $field_name;
                $params['values'] = $values;
                $params['value'] = $has_value ? $root_base.$this->relation_id : '';
                $params['use_template_vars'] = false;
                //$params['is_readonly'] = $this->is_readonly ? true : false;
                $params['attr']['id'] = 'sort-relation-'.$field_name;
                $params['attr']['class'] = 'sort-relation';
                if ($is_1n) {
                    $select_html_1n .= form_select($params);
                } else {
                    $form_html .= form_select($params);
                }
                $param_list[] = $field_name;
            }
        }

        if ( ! empty($this->params) ) {
            foreach ($this->params as $field_name => $params) {
                if ( in_array($field_name, $param_list) || empty($params['values']) || ! is_array($params['values']) ||
                    empty($params['name']) ) {
                    continue;
                }

                $root_base = $admin_fragment.'/'.$field_name.'/';
                $title = 'Sort by '.$params['name'];
                $values = array('' => $title);
                foreach ($params['values'] as $id => $name) {
                    $values[$root_base.$id] = $title.": ".$name;
                }
                $has_value = $field_name === $this->field_name;
                if ($has_value) {
                    $subheading = $title;
                }

                $select = array();
                $select['label'] = '';
                $select['name'] = $field_name;
                $select['values'] = $values;
                $select['value'] = $has_value ? $root_base.$this->relation_id : '';
                $select['use_template_vars'] = false;
                //$select['is_readonly'] = $this->is_readonly ? true : false;
                $select['attr']['id'] = 'sort-relation-'.$field_name;
                $select['attr']['class'] = 'sort-relation';
                $select_html_custom .= form_select($select);
            }
        }


        if ( ! isset($this->params[self::ALL_SEGMENT]) || ! empty($this->params[self::ALL_SEGMENT]) ) {
            $params = array();
            $params['label'] = $sort_all_records;
            //$params['is_readonly'] = $this->is_readonly ? true : false;
            $params['attr']['id'] = 'module-sort-all';
            $params['attr']['type'] = 'button';
            $params['attr']['data-fragment'] = $admin_fragment.'/'.self::ALL_SEGMENT;
            $form_html .= form_button($params);
        }

        $form_html = $select_html_custom.$select_html_1n.$form_html;
        $form_html .= $this->sort_items_html($subheading);
        $form_html .= "\n</div><!-- #form-main -->\n";

        //sidebar for relations, serialized and Submit/Cancel/Delete buttons
        $form_html .= '<div id="form-sidebar">'."\n";

        $default = array('id', 'name', 'class2');
        $redirect = empty($this->redirect) ? $admin_uri_segment.'/'.$this->module_name.'/list' : $this->redirect;
        $btn_attr = array('data-redirect' => $redirect);
        if ($this->is_readonly === false) {
            $id_attr = array('id' => Form_field::CONTAINER_ID_PREFIX.$module_name.'-submit');
            $form_html .= Form_field::form_button('submit', $default, $btn_attr, $id_attr);
        }
        $id_attr = array('id' => Form_field::CONTAINER_ID_PREFIX.$module_name.'-cancel');
        $form_html .= Form_field::form_button('cancel', $default, $btn_attr, $id_attr);

        $form_html .= '</div><!-- #form-sidebar -->'."\n";

        $form_html .= '</form>';

        $hidden_html = '  <div class="form-group form-hidden">'."\n".$hidden_html."\n  </div>\n";

        $form_html = $form_start.$hidden_html.$form_html;

        $ret = array();
        $ret['form_id'] = $this->form_id;
        $ret['form'] = $form_html;
        $ret['fields'] = array(
            'ids' => array('is_multiple' => true),
            'items' => array()
        );
        $ret['css_includes'] = array(
            'plugins/abstract/jquery.mobile.sort.css'
        );
        $ret['js_includes'] = array(
            'abstract/jquery.mobile.cms-forms.js',
            'abstract/jquery.mobile.sort.js'
        );
        $ret['js_load_block'] = '';
        $ret['js_unload_block'] = '';

        return $ret;
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
        $this->form_class[] = 'form-sort';
        if ( isset($this->form_attr['class']) ) {
            $attr = is_string($this->form_attr['class']) ? array($this->form_attr['class']) : $this->form_attr['class'];
            $this->form_class = array_merge($this->form_class + $attr);
        }
        return empty($this->form_class) ? '' : ' class="'.implode(' ', $this->form_class).'"';
    }


    /**
     * sort_items_html
     *
     * Generates the template for the sort form.
     *
     * @access private
     * @param string $head_title Title for the items to sort
     * @return string The underscore template for sortable items
     */
    private function sort_items_html($head_title) {
        $grid_min = self::$GRID_MIN_ITEMS;
        $disabled = $this->is_readonly ? ' disabled' : '';
        $class = $this->is_readonly ? 'class="disabled" ' : '';
        return <<<HTML

<% if (items !== false) { %>  

    <% if (items.length) { var has_grid = items.length >= {$grid_min}; %>

<h2>{$head_title}</h2>

<div id="sort-list-cnt" class="form-group">
  <ul id="sort-list" data-role="<%= has_grid ? 'none' : 'listview' %>" <%= has_grid ? ' class="sort-grid{$disabled}"' : '{$class}data-split-theme="a"' %>>

        <% for (var i=0; i < items.length; i++) { var item = items[i]; %>

    <li data-id="<%= item['id'] %>"<% if (has_grid && item['image'].length) { %> style="background-image:url(<%= item['image'] %>);"<% } %>>
      <% if ( ! has_grid && item['image'].length) { %><img src="<%= item['image'] %>" /><% } %>
      <div class="sort-number"><%= (i + 1) %></div>
      <h3><%= item['name'] %></h3>
    </li>

        <% } %>

  </ul>
</div>

    <% } else { %>

<p id="sort-no-items">There are currently no items to sort.</p>

    <% } %>

<% } %>

HTML;

    }
}

/* End of file AdminSortForm.php */
/* Location: ./App/Form/AdminSortForm.php */