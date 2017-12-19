<?php

$config['field_types'] = array(
	'button' => array(
		'after'			=> 'submit|cancel|delete',
		'lang'			=> 'field.button'
	),
	'checkbox' => array(
        'config_file'	=> '',
        'config_key'	=> '',
        'lang_config'	=> '',
        'module'		=> '',
        'values'		=> array(),
        'is_multiple'	=> true,
		'is_inline'		=> false,
        'sort'          => true,
		'lang'			=> 'field.checkbox'
	),
	'hidden' => array(
        'is_json'		=> false,
		'lang'			=> 'field.hidden'
	),
	'password' => array(
		'lang'			=> 'field.password'
	),
	'radio' => array(
        'config_file'	=> '',
        'config_key'	=> '',
        'dir'			=> '',
        'filename_only' => true,
        'show_ext'      => true,
        'lang_config'	=> '',
        'module'		=> '',
        'values'		=> array(),
		'is_inline'		=> false,
        'sort'          => true,
		'lang'			=> 'field.radio'
	),
	'select' => array(
		'config_file'	=> '',
        'config_key'	=> '',
		'dir'			=> '',
        'filename_only' => false,
        'show_ext'      => true,
        'lang_config'	=> '',
        'module'		=> '',
        'values'		=> array(),
        'is_multiple'	=> false,
        'sort'          => true,
		'lang'			=> 'field.select'
	),
	'multiselect' => array(
        'config_file'	=> '',
        'config_key'	=> '',
        'dir'			=> '',
        'filename_only' => false,
        'show_ext'      => true,
        'lang_config'	=> '',
        'module'		=> '',
        'values'		=> array(),
        'is_multiple'	=> true,
        'sort'          => true,
		'lang'			=> 'field.multiselect'
	),
	'text' => array(
		'placeholder' 	=> '',	
		'lang'			=> 'field.text'
	),
	'textarea' => array(
		'placeholder' 	=> '',
		'lang'			=> 'field.textarea'
	),
	'boolean' => array(
		'lang'			=> 'field.boolean'
	),
    'code' => array(
        'lang'			=> 'field.code'
    ),
	'countries' => array(
		'lang'			=> 'field.countries'
	),
	'custom' => array(
		'is_ajax' 		=> false,
		'html' 			=> '',
        'template' 		=> '',
		'lang'			=> 'field.custom'
	),
	'date' => array(
		'format' 		=> '',	
		'placeholder' 	=> '',	
		'lang'			=> 'field.date'
	),
	'editor' => array(
		'config_mce'	=> 'default',
		'lang'			=> 'field.editor'
	),
	'file' => array(
		'config_name'	=> 'sample',
		'lang'			=> 'field.file'
	),
	'image' => array(
		'config_name'	=> 'sample',
		'lang'			=> 'field.image'
	),
	'info' => array(
		'content' 		=> '',
		'lang'			=> 'field.info'
	),
	'jqm_flipswitch' => array(
		'lang'			=> 'field.boolean',
		'data-off-text'	=> '',
		'data-on-text'	=> '',
		'lang'			=> 'field.jqm_flipswitch'
	),
	'name_value_widget' => array(
		'name_label' 	=> 'Name',
		'value_label' 	=> 'Value',
		'max_items' 	=> '',
		'sort'		    => true,
		'value_required'=> true,
		'open_on_init'	=> true,
		'lang'			=> 'field.name_values_widget'
	),
    'object' => array(
        'lang'			=> 'field.object'
    ),
	'regions' => array(
		'lang'			=> 'field.regions'
	),
	'relation' => array(
		'module'	    => '',
		'type'		    => '',
        'is_ajax' 		=> false,
        'html' 			=> '',
        'template' 		=> '',
		'lang'		=> 'field.relation'
	),
	'time' => array(
        'is_24hr'		=> false,
		'placeholder' 	=> '',
		'lang'			=> 'field.time'
	),
	'values_widget' => array(
		'value_label' 	=> 'Value',
		'max_items' 	=> '',
		'sort'		    => true,
		'value_required'=> true,
		'open_on_init'	=> true,
		'lang'			=> 'field.values_widget'
	)
);

/* End of file fields_type.php */
/* Location: ./App/Config/fields_type.php */