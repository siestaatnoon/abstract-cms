<?php

namespace App\Database\Driver\Mysqli;

use 
mysqli, 
mysqli_result,
App\Database\Driver\Mysqli\ResultMysqli,
App\Exception\AppException;

/**
 * DriverMysqli class
 * 
 * Defines the database driver for use with mysqli. Extends the
 * App\Database\Driver\Driver abstract class for method definitions.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Database\Driver\Mysqli
 */
class DriverMysqli extends \App\Database\Driver\Driver {

    /**
     * @var mysqli The mysqli database driver object
     */
	private $mysqli;

    /**
     * @var array Holds the error number (index [error_num]) and error 
     * info (index [error_info]) of last operation
     */
	private $error_info;


	/**
	 * Constructor
	 *
	 * Initializes the mysqli database connection.
	 * 
	 * @access public
	 * @param array $config The driver configuration array
	 * @throws \App\Exception\AppException if $config assoc array missing required parameters
	 */
	public function __construct($config) {
		$errors = array();
		
		if ( empty($config['username']) ) {
			$errors[] = '[username]';
		}
		if ( empty($config['password']) ) {
			$errors[] = '[password]';
		}
		if ( empty($config['db_name']) ) {
			$errors[] = '[db_name]';
		}
		if ( ! empty($errors) ) {
			$message = 'Invalid param (array) $config '.implode(', ', $errors).' parameter not defined';
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$dbhost	= empty($config['host']) ? "localhost" : $config['host'];
		$dbport	= empty($config['port']) ? 3306 : $config['port'];
		$this->mysqli = new mysqli(
			$dbhost, 
			$config['username'], 
			$config['password'],
			$config['db_name'],
			$dbport
		);
		
		$charset = empty($config['charset']) ? 'utf8' : $config['charset'];
		$this->mysqli->set_charset($charset);
		
		$this->is_debug = ! empty($config['debug']);
		$this->error_info['error_num'] = $this->mysqli->connect_errno;
		$this->error_info['error_info'] = $this->mysqli->connect_error;
		
		if ( ! empty($this->mysqli->connect_error) ) {
			parent::sql_error_handler();
		}
	}
	

	/**
	 * Destructor
	 *
	 * Insures the database connection is closed upon script completion.
	 * 
	 * @access public
	 */
	public function __destruct() {
		if ( ! empty($this->mysqli) ) {
			$this->close();
		}
	}
	

	/**
	 * close
	 *
	 * Closes the database connection.
	 * 
	 * @access public
	 */
	public function close() {
		if ( ! empty($this->mysqli) ) {
			$this->mysqli->close();
			$this->mysqli = NULL;
		}
	}
	

	/**
	 * error_code
	 *
	 * Returns the MySQL error code of the last database operation.
	 * 
	 * @access public
	 * @return int The error code
	 */
	public function error_code() {
		return empty($this->error_info['error_num']) ? false : $this->error_info['error_num'];
	}
	

	/**
	 * error_info
	 *
	 * Returns the error information of the last database operation.
	 * 
	 * @access public
	 * @return string The error information
	 */
	public function error_info() {
		return empty($this->error_info['error_info']) ? '' : $this->error_info['error_info'];
	}
	

	/**
	 * escape
	 *
	 * Escapes the given string for an SQL query surrounding
	 * it with single quotes.
	 * 
	 * @access public
	 * @param string $str The string to escape
	 * @return string The string, escaped and bookended with single quotes except for NULL values
	 */
	public function escape($str) {
		if ( is_null($str) ) {
			return 'NULL';
		} else if ($str === true) {
			return 1;
		} else if ($str === false) {
			return 0;
		}

		return "'".$this->mysqli->real_escape_string($str)."'";
	}
	
	
	/**
	 * escape_identifier
	 *
	 * Escapes the given table or field name string for a MySQL query by
	 * surrounding it with backquotes. This will also format the string to insure
	 * no non-alphahumeric characters are present to prevent SQL injection.
	 * 
	 * @access public
	 * @param string $str The string to escape
	 * @return mixed The backquoted escaped string or false if empty $str param
	 */
	public function escape_identifier($str) {
		if ( empty($str) ) {
			return false;
		}
		
		$str = preg_replace('/\W+/', '', $str);
		return "`".$str."`";
	}
	

	/**
	 * escape_str
	 *
	 * Escapes the given string for an SQL query, single quotes not
	 * added before or after the string.
	 * 
	 * @access public
	 * @param string $str The string to escape
	 * @return string The escaped string or NULL (string) for null values
	 */
	public function escape_str($str) {
		if ( is_null($str) ) {
			return 'NULL';
		} else if ($str === true) {
			return 1;
		} else if ($str === false) {
			return 0;
		}
		
		return $this->mysqli->real_escape_string($str);
	}
	

	/**
	 * insert_id
	 *
	 * Returns the row ID of the last INSERT query.
	 * 
	 * @access public
	 * @return int The row ID
	 */
	public function insert_id() {
		return $this->mysqli->insert_id;
	}


	/**
	 * query
	 *
	 * Performs an SQL query and returns the number of rows affected if
	 * an INSERT, UPDATE or DELETE or a Result object for SELECT or other
	 * queries.
	 * 
	 * @access public
	 * @param string $query The query to perform
	 * @return mixed The rows affected (int) or (Result) object or false if query failed
	 * @see \App\Model\Database\Driver\Result The result set abstract class definition
	 * @see \App\Model\Database\Driver\Mysqli\ResultMysqli The mysqli result set
	 */
	public function query($query) {
		$query = trim($query);
		$query_type = strtoupper( substr($query, 0, 6) );
		$is_rows_affected = $query_type === "INSERT" || $query_type === "UPDATE" || $query_type === "DELETE";
		$result = $this->mysqli->query($query);
		$this->error_info['error_num'] = $this->mysqli->errno;
		$this->error_info['error_info'] = $this->mysqli->error;

		$result_set = false;
		if ( ! $is_rows_affected && $result instanceof mysqli_result) {
			$result_set = new ResultMysqli($result);
		} else if ( empty($result) ) {
			parent::sql_error_handler($query);
			return false;
		}
		
		return $is_rows_affected ? $this->mysqli->affected_rows : $result_set;
	}
	
}

/* End of file DriverMysqli.php */
/* Location: ./App/Database/Driver/Mysqli/DriverMysqli.php */