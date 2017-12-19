<?php

$config['form_fields_fields'] = array(
    'name' 		=> array(
        'name' 		=> 'name',
        'label' 	=> '',
        'lang' 		=> 'form.name',
        'data_type' => array(
            'type'   => 'varchar',
            'length' => 32
        ),
        'field_type' 	=> array(
            'text' => array(
                'attr' => array(
                    'class' => array(
                        'field-alphanum'
                    ),
                    'maxlength' => 32
                )
            )
        ),
        'validation' 	=> array(
            'required' => array(
                'message' 	=> '',
                'param'		=> ''
            )
        ),
        'default'		=> '',
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> true,
        'is_filter'		=> true,
        'is_model'		=> true,
        'sort_order'	=> 2
    ),
	'label' 		=> array(
        'name' 		=> 'label',
		'label' 	=> '',
		'lang' 		=> 'form.label',
		'data_type' => array(
			'type'   => 'varchar',
			'length' => 64
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 64
				)
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> '',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 3
	),
	'lang' 		=> array(
		'name' 		=> 'lang',
		'label' 	=> '',
		'lang' 		=> 'form.lang',
		'data_type' => array(
			'type'   => 'varchar',
			'length' => 64
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 64
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-info-hide'
                    )
                )
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 4
	),
	'field_type_type' 		=> array(
		'name' 		=> 'field_type_type',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.type',
		'data_type' => '',
		'field_type' 	=> array(
			'select' => array(
                'config_file' => 'field_types',
                'config_key'  => 'field_types',
                'sort'        => true
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> '',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 5
	),
    'field_type_value_select' => array(
        'name' 		=> 'field_type_value_select',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.value_select',
        'data_type' => '',
        'field_type' 	=> array(
            'radio' => array(
                'values' => array(
                    'values' => 'Enter Values',
                    'module' => 'Module Values',
                    'config' => 'Config File Values',
                    'dir'    => 'Directory Files'
                ),
                'is_inline'	 => true,
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-type-selector'
                    )
                )
            )
        ),
        'validation' 	=> array(
            'required' => array(
                'message' 	=> '',
                'param'		=> ''
            )
        ),
        'default'		=> '',
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 6
    ),
	'field_type_content' => array(
		'name' 		=> 'field_type_content',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.content',
		'data_type' => '',
		'field_type' 	=> array(
			'editor' => array(
				'config_mce' => 'default',
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-info'
                    )
                )
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> 'tooltip.field_type.content',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 7
	),
	'field_type_placeholder' => array(
		'name' 		=> 'field_type_placeholder',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.placeholder',
		'data_type' => '',
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 32
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-text',
                        'ct-multiselect'
                    )
                )
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 8
	),
	'field_type_name_label' => array(
		'name' 		=> 'field_type_name_label',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.name_label',
		'data_type' => '',
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 64
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-widget',
                        'ct-widget-nv'
                    )
                )
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 9
	),
	'field_type_value_label' => array(
		'name' 		=> 'field_type_value_label',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.value_label',
		'data_type' => '',
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 64
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-widget'
                    )
                )
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 10
	),
	'field_type_max_items' => array(
		'name' 		=> 'field_type_max_items',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.max_items',
		'data_type' => '',
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 4
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-widget'
                    )
                )
			)
		),
		'validation' 	=> array(
			'natural_not_zero' => array(
				'message' 	=> 'Enter a number greater than zero or leave blank',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 11
	),
	'field_type_value_required' => array(
		'name' 		=> 'field_type_value_required',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.value_required',
		'data_type' => '',
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-widget'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 12
	),
	'field_type_open_on_init' => array(
		'name' 		=> 'field_type_open_on_init',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.open_on_init',
		'data_type' => '',
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-widget'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 13
	),
    'field_type_values' => array(
        'name' 		=> 'field_type_values',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.values',
        'data_type' => '',
        'field_type' 	=> array(
            'name_value_widget' => array(
                'name_label' 	=> 'Display Name',
                'value_label' 	=> 'Form Value',
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-type-values',
                        'ct-values'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> array(),
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '<p>NOTE: Values must be unique</p>',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 14
    ),
	'field_type_config_file' => array(
		'name' 		=> 'field_type_config_file',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.config.file',
		'data_type' => '',
		'field_type' 	=> array(
			'select' => array(
				'dir'           => '/api/App/Config',
                'filename_only' => true,
                'show_ext'      => false,
                'ct_attr'       => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-type-config',
                        'ct-values'
                    )
                )
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> '',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 15
	),
	'field_type_config_key' => array(
		'name' 		=> 'field_type_config_key',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.config.key',
		'data_type' => '',
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 64
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-type-config',
                        'ct-values'
                    )
                )
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> '',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 16
	),
	'field_type_dir' => array(
		'name' 		=> 'field_type_dir',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.dir',
		'data_type' => '',
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 96
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-type-dir',
                        'ct-values'
                    )
                )
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> '',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> 'tooltip.field_type.dir',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 17
	),
    'field_type_filename_only' => array(
        'name' 		=> 'field_type_filename_only',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.filename_only',
        'data_type' => '',
        'field_type' 	=> array(
            'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-type-dir',
                        'ct-values'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> '',
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 18
    ),
    'field_type_show_ext' => array(
        'name' 		=> 'field_type_show_ext',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.show_ext',
        'data_type' => '',
        'field_type' 	=> array(
            'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-type-dir',
                        'ct-values'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> '1',
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 19
    ),
	'field_type_module' => array(
		'name' 		=> 'field_type_module',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.module',
		'data_type' => '',
		'field_type' 	=> array(
			'custom' => array(
				'is_ajax' => true,
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-type-module',
                        'ct-values'
                    )
                )
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> 'tooltip.field_type.module',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 20
	),

    'field_type_is_inline' => array(
        'name' 		=> 'field_type_is_inline',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.is_inline',
        'data_type' => '',
        'field_type' 	=> array(
            'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-checkbox',
                        'ct-radio'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> '',
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 21
    ),
    'field_type_is_multiple' => array(
        'name' 		=> 'field_type_is_multiple',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.is_multiple',
        'data_type' => '',
        'field_type' 	=> array(
            'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-checkbox'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> '',
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 22
    ),
	'field_type_format' => array(
		'name' 		=> 'field_type_format',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.format',
		'data_type' => '',
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 24
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-date'
                    )
                )
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> 'tooltip.field_type.format',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 23
	),
	'field_type_is_24hr' => array(
		'name' 		=> 'field_type_is_24hr',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.is_24hr',
		'data_type' => '',
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-time'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> 'tooltip.field_type.is_24hr',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 24
	),
	'field_type_upload_config' => array(
		'name' 		=> 'field_type_upload_config',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.upload',
		'data_type' => '',
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 64
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-upload'
                    )
                )
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> '',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 25
	),
	'field_type_relation_name' => array(
		'name' 		=> 'field_type_relation_name',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.relation.name',
		'data_type' => '',
		'field_type' 	=> array(
            'custom' => array(
                'is_ajax' => true,
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-relation'
                    )
                )
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> '',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 26
	),
	'field_type_relation_type' => array(
		'name' 		=> 'field_type_relation_type',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.relation.type',
		'data_type' => '',
		'field_type' 	=> array(
			'select' => array(
				'values' => array(
					'n:1' 	=> 'n -> 1',
					'n:n' 	=> 'n -> n',
					'1:n'	=> '1 -> n'
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-relation'
                    )
                )
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> '',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 27
	),
    'field_type_sort' => array(
        'name' 		=> 'field_type_sort',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.sort',
        'data_type' => '',
        'field_type' 	=> array(
            'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-checkbox',
                        'ct-radio',
                        'ct-select',
                        'ct-widget'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> '',
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 28
    ),
    'field_type_config_mce' => array(
        'name' 		=> 'field_type_config_mce',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.config_mce',
        'data_type' => '',
        'field_type' => array(
            'select' => array(
                'values' => array(
                    'default' 	=> 'Default',
                    'basic'		=> 'Basic'
                ),
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-editor'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> '',
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> 'tooltip.field_type.config_mce',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 29
    ),
    'field_type_is_json' => array(
        'name' 		=> 'field_type_is_json',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.is_json',
        'data_type' => '',
        'field_type' 	=> array(
            'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-hidden'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> '',
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 30
    ),
    'field_type_is_custom' => array(
        'name' 		=> 'field_type_is_custom',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.is_custom',
        'data_type' => '',
        'field_type' 	=> array(
            'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-relation'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> '',
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 31
    ),
	'field_type_is_ajax' => array(
		'name' 		=> 'field_type_is_ajax',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.is_ajax',
		'data_type' => '',
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-custom'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 32
	),
    'field_type_template' => array(
        'name' 		=> 'field_type_template',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.template',
        'data_type' => '',
        'field_type' 	=> array(
            'select' => array(
                'dir' => 'api/App/View',
                'show_ext' => false,
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-custom',
                        'ct-custom-template'
                    )
                )
            )
        ),
        'validation' 	=> array(
            'required' => true
        ),
        'default'		=> '',
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 33
    ),
	'field_type_html' => array(
		'name' 		=> 'field_type_html',
		'label' 	=> '',
		'lang' 		=> 'form.field_type.html',
		'data_type' => '',
		'field_type' 	=> array(
            'code' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-custom',
                        'ct-custom-html'
                    )
                )
            )
		),
		'validation' 	=> array(
            'required' => true
        ),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 34
	),
    'field_type_attributes' => array(
        'name' 		=> 'field_type_attributes',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.attributes',
        'data_type' => 'text',
        'field_type' 	=> array(
            'name_value_widget' => array(
                'name_label' 	=> 'Attribute value',
                'value_label' 	=> 'Attribute name (e.g. class, alt, style)',
                'ct_attr' => array(
                    'class' => array(
                        'ct-info-hide'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> array(),
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '<p>NOTE: Values must be unique</p>',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 35
    ),
    'field_type_ct_attr' => array(
        'name' 		=> 'field_type_ct_attr',
        'label' 	=> '',
        'lang' 		=> 'form.field_type.ct_attr',
        'data_type' => 'text',
        'field_type' 	=> array(
            'name_value_widget' => array(
                'name_label' 	=> 'Attribute value',
                'value_label' 	=> 'Attribute name (e.g. class, alt, style)',
                'ct_attr' => array(
                    'class' => array(
                        'ct-type-fields',
                        'ct-ct-attr'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> array(),
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '<p>NOTE: Values must be unique</p>',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 36
    ),
	'data_type_type' 		=> array(
		'name' 		=> 'data_type_type',
		'label' 	=> '',
		'lang' 		=> 'form.data_type.type',
		'data_type' => '',
		'field_type' 	=> array(
			'select' => array(
                'config_file' => 'model',
                'config_key'  => 'data_types',
                'ct_attr' => array(
                    'class' => array(
                        'ct-data-type',
                        'ct-info-hide'
                    )
                )
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> '',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 37
	),
	'data_type_length' => array(
		'name' 		=> 'data_type_length',
		'label' 	=> '',
		'lang' 		=> 'form.data_type.length',
		'data_type' => '',
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 3
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-data-fields',
                        'ct-data-length',
                        'ct-info-hide'
                    )
                )
			)
		),
		'validation' 	=> array(
			'natural_not_zero' => array(
				'message' 	=> '',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 38
	),
	'data_type_values' => array(
		'name' 		=> 'data_type_values',
		'label' 	=> '',
		'lang' 		=> 'form.data_type.values',
		'data_type' => '',
		'field_type' 	=> array(
			'values_widget' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-data-fields',
                        'ct-data-enum',
                        'ct-info-hide'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> false,
		'sort_order'	=> 39
	),
    'default' 		=> array(
        'name' 		=> 'default',
        'label' 	=> '',
        'lang' 		=> 'form.default',
        'data_type' => array(
            'type' => 'varchar',
            'length' => 64
        ),
        'field_type' 	=> array(
            'text' => array(
                'attr' => array(
                    'maxlength' => 64
                ),
                'ct_attr' => array(
                    'class' => array(
                        'ct-field-default',
                        'ct-info-hide'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> NULL,
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> 'Use "[]" for array',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> true,
        'sort_order'	=> 40
    ),
    'validation' 	=> array(
        'name' 		=> 'validation',
        'label' 	=> '',
        'lang' 		=> 'form.validation',
        'data_type' => array(
            'type' 	 => 'text'
        ),
        'field_type' 	=> array(
            'custom' => array(
                'is_ajax'  => false,
                'template' => 'admin/form_fields_validation',
                'ct_attr' => array(
                    'class' => array(
                        'ct-info-hide'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> array(),
        'module'		=> 'form_fields',
        'module_pk'		=> 'field_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> true,
        'sort_order'	=> 41
    ),
	'tooltip' 		=> array(
		'name' 		=> 'tooltip',
		'label' 	=> '',
		'lang' 		=> 'form.tooltip',
		'data_type' => array(
			'type' => 'text'
		),
		'field_type' 	=> array(
			'editor' => array(
				'config_mce' => 'default',
                'ct_attr' => array(
                    'class' => array(
                        'ct-info-hide'
                    )
                )
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 42
	),
	'tooltip_lang' 		=> array(
		'name' 		=> 'tooltip_lang',
		'label' 	=> '',
		'lang' 		=> 'form.tooltip_lang',
		'data_type' => array(
			'type'  => 'varchar',
			'length'=> 64
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 64
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-info-hide'
                    )
                )
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 43
	),
	'module' 		=> array(
		'name' 		=> 'module',
		'label' 	=> 'Module Reference',
		'lang' 		=> '',
		'data_type' => array(
			'type' => 'varchar',
			'length' => 32
		),
		'field_type' 	=> array(
			'hidden' => array()
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 44
	),
	'module_pk' 		=> array(
		'name' 		=> 'module_pk',
		'label' 	=> 'Module PK Field',
		'lang' 		=> '',
		'data_type' => array(
			'type' => 'varchar',
			'length' => 32
		),
		'field_type' 	=> array(
			'hidden' => array()
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 45
	),
	'is_model' 		=> array(
		'name' 		=> 'is_model',
		'label' 	=> '',
		'lang' 		=> 'form.is_model',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
            'jqm_flipswitch' => array()
        ),
		'validation' 	=> array(),
		'default'		=> 1,
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 46
	),
	'is_list_col' 		=> array(
		'name' 		=> 'is_list_col',
		'label' 	=> '',
		'lang' 		=> 'form.is_list_col',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-info-hide'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> 0,
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 47
	),
	'is_filter' 	=> array(
		'name' 		=> 'is_filter',
		'label' 	=> '',
		'lang' 		=> 'form.is_filter',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
                'ct_attr' => array(
                    'class' => array(
                        'ct-info-hide'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> 0,
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 48
	),
	'field_type' 	=> array(
		'name' 		=> 'field_type',
		'label' 	=> 'Field Type (Data)',
		'lang' 		=> '',
		'data_type' => array(
			'type' 	 => 'text'
		),
		'field_type' 	=> array(
			'object' => array()
		),
		'validation' 	=> array(),
		'default'		=> array(),
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 49
	),
	'data_type' 	=> array(
		'name' 		=> 'data_type',
		'label' 	=> 'DB Data Type (Data)',
		'lang' 		=> '',
		'data_type' => array(
			'type' 	 => 'text'
		),
		'field_type' 	=> array(
			'object' => array()
		),
		'validation' 	=> array(),
		'default'		=> array(),
		'module'		=> 'form_fields',
		'module_pk'		=> 'field_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 50
	)
);

$_data_type = array();
$_field_type = array();
$_defaults = array();
$_filters = array();
$_model = array();
$_uploads = array();
$_validation = array();

foreach ($config['form_fields_fields'] as $_field => $_params) {
	$_defaults[$_field] = $_params['default'];
	$_field_type[$_field] = key($_params['field_type']);
	
	if ( ! empty($_params['field_type']['image']) ) {
		$_uploads[$_field] = array(
			'config_name' => $_params['config_name'],
			'is_image'	  => true
		);
	}
	
	if ( ! empty($_params['field_type']['file']) ) {
		$_uploads[$_field] = array(
			'config_name' => $_params['config_name'],
			'is_image'	  => false
		);
	}
	
	if ($_params['is_filter']) {
		$_filters[] = $_field;
	}
	
	if ($_params['is_model'] && empty($_params['field_type']['relation']) ) {
		$_model[$_field] = $_params['default'];
		$_data_type[$_field] = $_params['data_type'];
		$_data_type[$_field]['default'] = $_params['default'];
	}
	
	if ( ! empty($_params['validation']) ) {
		$_a = array();
		foreach ($_params['validation'] as $_r => $_v) {
			if ( empty($_v['param']) && empty($_v['message']) ) {
				$_a[$_r] = true;
			} else if ( empty($_v['message']) ) {
				$_a[$_r] = $_v['param'];
			} else {
				$_a[$_r]['param'] = empty($_v['param']) ? true : $_v['param'];
				$_a[$_r]['message'] = $_v['message'];
			}
		}
		if ( ! empty($_a) ) {
			$_validation[$_field] = array('valid' => $_a);
		}
	}

	unset($_r);
	unset($_v);
	unset($_a);
}
unset($_field);
unset($_params);

$config['form_fields'] = array(
	'pk_field' 		=> 'field_id',
	'title_field' 	=> 'label',
	'slug_field' 	=> '',
	'name' 			=> 'form_fields',
	'label' 		=> 'Form Field',
	'label_plural' 	=> 'Form Fields',
	'css_includes'	=> array(
	
	),
	'js_includes'	=> array(
	
	),
	'js_load_block'	=> "",
	'js_unload_block'=> "",
	'form_fields' 	=> $config['form_fields_fields'],
	'field_data'	=> array(
		'data_type' 	=> $_data_type,
		'field_type' 	=> $_field_type,
		'defaults' 		=> $_defaults,
		'filters' 		=> $_filters,
		'model' 		=> $_model,
		'uploads' 		=> $_uploads,
		'validation' 	=> $_validation,
		'relations'		=> array()
	),
	'slug' 			=> 'form_fields',
	'use_model' 	=> 1,
	'use_add' 		=> 1,
	'use_edit' 		=> 1,
	'use_delete' 	=> 1,
	'use_active' 	=> 0,
	'use_sort' 		=> 1,
	'use_archive' 	=> 0,
	'use_slug' 		=> 0,
    'use_cms_form' 	=> 0,
    'use_frontend_form' => 0,
    'use_frontend_list' => 0,
	'is_active' 	=> 1
);

unset($_data_type);
unset($_field_type);
unset($_defaults);
unset($_filters);
unset($_model);
unset($_uploads);
unset($_validation);


/* End of file form_fields.php */
/* Location: ./App/Config/form_fields.php */