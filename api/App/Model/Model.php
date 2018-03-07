<?php

namespace App\Model;

use 
App\App,
App\Database\Database,
App\Exception\AppException,
App\Module\Module;

/**
 * Model class
 * 
 * Provides the database functions for a App\Module\Module object. The functions are limited to
 * insert, update and delete as well as setting rows active (show on frontend or not), archived
 * (showing in CMS AND frontend or not) and sorting of rows. This class also provides functions
 * to generate unique SEO friendly URI segments (slugs) for rows.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Model
 */
class Model {
	
    /**
     * @const int Max length of module slug (URI identifier segment)
     */
	const MODEL_MAX_SLUG_LENGTH = 64;

    /**
     * @const string Name of module field to set row active
     */
	const MODEL_ACTIVE_FIELD = 'is_active';

    /**
     * @const string Name of module field to archive row
     */
	const MODEL_ARCHIVE_FIELD = 'is_archive';
	
    /**
     * @const string Name of module field for timestamp of row creation
     */
	const MODEL_CREATED_FIELD = 'created';

    /**
     * @const string Name of module field to hold the slug (URI identifier segment)
     */
	const MODEL_SLUG_FIELD = 'slug';

    /**
     * @const string Name of module field to save the sorting order
     */	
	const MODEL_SORT_FIELD = 'sort_order';
	
    /**
     * @const string Name of module field for timestamp of last update
     */
	const MODEL_UPDATED_FIELD = 'updated';

    /**
     * @const string Not used as a field name but to hold file information for upload fields
     */
    const MODEL_UPLOADS_FIELD = 'uploads';
	
    /**
     * @const string Used to append to serialized data to check/verify serilization of a field
     */
	const SERIALIZED_MARKER = '%%';
	
    /**
     * @var string Name of table storing all slugs in application
     */
	protected static $SLUG_TABLE = 'slugs';
	
    /**
     * @var array Configuration for this model
     */
	protected $config;
	
    /**
     * @var \App\Database\Database The module database connection
     */
	protected $db;
	
    /**
     * @var string The charset for the module table
     */
	protected $db_charset;
	
    /**
     * @var array The module field names and corresponding default values
     */
	protected $fields;

    /**
     * @var boolean True if module uses the active field
     */
    protected $has_active;

    /**
     * @var boolean True if module uses the archive field
     */
    protected $has_archive;
	
    /**
     * @var boolean True if module uses the slug field
     */
	protected $has_slug;
	
    /**
     * @var boolean True if module uses the sort field
     */
	protected $has_sort;

    /**
     * @var array Storage for table field names reserved for use
     */
    protected $locked_fields = array();
	
    /**
     * @var string Module name
     */
	protected $module;
	
    /**
     * @var string Module field that stores the row primary key
     */
	protected $pk_field;

    /**
     * @var array Storage for table field names reserved for use
     */
	protected $reserved_fields = array();
	
    /**
     * @var string Module field used to generate the slug
     */
	protected $slug_field;
	
    /**
     * @var string Table name used by module
     */
	protected $table_name;
	
    /**
     * @var string Prefix used before each table name
     */
	protected $table_prefix;
	
    /**
     * @var string Module field used to identify row by name
     */
	protected $title_field;

	
	/**
	 * Constructor
	 *
	 * Initializes the Model with the given configuration parameters in assoc array:<br/><br/>
	 * <ul>
	 * <li>module => The module utilizing this model, which is the same name as the database table name</li>
	 * <li>fields => Assoc array of all table fields and corresponding default value</li>
	 * <li>pk_field => The primary key field used in the table</li>
	 * <li>title_field => Table field used to identify row by name</li>
	 * <li>slug_field => (Optional) Table field used to generate unique URI segment (slug) for row, 
	 * defaults to title_field</li>
	 * <li>use_sort => (Optional) True to allow sorting of table rows</li>
	 * <li>database => (Optional) The database configuration param defined in $config[database][use_this_param] in 
	 * ./App/config.php if using a connection other than default connection</li>
	 * </ul><br/><br/>
	 * Note that to use a generated slug, "slug" must be defined as one of the fields in the $config[fields] array.
	 * 
	 * @access public
	 * @param array $config The model configuration array
	 * @throws \App\Exception\AppException if $config assoc array missing required parameters
	 */
	public function __construct($config) {
		$app = App::get_instance();
		$errors = array();
		
		if ( empty($config['module']) ) {
			$errors[] = '[module] empty, must be name of module utilizing this model';
		}
		if ( empty($config['fields']) || ! is_array($config['fields']) ) {
			$errors[] = '[fields] empty or not array, must be assoc array of model field names and default values ['.$config['module'].']';
		}
		if ( empty($config['pk_field']) ) {
			$errors[] = '[pk_field] empty, must be name of model table primary key field ['.$config['module'].']';
		}
		if ( empty($config['title_field']) ) {
			$errors[] = '[title_field] empty, must be name of model field to identify row by name ['.$config['module'].']';
		}
		if ( ! empty($config['slug_field']) && ! array_key_exists($config['slug_field'], $config['fields']) ) {
			$errors[] = '[slug_field] must be name of model field, "'.$config['slug_field'].'" not found ['.$config['module'].']';
		}
		
		if ( ! empty($errors) ) {
			$message = 'Invalid param (array) $config'.implode("\n", $errors);
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$db_config = empty($config['database']) ? $app->config('db_config') : $config['database'];
		$this->db = Database::connection($db_config);
		$this->config = $app->load_config('model');
		$this->table_prefix = $app->config('db_table_prefix');
		$this->db_charset = empty($db_config['charset']) ? 'utf8' : $db_config['charset'];
		$this->module = $config['module'];
		$this->table_name = (empty($this->table_prefix) ? '' : $this->table_prefix).$this->module;
		$this->pk_field = $config['pk_field'];
		$this->title_field = $config['title_field'];
		$this->fields = $config['fields'];
        $this->has_active = empty($config['use_active']) ? false : true;
        $this->has_archive = empty($config['use_archive']) ? false : true;
		$this->has_sort = empty($config['use_sort']) ? false : true;
		$this->has_slug = empty($config['slug_field']) ? false : true;
		$this->slug_field = $this->has_slug ? $config['slug_field'] : '';

        // set fields only used internally by model and App
		$this->locked_fields = array(
			self::MODEL_CREATED_FIELD, 
			self::MODEL_SLUG_FIELD, 
			self::MODEL_UPDATED_FIELD
		);

        // set fields that cannot be used as module field names in models
        $this->reserved_fields = array(
            self::MODEL_ACTIVE_FIELD,
            self::MODEL_ARCHIVE_FIELD,
            self::MODEL_SORT_FIELD,
            self::MODEL_UPLOADS_FIELD
         );
        $this->reserved_fields = array_merge($this->reserved_fields, $this->locked_fields);

		//add pk field to fields array
		if ( ! array_key_exists($this->pk_field, $this->fields) ) {
            $this->fields = array($this->pk_field => '') + $this->fields;
		}

		if ($this->has_active) {
            $this->fields[self::MODEL_ACTIVE_FIELD] = 1;
        }

        if ($this->has_archive) {
            $this->fields[self::MODEL_ARCHIVE_FIELD] = 0;
        }

        //add locked fields to fields array since applies to all tables
		foreach ($this->locked_fields as $field) {
			$this->fields[$field] = '';
		}
		
		//prefix table prefix to slug table
		if ( ! empty($this->table_prefix) && 
			 substr(self::$SLUG_TABLE, 0, strlen($this->table_prefix) ) !== $this->table_prefix) {
			self::$SLUG_TABLE = $this->table_prefix.self::$SLUG_TABLE;
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
	 * alter_table
	 *
	 * Modifies the table name and/or field definitions of the model table.
	 *
	 * @access public
	 * @param array $model_config The model configuration assoc array, similar to constructor
	 * @param array $options Multidimenional array of options, fields and parameter:<br/><br/>
	 * [option1] => [ string | array(<br/>
	 * &nbsp;&nbsp;[field1] => array(<br/>
	 * &nbsp;&nbsp;&nbsp;&nbsp;[param1] => [value1],
	 * &nbsp;&nbsp;&nbsp;&nbsp;[param2] => ...,
	 * &nbsp;&nbsp;),
	 * &nbsp;&nbsp;[field2] => ...
	 * ) ],
	 * [option2] => ...<br/><br/>
	 * <strong>Options</strong><br/><br/>
	 * <ul>
	 * <li>add => Adds a table field</li>
	 * <li>change => Changes the field name and, optionally, field type</li>
	 * <li>drop => Drops the field from the table</li>
	 * <li>modify => Modifies the field type</li>
	 * <li>rename => Renames the model table, NOTE only accepts a string param as a value (new table name)</li>
	 * </ul>
	 * <ul><br/><br/>
	 * <strong>Fields</strong><br/><br/>
	 * The model fields to be modified.
	 * <strong>Parameters</strong><br/><br/>
	 * <li>type => SQL data type corresponding to those found in ./App/Config/model.php</li>
	 * <li>length => (Optional) field length for VARCHAR, INT and FLOAT type</li>
	 * <li>values => Array of values, required for type ENUM</li>
	 * <li>default => (Optional) default field value</li>
	 * </ul>
	 * <br/><br/>
	 * Note that invalid parameters values are ignored.
	 * @return boolean True if operation successful or false if $options param empty or has invalid data
	 * or an App\Exception\SQLException is passed and to be handled by \App\App class if an SQL error occurred
	 * @see __construct() for model configuration parameters
	 */
	public static function alter_table($model_config, $options) {
		if ( empty($model_config) || empty($options) ) {
			return false;
		}
		
		$model = new self($model_config);
		$ops = array();
		foreach ($options as $option => $fields) {
			if ($option === 'add' || 
				$option === 'change' || 
				$option === 'drop' || 
				$option === 'modify' || 
				$option === 'rename') {
				$ops[$option] = $fields;
			}
		}
		
		$table_name = $model->get_property('table_name');
		$db = $model->get_property('db');
		$query = "ALTER TABLE ".$db->escape_identifier($table_name)."\n";

		$field_defs = $model->sql_column_def($ops);
		if ($field_defs === false) {
			return false;
		}
		
		$query .= $field_defs.";";
        self::log_query($query);
		$result = $db->query($query);
		
		return true;
	}
	

	/**
	 * create_table
	 *
	 * Static function that creates the model table and returns the Model object. 
	 * Note that if the table already exists, it can optionally be dropped and 
	 * recreated.
	 *
	 * @access public
	 * @param array $model_config The model configuration assoc array, similar to constructor
	 * but without [fields] parameter
	 * @param array $fields The array of table fields => parameters:<br/><br/>
	 * <ul>
	 * <li>type => SQL data type corresponding to those found in ./App/Config/model.php</li>
	 * <li>length => (Optional) field length for VARCHAR, INT and FLOAT type</li>
	 * <li>values => Array of values, required for type ENUM</li>
	 * <li>default => (Optional) default field value</li>
	 * </ul>
	 * @param boolean $drop_if_exists True to first drop table if exists before creating
	 * @return mixed The Model object if create successful, false if operation not performed
	 * or an App\Exception\SQLException is passed and to be handled by \App\App class if an SQL error occurred
	 * @see __construct() for model configuration parameters
	 */
	public static function create_table($model_config, $fields, $drop_if_exists=false) {
		if ( empty($model_config) || empty($fields) ) {
			return false;
		}
		
		$model_fields = array();
		foreach ($fields as $field => $params) {
			$model_fields[$field] = $params['default'];
		}
		$model_config['fields'] = $model_fields;
		$model = new self($model_config);
		
		$field_defs = $model->sql_column_def( array('create' => $fields) );
		if ($field_defs === false) {
			return false;
		}
		
		//DROP table if exists
		if ($drop_if_exists) {
			$model->drop_table($model_config);
		}
		
		$db = $model->get_property('db');
		$table_name = $model->get_property('table_name');
		$pk_field = $model->get_property('pk_field');
		$db_charset = $model->get_property('db_charset');
		$slug_field = $db->escape_identifier(self::MODEL_SLUG_FIELD);
		
		$query = "CREATE TABLE ".$db->escape_identifier($table_name)." (\n";
		$query .= $db->escape_identifier($pk_field)." int(15) NOT NULL AUTO_INCREMENT,\n";
		$query .= $field_defs.",\n";
		$query .= $db->escape_identifier(self::MODEL_ACTIVE_FIELD)." tinyint(1) NOT NULL DEFAULT '1',\n";
		$query .= $db->escape_identifier(self::MODEL_ARCHIVE_FIELD)." tinyint(1) NOT NULL DEFAULT '0',\n";
		$query .= $db->escape_identifier(self::MODEL_SORT_FIELD)." int(15) NOT NULL DEFAULT '0',\n";
		$query .= $slug_field." varchar(64) NOT NULL,\n";
		$query .= $db->escape_identifier(self::MODEL_CREATED_FIELD)." datetime NOT NULL,\n";
		$query .= $db->escape_identifier(self::MODEL_UPDATED_FIELD)." timestamp NOT NULL ";
		$query .= "DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
		$query .= "PRIMARY KEY(".$db->escape_identifier($pk_field)."),\n";
		$query .= "KEY ".$slug_field." (".$slug_field.")\n";
		$query .= ") ENGINE=InnoDB  DEFAULT CHARSET=".$db->escape_str($db_charset).";";
        self::log_query($query);
		$result = $db->query($query);
		return $model;
	}
	
	
	/**
	 * delete
	 *
	 * Deletes a row or multiple rows from a given id or array of ids.
	 *
	 * @access public
	 * @param mixed $mixed The row id or array of ids
	 * @return boolean True if delete successful, false if not or an 
	 * App\Exception\SQLException is passed and to be handled by 
	 * \App\App class if an SQL error occurred
	 */
	public function delete($mixed) {
		if ( empty($mixed)) {
			return false;
		}
		
		$ids = is_array($mixed) ? $mixed : array($mixed);
		$query = "DELETE FROM ".$this->db->escape_identifier($this->table_name)." ";
		$query .= "WHERE ".$this->db->escape_identifier($this->pk_field)." IN (";
		$query .= $this->db->escape_str(implode(", ", $ids)).")";
		$result = $this->db->query($query);
		
		//delete slug, if used
		if ($this->has_slug) {
			$this->delete_slug($ids);
		}
		
		return is_numeric($result);
	}
	
	
	/**
	 * drop_table
	 *
	 * Deletes the model table.
	 *
	 * @access public
	 * @param array $model_config The model configuration assoc array, similar to constructor
	 * @return boolean True if operation successful or an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 * @see __construct() for model configuration parameters
	 */
	public static function drop_table($model_config) {
		if ( empty($model_config) ) {
			return false;
		}
		
		$model = new self($model_config);
		$table_name = $model->get_property('table_name');
		$db = $model->get_property('db');
		$query = "DROP TABLE IF EXISTS ".$db->escape_identifier($table_name);
        self::log_query($query);
		$result = $db->query($query);
		return true;
	}
	

	/**
	 * get
	 *
	 * Retrieves a row from a given id or slug.
	 *
	 * @access public
	 * @param mixed $id The row id or slug
	 * @param boolean $is_slug True to use slug to retrieve row, defaults to false
	 * @return mixed The associative array for the row or false if row not found or an
	 * App\Exception\SQLException is passed and to be handled by \App\App class if an 
	 * SQL error occurred
	 */
	public function get($id, $is_slug=false) {
		if ( empty($id) ) {
			return false;
		}
		
		$query = "SELECT ";
		$query_fields = array();
		$pk_field = $is_slug ? self::MODEL_SLUG_FIELD : $this->pk_field;
		foreach ($this->fields as $field => $value) {
			$query_fields[] = $this->db->escape_identifier($field);
		}
		
		$query .= implode(", ", $query_fields)." ";
		$query .= "FROM ".$this->db->escape_identifier($this->table_name)." ";
		$query .= "WHERE ".$this->db->escape_identifier($pk_field)."=";
		$query .= $this->db->escape($id)." LIMIT 1";
        self::log_query($query);
		$result = $this->db->query($query);
		$row = $this->parse_result($result, true);
		return empty($row) ? false : $row;
	}
	

	/**
	 * get_id_list
	 *
	 * Returns an array of [$this->pk_field] => [$this->title_field] for use in form dropdown selects.
	 *
	 * @access public
	 * @return array The array of IDs and corresponding names or an
	 * App\Exception\SQLException is passed and to be handled by \App\App class if 
	 * an SQL error occurred
	 */
	public function get_id_list() {
		$query = "SELECT ";
		$query .= $this->db->escape_identifier($this->pk_field).", ";
		$query .= $this->db->escape_identifier($this->title_field)." ";
		$query .= "FROM ".$this->db->escape_identifier($this->table_name)." ";
		$query .= "ORDER BY ".$this->db->escape_identifier($this->title_field)." ASC";
		$result = $this->db->query($query);
		
		$list = array();
		if ($result->num_rows() > 0) {
			$rows = $result->result_assoc();
			foreach ($rows as $row) {
				$pk = $row[$this->pk_field];
				$list[$pk] = $row[$this->title_field];
			}
		}

		return $list;
	}


    /**
     * get_reserved_fields
     *
     * Returns an array of field names reserved for the application Model.
     *
     * @access public
     * @return array The array of reserved field names
     */
	public function get_reserved_fields() {
	    return $this->reserved_fields;
    }
	

	/**
	 * get_rows
	 *
	 * Retrieves a result set from an optional assoc array of parameters which are the following:<br/><br/>
	 * <ul>
	 * <li>fields => (Optional) The array of fields to retrieve, default is all fields</li>
	 * <li>
	 * where => (Optional) The condition(s) for the query
	 *   <ul>
	 *   <li>equals => Join conditions using equals (=) AND
	 *     <ul>
	 *       <li>field1 => value1</li>
	 *       <li>field2 => value2</li>
	 *       <li>...</li>
	 *       <li>_condition => Condition (AND/OR) used to join the above subconditions, if more than one</li>
	 *       <li>_outer_cnd => Condition (AND/OR) used to join outer PRECEDING subconditions (or, not, in, not_in, %like, etc), 
	 * if more than one</li>
	 *     </ul>
	 *   </li>
	 *   <li>or => Join conditions using equals (=) OR
	 *     <ul>
	 *       <li>field1 => value1</li>
	 *       <li>...</li>
	 *       <li>_condition => AND/OR</li>
	 *       <li>_outer_cnd => Condition (AND/OR) used to join PRECEDING outer subconditions (or, not, in, not_in, %like, etc), 
	 * if more than one</li>
	 *     </ul>
	 *   </li>
	 *   <li>not => Join conditions using !=
	 *     <ul>
	 *       <li>field1 => value1</li>
	 *       <li>...</li>
	 *       <li>_condition => AND/OR</li>
	 *       <li>_outer_cnd => Condition (AND/OR) used to join PRECEDING outer subconditions (or, not, in, not_in, %like, etc), 
	 * if more than one</li>
	 *     </ul>
	 *   </li>
	 *   <li>in => Join conditions using IN (value1, value2, ...)
	 *     <ul>
	 *       <li>field1 => array(value1, value2, ...)</li>
	 *       <li>...</li>
	 *       <li>_condition => AND/OR</li>
	 *       <li>_outer_cnd => Condition (AND/OR) used to join PRECEDING outer subconditions (or, not, in, not_in, %like, etc), 
	 * if more than one</li>
	 *     </ul>
	 *   </li>
	 *   <li>not_in => Join conditions using NOT IN (value1, value2, ...)
	 *     <ul>
	 *       <li>field1 => array(value1, value2, ...)</li>
	 *       <li>...</li>
	 *       <li>_condition => AND/OR</li>
	 *       <li>_outer_cnd => Condition (AND/OR) used to join PRECEDING outer subconditions (or, not, in, not_in, %like, etc), 
	 * if more than one</li>
	 *     </ul>
	 *   </li>
	 *   <li>%like% => Join conditions using field1 LIKE '%value1%'
	 *     <ul>
	 *       <li>field1 => value1</li>
	 *       <li>...</li>
	 *       <li>_condition => AND/OR</li>
	 *       <li>_outer_cnd => Condition (AND/OR) used to join PRECEDING outer subconditions (or, not, in, not_in, %like, etc), 
	 * if more than one</li>
	 *     </ul>
	 *   </li>
	 *   <li>%like => Join conditions using field1 LIKE '%value1'
	 *     <ul>
	 *       <li>field1 => value1</li>
	 *       <li>...</li>
	 *       <li>_condition => AND/OR</li>
	 *       <li>_outer_cnd => Condition (AND/OR) used to join PRECEDING outer subcondition (or, not, in, not_in, %like, etc), 
	 * if more than one</li>
	 *     </ul>
	 *   </li>
	 *   <li>like% => Join conditions using field1 LIKE 'value1%'
	 *     <ul>
	 *       <li>field1 => value1</li>
	 *       <li>...</li>
	 *       <li>_condition => AND/OR</li>
	 *       <li>_outer_cnd => Condition (AND/OR) used to join PRECEDING outer subconditions (or, not, in, not_in, %like, etc), 
	 * if more than one</li>
	 *     </ul>
	 *   </li>
	 *   <li>like => Join conditions using field1 LIKE 'value1'
	 *     <ul>
	 *       <li>field1 => value1</li>
	 *       <li>...</li>
	 *       <li>_condition => AND/OR</li>
	 *       <li>_outer_cnd => Condition (AND/OR) used to join PRECEDING outer subconditions (or, not, in, not_in, %like, etc), 
	 * if more than one</li>
	 *     </ul>
	 *   </li>
	 *   <ul>
	 * </li>
	 * <li>_condition => (Optional) Condition (AND/OR) used to join the above subconditions, if more than one. NOTE
	 * that _outer_cnd parameter in any condition will take precedence.</li>
	 * <li>order_by => (Optional) The field to order the query result</li>
	 * <li>is_asc => (Optional) True to order the result in ascending order (default), false descending</li>
	 * <li>offset => (Optional) The rows to retrieve starting at this row in the result</li>
	 * <li>limit => (Optional) The number of rows to retrieve in the result set</li>
	 * </ul>
	 * 
	 * @access public
	 * @param array $params The parameters for the query
	 * @return mixed The query result in an assoc array or false if query failed an
	 * App\Exception\SQLException is passed and to be handled by \App\App class if an 
	 * SQL error occurred
	 */
	public function get_rows($params=array()) {
		$where = empty($params['where']) ? array() : $params['where'];
		$query = "SELECT ";
		$valid_ops = array(
			'equals' 	=> '=', 
			'or' 		=> '=',  
			'not' 		=> '!=', 
			'in' 		=> 'in', 
			'not_in' 	=> 'not_in',  
			'%like%' 	=> '%like%',  
			'%like' 	=> '%like',  
			'like%' 	=> 'like%',  
			'like' 		=> 'like'
		);
		$query_where = array();
		$fields = empty($params['fields']) ? array_keys($this->fields) : $params['fields'];
		
		if ( ! empty($params['count']) ) {
			$query .= "COUNT(*) AS count ";
		} else if ( ! empty($params['fields']) ) {
			$q = $this->fields_arr_to_query($params['fields'], ", ");
			$query .= empty($q) ? "* " : $q." ";
		} else {
			$query .= "* ";
		}
		
		$query .= "FROM ".$this->db->escape_identifier($this->table_name)." ";
		
		$outer_conditions = array();
		foreach ($where as $operation => $p) {
			if ( isset($valid_ops[$operation]) === false ) {
				continue;
			}
			
			$condition = empty($p['_condition']) ? $this->sql_cond() : $this->sql_cond($p['_condition']);
			$outer_cnd = empty($p['_outer_cnd']) ? '' : $this->sql_cond($p['_outer_cnd']);

			$q = $this->fields_arr_to_query($p, " ".$condition." ", $valid_ops[$operation]);
			if ( ! empty($q) ) {
				$outer_conditions[] = $outer_cnd;
				$query_where[] = $q;
			}
		}
		
		if ( ! empty($query_where) ) {
			$subquery = "";
			$condition = empty($where['_condition']) ? $this->sql_cond() : $this->sql_cond($where['_condition']);
			$count = count($query_where);
			for ($i=0; $i < $count; $i++) {
				$subquery .= $query_where[$i];
				$cnd = empty($outer_conditions[$i+1]) ? $condition : $outer_conditions[$i+1];
				if ($i < $count - 1) {
					$subquery .= ") ".$cnd." (";
				}
			}
			$query .= "WHERE (".$subquery.") ";
		}
		
		/*
		if ( ! empty($where['equals']) ) {
			$condition = empty($where['equals']['_condition']) ? 
						 $this->sql_cond() : 
						 $this->sql_cond($where['equals']['_condition']);
			$q = $this->fields_arr_to_query($where['equals'], " ".$condition." ");
			if ( ! empty($q) ) {
				$query_where[] = $q;
			}
		}
		
		if ( ! empty($where['or']) ) {
			$condition = empty($where['or']['_condition']) ? 
						 $this->sql_cond() : 
						 $this->sql_cond($where['or']['_condition']);
			$q = $this->fields_arr_to_query($where['or'], " ".$condition." ");
			if ( ! empty($q) ) {
				$query_where[] = $q;
			}
		}
		
		if ( ! empty($where['not']) ) {
			$condition = empty($where['not']['_condition']) ? 
						 $this->sql_cond() : 
						 $this->sql_cond($where['not']['_condition']);
			$q = $this->fields_arr_to_query($where['not'], " ".$condition." ", "!=");
			if ( ! empty($q) ) {
				$query_where[] = $q;
			}
		}
		
		if ( ! empty($where['in']) ) {
			$condition = empty($where['in']['_condition']) ? 
						 $this->sql_cond() : 
						 $this->sql_cond($where['in']['_condition']);
			$q = $this->fields_arr_to_query($where['in'], " ".$condition." ", "in");
			if ( ! empty($q) ) {
				$query_where[] = $q;
			}
		}
		
		if ( ! empty($where['not_in']) ) {
			$condition = empty($where['not_in']['_condition']) ? 
						 $this->sql_cond() : 
						 $this->sql_cond($where['not_in']['_condition']);
			$q = $this->fields_arr_to_query($where['not_in'], " ".$condition." ", "not_in");
			if ( ! empty($q) ) {
				$query_where[] = $q;
			}
		}
		
		if ( ! empty($where['%like%']) ) {
			$condition = empty($where['%like%']['_condition']) ? 
						 $this->sql_cond() : 
						 $this->sql_cond($where['%like%']['_condition']);
			$q = $this->fields_arr_to_query($where['%like%'], " ".$condition." ", "%like%");
			if ( ! empty($q) ) {
				$query_where[] = $q;
			}
		}
		
		if ( ! empty($where['%like']) ) {
			$condition = empty($where['%like']['_condition']) ? 
						 $this->sql_cond() : 
						 $this->sql_cond($where['%like']['_condition']);
			$q = $this->fields_arr_to_query($where['%like'], " ".$condition." ", "%like");
			if ( ! empty($q) ) {
				$query_where[] = $q;
			}
		}
		
		if ( ! empty($where['like%']) ) {
			$condition = empty($where['like%']['_condition']) ? 
						 $this->sql_cond() : 
						 $this->sql_cond($where['like%']['_condition']);
			$q = $this->fields_arr_to_query($where['like%'], " ".$condition." ", "like%");
			if ( ! empty($q) ) {
				$query_where[] = $q;
			}
		}
		
		if ( ! empty($query_where) ) {
			$condition = empty($where['_condition']) ? false : $this->sql_cond($where['_condition']);
			if ( empty($condition) ) {
				$condition = "AND";
			}
			$query .= "WHERE (".implode(") ".$condition." (", $query_where).") ";
		}
		*/

		if ( ! empty($params['order_by']) && (in_array($params['order_by'], $this->reserved_fields) ||
            in_array($params['order_by'], $this->fields) ) ) {
			$query .= "ORDER BY ".$this->db->escape_identifier($params['order_by']);
			$query .= ! isset($params['is_asc']) || $params['is_asc'] ? " ASC " : " DESC ";
			if ($params['order_by'] !== $this->title_field) {
				$query .= ", ".$this->db->escape_identifier($this->title_field)." ASC ";
			}
		}
		
		if ( ! empty($params['limit']) ) {
			$offset = isset($params['offset']) ? (int) $params['offset'] : 0;
			$limit = (int) $params['limit'];
			$query .= "LIMIT ".$offset.", ".$limit;
		}

        self::log_query($query);
		$result = $this->db->query($query);

		$rows = $this->parse_result($result);
		return empty($params['count']) ? $rows : $rows[0]['count'];
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
			case 'db_charset' :
				$property = $this->db_charset;
				break;
			case 'fields' :
				$property = $this->fields;
				break;
			case 'has_sort' :
				$property = $this->has_sort;
				break;
			case 'module' :
				$property = $this->module;
				break;
			case 'pk_field' :
				$property = $this->pk_field;
				break;
			case 'slug_field' :
				$property = $this->slug_field;
				break;
			case 'table_name' :
				$property = $this->table_name;
				break;
			case 'title_field' :
				$property = $this->title_field;
				break;
		}
		
		return $property;
	}
	

	/**
	 * insert
	 *
	 * Inserts a database row from an assoc array of fields => values. This array
	 * is merged with default values set in $this->fields and each index is whitelisted,
	 * checking if existing in $this->fields to prevent SQL errors. 
	 *
	 * @access public
	 * @param array $data The fields and corresponding values to insert
	 * @return int The insert ID of the row or zero if query failed or an 
	 * App\Exception\SQLException is passed and to be handled by \App\App class 
	 * if an SQL error occurred
	 */
	public function insert($data) {
		if ( empty($data) ) {
			$data = array();
		}
		
		$data = $data + $this->fields;
		$query = "INSERT INTO ".$this->db->escape_identifier($this->table_name)." (";
		$query_fields = array();
		$query_values = array();
		
		foreach ($data as $field => $value) {
			if ( ! array_key_exists($field, $this->fields) ||
				in_array($field, $this->locked_fields) ||
				$field === $this->pk_field) {
				//1. If field not in list of fields of this table
				//2. Or field is a reserved field (slug URI segment, created, updated date/time)
				//3. Or if field is primary key
				//
				//...then skip
				
				continue;
			}
			
			if ( is_array($value) ) {
			//convert array to serialized data
				$value = $this->db->escape( $this->serialize($value) );
			} else if ( is_bool($value) ) {
			//convert boolean to 0 or 1
				$value = $value ? 1 : 0;
			} else if ( is_null($value) ) {
                $value = 'NULL';
            } else {
                $value = $this->db->escape($value);
            }
			$query_fields[] = $this->db->escape_identifier($field);
			$query_values[] = $value;
		}
		
		if ( empty($query_fields) ) {
			return false;
		}
		
		//determine sort order if model uses it
		if ($this->has_sort) {
			$count = 1;
			$q = "SELECT MAX(".$this->db->escape_identifier(self::MODEL_SORT_FIELD);
			$q .= ") + 1 AS count FROM ".$this->db->escape_identifier($this->table_name);
			$result = $this->db->query($q);
			if ($result->num_rows() > 0) {
				$row = $result->row();
				if ( is_numeric($row['count']) ) {
					$count = $row['count'];
				}
			}
			$query_fields[] = self::MODEL_SORT_FIELD;
			$query_values[] = $count;
		}
		
		//set current date/time to "created" reserved field
		$query_fields[] = self::MODEL_CREATED_FIELD;
		$query_values[] = 'NOW()';
		
		$query .= implode(", ", $query_fields).") VALUES (".implode(", ", $query_values).")";
        self::log_query($query);
		$result = $this->db->query($query);
		$pk = $this->db->insert_id();
		
		//add to slugs table, if used
		if ($this->has_slug && ! empty($pk) ) {
			$this->add_slug($pk, $data[$this->slug_field]);
		}
		
		return $pk;
	}
	
	
	/**
	 * parse_result
	 *
	 * Accepts a result set and parses the row or rows to exclude data,
	 * not pertaining to model, from each row.
	 *
	 * @access public
	 * @param \App\Database\Result Driver dependant query result set
	 * @param boolean $is_single_row True if query returns a single row
	 * @return array The row or array of rows, assoc array parsed
	 */
	public function parse_result($result, $is_single_row=false) {
		if ( is_subclass_of($result, '\App\Database\Driver\Result') === false) {
			return $result;
		}
		
		$rows = array();
		if ( $result->num_rows() > 0) {
			$rows = $result->result_assoc();
			foreach ($rows as &$row) {
				if ( ! array_key_exists(self::MODEL_ACTIVE_FIELD, $this->fields) ) {
					unset($row[self::MODEL_ACTIVE_FIELD]);
				}
				if ( ! array_key_exists(self::MODEL_ARCHIVE_FIELD, $this->fields) ) {
					unset($row[self::MODEL_ARCHIVE_FIELD]);
				}
				if ( ! $this->has_sort) {
					unset($row[self::MODEL_SORT_FIELD]);
				}
				if ( ! $this->has_slug) {
					unset($row[self::MODEL_SLUG_FIELD]);
				}
				$this->unserialize_row($row);
			}
		}
		
		return $is_single_row && isset($rows[0]) ? $rows[0] : $rows;
	}
	

	/**
	 * set_active
	 *
	 * Sets a row active/inactive given a row ID or array of row IDs. If this model does not
	 * use the active field in its table, this simply returns false.
	 *
	 * @access public
	 * @param mixed $mixed The row ID or array or IDs to set active/inactive
	 * @param boolean $to_active True to set active, false for inactive
	 * @return boolean True if operation successful or an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 */
	public function set_active($mixed, $to_active=true) {
		if ( ! array_key_exists(self::MODEL_ACTIVE_FIELD, $this->fields) || empty($mixed)) {
			return false;
		}
		
		$ids = is_array($mixed) ? $mixed : array($mixed);
		$query = "UPDATE ".$this->db->escape_identifier($this->table_name)." SET ";
		$query .= $this->db->escape_identifier(self::MODEL_ACTIVE_FIELD)."='".($to_active ? '1' : '0')."' ";
		$query .= "WHERE ".$this->db->escape_identifier($this->pk_field)." ";
		$query .= "IN (".$this->db->escape_str(implode(", ", $ids)).")";
		$result = $this->db->query($query);
		
		return is_numeric($result);
	}
	

	/**
	 * set_archive
	 *
	 * Sets a row archived/unarchived given a row ID or array of row IDs. If this model does not
	 * use the archive field in its table, this simply returns false.
	 *
	 * @access public
	 * @param mixed $mixed The row ID or array or IDs to set archived/unarchived
	 * @param boolean $to_active True to set archived, false for unarchived
	 * @return boolean True if operation successful or an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 */
	public function set_archive($mixed, $to_archive=true) {
		if ( ! array_key_exists(self::MODEL_ARCHIVE_FIELD, $this->fields) || empty($mixed)) {
			return false;
		}
		
		$ids = is_array($mixed) ? $mixed : array($mixed);
		$query = "UPDATE ".$this->db->escape_identifier($this->table_name)." SET ";
		$query .= $this->db->escape_identifier(self::MODEL_ARCHIVE_FIELD)."='".($to_archive ? '1' : '0')."' ";
		$query .= "WHERE ".$this->db->escape_identifier($this->pk_field)." ";
		$query .= "IN (".$this->db->escape_str(implode(", ", $ids)).")";
		$result = $this->db->query($query);
		
		return is_numeric($result);
	}
	

	/**
	 * set_sort_order
	 *
	 * Sets the sort order field for multiple rows given an array of row IDs. The order
	 * is determined in the order of the IDs in the array.
	 *
	 * @access public
	 * @param array $ids The array of row IDs, in sorted order
	 * @return boolean True if operation successful or an App\Exception\SQLException
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 */
	public function set_sort_order($ids) {
		if ( empty($ids)) {
			return false;
		}
		
		$has_error = false;
		
		for ($i=0; $i < count($ids); $i++) {
			$query = "UPDATE ".$this->db->escape_identifier($this->table_name)." SET ";
			$query .= $this->db->escape_identifier(self::MODEL_SORT_FIELD)."=";
			$query .= $this->db->escape($i+1)." ";
			$query .= "WHERE ".$this->db->escape_identifier($this->pk_field)."=".$this->db->escape($ids[$i]);
			$result = $this->db->query($query);

			if ( ! is_numeric($result) ) {
				$has_error = true;
			}
		}
		
		return $has_error === false;
	}
	

	/**
	 * update
	 *
	 * Updates a database row from an assoc array of fields => values. Each of the
	 * array's indeces is whitelisted, checking if existing in $this->fields to prevent 
	 * SQL errors. The primary key field/value must be an index of this array or false
	 * will be returned. Note that some or all fields of the row can be updated.
	 *
	 * @access public
	 * @param array $data The fields and corresponding values to update
	 * @return boolean True if update successful, false if not or primary key field missing in $data param or 
	 * an App\Exception\SQLException is passed and to be handled by \App\App class if an SQL error occurred
	 */
	public function update($data) {
		if ( empty($data) || empty($data[$this->pk_field]) ) {
			return false;
		}

		$query = "UPDATE ".$this->db->escape_identifier($this->table_name)." SET ";
		$query_fields = array();
		
		foreach ($data as $field => $value) {
			if ( ! array_key_exists($field, $this->fields) ||
				in_array($field, $this->locked_fields) ||
				$field === $this->pk_field) {
				//1. If field not in list of fields of this table
				//2. Or field is a reserved field (slug URI segment, created, updated date/time)
				//3. Or if field is primary key
				//
				//...then skip
				
				continue;
			}
			
			if ( is_array($value) ) {
			//convert array to serialized data
				$value = $this->db->escape( $this->serialize($value) );
			} else if ( is_bool($value) ) {
			//convert boolean to 0 or 1
				$value = $value ? 1 : 0;
			} else if ( is_null($value) ) {
                $value = 'NULL';
            } else {
                $value = $this->db->escape($value);
            }
			
			$query_fields[] = $this->db->escape_identifier($field)."=".$value;
		}
		
		if ( empty($query_fields) ) {
			return false;
		}
		
		$query .= implode(", ", $query_fields)." ";
		$query .= "WHERE ".$this->db->escape_identifier($this->pk_field)."=";
		$query .= $this->db->escape($data[$this->pk_field]);
        self::log_query($query);
		$result = $this->db->query($query);
		
		//update slugs table, if used
		if ($this->has_slug && ! empty($data[$this->slug_field]) ) {
			$this->update_slug($data[$this->pk_field], $data[$this->slug_field]);
		}
		
		return is_numeric($result);
	}
	

	/**
	 * add_slug
	 *
	 * Creates a SEO friendly URI segment, or slug, to uniquely identify a row for this model. 
	 * Upon creation, the slug is stored in the row and a table of all slugs with corresponding
	 * module and row ID.
	 *
	 * @access protected
	 * @param int $id The row ID
	 * @param string $text The text to convert to the slug
	 * @return boolean True if operation successful or an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 */
	protected function add_slug($id, $text) {
		if ( empty($id) || empty($text) ) {
			return false;
		}

		$slug = $this->create_slug($id, $text);
		//$this->db->query("LOCK TABLE ".$this->db->escape_identifier(self::$SLUG_TABLE)." WRITE");
		$query = "INSERT INTO ".$this->db->escape_identifier(self::$SLUG_TABLE)." (module, row_id, slug) VALUES (";
		$query .= $this->db->escape($this->module).", ";
		$query .= $this->db->escape($id).", ";
		$query .= $this->db->escape($slug).")";
		$result = $this->db->query($query);
		//$this->db->query("UNLOCK TABLES");
		
		//update subclassed slug field
		$this->update_module_slug($id, $slug);
		
		return is_numeric($result);
	}
	

	/**
	 * create_slug
	 *
	 * Creates creates a unique slug by quering the slug table for duplicates of a given
	 * generated slug by module. If a duplicate is found, the slug will be appended with 
	 * a slash and 1, 2... until a unique value is found.
	 *
	 * @access protected
	 * @param int $id The row ID
	 * @param string $text The text to convert to the slug
	 * @return boolean True if operation successful or an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 */
	protected function create_slug($id, $text) {
		if ( empty($text) ) {
			return $text;
		}
		
		$slug = $this->safe_uri($text);
		$slug = substr(strtolower($slug), 0, self::MODEL_MAX_SLUG_LENGTH);	
		$reserved_uris = $this->get_reserved_uris();
		$to_check = $slug;
		$has_dupe = true;
		$count = 1;
		
		while ($has_dupe) {
			if ($this->module === Module::MAIN || ! in_array($to_check, $reserved_uris, true)) {
				$query = "SELECT COUNT(*) AS count FROM ";
				$query .= $this->db->escape_identifier(self::$SLUG_TABLE)." ";
				$query .= "WHERE slug=".$this->db->escape($to_check)." ";
				$query .= "AND module=".$this->db->escape($this->module)." ";
				$query .= "AND row_id!=".$this->db->escape($id);
				
				$rs = $this->db->query($query);
				$row = $rs->row();
				$has_dupe = $row['count'] > 0;
			}
			
			if ($has_dupe) {
				$new_slug = $slug;
				
				//check if slug has a "-[number]" ending 
				//and incrementit if it does
				$parts = explode('-', $new_slug);
				$end = $parts[count($parts)-1];
				if ( is_numeric($end) ) {
					$parts = array_splice($parts, 0, count($parts)-1);
					$count = $end + 1;
					$new_slug = implode('-', $parts);
				}
				
				$ext = '-'.$count++;
				$len = strlen($new_slug.$ext);
				
				if ($len > self::MODEL_MAX_SLUG_LENGTH) {
					$diff = $len - self::MODEL_MAX_SLUG_LENGTH;
					$new_slug = substr($new_slug, 0, self::MODEL_MAX_SLUG_LENGTH-$diff);
				}
				
				$to_check = $new_slug.$ext;
			}
		}
		
		return $to_check;
	}
	

	/**
	 * delete_slug
	 *
	 * Deletes a slug or multiple slugs from the slug table given the model row
	 * ID or array of row IDs.
	 *
	 * @access protected
	 * @param mixed $mixed The row ID or array of row IDs
	 * @return boolean True if operation successful or an App\Exception\SQLException 
	 * is passed and to behandled by \App\App class if an SQL error occurred
	 */
	protected function delete_slug($mixed) {
		if ( empty($mixed)) {
			return false;
		}
		
		$ids = is_array($mixed) ? $mixed : array($mixed);
		$query = "DELETE FROM ".$this->db->escape_identifier(self::$SLUG_TABLE)." ";
		$query .= "WHERE module=".$this->db->escape($this->module)." ";
		$query .= "AND row_id IN (".$this->db->escape_str(implode(", ", $ids)).")";
		$result = $this->db->query($query);
		
		return is_numeric($result);
	}
	

	/**
	 * fields_arr_to_query
	 *
	 * Converts an array of field/value pairs into a portion of the WHERE clause of
	 * an SQL statement.
	 *
	 * @access protected
	 * @param array $arr The array of field/value pairs
	 * @param string $separator The string used to separate the conditionals
	 * @param string $operator The operator to use for each field/value pair ("=", "!=", ...)
	 * @return string The SQL string
	 */
	protected function fields_arr_to_query($arr, $separator=", ", $operator="=") {
		if ( empty($arr) || ! is_array($arr) ) {
			return "";
		}
		
		$query_fields = array();
		$has_vals = is_numeric( key($arr) ) === false;

		foreach ($arr as $x => $y) {
			$field = $has_vals ? $x : $y;
			if ( $field === '_condition' || $field === '_outer_cnd' ||
                ( ! array_key_exists($field, $this->fields) && ! array_key_exists($field, $this->reserved_fields) ) ) {
			    //field doesn't exist or "_condition" used instead for AND/OR in query
				continue;
			}
			$q = $this->db->escape_identifier($field);
			if ($has_vals) {
				if ( strpos($operator, "like") !== false) {
					$val = $this->db->escape_str($y);
					
					if ($operator === '%like%') {
						$q .= " LIKE '%".$val."%'";
					} else if ($operator === 'like%') {
						$q .= " LIKE '".$val."%'";
					} else if ($operator === '%like') {
						$q .= " LIKE '%".$val."'";
					} else if ($operator === 'like') {
						$q .= " LIKE '".$val."'";
					}
				} else if ($operator === 'in' || $operator === 'not_in') {
					$vals = is_array($y) ? $y : array($y);
					$q .= ($operator === 'not_in' ? " NOT" : "")." IN (";
					foreach ($vals as $i => $val) {
						$q .= $this->db->escape($val);
						if ($i < count($vals) - 1) {
							$q .= ", ";
						}
					}
					$q .= ")";
				} else {
					$q .= $operator.$this->db->escape($y);
				}
			}
			$query_fields[] = $q;
		} 

		return empty($query_fields) ? "" : implode($separator, $query_fields);
	}
	

	/**
	 * get_reserved_uris
	 *
	 * Returns the "reserved" URIs or names that cannot be used for a slug which are the
	 * module names.
	 *
	 * @access protected
	 * @return array The reserved URI, or module names or an App\Exception\SQLException 
	 * is passed and to behandled by \App\App class if an SQL error occurred
	 */
	protected function get_reserved_uris() {
		$query = "SELECT DISTINCT module FROM ".$this->db->escape_identifier(self::$SLUG_TABLE);
		$rs = $this->db->query($query);
		$rows = $rs->result_assoc();
		$slugs = array();
		
		if ($rs->num_rows() > 0) {
			foreach ($rows as $row) {
				$slugs[] = $row['module'];
			}
		}
		
		return $slugs;
	}


    /**
     * log_query
     *
     * Writes a query to the query log if activated in config.php config file.
     *
     * @access protected
     * @param string $query The query to log
     * @return void
     */
    protected static function log_query($query) {
        $App = App::get_instance();
        $log_file = $App->config('db_query_log');
        if ( $App->config('log_queries') === true && ! empty($log_file) ) {
            error_log($query."\n", 3, $log_file);
        }
    }
	

	/**
	 * safe_uri
	 *
	 * Takes a given string and converts it to a properly formatted URI segment which is
	 * lowercase and contains only values a-z, 0-9, underscore and dash. Special foreign
	 * characters are converted to their English equivalents. This function is primarily
	 * for generating a slug.
	 *
	 * @access protected
	 * @param string $str The string to convert to URI segment
	 * @return string The converted string
	 */
	protected function safe_uri($str) {
		if ( empty($str) ) {
			return $str;
		}
		
		$str = trim($str);
		
		//replace foreign chars with english equivalents
		$str = preg_replace(
			'~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', 
			'$1', 
			htmlentities($str, ENT_NOQUOTES, 'UTF-8')
		);
		
		$str = str_replace(" ", "-", $str);	
		$str = strtolower($str);			
		$safe_uri = "";
		$allowed = "0123456789abcdefghijklmnopqrstuvwxyz_-";
		for ($i=0; $i < strlen($str); $i++) {
			$char = substr($str, $i, 1);
			$pos = strpos($allowed, $char);
			if ($pos !== false) {
				$safe_uri .= $char;
			}
		}
				
		return $safe_uri;
	}
	
	
	/**
	 * serialize
	 *
	 * Serializes an array or multidimensional array of literal values for storage as 
	 * a string in the database. Uses the PHP json_encode() and appends it with an MD5
	 * of the newly encoded string and the string length in the format:<br/>
	 * <br/>
	 * '%%[md5($str)].[strlen($str)]%%'<br/>
	 * <br/>
	 * Note that non-arrays passed in are returned as is.
	 * 
	 * @access protected
	 * @param mixed $data The data to serialize
	 * @return string The serialized data, 
	 */
	protected function serialize($data) {
		if ( ! is_array($data) ) {
			return $data;
		}

		$json = json_encode($data);
		return $json.self::SERIALIZED_MARKER.md5($json).strlen($json).self::SERIALIZED_MARKER;
	}
	
	
	/**
	 * serialize_row
	 *
	 * Accepts an associative array of database row field values and converts any 
	 * array values into a serialized string.
	 * 
	 * @access protected
	 * @param array $row The associative array
	 * @return array The array where array values are serialized
	 */
	protected function serialize_row(&$row) {
		if ( ! is_array($row) ) {
			return $row;
		}

		foreach ($row as $key => $mixed) {
			if ( ! is_array($mixed) ) {
				continue;
			}
			$row[$key] = $this->serialize($mixed);
		}
		
		return $row;
	}
	
	
	/**
	 * sql_column_def
	 *
	 * Returns the field options and definitions part of an SQL query for CREATE or ALTER table.
	 *
	 * @access protected
	 * @param array $options Multidimenional array of options, fields and parameter:<br/><br/>
	 * [option1] => [ string | array(<br/>
	 * &nbsp;&nbsp;[field1] => array(<br/>
	 * &nbsp;&nbsp;&nbsp;&nbsp;[param1] => [value1],
	 * &nbsp;&nbsp;&nbsp;&nbsp;[param2] => ...,
	 * &nbsp;&nbsp;),
	 * &nbsp;&nbsp;[field2] => ...
	 * ) ],
	 * [option2] => ...<br/><br/>
	 * <strong>Options</strong><br/><br/>
	 * <ul>
	 * <li>add => Adds a table field</li>
	 * <li>change => Changes the field name and, optionally, field type</li>
	 * <li>create => Fields used in CREATE table with no option for changes</li>
	 * <li>drop => Drops the field from the table</li>
	 * <li>modify => Modifies the field type</li>
	 * <li>rename => Renames the model table, NOTE only accepts a string param as a value (new table name)</li>
	 * </ul>
	 * <ul><br/><br/>
	 * <strong>Fields</strong><br/><br/>
	 * The model fields to be modified.
	 * <strong>Parameters</strong><br/><br/>
	 * <li>type => SQL data type corresponding to those found in ./App/Config/model.php</li>
	 * <li>length => (Optional) field length for VARCHAR, INT and FLOAT type</li>
	 * <li>values => Array of values, required for type ENUM</li>
	 * <li>default => (Optional) default field value</li>
	 * </ul>
	 * @return mixed The SQL query part or false if $options param empty or has invalid data
	 * @throws \App\Exception\AppException if $config[data_type] config not found in ./App/Config/model.php
	 */
	protected function sql_column_def($options) {
		$data_type = empty($this->config['data_types']) ? false : $this->config['data_types'];
		$arr = array();
		$change_fields = array();

		if ($data_type === false) {
			$mesage = 'Config $config[data_type] not found in ./App/Config/model.php';
			throw new AppException($mesage, AppException::ERROR_FATAL);
		}
		
		foreach ($options as $option => $mixed) {
			$op_str = "";
			
			switch($option){
				case 'add':
					$op_str .= "ADD COLUMN ";
					break;
				case 'change':
					$op_str .= "CHANGE COLUMN ";
					break;
				case 'modify':
					$op_str .= "MODIFY COLUMN ";
					break;
				case 'drop':
					$op_str .= "DROP COLUMN ";
					break;
				case 'rename':
					//rename table
					if ( is_string($mixed) ) {
						$this->module = $mixed;
						$this->table_name = $this->table_prefix.$mixed;
						$arr[] = "RENAME TO ".$this->db->escape_identifier($this->table_name);
					}
					continue 2;
				case 'create':
				default:
					$option = 'create';
					break;
			}
			
			foreach ($mixed as $field => $params) {
				$type = empty($params['type']) ? false : $params['type'];
				$new_field = "";
				$qp = $op_str;
				
				if ($option !== 'drop' && ($type === false || ! array_key_exists($type, $data_type) ) ) {
				//no field definitions provided, skip
					continue;
				} else {
					//for add or change columns, need to 
					//add field to class fields array
					//
					if ($option === 'add') {
						$field = $this->sql_safe_indentifier($field);
						$this->fields[] = $field;
					} else if ($option === 'change') {
						if ( ! empty($params['new_name']) && is_string($params['new_name']) ) {
							$new_field = $this->sql_safe_indentifier($params['new_name']);
							$this->fields[] = $new_field;
							unset($this->fields[$field]);
						} else {
						//no new field name provided, skip
							continue;
						}
						
					//for create or modify columns, need to make sure
					//not a reserved model field and field exists in model
					//
					} else if ( ! array_key_exists($field, $this->fields) ||
						$field === self::MODEL_ACTIVE_FIELD ||
						$field === self::MODEL_ARCHIVE_FIELD ||
						$field === self::MODEL_SORT_FIELD ||
						$field === self::MODEL_SLUG_FIELD ||
						in_array($field, $this->locked_fields) ||
						$field === $this->pk_field) {
						//1. If field not in list of fields of this table
						//2. Or if field holds the active setting
						//3. Or if field holds the archive setting
						//4. Or if field holds the sorting value
						//5. Or if field holds the slug value
						//6. Or field is a reserved field (created, updated date/time)
						//7. Or if field is primary key
						//8. Or if field data type empty or invalid
						//
						//...then skip
						continue;
					}
				}
				
				if ($option === 'drop') {
					$arr[] = $op_str.$this->db->escape_identifier($field);
					continue;
				}
				
				$dt = $data_type[$type];
                $default = array_key_exists('default', $params) ?
                           $params['default'] :
                           (array_key_exists('default', $dt) ? $dt['default'] : false);
				$vals = "";

				if ($option === 'change') {
					$of = $this->db->escape_identifier($field);
					$nf = $this->db->escape_identifier($new_field);
					$qp .= $of." ".$nf." ".$this->db->escape_str($type);
					$change_fields[$of] = $nf;
				} else {
					$qp .= $this->db->escape_identifier($field)." ".$this->db->escape_str($type);
				}
				if ( isset($dt['max_length']) ) {
					$vals .= ! empty($params['length']) && 
							 is_numeric($params['length']) &&
							 $params['length'] < $dt['max_length'] ? 
							 $this->db->escape_str($params['length']) : 
							 $this->db->escape_str($dt['length']);
				} else if ( isset($dt['length']) ) {
					$vals .= $this->db->escape_str($dt['length']);
					
					if ( isset($dt['decimals']) ) {
						$vals .= ", ".$this->db->escape_str($dt['decimals']);
					}
				} else if ( isset($dt['values']) && is_array($params['values']) ) {
					$r = array();
					foreach ($params['values'] as $v) {
						$r[] = $this->db->escape($v);
					}
					$vals .= implode(", ", $r);
				}
				
				if ( ! empty($vals) ) {
					$qp .= "(".$vals.")";
				}

				$qp .= $default === NULL ? " NULL" : " NOT NULL";
				
				if ($default !== false && $default !== '' && ! in_array($type, array('text', 'mediumtext') ) ) {
				//if default false or empty string then set as no-value default values
                    if ( is_array($default) ) {
                     //convert array defaults to JSON string
                        $default = json_encode($default);
                    }
                    $qp .= " DEFAULT ".($default === NULL ? "NULL" : $this->db->escape($default) );
				}
				
				if ($option === 'add') {
					$qp .= " AFTER ";
					if (empty($params['after']) || 
						! is_string($params['after']) || 
						! array_key_exists($params['after'], $this->fields) ) {
						$qp .= $this->db->escape_identifier($this->pk_field);
					} else {
						$qp .= $this->db->escape_identifier($params['after']);
					}
				}
				
				$arr[] = $qp;
			}
		}
		
		$str = empty($arr) ? false : implode(",\n", $arr);
		if ($str !== false && ! empty($change_fields)) {
		//workaround if column names are changed and old column name used in AFTER
		//operator, then must change the old names to the new names
			foreach ($change_fields as $old_field => $new_field) {
				$str = str_replace("AFTER ".$old_field, "AFTER ".$new_field, $str);
			}
		}
	
		return $str;
	}
	

	/**
	 * sql_cond
	 *
	 * Checks a given string for a correct SQL condition value for a query. Primarily a method
	 * to ensure no harmful SQL is injected.
	 *
	 * @access protected
	 * @param string $val The condition value to check
	 * @return string The accepted SQL condition value or AND by default if $val param invalid
	 */
	protected function sql_cond($val='') {
		$accepted = array('AND', 'OR');
		
		if ( ! is_string($val) ) {
			$val =  $accepted[0];
		}
	
		$val = strtoupper($val);
		return in_array($val, $accepted) ? $val : $accepted[0];
	}
	
	
	/**
	 * sql_safe_indentifier
	 *
	 * Converts a string into only alphanumeric characters, used for an
	 * SQL identifier.
	 *
	 * @access public
	 * @param string $str The string to update
	 * @return string The updated string
	 */
	protected function sql_safe_indentifier($str) {
		if ( empty($str) ) {
			return false;
		}
		
		return preg_replace('/\W+/', '', $str);
	}
	
	
	/**
	 * unserialize
	 *
	 * Unserializes a string that has been serialized with $this->serialize method.
	 * Verifies the the appended hash to the string data before using json_decode()
	 * function to convert back to an array.
	 * 
	 * @access protected
	 * @param mixed $data The data to unserialize
	 * @return mixed The unserialized array or the data passed in if not serialized
	 */
	protected function unserialize($data) {
		if ( is_numeric($data) ) {
			return $data;
		}

		$marker = self::SERIALIZED_MARKER;
		$offset = -(strlen($marker)) - 1;
		$data_offset = strlen($data) + $offset;
		
		if ($data_offset > 0 && ($pos = strrpos($data, $marker, $offset)) !== false ) {
			$value = substr($data, 0, $pos);
			$verify = substr($data, $pos);
			$verify = str_replace($marker, '', $verify);
			$hash = substr($verify, 0, 32);
			$length = (int) substr($verify, 32);
	
			if ( md5($value) === $hash && strlen($value) === $length ) {
				$data = json_decode($value, true);
			}
		}
		
		return $data;
	}
	
	
	/**
	 * unserialize_row
	 *
	 * Accepts an associative array of database row field values, or an array
	 * of rows, and converts any serialized fields back into array values.
	 * 
	 * @access protected
	 * @param array $row The assoc array or array of assoc array
	 * @return array The assoc array whose serialized values are converted back into an array
	 */
	protected function unserialize_row(&$row) {
		if ( ! is_array($row) ) {
			return $row;
		}

		foreach ($row as $key => $mixed) {
			if ( is_numeric($mixed) ) {
				continue;
			} else if ( is_array($mixed) ) {
				$row[$key] = $this->unserialize_row($mixed);
			} else {
				$row[$key] = $this->unserialize($mixed);
			}
		}
		
		return $row;
	}
	

	/**
	 * update_module_slug
	 *
	 * Updates the slug field of a model (module) row.
	 *
	 * @access protected
	 * @param int $id The row ID
	 * @param string $slug The slug to update
	 * @return boolean True if operation successful or an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 */
	protected function update_module_slug($id, $slug) {
		if ( empty($id) ) {
			return false;
		}
		
		$query = "UPDATE ".$this->db->escape_identifier($this->table_name)." SET ";
		$query .= $this->db->escape_identifier(self::MODEL_SLUG_FIELD)."=".$this->db->escape($slug)." ";
		$query .= "WHERE ".$this->db->escape_identifier($this->pk_field)."=".$this->db->escape($id);
		$result = $this->db->query($query);
		return is_numeric($result);
	}
	

	/**
	 * update_slug
	 *
	 * Updates a previously generated slug for a model row.
	 *
	 * @access protected
	 * @param int $id The row ID
	 * @param string $text The text to convert to the slug
	 * @return boolean True if operation successful or an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 */
	protected function update_slug($id, $text) {
		if (empty($id) || empty($text)) {
			return false;
		}
		
		$slug = $this->create_slug($id, $text);
		//$this->db->query("LOCK TABLE ".$this->db->escape_identifier(self::$SLUG_TABLE)." WRITE");
		$query = "UPDATE ".$this->db->escape_identifier(self::$SLUG_TABLE)." SET ";
		$query .= "slug=".$this->db->escape($slug)." ";
		$query .= "WHERE module=".$this->db->escape($this->module)." ";
		$query .= "AND row_id=".$this->db->escape($id);
		$result = $this->db->query($query);
		//$this->db->query("UNLOCK TABLES");
		
		//update subclassed slug field
		$this->update_module_slug($id, $slug);

		return is_numeric($result);
	}
	
}

/* End of file Model.php */
/* Location: ./App/Model/Model.php */