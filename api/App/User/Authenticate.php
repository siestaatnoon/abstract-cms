<?php

namespace App\User;

use
App\App,
App\Module\Module,
App\User\Permission;

/**
 * Authenticate class
 * 
 * Contains the logic for authenticating a CMS user from login and authorizing requests to individual
 * CMS pages and API calls within the CMS. In addition, provides methods to retrieve the current logged-in
 * user data (minus the password of course) and \App\User\Permission object containing the permission
 * data for the current request.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2016 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\User
 */
class Authenticate {
	
    /**
     * @var string The user session key
     */
	private static $SESSION_COOKIE = 'abs_session';
	
    /**
     * @var string The user session key
     */
	private static $SESSION_KEY = 'abs_user';

    /**
     * @var \App\App Instance of main App class
     */
	private $App;

    /**
     * @var array Assoc array of data from main "modules" module
     */
	private $modules;
	
    /**
     * @var string Cookie name holding CMS session
     */
	private $session_cookie;
	
    /**
     * @var \App\User\Session Session object
     */
	private $session;

    /**
     * @var \App\Module\Module_users Instance of Module_users class
     */
	private $Users;
	
	
	/**
	 * Constructor
	 *
	 * Initializes the Authenticate class with default values.
	 * 
	 * @access public
     * @throws \App\Exception\AppException if an error occurs while loading module, handled by \App\App class
	 */
	public function __construct() {
		$this->App = App::get_instance();
		$this->modules = Module::load();
		$this->Users = Module::load('users');
		$cookie = $this->App->config('session_cms_cookie');
		$this->session_cookie = empty($cookie) ? self::$SESSION_COOKIE : $cookie;
		$this->session = NULL;
	}


	/**
	 * authenticate
	 *
	 * Authenticates a given username/email and password to verify a user login. Upon
	 * successful authentication, a user session is generated and CRSF token created 
	 * to be used in further calls in the CMS. If lockout is enabled after a number
	 * of login attempts is reached, the number of seconds remaining in the lockout
	 * is returned.
	 * 
	 * 
	 * @access public
	 * @param string $user The username or email
	 * @param string $pass The password
	 * @param bool $is_remember True if user session is kept alive beyond default browser session
	 * @return mixed True or false if authenticated or the number of seconds a user is locked
	 * out if max login attempts reached
	 */
	public function authenticate($user, $pass, $is_remember=false) {
		if ( empty($user) || empty($pass) ) {
			return false;
		}
		
		$timeout = $is_remember ? $this->App->config('session_cms_max_time') : 0;
		$this->session = $this->App->session($this->session_cookie, $timeout);
		$ip = $this->session->get_ip();
        if ( $this->is_locked_out($ip) ) {
            return $this->set_attempt($ip);
        }

        $user = $this->Users->get_model()->authenticate($user, $pass);
		$return = false;
		
		if ($user === false) {
		//auth failed, record timeout
			$return = $this->set_attempt($ip);
		} else {
		//clear login attempts
			$this->clear_attempt($ip);
			
 			//add CRSF token to user data
 			$crsf = $this->App->get_csrf();
 			$crsf_key = $crsf->get_key();
 			$user[$crsf_key] = $crsf->get_token();

 			//save user data to session
 			$this->session->session_start();
 			$user['ip'] = $ip;	//add IP to user data
 			$this->session->set_data(self::$SESSION_KEY, $user);
 			$return = true;
		}

		return $return;
	}


	/**
	 * authorize
	 *
	 * Authorizes a page request in the CMS or a request to the API. Checks for
	 * valid CRSF token and validates a user's permission for the request.
	 * 
	 * @access public
	 * @param string $module_name The module name (slug)
	 * @param string $method The request method, one of GET, POST, PUT or DELETE
	 * @return bool True if user authorized for request
	 */
	public function authorize($module_name, $method) {
		if ( empty($module_name) || empty($method) ) {
			return false;
		}

		$this->session = $this->get_session();
		$crsf = $this->App->get_csrf();
		$user = $this->session->get_data(self::$SESSION_KEY);
		$crsf_key = $crsf->get_key();
		$is_auth = false;

		if (  empty($user) || empty($user[$crsf_key]) || $crsf->is_valid($user[$crsf_key]) === false ) {
		//session user data does not exist or missing or 
		//invalid CRSF token so invalidate session
			$this->session->session_destroy();
			$crsf->invalidate();
		} else if ($user['is_super']) {
		//super user, no further checks needed
			$is_auth = true;
		} else {
			$Perm = $this->get_permission($module_name);
			if ( ! empty($Perm) ) {
				$is_auth = $this->has_permission($Perm, $method);
			}
		}

		return $is_auth;
	}
	

	/**
	 * get_permission
	 *
	 * Returns an \App\User\Permission object with the logged-in user's permission 
	 * data for the current request.
	 * 
	 * @access public
	 * @param string $module_name The module name (slug)
	 * @return mixed The \App\User\Permission object or NULL if user or module name param invalid
     * @throws \App\Exception\AppException if an error occurs while loading module, handled by \App\App class
	 */
	public function get_permission($module_name) {
		$module = $this->modules->get_module_data($module_name);
		$user = $this->get_user_data();
		$Perm = NULL;
		
		if ( ! empty($module) && ! empty($user) ) {
			$permission = 0;
			if ($user['is_super']) {
				$permission = Permission::PERMISSION_SUPER_USER;
			} else {
				$md = $this->modules->get_module_data();
				$pk_field = $md['pk_field'];
				$pk = $module[$pk_field];
				$relation = $this->Users->get_relations('modules');
				$permission = $relation->get_user_perm($user['user_id'], $pk);
			}
			
			$Perm = new Permission($permission);
	
			//merge user global permissions
			if ( ! $user['is_super'] && ! empty($user['global_perm']) ) {
				$Perm->merge($user['global_perm']);
			}
		}
		
		return $Perm;
	}
	

	/**
	 * get_session
	 *
	 * Returns the CMS user Session object.
	 * 
	 * @access public
	 * @return \App\User\Session The Session object
	 */
	public function get_session() {
		if ( empty($this->session) ) {
			$this->session = $this->App->session($this->session_cookie);
		}
		return $this->session;
	}
	

	/**
	 * get_user_data
	 *
	 * Returns the CMS user's data from the session.
	 * 
	 * @access public
	 * @param string $index The index of the data to retrieve, if left empty retrieves all data
	 * @return mixed The CMS user data or NULL if the user or data index does not exist
	 */
	public function get_user_data($index='') {
		$this->session = $this->get_session();
		$user = $this->session->get_data(self::$SESSION_KEY);
		$data = NULL;
		
		if ( ! empty($user) ) {
			if ( empty($index) ) {
				$data = $user;
			} else if ( isset($user[$index]) ) {
				$data = $user[$index];
			}
		}
		
		return $data;
	}


    /**
     * invalidate
     *
     * Destroys a CMS user session and CRSF token.
     *
     * @access public
     * @return void
     */
    public function invalidate() {
        $this->session = $this->get_session();
        $this->session->session_destroy();
        $this->App->get_csrf()->invalidate();
    }


    /**
     * is_logged_in
     *
     * Checks if a user is currently logged in.
     *
     * @access public
     * @return bool True if user currently logged in
     */
    public function is_logged_in() {
        $this->session = $this->get_session();
        $user = $this->session->get_data(self::$SESSION_KEY);
        return ! empty($user);
    }


    /**
     * clear_attempt
     *
     * Clears a user's login attempt.
     *
     * @access private
     * @param  string $ip The user IP address
     * @return bool True if operation successful
     */
    private function clear_attempt($ip) {
        return $this->Users->get_model()->clear_login_attempt($ip);
    }


    /**
     * has_permission
     *
     * Checks a CMS user's permission to a request call.
     *
     * @access private
     * @param \App\User\Permission $Perm The Permission object
     * @param string $method The request method, one of GET, POST, PUT or DELETE
     * @return bool True if CMS user has permission for request
     */
    private function has_permission($Perm, $method) {
        if ( empty($Perm) || empty($method) ) {
            return false;
        } else if ( $Perm->has_all() ) {
            return true;
        }

        $has_perm = false;
        $method = strtoupper($method);
        switch($method) {
            case 'GET' :
                $has_perm = $Perm->has_read();
                break;
            case 'POST' :
                $has_perm = $Perm->has_add();
                break;
            case 'PUT' :
                $has_perm = $Perm->has_update();
                break;
            case 'DELETE' :
                $has_perm = $Perm->has_delete();
                break;
        }

        return $has_perm;
    }


    /**
     * is_locked_out
     *
     * Checks if user is locked out due to too many login attempts.
     *
     * @access private
     * @param  string $ip The user IP address
     * @return bool True if locked out or false if lockout disabled or not locked out
     */
    private function is_locked_out($ip) {
        $login_attempts = $this->App->config('login_max_attempts');
        $login_timeout = $this->App->config('login_timeout_lock');
        if ( empty($ip) || empty($login_attempts) || empty($login_timeout) ) {
            return false;
        }

        $attempt = $this->Users->get_model()->get_login_attempt($ip);
        return ! empty($attempt) && $attempt['attempts'] >= $login_attempts;
    }
	
	
	/**
	 * set_attempt
	 *
	 * Adds a user login attempt and returns the lockout timeout in seconds if max attempts reached.
	 *
     * @access private
	 * @param  string $ip The user IP address
	 * @return mixed The lockout timeout in seconds or false if lockout disabled
	 */
	private function set_attempt($ip) {
		$login_attempts = $this->App->config('login_max_attempts');
		$login_timeout = $this->App->config('login_timeout_lock');
		if ( empty($ip) || empty($login_attempts) || empty($login_timeout) ) {
			return false;
		}

		$timeout = 0;
		$attempt = $this->Users->get_model()->set_login_attempt($ip);
		if ($attempt['attempts'] > $login_attempts) {
            $timeout = $attempt['login_time'] + $login_timeout - time();
		}

		return $timeout;
	}
}

/* End of file Authenticate.php */
/* Location: ./App/User/Authenticate.php */