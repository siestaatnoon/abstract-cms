<?php

namespace App\Model;

use 
App\App,
App\Database\Database,
App\Exception\AppException;

/**
 * Options class
 * 
 * Provides the database functions for an App\Module\Module object consisting of data,
 * such as configurations, states, etc, that do not require their own table for storage. This
 * data is instead saved to a single table, "options" and can be retrieved with a call to
 * the method get(module_name).
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Model
 */
class Options {
	
    /**
     * @var string Name of the table storing options data
     */
	protected static $OPTIONS_TABLE = 'options';
	
    /**
     * @var \App\Database\Database The module database connection
     */
	protected $db;
	
    /**
     * @var array The module field names and corresponding default values
     */
	protected $fields;
	
    /**
     * @var string Module name
     */
	protected $module;

	
	/**
	 * Constructor
	 *
	 * Initializes the Model with the given configuration parameters in assoc array:<br/><br/>
	 * <ul>
	 * <li>module => The module utilizing this model, which corresponds to identifier in options table</li>
	 * <li>fields => Assoc array of all table fields and corresponding default value</li>
	 * <li>title_field => Table field used to identify record by name</li>
	 * <li>database => (Optional) The database configuration param defined in $config[database][use_this_param] in 
	 * ./App/config.php if using a connection other than default connection</li>
	 * </ul><br/><br/>
	 * 
	 * @access public
	 * @param array $config The options model configuration array
	 * @throws \App\Exception\AppException if $config assoc array missing required parameters
	 */
	public function __construct($config) {
		$app = App::get_instance();
		$errors = array();
		
		if ( empty($config['module']) ) {
			$errors[] = '$config[module] empty, must be name of module utilizing this model';
		}
		if ( empty($config['fields']) || ! is_array($config['fields']) ) {
			$errors[] = '$config[fields] empty or not array, must be assoc array of model field names and default values';
		}
		if ( ! empty($errors) ) {
			$message = 'Invalid param (array) $config: '.implode("\n", $errors);
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$db_config = empty($config['database']) ? $app->config('db_config') : $config['database'];
		$this->db = Database::connection($db_config);
		$this->module = $config['module'];
		$this->fields = $config['fields'];
		
		//prefix table prefix to slug table
		$table_prefix = $app->config('db_table_prefix');
		if ( ! empty($table_prefix) && substr(self::$OPTIONS_TABLE, 0, strlen($table_prefix) ) !== $table_prefix) {
			self::$OPTIONS_TABLE = $table_prefix.self::$OPTIONS_TABLE;
		}
	}
	
	
	/**
	 * Destructor
	 * 
	 * Ensures the database connection is closed upon script exit.
	 * 
	 * @access public
	 */
	public function __destruct() {
		if ( ! empty($this->db) ) {
			$this->db->close();
		}
	}
	

	/**
	 * create
	 *
	 * Inserts new module options from an assoc array of fields => values 
	 * If options already exist for this module upon, they will first be deleted.<br/><br/>
	 * NOTE: All values are saved as data type LONGTEXT in the options table.
	 *
	 * @access public
	 * @param array $config The options configuration assoc array, similar to constructor
	 * @param array $data The fields and corresponding values to insert
	 * @return boolean True if operation successful or an App\Exception\SQLException is passed 
	 * and to be handled by \App\App class if an SQL error occurred
	 * @see __construct() for model configuration parameters
	 */
	public static function create($config, $data=array()) {
		if ( empty($config) ) {
			return false;
		} else if ( ! is_array($data) ) {
			$data = array();
		}
		
		$options = new self($config);
		
		//first delete any options with same module name
		$options->destroy($config);
		
		$db = $options->get_property('db');
		$fields = $options->get_property('fields');
		$module = $options->get_property('module');
		
		$data = $data + $fields;
		$query = "INSERT INTO ".$db->escape_identifier(self::$OPTIONS_TABLE)." (module, var, value) VALUES ";
		$insert_rows = [];

		foreach ($data as $field => $value) {
			if ( ! array_key_exists($field, $fields) ) {
			//If field not in list of option fields of this module then skip
				continue;
			}

			$row = "(".$db->escape($module).", ";
			$row .= $db->escape($field).", ";
			$row .= $db->escape($value).")";
			$insert_rows[] = $row;
		}
		
		$query .= implode(", ", $insert_rows).";";
		$result = $db->query($query);
		
		return is_numeric($result) ? $options : false;
	}
	

	/**
	 * destroy
	 *
	 * Deletes all values from the options table for this Option's module.
	 *
	 * @access public
	 * @param array $config The options configuration assoc array, similar to constructor
	 * @return boolean True if operation successful or an App\Exception\SQLException is passed 
	 * and to be handled by \App\App class if an SQL error occurred
	 * @see __construct() for model configuration parameters
	 */
	public static function destroy($config) {
		if ( empty($config) ) {
			return false;
		}
		
		$options = new self($config);
		$db = $options->get_property('db');
		$fields = $options->get_property('fields');
		$module = $options->get_property('module');
		
		$query = "DELETE FROM ".$db->escape_identifier(self::$OPTIONS_TABLE)." ";
		$query .= "WHERE module=".$db->escape($module);
		$result = $db->query($query);

		return is_numeric($result);
	}
	

	/**
	 * get
	 *
	 * Retrieves the options for this Option's module.<br/><br/>
	 * NOTE: Since all values are stored as strings, they ARE NOT are not typecast upon 
	 * retrieval.
	 *
	 * @access public
	 * @return mixed The assoc array of options OR false if operation unsuccessful OR
	 * an App\Exception\SQLException is passed and to be handled by \App\App class if an 
	 * SQL error occurred
	 */
	public function get() {
		$query = "SELECT var, value FROM ".$this->db->escape_identifier(self::$OPTIONS_TABLE)." ";
		$query .= "WHERE module=".$this->db->escape($this->module);
		$result = $this->db->query($query);
		$rows = $result === false ? false : $result->result_assoc();
	
		return empty($rows) ? false : $this->parse_result($rows);
	}
	
	
	/**
	 * get_property
	 *
	 * Returns a member var value of this class given the property name that corresponds to it. 
	 *
	 * @access public
	 * @param string $name The name of the class member variable
	 * @return mixed The value of the variable or false if it does not exist
	 */
	public function get_property($name) {
		$property = false;
		switch($name){
			case 'db' :
				$property = $this->db;
				break;
			case 'fields' :
				$property = $this->fields;
				break;
			case 'module' :
				$property = $this->module;
				break;
		}
		
		return $property;
	}
	
	
	/**
	 * update
	 *
	 * Updates the module options from an assoc array of fields => values. Each array
	 * index is whitelisted, checking if existing in $this->fields to prevent SQL errors.<br/><br/>
	 * NOTE: All values are saved as data type LONGTEXT in the options table.
	 *
	 * @access public
	 * @param array $data The fields and corresponding values to insert
	 * @return boolean True if operation successful or an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 */
	public function update($data) {
		if ( empty($data) ) {
			return false;
		}

		foreach ($data as $field => $value) {
			if ( ! array_key_exists($field, $this->fields) ) {
			//If field not in list of option fields of this module then skip
				continue;
			}
			
			$query = "UPDATE ".$this->db->escape_identifier(self::$OPTIONS_TABLE)." SET ";
			$query .= "value=".$this->db->escape($value)." ";
			$query .= "WHERE module=".$this->db->escape($this->module)." ";
			$query .= "AND var=".$this->db->escape($field);
			$result = $this->db->query($query);
		}
		
		return true;
	}
	
	
	/**
	 * update_fields
	 *
	 * Updates the module options by inserting and/or deleting fields and values<br/><br/>
	 * NOTE: All values are saved as data type LONGTEXT in the options table.
	 *
	 * @access public
	 * @param array $data The new fields and corresponding values to insert/delete
	 * @return mixed \App\Model\Option An updated instance of this class if operation successful OR 
	 * false if empty $data param OR an App\Exception\SQLException is passed and to be handled by 
	 * \App\App class if an SQL error occurred
	 */
	public function update_fields($data) {
		if ( empty($data) ) {
			return false;
		}

		$fields_add = array_diff_key($data, $this->fields);
		if ( ! empty($fields_add) ) {
			$query = "INSERT INTO ".$this->db->escape_identifier(self::$OPTIONS_TABLE)." (module, var, value) VALUES ";
			$insert_rows = [];

			foreach ($fields_add as $field => $value) {
				$row = "(".$this->db->escape($this->module).", ";
				$row .= $this->db->escape($field).", ";
				$row .= $this->db->escape($value).")";
				$insert_rows[] = $row;
			}
			$query .= implode(", ", $insert_rows).";";
			$result = $this->db->query($query);
		}
		
		$fields_delete = array_diff_key($this->fields, $data);
		if ( ! empty($fields_delete) ) {
			$query = "DELETE FROM ".$this->db->escape_identifier(self::$OPTIONS_TABLE)." ";
			$query .= "WHERE module=".$this->db->escape($this->module)." AND var IN (";
			$count = 1;
			foreach ($fields_delete as $field => $value) {
				$query .= $this->db->escape($field);
				if ( $count !== count($fields_delete) ) {
					$query .= ", ";
				}
				$count++;
			}
			$query .= ")";
			$result = $this->db->query($query);
		}
		
		//update fields member var
		$this->fields = $this->fields + $data;
		
		return $this;
	}
	

	/**
	 * parse_result
	 *
	 * Converts the result set of a SELECT query in the options table to an
	 * assoc array of field => value.
	 *
	 * @access protected
	 * @param array $rows The assoc array of rows from a result set
	 * @return mixed The parsed options array or parameter value if empty or not array
	 */
	protected function parse_result($rows) {
		if ( empty($rows) || ! is_array($rows) ) {
			return $rows;
		}
		
		$parsed = array();
		foreach ($rows as $row) {
			$parsed[ $row['var'] ] = $row['value'];
		}
		
		return $parsed;
	}
}

/* End of file Options.php */
/* Location: ./App/Model/Options.php */