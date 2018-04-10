<?php

use
App\App,
App\Exception\AppException;

/**
 * __
 *
 * Returns an I18n string given a key from the current lang locale file. If the 
 * lang key is not found, then the string value will be searched for in the en_US
 * lang file and a I18n value will be returned. If no value is found, the string 
 * parameter is returned.
 *
 * @param string $str The lang key or en_US word for I18n value
 * @return string The I18n value in the current lang locale OR
 * $str param if value not found or invalid
 */
if ( ! function_exists('__'))
{
    function __($str) {
        if ( empty($str) || substr($str, 0, 6) === 'error.' ) {
            return $str;
        }

        $App = App::get_instance();

        // first check main lang file
        $i18n = $App->lang($str);

        if ($str === $i18n) {
            // translation not found, next check for text translation
            $i18n = $App->lang_text($str);
        }

        // search main EN translation file for $str and
        // use corresponding key for translation
        if ( empty($i18n) ) {
            $lang = array();
            try {
                $lang = $App->load_lang('en_US');
            } catch (AppException $e) {
                return $str;
            }
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
 * @param mixed $args The string parameter or array of strings to add to error string
 * @return string The I18n error string in the current lang locale or empty
 * string if key not found or invalid
 */
if ( ! function_exists('error_str'))
{
    function error_str($key, $args=array()) {
        if ( empty($key) || substr($key, 0, 6) !== 'error.' ) {
            return '';
        } else if ( is_array($args) === false ) {
            $args = array($args);
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