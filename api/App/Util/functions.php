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

        // first check main lang file
        $i18n = $App->lang($str);

        // next check for text translation
        if ( empty($i18n) ) {
            $i18n = $App->lang_text($str);
        }

        // search main EN translation file for $str and
        // use corresponding key for translation
        if ( empty($i18n) ) {
            $lang = $App->load_lang('en_US');
            $key = array_search($str, $lang);
            $i18n = empty($key) ? $str : $App->lang($key);
        }

        return $i18n;
    }
}


/**
 * error_str
 *
 * Returns an I18n error string given a key from the current lang locale file. If
 * the error text contains "%s" parameters, the second parameter is an array of
 * corresponding values used with the PHP vsprintf() function.
 *
 * @param string $str The lang error key
 * @param array $args The string parameters to add to error string
 * @return string The I18n error string in the current lang locale or empty
 * string if key not found or invalid
 */
if ( ! function_exists('error_str'))
{
    function error_str($key, $args=array()) {
        if ( empty($key) || substr($key, 0, 6) !== 'error.' ) {
            return '';
        }

        $App = App::get_instance();
        $i18n = $App->lang($key);
        if ( ! empty($i18n) && ! empty($args) ) {
            $i18n = vsprintf($i18n, $args);
        }

        return $i18n;
    }
}

/* End of file functions.php */
/* Location: ./App/Util/functions.php */