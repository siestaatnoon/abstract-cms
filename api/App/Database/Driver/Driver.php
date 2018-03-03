<?php

namespace App\Database\Driver;

use 
App\Exception\AppException,
App\Exception\SQLException;

/**
 * Driver abstract class
 * 
 * Provides the method definitions for all database drivers used in this application.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Database\Driver
 */
abstract class Driver {
	
    /**
     * @var boolean True to debug SQL query in error messages
     */
	protected $is_debug = false;
	
	
	/**
	 * Constructor
	 *
	 * Subclass implementation initializes the database connection.
	 * 
	 * @access public
	 * @param array $config The driver configuration array
	 */
	abstract public function __construct($config);
	
	
	/**
	 * Destructor
	 *
	 * Subclass implementation insures database connection is closed for gc.
	 * 
	 * @access public
	 */
	abstract public function __destruct();
	

	/**
	 * close
	 *
	 * Subclass implementation closes the database connection.
	 * 
	 * @access public
	 */
	abstract public function close();
	
	
	/**
	 * error_code
	 *
	 * Subclass implementation returns the error code(s) of the last database operation.
	 * 
	 * @access public
	 * @return mixed The error code(s)
	 */
	abstract public function error_code();
	
	
	/**
	 * error_info
	 *
	 * Subclass implementation returns the error information of the last database operation.
	 * 
	 * @access public
	 * @return string The error information
	 */
	abstract public function error_info();
	

	/**
	 * escape
	 *
	 * Subclass implementation escapes the given string for an SQL query surrounding
	 * it with single quotes.
	 * 
	 * @access public
	 * @param string $str The string to escape
	 * @return string The string, escaped and bookended with single quotes
	 */
	abstract public function escape($str);
	
	
	/**
	 * escape_identifier
	 *
	 * Subclass implementation escapes the given table or field name string.<br/><br/>
	 * NOTE: Subclass MUST verify identifier is a suitable identifier string to prevent
	 * SQL injections (e.g. calling preg_replace('/\W+/g', '', $str)).
	 * 
	 * @access public
	 * @param string $str The string to escape
	 * @return string The string escaped as an identifier
	 */
	abstract public function escape_identifier($str);
	
	
	/**
	 * escape_str
	 *
	 * Subclass implementation escapes the given string for an SQL query.
	 * 
	 * @access public
	 * @param string $str The string to escape
	 * @return string The string, escaped, no single quotes added
	 */
	abstract public function escape_str($str);
	
	
	/**
	 * insert_id
	 *
	 * Subclass implementation returns the row ID of the last INSERT query.
	 * 
	 * @access public
	 * @return int The row ID
	 */
	abstract public function insert_id();
	
	
	/**
	 * query
	 *
	 * Subclass implementation performs an SQL query and returns a Result object. If
	 * a driver-specific Exception or an SQL error occurs, the subclass should call
	 * parent::sql_error_handler() for the application to handle the error.
	 * 
	 * @access public
	 * @param string $query The query to perform
	 * @return \App\Database\Driver\Result The query result
	 * @see \App\Database\Driver\Result The result set abstract class definition
	 */
	abstract public function query($query);
	
	

	

	/**
	 * sql_error_handler
	 *
	 * Handles all SQL errors by directing to main application error handler.
	 * 
	 * @access protected
	 * @param string $query The SQL query that resulted in an exception or SQL error
	 * @return void
	 * @throws \App\Exception\SQLException if an SQL error has occurred
	 */
	protected function sql_error_handler($query='') {
		$error_info = $this->error_info();
		$message = "";
		
		if ( ! empty($error_info) ) {
			$error_code = $this->error_code();
			
			if ($this->is_debug && ! empty($query) ) {
				$message .= "[".$query."]\n";
			}
			$message .= "SQL error ".$error_code.": ".$error_info;

			throw new SQLException($message, AppException::ERROR_RUNTIME);
		}
	}
	
}

/* End of file Driver.php */
/* Location: ./App/Database/Driver/Driver.php */