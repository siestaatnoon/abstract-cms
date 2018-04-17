<?php

namespace App\Database\Driver\PdoMysql;

use 
PDO, 
PDOStatement,
App\Exception\AppException;

/**
 * ResultPdoMysql class
 * 
 * Defines the query result set for use with mysqli. Extends the
 * App\Database\Driver\Result abstract class for method definitions.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Database\Driver\PdoMysql
 */
class ResultPdoMysql extends \App\Database\Driver\Result {

    /**
     * @var \PDOStatement Holds the result set of a PDO query
     */
	private $statement;

    /**
     * @var int Number of rows returned in the query set
     */
	private $num_rows;

    /**
     * @var int Current pointer for row retrieval in all to self::row(), increments after each call
     */
	private $pointer = 0;

    /**
     * @var array Array of rows as numerical arrays from the result set
     */
	private $rows_array;

    /**
     * @var array Array of rows as assoc arrays by field name from the result set
     */
	private $rows_assoc;


	/**
	 * Constructor
	 *
	 * Initializes the Result object.
	 * 
	 * @access public
	 * @param \PDOStatement $statement The object containing the query result set
	 * @see \App\Database\Driver\Result
	 * @throws \App\Exception\AppException if $statement param not of class PDOStatement
	 */
	public function __construct($statement) {
		if ( empty($statement) || ! $statement instanceof PDOStatement) {
            $msg_part = error_str('error.param.type', array('$statement', 'PDOStatement'));
            $message = error_str('error.type.param.invalid', array($msg_part));
			throw new AppException($message, AppException::ERROR_FATAL);
		}

		$this->statement = $statement;
		$this->num_rows = $this->statement->rowCount();
        $this->rows_assoc = $this->statement->fetchAll(PDO::FETCH_ASSOC);

        $rows_array = array();
        foreach ($this->rows_assoc as  $row_assoc) {
            $index = 0;
            $row_array = array();
            foreach ($row_assoc as $val) {
                $row_array[$index] = $val;
                $index++;
            }
            $rows_array[] = $row_array;
        }
		$this->rows_array = $rows_array;
	}
	
	
	/**
	 * Destructor
	 *
	 * Insures the result set frees resources upon script completion.
	 * 
	 * @access public
	 */
	public function __destruct() {
        $this->free_result();
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
        if ( ! empty($this->statement) ) {
            $this->statement->closeCursor();
            $this->statement = NULL;
        }
	}
	

	/**
	 * num_rows
	 *
	 * Returns the number of rows retrieved in the last operation.<br/><br/>
	 * NOTE: According to PHP PDOStatement documentation:<br/><br/>
	 * If the last SQL statement executed by the associated PDOStatement was a SELECT statement, 
	 * some databases may return the number of rows returned by that statement. However, this 
	 * behaviour is not guaranteed for all databases and should not be relied on for portable 
	 * applications.
	 * 
	 * @access public
	 * @return int The number of rows
	 * @see http://php.net/manual/en/pdostatement.rowcount.php
	 */
	public function num_rows() {
		if ( ! is_numeric($this->num_rows) ) {
			return 0;
		}
		return $this->num_rows;
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
		$row = NULL;
		if ($is_assoc) {
		    if ( isset($this->rows_assoc[$this->pointer]) ) {
                $row = $this->rows_assoc[$this->pointer];
                $this->pointer++;
            }
        } else if ( isset($this->rows_array[$this->pointer]) ) {
            $row = $this->rows_array[$this->pointer];
            $this->pointer++;
        }

		return $row;
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
		return $this->rows_array;
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
		return $this->rows_assoc;
	}
	
}

/* End of file ResultPdoMysql.php */
/* Location: ./App/Database/Driver/PdoMysql/ResultPdoMysql.php */