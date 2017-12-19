<?php

$config['modules_fields'] = array(
    'name' 		=> array(				//NOTE: CANNOT EDIT AFTER INSERT
        'name' 		=> 'name',
        'label' 	=> '',
        'lang' 		=> 'modules.name',
        'data_type' => array(
            'type' 	 => 'varchar',
            'length' => 32
        ),
        'field_type' 	=> array(
            'text' => array(
                'attr' => array(
                    'class' => array(
                        'field-alphanum',
                        'field-modules',
                        'field-disable-edit'
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
        'module'		=> 'modules',
        'module_pk'		=> 'id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> true,
        'is_filter'		=> true,
        'is_model'		=> true,
        'sort_order'	=> 2
    ),
	'pk_field' 		=> array(				//NOTE: CANNOT EDIT AFTER INSERT
		'name' 		=> 'pk_field',
		'label' 	=> '',
		'lang' 		=> 'modules.pk_field',
		'data_type' => array(
			'type' 	 => 'varchar',
			'length' => 32
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
				    'class' => array(
				        'field-alphanum',
                        'field-disable-edit',
                    )
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-model'
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
		'default'		=> 'id',
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 3
	),
	'title_field' 		=> array(
		'name' 		=> 'title_field',
		'label' 	=> '',
		'lang' 		=> 'modules.title_field',
		'data_type' => array(
			'type' 	 => 'varchar',
			'length' => 32
		),
		'field_type' 	=> array(
            'custom' => array(
                'is_ajax' => true,
                'ct_attr' => array(
                    'class' => array(
                        'ct-form-fields',
                        'ct-model'
                    )
                )
            )
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> 'Please add Form Fields and make a selection',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 4
	),
    'use_slug' 	=> array(
        'name' 		=> 'use_slug',
        'label' 	=> '',
        'lang' 		=> 'modules.use_slug',
        'data_type' => array(
            'type' 	 => 'tinyint',
            'length' => 1
        ),
        'field_type' 	=> array(
            'jqm_flipswitch' => array()
        ),
        'validation' 	=> array(),
        'default'		=> 0,
        'module'		=> 'modules',
        'module_pk'		=> 'id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> true,
        'sort_order'	=> 5
    ),
	'slug_field' 		=> array(
		'name' 		=> 'slug_field',
		'label' 	=> '',
		'lang' 		=> 'modules.slug_field',
		'data_type' => array(
			'type' 	 => 'varchar',
			'length' => 32
		),
		'field_type' 	=> array(
            'custom' => array(
                'is_ajax' => true,
                'ct_attr' => array(
                    'class' => array(
                        'ct-form-fields',
                        'ct-slug'
                    )
                )
            )
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> 'Please add Form Fields and make a selection',
				'param'		=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 6
	),
	'label' 		=> array(
		'name' 		=> 'label',
		'label' 	=> '',
		'lang' 		=> 'modules.label',
		'data_type' => array(
			'type' 	 => 'varchar',
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
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 7
	),
	'label_plural' 		=> array(
		'name' 		=> 'label_plural',
		'label' 	=> '',
		'lang' 		=> 'modules.label_plural',
		'data_type' => array(
			'type' 	 => 'varchar',
			'length' => 64
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 64
				),
                'ct_attr' => array(
                    'class' => array(
                        'ct-model'
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
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 8
	),
	'css_includes' => array(
		'name' 		=> 'css_includes',
		'label' 	=> '',
		'lang' 		=> 'modules.css_includes',
		'data_type' => array(
			'type' => 'text'
		),
		'field_type' 	=> array(
			'values_widget' => array(
				'value_label' 	=> 'File path from /css directory',
                'value_required'=> true
			)
		),
		'validation' 	=> array(),
		'default'		=> array(),
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 9
	),
	'js_includes' => array(
		'name' 		=> 'js_includes',
		'label' 	=> '',
		'lang' 		=> 'modules.js_includes',
		'data_type' => array(
			'type' => 'text'
		),
		'field_type' 	=> array(
			'values_widget' => array(
				'value_label' 	=> 'File path from /js/plugins directory',
                'value_required'=> true,
			)
		),
		'validation' 	=> array(),
		'default'		=> array(),
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 10
	),
	'js_load_block' 	=> array(
		'name' 		=> 'js_load_block',
		'label' 	=> '',
		'lang' 		=> 'modules.js_load_block',
		'data_type' => array(
			'type' => 'text'
		),
		'field_type' 	=> array(
			'code' => array()
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 11
	),
	'js_unload_block' => array(
		'name' 		=> 'js_unload_block',
		'label' 	=> '',
		'lang' 		=> 'modules.js_unload_block',
		'data_type' => array(
			'type' => 'text'
		),
		'field_type' 	=> array(
            'code' => array()
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> 'tooltip.js_unload_block',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 12
	),
	'form_fields' 	=> array(
		'name' 		=> 'form_fields',
		'label' 	=> '',
		'lang' 		=> 'modules.form_fields',
		'data_type' => array(),
		'field_type' 	=> array(
			'relation' 	=> array(
				'module' 	=> 'form_fields',
				'type' 		=> '1:n'
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> 'At least one Form Field is required'
			)
		),
		'default'		=> array(),
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 13
	),
	'field_data' => array(
		'name' 		=> 'field_data',
		'label' 	=> 'Field Data',
		'lang' 		=> '',
		'data_type' => array(
			'type' => 'mediumtext'
		),
		'field_type' 	=> array(
			'object' => array()
		),
		'validation' 	=> array(),
		'default'		=> array(),
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 14
	),
	'use_model' 	=> array(		//NOTE: CANNOT EDIT AFTER INSERT
		'name' 		=> 'use_model',
		'label' 	=> '',
		'lang' 		=> 'modules.use_model',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
            'radio' => array(
                'values' => array(
                    '1' => 'Model',
                    '0' => 'Options'
                ),
                'is_inline'	 => true
            )
		),
		'validation' 	=> array(),
		'default'		=> 1,
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 15
	),
	'use_add' 		=> array(
		'name' 		=> 'use_add',
		'label' 	=> '',
		'lang' 		=> 'modules.use_add',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
			    'attr' => array(
			        'class' => array(
                        'field-model-options',
                        'ct-model-on'
                    )
                ),
			    'ct_attr' => array(
                    'class' => array(
                        'ct-model'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> 1,
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 16
	),
	'use_edit' 		=> array(
		'name' 		=> 'use_edit',
		'label' 	=> '',
		'lang' 		=> 'modules.use_edit',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
                'attr' => array(
                    'class' => array(
                        'field-model-options',
                        'ct-model-on'
                    )
                ),
                'ct_attr' => array(
                    'class' => array(
                        'ct-model'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> 1,
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 17
	),
	'use_delete' 	=> array(
		'name' 		=> 'use_delete',
		'label' 	=> '',
		'lang' 		=> 'modules.use_delete',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
                'attr' => array(
                    'class' => array(
                        'field-model-options',
                        'ct-model-on'
                    )
                ),
                'ct_attr' => array(
                    'class' => array(
                        'ct-model'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> 1,
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 18
	),
	'use_active' 	=> array(
		'name' 		=> 'use_active',
		'label' 	=> '',
		'lang' 		=> 'modules.use_active',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
                'attr' => array(
                    'class' => array(
                        'field-model-options',
                        'ct-model-on'
                    )
                ),
                'ct_attr' => array(
                    'class' => array(
                        'ct-model'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> 1,
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 19
	),
	'use_sort' 	=> array(
		'name' 		=> 'use_sort',
		'label' 	=> '',
		'lang' 		=> 'modules.use_sort',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
                'attr' => array(
                    'class' => 'field-model-options'
                ),
                'ct_attr' => array(
                    'class' => array(
                        'ct-model'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> 0,
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 20
	),
	'use_archive' 	=> array(
		'name' 		=> 'use_archive',
		'label' 	=> '',
		'lang' 		=> 'modules.use_archive',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
			'jqm_flipswitch' => array(
                'attr' => array(
                    'class' => 'field-model-options'
                ),
                'ct_attr' => array(
                    'class' => array(
                        'ct-model'
                    )
                )
            )
		),
		'validation' 	=> array(),
		'default'		=> 0,
		'module'		=> 'modules',
		'module_pk'		=> 'id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 21
	),
    'use_cms_form' 	=> array(
        'name' 		=> 'use_cms_form',
        'label' 	=> '',
        'lang' 		=> 'modules.use_cms_form',
        'data_type' => array(
            'type' 	 => 'tinyint',
            'length' => 1
        ),
        'field_type' 	=> array(
            'jqm_flipswitch' => array(
                'attr' => array(
                    'class' => 'field-model-options'
                ),
                'ct_attr' => array(
                    'class' => array(
                        'ct-model'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> 0,
        'module'		=> 'modules',
        'module_pk'		=> 'id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> true,
        'sort_order'	=> 22
    ),
    'use_frontend_form' 	=> array(
        'name' 		=> 'use_frontend_form',
        'label' 	=> '',
        'lang' 		=> 'modules.use_frontend_form',
        'data_type' => array(
            'type' 	 => 'tinyint',
            'length' => 1
        ),
        'field_type' 	=> array(
            'jqm_flipswitch' => array(
                'attr' => array(
                    'class' => 'field-model-options'
                ),
                'ct_attr' => array(
                    'class' => array(
                        'ct-model'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> 0,
        'module'		=> 'modules',
        'module_pk'		=> 'id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> true,
        'sort_order'	=> 23
    ),
    'use_frontend_list' 	=> array(
        'name' 		=> 'use_frontend_list',
        'label' 	=> '',
        'lang' 		=> 'modules.use_frontend_list',
        'data_type' => array(
            'type' 	 => 'tinyint',
            'length' => 1
        ),
        'field_type' 	=> array(
            'jqm_flipswitch' => array(
                'attr' => array(
                    'class' => 'field-model-options'
                ),
                'ct_attr' => array(
                    'class' => array(
                        'ct-model'
                    )
                )
            )
        ),
        'validation' 	=> array(),
        'default'		=> 0,
        'module'		=> 'modules',
        'module_pk'		=> 'id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> true,
        'sort_order'	=> 24
    ),
    'reserved_fields' 	=> array(
        'name' 		=> 'reserved_fields',
        'label' 	=> 'Reserved Fields',
        'lang' 		=> '',
        'data_type' => array(),
        'field_type' 	=> array(
            'hidden' => array(
                'is_json' => true
            )
        ),
        'validation' 	=> array(),
        'default'		=> '',
        'module'		=> 'modules',
        'module_pk'		=> 'id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> false,
        'sort_order'	=> 25
    )
);

$_data_type = array();
$_field_type = array();
$_defaults = array();
$_filters = array();
$_model = array();
$_uploads = array();
$_validation = array();

foreach ($config['modules_fields'] as $_field => $_params) {
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

$config['modules'] = array(
	'pk_field' 		=> 'id',
	'title_field' 	=> 'label_plural',
	'slug_field' 	=> 'name',
	'name' 			=> 'modules',
	'label' 		=> 'Module',
	'label_plural' 	=> 'Modules',
	'css_includes'	=> array(
        'plugins/abstract/jquery.mobile.admin.modules.css'
	),
	'js_includes'	=> array(
	    'abstract/jquery.mobile.admin.modules.js'
	),
	'js_load_block'	=> "",
	'js_unload_block' => "",
	'form_fields' 	=> $config['modules_fields'],
	'field_data'	=> array(
		'data_type' 	=> $_data_type,
		'field_type' 	=> $_field_type,
		'defaults' 		=> $_defaults,
		'filters' 		=> $_filters,
		'model' 		=> $_model,
		'uploads' 		=> $_uploads,
		'validation' 	=> $_validation,
		'relations'		=> array(
			'form_fields' => array(
			    'module'    => 'form_fields',
				'type'      => '1:n'
			)
		)
	),
	'slug' 			=> 'modules',
	'use_model' 	=> 1,
	'use_add' 		=> 1,
	'use_edit' 		=> 1,
	'use_delete' 	=> 1,
	'use_active' 	=> 1,
	'use_sort' 		=> 0,
	'use_archive' 	=> 0,
	'use_slug' 		=> 1,
    'use_cms_form' 	=> 1,
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

/* End of file modules.php */
/* Location: ./App/Config/modules.php */