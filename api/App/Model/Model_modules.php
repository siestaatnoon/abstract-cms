<?php

namespace App\Model;

/**
 * Model_module class
 * 
 * Provides the database functions for a App\Module\Module object. Subclass of App\Model\Model,
 * adds functions for resetting modules tables and clear user permission modules.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Model
 */
class Model_modules extends \App\Model\Model {


    /**
     * Constructor
     *
     * Initializes the Model_modules model.
     *
     * @access public
     * @param array $config The model configuration array
     * @throws \App\Exception\AppException if $config assoc array missing required parameters
     * @see Model::__construct() for model configuration parameters
     */
	public function __construct($config) {
		parent::__construct($config);
	}


    /**
     * clear_modules
     *
     * Truncates the core module tables.
     *
     * @access public
     * @return bool True if operation successful
     */
	public function clear_modules() {
		$prefix = $this->table_prefix;
		$queries = array();
		$has_cleared = true;
		$queries[] = "TRUNCATE TABLE ".$this->db->escape_identifier($prefix.'modules');
		$queries[] = "TRUNCATE TABLE ".$this->db->escape_identifier($prefix.'form_fields');
		$queries[] = "TRUNCATE TABLE ".$this->db->escape_identifier($prefix.'modules2form_fields');
		$queries[] = "TRUNCATE TABLE ".$this->db->escape_identifier($prefix.'slugs');
        $queries[] = "TRUNCATE TABLE ".$this->db->escape_identifier($prefix.'users');
        $queries[] = "TRUNCATE TABLE ".$this->db->escape_identifier($prefix.'users2modules');
        $queries[] = "TRUNCATE TABLE ".$this->db->escape_identifier($prefix.'options');
        $queries[] = "DELETE FROM ".$this->db->escape_identifier($prefix.'pages')." WHERE page_id>2";
        $queries[] = "ALTER TABLE ".$this->db->escape_identifier($prefix.'pages')." AUTO_INCREMENT=3";
		 
		foreach ($queries as $query) {
			$result = $this->db->query($query);
			if ( ! is_numeric($result) ) {
				$has_cleared = false;
			}
		}

		return $has_cleared;
	}


    /**
     * delete_user_module_relations
     *
     * Deletes an admin user's module relation(s).
     *
     * @access public
     * @param mixed $mixed The module ID or array of module IDs
     * @return bool True if operation successful
     */
    public function delete_user_module_relations($mixed) {
        if ( empty($mixed) ) {
            return false;
        }

        $ids = is_array($mixed) ? $mixed : array($mixed);
        $query = "DELETE FROM ".$this->db->escape_identifier($this->table_prefix.'users2modules')." ";
        $query .= "WHERE ".$this->db->escape_identifier('modules_id')." IN (";
        $query .= $this->db->escape_str(implode(", ", $ids)).")";
        $result = $this->db->query($query);
        return is_numeric($result);
    }


    /**
     * reset_auto_increment
     *
     * Resets the AUTO_INCREMENT values for the modules, form_fields and modules2form_fields tables.
     * Values lower than 1000 are reserved for core modules.
     *
     * @access public
     * @return bool True if operation successful
     */
	public function reset_auto_increment() {
		$prefix = $this->table_prefix;
		$queries = array();
		$has_reset = true;
		$queries[] = "ALTER TABLE ".$this->db->escape_identifier($prefix.'modules')." AUTO_INCREMENT=1000";
		$queries[] = "ALTER TABLE ".$this->db->escape_identifier($prefix.'form_fields')." AUTO_INCREMENT=1000";
		$queries[] = "ALTER TABLE ".$this->db->escape_identifier($prefix.'modules2form_fields')." AUTO_INCREMENT=1000";
					 
		foreach ($queries as $query) {
			$result = $this->db->query($query);
			if ( ! is_numeric($result) ) {
				$has_reset = false;
			}
		}

		return $has_reset;
	}
	
}

/* End of file Model.php */
/* Location: ./App/Model/Model.php */