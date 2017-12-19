<?php

namespace App\Database\Driver;

/**
 * Result class
 * 
 * Provides the method definitions for the query result set corresponding with a database driver.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Database\Driver
 */
abstract class Result {	

	/**
	 * Destructor
	 *
	 * Subclass implementation insures the result set frees resources upon script completion.
	 * 
	 * @access public
	 */
	abstract public function __destruct();
	
	
	/**
	 * free_result
	 *
	 * Subclass implementation frees the resources of the query Result object.
	 * 
	 * @access public
	 * @return void
	 */
	abstract public function free_result();
	
	
	/**
	 * num_rows
	 *
	 * Subclass implementation returns the number of rows retrieved in the last operation. Note
	 * that this should return zero in the event of an Exception or SQL error.
	 * 
	 * @access public
	 * @return int The number of rows
	 */
	abstract public function num_rows();
	

	/**
	 * row
	 *
	 * Subclass implementation returns the next row in a result set. Returns NULL if at the end of
	 * result set or result set empty.
	 * 
	 * @access public
	 * @param boolean $is_assoc True to return the row as an associative array, false for numeric
	 * @return mixed The result set row or NULL if at end of result set on subsequent call
	 */
	abstract public function row($is_assoc);
	
	
	/**
	 * result_array
	 *
	 * Subclass implementation returns the result set as a numeric array.
	 * 
	 * @access public
	 * @return array The result set
	 */
	abstract public function result_array();
	
	/**
	 * result_assoc
	 *
	 * Subclass implementation returns the result set as an associative array.
	 * 
	 * @access public
	 * @return array The result set
	 */
	abstract public function result_assoc();
	
}

/* End of file Result.php */
/* Location: ./App/Database/Driver/Result.php */