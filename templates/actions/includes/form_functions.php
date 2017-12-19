<?php

if ( ! function_exists('is_recaptcha_verified'))
{
    function is_recaptcha_verified($private_key, $recaptcha_response) {
        if ( empty($private_key) || empty($recaptcha_response) ) {
            return false;
        }

        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify?secret='.$private_key;
        $recaptcha_url .= '&response='.$recaptcha_response;
        $recaptcha_resp = file_get_contents($recaptcha_url);
        $response = json_decode($recaptcha_resp);
        return ! empty($response->success);
    }
}

if ( ! function_exists('is_valid_email'))
{
    function is_valid_email($email) {
        if ( empty($email) ) {
            return false;
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if ( ! function_exists('sanitize'))
{
    function sanitize($arr_or_str) {
        if ( empty($arr_or_str) ) {
            return $arr_or_str;
        } else if ( is_array($arr_or_str) === false ) {
            return strip_tags($arr_or_str);
        }

        foreach ($arr_or_str as &$val) {
            $val = sanitize($val);
        }
        return $arr_or_str;
    }
}