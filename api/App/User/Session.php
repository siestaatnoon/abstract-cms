<?php

namespace App\User;

use 
App\App,
App\Database\Database;

/**
 * Session class
 * 
 * This class is an alternative to PHP sessions utilizing a database to store session data. This
 * will try to use the PHP configurations for session lifetime (timeout) and garbage collection
 * to minimize additional configurations.<br/><br/>
 * Upon retrieving a singleton instance of this class (with Session::get_instance()), and calling 
 * session_start() once, a user session will be initialized and saved to the database and identified
 * upon successive requests with a session cookie.<br/><br/>
 * Note that a session can be "kept alive" on successive request by saving data using the get_data()
 * or set_data() functions or deleting data with unset_data() or by calling touch() which updates the
 * time of last activity.<br/><br/>
* NOTE: If $sess_timeout param in constructor greater than one day then calls to $this->get_data(),
* $this->set_data(), this->touch() and $this->unset_data() will not update time of last activity.
 * 
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @license     http://www.projectabstractcms.com/license
 * @version     0.1.0
 * @package		App
 */
class Session {

    /**
     * @var string Method used for encrypting session data
     */
    private static $ENCRYPT_METHOD = 'aes-256-cbc';

    /**
     * @var \App\Session Singleton instance of this class
     */
	protected static $instance = NULL;

    /**
     * @var string The default name of the cookie holding the encrypted session id
     */
	protected static $default_cookie = 'abs_session';
	
    /**
     * @var string The default session time
     */
	protected static $default_timeout= 3600;
	
    /**
     * @var array Container to keep track of session IDs
     */
	protected static $sessions = array();
	
    /**
     * @var string Session cookie timeout in seconds
     */
	protected $cookie_timeout;

    /**
     * @var \App\Database\Database The database connection
     */
	protected $db = NULL;

    /**
     * @var bool True to use App or PHP default session timeout, false for custom timeout which
     * corresponds to session cookie expiration; note that last activity is not updated if true
     */
	protected $is_default_timeout = false;

    /**
     * @var boolean True if current session is new
     */
	protected $is_new = false;

    /**
     * @var boolean True if current session is updated and is to be saved
     */
	protected $is_writable = false;

    /**
     * @var boolean True if a saved session has been found and/or session is activated
     */
	protected $has_session = false;

    /**
     * @var int Current time used to determine last activity
     */
	protected $now;
	
    /**
     * @var string The name of the cookie holding the encrypted session id
     */
	protected $sess_cookie;

    /**
     * @var array Data storage for current session
     */
	protected $sess_data;

    /**
     * @var string Added to session id and resulting MD5 hash to store in session cookie
     */
	protected $sess_hash_salt = '';

    /**
     * @var string Table storing session data
     */
	protected $sess_table = 'sessions';

    /**
     * @var int Session length in seconds
     */
	protected $sess_timeout;

    /**
     * @var array The storage for session user data
     */
	protected $sess_user;


	/**
	 * Constructor
	 *
	 * Initializes the Session class singleton object. Retrieves a session from the database,
	 * if found, and performs garbage collection on expired sessions remaining in the database.
	 * 
	 * @access private
	 * @param string $sess_cookie Optional name of the cookie holding the session ID
	 * @param int $sess_timeout Session lifetime in seconds, zero or false for default
     * @throws \App\Exception\AppException if an error occurs while loading module, handled by \App\App class
	 */
	private function __construct($sess_cookie='', $sess_timeout=0) {
		$App = App::get_instance();
		$this->now = time();
		
		$db_config = $App->config('db_config');
		$this->db = Database::connection($db_config);

		$sess_hash_salt = $App->config('session_hash_salt');
		$this->sess_hash_salt = empty($sess_hash_salt) ? uniqid( mt_rand(), true) : $sess_hash_salt;
		
		$table_prefix = $App->config('db_table_prefix');
		if ( ! empty($table_prefix) ) {
			$this->sess_table = $table_prefix.$this->sess_table;
		}
		
		$this->session_init($sess_cookie, $sess_timeout);

        //gc old sessions
        $this->gc();
	}
	

	/**
	 * Destructor
	 *
	 * Closes the database connection upon script exit. 
	 * 
	 * @access public
	 */
	public function __destruct() {
		$this->db->close();
	}
	

	/**
	* get_data
	* 
	* Returns session data by index.
	*
	* @access public
	* @param string $name The name (index) of session data
	* @return mixed The session data
	*/
	public function get_data($name) {
		$this->touch();
		return $this->has_session && isset($this->sess_data[$name]) ? $this->sess_data[$name] : false;
	}
	

	/**
	* get_instance
	* 
	* Returns a singleton instance of this Session class.<br/><br/>
	* NOTE: If $sess_timeout param greater than one day then calls to $this->get_data(),
	* $this->set_data(), this->touch() and $this->unset_data() will not reset the
	* session countdown.
	*
	* @access public
	* @param string $sess_cookie Optional name of the cookie holding the session ID
	* @param int $sess_timeout Session lifetime in seconds, zero or false for default
	* @return \App\Session The singleton instance
    * @throws \App\Exception\AppException if an error occurs while loading module, handled by \App\App class
	*/
	public static function get_instance($sess_cookie='', $sess_timeout=0) {
		if ( empty(self::$instance) ) {
			self::$instance = new \App\User\Session($sess_cookie, $sess_timeout);
		} else if ( ! isset(self::$sessions[$sess_cookie]) ) {
            self::$instance->session_init($sess_cookie, $sess_timeout);
		}
		return self::$instance;
	}
	
	
	/**
	 * get_ip
	 *
	 * Returns the user ip address.
	 *
	 * @access public
	 * @return string The ip address
	 */
	public function get_ip() {
		$ip = '127.0.0.1';
		if ( ! empty($_SERVER['REMOTE_ADDR']) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else if ( ! empty($_SERVER['HTTP_CLIENT_IP']) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} else if ( ! empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		return $ip;
	}
	

	/**
	 * has_session
	 *
	 * Returns true if a session is found and active and/or updates have been
	 * made to the current session. 
	 *
	 * @access public
	 * @return boolean True if session is active
	 */
	public function has_session() {
		return $this->has_session;
	}
	

	/**
	 * session_destroy
	 *
	 * Ends the current session by deleting the database row and session cookie. 
	 *
	 * @access public
	 * @return void
	 */
	public function session_destroy() {
		$cookie_val = isset($_COOKIE[$this->sess_cookie]) ? $_COOKIE[$this->sess_cookie] : false;
	
		if ( ! empty($cookie_val) ) {
		//delete session from db
			$query = "DELETE FROM ".$this->db->escape_identifier($this->sess_table)." ";
			$query .= "WHERE MD5( CONCAT(`session_id`, ".$this->db->escape($this->sess_hash_salt).") )=";
			$query .= $this->db->escape($cookie_val);
			$this->db->query($query);

			//remove from session storage
            unset(self::$sessions[$this->sess_cookie]);
		
			//destroy session cookie
			setcookie($this->sess_cookie, '', 0, '/');
			unset($_COOKIE[$this->sess_cookie]);
		}

		//prevents saving the current session
		$this->is_writable = false; 
	}


	/**
	 * session_poll
	 *
	 * Returns the time left in seconds of the current session. 
	 *
	 * @access public
	 * @return int The time left in the session
	 */
	public function session_poll() {
		$time_left = $this->sess_timeout - ($this->now - $this->sess_user['last_activity']);
		return $this->has_session && $time_left > 0 ? $time_left : 0;
	}
	

	/**
	 * session_start
	 *
	 * Must be exlicitly called to start a new user session. 
	 *
	 * @access public
	 * @return void
	 */
	public function session_start() {
		//set session coookie
		$cookie_data = md5($this->sess_user['session_id'].$this->sess_hash_salt);
		setcookie($this->sess_cookie, $cookie_data, $this->cookie_timeout, '/');
		
		$this->has_session = true;
		$this->touch();
	}
	

	/**
	 * set_data
	 *
	 * Saves a name => value data to the session. All primitive
	 * data types are supported
	 *
	 * @access public
	 * @param string $name The name of the data
	 * @param mixed $data The value of the data
	 * @return boolean True if data saved, false if session is inactive or expired
	 */
	public function set_data($name, $data) {
		if ($this->has_session && strlen($name) > 0) {
			$this->sess_data[$name] = $data;
			$this->is_writable = true;
			$this->write();
		}
		return $this->has_session;
	}
	

	/**
	 * set_timeout
	 *
	 * Updates the session length in seconds. If parameter empty
	 * then will be set to default session timeout.
	 *
	 * @access public
	 * @param int $time The session time in seconds
	 * @return void
	 */
	public function set_timeout($time) {
		if ( empty($time) ) {
			$time = self::$default_timeout;
		}
		
		$this->sess_timeout = $time;
		$this->sess_user['timeout'] = $time;
		self::$sessions[$this->sess_cookie] = $time;
	}
	

	/**
	 * touch
	 *
	 * Updates the time of last activity of the current session. 
	 *
	 * @access public
	 * @return boolean True if successful, false if session is inactive or expired
	 */
	public function touch() {
		if ($this->has_session) {
			$this->is_writable = true;

            if ($this->is_default_timeout) {
            //update last activity time
                $this->db->query("LOCK TABLES ".$this->db->escape_identifier($this->sess_table)." WRITE;");
                $query = "UPDATE ".$this->db->escape_identifier($this->sess_table)." ";
                $query .= "SET `last_activity`=".$this->db->escape($this->now)." ";
                $query .= "WHERE `session_id`=".$this->db->escape($this->sess_user['session_id']);
                $this->db->query($query);
                $this->db->query("UNLOCK TABLES;");
                $this->sess_user['last_activity'] = $this->now;
            }
		}
		
		return $this->has_session;
	}
	

	/**
	 * unset_data
	 *
	 * Deletes named data from the session.
	 *
	 * @access public
	 * @param string $name The name of the data
	 * @return boolean True if data deleted, false if session is inactive or expired
	 */
	public function unset_data($name) {
		if ($this->has_session && strlen($name) > 0 && isset($this->sess_data[$name])) {
			unset($this->sess_data[$name]);
			$this->is_writable = true;
			$this->write();
		}
		return $this->has_session;
	}
	

	/**
	 * gc
	 *
	 * Performs garbage collection on expired sessions saved in the database.
	 *
	 * @access protected
	 * @return void
	 */
	protected function gc() {
        $query = "DELETE FROM ".$this->db->escape_identifier($this->sess_table)." ";
        $query .= "WHERE (`last_activity`+`timeout`)<".$this->db->escape($this->now);
        $this->db->query($query);
	}
	

	/**
	 * generate_sess_id
	 *
	 * Generates a session id. A random integer of at least 32 digits
	 * appended with the user ip address and SHA-1 hashed is generated.
	 *
	 * @access protected
	 * @return string The session id
	 */
	protected function generate_sess_id() {
		$sess_id = '';
		while (strlen($sess_id) < 32) {
			$sess_id .= mt_rand(0, mt_getrandmax());
		}

		$sess_id = uniqid($sess_id, true).$this->get_ip();
		return substr( sha1($sess_id), 0, 32);
	}
	

	/**
	 * get_default_timeout
	 *
	 * Returns the default session timeout according set in config and, if not,
	 * from PHP settings or, if not accessible, defaults to default of this class.
	 *
	 * @access public
	 * @return int The default session timeout
	 */
	protected function get_default_timeout() {
		$App = App::get_instance();
		$timeout = $App->config('session_max_time');
		
		if ( empty($timeout) ) {
		//session time not set in config, check PHP settings
			$cookie_lifetime = (int) ini_get('session.cookie_lifetime');
			$gc_maxlifetime = (int) ini_get('session.gc_maxlifetime');
			if ( ! is_numeric($cookie_lifetime) ) {
				$cookie_lifetime = self::$default_timeout;
			}
			if ( ! is_numeric($gc_maxlifetime) ) {
				$gc_maxlifetime = self::$default_timeout;
			}
			$timeout = $gc_maxlifetime > $cookie_lifetime ? $gc_maxlifetime : $cookie_lifetime;
		}
		
		self::$default_timeout = $timeout;
		return $timeout;
	}
	

	/**
	 * get_user_agent
	 *
	 * Returns the user browser user agent string.
	 *
	 * @access protected
	 * @return string The user agent string
	 */
	protected function get_user_agent() {
		return empty($_SERVER['HTTP_USER_AGENT']) ? '' : trim($_SERVER['HTTP_USER_AGENT']);
	}
	

	/**
	 * serialize
	 *
	 * Serializes and then encrypts  given data for database storage.
	 *
	 * @access protected
	 * @param mixed $data The data to serialize
	 * @return string The encrypted serialized string
	 */
	protected function serialize($data) {
		if ( empty($data) ) {
			return '';
		}

        $data = json_encode($data);
		return $this->encrypt($data);
	}
	

	/**
	 * session_init
	 *
	 * Initializes a session by searching the database for an active session and 
	 * loading it. If a session was not found, session data will be initialized
	 * and this class instance will await a call to session_start() to begin
	 * a new session.
	 *
	 * @access protected
	 * @return void
	 */
	protected function session_init($sess_cookie, $sess_timeout) {
        $this->is_default_timeout = empty($sess_timeout);
		$this->sess_cookie = empty($sess_cookie) ? self::$default_cookie : $sess_cookie;
		if ( isset(self::$sessions[$this->sess_cookie]) ) {
			return;
		}
		$cookie_val = isset($_COOKIE[$this->sess_cookie]) ? $_COOKIE[$this->sess_cookie] : false;
		$sess_user = array();
		
		if ( ! empty($cookie_val) ) {
		//retrieve session from db
			$query = "SELECT * FROM ".$this->db->escape_identifier($this->sess_table)." ";
			$query .= "WHERE MD5( CONCAT(`session_id`, ".$this->db->escape($this->sess_hash_salt).") )=";
			$query .= $this->db->escape($cookie_val);
			$result = $this->db->query($query);

			if ($result->num_rows() > 0) {
				$sess_user = $result->row();
				
				//session found, so match up user ip, user agent and time of last activity
				if ($sess_user['ip_address'] === $this->get_ip() &&
					$sess_user['user_agent'] === $this->get_user_agent() && 
					$sess_user['last_activity'] > ($this->now - $sess_user['timeout']) ) {
					$sess_data = empty($sess_user['data']) ? array() : $this->unserialize($sess_user['data']);
					unset($sess_user['data']);
					$this->sess_user = $sess_user;
					$this->sess_data = $sess_data;
					$this->has_session = true;
					$this->sess_timeout = $sess_user['timeout'];
					$this->cookie_timeout = $this->sess_timeout === self::$default_timeout ? 
											0 : 
											$this->now + $this->sess_timeout;
					self::$sessions[$this->sess_cookie] = $this->sess_timeout;
					return;
				} else {
				//session data does not matchup to user data or session expired, so end this session
					$this->session_destroy();
				}
			} else {
			//session data not found in db, so end this session
				$this->session_destroy();
			}
		}

		$timeout = $this->get_default_timeout();
		$this->sess_timeout = $this->is_default_timeout ? $timeout : $sess_timeout;
		$this->cookie_timeout = $this->is_default_timeout ? 0 : $this->now + $sess_timeout;
		self::$sessions[$this->sess_cookie] = $this->sess_timeout;
		
		$sess_user['session_id'] = $this->generate_sess_id();
		$sess_user['ip_address'] = $this->get_ip();
		$sess_user['user_agent'] = $this->get_user_agent();
		$sess_user['last_activity'] = $this->now;
		$sess_user['timeout'] = $this->sess_timeout;
	
		$this->sess_user = $sess_user;
		$this->sess_data = array();
		$this->is_new = true;
	}
	

	/**
	 * unserialize
	 *
	 * Decrypts then unserializes a string to a native data value.
	 *
	 * @access protected
	 * @param string $data The base64 serialized string
	 * @return mixed The unserialized data
	 */
	protected function unserialize($data) {
		if ( empty($data) ) {
			return $data;
		}

        $data = $this->decrypt($data);
		return json_decode($data, true);
	}
	
	
	/**
	 * write
	 *
	 * Saves the current session data to the database.<br/><br/>
	 * NOTE: If $sess_timeout param greater than one day then calls to $this->get_data(),
	 * $this->set_data(), this->touch() and $this->unset_data() will not reset the
	 * session countdown.
	 *
	 * @access protected
	 * @return void
	 */
	protected function write() {
		if ($this->is_writable) {
			$query = "";
			$data = $this->serialize($this->sess_data);
			
			if ($this->is_new) {
				$query = "INSERT INTO ".$this->db->escape_identifier($this->sess_table)." ";
				$query .= "(`session_id`, `ip_address`, `last_activity`, `user_agent`, `timeout`, `data`) VALUES (";
				$query .= $this->db->escape($this->sess_user['session_id']).", ";
				$query .= $this->db->escape($this->sess_user['ip_address']).", ";
				$query .= $this->db->escape($this->sess_user['last_activity']).", ";
				$query .= $this->db->escape($this->sess_user['user_agent']).", ";
				$query .= $this->db->escape($this->sess_user['timeout']).", ";
				$query .= $this->db->escape($data).")";
                $this->db->query($query);
				$this->is_new = false;
			} else {
                $this->db->query("LOCK TABLES ".$this->db->escape_identifier($this->sess_table)." WRITE;");
				$query = "UPDATE ".$this->db->escape_identifier($this->sess_table)." SET ";
				if ($this->is_default_timeout) {
					$query .= "`last_activity`=".$this->db->escape($this->now).", ";
				}
				$query .= "`data`=".$this->db->escape($data)." ";
				$query .= "WHERE `session_id`=".$this->db->escape($this->sess_user['session_id']);
                $this->db->query($query);
                $this->db->query("UNLOCK TABLES;");
			}
		} 
	}


    /**
     * decrypt
     *
     * Decrypta a given string
     *
     * @param string $data Data to decrypt
     * @return string The decrypted data
     */
    private function decrypt($data) {
        $data = base64_decode($data);
        $ivsize = openssl_cipher_iv_length(self::$ENCRYPT_METHOD);
        $iv = mb_substr($data, 0, $ivsize, '8bit');
        $key = substr( sha1($this->sess_hash_salt), 0, $ivsize);
        $ciphertext = mb_substr($data, $ivsize, null, '8bit');

        return openssl_decrypt(
            $ciphertext,
            self::$ENCRYPT_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }


    /**
     * Encrypt the given data
     *
     * @param mixed $data Session data to encrypt
     * @return string The encrypted data
     */
    private function encrypt($data) {
        $ivsize = openssl_cipher_iv_length(self::$ENCRYPT_METHOD);
        $iv = openssl_random_pseudo_bytes($ivsize);
        $key = substr( sha1($this->sess_hash_salt), 0, $ivsize);

        $ciphertext = openssl_encrypt(
            $data,
            self::$ENCRYPT_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv.$ciphertext);
    }
}

/* End of file Session.php */
/* Location: ./App/User/Session.php */