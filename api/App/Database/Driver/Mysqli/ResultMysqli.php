<?php

namespace App\Database\Driver\Mysqli;

use 
mysqli_result,
App\Exception\AppException;

/**
 * ResultMysqli class
 * 
 * Defines the query result set for use with PDO MySQL. Extends the
 * App\Database\Driver\Result abstract class for method definitions.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Database\Driver\Mysqli
 */
class ResultMysqli extends \App\Database\Driver\Result {

    /**
     * @var \mysqli_result Holds the result set of a mysqli query
     */
	private $mysqli_result;


	/**
	 * Constructor
	 *
	 * Initializes the Result object.
	 * 
	 * @access public
	 * @param \mysqli_result $mysqli_result The object containing the query result set
	 * @see \App\Model\Database\Driver\Result
	 * @throws \App\Exception\AppException if $mysqli_result param not of class mysqli_result
	 */
	public function __construct($mysqli_result) {
		if ( empty($mysqli_result) || ! $mysqli_result instanceof mysqli_result) {
			$message = 'Invalid param (mysqli_result) $mysqli_result must be of type mysqli_result';
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$this->mysqli_result = $mysqli_result;
	}
	

	/**
	 * Destructor
	 *
	 * Insures the result set frees resources upon script completion.
	 * 
	 * @access public
	 */
	public function __destruct() {
		if ( ! empty($this->mysqli_result) ) {
			$this->free_result();
		}
	}
	

	/**
	 * free_result
	 *
	 * Frees the resources of the query Result object.
	 * 
	 * @access public
	 * @return void
	 */
	public function free_result() {
		$this->mysqli_result->free();
		$this->mysqli_result = NULL;
	}
	

	/**
	 * num_rows
	 *
	 * Returns the number of rows retrieved in the last operation.
	 * 
	 * @access public
	 * @return int The number of rows
	 */
	public function num_rows() {
		$num_rows = $this->mysqli_result->num_rows;
		if ( ! is_numeric($num_rows) ) {
			$num_rows = 0;
		}
		return $num_rows;
	}
	

	/**
	 * row
	 *
	 * Returns the next row in a result set. Returns NULL if at the end of
	 * result set or result set empty.
	 * 
	 * @access public
	 * @param boolean $is_assoc True to return the row as an associative array, false for numeric
	 * @return mixed The result set row or NULL if at end of result set on subsequent call
	 */
	public function row($is_assoc=true) {
		return $is_assoc ? $this->mysqli_result->fetch_assoc() : $this->mysqli_result->fetch_row();
	}
	

	/**
	 * result_array
	 *
	 * Returns the result set as a numerical array.
	 * 
	 * @access public
	 * @return array The result set
	 */
	public function result_array() {
		return $this->mysqli_result->fetch_all(MYSQLI_NUM);
	}
	
	
	/**
	 * result_assoc
	 *
	 * Returns the result set as an associative array.
	 * 
	 * @access public
	 * @return array The result set
	 */
	public function result_assoc() {
		return $this->mysqli_result->fetch_all(MYSQLI_ASSOC);
	}
	
}

/* End of file ResultMysqli.php */
/* Location: ./App/Database/Driver/Mysqli/ResultMysqli.php */