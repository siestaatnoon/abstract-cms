<?php

$config['pages_fields'] = array(
	'parent_id' 		=> array(
		'name' 		=> 'parent_id',
		'label' 	=> '',
		'lang' 		=> 'form.pages.parent_id',
		'data_type' => array(
			'type' 	 => 'int',
			'length' => 10
		),
		'field_type' 	=> array(
			'custom' => array(
				'is_ajax' => true
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'pages',
		'module_pk'		=> 'page_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 2
	),
	'short_title' 		=> array(
		'name' 		=> 'short_title',
		'label' 	=> '',
		'lang' 		=> 'form.pages.short_title',
		'data_type' => array(
			'type' 	 => 'varchar',
			'length' => 128
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 128
				)
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'pages',
		'module_pk'		=> 'page_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 3
	),
	'url' 			=> array(
		'name' 		=> 'url',
		'label' 	=> '',
		'lang' 		=> 'form.pages.url',
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
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'pages',
		'module_pk'		=> 'page_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 4
	),
	'content' 	=> array(
		'name' 		=> 'content',
		'label' 	=> '',
		'lang' 		=> 'form.pages.content',
		'data_type' => array(
			'type' 	 => 'mediumtext'
		),
		'field_type' 	=> array(
			'editor' => array(
				'config_mce' => 'default'
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'pages',
		'module_pk'		=> 'page_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 5
	),
	'long_title' 		=> array(
		'name' 		=> 'long_title',
		'label' 	=> '',
		'lang' 		=> 'form.pages.long_title',
		'data_type' => array(
			'type' 	 => 'varchar',
			'length' => 128
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 128
				)
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> ''
			)
		),
		'default'		=> '',
		'module'		=> 'pages',
		'module_pk'		=> 'page_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 6
	),
	'meta_keywords' 	=> array(
		'name' 		=> 'meta_keywords',
		'label' 	=> '',
		'lang' 		=> 'form.pages.meta_keywords',
		'data_type' => array(
			'type' 	 => 'text'
		),
		'field_type' 	=> array(
			'textarea' => array(
				'attr' => array(
					'class' => 'short'
				)
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'pages',
		'module_pk'		=> 'page_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 7
	),
	'meta_description' 	=> array(
		'name' 		=> 'meta_description',
		'label' 	=> '',
		'lang' 		=> 'form.pages.meta_description',
		'data_type' => array(
			'type' 	 => 'text'
		),
		'field_type' 	=> array(
			'textarea' => array(
				'attr' => array(
					'class' => 'short'
				)
			)
		),
		'validation' 	=> array(),
		'default'		=> '',
		'module'		=> 'pages',
		'module_pk'		=> 'page_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 8
	),
    'module_id_list' 	=> array(
        'name' 		=> 'module_id_list',
        'label' 	=> '',
        'lang' 		=> 'form.pages.module_id_list',
        'data_type' => array(
            'type' 	 => 'int',
            'length' => 10
        ),
        'field_type' 	=> array(
            'custom' => array(
                'is_ajax' => true
            )
        ),
        'validation' 	=> array(),
        'default'		=> 0,
        'module'		=> 'pages',
        'module_pk'		=> 'page_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> false,
        'is_filter'		=> false,
        'is_model'		=> true,
        'sort_order'	=> 9
    ),
    'module_id_form' 	=> array(
        'name' 		=> 'module_id_form',
        'label' 	=> '',
        'lang' 		=> 'form.pages.module_id_form',
        'data_type' => array(
            'type' 	 => 'int',
            'length' => 10
        ),
        'field_type' 	=> array(
            'custom' => array(
                'is_ajax' => true
            )
        ),
        'validation' 	=> array(),
        'default'		=> 0,
        'module'		=> 'pages',
        'module_pk'		=> 'page_id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> true,
        'is_filter'		=> false,
        'is_model'		=> true,
        'sort_order'	=> 10
    ),
	'is_permanent' 	=> array(
		'name' 		=> 'is_permanent',
		'label' 	=> 'Is permanent?',
		'lang' 		=> 'form.pages.is_permanent',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
			'hidden' => array()
		),
		'validation' 	=> array(),
		'default'		=> 0,
		'module'		=> 'pages',
		'module_pk'		=> 'page_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 11
	)
);

$_data_type = array();
$_field_type = array();
$_defaults = array();
$_filters = array();
$_model = array();
$_uploads = array();
$_validation = array();

foreach ($config['pages_fields'] as $_field => $_params) {
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

$config['pages'] = array(
	'pk_field' 		=> 'page_id',
	'title_field' 	=> 'short_title',
	'slug_field' 	=> 'url',
	'name' 			=> 'pages',
	'label' 		=> 'Page',
	'label_plural' 	=> 'Pages',
	'css_includes'	=> array(
	
	),
	'js_includes'	=> array(
	
	),
	'js_load_block'	=> "
$('#form-pages').on('change', '.module-select', function() {
    $('.module-select').not( $(this) ).each(function(i, el) {
        $(this).val('0');
        Utils.refreshJqmField( $(this) );
    });
});
	",
	'js_unload_block' => "
$('#form-pages').off('change', '.module-select');
	",
	'form_fields' 	=> $config['pages_fields'],
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
	'slug' 			=> 'pages',
	'use_model' 	=> 1,
	'use_add' 		=> 1,
	'use_edit' 		=> 1,
	'use_delete' 	=> 1,
	'use_active' 	=> 1,
	'use_sort' 		=> 1,
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

/* End of file pages.php */
/* Location: ./App/Config/pages.php */