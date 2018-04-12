<?php
namespace App\Model;

use 
App\Model\Relation;

/**
 * Relation_users_modules class
 * 
 * Provides the database functions for users to modules module relations. Subclass of App\Model\Relation,
 * provides additional functions for handling user permission relational data.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Model
 */
class Relation_users_modules extends \App\Model\Relation {
	
	/**
     * @const string Name of permission field to save user permission for module
     */	
	const RELATION_PERM_FIELD = 'permission';

    /**
     * @const string Name of form field utilizing this relation
     */
    const RELATION_FIELD_NAME = 'modules';


    /**
     * Constructor
     *
     * Initializes the Relation_users_modules relation.
     *
     * @access public
     * @param array $config The relation configuration array
     * @throws \App\Exception\AppException if $config assoc array missing required parameters
     * @see Relation::__construct() for model configuration parameters
     */
    public function __construct($config) {
        parent::__construct($config);
    }


	/**
	 * add
	 *
	 * Inserts a user permission relation or multiple relations in the relational table. If array 
	 * given in $indep_id and permission.
	 *
	 * @access public
	 * @param int $dep_id The ID of the dependant model row
	 * @param mixed $indep_id The ID or numeric array of IDs of the independant model row(s)
     * @param string $field_name The name of the form field utilizing relation
	 * @param mixed $args The permission decimal value or numeric array of permissions indexed
	 * by corresponding $indep_id param. Note that if array and no corresponding value exists,
	 * it will be given a value of zero.
	 * @return bool True if operation successful
	 */
	public function add($dep_id, $indep_id, $field_name, $args=false) {
		if ( empty($dep_id) || empty($indep_id) || empty($args) ) {
			return false;
		}
		
		$indep_ids = is_numeric($indep_id) ? array($indep_id) : $indep_id;
		$permissions =  is_numeric($indep_id) ? array($indep_id => $args) : $args;
		$query = "INSERT INTO ".$this->db->escape_identifier($this->relation_table)." (";
		$query .= $this->db->escape_identifier($this->dep_id_field).", ";
		$query .= $this->db->escape_identifier($this->indep_id_field).", ";
        $query .= $this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD).", ";
		$query .= $this->db->escape_identifier(self::RELATION_PERM_FIELD).") VALUES ";
		$query_insert = array();
		$perm = 0;
		
		foreach ($indep_ids as $i => $id) {
			$permission = empty($permissions[$id]) ? 0 : $permissions[$id];
			$q = "(".$this->db->escape($dep_id).",";
			$q .= $this->db->escape($id).",";
            $q .= $this->db->escape(self::RELATION_FIELD_NAME).",";
			$q .= $this->db->escape($permission).")";
			$query_insert[] = $q;
		}
		$query .= implode(", ", $query_insert);
		$result = $this->db->query($query);
		
		return is_numeric($result);
	}


    /**
     * get
     *
     * Returns an independant model row, or all rows, given it's relational dependant model ID.
     * Overwrites the parent method to include permission value for the module.
     *
     * @access public
     * @param int $dep_id The dependant row ID
     * @param int $indep_id The independant row ID to retrieve a single row (optional)
     * @param string $field_name The name of the form field utilizing relation (unused
     * @return mixed An array of dependant model rows OR single row OR false if no
     * matches found for a single row or if invalid parameter
     */
    public function get($dep_id, $indep_id=0, $field_name='') {
        if ( empty($dep_id) ) {
            return false;
        }

        $indep_table = $this->indep_model->get_property('table_name');
        $indep_pk_field = $this->indep_model->get_property('pk_field');

        $query = "SELECT `indep`.*, `rel`.permission ";
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

        $query .= "ORDER BY `rel`.".$this->db->escape_identifier(self::RELATION_SORT_FIELD)." ASC";
        $result = $this->db->query($query);
        if ($result === false) {
            return false;
        }

        return $this->indep_model->parse_result($result);
    }
	

	/**
	 * get_user_perm
	 *
	 * Returns the integer permission value for a user to a module.
	 *
	 * @access public
	 * @param int $user_id The user (dependant) ID
	 * @param int $module_id The module (independant) ID
	 * @return mixed The permission value OR false if does not exist
	 */
	public function get_user_perm($user_id, $module_id) {
		if ( empty($user_id) || empty($module_id) ) {
			return false;
		}
		
		$permission = false;
		$row = parent::get_relations($user_id, $module_id, self::RELATION_FIELD_NAME);
		if ( is_array($row) && array_key_exists('permission', $row) ) {
			$permission = $row['permission'];
		}
		
		return $permission;
	}
	

	/**
	 * set_sort_order
	 *
	 * Overwrites the parent class method to return false. Method not used by this class.
	 *
	 * @access public
	 * @param int $dep_id The ID of the dependant model row
	 * @param array $indep_ids The array of independant row IDs, in sorted order
     * @param string $field_name The name of the form field utilizing relation
	 * @return bool True
	 */
	public function set_sort_order($dep_id, $indep_ids, $field_name='') {
		return true;
	}
	
	
	/**
	 * update
	 *
	 * Updates a relation or multiple relations in the relational table.
	 *
	 * @access public
	 * @param int $dep_id The ID of the dependant model row
	 * @param mixed $indep_id The ID or array of IDs of the independant model row(s)
     * @param string $field_name The name of the form field utilizing relation
	 * @param mixed $args Arguments passed in to method, primarily used by subclasses to add
	 * additional data to relational table
	 * @return bool True if operation successful
	 */
	public function update($dep_id, $indep_id, $field_name='', $args=false) {
		if ( empty($dep_id) || empty($indep_id) || empty($args)  ) {
			return false;
		}

        $indep_ids = is_array($indep_id) ? $indep_id : array($indep_id);
        $args = is_array($indep_id) ? $args : array($indep_id => $args);
		$is_updated = true;
		foreach ($indep_ids as $id) {
			if ( empty($args[$id]) ) {
				continue;
			}
			
			$permission = $args[$id];
			$query = "UPDATE ".$this->db->escape_identifier($this->relation_table)." SET ";
			$query .= $this->db->escape_identifier(self::RELATION_PERM_FIELD)."=".$this->db->escape($permission).", ";
            $query .= $this->db->escape_identifier(self::RELATION_FIELD_NAME_FIELD)."=".$this->db->escape($field_name)." ";
			$query .= "WHERE ".$this->db->escape_identifier($this->dep_id_field)."=".$this->db->escape($dep_id)." ";
			$query .= "AND ".$this->db->escape_identifier($this->indep_id_field)."=".$this->db->escape($id);
			$result = $this->db->query($query);
			if ( is_numeric($result) === false ) {
				$is_updated = false;
			}
		}

		return $is_updated;
	}
}

/* End of file Relation_users_modules.php */
/* Location: ./App/Model/Relation_users_modules.php */