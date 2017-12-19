<?php

use App\App;

/**
 * __
 *
 * Returns an I18n string given a key from the current lang locale file. If the 
 * lang key is not found, then the string value will be searched for in the en_US
 * lang file and a I18n value will be returned. If no value is found, the string 
 * parameter is returned.
 *
 * @param string $str The lang key or en_US word for I18n value
 * @return string The I18n value in the current lang locale or $str param if
 * value not found
 */
if ( ! function_exists('__'))
{
    function __($str) {
        $App = App::get_instance();
        $i18n = $App->lang($str);
        if ( empty($i18n) ) {
            $lang = $App->load_lang('en_US');
            $key = array_search($str, $lang);
            $i18n = empty($key) ? $str : $App->lang($key);
        }

        return $i18n;
    }
}

/* End of file functions.php */
/* Location: ./App/Util/functions.php */