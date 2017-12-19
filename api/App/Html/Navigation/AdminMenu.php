<?php

namespace App\Html\Navigation;

use
App\App,
App\Model\Relation,
App\Module\Module,
App\User\Permission;

/**
 * AdminMenu class
 *
 * Generates the navigation menu and jQuery Mobile search panel for the CMS. Menu
 * items appear based on the logged-in user's pemissions and will need at least a
 * read permission for the item to appear. The search panel allows a search for
 * a particular module/function.
 *
 * @author      Johnny Spence <info@projectabstractcms.org>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.org
 * @version     0.1.0
 * @package		App\Html\Navigation
 */
class AdminMenu {

    /**
     * @var array Index of keywords related to specific functions in the CMS
     */
    private static $SEARCH_TERMS = array(
        'add' => array('add', 'new'),
        'home' => array('home', 'page', 'homepage', 'start'),
        'list' => array('delete', 'edit', 'list', 'manage', 'update', 'view'),
        'logout' => array('logout', 'session', 'end'),
        'options' => array('config', 'options', 'settings', 'update'),
        'relation' => array('relation', 'relations'),
        'sort' => array('arrange', 'sort', 'numeric', 'order')
    );

    /**
     * @var \App\App Instance of the main App class
     */
    protected $App;

    /**
     * @var array Row data for all modules
     */
    protected $modules;

    /**
     * @var array Relation (1:n) data for all modules
     */
    protected $relations;

    /**
     * @var array Current logged in user information
     */
    protected $user;

    /**
     * @var \App\Module\Module_users Instance of the users module subclass
     */
    protected $Users;


    /**
     * Constructor
     *
     * Initializes the AdminMenu. Note that if the logged-in user ID is invalid and is not
     * a super user, then empty menu/search panels are generated.
     *
     * @access public
     * @param mixed $user_id The logged-in user ID or an empty value if the next parameter is true
     * @param bool $is_super True if user is a super user (has all permissions)
     */
    public function __construct($user_id, $is_super) {
        $this->App = App::get_instance();
        $this->Users = Module::load('users');

        $this->modules = $this->Users->get_all_modules_data();
        $this->relations = array();

        // All modules with 1:n relations will not have direct links to the relation
        // in the navigation but will have link to parent module for search panel
        foreach ($this->modules as $module_name => $module_data) {
            if ( ! empty($module_data['field_data']['relations']) ) {
                $relations = $module_data['field_data']['relations'];
                foreach ($relations as $field_name => $rel_data) {
                    if ($rel_data['type'] === Relation::RELATION_TYPE_1N) {
                        $rel_module = $rel_data['module'];
                        if ( ! isset($this->relations[$module_name]) ) {
                            $this->relations[$module_name] = array();
                        }
                        $this->relations[$module_name][] = $this->modules[$rel_module];
                        unset($this->modules[$rel_module]);
                        continue 2;
                    }
                }
            }
        }
        ksort($this->modules);

        $model = $this->Users->get_model();
        $this->user = $is_super ? array('is_super' => true) : $model->get($user_id);
        $this->App->load_util('navigation');
    }


    /**
     * generate
     *
     * Generates the menu/search panel HTML based the the current logged-in user permissions. Although
     * the generated menu/search panels are primarily used for jQuery Mobile, handling of the HTML
     * generation will be done with a Util function to facilitate any future use of another
     * framework.
     *
     * @access public
     * @return array The menu and search panel HTML in an assoc array:<br/></br>
     * <ul>
     * <li>navigation => The navigation menu HTML</li>
     * <li>search => The search panel HTML</li>
     * <ul>
     */
    public function generate() {
        if ( empty($this->user) ) {
            return array(
                'menu_items' => array(),
                'search_items' => array(),
            );
        }
        $core_modules = Module::get_core_modules(false);
        $admin_base_url = WEB_BASE.'/'.$this->App->config('admin_uri_segment');
        $is_super = ! empty($this->user['is_super']);
        $global_perm = $is_super ? Permission::PERMISSION_SUPER_USER : $this->user['global_perm'];
        $Perm = new Permission($global_perm);
        $menu_items = array();
        $search_items = array();
        $first_items = array();
        $last_items = array();
        $perms = array();
        if ( ! $is_super) {
            $relations = $this->Users->get_relations();
            $rel_data = $relations['modules']->get($this->user['user_id']);
            foreach ($rel_data as $rd) {
                $perms[ $rd['id'] ] = $rd['permission'];
            }
        }

        // home page
        $data = array(
            'label'     => __('Home'),
            'url'       => $admin_base_url.'/home',
            'keywords'  => self::$SEARCH_TERMS['home']
        );
        $menu_items[] = $data;
        $search_items[] = $data;

        $modules_data = array();
        $options_data = array();
        foreach ($this->modules as $module_name => $module_data) {
            $id = $module_data['id'];
            $Perm->set_val($global_perm);
            if ( isset($perms[$id]) ) {
                $Perm->merge($perms[$id]);
            }
            if ( $Perm->has_read() === false ) {
                continue;
            }

            $base_url = $admin_base_url.'/'.$module_data['slug'];
            $is_options = empty($module_data['use_model']);
            $uri = $is_options ? Module::UPDATE_URI : Module::LIST_URI;
            $keywords = $is_options ?
                        array( strtolower($module_data['label']) ) :
                        array( strtolower($module_data['label']), strtolower($module_data['label_plural']) );
            $terms = $is_options ? self::$SEARCH_TERMS['options'] : self::$SEARCH_TERMS['list'];
            $data = array(
                'label'     => $module_data['label_plural'],
                'url'       => $base_url.'/'.$uri,
                'keywords'  => array_merge($keywords, $terms)
            );

            if ($is_options) {
             // option module type
                $fields = array_keys($module_data['field_data']['defaults']);
                foreach ($fields  as &$field) {
                    $field = str_replace('_', ' ', $field);
                }
                $data['keywords'] = array_merge($data['keywords'], $fields);
                $options_data[] = $data;
                $search_items[] = $data;
            } else {
             // standard module
                if ($module_name === 'users') {
                 // Users last item in modules list
                    $last_items[] = $data;
                } else if ( in_array($module_name, $core_modules) ) {
                    // Other core modules first items in modules list
                    $first_items[] = $data;
                } else {
                    $modules_data[] = $data;
                }
                $search_items[] = $data;

                if ( ! empty($this->relations[$module_name]) ) {
                // Add 1:n relation links for relation to parent module
                    $keywords = $data['keywords'];
                    foreach ($this->relations[$module_name] as $relation) {
                        $rel_kw = array( strtolower($relation['label']), strtolower($relation['label_plural']) );
                        $data['label'] = $relation['label_plural'].' ('.$module_data['label_plural'].')';
                        $data['keywords'] = array_merge($keywords, $rel_kw, self::$SEARCH_TERMS['relation']);
                        $search_items[] = $data;
                    }
                }

                if ( ! empty($module_data['use_add']) && $Perm->has_add() ) {
                    $data = array(
                        'label'     => __('Add').' '.$module_data['label'],
                        'url'       => $base_url.'/'.Module::ADD_URI,
                        'keywords'  => array_merge($keywords, self::$SEARCH_TERMS['add'])
                    );
                    $search_items[] = $data;
                }
                if ( ! empty($module_data['use_sort']) && $Perm->has_update() ) {
                    $data = array(
                        'label'     => __('Arrange').' '.$module_data['label_plural'],
                        'url'       => $base_url.'/'.Module::SORT_URI,
                        'keywords'  => array_merge($keywords, self::$SEARCH_TERMS['sort'])
                    );
                    $search_items[] = $data;
                }
            }
        }

        $modules_data = array_merge($first_items, $modules_data, $last_items);
        if ( ! empty($modules_data) ) {
            $data = array(
                'label' => __('Modules'),
                'items' => $modules_data
            );
            $menu_items[] = $data;
        }

        if ( ! empty($options_data) ) {
            $data = array(
                'label' => __('Options'),
                'items' => $options_data
            );
            $menu_items[] = $data;
        }

        // Sample Page
        $data = array(
            'label' => __('Sample Page'),
            'url' => $admin_base_url.'/docs/sample',
            'keywords' => array('sample', 'page')
        );
        $menu_items[] = $data;

        // logout
        $data = array(
            'label' => __('Logout'),
            'url' => $admin_base_url.'/authenticate/logout',
            'keywords'  => self::$SEARCH_TERMS['logout']
        );
        $menu_items[] = $data;
        $search_items[] = $data;

        $navdata = array(
            'menu_items' => $menu_items,
            'search_items' => $search_items,
        );

        return nav_cms_menu($navdata);
    }
}

/* End of file AdminMenu.php */
/* Location: ./App/Html/Navigation/AdminMenu.php */