<?php
use App\Html\Form\Form;
use App\Html\Navigation\AdminMenu;
use App\Html\Template\Template;
use App\Module\Module;
use App\Model\Relation;
use App\User\Permission;
use Slim\Slim;

require 'Slim/Slim.php';
require 'App/App.php';

Slim::registerAutoloader();
$Slim = new Slim();
\App\App::register_autoload();
$app = \App\App::get_instance();

$gizmos_fields = array(
	array(
		'label' 	=> 'Post Date',
		'name' 		=> 'post_date',
		'lang' 		=> '',
		'data_type_type'	=> 'date',
		'data_type_length'	=> '',
		'data_type_values'	=> array(),
		'field_type_type'	=> 'date',
		'field_type_format'	=> 'm/d/Y',
		'validation'		=> array('required' => true),
		'default'		=> '',
		'module'		=> 'gizmos',
		'module_pk'		=> 'gizmo_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> 'tooltip.widget.post_date',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 1
	),
	array(
		'label' 	=> 'Post Time',
		'name' 		=> 'post_time',
		'lang' 		=> '',
		'data_type_type'	=> 'time',
		'data_type_length'	=> '',
		'data_type_values'	=> array(),
		'field_type_type'	=> 'time',
		'field_type_is_24hr'=> 0,
        'validation'		=> array('required' => true),
		'default'		=> '',
		'module'		=> 'gizmos',
		'module_pk'		=> 'gizmo_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 2
	),
	array(
		'label' 	=> 'Title',
		'name' 		=> 'title',
		'lang' 		=> '',
		'data_type_type'	=> 'varchar',
		'data_type_length'	=> 64,
		'data_type_values'	=> array(),
		'field_type_type'		=> 'text',
		'field_type_placeholder'=> '',
		'field_type_attributes' => array('max_length' => 64),
        'validation'		=> array(
            'required' => true,
            'is_good_param' => array(
                'param' => '["one","two","three"]',
                'rules' => "\talert(param[1]);\n\treturn true;",
                'message' => 'This should never return an error'
            )
        ),
		'default'		=> '',
		'module'		=> 'gizmos',
		'module_pk'		=> 'gizmo_id',
		'tooltip'		=> "
		
<h2>Help for Title</h2>
<p>
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam sed erat at nisi mattis hendrerit at a tortor. Vivamus a dictum nisl, nec eleifend mauris.
</p>
<ul>
  <li>Step 1</li>
  <li>Step 2
    <ul>
      <li>Step 2.1</li>
      <li>Step 2.2</li>
      <li>Step 2.3</li>
    </ul>
  </li>
  <li>Step 3</li>
</ul>
		
		",
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 3
	),
	array(
		'label' 	=> 'Day',
		'name' 		=> 'day',
		'lang' 		=> '',
		'data_type_type'	=> 'varchar',
		'data_type_length'	=> 16,
		'field_type_type'	=> 'select',
		'field_type_values'	=> array(
			'sunday' 	=> 'Sunday',
			'monday' 	=> 'Monday',
			'tuesday' 	=> 'Tuesday',
			'wednesday' => 'Wednesday',
			'thursday' 	=> 'Thursday',
			'friday' 	=> 'Friday',
			'saturday' 	=> 'Saturday'
		),
        'validation'	=> array('required' => true),
		'default'		=> '',
		'module'		=> 'gizmos',
		'module_pk'		=> 'gizmo_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 4
	),
	array(
		'label' 	=> 'Dessert',
		'name' 		=> 'dessert',
		'lang' 		=> '',
		'data_type_type'	=> 'text',
		'field_type_type'	=> 'multiselect',
		'field_type_values'	=> array(
			'ice_cream' => 'Ice Cream',
			'cake' 		=> 'Cake',
			'pie' 		=> 'Pie',
			'fruit' 	=> 'Fruit'
		),
		'field_type_placeholder'=> 'Select a tasty item',
        'validation'	=> array('min' => array('param' => 2) ),
		'default'		=> array(),
		'module'		=> 'gizmos',
		'module_pk'		=> 'gizmo_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 5
	),
	array(
		'label' 	=> 'Content',
		'name' 		=> 'content',
		'lang' 		=> '',
		'data_type_type'	=> 'text',
		'field_type_type'		=> 'editor',
		'field_type_config_mce' => 'default',
        'validation'	=> array('required' => true),
		'default'		=> '',
		'module'		=> 'gizmos',
		'module_pk'		=> 'gizmo_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> true,
		'is_filter'		=> true,
		'is_model'		=> true,
		'sort_order'	=> 6
	),
	array(
		'label' 	=> 'Select Some',
		'name' 		=> 'choose',
		'lang' 		=> '',
		'data_type_type'	=> 'text',
		'field_type_type'		=> 'checkbox',
		'field_type_values'	=> array(
			'monday' 	=> 'Monday',
			'tuesday' 	=> 'Tuesday',
			'wednesday' => 'Wednesday',
			'thursday' 	=> 'Thursday'
		),
        'validation'	=> array(
            'min' => array(
                'param' => 2
            ),
            'max' => array(
                'param' => 3
            )
        ),
		'default'		=> array(),
		'module'		=> 'gizmos',
		'module_pk'		=> 'gizmo_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 7
	),
	array(
		'label' 	=> 'Select One',
		'name' 		=> 'color',
		'lang' 		=> '',
		'data_type_type'	=> 'varchar',
		'data_type_length'	=> 16,
		'field_type_type'	=> 'radio',
		'field_type_values'	=> array(
			'black' 	=> 'Black',
			'blue' 		=> 'Blue',
			'red' 		=> 'Red',
			'green' 	=> 'Green'
		),
        'validation'	=> array('required' => true),
		'default'		=> '',
		'module'		=> 'gizmos',
		'module_pk'		=> 'gizmo_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 8
	),
	array(
		'label' 	=> 'Attributes',
		'name' 		=> 'attributes',
		'lang' 		=> '',
		'data_type_type'		=> 'text',
		'field_type_type'		=> 'name_value_widget',	//
		'field_type_name_label'		=> 'Name',			// NEED TO ADD FIELDS
		'field_type_value_label'	=> 'Value',			// TO form.php
		'field_type_max_items'		=> 10,				// AND
		'field_type_value_required'	=> 1,				// Module_form_fields.php
		'field_type_open_on_init'	=> 1,				//
		'field_type_sort'			=> 1,				//
        'validation'	=> array('required' => true),
		'default'		=> array(),
		'module'		=> 'gizmos',
		'module_pk'		=> 'gizmo_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 9
	),
	array(
		'label' 	=> 'Class Attributes',
		'name' 		=> 'klass',
		'lang' 		=> '',
		'data_type_type'			=> 'text',
		'field_type_type'			=> 'values_widget',
		'field_type_value_label'	=> 'Value',
		'field_type_max_items'		=> 10,
		'field_type_value_required'	=> 1,
		'field_type_open_on_init'	=> 1,
		'field_type_sort'		    => 1,
        'validation'	=> array('required' => true),
		'default'		=> array(),
		'module'		=> 'gizmos',
		'module_pk'		=> 'gizmo_id',
		'tooltip'		=> '',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 10
	),
	array(	//image
		'label' 	=> 'Image Upload',
		'name' 		=> 'images',
		'lang' 		=> '',
		'data_type_type'	=> 'text',
		'field_type_type'			=> 'image',
		'field_type_upload_config' 	=> 'test',
        'validation'	=> array('required' => true),
		'default'		=> array(),
		'module'		=> 'gizmos',
		'module_pk'		=> 'gizmo_id',
		'tooltip'		=> '<p>Some images on your computer/phone that you upload.</p>',
		'tooltip_lang'	=> '',
		'is_list_col'	=> false,
		'is_filter'		=> false,
		'is_model'		=> true,
		'sort_order'	=> 11
	)
);

$widgets_fields = $gizmos_fields;
foreach ($widgets_fields as &$wf) {
	$wf['module'] = 'widgets';
	$wf['module_pk'] = 'widget_id';
}

$gizmos_fields[] = array(
	'label' 	=> 'Widgets',
	'name' 		=> 'widgeties',
	'lang' 		=> '',
	'data_type_type'	=> '',
	'data_type_length'	=> '',
	'data_type_values'	=> array(),
	'field_type_type'				=> 'relation',
	'field_type_relation_name'		=> 'widgets',
	'field_type_relation_type'		=> '1:n',
    'validation'	=> array(
        'required' => array(
            'message' => 'Please add at least one Widget'
        )
    ),
	'default'		=> array(),
	'module'		=> 'gizmos',
	'module_pk'		=> 'gizmo_id',
	'tooltip'		=> '',
	'tooltip_lang'	=> '',
	'is_list_col'	=> false,
	'is_filter'		=> true,
	'is_model'		=> true,
	'sort_order'	=> 4
);

/*
$gizmos_fields[] = array(
    'label' 	=> 'Gizmos',
    'name' 		=> 'gizmies',
    'lang' 		=> '',
    'data_type_type'	=> '',
    'data_type_length'	=> '',
    'data_type_values'	=> array(),
    'field_type_type'				=> 'relation',
    'field_type_relation_name'		=> 'gizmos',
    'field_type_relation_type'		=> 'n:1',
    'validation'	=> array(
        'required' => array(
            'message' => 'Please select a gizmo'
        )
    ),
    'default'		=> array(),
    'module'		=> 'gizmos',
    'module_pk'		=> 'gizmo_id',
    'tooltip'		=> '',
    'tooltip_lang'	=> '',
    'is_list_col'	=> false,
    'is_filter'		=> true,
    'is_model'		=> true,
    'sort_order'	=> 5
);
*/

$gizmos_data = array(
	'pk_field' 		=> 'gizmo_id',
	'title_field' 	=> 'title',
	'slug_field' 	=> 'title',
	'name' 			=> 'gizmos',
	'label' 		=> 'Gizmo',
	'label_plural' 	=> 'Gizmos',
	'css_includes_files'	=> array(),
	'js_includes_files'		=> array(),
	'js_load_block'	=> "
	
//[ JS LOAD BLOCK ]
	
	",
	'js_unload_block'=> "
	
//[ JS UNLOAD BLOCK ]
	
	",
	'form_fields' 	=> $gizmos_fields,
	'slug' 			=> 'gizmos',
	'use_model' 	=> 1,		//toggle create table or use options table
	'use_add' 		=> 1,
	'use_edit' 		=> 1,
	'use_delete' 	=> 1,
	'use_active' 	=> 1,
	'use_sort' 		=> 1,
	'use_archive' 	=> 1,
	'use_slug' 		=> 1,
    'use_cms_form' 	=> 1,
    'use_frontend_form' => 0,
    'use_frontend_list' => 0,
	'is_active' 	=> 1
);

$widgets_fields[1]['field_type_is_24hr'] = 1;
$widgets_fields[10] = array(
	'label' 	=> 'File Upload',
	'name' 		=> 'files',
	'lang' 		=> '',
	'data_type_type'			=> 'text',
	'field_type_type'			=> 'file',
	'field_type_upload_config' 	=> 'test',
    'validation'	=> array('required' => true),
	'default'		=> array(),
	'module'		=> 'widgets',
	'module_pk'		=> 'widget_id',
	'tooltip'		=> '<p>Some files on your computer/phone that you upload.</p>',
	'is_list_col'	=> false,
	'is_filter'		=> false,
	'is_model'		=> true,
	'sort_order'	=> 10
);

$widgets_data = array(
	'pk_field' 		=> 'widget_id',
	'title_field' 	=> 'title',
	'slug_field' 	=> 'title',
	'name' 			=> 'widgets',
	'label' 		=> 'Widget',
	'label_plural' 	=> 'Widgets',
	'css_includes_files'	=> array(),
	'js_includes_files'		=> array(),
	'js_load_block'	=> "",
	'js_unload_block'=> "",
	'form_fields' 	=> $widgets_fields,
	'slug' 			=> 'widgets',
	'use_model' 	=> 1,
	'use_add' 		=> 1,
	'use_edit' 		=> 1,
	'use_delete' 	=> 1,
	'use_active' 	=> 0,
	'use_sort' 		=> 1,
	'use_archive' 	=> 0,
	'use_slug' 		=> 1,
    'use_cms_form' 	=> 0,
    'use_frontend_form' => 0,
    'use_frontend_list' => 0,
	'is_active' 	=> 1
);

$gizmos = array(
	array(
		'gizmo_id' 	=> 1,
		'post_date' => '2013-12-25',
		'post_time' => '08:15:00',
		'title' 	=> 'Samplé "Item" 1',
		'day'		=> 'monday',
		'dessert'	=> array('ice_cream', 'cake'),
		'content'	=> '<p>WHoo! "Content" here, yeah!</p>',
		'choose'	=> array('monday', 'tuesday'),
		'color'		=> 'black',
		'is_active' => 1,
		'attributes'=> array(
			'disabled' 	=> 'disabled',
			'maxlength' => 32,
			'data-id' 	=> 4
		),
		'klass'		=> array(
			'columnit',
			'tree-hug',
			'whiteBG'
		),
		'images'	=> array(
			'test.jpg',
			'test.png'
		),
		'widgeties'	=> array(),
		'uploads'	=> array(
			'images' => array(
				array(
					'filename' => 'test.jpg',
					'filesize' => '3.9 KB',
					'filetype' => 'JPEG Image'
				),
				array(
					'filename' => 'test.png',
					'filesize' => '1.6 KB',
					'filetype' => 'PNG Image'
				)
			)
		)
	),
	array(
		'gizmo_id' 	=> 2,
		'post_date' => '2014-01-01',
		'post_time' => '12:30:00',
		'title' 	=> "Sample 'Item' 2",
		'day'		=> 'tuesday',
		'dessert'	=> array('ice_cream', 'pie'),
		'content'	=> 'WHoo hoo! More content here, yeah!',
		'choose'	=> array('monday', 'wednesday'),
		'color'		=> 'blue',
		'is_active' => 1,
		'attributes'=> array(
			 'maxlength' => 64
		),
		'klass'		=> array(
			'blackBG'
		),
		'images'	=> array(),
		'widgeties'	=> array(),
		'uploads'	=> array('images' => array())
	),
	array(
		'gizmo_id' 	=> 3,
		'post_date' => '2014-01-08',
		'post_time' => '15:45:00',
		'title' 	=> "Sample 'Item' 3",
		'day'		=> 'wednesday',
		'dessert'	=> array('ice_cream', 'fruit'),
		'content'	=> 'Nam augue nunc, imperdiet ut justo eget, mollis ultricies ipsum.',
		'choose'	=> array('monday', 'thursday'),
		'color'		=> 'red',
		'is_active' => 1,
		'attributes'=> array(),
		'klass'		=> array(),
		'images'	=> array(),
		'widgeties'	=> array(),
		'uploads'	=> array('images' => array())
	),
	array(
		'gizmo_id' 	=> 4,
		'post_date' => '2014-01-15',
		'post_time' => '20:00:00',
		'title' 	=> 'Sample Item 4',
		'day'		=> 'thursday',
		'dessert'	=> array('cake', 'pie'),
		'content'	=> 'Nunc vehicula turpis arcu, eget ultricies neque facilisis vitae.',
		'choose'	=> array('tuesday', 'thursday'),
		'color'		=> 'green',
		'is_active' => 0,
		'attributes'=> array(),
		'klass'		=> array(),
		'images'	=> array(),
		'widgeties'	=> array(),
		'uploads'	=> array('images' => array())
	),
	array(
		'gizmo_id' 	=> 5,
		'post_date' => '2014-01-22',
		'post_time' => '08:37:00',
		'title' 	=> 'Sample Item 5',
		'day'		=> 'monday',
		'dessert'	=> array('cake', 'fruit'),
		'content'	=> 'Nulla mattis pellentesque turpis quis mattis.',
		'choose'	=> array('wednesday', 'thursday'),
		'color'		=> 'black',
		'is_active' => 1,
		'attributes'=> array(),
		'klass'		=> array(),
		'images'	=> array(),
		'widgeties'	=> array(),
		'uploads'	=> array('images' => array())
	),
	array(
		'gizmo_id' 	=> 6,
		'post_date' => '2014-01-29',
		'post_time' => '00:00:00',
		'title' 	=> 'Sample Item 6',
		'day'		=> 'tuesday',
		'dessert'	=> array('pie', 'fruit'),
		'content'	=> 'Duis vitae augue commodo, ornare augue eu, porta leo.',
		'choose'	=> array('tuesday'),
		'color'		=> 'blue',
		'is_active' => 0,
		'attributes'=> array(),
		'klass'		=> array(),
		'images'	=> array(),
		'widgeties'	=> array(),
		'uploads'	=> array('images' => array())
	),
	array(
		'gizmo_id' 	=> 7,
		'post_date' => '2014-02-05',
		'post_time' => '00:00:00',
		'title' 	=> 'Sample Item 7',
		'day'		=> 'wednesday',
		'dessert'	=> array('ice_cream', 'cake', 'pie'),
		'content'	=> 'Nam bibendum porttitor elit vel aliquam. Sed pharetra consectetur felis.',
		'choose'	=> array('monday', 'tuesday', 'thursday'),
		'color'		=> 'red',
		'is_active' => 1,
		'attributes'=> array(),
		'klass'		=> array(),
		'images'	=> array(),
		'widgeties'	=> array(),
		'uploads'	=> array('images' => array())
	),
	array(
		'gizmo_id' 	=> 8,
		'post_date' => '2014-02-12',
		'post_time' => '00:00:00',
		'title' 	=> 'Sample Item 8',
		'day'		=> 'thursday',
		'dessert'	=> array('cake', 'pie', 'fruit'),
		'content'	=> 'Mauris feugiat enim non elit accumsan, id rutrum arcu auctor.',
		'choose'	=> array('monday', 'tuesday', 'wednesday', 'thursday'),
		'color'		=> 'green',
		'is_active' => 0,
		'attributes'=> array(),
		'klass'		=> array(),
		'images'	=> array(),
		'widgeties'	=> array(),
		'uploads'	=> array('images' => array())
	),
	array(
		'gizmo_id' 	=> 9,
		'post_date' => '2014-02-19',
		'post_time' => '00:00:00',
		'title' 	=> 'Sample Item 9',
		'day'		=> 'monday',
		'dessert'	=> array('ice_cream', 'pie', 'fruit'),
		'content'	=> 'Ut eget malesuada felis, quis molestie nisi. Nullam posuere eleifend condimentum. ',
		'choose'	=> array('monday'),
		'color'		=> 'black',
		'is_active' => 1,
		'attributes'=> array(),
		'klass'		=> array(),
		'images'	=> array(),
		'widgeties'	=> array(),
		'uploads'	=> array('images' => array())
	),
	array(
		'gizmo_id' 	=> 10,
		'post_date' => '2014-02-26',
		'post_time' => '00:00:00',
		'title' 	=> 'Sample Item 10',
		'day'		=> 'tuesday',
		'dessert'	=> array('ice_cream', 'cake', 'pie', 'fruit'),
		'content'	=> 'Proin sit amet faucibus augue. Integer dignissim sapien vel leo eleifend tempus.',
		'choose'	=> array('thursday'),
		'color'		=> 'blue',
		'is_active' => 1,
		'attributes'=> array(),
		'klass'		=> array(),
		'images'	=> array(),
		'widgeties'	=> array(),
		'uploads'	=> array('images' => array())
	)
);
$gizmos[0]['widgeties'] = array($gizmos[9], $gizmos[8], $gizmos[7], $gizmos[6], $gizmos[5], $gizmos[4]);
$gizmos[1]['widgeties'] = array($gizmos[3], $gizmos[2]);
$count = 1;
foreach ($gizmos as &$gizmo){
	foreach ($gizmo['widgeties'] as &$widget){
	    if ($count === 1) {
            $widget['files'] = array(
                'test_document.doc',
                'test_document.pdf'
            );
		    $widget['uploads'] = array(
                'files' => array(
                    array(
                        'filename' => 'test_document.doc',
                        'filesize' => '20 KB',
                        'filetype' => 'MS Word Document'
                    ),
                    array(
                        'filename' => 'test_document.pdf',
                        'filesize' => '6.0 KB',
                        'filetype' => 'PDF Document'
                    )
                )
            );
        } else {
            $widget['files'] = array();
            $widget['uploads']['files'] = array();
        }

        $widget['widget_id'] = $count++;
        unset($widget['images']);
        unset($widget['uploads']['images']);
        unset($widget['widgeties']);
		unset($widget['gizmo_id']);
	}
}

// OPTIONS TEST

$testo_fields = array(
    array(
        'label' 	=> 'Descriptive Name',
        'name' 		=> 'name',
        'lang' 		=> '',
        'data_type_type'	=> 'varchar',
        'data_type_length'	=> 64,
        'data_type_values'	=> array(),
        'field_type_type'	=> 'text',
        'field_type_placeholder'=> '',
        'field_type_attributes' => array(
            'max_length' => 64
        ),
        'validation' => array(
            'required' => true
        ),
        'default'		=> '',
        'module'		=> 'testo',
        'module_pk'		=> 'id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> true,
        'is_filter'		=> true,
        'is_model'		=> true,
        'sort_order'	=> 1
    ),
    array(
        'label' 	=> 'Date',
        'name' 		=> 'date',
        'lang' 		=> '',
        'data_type_type'	=> 'date',
        'data_type_length'	=> '',
        'data_type_values'	=> array(),
        'field_type_type'	=> 'date',
        'field_type_format'	=> 'm/d/Y',
        'validation'		=> array('required' => true),
        'default'		=> '',
        'module'		=> 'testo',
        'module_pk'		=> 'id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> true,
        'is_filter'		=> true,
        'is_model'		=> true,
        'sort_order'	=> 2
    ),
    array(
        'label' 	=> 'Time',
        'name' 		=> 'time',
        'lang' 		=> '',
        'data_type_type'	=> 'time',
        'data_type_length'	=> '',
        'data_type_values'	=> array(),
        'field_type_type'	=> 'time',
        'field_type_is_24hr'=> 1,
        'validation'		=> array(
            'required' => true
        ),
        'default'		=> '',
        'module'		=> 'testo',
        'module_pk'		=> 'id',
        'tooltip'		=> '',
        'tooltip_lang'	=> '',
        'is_list_col'	=> true,
        'is_filter'		=> true,
        'is_model'		=> true,
        'sort_order'	=> 3
    )
);

$testo_data = array(
    'pk_field' 		=> '',
    'title_field' 	=> 'name',
    'slug_field' 	=> '',
    'name' 			=> 'testo',
    'label' 		=> 'Testo',
    'label_plural' 	=> '',
    'css_includes_files'	=> array(),
    'js_includes_files'		=> array(),
    'js_load_block'	=> "",
    'js_unload_block'=> "",
    'form_fields' 	=> $testo_fields,
    'slug' 			=> 'testo',
    'use_model' 	=> 0,
    'use_add' 		=> 1,
    'use_edit' 		=> 1,
    'use_delete' 	=> 1,
    'use_active' 	=> 1,
    'use_sort' 		=> 1,
    'use_archive' 	=> 1,
    'use_slug' 		=> 1,
    'is_active' 	=> 1
);

use App\Html\Form\Field\Form_field;
$config = array(
    'name' => 'gizmos',
    'module' => 'gizmos',
    'field_type' => array(
        'image' => array(
            'module' => 'widgetz',
            'config_name' => 'testorooni'
        )
    ),
    'is_filter' => true
);
$FF = new Form_field($config);
$FF->html();

/*

// TEMPLATE

//$parts = Template::get_content('home', 'default', NULL, true);
//print_r($parts);
//echo Template::get('home', 'default');
$parts = Template::get_content('page', 'test-page', array(), true);
print_r($parts);

// SERVER VARS

echo "DOCUMENT_ROOT: ".$_SERVER['DOCUMENT_ROOT']."\n";
echo "APP_PATH: ".APP_PATH."\n";
echo "API_DIR: ".API_DIR."\n";
echo "WEB_ROOT: ".WEB_ROOT."\n";
echo "WEB_BASE: ".WEB_BASE."\n\n";
echo "<br/>\nHTTP_USER_AGENT: ".$_SERVER['HTTP_USER_AGENT']."<br/>";

//MODULE RESET

$module = Module::load('modules');
$result = $module->reset(true);
if ( is_array($result) ) {
    print_r($result);
} else {
    echo "\n\nRESET successful!\n\n";
}

//TEST DATA

$module = Module::create($widgets_data);
if ( is_array($module) ) {
    print_r($module);
}
$module = Module::load('modules');
$data = $module->get_data('widgets', true);
print_r($data);
$module = Module::create($gizmos_data);
if ( is_array($module) ) {
    print_r($module);
}
$module = Module::load('modules');
$data = $module->get_data('gizmos', true);
print_r($data);
$module_name = 'gizmos';
foreach ($gizmos as &$item) {
    $item = Form::form_field_values($module_name, $item);
    $item = Form::form_data_values($module_name, $item);
}
$module = Module::load('gizmos');
foreach ($gizmos as $item) {
    $module->add($item);
}

$module = Module::create($testo_data);
if ( is_array($module) ) {
    print_r($module);
}


// SLIM: Get request object

$req = $Slim->request;
//Get root URI
echo "ROOT URI: ".$req->getRootUri()."<br/>";
//Get resource URI
echo "RRESOURCE URI: ".$req->getResourceUri();

//FORM

$module_name = 'gizmos';
$module = Module::load($module_name);
$form = $module->get_cms_form(1, new Permission(Permission::PERMISSION_SUPER_USER) );
print_r($form);

$module_name = 'pages';
$module = Module::load($module_name);
$form = $module->get_cms_form(1, new Permission(Permission::PERMISSION_SUPER_USER) );
print_r($form);


$files = array('test_document.doc', 'test_document.pdf');
$images = array('test.jpg', 'test.png');
$files_info = $app->fileinfo('test', $files, false);
$images_info = $app->fileinfo('test', $images, true);
print_r($files_info);
print_r($images_info);


$module = Module::load('modules');
$data = $module->get_data('gizmos', true, true);
print_r($data);


set_time_limit(0);
$module = Module::load('modules');
$data = $module->get_data('news', true, true);
$data['use_model'] = 1;
$last = array_pop($data['form_fields']);
$data['form_fields'][] = $relation;
$data['form_fields'][] = $new_field;
$data['form_fields'][] = $last;
print_r($data);
echo "\n\n";
$result = Module::modify($data);
if ( is_array($result) ) {
	print_r($result);
} else {
	echo 'Success';
}


$module = Module::load('news');
$options = $module->get_options();
print_r($options);
$options['news_date'] = '2015-04-15';
$options['title'] = 'Test Title';
$options['description'] = 'Just a test folks';
$options['short_descr'] = 'Test... only';
echo $module->update_options($options) ? "Success" : "FAIL";
$options = $module->get_options();
print_r($options);


$module = Module::load('modules');
$data = $module->get_data('news', true);
$data['use_model'] = 0;
print_r($data);
echo "\n\n";
$result = Module::modify($data);
if ( is_array($result) ) {
	print_r($result);
} else {
	echo 'Success';
}


Module::drop('news');
*/


/*
TRUNCATE aa_modules;
TRUNCATE aa_form_fields;
TRUNCATE aa_modules2form_fields;
TRUNCATE aa_slugs;
ALTER TABLE aa_modules2form_fields AUTO_INCREMENT=1000;
ALTER TABLE aa_modules AUTO_INCREMENT=1000;
ALTER TABLE aa_form_fields AUTO_INCREMENT=1000;
DROP TABLE IF EXISTS aa_gizmos;
DROP TABLE IF EXISTS aa_widgets;
DROP TABLE IF EXISTS aa_gizmos2widgets;


DELETE FROM aa_slugs WHERE module IN ('gizmos', 'widgets');
DELETE FROM aa_options WHERE module='news';
DROP TABLE IF EXISTS aa_news;
DROP TABLE IF EXISTS aa_news_meta;
DROP TABLE IF EXISTS aa_news2news_meta;
DROP TABLE IF EXISTS aa_news2modules;

//FORM

$module_data = $module->get_module_data();
$fields = $module->get_form_fields();
$form_config = array();
$form_config['module_name'] = $module_name;
$form_config['title'] = $module_data['label'];
$form_config['is_cms'] = true;
$form_config['is_horizontal'] = true;
$form_config['is_readonly'] = false;
$form_config['fields'] = $fields; //$f_config['form_fields'];
$form_config['class'] = 'test-form';
$Form = new \App\Form\Form($form_config);
$f = $Form->generate();
print_r($f);

//SESSION

$app->session->session_start();
$user = $app->session->get_data('user');
print_r($user);
echo "\n\n";
echo $app->session->is_crsf_valid() ? "CRSF VALID" : "crsf invalid";



$config = array(
	'database' => 'test',
	'module' => 'hamburgler',
	'pk_field' => 'id',
	'title_field' => 'name',
	'slug_field' => 'name',
	'fields' => array(
		'id' => 0,
		'name' => '',
		'notes' => '',
		'bio' => '',
		'is_dead' => 0,
		'employee_num' => 0,
		'random' => 0.000,
		'cost' => 0.00,
		'initials' => '',
		'today' => '',
		'curr_time' => '',
		'stampo' => '',
		'choose' => 'three',
		'is_active' => 1,
		'is_archive' => 0,
		'sort_order' => 0,
		'slug' => ''
	)
);

$columns = array(
	'id' => array(
		'type' => 'int',
		'length' => 11,
		'default' => 0
	),
	'name' => array(
		'type' => 'varchar',
		'length' => 64,
		'default' => ''
	),
	'notes' => array(
		'type' => 'text'
	),
	'bio' => array(
		'type' => 'mediumtext'
	),
	'is_dead' => array(
		'type' => 'tinyint'
	),
	'employee_num' => array(
		'type' => 'int',
		'length' => 8,
		'default' => 0
	),
	'random' => array(
		'type' => 'float',
		'length' => 10,
		'default' => 0.000
	),
	'cost' => array(
		'type' => 'decimal',
		'default' => 0
	),
	'initials' => array(
		'type' => 'char',
		'length' => 4
	),
	'today' => array(
		'type' => 'date'
	),
	'curr_time' => array(
		'type' => 'time'
	),
	'stampo' => array(
		'type' => 'datetime'
	),
	'choose' => array(
		'type' => 'enum',
		'values' => array('one', 'two', 'three', 'four'),
		'default' => 'one'
	)
);

$alter = array(
	'add' => array(
		'nickname' => array(
			'type' => 'varchar',
			'length' => 16,
			'default' => '',
			'after' => 'name'
		),
		'siglos' => array(
			'type' => 'char',
			'length' => 4
		)
	),
	'change' => array(
		'random' => array(
			'new_name' => 'fixied',
			'type' => 'decimal'
		),
		'name' => array(
			'new_name' => 'pretty_name',
			'type' => 'varchar',
			'length' => 64,
			'default' => ''
		)
	),
	'modify' => array(
		'today' => array(
			'type' => 'datetime'
		),
		'notes' => array(
			'type' => 'text',
			'default' => 'Is stupid'
		)
	),
	'drop' => array(
		'id' => array(),
		'employee_num' => array(),
		'stampo' => array()
	),
	'rename' => 'ronaldo',
);

$model = new \App\Model\Model($config);
//$model->create_table($columns);
$model->alter_table($alter);
//$model->drop_table();

//RELATIONS

$config1 = array(
	'module' => 'test',
	'pk_field' => 'id',
	'title_field' => 'last_name',
	'slug_field' => 'first_name',
	'fields' => array(
		'id' => 0,
		'first_name' => '',
		'last_name' => '',
		'is_active' => 1,
		'is_archive' => 0,
		'sort_order' => 0,
		'slug' => ''
	),
	'database' => 'test'
);

$config2 = array(
	'module' => 'categories',
	'pk_field' => 'id',
	'title_field' => 'name',
	'slug_field' => 'name',
	'fields' => array(
		'id' => 0,
		'name' => '',
		'is_active' => 1,
		'sort_order' => 0,
		'slug' => ''
	),
	'database' => 'test'
);

$test = new \App\Model\Model($config1);
$categories = new \App\Model\Model($config2);

$config = array(
	'relation_type' => Relation::RELATION_TYPE_N1,
	'dep_model' => $test,
	'indep_model' => $categories,
	'database' => 'test'
);
$relation = new Relation($config);
$relation->delete(1, array(2) );

//OPTIONS

$config = array(
	'module' => 'test',
	'title_field' => 'name',
	'fields' => array(
		'bg_color'	=> '#FFFFFF',
		'template'	=> 'page',
		'footer' 	=> '',
		'callout' 	=> ''
	),
	'database' => 'test'
);

$options = new \App\Model\Options($config);
$op = $options->get();
print_r($op);

$options = new \App\Model\Options($config);
$op = $options->get();
print_r($op);

$options->create();
$vals = array(
	'bg_color' => '#000000',
	'template' => 'home',
	'footer' => 'I am a footer!',
	'callout' => 'This is a callout'
);
$options->update($vals);


//MODEL

$row = $model->get(1);

$model->delete( array(3, 4) );

$row = array(
	'id' => 1,
	'first_name' => 'José Peña Jingleheimer Fracaso de la Soul María "Fastback" Eddy',
	'last_name' => 'Smitty',
	'is_active' => 1,
	'is_archive' => 0,
	'sort_order' => 1,
	'slug' => 'jose-pena-jingleheimer-catastrophe-de-la-soul-maria-fastback-edd'
);
$row = array(
	'id' => 1,
	'last_name' => 'Smitten',
	'is_active' => 1,
	'is_archive' => 0
);
echo "UPDATE: ".$model->update($row)."<br/>";

$model->set_archive(array(1, 2, 3, 4), false);
$model->set_sort_order(array(1, 2, 3, 4));

$params = array(
	'where' => array(
		'_condition' => 'OR',
		'%like%' => array(
			'_condition' => 'OR',
			'first_name' => 'John',
			'last_name' => 'S'
		),
		'like%' => array(
			'_condition' => 'OR',
			'first_name' => 'John',
			'last_name' => 'S'
		)
	),
	'order_by' => 'dweezle',
	'is_asc' => false,
	'offset' => 2,
	'limit' => 2
);
$rows = $model->get_list($params);
header('Content-type: text/html; charset=utf-8');
print_r($rows);

$row = $model->get(1);

$row1 = array(
	'first_name' => 'José Peña Jingleheimer Catastrophe de la Soul María "Fastback" Eddy',
	'last_name' => 'Smitty',
	'is_active' => 0,
	'sort_order' => 0,
	'slug' => ''
);

$row2 = array(
	'first_name' => 'José Peña Jingleheimer Catastrophe de la Soul María "Fastback" Eddy',
	'last_name' => 'Smittipong',
	'is_archive' => 1,
	'sort_order' => 0,
	'slug' => ''
);

$row3 = array(
	'first_name' => 'Johnny',
	'last_name' => 'Spence',
	'is_active' => 0,
	'is_archive' => 1,
	'sort_order' => 0,
	'slug' => ''
);

$row4 = array(
	'first_name' => 'Johnny',
	'last_name' => 'Smith',
	'sort_order' => 0,
	'slug' => ''
);

echo "ROW1: ".$model->insert($row1)."<br/>";
echo "ROW2: ".$model->insert($row2)."<br/>";
echo "ROW3: ".$model->insert($row3)."<br/>";
echo "ROW4: ".$model->insert($row4)."<br/>";
*/

/*
//DATABASE

$app = \App\App::get_instance();
$db = $app->db();

$query = "INSERT INTO test (first_name, last_name) VALUES ('Johnny', 'Depp'), ('Ida', 'Know'), ('Johnny', 'Dope');";
$result = $db->query($query);
echo "INSERT RESULT: ".$result;

$query = "UPDATE test SET first_name='Johann', last_name='\"Deeper\"' WHERE id=2;";
$result = $db->query($query);
echo "UPDATE RESULT: ".$result;


$query = "SHOW COLUMNS FROM test;";
$result = $db->query($query);
$rs = $result->result_assoc();
print_r($rs);

$query = "SELECT * FROM test;";
$result = $db->query($query);
echo "ROWS: ".$result->num_rows()."\n\n";
$rs = $result->result_array();
print_r($rs);

$query = "DELETE FROM ";
$query .= $db->escape_identifier('test')." ";
$query .= "WHERE id IN (";
$query .= $db->escape(2).", ";
$query .= $db->escape(3).");";
$result = $db->query($query);
echo $query."<br/>";
echo "DELETE RESULT: ".$result;

$query = "DELETE FROM test WHERE id IN (2, 3);";
$result = $db->query($query);
echo "DELETE RESULT: ".$result;

$row = $result->row();
print_r($row);
$row = $result->row(false);
print_r($row);
while (($row = $result->row()) !== NULL) {
	print_r($row);
}

$str = 'Hi, my "name" is \'johnny\' Depp';
echo $db->escape($str).'<br/>';
echo $db->escape_str($str).'<br/>';

$query = "SELECTO * FROM dorky;";
$result = $db->query($query);
echo "ERROR CODE: ".$db->error_code()."<br/>\n";
echo $db->error_info();

$query = "SELECTO * FROM dorky;";
$result = $db->query($query);
echo "ERROR CODE: ".$db->error_code()."<br/>\n";
echo $db->error_info();
*/

