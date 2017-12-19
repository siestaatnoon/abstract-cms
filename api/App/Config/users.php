<?php

$config['users_fields'] = array(
	'last_name' 		=> array(
		'name' 		=> 'last_name',
		'label' 	=> '',
		'lang' 		=> 'form.users.last_name',
		'data_type' => array(
			'type' 	 => 'varchar',
			'length' => 32
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
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
		'module'		=> 'users',
		'module_pk'		=> 'user_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 2
	),
	'first_name' 		=> array(
		'name' 		=> 'first_name',
		'label' 	=> '',
		'lang' 		=> 'form.users.first_name',
		'data_type' => array(
			'type' 	 => 'varchar',
			'length' => 32
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 32
				)
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> 'Please enter a first name'
			)
		),
		'default'		=> '',
		'module'		=> 'users',
		'module_pk'		=> 'user_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 3
	),
	'email' 		=> array(
		'name' 		=> 'email',
		'label' 	=> '',
		'lang' 		=> 'form.users.email',
		'data_type' => array(
			'type' 	 => 'varchar',
			'length' => 96
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 96
				)
			)
		),
		'validation' 	=> array(
            'required' => true,
			'email' => array(
				'message' 	=> 'Please enter a valid email'
			)
		),
		'default'		=> '',
		'module'		=> 'users',
		'module_pk'		=> 'user_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 4
	),
	'username' 		=> array(
		'name' 		=> 'username',
		'label' 	=> '',
		'lang' 		=> 'form.users.username',
		'data_type' => array(
			'type' 	 => 'varchar',
			'length' => 32
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 32
				)
			)
		),
		'validation' 	=> array(
			'required' => array(
				'message' 	=> 'Please enter a username'
			)
		),
		'default'		=> '',
		'module'		=> 'users',
		'module_pk'		=> 'user_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 5
	),
	'userpass' 		=> array(
		'name' 		=> 'userpass',
		'label' 	=> '',
		'lang' 		=> 'form.users.userpass',
		'data_type' => array(
			'type' 	 => 'varchar',
			'length' => 32
		),
		'field_type' 	=> array(
			'text' => array(
				'attr' => array(
					'maxlength' => 32
				)
			)
		),
		'validation' 	=> array(
            'required_on_add' => array(
                'rules' => "\treturn $('.abstract-form input[name=\"user_id\"]').val() !== '' ? true : $.trim(value) !== '';",
                'message' => 'Required field'
            ),
            'strong_password' => true
		),
		'default'		=> '',
		'module'		=> 'users',
		'module_pk'		=> 'user_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 6
	),
	'global_perm' 	=> array(
		'name' 		=> 'global_perm',
		'label' 	=> '',
		'lang' 		=> 'form.users.global_perm',
		'data_type' => array(
			'type' 	 => 'int',
			'length' => 4
		),
		'field_type' 	=> array(
			'custom' => array(
				'is_ajax' => true
			)
		),
		'validation' 	=> array(),
		'default'		=> 0,
		'module'		=> 'users',
		'module_pk'		=> 'user_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 7
	),
	'modules' 	=> array(
		'name' 		=> 'modules',
		'label' 	=> '',
		'lang' 		=> 'form.users.modules',
		'data_type' => array(
			'type' 	 => 'text'
		),
		'field_type' 	=> array(
			'relation' => array(
				'module' 		=> 'modules',
				'type' 			=> 'n:n',
				'is_custom' 	=> true,
				'is_ajax' 		=> true
			)
		),
		'validation' 	=> array(),
		'default'		=> array(),
		'module'		=> 'users',
		'module_pk'		=> 'user_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 8
	),
	'has_reset_pending' 	=> array(
		'name' 		=> 'has_reset_pending',
		'label' 	=> 'Has reset pending?',
		'lang' 		=> '',
		'data_type' => array(
			'type' 	 => 'tinyint',
			'length' => 1
		),
		'field_type' 	=> array(
			'hidden' => array()
		),
		'validation' 	=> array(),
		'default'		=> 0,
		'module'		=> 'users',
		'module_pk'		=> 'user_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 9
	)
);

$_data_type = array();
$_field_type = array();
$_defaults = array();
$_filters = array();
$_model = array();
$_uploads = array();
$_validation = array();

foreach ($config['users_fields'] as $_field => $_params) {
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

$config['users'] = array(
	'pk_field' 		=> 'user_id',
	'title_field' 	=> 'last_name',
	'slug_field' 	=> '',
	'name' 			=> 'users',
	'label' 		=> 'User',
	'label_plural' 	=> 'Users',
	'css_includes'	=> array(
        'plugins/abstract/jquery.mobile.admin.users.css'
	),
	'js_includes'	=> array(
        'abstract/jquery.mobile.admin.users.js'
	),
	'js_load_block'	=> "",
	'js_unload_block' => "",
	'form_fields' 	=> $config['users_fields'],
	'field_data'	=> array(
		'data_type' 	=> $_data_type,
		'field_type' 	=> $_field_type,
		'defaults' 		=> $_defaults,
		'filters' 		=> $_filters,
		'model' 		=> $_model,
		'uploads' 		=> $_uploads,
		'validation' 	=> $_validation,
		'relations'		=> array(
			'modules' => array(
                'module'    => 'modules',
				'type' 		=> 'n:n',
				'is_custom' => true,
				'is_ajax' 	=> true
			)
		)
	),
	'slug' 			=> 'users',
	'use_model' 	=> 1,
	'use_add' 		=> 1,
	'use_edit' 		=> 1,
	'use_delete' 	=> 1,
	'use_active' 	=> 1,
	'use_sort' 		=> 0,
	'use_archive' 	=> 0,
	'use_slug' 		=> 0,
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

/* End of file users.php */
/* Location: ./App/Config/users.php */