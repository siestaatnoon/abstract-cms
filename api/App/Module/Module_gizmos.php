<?php

namespace App\Module;

use App\Exception\AppException;

/**
 * Module_gizmos class
 *
 * Subclass of \App\Module\Abstract_module, this class provides custom definitions for the Pages module.
 *
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Module
 */
class Module_gizmos extends \App\Module\Abstract_module {


    /**
     * Constructor
     *
     * Initializes the Users module.
     *
     * @access public
     */
    public function __construct() {
        parent::__construct('gizmos');
    }


    public function front_func_test($params, $vars) {
        $row = parent::get_data('sample-item-1', true);
        return $row;
    }


    /**
     * module_form_data_to_row
     *
     * Converts CMS form data to an assoc array to INSERT/UPDATE a module table row. Removes
     * any indexes that are not a module field and adds any fields with a default value if
     * missing. Overwritten from parent class.
     *
     * @access protected
     * @param array $data The form field data from CMS form
     * @return array The data converted to an assoc array for form_fields table row
     */
    protected function module_form_data_to_row($data) {
        return parent::module_form_data_to_row($data);
    }


    /**
     * row_to_module_form_data
     *
     * Converts a module table row into an assoc array of data used for the CMS form
     * to add or update a module object. Overwritten from parent class.
     *
     * @access protected
     * @param array $data A module row
     * @return array The row converted to CMS form data
     */
    protected function row_to_module_form_data($data) {
        return parent::row_to_module_form_data($data);
    }


    /**
     * validate
     *
     * Checks the given assoc array of form data for necessary indeces. Overwritten from parent class.
     *
     * @access protected
     * @param array $data The form field form data as an assoc array
     * @param boolean $has_id True if $data param contains a non-empty id for the form field row
     * @return mixed True if row data validated or an array of validation errors
     */
    protected function validate($data, $has_id=false) {
        return parent::validate($data, $has_id);
    }

}

/* End of file Module_gizmos.php */
/* Location: ./App/Module/Module_gizmos.php */