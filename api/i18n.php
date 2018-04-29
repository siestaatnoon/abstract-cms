<?php
/***
 * A workaround script to dynamically load i18n translation JSON files
 * from the require modules in the javascript. Searches the App Lang
 * directories for a xx_XX.json lang file and echos a RequireJS
 * module that will load them. Note that the .htaccess in this
 * directory had to be modified to accommodate handling serving up
 * javascript in a PHP script.
 */
$locales = array();
$lang_dir = '/api/App/Lang';
$base_path = realpath(__DIR__.'/../').$lang_dir;
if ( ($handle = @opendir($base_path) ) !== false) {
    while ( ($lang_code = readdir($handle) ) !== false) {
        if ($lang_code !== '..' && @is_dir($base_path.'/'.$lang_code) ) {
            if ( ($handle2 = @opendir($base_path.'/'.$lang_code) ) !== false) {
                while ( ($country_code = readdir($handle2) ) !== false) {
                    $locale = $lang_code.'_'.$country_code;
                    $lang_json_file = '/'.$lang_code.'/'.$country_code.'/'.$locale.'.json';
                    if ( @is_file($base_path.$lang_json_file) ) {
                        $locales[$locale] = $lang_dir.$lang_json_file;
                    }
                }
                closedir($handle2);
            }
        }
    }
    closedir($handle);
}

header('Content-Type: text/javascript');
echo 'define(['."\n";
$obj = array();
$count = 0;
foreach ($locales as $locale => $path) {
    $obj[$locale] = strtolower($locale).'Json';
    echo "\t'".'text!..'.$path."'".($count++ < count($locales) - 1 ? ',' : '')."\n";
}
echo '], function('.implode(', ', $obj).') {'."\n";
echo "\t".'return {'."\n";
$count = 0;
foreach ($obj as $locale => $var) {
    echo "\t\t".$locale.': '.$var.($count++ < count($obj) - 1 ? ',' : '')."\n";
}
echo "\t".'};'."\n";
echo '});'."\n";