<?php

$ERROR_DELIMETER = '%|%';

/**
 * get_params_from_uri
 *
 * Returns an array of parameters extracted from a URI. Since a URI may have
 * zero, one or multiple segments, this will return an empty array for zero
 * segments, an array with one element for one segment and so on.
 *
 * @param mixed $mixed A URI segment string for parameters or can be array which is returned
 * @return array The parameters as numeric array or an empty array if $mixed is empty
 */
function get_params_from_uri($mixed) {
    if ( empty($mixed) ) {
        return array();
    } else if ( is_array($mixed) ) {
        return $mixed;
    }
    return explode('/', $mixed);
}

/**
 * set_headers
 *
 * Sets the response headers.
 *
 * @param Psr\Http\Message\ResponseInterface $response The Slim response object
 * @param array $add Assoc array of header name => header value headers to add
 * @param array $remove Array of header names to remove
 * @return Psr\Http\Message\ResponseInterface The Slim response object with updated headers
 */
function set_headers($response, $add=array(), $remove=array()) {
    //$response = $response->withHeader('Content-Type', 'application/json');
    $response = $response->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    $response = $response->withHeader('Pragma', 'no-cache');
    $response = $response->withHeader('Expires', '0');

    if ( ! empty($add) ) {
        foreach ($add as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
    }

    if ( ! empty($remove) ) {
        foreach ($remove as $name) {
            $response = $response->withoutHeader($name);
        }
    }

    return $response;
}

$slim_config = [
    'settings' => [
        'displayErrorDetails' => $App->config('debug'),
    ],
];
$Container = new \Slim\Container($slim_config);

// Default error handler
$Container['errorHandler'] = function ($Container) {
    return function ($request, $response, $exception) use ($Container) {
        global $App, $ERROR_DELIMETER;
        $message = $exception->getMessage();
        $message = str_replace($ERROR_DELIMETER, ", ", $message);
        $code = $exception->getCode();
        if ( ! empty($code) ) {
            $code = '['.$code.'] ';
        } else {
            $code = '';
        }
        $file = $App->config('debug') ? " in ".$exception->getFile() : '';
        $errors = array(
            'errors' => array($code.$message.$file)
        );
        return $Container['response']->withStatus(500)->withJson($errors);
    };
};

// PHP runtime error handler
$Container['phpErrorHandler'] = function ($Container) {
    return function ($request, $response, $error) use ($Container) {
        global $ERROR_DELIMETER;
        $errors = explode($ERROR_DELIMETER, $error);
        $data = array(
            'errors' => $errors
        );
        return $Container['response']->withStatus(500)->withJson($data);
    };
};

// 404 error handler
$Container['notFoundHandler'] = function ($Container) {
    return function ($request, $response) use ($Container) {
        return $Container['response']->withStatus(404);
    };
};