<?php

namespace App\Model;

use 
App\App,
App\Database\Database,
App\Exception\AppException,
App\Model\Model;

/**
 * Relation class
 * 
 * Provides a relation between a dependant model and independant model, both of type \App\Model\Model,
 * and methods to access another independant model's row data from the dependant model.
 * The relation can be defined as one of the following<br/><br/>
 * <ul>
 * <li>One-Many: A dependant model row linked to one or more independant model rows</li>
 * <li>Many-One: A dependant model row linked to one independant model row, multiple rows
 * can be linked to the same independant model row</li>
 * <li>Many-Many: A dependant model row linked to many independant model rows and vice versa</li>
 * <li>One-One: One dependant model row linked to only one independant model row and vice versa, NOTE:
 * is not supported in this class</li>
 * </ul>
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Model
 */
class Relation {

    /**
     * @const string Name of form field using the relation
     */
    const RELATION_FIELD_NAME_FIELD = 'field_name';
	
    /**
     * @const string Name of module field to save the sorting order
     */	
	const RELATION_SORT_FIELD = 'sort_order';
	
    /**
     * @const string Identifier for a relation of many to one
     */
	const RELATION_TYPE_N1 = 'n:1';
	
    /**
     * @const string Identifier for a relation of one to many
     */
	const RELATION_TYPE_1N = '1:n';
	
    /**
     * @const string Identifier for a relation of many to many
     */
	const RELATION_TYPE_NN = 'n:n';	
	
	
    /**
     * @var array Array of all possible relation types
     */
	protected static $RELATION_TYPES = array(self::RELATION_TYPE_N1, self::RELATION_TYPE_1N, self::RELATION_TYPE_NN);
	
    /**
     * @var \App\Database\Database The module database connection
     */
	protected $db;
	
    /**
     * @var array Configuration parameters for the database connection
     */
	protected $db_config;
	
    /**
     * @var string The dependant model ID field in the relation table
     */
	protected $dep_id_field;
	
    /**
     * @var \App\Model\Model The dependant model object
     */
	protected $dep_model;

    /**
     * @var string The independant model ID field in the relation table
     */
	protected $indep_id_field;
	
    /**
     * @var \App\Model\Model The independant model object
     */
	protected $indep_model;
	
    /**
     * @var array Assoc array of module data (database) of relation
     */
	protected $module;

    /**
     * @var string Name of dependant/independant model relational table
     */
	protected $relation_table;
	
    /**
     * @var string The relation type of the dependant model to independant model
     */
	protected $relation_type;


	/**
	 * Constructor
	 *
	 * Initializes the Relation with the given configuration parameters in $config assoc array:<br/><br/>
	 * <ul>
	 * <li>module => The module data of the relation (NOTE: not \App\Module\Module object)</li>
	 * <li>dep_model => The dependant \App\Model\Model model object</li>
	 * <li>indep_model => The independant \App\Model\Model model object</li>
	 * <li>relation_type => The relation type of the dependant model to independant model.
	 * Is one of the following:<br/><br/>
	 * <ul>
	 * <li>RELATION_TYPE_N1: many to one relation</li>
	 * <li>RELATION_TYPE_1N: one to many relation</li>
	 * <li>RELATION_TYPE_NN: many to many relation</li>
	 * </ul>
	 * </li>
	 * <li>database => (Optional) The database configuration param defined in $config[database][use_this_param] in 
	 * ./App/config.php if using a connection other than default connection</li>
	 * </ul>
	 * 
	 * @access public
	 * @param array $config The relation configuration array
	 * @throws \App\Exception\AppException if $config assoc array missing required parameters
	 */
	public function __construct($config) {
		$app = App::get_instance();
		$errors = array();
		
		if ( empty($config['module']) ) {
			$errors[] = '$config[module] invalid, must be module data of relation';
		}
		if ( empty($config['dep_model']) || ! $config['dep_model'] instanceof \App\Model\Model) {
			$errors[] = '$config[dep_model] invalid, dependant model must be of type \\App\\Model\\Model';
		}
		if ( empty($config['indep_model']) || ! $config['indep_model'] instanceof \App\Model\Model) {
			$errors[] = '$config[indep_model] invalid, independant model must be of type \\App\\Model\\Model';
		}
		if ( empty($config['relation_type']) || ! in_array($config['relation_type'], self::$RELATION_TYPES) ) {
			$errors[] = '$config[relation_type] invalid, must be one of ['.implode('|', self::$RELATION_TYPES).']';
		}
		if ( ! empty($errors) ) {
			$message = 'Invalid param (array) $config: '.implode("\n", $errors);
			throw new AppException($message, AppException::ERROR_FATAL);
		}

		$db_config = empty($config['database']) ? $app->config('db_config') : $config['database'];
		$database = $app->config('database');
		if ( empty($database) ) {
			$message = 'Database config ['.$db_config.'] not found in config.php';
			throw new AppException($message, AppException::ERROR_FATAL);
		}
		
		$this->module = $config['module'];
		$this->db_config = $database[$db_config];
		$this->db = Database::connection($db_config);
		$this->dep_model = $config['dep_model'];
		$this->indep_model = $config['indep_model'];
		$this->relation_type = $config['relation_type'];
		
		// Note: relational table name is:
		// [table prefix] + [dependant module name] + "2" + [independant module name]
		// e.g. prefix_products2product_categories
		//
		$table_prefix = $app->config('db_table_prefix');
		$dep_module = $this->dep_model->get_property('module');
		$indep_module = $this->indep_model->get_property('module');
		$this->relation_table = (empty($table_prefix) ? '' : $table_prefix).$dep_module."2".$indep_module;
		$this->dep_id_field = $dep_module."_id";
		$this->indep_id_field = $indep_module.($dep_module === $indep_module ? '_rel' : '')."_id";
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
	 * add
	 *
	 * Inserts a relation or multiple relations in the relational table. If array given in $indep_id
	 * param then the sorting index of the rows will default to the order within this array.
	 *
	 * @access public
	 * @param int $dep_id The ID of the dependant model row
	 * @param mixed $indep_id The ID or array of IDs of the independant model row(s)
     * @param string $field_name The name of the form field utilizing relation
	 * @param mixed $args Arguments passed in to method, primarily used by subclasses to add
	 * additional data to relational table
	 * @return boolean True if operation successful or an App\Exception\SQLException is passed 
	 * and to be handled by \App\App class if an SQL error occurred
	 */
	public function add($dep_id, $indep_id, $field_name, $args=false) {
		if ( empty($dep_id) || empty($indep_id) ) {
			return false;
		}
		
		//determine sort order if model uses it
		$count = 1;
		$query = "SELECT MAX(".$this->db->escape_identifier(self::RELATION_SORT_FIELD).") + 1 AS count ";
		$query .= "FROM ".$this->db->escape_identifier($this->relation_table)." ";
		$query .= "WHERE ".$this->db->escape_identifier($this->dep_id_field)."=".$this->db->escape($dep_id);
		$result = $this->db->query($query);
		if ($result->num_rows() > 0) {
			$row = $result->row();
			$count = $row['count'];
		}
		
		$indep_ids = is_numeric($indep_id) ? array($indep_id) : $indep_id;
		$query = "INSERT INTO ".$this->db->escape_identifier($this->relation_table)." (";
		$query .= $this->db->escape_identifier($this->dep_id_field).", ";
		$query .= $this->db->escape_identifier($this->indep_id_field).", ";
        $query .= $this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD).", ";
		$query .= $this->db->escape_identifier(self::RELATION_SORT_FIELD).") VALUES ";
		$query_insert = array();

		foreach ($indep_ids as $i => $id) {
			$q = "(".$this->db->escape($dep_id).",";
			$q .= $this->db->escape($id).",";
            $q .= $this->db->escape($field_name).",";
			$q .= $this->db->escape($count + $i).")";
			$query_insert[] = $q;
		}
		$query .= implode(", ", $query_insert);
		$result = $this->db->query($query);
		
		return is_numeric($result);
	}


    /**
     * clear_table
     *
     * Truncates a relation table.
     *
     * @access public
     * @param array $config The relation configuration assoc array, similar to constructor
     * @return bool True if operation successful or an App\Exception\SQLException
     * is passed and to be handled by \App\App class if an SQL error occurred
     * @see __construct() for relation configuration parameters
     */
    public static function clear_table($config) {
        $relation = new self($config);
        $db = $relation->get_property('db');
        $relation_table = $relation->get_property('relation_table');
        $query = "TRUNCATE TABLE ".$db->escape_identifier($relation_table).";";
        $db->query($query);
        return true;
    }
	

	/**
	 * create_table
	 *
	 * Static function that creates the table for the dependant/independant relation. Note that 
	 * if the table already exists, it will be dropped and recreated.
	 *
	 * @access public
	 * @param array $config The relation configuration assoc array, similar to constructor
	 * @param boolean $drop_if_exists True to first drop table if exists before creating
	 * @return \App\model\Relation The Relation object if operation successful or an 
	 * App\Exception\SQLException is passed and to be handled by \App\App class if an SQL 
	 * error occurred
	 * @see __construct() for relation configuration parameters
	 */
	public static function create_table($config, $drop_if_exists=false) {
		$relation = new self($config);
		
		//DROP table if exists
		if ($drop_if_exists) {
			$relation->drop_table($config);
		}
		
		$db = $relation->get_property('db');
		$relation_table = $relation->get_property('relation_table');
		$dep_id_field = $relation->get_property('dep_id_field');
		$indep_id_field = $relation->get_property('indep_id_field');
		
		$query = "CREATE TABLE IF NOT EXISTS ".$db->escape_identifier($relation_table)." (\n";
		$query .= "`id` bigint(15) NOT NULL AUTO_INCREMENT,\n";
		$query .= $db->escape_identifier($dep_id_field)." int(15) NOT NULL DEFAULT '0',\n";
		$query .= $db->escape_identifier($indep_id_field)." int(15) NOT NULL DEFAULT '0',\n";
        $query .= $db->escape_identifier(self::RELATION_FIELD_NAME_FIELD)." varchar(64) NOT NULL,\n";
		$query .= $db->escape_identifier(self::RELATION_SORT_FIELD)." int(15) NOT NULL DEFAULT '0',\n";
		$query .= "PRIMARY KEY (`id`),\n";
		$query .= "KEY ".$dep_id_field." (".$dep_id_field."),\n";
		$query .= "KEY ".$indep_id_field." (".$indep_id_field."),\n";
        $query .= "KEY ".self::RELATION_FIELD_NAME_FIELD." (".self::RELATION_FIELD_NAME_FIELD.")\n";
		$query .= ") ENGINE=InnoDB;\n";
		$db->query($query);

        // truncate table in case it existed beforehand
        self::clear_table($config);

		return $relation;
	}
	
	
	/**
	 * delete
	 *
	 * Deletes the relations of a dependant model row. If second parameter not given, this will
	 * delete all relations of the dependant model row. If given, will delete only the links
	 * with the independant row ID or array of IDs.
	 *
	 * @access public
	 * @param array $dep_id The dependant model row ID
	 * @param array $indep_id The independant model row ID or array of IDs
     * @param string $field_name The name of the form field utilizing relation
	 * @return boolean True if operation successful or an App\Exception\SQLException is passed 
	 * and to be handled by \App\App class if an SQL error occurred
	 */
	public function delete($dep_id='', $indep_id=array(), $field_name='') {
		if ( empty($dep_id) && empty($indep_id) && empty($field_name) ) {
			return false;
		} else if ( is_numeric($indep_id) ) {
			$indep_id = array($indep_id);
		}

		$query = "DELETE FROM ".$this->db->escape_identifier($this->relation_table)." WHERE ";

        if ( ! empty($dep_id) ) {
            $query .= $this->db->escape_identifier($this->dep_id_field)."=".$this->db->escape($dep_id);
        }

		if ( ! empty($indep_id) ) {
            $vals = array();
            if ( ! empty($dep_id) ) {
                $query .= " AND ";
            }
			$query .= $this->db->escape_identifier($this->indep_id_field)." IN (";
			foreach ($indep_id as $id) {
				$vals[] = $this->db->escape($id);
			}
			$query .= implode(", ", $vals);
			$query .= ")";
		}

        if ( ! empty($field_name) ) {
            if ( ! empty($dep_id) || ! empty($indep_id) ) {
                $query .= " AND ";
            }
            $query .= $this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD)."=".$this->db->escape($field_name);
        }
		
		$result = $this->db->query($query);
		return is_numeric($result);
	}
	
	
	/**
	 * drop_table
	 *
	 * Deletes the relational table.
	 *
	 * @access public
	 * @param array $config The relation configuration assoc array, similar to constructor
	 * @return boolean True if operation successful or an App\Exception\SQLException is passed 
	 * and to be handled by \App\App class if an SQL error occurred
	 * @see __construct() for relation configuration parameters
	 */
	public static function drop_table($config) {
		$relation = new self($config);
		$relation_table = $relation->get_property('relation_table');
		$db = $relation->get_property('db');
		$query = "DROP TABLE IF EXISTS ".$db->escape_identifier($relation_table);
		$db->query($query);
		return true;
	}


	/**
	 * filter
	 *
	 * Queries the relations table given an independant model ID or array of IDs 
	 * and returns the IDs of dependant model rows that match.
	 *
	 * @access public
	 * @param array $indep_ids The independant row ID or array of IDs
     * @param string $field_name The name of the form field utilizing relation
	 * @return mixed An array of dependant model IDs OR empty array if no matches found OR
	 * false if invalid parameters OR an App\Exception\SQLException is passed 
	 * and to be handled by \App\App class if an SQL error occurred
	 */
	public function filter($indep_id, $field_name='') {
		if ( empty($indep_id) ) {
			return false;
		} else if ( is_numeric($indep_id) ) {
			$indep_id = array($indep_id);
		}

		$query = "SELECT ".$this->db->escape_identifier($this->dep_id_field)." ";
		$query .= "FROM ".$this->db->escape_identifier($this->relation_table)." ";
		$query .= "WHERE ".$this->db->escape_identifier($this->indep_id_field)." IN (";

        $vals = array();
		foreach ($indep_id as $id) {
			$vals[] = $this->db->escape($id);
		}
		$query .= implode(", ", $vals).") ";

        if ( ! empty($field_name) ) {
            $query .= "AND ".$this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD)."=".$this->db->escape($field_name)." ";
        }

		$query .= "ORDER BY ".$this->db->escape_identifier($this->dep_id_field)." ASC";
		$result = $this->db->query($query);
		
		$dep_ids = array();
		if ($result->num_rows() > 0) {
			$rows = $result->result_assoc();
			foreach ($rows as $row) {
				$dep_ids[] = $row[$this->dep_id_field];
			}
		}
		
		return $dep_ids;
	}
	

	/**
	 * get
	 *
	 * Returns an independant model row, or all rows, given it's relational dependant model ID.
	 *
	 * @access public
	 * @param int $dep_id The dependant row ID
	 * @param int $indep_id The independant row ID to retrieve a single row (optional)
     * @param string $field_name The name of the form field utilizing relation
	 * @return mixed An array of dependant model rows OR single row OR false if no 
	 * matches found for a single row or if invalid parameter OR an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 */
	public function get($dep_id, $indep_id=false, $field_name='') {
		if ( empty($dep_id) && empty($indep_id) ) {
			return false;
		}
		
		$indep_table = $this->indep_model->get_property('table_name');
		$indep_pk_field = $this->indep_model->get_property('pk_field');
		
		$query = "SELECT `indep`.* ";
		$query .= "FROM ".$this->db->escape_identifier($indep_table)." AS `indep` ";
		$query .= "INNER JOIN ".$this->db->escape_identifier($this->relation_table)." AS `rel` ";
		$query .= "ON `indep`.".$this->db->escape_identifier($indep_pk_field)."=";
		$query .= "`rel`.".$this->db->escape_identifier($this->indep_id_field)." ";
		$query .= "WHERE `rel`.".$this->db->escape_identifier($this->dep_id_field)."=";
		$query .= $this->db->escape($dep_id)." ";

		if ( ! empty($indep_id) ) {
			$query .= "AND `rel`.".$this->db->escape_identifier($this->indep_id_field)."=";
			$query .= $this->db->escape($indep_id)." ";
		}

        if ( ! empty($field_name) ) {
            $query .= "AND ".$this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD)."=".$this->db->escape($field_name)." ";
        }

		$query .= "ORDER BY `rel`.".$this->db->escape_identifier(self::RELATION_SORT_FIELD)." ASC";
		$result = $this->db->query($query);
		if ($result === false) {
			return false;
		}

		return $this->indep_model->parse_result($result);
	}


    /**
     * get_dep
     *
     * Returns an dependant model row, or all rows, given it's relational independant model ID.
     * Used primarily to retrieve N:1 relational rows.
     *
     * @access public
     * @param int $indep_id The independant row ID to retrieve a single row (optional)
     * @param string $field_name The name of the form field utilizing relation
     * @return mixed An array of dependant model rows OR single row OR false if no
     * matches found for a single row or if invalid parameter OR an App\Exception\SQLException
     * is passed and to be handled by \App\App class if an SQL error occurred
     */
    public function get_dep($indep_id, $field_name='') {
        if ( empty($indep_id) ) {
            return false;
        }

        $dep_table = $this->dep_model->get_property('table_name');
        $dep_pk_field = $this->dep_model->get_property('pk_field');

        $query = "SELECT `dep`.* ";
        $query .= "FROM ".$this->db->escape_identifier($dep_table)." AS `dep` ";
        $query .= "INNER JOIN ".$this->db->escape_identifier($this->relation_table)." AS `rel` ";
        $query .= "ON `dep`.".$this->db->escape_identifier($dep_pk_field)."=";
        $query .= "`rel`.".$this->db->escape_identifier($this->dep_id_field)." ";
        $query .= "WHERE `rel`.".$this->db->escape_identifier($this->indep_id_field)."=";
        $query .= $this->db->escape($indep_id);

        if ( ! empty($field_name) ) {
            $query .= " AND ".$this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD)."=".$this->db->escape($field_name)." ";
        }

        $query .= "ORDER BY `rel`.".$this->db->escape_identifier(self::RELATION_SORT_FIELD)." ASC";
        $result = $this->db->query($query);
        if ($result === false) {
            return false;
        }

        return $this->dep_model->parse_result($result);
    }
	

	/**
	 * get_ids
	 *
	 * Returns all independant model row IDs given a relational dependant model ID.
	 *
	 * @access public
	 * @param into $dep_id The dependant row ID
     * @param string $field_name The name of the form field utilizing relation
	 * @return mixed An array of dependant model IDs OR empty array if no matches found OR
	 * false if invalid parameter OR an App\Exception\SQLException is passed 
	 * and to be handled by \App\App class if an SQL error occurred
	 */
	public function get_ids($dep_id, $field_name='') {
		if ( empty($dep_id) ) {
			return false;
		}

		$query = "SELECT ".$this->db->escape_identifier($this->indep_id_field)." ";
		$query .= "FROM ".$this->db->escape_identifier($this->relation_table)." ";
		$query .= "WHERE ".$this->db->escape_identifier($this->dep_id_field)."=".$this->db->escape($dep_id)." ";

        if ( ! empty($field_name) ) {
            $query .= "AND ".$this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD)."=".$this->db->escape($field_name)." ";
        }

		$query .= "ORDER BY ".$this->db->escape_identifier(self::RELATION_SORT_FIELD)." ASC";
		$result = $this->db->query($query);
		
		$ids = array();
		if ($result->num_rows() > 0) {
			$rows = $result->result_assoc();
			foreach ($rows as $row) {
				$ids[] = $row[$this->indep_id_field];
			}
		}
		
		return $this->relation_type == self::RELATION_TYPE_N1 ? (isset($ids[0]) ? $ids[0] : false) : $ids;
	}


    /**
     * get_dep
     *
     * Returns an independant model row, or all rows, given it's relational dependant model ID.
     * Used primarily to retrieve 1:N relational rows.
     *
     * @access public
     * @param int $dep_id The dependant row ID to retrieve a single row (optional)
     * @param string $field_name The name of the form field utilizing relation
     * @return mixed An array of independant model rows OR single row OR false if no
     * matches found for a single row or if invalid parameter OR an App\Exception\SQLException
     * is passed and to be handled by \App\App class if an SQL error occurred
     */
    public function get_indep($dep_id, $field_name='') {
        if ( empty($dep_id) ) {
            return false;
        }
        return $this->get($dep_id, false, $field_name);
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
			case 'dep_id_field' :
				$property = $this->dep_id_field;
				break;
			case 'indep_id_field' :
				$property = $this->indep_id_field;
				break;
			case 'dep_model' :
				$property = $this->dep_model;
				break;
			case 'indep_model' :
				$property = $this->indep_model;
				break;
			case 'module' :
				$property = $this->module;
				break;
			case 'relation_table' :
				$property = $this->relation_table;
				break;
			case 'relation_type' :
				$property = $this->relation_type;
				break;
		}
		
		return $property;
	}
	
	
	/**
	 * get
	 *
	 * Returns the rows from the relational table given a dependant ID.
	 *
	 * @access public
	 * @param int $dep_id The dependant row ID
	 * @param int $indep_id The independant row ID to retrieve a single row (optional)
     * @param string $field_name The name of the form field utilizing relation
	 * @return mixed An array of relational rows OR single row OR false if no 
	 * matches found for a single row or if invalid parameter OR an App\Exception\SQLException 
	 * is passed and to be handled by \App\App class if an SQL error occurred
	 */
	public function get_relations($dep_id, $indep_id=false, $field_name='') {
		if ( empty($dep_id) ) {
			return false;
		}
		
		$indep_table = $this->indep_model->get_property('table_name');
		$indep_pk_field = $this->indep_model->get_property('pk_field');
		
		$query = "SELECT `rel`.* ";
		$query .= "FROM ".$this->db->escape_identifier($this->relation_table)." AS `rel` ";
		$query .= "INNER JOIN ".$this->db->escape_identifier($indep_table)." AS `indep` ";
		$query .= "ON `indep`.".$this->db->escape_identifier($indep_pk_field)."=";
		$query .= "`rel`.".$this->db->escape_identifier($this->indep_id_field)." ";
		$query .= "WHERE `rel`.".$this->db->escape_identifier($this->dep_id_field)."=";
		$query .= $this->db->escape($dep_id)." ";

		if ( ! empty($indep_id) ) {
			$query .= "AND `rel`.".$this->db->escape_identifier($this->indep_id_field)."=";
			$query .= $this->db->escape($indep_id)." ";
		}

        if ( ! empty($field_name) ) {
            $query .= "AND `rel`.".$this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD)."=".$this->db->escape($field_name)." ";
        }

		$query .= "ORDER BY `rel`.".$this->db->escape_identifier(self::RELATION_SORT_FIELD)." ASC";
		$result = $this->db->query($query);
		
		if ($result === false) {
			return false;
		}
		$rows = $result->result_assoc();
		$ret = false;
		if ( ($this->relation_type == self::RELATION_TYPE_N1 || ! empty($indep_id)) && isset($rows[0]) ) {
			$ret = $rows[0];
		} else {
			$ret = $rows;
		}
		
		return $ret;
	}
	
	
	/**
	 * set_sort_order
	 *
	 * Sets the sort order field for multiple independant rows given an array of row IDs 
	 * and the dependant row ID OR multiple dependant IDs and the independant row ID.
     * The order is determined in the order of the IDs in the ID array.
	 *
	 * @access public
	 * @param int $dep_id The ID of the dependant model row OR array of dependant IDs in sorted order
	 * @param array $indep_ids The array of independant row IDs, in sorted order OR ID of independant row ID
     * @param string $field_name The name of the form field utilizing relation
	 * @return boolean True if operation successful or an App\Exception\SQLException is passed 
	 * and to be handled by \App\App class if an SQL error occurred
	 */
	public function set_sort_order($dep_ids, $indep_ids, $field_name='') {
		if ( empty($dep_ids) || empty($indep_ids) ||
            ( is_numeric($dep_ids) && is_numeric($indep_ids) ) ||
            ( is_array($dep_ids) && is_array($indep_ids) ) ) {
			return false;
		}
		
		$has_error = false;
        $sort_ids = is_array($dep_ids) ? $dep_ids : $indep_ids;
        $const_id = is_array($dep_ids) ? $indep_ids : $dep_ids;
        $const_id_field = is_array($dep_ids) ? $this->indep_id_field : $this->dep_id_field;
        $sort_id_field = is_array($dep_ids) ? $this->dep_id_field : $this->indep_id_field;
		
		for ($i=0; $i < count($sort_ids); $i++) {
			$query = "UPDATE ".$this->db->escape_identifier($this->relation_table)." SET ";
			$query .= $this->db->escape_identifier(self::RELATION_SORT_FIELD)."=";
			$query .= $this->db->escape($i+1)." ";
			$query .= "WHERE ".$this->db->escape_identifier($const_id_field)."=".$this->db->escape($const_id)." ";
			$query .= "AND ".$this->db->escape_identifier($sort_id_field)."=".$this->db->escape($sort_ids[$i])." ";
            if ( ! empty($field_name) ) {
                $query .= "AND ".$this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD)."=".$this->db->escape($field_name);
            }

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
	 * Updates a relation or multiple relations in the relational table. Since only the
	 * relation IDs are saved at this class level, nothing is performed here. Used by
	 * subclasses to overwrite this method and add data to the table through the $args param.
	 *
	 * @access public
	 * @param int $dep_id The ID of the dependant model row
	 * @param mixed $indep_id The ID or array of IDs of the independant model row(s)
     * @param string $field_name The name of the form field utilizing relation
	 * @param mixed $args Arguments passed in to method, primarily used by subclasses to add
	 * additional data to relational table
	 * @return boolean false
	 */
	public function update($dep_id, $indep_id, $field_name='', $args=false) {
		return false;
	}


    /**
     * update_field_name
     *
     * Updates the field name field in the event of a name change.
     *
     * @access public
     * @param string $old_name The old field name
     * @param string $new_name The new field name
     * @return boolean True if operation successful or an App\Exception\SQLException is passed
     * and to be handled by \App\App class if an SQL error occurred
     */
    public function update_field_name($old_name, $new_name) {
        if ( empty($old_name) || empty($new_name) ) {
            return false;
        }

        $has_error = false;
        $query = "UPDATE ".$this->db->escape_identifier($this->relation_table)." SET ";
        $query .= $this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD)."=".$this->db->escape($new_name)." ";
        $query .= "WHERE ".$this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD)."=".$this->db->escape($old_name);
        $result = $this->db->query($query);
        if ( ! is_numeric($result) ) {
            $has_error = true;
        }

        return $has_error === false;
    }
}

/* End of file Relation.php */
/* Location: ./App/Model/Relation.php */