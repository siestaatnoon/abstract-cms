<?php

use App\App;

/**
 * form_fields_valid_options_html
 *
 * Returns the select option values as HTML for validation types in the Modules CMS form.
 *
 * @return string The validation type select options HTML
 * @throws \App\Exception\AppException if an application error occurred, handled by \App\App class
 */
if ( ! function_exists('form_fields_valid_options_html'))
{
    function form_fields_valid_options_html() {
        $App = App::get_instance();
        $config = $App->load_config('validation');
        $validation = $config['validation'];
        $values = array('' => '-- Select --');

        if ( ! empty($validation) ) {
            foreach ($validation as $val => $param) {
                $name = "";
                if ( is_string($param) ) {
                    $name = $param;
                } else if ( isset($param['lang']) ) {
                    $name = $App->lang($param['lang']);
                } else if ( isset($param['label']) ) {
                    $name = $param['label'];
                }
                $values[$val] = $name;
            }
        }

        $html = '';
        foreach ($values as $val => $label) {
            $html .= '<option value="'.$val.'">'.$label.'</option>'."\n";
        }

        return $html;
    }
}

/* End of file form_fields.php */
/* Location: ./App/Util/form_fields.php */