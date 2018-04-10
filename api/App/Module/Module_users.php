<?php

namespace App\Module;

use
App\User\Permission,
App\Exception\AppException;

/**
 * Module_users class
 * 
 * Subclass of \App\Module\Abstract_module, this class provides custom definitions for the Users module.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Module
 */
class Module_users extends \App\Module\Abstract_module {

    /**
     * @var array Permission values used in admin form
     *
     */
    protected static $FIELD_OPTIONS = array(
        'r' => 'Read',
        'u' => 'Update',
        'a' => 'Add',
        'd' => 'Delete'
    );
	
	
	/**
	 * Constructor
	 *
	 * Initializes the Users module.
	 * 
	 * @access public
     * @throws \App\Exception\AppException if an error occurs while loading module, rethrown and
     * handled by \App\App class
	 */
	public function __construct() {
        try {
            parent::__construct('users');
        } catch (AppException $e) {
            throw $e;
        }
	}


    /**
     * admin_func_check_user
     *
     * Function called by AJAX from CMS form which checks if a username or email is already in use.
     *
     * @access public
     * @param array $params Parameters passed in from AJAX (unused)
     * @param array $vars The GET/POST vars passed in with AJAX call
     * @return int One if username/email not in use or zero if in use
     */
    public function admin_func_check_user($params, $vars) {
        if ( ! empty($vars['username']) || ! empty($vars['email']) ) {
            $is_available = 1;
            $user_id = empty($vars['user_id']) ? false : $vars['user_id'];
            if ( ! empty($vars['username']) ) {
                $username = $vars['username'];
                if ( $this->model->is_user($username, $user_id) || $this->model->is_super($username) ) {
                    $is_available = 0;
                }
            } else {
                $email = trim( strtolower($vars['email']) );
                if ( $this->model->email_exists($email, $user_id) ) {
                    $is_available = 0;
                }
            }
            return $is_available;
        }
    }
	

	/**
	 * form_field_global_perm
	 *
	 * Creates the custom form field HTML for the global permissions field for a user. The
	 * field is a jQuery multiselect which converts permission data into a JSON string saved
	 * to a submitted form field.
	 * 
	 * @access public
	 * @param mixed $user_id The user ID (int) or empty if not used
	 * @param mixed $value The value of the field
	 * @param \App\User\Permission $permission The current CMS user Permission object
	 * @return string The global permissions form field HTML
     * @throws \App\Exception\AppException if $permission parameter invalid, handled by \App\App class
	 */
	public function form_field_global_perm($user_id, $value, $permission, $params=array()) {
		if ($permission instanceof \App\User\Permission === false ) {
            $msg_part = error_str('error.param.type', array('$permission', '\\App\\User\\Permission'));
            $message = error_str('error.type.param.invalid', $msg_part);
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$field_name = 'global_perm';
		$user = $this->model->get($user_id);
		$global_perm = empty($user) ? Permission::to_binary(0) : Permission::to_binary($user[$field_name]);
		$params = array(
			'name' => $field_name,
			'value' => $global_perm,
            'use_template_vars' => false
		);

        $field_values = $this->get_perm_field_values($global_perm);
        $html = form_hidden($params);
		$html .= $this->field_permissions($field_name, $field_name, 'Global Permissions', $field_values, false, false, $permission);
		return $html;
	}
	

	/**
	 * form_field_modules
	 *
	 * Creates the custom form field HTML for the module permissions fields for a user. Each
	 * field is a jQuery multiselect, for each module, which converts permission data into a
	 * JSON string savedto a submitted hidden form field.
	 * 
	 * @access public
	 * @param mixed $user_id The user ID (int) or empty if not used
	 * @param mixed $value The value of the field
	 * @param \App\User\Permission $permission The current CMS user Permission object
	 * @return string The module permissions form fields HTML
     * @throws \App\Exception\AppException if $permission parameter invalid, handled by \App\App class
	 */
	public function form_field_modules($user_id, $value, $permission, $params=array()) {
		if ($permission instanceof \App\User\Permission === false ) {
            $msg_part = error_str('error.param.type', array('$permission', '\\App\\User\\Permission'));
            $message = error_str('error.type.param.invalid', $msg_part);
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$rel = $this->get_relations();
		$rel_data = $rel['modules']->get($user_id);
		$modules = self::$MODULES;
        ksort($modules);
		$user_perms = array();
		$perms = array();
		$no_perm_bin = Permission::to_binary(0);

        if ( ! empty($rel_data) ) {
            foreach ($rel_data as $rd) {
                foreach ($modules as $name => $mod) {
                    if ($mod['id'] === $rd['id']) {
                        $user_perms[$name] = Permission::to_binary($rd['permission']);
                    }
                }
            }
        }
		
		$html = '';
		foreach ($modules as $name => $mod) {
		    $perm = array_key_exists($name, $user_perms) ? $user_perms[$name] : $no_perm_bin;
			$perms[$name] = $perm;
            $field_values = $this->get_perm_field_values($perm);
            $is_options = empty($mod['use_model']);
			$html .= $this->field_permissions('modules', $name, $mod['label_plural'], $field_values, true, $is_options, $permission);
		}
		
		$json = json_encode($perms);
		$params = array(
			'name' => 'modules[]',
			'value' => $json,
            'is_json' => true,
            'use_template_vars' => false
		);
		
		return form_hidden($params).$html;
	}
	
	
	/**
	 * module_form_data_to_row
	 *
	 * Converts CMS form data to an assoc array to INSERT/UPDATE a module table row. Removes
	 * any indexes that are not a module field and adds any fields with a default value if
	 * missing. Overwritten from parent class.
	 * 
	 * @access protected
	 * @param array $data The form field data from CMS form
	 * @return array The data converted to an assoc array for form_fields table row
	 */
	protected function module_form_data_to_row($data) {
		if ( empty($data) ) {
			return $data;
		}

		// make emails lowercase and trimmed
        $data['email'] = trim( strtolower($data['email']) );

        $data['global_perm'] = Permission::to_decimal($data['global_perm']);
		if ( isset($data['modules']) ) {
		    $modules = self::$MODULES;
            $module_perms = is_array($data['modules']) && isset($data['modules'][0]) ? $data['modules'][0] : $data['modules'];
            $indep_ids = array();
            $args = array();
            foreach ($module_perms as $name => $perm) {
                if ( isset($modules[$name]) ) {
                    $id = $modules[$name]['id'];
                    $indep_ids[] = $id;
                    $args[$id] = Permission::to_decimal($perm);
                }
            }
            $data['modules'] = $indep_ids;
            $data['modules_args'] = $args;
        }

		return $data;
	}
	
	
	/**
	 * row_to_module_form_data
	 *
	 * Converts a module table row into an assoc array of data used for the CMS form 
	 * to add or update a module object. Overwritten from parent class.
	 * 
	 * @access protected
	 * @param array $data A module row
	 * @return array The row converted to CMS form data
	 */
	protected function row_to_module_form_data($data) {
		if ( empty($data) ) {
			return $data;
		}

        $data['global_perm'] = Permission::to_binary($data['global_perm']);

        //TODO: convert module permissions to field format
		
		return $data;
	}
	
	
	/**
	 * validate
	 *
	 * Checks the given assoc array of form data for necessary indeces. Overwritten from parent class.
	 * 
	 * @access protected
	 * @param array $data The form field form data as an assoc array
	 * @param boolean $has_id True if $data param contains a non-empty id for the form field row
	 * @return mixed True if row data validated or an array of validation errors
	 */
	protected function validate($data, $has_id=false) {
		$errors = array();
		return empty($errors) ? true : $errors;
	}
	

	/**
	 * field_permissions
	 *
	 * Creates a multiselect form field for a global or module permission. 
	 * 
	 * @access private
     * @param string $field_name The field name of input storing permission
	 * @param string $perm_name The permission (module) name
	 * @param string $label The display name or the permission
     * @param array $field_values The permission value, converted to form field values
     * @param bool $is_object True if input field stores JSON object
     * @param bool $is_options True if module permission and module is of options type
	 * @param \App\User\Permission $permission The current CMS user Permission object
	 * @return string The multiselect HTML
	 */
	private function field_permissions($field_name, $perm_name, $label, $field_values, $is_object, $is_options, $permission) {
	    $values = self::$FIELD_OPTIONS;
        if ($is_options) {
            unset($values['a']);
            unset($values['d']);
        }

        $field = 'permissions-'.$perm_name;
        $params = array();
        if ($is_object) {
            $params['label'] = $label.($is_options ? ' (Options)' : '');
        }
        $params['name'] = $field;
		$params['values'] = $values;
        $params['value'] = $field_values;
		$params['placeholder'] = 'No permissions set';
		$params['is_multiple'] = true;
		$params['use_template_vars'] = false;
		$params['is_readonly'] = $permission->has_add() === false && $permission->has_update() === false;
		$params['attr']['id'] = $field;
		$params['attr']['class'] = 'permissions';
		$params['attr']['data-native-menu'] = 'false';
        $params['attr']['data-field'] = $field_name;
        $params['attr']['data-object'] = $is_object ? 1 : 0;
		return form_select($params);
	}


    /**
     * get_perm_field_values
     *
     * Returns the values for a multiselect form field given an int or binary permission.
     *
     * @access private
     * @param mixed $perm The permission value, int or binary string
     * @return array The permission form field values
     */
	private function get_perm_field_values($perm) {
        $vals = array();
        if ( empty($perm) || (int) $perm === 0 ) {
            return $vals;
        }

        $Perm = new Permission($perm);
        if ( $Perm->has_read() ) {
            $vals[] = 'r';
        }
        if ( $Perm->has_add() ) {
            $vals[] = 'a';
        }
        if ( $Perm->has_update() ) {
            $vals[] = 'u';
        }
        if ( $Perm->has_delete() ) {
            $vals[] = 'd';
        }

        return $vals;
    }
}

/* End of file Module_users.php */
/* Location: ./App/Module/Module_users.php */