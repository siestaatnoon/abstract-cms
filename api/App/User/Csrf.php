<?php

namespace App\User;

use App\App;

/**
 * Crsf class
 * 
 * This class is used to for the prevention of CRSF attacks. An instance
 * creates a hashed token saved in a cookie in the user's browser. The
 * validity can then be checked against the server request headers with
 * the is_valid() function.
 * 
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @license     http://www.projectabstractcms.com/license
 * @version     0.1.0
 * @package		App
 */
class Csrf {

    /**
     * @var string The cookie name storing the CSRF token
     */
	protected static $csrf_cookie = '';

    /**
     * @var \App\User\Csrf Singleton instance of this class
     */
	protected static $instance = NULL;

    /**
     * @var string The CSRF token value
     */
	protected $token;


	/**
	 * Constructor
	 *
	 * Initializes the Csrf class singleton object. Will
	 * automatically generate the CSRF session cookie.
	 * 
	 * @access private
	 */
	private function __construct() {
        $App = App::get_instance();
        self::$csrf_cookie = $App->config('csrf_token');
		$this->set_token(false);
	}
	

	/**
	* get_instance
	* 
	* Returns a singleton instance of this Csrf class.
	*
	* @access public
	* @return \App\User\Csrf The singleton instance
	*/
	public static function get_instance() {
		if ( empty(self::$instance) ) {
			self::$instance = new \App\User\Csrf();
		}
		return self::$instance;
	}
	
	
	/**
	* get_key
	* 
	* Returns the CSRF token name.
	*
	* @access public
	* @return string The CSRF token name
	*/
	public function get_key() {
		return self::$csrf_cookie;
	}
	

	/**
	* get_token
	* 
	* Returns the current CSRF token.
	*
	* @access public
	* @return string The CSRF token
	*/
	public function get_token() {
		return $this->token;
	}
	

	/**
	* is_valid
	* 
	* Validates the CSRF token against a session saved value.
	*
	* @access public
	* @param string $token The session saved token to check
	* @return boolean True if token value matches the CSRF cookie token
	*/
	public function is_valid($token) {
		return ! empty($token) &&
			   ! empty($_COOKIE[self::$csrf_cookie]) && 
			   hash_equals($_COOKIE[self::$csrf_cookie], $token);
	}
	

	/**
	* invalidate
	* 
	* Deletes the current CSRF token cookie.
	*
	* @access public
	* @return void
	*/
	public function invalidate() {
		setcookie(self::$csrf_cookie, '', 0, '/');
		unset($_COOKIE[self::$csrf_cookie]);
	}


	/**
	* set_token
	* 
	* Sets the CSRF token cookie.
	*
	* @access public
	* @param boolean $replace True to replace if a token cookie already exists
	* @return string The generated CSRF token
	*/
	public function set_token($replace=true) {
		if ( ! $replace &&  isset($_COOKIE[self::$csrf_cookie]) &&
            preg_match('#^[0-9a-f]{32}$#iS', $_COOKIE[self::$csrf_cookie]) === 1) {
            $this->token = $_COOKIE[self::$csrf_cookie];
			return $this->token;
		}

		$rand_bytes = '';
		$length = 16;
        if ( function_exists('openssl_random_pseudo_bytes') ) {
            $rand_bytes = openssl_random_pseudo_bytes($length);
        } else if ( is_php('5.4') && is_readable('/dev/urandom') ) {
            $fp = fopen('/dev/urandom', 'rb');
            if ( ! empty($fp) ) {
                stream_set_chunk_size($fp, $length);
                $rand_bytes = fread($fp, $length);
                fclose($fp);
            }
        } else if ( defined('MCRYPT_DEV_URANDOM') )  {
        //fallback for < PHP 5.3
            $rand_bytes = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
        }
		
		$this->token = empty($rand_bytes) ? md5( uniqid( mt_rand(), true) ) : bin2hex($rand_bytes);
		setcookie(self::$csrf_cookie, $this->token, 0, '/');
		return $this->token;
	}

}

/* End of file Csrf.php */
/* Location: ./App/User/Csrf.php */