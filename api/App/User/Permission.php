<?php

namespace App\User;

/**
 * Permission class
 * 
 * Contains the logic for a CMS user permission in the Abstract app. A permission
 * is represented by a 4 bit binary number (delete, add, update, read in that order) 
 * it's integer representation. Note that if the delete, add and/or update bit is 
 * set to 1, then the read bit is set automatically to 1.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2016 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\User
 */
class Permission {
	
    /**
     * @const int Special permission for super user
     */
	const PERMISSION_SUPER_USER = -3;

    /**
     * @var int Max decimal value for permission
     */
	private static $MAX_DECIMAL = 15;

    /**
     * @var string Max binary value for permission
     */
	private static $PERM = array(
		'delete'=> 0,
		'add' 	=> 1,
		'update'=> 2,
		'read' 	=> 3
	);
	
    /**
     * @var bool True if permission is for super user
     */
	private $is_super = false;

    /**
     * @var \App\User\Permission Instance of this class used for static methods
     */
	private static $instance = NULL;

    /**
     * @var int Current decimal value of permission
     */
	private $perm_dec = 0;

    /**
     * @var string Current binary value of permission
     */
	private $perm_bin = '0000';
	

	/**
	 * Constructor
	 *
	 * Initializes the permission from a given decimal or binary value.
	 * 
	 * @access public
	 * @param mixed $dec_or_bin The binary or decimal value of permission
	 */
	public function __construct($dec_or_bin) {
		if ($dec_or_bin === self::PERMISSION_SUPER_USER) {
			$this->is_super = true;
			$dec_or_bin = self::$MAX_DECIMAL;
		}
		$this->set_val($dec_or_bin);
	}
	

	/**
	 * bin
	 *
	 * Returns the binary value of the permission.
	 * 
	 * @access public
	 * @return string The binary permission
	 */
	public function bin() {
		return $this->perm_bin;
	}
	
	
	/**
	 * dec
	 *
	 * Returns the integer value of the permission.
	 * 
	 * @access public
	 * @return int The permission as an integer
	 */
	public function dec() {
		return $this->perm_dec;
	}
	

	/**
	 * has
	 *
	 * Checks the param bit and returns true if on, or equals one.
	 * 
	 * @access public
	 * @param string $perm The bit name, (delete|add|update|read)
	 * @return bool True if bit is on (is one), false if zero
	 */
	public function has($perm) {
		$has_perm = false;
		if ( isset(self::$PERM[$perm]) ) {
			$has_perm = substr($this->perm_bin, self::$PERM[$perm], 1) === '1';
		}
		return $has_perm;
	}
	

	/**
	 * has_add
	 *
	 * Checks the read bit and returns true if on, or equals one. Calls
	 * $this->has('read').
	 * 
	 * @access public
	 * @return bool True if add bit is on (is one), false if zero
	 */
	public function has_add() {
		return $this->has('add');
	}
	

	/**
	 * has_all
	 *
	 * Checks if all permission bits are on.
	 * 
	 * @access public
	 * @return bool True if all bits are on (all one), false if not
	 */
	public function has_all() {
		return $this->perm_dec === self::$MAX_DECIMAL;
	}
	

	/**
	 * has_delete
	 *
	 * Checks the delete bit and returns true if on, or equals one. Calls
	 * $this->has('delete').
	 * 
	 * @access public
	 * @return bool True if delete bit is on (is one), false if zero
	 */
	public function has_delete() {
		return $this->has('delete');
	}
	

	/**
	 * has_read
	 *
	 * Checks the read bit and returns true if on, or equals one. Calls
	 * $this->has('read').
	 * 
	 * @access public
	 * @return bool True if read bit is on (is one), false if zero
	 */
	public function has_read() {
		return $this->has('read');
	}
	

	/**
	 * has_update
	 *
	 * Checks the update bit and returns true if on, or equals one. Calls
	 * $this->has('update').
	 * 
	 * @access public
	 * @return bool True if update bit is on (is one), false if zero
	 */
	public function has_update() {
		return $this->has('update');
	}
	

	/**
	 * is_binary
	 *
	 * Checks if gven parameter is a binary string. Note that the parameter
	 * must be a string in order to be considered binary.
	 * 
	 * @access public
	 * @param string $num The string to check
	 * @return bool True if given param is binary string
	 */
	public function is_binary($num) {
		if ( is_string($num) === false) {
			return false;
		}
		
		$perm_length = strlen($this->perm_bin);
		if ( strlen($num) > $perm_length) {
			$num = substr($num, strlen($num) - $perm_length);
		}

		$is_bin = true;
		for ($i=0; $i < strlen($num); $i++) {
			$bit = (int) substr($num, $i, 1);
			if ($bit !== 0 && $bit !== 1) {
				$is_bin = false;
				break;
			}
		}
		
		return $is_bin;
	}
	

	/**
	 * merge
	 *
	 * Merges a given int or binary permission value with the value of this
	 * Permission object. Note that only the "on" bits are merged from the
	 * given param.
	 * 
	 * @access public
	 * @param $dec_or_bin The binary or decimal value of permission
	 * @return string The merged permission as a binary string
	 */
	public function merge($dec_or_bin) {
		if ( $this->has_all() ) {
			return self::to_binary(self::$MAX_DECIMAL);
		}
		
		$to_merge = new self($dec_or_bin);
		foreach (self::$PERM as $perm => $index) {
			if ( $to_merge->has($perm) ) {
				$this->set($perm, true);
			}
		}
		
		return $this->bin();
	}
	

	/**
	 * set
	 *
	 * Sets the param bit on or off (one or zero). Note that super
	 * users cannot have permission bits set.
	 * 
	 * @access public
	 * @param string $perm The bit name, (delete|add|update|read)
	 * @param bool $is_on True to set the bit as one, false zero
	 * @return void
	 */
	public function set($perm, $is_on) {
		if ($this->is_super) {
			return;
		}
		
		if ( isset(self::$PERM[$perm]) ) {
			$val = $is_on ? 1 : 0;
			$index = self::$PERM[$perm];
			$curr_val = (int) substr($this->perm_bin, $index, 1);
			if ($curr_val !== $val) {
				if ($perm !== 'read' && $val === 1) {
				//if delete/add/update permission set, 
				//then read set automatically
					$this->set_read(true);
				}
				$this->perm_bin = substr_replace($this->perm_bin, $val, $index, 1);
				$this->perm_dec = bindec($this->perm_bin);
			}
		}
	}
	

	/**
	 * set_add
	 *
	 * Sets the add bit on or off (one or zero). Note that super
	 * users cannot have permission bits set.
	 * 
	 * @access public
	 * @param bool $is_on True to set the bit as one, false zero
	 * @return void
	 */
	public function set_add($is_on) {
		if ($this->is_super) {
			return;
		}
		$this->set('add', $is_on);
	}
	

	/**
	 * set_delete
	 *
	 * Sets the delete bit on or off (one or zero). Note that super
	 * users cannot have permission bits set.
	 * 
	 * @access public
	 * @param bool $is_on True to set the bit as one, false zero
	 * @return void
	 */
	public function set_delete($is_on) {
		if ($this->is_super) {
			return;
		}
		$this->set('delete', $is_on);
	}
	

	/**
	 * set_read
	 *
	 * Sets the read bit on or off (one or zero). Note that super
	 * users cannot have permission bits set.
	 * 
	 * @access public
	 * @param bool $is_on True to set the bit as one, false zero
	 * @return void
	 */
	public function set_read($is_on) {
		if ($this->is_super) {
			return;
		}
		$this->set('read', $is_on);
	}
	

	/**
	 * set_update
	 *
	 * Sets the update bit on or off (one or zero). Note that super
	 * users cannot have permission bits set.
	 * 
	 * @access public
	 * @param bool $is_on True to set the bit as one, false zero
	 * @return void
	 */
	public function set_update($is_on) {
		if ($this->is_super) {
			return;
		}
		$this->set('update', $is_on);
	}
	

	/**
	 * set_val
	 *
	 * Sets the permission value from a decimal value or binary string value.
	 * Note that, if decimal value and less than zero or non-numeric, the value will be zero
	 * and if the value is greater than self::$MAX_DECIMAL, then the value becomes
	 * self::$MAX_DECIMAL. For binary string values, the last four chars are used. Note 
	 * that super users cannot have permission bits set.
	 * 
	 * @access public
	 * @param mixed $dec_or_bin The decimal or binary permission value
	 * @return void
	 */
	public function set_val($dec_or_bin) {
        if ($dec_or_bin === self::PERMISSION_SUPER_USER) {
            $dec_or_bin = self::$MAX_DECIMAL;
        } else if ( empty($dec_or_bin) || ctype_digit((string) $dec_or_bin) === false ) {
			$dec_or_bin = 0;
		}
		
		$perm_length = strlen($this->perm_bin);
		if ( $this->is_binary($dec_or_bin) === true) {
		//arg binary value
			$length = strlen($dec_or_bin);
			if ($length > $perm_length) {
			//if bin string greater than 4 chars, truncate to last 4
				$dec_or_bin = substr($dec_or_bin, strlen($dec_or_bin) - $perm_length);
			}
			$this->perm_dec = bindec($dec_or_bin);
			$this->perm_bin = $this->zero_pad($dec_or_bin);
		} else {
		//arg decimal value
			if ($dec_or_bin < 0) {
				$dec_or_bin = 0;
			} else if ($dec_or_bin > self::$MAX_DECIMAL) {
				$dec_or_bin = self::$MAX_DECIMAL;
			}
	
			$perm_bin = $this->zero_pad( decbin($dec_or_bin) );
			$this->perm_dec = (int) $dec_or_bin;
			$this->perm_bin = $perm_bin;
		}
	}
	

	/**
	 * to_binary
	 *
	 * Static method that converts a decimal permission value to it's binary value as a string.
	 * 
	 * @access public
	 * @param mixed $perm The decimal permission value
	 * @return string The permission binary value
	 */
	public static function to_binary($perm) {
		if ( empty(self::$instance) ) {
			self::$instance = new self($perm);
		} else {
			self::$instance->set_val($perm);
		}
		
		return self::$instance->bin();
	}
	

	/**
	 * to_decimal
	 *
	 * Static method that converts a binary permission value to it's decimal value as an integer.
	 * 
	 * @access public
	 * @param mixed $perm The binary permission value
	 * @return int The permission decimal value
	 */
	public static function to_decimal($perm) {
		if ( empty(self::$instance) ) {
			self::$instance = new self($perm);
		} else {
			self::$instance->set_val($perm);
		}
		
		return self::$instance->dec();
	}


	/**
	 * zero_pad
	 *
	 * Zero pads a string to four chars.
	 * 
	 * @access private
	 * @param string $str The string to pad
	 * @return string The zero padded string
	 */
	private function zero_pad($str) {
		return str_pad($str, strlen($this->perm_bin), '0', STR_PAD_LEFT);
	}
}


/* End of file Permission.php */
/* Location: ./App/User/Permission.php */