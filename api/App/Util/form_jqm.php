<?php

/**
 * form_attr_str
 *
 * Creates the name => value attribute string for an input field.
 * 
 * @param array $params The field parameters<br/><br/>
 * <ul>
 * <li>type => The form field type [button, checkbox, fieldset, file, hidden, password, 
 * select, text, textarea] (required)</li>
 * <li>name => The form field name attribute (required)</li>
 * <li>
 * 	 attr => 
 *   <ul>
 *     <li>name => The form field name attribute (optional, can be set here or in above index)</li>
 *     <li>id => The form field id attribute (optional)</li>
 *     <li>class => An array or string for form field class attribute(optional)</li>
 *     <li>[attribute] => [value], Any other name => value attribute pairs (e.g. data-id => 1)</li>
 *   </ul>
 * </li>
 * <li>is_multiple => True/false if field is a form field array (optional)</li>
 * <li>is_readonly => True/false if field is readonly (disabled) (optional, default false)</li>
 * <li>use_template_vars => True/false to use Underscore template vars to populate form fields
 * (optional, default true)</li>
 * </ul>
 * @param string $select_value The value attribute for a checkbox/radio button
 * @param int $index The index if the form field is an array of input fields
 * @return string The attribute string
 */
if ( ! function_exists('form_attr_str'))
{
	function form_attr_str($params, $select_value=false, $index=false) {
		$accepted = array(
			'button', 
			'checkbox', 
			'fieldset', 
			'file',
			'hidden', 
			'password',
			'radio', 
			'select', 
			'text',
			'textarea'
		);
		
		if ( empty($params['type']) || ! in_array($params['type'], $accepted) ) {
			return '';
		}

		$no_value = array(
			'button', 
			'fieldset', 
			'file', 
			'select', 
			'password',
			'textarea'
		);

		$type = $params['type'];
		$field_name = empty($params['name']) ? (empty($params['attr']['name']) ? '' : $params['attr']['name']) : $params['name'];
		$has_index = is_numeric($index);
		$is_multiple = ! empty($params['is_multiple']);
		$use_template_vars = ! isset($params['use_template_vars']) || ! empty($params['use_template_vars']);
		$attr = array();
		$str = '';

		if ( ! in_array($type, array('button', 'select', 'textarea') ) ) {
			$attr['type'] = $type;
		}
		if ( ! empty($params['attr']['id']) ) {
			$attr['id'] = $params['attr']['id'].($has_index ? '-'.$index : '');
		}
		if ( ! empty($field_name) ) {
			$attr['name'] = $field_name.($is_multiple ? '[]' : '');
		}

		if ($type === 'checkbox' || $type === 'radio') {
            $select_value = str_replace('"', '&quot;', $select_value); //escape double quotes for value attr
            $attr['value'] = $select_value;
		} else if ($type === 'select' && $is_multiple) {
			$attr['multiple'] = 'multiple';
		} else if ( ! empty($field_name) && ! in_array($type, $no_value) && ($use_template_vars || isset($params['value']) ) ) {
		    if ($use_template_vars) {
                $is_json = ! empty($params['is_json']);
                $oper = in_array($type, array('hidden', 'text')) && !$is_json ? '<%- ' : '<%= ';
                $value = $is_json ?
                    $oper.'JSON.stringify('.$field_name.') %>' :
                    $oper.$field_name.($has_index ? '[' . $index . ']' : '').' %>';
            } else {
                $value = $params['value'];
            }
			$attr['value'] = $value;
		}
		$attr['class'] = in_array($type, array('button', 'hidden') ) ? array() : array('form-control');

		if ( ! empty($params['placeholder']) ) {
			$attr['placeholder'] = $params['placeholder'];
		}
		if ( ! empty($params['attr']) && is_array($params['attr']) ) {
			if ( ! empty($params['attr']['class']) ) {
				$class = $params['attr']['class'];
				if ( is_array($class) ) {
					$attr['class'] = array_merge($attr['class'], $class);
				} else {
					$attr['class'][] = $class;
				}
				unset($params['attr']['class']);
			}
			$attr = $attr + $params['attr'];
		}
		
		
		foreach ($attr as $name => $value) {
			if ( is_array($value) ) {
				$value = implode(' ', $value);
			}

			$is_json = is_json_obj($value) || ($name === 'value' && ! empty($params['is_json']) );
            if ( ! $is_json) {
                $value = str_replace('"', '&quot;', $value); //escape double quotes for attr
            }
			$str .= $name.($is_json ? "='".$value."' " : '="'.$value.'" ');
		}
		
		if ($use_template_vars && ($type === 'checkbox' || $type === 'radio') ) {
			$op = $is_multiple ? 
				  $field_name.'.lastIndexOf("'.$select_value.'")!==-1' : 
				  $field_name."=='".$select_value."'";
			$str .= '<% if ('.$op.') { %>checked="checked" <% } %>';
		}
		
		if ( ! empty($params['is_readonly']) ) {
			$str .= 'disabled="disabled" ';
		}
		
		return $str;
	}
}


/**
 * form_button
 *
 * Creates a form button and enclosing div container. 
 * 
 * @param array $params The button field parameters:<br/><br/>
 * <ul>
 * <li>label => Label tag text for checkbox group (required)</li>
 * <li>Also, the parameters in function form_attr_str() also apply to $params var in this function</li>
 * </ul>
 * @param array $attr Assoc array of attributes for enclosing div container
 * @return string The button field and enclosing div container OR empty string if $params['label']
 * not defined or empty
 */
if ( ! function_exists('form_button'))
{
	function form_button($params, $attr=array()) {
		if ( empty($params) || empty($params['label']) ) {
			return '';
		}
		
		$params['type'] = 'button';
		$class = array();
		if ( isset($params['attr']['class']) ) {
			$class = $params['attr']['class'];
			if ( is_string($class) ) {
				$class = explode(' ', $class);
			}
			$class = array_values($class);
		}
		if ( ! in_array('btn', $class) ) {
			$class[] = 'btn';
		}
		if ( ! in_array('btn-primary', $class) ) {
			$class[] = 'btn-primary';
		}
		$params['attr']['class'] = $class;
		
		$html = '  <button '.form_attr_str($params).'>'.$params['label'].'</button>'."\n";
		return form_field_wrap($html, $attr);
	}
}


/**
 * form_checkbox
 *
 * Creates one or more checkbox fields and enclosing div container.<br/><br/>
 * Also, if there are more than one checkboxes, the checkbox field name will
 * automatically be appended with "[]" to be a POST array var.
 * 
 * @param array $params The checkbox field parameters:<br/><br/>
 * <ul>
 * <li>is_inline => True to display the checkboxes inline instead of stacked (optional, default false)</li>
 * <li>label => Label tag text for checkbox group (optional)</li>
 * <li>value => String or array of values to select as checked. NOTE: use_template_vars parameter
 * must be set to false for these values to be checked</li>
 * <li>tooltip => HTML/text for help information for this field (optional)</li>
 * <li>values => Array of value => label corresponding to each checkbox (required)</li>
 * <li>Also, the parameters in function form_attr_str() also apply to $params var in this function</li>
 * </ul>
 * @param bool $has_label True if the checkbox(es) have a label for the group as a whole
 * @param array $attr Assoc array of attributes for enclosing div container
 * @return string The checkbox fields and enclosing div container
 */
if ( ! function_exists('form_checkbox'))
{
	function form_checkbox($params, $has_label=true, $attr=array()) {
		if ( empty($params) || empty($params['values']) ) {
			return '';
		}
		
		$html = '';
		$params['type'] = 'checkbox';
		$values = $params['values'];
        $is_inline = ! empty($params['is_inline']);
		$has_value = isset($params['use_template_vars']) && empty($params['use_template_vars']) && isset($params['value']);
		
		if ( empty($params['is_multiple']) && count($values) > 1 ) {
			$params['is_multiple'] = true;
		}
		
		if ($has_label && ! empty($params['label']) ) {
			$id = empty($params['attr']['id']) ? '' : $params['attr']['id'];
			$tooltip = empty($params['tooltip']) ? '' : $params['tooltip'];
			$html .= '  '.form_label_tt($params['label'], $id, $tooltip)."\n";
		}

        $html .= $is_inline ? '  <fieldset data-role="controlgroup" data-type="horizontal">'."\n" : '';

		$count = 0;
		foreach ($values as $val => $name) {
			$is_val = $has_value && ((is_array($params['value']) && in_array($val, $params['value'])) || $params['value'] == $val);
			$checked = $is_val ? 'checked="checked"' : '';
            $html .= '  <label>'."\n";
			$html .= '    <input '.form_attr_str($params, $val, $count++).$checked.'/> '.$name."\n";
            $html .= '  </label>'."\n";
		}

        $html .= $is_inline ? '  </fieldset>'."\n" : '';
		return form_field_wrap($html, $attr);
	}
}


/**
 * form_field_wrap
 *
 * Wraps a form field label, tooltip and field HTML in a div tag wrapper.
 * 
 * @param string $html The form field HTML to wrap
 * @param array $attr Optional atributes to add to the wrapper div
 * @return string The wrapped form field HTML
 */
if ( ! function_exists('form_field_wrap'))
{
	function form_field_wrap($html, $attr=array()) {
		$wrap = "<div%s>\n%s</div>\n";
		$class = 'form-group';
		$attr_str = '';
		if ( ! empty($attr) ) {
			$has_class = false;
			foreach ($attr as $name => $value) {
				if ($name === 'class') {
					$attr_str .= ' class="'.$class.' '.(is_array($value) ? implode(' ', $value) : $value).'"';
					$has_class = true;
				} else {
					$attr_str .= ' '.$name.(is_json_obj($value) ? "='".$value."'" : '="'.$value.'"');
				}
			}
			if ( ! $has_class) {
				$attr_str = ' class="'.$class.'"'.$attr_str;
			}
		} else {
			$attr_str = ' class="'.$class.'"';
		}

		return sprintf($wrap, $attr_str, $html);
	}
}


/**
 * form_flipswitch
 *
 * Creates a jQuery Mobile Flipswitch using an html select and corresponding to
 * a true/false or yes/no type input.
 * 
 * @param array $params The field parameters:<br/><br/>
 * <ul>
 * <li>label => Label tag text for input fied (optional)</li>
 * <li>
 * 	 attr => 
 *   <ul>
 *     <li>data-off-text => Text for off state(optional)</li>
 *     <li>data-on-text => Text for on state (optional)</li>
 *   </ul>
 * </li>
 * <li>Also, the parameters in function form_attr_str() also apply to $params var in this function</li>
 * </ul>
 * @param array $attr Assoc array of attributes for enclosing div container
 * @return string The JQuery Mobile flipswitch field and enclosing div container
 */
if ( ! function_exists('form_flipswitch'))
{
	function form_flipswitch($params, $attr=array()) {
		$params['type'] = 'select';
		$params['attr']['data-role'] = 'flipswitch';
		$values = array(
			0 => (empty($params['attr']['data-off-text']) ? 'No' : $params['attr']['data-off-text']),
			1 => (empty($params['attr']['data-on-text']) ? 'Yes' : $params['attr']['data-on-text'])
		);

		$html = "  <label>\n";
		$html .= '    <select '.form_attr_str($params).'>'."\n";
		
		foreach ($values as $val => $name) {
			$html .= '      <option value="'.$val.'"';
			if ( ! empty($params['name']) && ! isset($params['use_template_vars']) || ! empty($params['use_template_vars']) ) {
				$op = $params['name'].'=="'.$val.'"';
				$html .= '<% if ('.$op.') { %> selected="selected"<% } %>';
			}
			$html .= '>'.$name."</option>\n";
		}
		
		$html .= "    </select> ".$params['label']."\n";
		$html .= "  </label>\n";
		return form_field_wrap($html, $attr);
	}
}


/**
 * form_hidden
 *
 * Creates a hidden input field.
 * 
 * @param $mixed Field name attr (string) OR array of name => value field attributes
 * @return string The hidden input field HTML
 */
if ( ! function_exists('form_hidden'))
{
	function form_hidden($mixed) {
		$params = array();
		if ( is_array($mixed) ) {
			$params = $mixed;
			$params['type'] = 'hidden';
		} else {
			$params = array(
				'type' => 'hidden', 
				'name' => $mixed
			);
		}
		
		return '<input '.form_attr_str($params).'/>'."\n";
	}
}


/**
 * form_input
 *
 * Creates a form input field and enclosing div container.
 * 
 * @param array $params The field parameters:<br/><br/>
 * <ul>
 * <li>type => The input type attribute, [file, hidden, password, text] (required)</li>
 * <li>label => Label tag text for input fied (optional)</li>
 * <li>tooltip => HTML/text for help information for this field (optional, label required if using)</li>
 * <li>Also, the parameters in function form_attr_str() also apply to $params var in this function</li>
 * </ul>
 * @param array $attr Assoc array of attributes for enclosing div container
 * @return string The form input field HTML
 */
if ( ! function_exists('form_input'))
{
	function form_input($params, $attr=array()) {
		$accepted = array(
			'file',
			'hidden', 
			'password',
			'text'
		);

		if ( empty($params['type']) || ! in_array($params['type'], $accepted) ) {
			return '';
		}
		
		$html = '';
		if ( ! empty($params['label']) ) {
			$id = empty($params['attr']['id']) ? '' : $params['attr']['id'];
			$tooltip = empty($params['tooltip']) ? '' : $params['tooltip'];
			$html .= '  '.form_label_tt($params['label'], $id, $tooltip)."\n";
		}
		
		$html .= '  <input '.form_attr_str($params).'/>'."\n";
		return form_field_wrap($html, $attr);
	}
}


/**
 * form_label_tt
 *
 * Creates a form field label and sets the field tooltip.
 * 
 * @param string $label The Label tag text
 * @param string $field_id The form field id used in the for attribute for the label
 * @param string $tooltip Tooltip content to add to form field, note that an icon is
 * placed in the label to open the tooltip content
 * @return string The form field label and optional tooltip
 */
if ( ! function_exists('form_label_tt'))
{
	function form_label_tt($label, $field_id='', $tooltip='') {
		$has_tooltip = ! empty($tooltip);
		if ($has_tooltip) {
			$label .= ' <div class="tooltip"></div>';
		}
		$for = empty($field_id) ? '' : 'for="'.$field_id.'" ';
		$html = '<label '.$for.'class="control-label">'.$label.'</label>';
		if ($has_tooltip) {
			$html .= "\n".'  <div class="tooltip-content">'."\n";
			$html .= '    <div class="tooltip-info"></div>'."\n".$tooltip."\n  </div>";
		}
		return $html;
	}
}


/**
 * form_radio
 *
 * Creates the radio button fields and enclosing div container. 
 * 
 * @param array $params The radio button field parameters:<br/><br/>
 * <ul>
 * <li>is_inline => True to display the checkboxes inline instead of stacked (optional, default false)</li>
 * <li>label => Label tag text for radio button group (optional)</li>
 * <li>value => Value to select as checked. NOTE: use_template_vars parameter
 * must be set to false for this values to be checked</li>
 * <li>tooltip => HTML/text for help information for this field (optional)</li>
 * <li>values => Array of value => label corresponding to each checkbox (required)</li>
 * <li>Also, the parameters in function form_attr_str() also apply to $params var in this function</li>
 * </ul>
 * @param array $attr Assoc array of attributes for enclosing div container
 * @return string The radio button fields and enclosing div container
 */
if ( ! function_exists('form_radio'))
{
	function form_radio($params, $attr=array()) {
		if ( empty($params) || empty($params['values']) ) {
			return '';
		}
		
		$html = '';
		$params['type'] = 'radio';
		$values = $params['values'];
		$label = empty($params['label']) ? '' : $params['label'];
		$is_inline = ! empty($params['is_inline']);
		$has_value = isset($params['use_template_vars']) && empty($params['use_template_vars']) && isset($params['value']);

		if ( ! empty($label) ) {
			$id = empty($params['attr']['id']) ? '' : $params['attr']['id'];
			$tooltip = empty($params['tooltip']) ? '' : $params['tooltip'];
			$html .= '  '.form_label_tt($label, $id, $tooltip)."\n";
		}

        $html .= $is_inline ? '  <fieldset data-role="controlgroup" data-type="horizontal">'."\n" : '';

        $count = 0;
		foreach ($values as $val => $name) {
			$checked = $has_value && $params['value'] == $val ? 'checked="checked"' : '';
            $html .= '  <label>'."\n";
			$html .= '    <input '.form_attr_str($params, $val, $count++).$checked.'/> '.$name."\n";
            $html .= '  </label>'."\n";
		}

        $html .= $is_inline ? '  </fieldset>'."\n" : '';

		return form_field_wrap($html, $attr);
	}
}


/**
 * form_select
 *
 * Creates a select dropdown field and enclosing div container.
 * 
 * @param array $params The radio button field parameters:<br/><br/>
 * <ul>
 * <li>is_multiple => True if multiselect menu (optional)</li>
 * <li>label => Label tag text for radio button group (optional)</li>
 * <li>value => String or array of values to select as selected. NOTE: use_template_vars parameter
 * must be set to false for these values to be selected</li>
 * <li>placeholder => Placeholder text forJQuery Mobile selectmenu (optional)</li>
 * <li>tooltip => HTML/text for help information for this field (optional)</li>
 * <li>values => Array of value => label corresponding to each checkbox (required)</li>
 * <li>Also, the parameters in function form_attr_str() also apply to $params var in this function</li>
 * </ul>
 * @param array $attr Assoc array of attributes for enclosing div container
 * @return string The select dropdown field and enclosing div container
 */
if ( ! function_exists('form_select'))
{
	function form_select($params, $attr=array()) {
		if ( empty($params) || empty($params['values']) ) {
			return '';
		}
		
		$html = '';
		$params['type'] = 'select';
		$field_name = $params['name'];
		$values = $params['values'];
        $option_attr = empty($params['option_attr']) ? array() : $params['option_attr'];
		$label = empty($params['label']) ? '' : $params['label'];
		$is_multiple= ! empty($params['is_multiple']);
		$use_template_vars = ! isset($params['use_template_vars']) || ! empty($params['use_template_vars']);
		
		if ( ! empty($label) ) {
			$id = empty($params['attr']['id']) ? '' : $params['attr']['id'];
			$tooltip = empty($params['tooltip']) ? '' : $params['tooltip'];
			$html .= '  '.form_label_tt($label, $id, $tooltip)."\n";
		}

		$html .= '  <select '.form_attr_str($params).'>'."\n";
		if ($is_multiple) {
			$label = empty($params['placeholder']) ? 'Select '.(empty($label) ? '' : $label) : $params['placeholder'];
			$html .= '    <option data-placeholder="true">'.$label."</option>\n";
		}

		foreach ($values as $val => $name) {
            $val = str_replace('"', '&quot;', $val); //escape double quotes for value attr
			$html .= '    <option value="'.$val.'"';

            if ( isset($option_attr[$val]) && is_array($option_attr[$val]) ) {
                $attr = $option_attr[$val];
                foreach ($attr as $attr_name => $v) {
                    if (is_array($v)) {
                        $v = implode(' ', $v);
                    }

                    $is_json =  is_json_obj($v);
                    if ( ! $is_json) {
                        $v = str_replace('"', '&quot;', $v); //escape double quotes for attr
                    }
                    $html .= ' '.$attr_name.($is_json ? "='".$v."' " : '="'.$v.'" ');
                }
            }

			if ( ! empty($field_name) && $use_template_vars ) {
				$op = $is_multiple ? $field_name.'.lastIndexOf("'.$val.'")!==-1' : $field_name."==='".$val."'";
				$html .= '<% if ('.$op.') { %> selected="selected"<% } %>';
			} else if ( isset($params['value']) && ! $use_template_vars ) {
				if ( ( is_array($params['value']) && in_array($val, $params['value']) ) || $params['value'] == $val) {
					$html .= ' selected="selected"';
				}
			}
			$html .= '>'.$name."</option>\n";
		}
		
		$html .= "  </select>\n";
		return form_field_wrap($html, $attr);
	}
}


/**
 * form_textarea
 *
 * Creates a form textarea and enclosing div container.
 * 
 * @param array $params The radio button field parameters:<br/><br/>
 * <ul>
 * <li>label => Label tag text for radio button group (optional)</li>
 * <li>tooltip => HTML/text for help information for this field (optional)</li>
 * <li>Also, the parameters in function form_attr_str() also apply to $params var in this function</li>
 * </ul>
 * @param array $attr Assoc array of attributes for enclosing div container
 * @return string The textarea field and enclosing div container
 */
if ( ! function_exists('form_textarea'))
{
	function form_textarea($params, $attr=array()) {
		$params['type'] = 'textarea';
		$html = '';
		if ( ! empty($params['label']) ) {
			$id = empty($params['attr']['id']) ? '' : $params['attr']['id'];
			$tooltip = empty($params['tooltip']) ? '' : $params['tooltip'];
			$html .= '  '.form_label_tt($params['label'], $id, $tooltip)."\n";
		}
		
		$html .= '  <textarea '.form_attr_str($params).'>';
		if ( ! empty($params['name']) && ( ! isset($params['use_template_vars']) || ! empty($params['use_template_vars']) ) ) {
			$html .= '<%= '.$params['name'].' %>';
		}
		$html .= '</textarea>'."\n";
		return form_field_wrap($html, $attr);
	}
}


/**
 * is_json_obj
 *
 * Checks if a string is a valid JSON array or object.
 * 
 * @param string $var The JSON string
 * @return bool True if string is valid JSON array or object
 */
if ( ! function_exists('is_json_obj'))
{
	function is_json_obj($var) {
		if ( empty($var) || is_array($var) || is_object($var) ) {
			return false;
		}
		
		$result = json_decode($var, true);
		return is_array($result);
	}
}


/* End of file form_jqm.php */
/* Location: ./App/Functions/form_jqm.php */