<?php

namespace App\Database\Driver\PdoMysql;

use 
PDO, 
PDOException,
App\Database\Driver\PdoMysql\ResultPdoMysql,
App\Exception\AppException;

/**
 * DriverPdoMysql class
 * 
 * Defines the database driver for use with PDO MySQL. Extends the
 * App\Database\Driver\Driver abstract class for method definitions.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Database\Driver\PdoMysql
 */
class DriverPdoMysql extends \App\Database\Driver\Driver {
	
    /**
     * @var \PDO The PDO database driver object
     */
	private $pdo;

    /**
     * @var array Holds the error number (index 0) and error info (index 1) of last operation
     */
	private $error_info;


	/**
	 * Constructor
	 *
	 * Initializes the PDO database connection.
	 * 
	 * @access public
	 * @param array $config The driver configuration array
	 * @throws \App\Exception\AppException if PDOException occurs in connecting to database
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
			$error = 'Invalid param (array) $config '.implode(', ', $errors).' parameter not defined';
			throw new AppException($errors, AppException::ERROR_FATAL);
		}
		
		$dbhost	= empty($config['host']) ? "localhost" : $config['host'];
		$dbport	= empty($config['port']) ? 3306 : $config['port'];
		$charset = empty($config['charset']) ? 'utf8' : $config['charset'];
		$conn = 'mysql:host='.$dbhost.';port='.$dbport.';dbname='.$config['db_name'].';charset='.$charset;
		$this->is_debug = ! empty($config['debug']);
		
		try {
			$this->pdo = new PDO($conn, $config['username'], $config['password']);
			$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			throw new AppException($e->getMessage(), AppException::ERROR_FATAL, $e);
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
		$this->pdo = NULL;
	}
	

	/**
	 * close
	 *
	 * Closes the database connection.
	 * 
	 * @access public
	 */
	public function close() {
		$this->pdo = NULL;
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
		return empty($this->error_info[1]) ? false : $this->error_info[1];
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
		return empty($this->error_info[2]) ? '' : $this->error_info[2];
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
	 * @throws \App\Exception\AppException if PDOException occurs in operation
	 */
	public function escape($str) {
		if ( is_null($str) ) {
			return 'NULL';
		} else if ($str === true) {
			return 1;
		} else if ($str === false) {
			return 0;
		}
		
		try {
			$str = $this->pdo->quote($str);
		} catch(PDOException $e) {
			throw new AppException($e->getMessage(), AppException::ERROR_RUNTIME, $e);
		}
		
		return $str;
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
	 * @throws \App\Exception\AppException if PDOException occurs in operation
	 */
	public function escape_str($str) {
		if ( is_null($str) ) {
			return 'NULL';
		} else if ($str === true) {
			return 1;
		} else if ($str === false) {
			return 0;
		}
		
		try {
			$str = $this->pdo->quote($str);
		} catch(PDOException $e) {
			throw new AppException($e->getMessage(), AppException::ERROR_RUNTIME, $e);
		}
		
		return $str === "''" ? '' : substr($str, 1, (strlen($str) - 2) );
	}
	

	/**
	 * insert_id
	 *
	 * Returns the row ID of the last INSERT query.
	 * 
	 * @access public
	 * @return int The row ID
	 * @throws \App\Exception\AppException if PDOException occurs in operation
	 */
	public function insert_id() {
		$insert_id = false;
		
		try {
			$insert_id = $this->pdo->lastInsertId();
		} catch(PDOException $e) {
			throw new AppException($e->getMessage(), AppException::ERROR_RUNTIME, $e);
		}
		
		return $insert_id;
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
	 * @return mixed The rows affected (int) or (Result) object
	 * @see \App\Database\Driver\Result The result set abstract class definition
	 * @see \App\Database\Driver\PdoMysql\ResultPdoMysql The PDO MySQL result set
	 */
	public function query($query) {
		$query = trim($query);
		$query_type = strtoupper( substr($query, 0, 6) );
		$is_select = $query_type === "SELECT";
		$is_rows_affected = $query_type === "INSERT" || $query_type === "UPDATE" || $query_type === "DELETE";
		$result = false;
		
		try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
			if ($is_select) {
                $result = new ResultPdoMysql($stmt);
            } else {
                $result = $is_rows_affected ? $stmt->rowCount() : 1;
            }
            $stmt->closeCursor();
		} catch(PDOException $e) {
			$this->error_info = $this->pdo->errorInfo();
			parent::sql_error_handler($query);
		}

		return $result;
	}

}

/* End of file DriverPdoMysql.php */
/* Location: ./App/Database/Driver/PdoMysql/DriverPdoMysql.php */