<?php

namespace App\HTML\ListPage;

use
App\App,
App\Module\Module;



/**
 * ListPage class
 *
 *
 *
 * @author      Johnny Spence <info@projectabstractcms.org>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.org
 * @version     0.1.0
 * @package		App\Html\AdminList
 */
class ListPage {
    /**
     * @var Name of database "active" boolean field for modules
     */
    protected static $ACTIVE_FIELD = 'is_active';

    /**
     * @var Name of database "archive" boolean field for modules
     */
    protected static $ARCHIVE_FIELD = 'is_archive';

    /**
     * @var Maximum columns in addition to ID and title field
     */
    protected static $MAX_COLUMNS = 3;

    /**
     * @var \App\App Instance of the main App class
     */
    protected $App;

    /**
     * @var \App\Html\Form\Field\Form_field Form felds of module
     */
    protected $fields;

    /**
     * @var bool True if list page is archived records page
     */
    protected $is_archive;

    /**
     * @var \App\Module\Module Instance of module class
     */
    protected $module;

    /**
     * @var array Pagination parameters
     */
    protected $pagination;



    public function __construct($mixed, $is_archive=false) {
        $this->App = App::get_instance();
        $this->module = $mixed instanceof \App\Module\Module ? $mixed : Module::load($mixed);
        $this->fields = $this->module->get_form_fields();

        $data = $this->module->get_module_data();
        $this->pagination = array(
            'page' 			=> 1,
            'per_page' 		=>  $this->App->config('front_list_per_page'),
            'sort_by' 		=> $data['pk_field'],
            'order' 		=> 'asc',
            'total_pages' 	=> 1,
            'total_entries' => 0
        );
        $this->is_archive = $is_archive;
    }


    public function columns() {
        $data = $this->module->get_module_data();
        $columns = array();
        $fields = $this->fields;
        $pk_field = $data['pk_field'];
        $max_items = empty($data['use_active']) ? self::$MAX_COLUMNS : self::$MAX_COLUMNS - 1;

        //ID (primary key) is first column
        $columns[] = array(
            'label' 	=> 'ID',
            'name' 		=> $pk_field,
            'cell' 		=> 'string',
            'editable' 	=> false,
            'sortable' 	=> true,
            'sortType' 	=> 'toggle'
        );

        //title field is second column
        foreach ($fields as $i => $field) {
            $title_field = $data['title_field'];
            if ( $field->get_data('name') === $title_field ) {
                $lang = $field->get_data('lang');
                $label = empty($lang) ? $field->get_data('label') : $this->App->lang($lang);
                $columns[] = array(
                    'label' 	=> $label,
                    'name' 		=> $title_field,
                    'cell' 		=> 'string',
                    'editable' 	=> false,
                    'sortable' 	=> true,
                    'sortType' 	=> 'toggle'
                );
                unset($fields[$i]);
                break;
            }
        }

        $count = 1;
        foreach ($fields as $field) {
            if ($count > $max_items) {
                break;
            }

            if ( $field->get_data('is_list_col') ) {
                $lang = $field->get_data('lang');
                $label = empty($lang) ? $field->get_data('label') : $this->App->lang($lang);
                $type = $field->get_type();
                $cell = 'string';
                switch ($type) {
                    case 'date':
                        $cell = 'date';
                        break;
                    case 'time':
                        $cell = 'time';
                        break;
                }

                $columns[] = array(
                    'label' 	=> $label,
                    'name' 		=> $field->get_data('name'),
                    'cell' 		=> $cell,
                    'editable' 	=> false,
                    'sortable' 	=> true,
                    'sortType' 	=> 'toggle'
                );
                $count++;
            }
        }

        //if module uses "active" field, then last column
        if ($data['use_active']) {
            $columns[] = array(
                'label' 	=> 'Active',
                'name' 		=> self::$ACTIVE_FIELD,
                'cell' 		=> 'string',
                'editable' 	=> false,
                'sortable' 	=> true,
                'sortType' 	=> 'toggle'
            );
        }

        return $columns;
    }


    public function db_query_params($get) {
        $data = $this->module->get_module_data();
        $like = array();
        $equals = array();
        $in = array();
        $has_search = ! empty($get['search']);

        //add primary key for list of fields to search
        $pk_field = $data['pk_field'];
        if ($has_search) {
            $like[$pk_field] = $get['search'];
        }

        foreach ($this->fields as $field) {
            $field_name = $field->get_data('name');
            if ($field_name === $pk_field) {
                continue;
            }
            if ( $field->is_select_filter() === true ) {
                if ( isset($get[$field_name]) && $get[$field_name] !== '' ) {
                    if ( is_array($get[$field_name]) ) {
                        $in[$field_name] = $get[$field_name];
                    } else {
                        $equals[$field_name] = $get[$field_name];
                    }
                }
            } else if ($has_search && $field->is_filter() === true ) {
                $like[$field_name] = $get['search'];
            }
        }

        if ($data['use_active'] && ! isset($equals[self::$ACTIVE_FIELD]) &&
            isset($get[self::$ACTIVE_FIELD]) && $get[self::$ACTIVE_FIELD] !== '' ) {
            $equals[self::$ACTIVE_FIELD] = $get[self::$ACTIVE_FIELD];
        }

        if ($data['use_archive'] && ! isset($equals[self::$ARCHIVE_FIELD]) &&
            isset($get[self::$ARCHIVE_FIELD]) && $get[self::$ARCHIVE_FIELD] !== '' ) {
            $equals[self::$ARCHIVE_FIELD] = $get[self::$ARCHIVE_FIELD];
        }

        $params = array();
        if ( ! empty($equals) || ! empty($like) || ! empty($in) ) {
            $params = array('_condition' => 'OR');
            if ( ! empty($equals) ) {
                $equals['_outer_cnd'] = 'AND';
                $equals['_condition'] = 'AND';
                $params['equals'] = $equals;
            }
            if ( ! empty($like) ) {
                $like['_outer_cnd'] = 'AND';
                $like['_condition'] = 'OR';
                $params['%like%'] = $like;
            }
            if ( ! empty($in) ) {
                $in['_outer_cnd'] = 'AND';
                $in['_condition'] = 'AND';
                $params['in'] = $in;
            }
        }

        return $params;
    }


    public function filter_query_params($get) {
        $data = $this->module->get_module_data();
        $params = array();
        $has_search = isset($get['search']);

        foreach ($this->fields as $field) {
            $field_name = $field->get_data('name');
            $val = isset($get[$field_name]) ? $get[$field_name] : '';
            if ( $field->is_select_filter() === true ) {
                $params[$field_name] = $val;
            } else if ( $field->is_filter() === true ) {
                $has_search = true;
            }
        }

        if ($has_search) {
            $params['search'] = isset($get['search']) ? $get['search'] : '';
        }
        return $params;
    }


    public function pagination_params() {
        return $this->pagination;
    }


    public function row_boolean_vals($rows) {
        if ( empty($rows) || ! is_array($rows) ) {
            return $rows;
        }

        $data = $this->module->get_module_data();
        $field_type = $data['field_data']['field_type'];
        $bool_types = array('boolean', 'jqm_flipswitch');

        //check if single row or array of rows
        $key = key($rows);
        $is_single = ! is_numeric($key);
        if ($is_single) {
            $rows = array($rows);
        }

        foreach ($rows as &$row) {
            foreach ($row as $field => $val) {
                $type = empty($field_type[$field]) ? '' : $field_type[$field];
                if ( in_array($type, $bool_types) ) {
                    $row[$field] = (int) $val === 1 ? 'Yes' : 'No';
                }
            }
        }

        return $is_single ? $rows[0] : $rows;
    }
}