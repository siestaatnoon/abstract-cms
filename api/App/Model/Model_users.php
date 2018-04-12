<?php
namespace App\Model;

use
App\App,
App\Model\Model,
App\Exception\AppException;

/**
 * Model_user class
 * 
 * Provides the database functions for a App\Module\Module object. Subclass of App\Model\Model,
 * provides additional user authentication functions.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Model
 */
class Model_users extends \App\Model\Model {
	
	/**
     * @const string Name of module table to retrieve permission names
     */
	const USERS_TABLE_SUPER = 'users_super';
	
	/**
     * @const string Name of module table to retrieve permission names
     */
	const USERS_TABLE_LOGIN_ATTEMPTS = 'abstract_login_attempts';

    /**
     * @var \App\App Instance of App module
     */
    protected $App;
	
    /**
     * @var string String used to salt a password hash
     */
	protected $pass_salt;
	
    /**
     * @var string Table name used by super users, prefix added
     */
	protected $table_super;
	
    /**
     * @var string Table name used to record login attempts by users
     */
	protected $table_login_attempts;


    /**
     * Constructor
     *
     * Initializes the Model_pages model.
     *
     * @access public
     * @param array $config The model configuration array
     * @throws \App\Exception\AppException if $config assoc array missing required parameters
     * @see Model::__construct() for model configuration parameters
     */
    public function __construct($config) {
        parent::__construct($config);
        $this->App = App::get_instance();
        $this->pass_salt = $this->App->config('pass_hash_salt');
        $this->table_super = $this->table_prefix.self::USERS_TABLE_SUPER;
        $this->table_login_attempts = $this->table_prefix.self::USERS_TABLE_LOGIN_ATTEMPTS;
    }
	

	/**
	 * authenticate
	 *
	 * Authenticates a user for the CMS admin from given username and password.
	 *
	 * @access public
	 * @param string $username The user username or email
	 * @param string $userpass The user password
	 * @return array The user data as assoc array if authenticated, false if not
	 */
	public function authenticate($username, $userpass) {
		if ( empty($username) || empty($userpass) ) {
			return false;
		}
		
		$is_email = false;
		if ( filter_var($username, FILTER_VALIDATE_EMAIL) ) {
			$username = strtolower( trim($username) );
			$is_email = true;
		}
		
		$pass_hash = $this->pass_salt.$userpass;
		$user = false;

		//first check for super user
		$query = "SELECT first_name, last_name, username, email ";
		$query .= "FROM ".$this->db->escape_identifier($this->table_super)." WHERE ";
		$query .= ($is_email ? "email" : "username")."=BINARY(".$this->db->escape($username).") ";
		$query .= "AND userpass=MD5(".$this->db->escape($pass_hash).") AND is_active='1' LIMIT 1";
		$result = $this->db->query($query);
		if ($result->num_rows() > 0) {
			$rows = $result->result_assoc();
			$user = $rows[0];
			$user['is_super'] = true;
		}
			
		if ($user === false) {
		//check user table
			$query = "SELECT user_id, first_name, last_name, username, email, global_perm ";
			$query .= "FROM ".$this->db->escape_identifier($this->table_name)." WHERE ";
			$query .= ($is_email ? "email" : "username")."=BINARY(".$this->db->escape($username).") ";
			$query .= "AND userpass=MD5(".$this->db->escape($pass_hash).") AND is_active='1'";
			$result = $this->db->query($query);
			if ($result->num_rows() > 0) {
				$rows = $result->result_assoc();
				$user = $rows[0];
				$user['is_super'] = false;
			}
		}

		return $user;
	}
	
	
	/**
	 * clear_login_attempt
	 *
	 * Clears a user login attempts by IP.
	 *
	 * @access public
	 * @param string $ip The user IP address
	 * @return bool True if successful
	 */
	public function clear_login_attempt($ip) {
		if ( empty($ip) ) {
			return false;
		}
		
		$query = "DELETE FROM ".$this->db->escape_identifier($this->table_login_attempts)." ";
		$query .= "WHERE `ip_address`=".$this->db->escape($ip);
		$result = $this->db->query($query);
		return is_numeric($result);
	}
	
	
	/**
	 * email_exists
	 *
	 * Checks if a given email is in use by a user or super user.
	 *
	 * @param string $email The email address
	 * @param int $user_id The user id to skip in search (otherwise will return false positive)
	 * @return bool True if email in use, false if not
	 */
	public function email_exists($email, $user_id=0) {
		if ( empty($email) ) {
			return false;
		}

		$has_email = false;
		$email = strtolower( trim($email) );
		$query = "SELECT COUNT(*) AS count FROM ".$this->db->escape_identifier($this->table_name)." ";
		$query .= "WHERE email=BINARY(".$this->db->escape($email).")";
		if ( ! empty($user_id) ) {
			$query .= " AND user_id!=".$this->db->escape($user_id);
		}
		$result = $this->db->query($query);
		$rows = $result->result_assoc();
        $has_email = $rows[0]['count'] > 0;

        if ( ! $has_email) {
            $query = "SELECT COUNT(*) AS count FROM " . $this->db->escape_identifier($this->table_super) . " ";
            $query .= "WHERE email=BINARY(" . $this->db->escape($email) . ")";
            $result = $this->db->query($query);
            $rows = $result->result_assoc();
            $has_email = $rows[0]['count'] > 0;
        }
        return $has_email;
	}
	

	/**
	 * get
	 *
	 * Retrieves a user from a given id. Calls parent::get([ID], false).
	 *
	 * @access public
	 * @param mixed $id The row id or slug
	 * @param bool $is_slug Not required and not passed to overwritten parent method
	 * @return mixed The associative array for the row or false if row not found
	 */
	public function get($id, $is_slug=false) {
		$row = parent::get($id, false);
		if ( is_array($row) && isset($row['userpass']) ) {
			$row['userpass'] = '';
		}
		return $row;
	}


    /**
     * get_login_attempt
     *
     * Returns a login attempt row, if set, from the db.
     *
     * @access public
     * @param string $ip The user IP address
     * @param int $login_time The login time, if empty current time
     * @return mixed The login attempt row by user IP or false if no row exists or lockout is disabled
     */
    public function get_login_attempt($ip, $login_time=0) {
        $login_attempts = $this->App->config('login_max_attempts');
        $login_timeout = $this->App->config('login_timeout_lock');
        if ( empty($ip) || empty($login_attempts) || empty($login_timeout) ) {
            return false;
        }

        if ( empty($login_time) ) {
            $login_time = time();
        }
        $row = false;

        $expire_time = $login_time - $login_timeout;
        $query = "SELECT * FROM ".$this->db->escape_identifier($this->table_login_attempts)." ";
        $query .= "WHERE `ip_address`=".$this->db->escape($ip)." ";
        $query .= "AND `login_time`>=".$this->db->escape($expire_time);
        $result = $this->db->query($query);
        if ($result->num_rows() > 0) {
            $rows = $result->result_assoc();
            $row = $rows[0];
        }

        return $row;
    }
	
	
	/**
	 * insert
	 *
	 * Inserts a row with the given array of fields and corresponding values. Overwrites parent
     * method to hash the password.
	 *
	 * @access public
	 * @param array $data The array of fields and corresponding values
	 * @return bool True if insert was successful, false if insert unsuccessful
	 * or if username or email already exists for a user
	 */
	public function insert($data) {
		if ( empty($data) || 
			$this->is_user($data['username']) === true || 
			$this->email_exists($data['email']) === true) {
			return false;
		}
		
		$pass_hash = $this->pass_salt.$data['userpass'];
		$data['userpass'] = md5($pass_hash);
		$data['email'] = strtolower( trim($data['email']) );
		return parent::insert($data);
	}
	

	/**
	 * is_super
	 *
	 * Checks if a given username is a super user.
	 *
	 * @param string $username The username
	 * @return bool True if super user admin, false if not
	 */
	public function is_super($username) {
		if ( empty($username) ) {
			return false;
		}

		$query = "SELECT COUNT(*) AS count ";
		$query .= "FROM ".$this->db->escape_identifier($this->table_super)." ";
		$query .= "WHERE username=BINARY(".$this->db->escape($username).")";
		$result = $this->db->query($query);
		$rows = $result->result_assoc();
		return $rows[0]['count'] > 0;
	}
	

	/**
	 * is_user
	 *
	 * Checks if a given username is in use.
	 *
	 * @param string $username The username
	 * @param int $user_id The user id to skip in search (otherwise may return false positive)
	 * @return bool True if user exists, false if not
	 */
	public function is_user($username, $user_id=0) {
		if ( empty($username) ) {
			return false;
		}
		
		$is_user = false;
		if ( $this->is_super($username) === false) {
		//check user table
			$query = "SELECT COUNT(*) AS count FROM ".$this->db->escape_identifier($this->table_name)." ";
			$query .= "WHERE username=BINARY(".$this->db->escape($username).")";
			if ( ! empty($user_id) ) {
				$query .= " AND user_id!=".$this->db->escape($user_id);
			}
			$result = $this->db->query($query);
			$rows = $result->result_assoc();
			$is_user = $rows[0]['count'] > 0;
		}
	
		return $is_user;
	}
	
	
	/**
	 * parse_result
	 *
	 * Accepts a result set and parses the row or rows to exclude data,
	 * not pertaining to model, from each row.
	 *
	 * @access public
	 * @param \App\Database\Result $result Driver dependant query result set
	 * @param bool $is_single_row True if query returns a single row
	 * @return array The row or array of rows, assoc array parsed
	 */
	public function parse_result($result, $is_single_row=false) {
		if ( is_subclass_of($result, '\App\Database\Driver\Result') === false) {
			return $result;
		}
		
		$rows = array();
		if ( $result->num_rows() > 0) {
			$rows = $result->result_assoc();
			foreach ($rows as &$row) {
				parent::unserialize_row($row);
			}
		}
		
		return $is_single_row && isset($rows[0]) ? $rows[0] : $rows;
	}
	
	
	/**
	 * set_login_attempt
	 *
	 * Records the login time and numer of user login attempts for a given user IP,
	 * if enabled, and returns an assoc array of:<br/><br/>
	 * <ul>
	 * <li>attempts => Number of user login attempts</li>
	 * <li>login_time => Time of user login in seconds</li>
	 * </ul><br/><br/>
	 * Or false is returned if disabled.
	 * 
	 * @access public
	 * @param string $ip The user IP address.
	 * @return mixed Assoc array of login time and number of login attempts or false
	 * if lockout is disabled
	 */
	public function set_login_attempt($ip) {
		$login_attempts = $this->App->config('login_max_attempts');
		$login_timeout = $this->App->config('login_timeout_lock');
		if ( empty($ip) || empty($login_attempts) || empty($login_timeout) ) {
			return false;
		}
		
		$id = false;
		$attempts = 1;
		$login_time = time();
		$last_login_time = 0;
		
		// First gc old attempts
		$this->gc_login_attempts();

        $row = $this->get_login_attempt($ip, $login_time);
        if ( ! empty($row) ) {
            $id = $row['id'];
            $attempts = $row['attempts'] + 1;
            $last_login_time = $row['login_time'];
        }
		
		if ($attempts === 1) {
			$query = "INSERT INTO ".$this->db->escape_identifier($this->table_login_attempts)." ";
			$query .= "(`login_time`, `ip_address`, `attempts`) VALUES(";
			$query .= $this->db->escape($login_time).", ";
			$query .= $this->db->escape($ip).", ";
			$query .= $this->db->escape($attempts).")";
			$this->db->query($query);
		} else if ($attempts <= $login_attempts) {
			$query = "UPDATE ".$this->db->escape_identifier($this->table_login_attempts)." SET ";
			$query .= "`login_time`=".$this->db->escape($login_time).", ";
			$query .= "`attempts`=".$this->db->escape($attempts)." ";
			$query .= "WHERE `id`=".$this->db->escape($id);
			$this->db->query($query);
		}
		
		$data['attempts'] = $attempts;
		$data['login_time'] = $attempts <= $login_attempts ? $login_time : $last_login_time;
		
		return $data;
	}
	
	
	/**
	 * update
	 *
	 * Updates a row with the given array of fields and corresponding values. Overwrites
     * parent method to first check for duplicate email and return false if found.
	 *
	 * @access public
	 * @param array $data The assoc array of user fields and values
	 * @return bool True if update was successful, false if update unsuccessful
	 * or if username or email already exists for a user
	 */
	public function update($data) {
		$user_id = empty($data['user_id']) ? 0 : $data['user_id'];
		$username = empty($data['username']) ? '' : $data['username'];
		$email = empty($data['email']) ? '' : $data['email'];
		
		if ( empty($data) || 
			$this->is_user($username, $user_id) === true ||
			$this->email_exists($email, $user_id) === true) {
			return false;
		}

		$data['email'] = strtolower( trim($data['email']) );
		if ( ! empty($data['userpass']) ) {
			$pass_hash = $this->pass_salt.$data['userpass'];
			$data['userpass'] = md5($pass_hash);
		}

		return parent::update($data);
	}
	
	
	/**
	 * gc_login_attempts
	 *
	 * Performs garbage collection on expired login attempts saved in the database. The
	 * frequency is determined by the PHP session gc configuration settings.
	 *
	 * @access protected
	 * @return void
	 */
	protected function gc_login_attempts() {
		$login_timeout = $this->App->config('login_timeout_lock');
		if ( empty($login_timeout) ) {
			return;
		}
		
		$gc_prob = ini_get('session.gc_probability');
		$gc_div = ini_get('session.gc_divisor');
		if ( ! is_numeric($gc_prob) ) {
			$gc_prob = 5;
		}
		if ( ! is_numeric($gc_div) ) {
			$gc_div = 100;
		}
		
		if ( ($gc_prob / $gc_div) >= (rand(1, 100) / 100) ) {
			$expire_time = time() - $login_timeout;
			$query = "DELETE FROM ".$this->db->escape_identifier($this->table_login_attempts)." ";
			$query .= "WHERE `login_time`<".$this->db->escape($expire_time);
			$this->db->query($query);
		}
	}

}

/* End of file Model_users.php */
/* Location: ./App/Model/Model_users.php */