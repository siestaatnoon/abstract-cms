<?php
use App\App;
use App\Html\Template\Template;
use App\Module\Module;
use App\Model\Model;

// Load Slim Framework
require '../vendor/autoload.php';

// Abstract initialization
require 'App/App.php';
define('ERROR_LOG', '../logs/errors.log');
App::register_autoload();
$App = App::get_instance();
$Pages = Module::load('pages');

// script variables
$APIURI = 'api/front/data';
$CSRF_FIELD = 'csrf_token';
$GET_FORM_TASK = 'form';
$GET_ITEM_TASK = 'get';
$GET_LIST_TASK = 'list';
$HOME_TEMPLATE = Template::get_default_home_template();
$MODULE_PAGES = 'pages';
$PAGE_TEMPLATE = Template::get_default_template();
$TPL_COOKIE = 'abs_front_tpl';
$ERROR_DELIMETER = '%|%';

//***********************
// BELOW SLIM FRAMEWORK *
//***********************

/**
 * app_404
 *
 * Handles a 404 error page.
 *
 * @return array Assoc array of 404 page data
 */
function app_404() {
    global $GET_ITEM_TASK, $PAGE_TEMPLATE, $MODULE_PAGES;
    $data = get_template($PAGE_TEMPLATE, '404', NULL, false, false, true);
    $data['module'] = $PAGE_TEMPLATE;
    $data['task'] = $GET_ITEM_TASK;

    //prevents a repeat API call for data already set in this call
    $data['bootstrapModel'] = array();

    $data['model_url'] = get_data_api_url($MODULE_PAGES, '');
    return $data;
}

/**
 * app_custom_func
 *
 * Handles a GET request for a custom module function.
 *
 * @param string $module_name The module name (slug)
 * @param string $fn Custom function within Module class, front_func_{$fn}, that handles
 * request and returns data for resulting page
 * @param array $params Numerical array of additional parameters to pass into custom function call
 * @param array $args GET or POST values to pass into function
 * @param bool $data_only True if call to method is for data only, not including template
 * @return array The assoc array for custom module data OR null if invalid module
 * @throws \Exception if an application or runtime error occurs and is handled by the Slim error handler
 */
function app_custom_func($module_name, $fn, $params=array(), $args=array(), $data_only=true) {
    global $App, $ERROR_DELIMETER;

    // verify module exists, return if not
    if ( check_for_module($module_name) === false ) {
        return NULL;
    }

    $Module = Module::load($module_name);
    $function = 'front_func_'.$fn;
    $tpl_file = $module_name.'_fn_'.$fn;
    $var = array($Module, $function);
    $json = array();
    $errors = array();

    if (is_callable($var)) {
        try {
            $response = $Module->$function($params, $args);
            $response['breadcrumbs'] = template_breadcrumbs();
            $json = get_template($module_name, $tpl_file, $response, false, true, $data_only);
            $json['module'] = $module_name;
            $json['task'] = $fn;
            $json['idAttribute'] = $Module->has_slug() ? Model::MODEL_SLUG_FIELD : $Module->get_pk_field();
            if ($data_only) {
                $json['model'] = $response;
            } else {
                $json['bootstrapModel'] = $response;
            }
            $json['model_url'] = get_data_api_url($module_name, $fn, $params);
        } catch (Exception $e) {
            $error = 'An error has occurred in '.$module_name.'->'.$function . '()';
            if ($App->config('debug')) {
                $error .= ": ".$e->getMessage();
            }
            $errors[] = $error;
        }
    } else {
        $errors[] = 'Module_'.$module_name.'->'.$function.'() undefined';
    }

    if ( ! empty($errors) ) {
        throw new \Exception( implode($ERROR_DELIMETER, $errors) );
    }

    return $json;
}

/**
 * app_data_module
 *
 * Retrieves module item data from database.
 *
 * @param string $module_name The module name (slug)
 * @param string $id_or_slug Row ID or slug to identify item
 * @param array $params Numerical array of additional parameters to pass into function call
 * @return mixed Assoc array for module item data OR null if item not found or module not found or invalid
 * @throws \Exception if an application or runtime error occurs and is handled by the Slim error handler
 */
function app_data_module($module_name, $id_or_slug, $params=array()) {
    // verify module exists, return if not
    if ( check_for_module($module_name) === false) {
        return NULL;
    }

    $Module = Module::load($module_name);
    $model = empty($id_or_slug) ? array() : $Module->get_data($id_or_slug, $Module->has_slug() );
    if ( empty($model) ) {
        // page object not found or not active, 404
        return NULL;
    }

    $data = array('model' => $model);
    return $data;
}

/**
 * app_data_pages
 *
 * Retrieves page data and template along with any module data associated
 * with the page.
 *
 * @param string $uri The page uri (slug)
 * @param array $params Numerical array of additional parameters to pass into function call
 * @param bool $data_only True to echo model data only and NOT include template data
 * @return mixed The assoc array of page or page/module data or NULL if page and/or module
 * data not found
 * @throws \Exception if an application or runtime error occurs and is handled by the Slim error handler
 */
function app_data_pages($uri, $params=array(), $data_only=true) {
    global $Pages, $MODULE_PAGES, $GET_FORM_TASK, $GET_ITEM_TASK, $GET_LIST_TASK, $PAGE_TEMPLATE;
    if ( $Pages->is_page($uri) === false ) {
        //page not found, end of story
        return NULL;
    }

    $Module = Module::load();
    $module_name = $MODULE_PAGES;
    $tpl = $PAGE_TEMPLATE;
    $tpl_file = $uri;
    $data = array();
    $id_field = $Pages->has_slug() ? Model::MODEL_SLUG_FIELD : $Pages->get_pk_field();
    $page = $Pages->get_data($uri, true);
    $model = array();
    $is_list = false;
    $is_item = false;
    $is_form = false;
    $is_module = false;

    $task = '';
    if ( ! empty($params) ) {
        $task = $params[0];
        unset($params[0]);
        $params = array_values($params);
    }

    if ( empty($page) || empty($page['is_active']) ) {
        // page object not found or not active, 404
        return NULL;
    }

    // Add page breadcrumbs
    $page['breadcrumbs'] = template_breadcrumbs();

    if ( ! empty($page['module_id_list']) ) {
        $mod = $Module->get_data($page['module_id_list']);
        $module_name = empty($mod) ? '' : $mod['name'];
        $M1 = Module::load($module_name);
        $id_field = $M1->has_slug() ? Model::MODEL_SLUG_FIELD : $M1->get_pk_field();

        if ( in_array($task, array($GET_ITEM_TASK, 'item', 'detail') ) ) {
            $id_or_slug = false;
            if ( ! empty($params) ) {
                $id_or_slug = $params[0];
                unset($params[0]);
                $params = array_values($params);
            }

            $model = empty($id_or_slug) ? array() : $M1->get_data($id_or_slug, $M1->has_slug() );
            if ( empty($model) ) {
                // page object not found or not active, 404
                return NULL;
            }

            $tpl_file .= '_detail';
            $task = $GET_ITEM_TASK;
            $is_item = true;
        } else {
            $task = $GET_LIST_TASK;
            $is_list = true;
        }
        $tpl = $module_name;
        $is_module = true;
    } else if ( ! empty($page['module_id_form']) ) {
        $task = $GET_FORM_TASK;
        $mod = $Module->get_data($page['module_id_form']);
        $module_name = empty($mod) ? '' : $mod['name'];
        $data = get_form_data($module_name, $params);
        $model = $data['defaults'];
        $id_field = $data['idAttribute'];
        unset($data['defaults']);
        unset($data['idAttribute']);
        $tpl = $module_name;
        $is_form = true;
        $is_module = true;
    } else {
        $model = $page;
    }

    $json = get_template($tpl, $tpl_file, $page, $data_only, $is_module, true);
    $json['module'] = $module_name;
    $json['task'] = empty($task) ? $GET_ITEM_TASK : $task;
    $json['idAttribute'] = $id_field;

    if ($data_only) {
        if ($is_list || $is_item || $is_form) {
            $data['page'] = $page;
        }
        $json['model'] = $model;
    } else {
        if ($is_list || $is_item || $is_form) {
            $data['page'] = $page;
        }
        $json['bootstrapModel'] = $model;
        $url_param = $is_list ? 'collection_url' : 'model_url';
        $json[$url_param] = get_data_api_url($module_name, $task, $params);
    }

    if ( ! empty($data) ) {
        $json['data'] = $data;
    }

    return $json;
}

/**
 * app_template
 *
 * Handles a GET request to return template or a call by another function to retrieve template data.
 *
 * @param string $template The template name in /static directory
 * @param string $file The template file name (minus .phtml extension) within the template directory
 * or [$template]/content directory
 * @param bool $reset_current True to update the current template to this template
 * @param bool $is_module True if template is for module other than pages module
 * @return The assoc array of page or page/module data or NULL if page and/or module
 * data not found
 * @throws \Exception if an application or runtime error occurs and is handled by the Slim error handler
 */
function app_template($template='page', $file='default', $reset_current=true, $is_module=false) {
    global $App, $TPL_COOKIE;
    $json = get_template($template, $file, NULL, false, $is_module);
    $tpl_params = $json['params'];

    if ($reset_current) {
        $session = $App->session();
        $params = array(
            'name' => key($tpl_params),
            'file' => current($tpl_params)
        );
        $session->set_data($TPL_COOKIE, $params);
    }

    return $json;
}


/**
 * check_for_module
 *
 * Verifies a given module slug is to a valid module, module is not of "options" type and
 * module is not a core application module which cannot be displayed in a web page. If
 * not a valid module, will exit script and echo the specific error.
 *
 * @param string $module_name The module name (slug)
 * @param bool $show_404 True to not throw an exception but return false instead
 * @return bool True if valid module found, false if invalid and $show_404 param set to true
 * @throws \Exception if $show_404 false and module does not exist or invalid
 */
function check_for_module($module_name, $show_404=false) {
    $error = '';

    if ( Module::is_module($module_name) === false ) {
        $error = 'Module ['.$module_name.'] not found.';
    } else if ( Module::is_core($module_name) ) {
        $error = 'Module ['.$module_name.'] not valid for use in web page.';
    } else {
        $Module = Module::load($module_name);
        if ( $Module->is_options() ) {
            $error = 'Module ['.$module_name.'] is of options type, not valid for use in web page.';
        } else if ( $Module->is_active() === false ) {
            $error = 'Module ['.$module_name.'] is an inactive module.';
        }
    }

    if ( ! empty($error) ) {
        if ($show_404) {
            return false;
        } else {
            throw new \Exception($error);
        }
    }

    return true;
}


/**
 * get_data_api_url
 *
 * Generates the API url to retrieve module data without template.
 *
 * @param string $module_name The module name (slug)
 * @param string $func The module function (e.g. get, form) or custom function name
 * @param array $params Numerical array of additional parameters for URL
 * @return string The API url
 */
function get_data_api_url($module_name, $func, $params=array()) {
    global $APIURI;
    $url = WEB_BASE.'/'.$APIURI.'/'.$module_name.(empty($func) ? '' : '/'.$func);
    if ( ! empty($params) ) {
        foreach ($params as $param) {
            $url .= empty($param) ? '' : '/'.$param;
        }
    }
    return $url;
}


/**
 * get_form_data
 *
 * Generates the data for a form including form field defaults, validation parameters, form ID,
 * and module ID attribute. Also a CSRF token is generated for the form and included with the data.
 *
 * @param string $module_name The module name (slug)
 * @param array $params Numerical array of additional parameters for form
 * @return array The form data or NULL if module invalid
 * @throws \Exception if an application or runtime error occurs and is handled by the Slim error handler
 */
function get_form_data($module_name, $params=array() ) {
    global $App, $CSRF_FIELD, $GET_FORM_TASK;

    // verify module exists, return if not
    if ( check_for_module($module_name) === false ) {
        return NULL;
    }

    $Module = Module::load($module_name);
    $Csrf = $App->get_csrf();
    $form_fields = $Module->get_form_fields();
    $fields = array($CSRF_FIELD => array() );
    foreach ($form_fields as $field) {
        $field_name = $field->get_name();
        $ff_data = $field->get_data();
        $info = array();
        if ( ! empty($ff_data['validation']) ) {
            $info['valid'] = $ff_data['validation'];
        }
        if ( is_array($ff_data['default']) ) {
            $info['is_multiple'] = true;
        }
        $fields[$field_name] = $info;
    }
    $fields['g-recaptcha-response'] = array(); // for use with recaptcha

    $data = array();
    $csrf_token = $Csrf->get_token();
    $defaults = $Module->get_default_field_values($params);
    $defaults[$CSRF_FIELD] = $csrf_token;
    unset($defaults['reserved_fields']);
    $data['defaults']  = $defaults;
    $data['idAttribute'] = $Module->has_slug() ? Model::MODEL_SLUG_FIELD : $Module->get_pk_field();
    $data['form_id'] = $GET_FORM_TASK.'-'.$module_name;
    $data['fields'] = $fields;
    $data[$CSRF_FIELD] = $csrf_token;
    return $data;
}


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
 * get_template
 *
 * Returns the module template data.
 *
 * @param string $tpl The template name in /static directory
 * @param string $tpl_file The template file name (minus .phtml extension) within the template directory
 * or [$tpl]/content directory
 * @param array $model Assoc array of data to pass into template
 * @param bool $data_only True to include minimal template data instead of full template, for
 * data API calls
 * @param bool $is_module True if template for module other than pages module
 * @param bool $is_content True to return template content data instead of full template, note that
 * modules and pages may have a specific template, in which case, the full template will be returned
 * @return array The template data in an assoc array
 * @throws \Exception if an application or runtime error occurs and is handled by the Slim error handler
 */
function get_template($tpl, $tpl_file, $model=array(), $data_only=false, $is_module=false, $is_content=false) {
    global $Slim, $PAGE_TEMPLATE;

    if ( empty($tpl) ) {
        $tpl = $PAGE_TEMPLATE;
    }
    if ($is_module) {
        $default_file = Template::get_default_filename();

        // for modules, first check for [module]/content/[file].phtml
        if ( Template::is_template($tpl, $tpl_file, true) === false ) {

            // next check for [module]/default.phtml
            if ( Template::is_template($tpl, $default_file) === false ) {

                //if not, check for page/content/[file].phtml and if not there, we're done
                if ( Template::is_template($PAGE_TEMPLATE, $tpl_file, true) === false ) {
                    $static_dir = Template::get_static_dir();
                    $err = array('Template '.$tpl_file.' not found in:');
                    $err[] = $static_dir.'/'.$tpl.'/'.$default_file.'.phtml';
                    $err[] = $static_dir.'/'.$tpl.'/content/'.$tpl_file.'.phtml';
                    $err[] = $static_dir.'/'.$PAGE_TEMPLATE.'/content/'.$tpl_file.'.phtml';
                    $errors = array(
                        'errors' => $err
                    );
                    $Slim->halt(500, json_encode($errors));
                    exit;
                }

                //module uses default page template and is content file
                $tpl = $PAGE_TEMPLATE;
                $is_content = true;
            } else {
                //found template file so not a content file
                $tpl_file = $default_file;
                $is_content = false;
            }
        }
    }

    $template = $is_content ?
        Template::get_content($tpl, $tpl_file, $model, true) :
        Template::get($tpl, $tpl_file, $model, true);

    $json = array();
    $json['blocks'] = empty($template['blocks']) ? array() : $template['blocks'];
    $json['headTags'] = $template['head_tags'];
    $json['params'] = $template['params'];
    if ($data_only === false) {
        $json['template'] = $template['html'];
        $json['scripts'] = array(
            'css' => $template['css'],
            'js' => array(
                'src' => $template['js'],
                'onload' => $template['onload'],
                'unload' => $template['unload']
            )
        );
        $json['useJqm'] = $template['use_jqm'];
    }

    // needed to prevent endless loop call from app_template()
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

    if ( $caller !== 'app_template' && is_same_template($json['params']) === false ) {
        Template::reset();
        if ($data_only) {
            $json['template'] = $template['html'];
        }
        $json['newTpl'] = app_template($tpl, $tpl_file, true, $is_module);
    }

    return $json;
}


/**
 * is_same_template
 *
 * Checks the parameters of a template request to the current template to determine if they are
 * the same. If not, a new template is generated in the calling function.
 *
 * @param array $tpl_params Assoc array of the template parameters to compare containing
 * name => [template name] and file => [template file name]
 * @return bool True if template parameters are the same
 */
function is_same_template($tpl_params) {
    global $App, $TPL_COOKIE;
    if ( empty($tpl_params) || is_array($tpl_params) === false ) {
        return false;
    }

    $session = $App->session();
    $sess_params = $session->get_data($TPL_COOKIE);
    if ( empty($sess_params) ) {
        return false;
    }
    $tpl_name = key($tpl_params);
    $tpl_file = current($tpl_params);
    return $tpl_name === $sess_params['name'] && $tpl_file === $sess_params['file'];
}


/**
 * session_ping
 *
 * Keeps a session alive, resetting the timeout or creates a new session if inactive.
 *
 * @return bool True if session is active and not restarted
 */
function session_ping() {
    global $App;
    $session = $App->session();
    $session_active = $session->touch();
    if ( empty($session_active) ) {
        $session->session_start();
    }

    //set csrf token
    $Crsf = $App->get_csrf();
    $Crsf->set_token(false);

    return $session_active;
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

/**
 * $mw_ping
 *
 * Slim Middleware that keeps a session alive, resetting the timeout or creates a new session if inactive.
 *
 */
$mw_ping = function($request, $response, $next) {
    session_ping();
    return $next($request, $response);
};

/**
 * $route_404
 *
 * Handles any invalid (unknown route) for GET, POST and PUT requests,
 * triggers the Slim 404 error handler.
 *
 */
$route_404 = function($request, $response, $args) {
    throw new \Slim\Exception\NotFoundException($request, $response);
};

/**
 * $route_custom_func
 *
 * Handles a GET request to retrieve page data and template along with any module data associated
 * with the page.
 *
 */
$route_custom_func = function($request, $response, $args)  {
    $module = empty($args['module']) ? '' : $args['module'];
    $fn = empty($args['fn']) ? '' : $args['fn'];
    $params = empty($args['params']) ? array() : get_params_from_uri($args['params']);
    $vars = $request->getParsedBody();
    $data = app_custom_func($module, $fn, $params, $vars,true);
    if ($data === NULL) {
        // module not found, 404 page
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_data_list
 *
 * Handles a GET request to return module list page data.
 *
 */
$route_data_list = function($request, $response, $args) use ($App) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $archive = empty($args['archive']) ? '' : $args['archive'];

    if ( check_for_module($module_name) === false ) {
        // module not found, 404 page
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $module = Module::load($module_name);
    $get = $request->getQueryParams();
    $is_archive = $archive === 'archive';
    $data = $module->get_front_list($get, $is_archive);
    $uri = $request->getUri();

    //save sort params to session
    $cookie = $module->get_session_name().'_front';
    $session = $App->session();
    $params = $session->get_data($cookie);
    if ( ! empty($params['sort_by']) ) {
        if ($params['sort_by'] !== $data['state']['sort_by']) {
            //if sort column changed, set to first page
            $data['state']['page'] = 1;
        }
    } else {
        $params = array();
    }
    $params = $data['state'] + $params;
    $session->set_data($cookie, $params);

    $config = $data['state'] + $data['query_params'];
    $page = $config['page'];
    $total_pages = $config['total_pages'];
    $base_url = $uri->getBasePath().$uri->getPath().'?';
    $config['page'] = 1;
    $q = http_build_query($config);
    $header = '<'.$base_url.$q.'>; rel="first", ';
    $config['page'] = $total_pages;
    $q = http_build_query($config);
    $header .= '<'.$base_url.$q.'>; rel="last"';
    if ($page > 1) {
        $config['page'] = $page - 1;
        $q = http_build_query($config);
        $header .= ', <'.$base_url.$q.'>; rel="prev"';
    }
    if ($page < $total_pages) {
        $config['page'] = $page + 1;
        $q = http_build_query($config);
        $header .= ', <'.$base_url.$q.'>; rel="next"';
    }

    $App->load_util('template');
    $data['data'] = array('pagination' => template_pagination($total_pages, $page) );
    $response = set_headers($response, array('Link' => $header));
    return $response->withJson($data);
};

/**
 * $route_data_module
 *
 * Handles a GET request to retrieve module item data from database.
 *
 */
$route_data_module = function($request, $response, $args)  {
    $module = empty($args['module']) ? '' : $args['module'];
    $id = empty($args['id']) ? '' : $args['id'];
    $params = empty($args['params']) ? array() : get_params_from_uri($args['params']);
    $data = app_data_module($module, $id, $params);
    if ($data === NULL) {
    //page not found, 404 page
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_data_pages
 *
 * Handles a GET request to retrieve page data and template along with any module data associated
 * with the page.
 *
 */
$route_data_pages = function($request, $response, $args)  {
    $uri = empty($args['uri']) ? '' : $args['uri'];
    $params = empty($args['params']) ? array() : get_params_from_uri($args['params']);
    $data = app_data_pages($uri, $params, true);
    if ($data === NULL) {
        //page not found, 404 page
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_home
 *
 * Handles a GET request to retrieve the home page data and template.
 *
 */
$route_home = function($request, $response, $args) use ($GET_ITEM_TASK, $HOME_TEMPLATE, $PAGE_TEMPLATE, $MODULE_PAGES) {
    $tpl = $PAGE_TEMPLATE;
    $tpl_file = $HOME_TEMPLATE;
    if ( Template::is_template($tpl, $tpl_file, true) === false ) {
        $tpl = $HOME_TEMPLATE;
        $tpl_file = $PAGE_TEMPLATE;
    }

    $data = get_template($tpl, $tpl_file, NULL, false, false, true);
    $data['module'] = $MODULE_PAGES;
    $data['task'] = $GET_ITEM_TASK;

    //prevents a repeat API call for data already set in this call
    $data['bootstrapModel'] = array();

    $data['model_url'] = get_data_api_url($MODULE_PAGES, '');
    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_form_handler
 *
 * Handles a PUT or POST request to process a form submit.
 *
 */
$route_form_handler = function($request, $response, $args) use ($App, $CSRF_FIELD, $ERROR_DELIMETER) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $params = empty($args['params']) ? array() : get_params_from_uri($args['params']);

    // verify module exists, 404 page if not
    if ( check_for_module($module_name, true) === false ) {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $Module = Module::load($module_name);
    $Csrf = $App->get_csrf();
    $post = $request->getParsedBody();
    $json = array();
    if ( empty($post[$CSRF_FIELD]) || $Csrf->is_valid($post[$CSRF_FIELD]) === false ) {
        //CSRF token invalid, cannot continue
        throw new \Exception('Please refresh this page and fill out the form again.');
    }
    unset($post[$CSRF_FIELD]);

    $func = '';
    if ( ! empty($params) ) {
        if ( is_array($params) ) {
            $func = value($params);
            unset($params[ key($params) ]);
            $params = array_values($params);
        } else {
            $func = $params;
            $params = '';
        }
    }

    $script_path = Template::get_actions_path();
    $include_path = Template::get_actions_includes_path();
    $script_response = NULL;

    if ( Template::is_action_script($module_name, $func) ) {
        $config = Template::get_var();
        $anon = function () use (
            $App,
            $Module,
            $config,
            $script_path,
            $include_path,
            $module_name,
            $func,
            $params,
            &$post,
            &$script_response
        ) {
            include( Template::get_actions_script_file_path($module_name, $func) );
        };
        $anon();
    } else {
        $script = $module_name.(empty($func) ? '.phtml' : '/'.$func.'.phtml');
        throw new \Exception('Form handler '.$script.' not found.');
    }

    if ( empty($script_response) ) {
        $script_response = array();
    }

    if ( isset($script_response['errors']) ) {
        $errors = is_array($script_response['errors']) ?
                  implode($ERROR_DELIMETER, $script_response['errors']) :
                  $script_response['errors'];
        throw new \Exception($errors);
    } else if ( isset($script_response['message']) ) {
        $message = is_array($script_response['message']) ? $script_response['message'] : array($script_response['message']);
        $json = array('message' => $message);
    }

    if ( isset($script_response['redirect']) ) {
        $json['redirect'] = $script_response['redirect'];
    }  else if ( isset($script_response['fragment']) ) {
        $json['fragment'] = $script_response['fragment'];
    }

    $csrf_token = $Csrf->get_token();
    $model = $Module->get_default_field_values($params);
    $model[$CSRF_FIELD] = $csrf_token;
    unset($model['reserved_fields']);
    $json['model'] = $model;
    $json['clear_form'] = ! isset($script_response['clear_form']) || ! empty($script_response['clear_form']);

    $response = set_headers($response);
    return $response->withJson($json);
};

/**
 * $route_page_module
 *
 * Handles a GET request to retrieve page data and template along with any module data associated
 * with the page.
 *
 */
$route_page_module = function($request, $response, $args) use ($Pages, $GET_ITEM_TASK, $GET_LIST_TASK, $GET_FORM_TASK)  {
    $uri = empty($args['uri']) ? '' : $args['uri'];
    $params = empty($args['params']) ? array() : get_params_from_uri($args['params']);
    if ( $Pages->is_page($uri) ) {
        $data = app_data_pages($uri, $params, false);
        if ($data === NULL) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
        $response = set_headers($response);
        return $response->withJson($data);
    }

    // verify module exists, 404 page if not
    if ( check_for_module($uri, true) === false ) {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $task = $GET_LIST_TASK; // default task
    if ( ! empty($params) ) {
        $task = $params[0];
        unset($params[0]);
        $params = array_values($params);
    }

    $module_name = $uri;
    $Module = Module::load($module_name);
    $id_field = $Module->has_slug() ? Model::MODEL_SLUG_FIELD : $Module->get_pk_field();
    $tpl_file = $uri;
    $model = array();
    $data = array();
    $is_list = false;
    $func = '';

    switch ($task) {
        case $GET_ITEM_TASK:
        case 'item':
        case 'detail':
            $func = $GET_ITEM_TASK;
            $id_or_slug = false;
            if ( ! empty($params[0]) ) {
                $id_or_slug = $params[0];
                unset($params[0]);
                $params = array_values($params);
            }

            $model = empty($id_or_slug) ? array() : $Module->get_data($id_or_slug, $Module->has_slug() );
            if ( empty($model) ) {
                // page object not found or not active, 404
                throw new \Slim\Exception\NotFoundException($request, $response);
            }

            $tpl_file .= '_detail';
            break;
        case $GET_FORM_TASK:
            $func = $GET_FORM_TASK;
            $data = get_form_data($module_name, $params);
            $data['breadcrumbs'] = template_breadcrumbs();
            $model = $data['defaults'];
            $id_field = $data['idAttribute'];
            unset($data['defaults']);
            unset($data['idAttribute']);
        case $GET_LIST_TASK:
        case 'items':
            $func = $GET_LIST_TASK;
            $data['breadcrumbs'] = template_breadcrumbs();
            $is_list = true;
            break;
        default:
            $data = app_custom_func($module_name, $task, $params, array(),false);
            $response = set_headers($response);
            return $response->withJson($data);
    }

    $json = get_template($module_name, $tpl_file, $model, false, true, true);
    $json['module'] = $module_name;
    $json['task'] = $task;
    $json['idAttribute'] = $id_field;
    if ( ! empty($model) ) {
        $model['breadcrumbs'] = template_breadcrumbs();
        $json['bootstrapModel'] = $model;
    }
    if ( ! empty($data) ) {
        $json['data'] = $data;
    }

    $url_param = $is_list ? 'collection_url' : 'model_url';
    $json[$url_param] = get_data_api_url($module_name, $func, $params);
    $response = set_headers($response);
    return $response->withJson($json);
};

/**
 * $route_ping
 *
 * Handles a GET request to keeps a session alive, resetting the timeout or creates a new
 * session if inactive. Returns JSON with session_active: true|false. False is if session
 * was restarted.
 *
 */
$route_ping = function($request, $response, $args) {
    $data = array('session_active' => session_ping() );
    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_template
 *
 * Handles a GET request to retrieve the app template JSON.
 *
 */
$route_template = function($request, $response, $args) {
    $template = empty($args['template']) ? '' : $args['template'];
    $file = empty($args['file']) ? '' : $args['file'];
    $data = app_template($template, $file, true);
    $response = set_headers($response);
    return $response->withJson($data);
};


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
        $data = app_404();
        return $Container['response']->withStatus(404)->withJson($data);
    };
};

// Initialize Slim
$Slim = new \Slim\App($Container);

// GET routes
$Slim->get('/front/data/app[/{template}[/{file}]]', $route_template);
$Slim->get('/front/session/ping', $route_ping);
$Slim->get('/front/home', $route_home)->add($mw_ping);
$Slim->get('/front/data/home', $route_home)->add($mw_ping);
$Slim->get('/front/data/pages/home', $route_home)->add($mw_ping);
$Slim->get('/front/data/pages/{uri}[/{params:.*}]', $route_data_pages)->add($mw_ping);
$Slim->get('/front/data/pages/get/{uri}[/{params:.*}]', $route_data_pages)->add($mw_ping);
$Slim->get('/front/data/{module}/get/{id}[/{params:.*}]', $route_data_module)->add($mw_ping);
$Slim->get('/front/data/{module}/list[/{archive}]', $route_data_list)->add($mw_ping);
$Slim->get('/front/data/{module}/{fn}[/{params:.*}]', $route_custom_func)->add($mw_ping);
$Slim->get('/front/{uri}[/{params:.*}]', $route_page_module)->add($mw_ping);

// PUT routes
$Slim->put('/front/data/{module}/{fn}[/{params:.*}]', $route_form_handler)->add($mw_ping);

// POST routes
$Slim->post('/front/data/{module}/form[/{params:.*}]', $route_form_handler)->add($mw_ping);
$Slim->post('/front/data/{module}/{fn}[/{params:.*}]', $route_form_handler)->add($mw_ping);

// fallback route to 404 page
$Slim->any('/{params:.*}', $route_404)->add($mw_ping);

$Slim->run();
