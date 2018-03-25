<?php
use App\App;
use App\Html\Form\Form;
use App\Html\Navigation\AdminMenu;
use App\Module\Module;
use App\User\Authenticate;

// Load Slim Framework
require '../vendor/autoload.php';

// Abstract initialization
require 'App/App.php';
define('ERROR_LOG', '../logs/errors.log');
App::register_autoload();
$App = App::get_instance();
$Auth = new Authenticate();

// common vars/functions for admin/front
require 'common.php';


/**
 * app_data_pages
 *
 * Returns sample page data.
 *
 * @param string $slug Identifier for sample page
 * @return array Assoc array of sample page data or NULL if $slug param invalid
 */
function app_data_pages($slug) {
	global $Slim;
	$pages = array('home', 'sample');
	if ( ! in_array($slug, $pages) ) {
		return NULL;
	}

	$content = '';
	switch($slug) {
		case 'home':
            $content = <<<HTML

<h1>Home</h1>

<h2>Welcome to Abstract CMS</h2>

<p>Sed ultricies nunc vel posuere euismod. Aenean in sapien adipiscing, scelerisque mauris vel, 
dapibus nisl. Suspendisse venenatis dolor ipsum, vel lobortis dolor commodo ac. Nam ullamcorper 
adipiscing felis, vitae consequat magna sagittis non. Aenean nunc elit, interdum quis dignissim 
dignissim, aliquet nec leo. Quisque fermentum tempor volutpat. Ut ut arcu vel nunc porta 
vehicula.</p>

HTML;
			break;
		case 'sample':
            $content = <<<HTML

<h1>Sample Page</h1>

<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas tincidunt tristique fringilla. 
Duis malesuada, neque vel cursus placerat, augue risus tristique lacus, id tincidunt orci eros in erat. 
Donec adipiscing tincidunt gravida. Suspendisse erat ante, feugiat ac malesuada quis, aliquam eget felis. 
Donec viverra metus enim, ac ultricies justo ultrices vitae. Maecenas adipiscing pharetra augue in 
volutpat. Morbi tortor lorem, dictum nec sollicitudin eu, venenatis sed massa. Aenean ac ultricies 
metus. Morbi tristique felis tortor. Pellentesque habitant morbi tristique senectus et netus et 
malesuada fames ac turpis egestas. In ac elit sed purus fringilla rhoncus. Nam aliquam euismod 
ultrices. Morbi condimentum vulputate ipsum, nec elementum nisi sodales id. Aliquam ac euismod 
turpis, vitae porta nibh.</p>

<p>Sed ultricies nunc vel posuere euismod. Aenean in sapien adipiscing, scelerisque mauris vel, 
dapibus nisl. Suspendisse venenatis dolor ipsum, vel lobortis dolor commodo ac. Nam ullamcorper 
adipiscing felis, vitae consequat magna sagittis non. Aenean nunc elit, interdum quis dignissim 
dignissim, aliquet nec leo. Quisque fermentum tempor volutpat. Ut ut arcu vel nunc porta 
vehicula.</p>

HTML;
			break;
	}

	return array(
	    'data' => array(),
        'blocks' => array(),
        'scripts' => array(),
        'template' => $content
    );
}

/**
 * app_template
 *
 * Returns the admin page template data.
 *
 * @return array Assoc array of template data
 */
function app_template() {
	$template = <<<HTML



HTML;

    $navigation = app_template_navigation();
    $dialogs = app_template_dialogs();
	$data['blocks'] = empty($navigation) ? array($dialogs) : array($dialogs, $navigation);
	$data['template'] = $template;
	return $data;
}

/**
 * app_template_dialogs
 *
 * Returns the custom dialog box HTML used in admin pages.
 *
 * @return array Assoc array of data for dialog boxes
 */
function app_template_dialogs() {
    global $App;
    $dialog_html = <<<HTML

<div id="modal-warning" class="abstract-modal-popup" data-overlay-theme="b">
  <div data-role="header">
    <h1 id="modal-warning-label"></h1>
  </div>
  <div id="modal-warning-body" class="ui-content" data-role="main"></div>
  <div data-role="footer">
    <a href="#" class="modal-warning-close" data-role="button" data-transition="flow">Continue</a>
  </div>
</div>
  
<div id="modal-dialog" class="abstract-modal-popup" data-overlay-theme="b">
  <div data-role="header">
    <h1 id="modal-dialog-label"></h1>
  </div>
  <div id="modal-dialog-body" class="ui-content" data-role="main"></div>
  <div data-role="footer">
    <a href="#" class="modal-dialog-close" data-role="button" data-inline="true" data-transition="flow">Continue</a>
  </div>
</div>
  
<div id="modal-confirm" class="abstract-modal-popup" data-overlay-theme="b">
  <div data-role="header">
    <h1 id="modal-confirm-label"></h1>
  </div>
  <div id="modal-confirm-body" class="ui-content" data-role="main"></div>
  <div data-role="footer">
    <a href="#" class="modal-confirm-cancel" data-role="button" data-inline="true" data-transition="flow">Cancel</a>
    <a href="#" class="modal-confirm-ok" data-role="button" data-inline="true" data-transition="flow" data-theme="b">OK</a>
  </div>
</div>

HTML;

    return array(
        'selector' 	=> $App->config('page_content_id'),
        'pos_func' 	=> 'insertAfter',
        'html'		=> $dialog_html
    );
}

/**
 * app_template_navigation
 *
 * Returns the navigation menu HTML based on user permissions.
 *
 * @return array Assoc array of data for navigation menu
 */
function app_template_navigation() {
    global $Auth, $App;
    $user = $Auth->get_user_data();
    $navigation = NULL;

    if ( ! empty($user) ) {
        $user_id = $user['is_super'] ? false : $user['user_id'];
        $Menu = new AdminMenu($user_id, $user['is_super']);
        $nav = $Menu->generate();
        $navigation = array(
            'selector' 	=> $App->config('page_content_id'),
            'pos_func' 	=> 'insertAfter',
            'html'		=> $nav['navigation'].$nav['search']
        );
    }
    return $navigation;
}

/**
 * $mw_authorize
 *
 * Slim Middleware that checks if a user is authorized for the request.
 *
 */
$mw_authorize = function($request, $response, $next) use ($Auth) {
    $route = $request->getAttribute('route');
    $module_name = $route->getArgument('module');
    $method = $request->getMethod();

    if ( $Auth->authorize($module_name, $method) === false ) {
        $status_code = $Auth->is_logged_in() ? 403 : 401;
        return $response->withStatus($status_code);
    }

    return $next($request, $response);
};

/**
 * $route_authenticate
 *
 * Handles a GET request to authenticate user upon login.
 *
 */
$route_authenticate = function($request, $response, $args) use ($Auth)  {
    $post = $request->getParsedBody();
    $user = $post['user'];
    $pass = $post['pass'];
    $is_remember = ! empty($post['is_remember']);

    $auth = $Auth->authenticate($user, $pass, $is_remember);
    $error_msg = 'Username and/or password invalid';
    $is_auth = false;
    $return = array();

    if ( is_numeric($auth) ) {
        //auth failed, timeout in seconds returned
        $timeout = $auth;
        if ($timeout > 0) {
            //surpassed allowed login attempts
            $hrs = floor($timeout / 60 / 60);
            $min = floor(($timeout - ($hrs * 3600)) / 60);
            $sec = $timeout % 60;
            $time_str = ($hrs > 0 ? $hrs.($hrs == 1 ? ' hour ' : ' hours ') : '');
            $time_str .= ($min > 0 ? $min.($min == 1 ? ' minute ' : ' minutes ') : '');
            $time_str .= $sec > 0 ? $sec.' seconds' : '';
            $error_msg = '<div style="text-align:center;">You have reached the maximum<br/>allowed login attempts';
            $error_msg .= '<br/><br/>You will be able to retry in<br/><strong>'.$time_str.'</strong></div>';
        }
    } else if ( is_bool($auth) && $auth) {
        // successfully authenticated, generate nav menu, search panel
        $dialogs = app_template_dialogs();
        $navigation = app_template_navigation();
        $blocks = array($dialogs, $navigation);
        $return = array(
            "session_active" => true,
            "blocks" => $blocks
        );
        $is_auth = true;
    } else {
        $is_auth = false;
    }

    $data = $is_auth ? $return : array("error" => array("text" => $error_msg) );
    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_bulk_update
 *
 * Handles a PUT request to do a bulk update for module items.
 *
 */
$route_bulk_update = function($request, $response, $args) use ($ERROR_DELIMETER) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 error
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $module = Module::load($module_name);
    $post = $request->getParsedBody();
    $result = $module->bulk_update($post['task'], $post['ids']);
    $data = array('success' => false);
    if ( is_array($result) ) {
        $errors = $result['errors'];
        throw new \Exception( implode($ERROR_DELIMETER, $errors) );
    } else {
        $data['success'] = $result;
    }

    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_data_form
 *
 * Handles a GET request to retrieve data or process a custom form.
 *
 */
$route_data_form = function($request, $response, $args) use ($App) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $id = empty($args['id']) ? false : $args['id'];
    $params = empty($args['params']) ? array() : get_params_from_uri($args['params']);

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 error
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $module = Module::load($module_name);
    $data = $module->is_options() ? $module->get_options() : $module->get_field_values($id, $params);
    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_data_form_arrange
 *
 * Handles a GET request to retrieve data for an arrange form.
 *
 */
$route_data_form_arrange = function($request, $response, $args) use ($Auth) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $field_name = empty($args['field_name']) ? '' : $args['field_name'];
    $id = empty($args['id']) ? false : $args['id'];

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 error
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $module = Module::load($module_name);
    if ( $module->has_sort() === false ) {
        //sorting not used in module
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $Perm = $Auth->get_permission($module_name);
    $data = $module->get_cms_sort_form($field_name, $id, $Perm);
    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_data_list
 *
 * Handles a GET request to return module list page data.
 *
 */
$route_data_list = function($request, $response, $args) use ($App, $Auth) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $archive = empty($args['archive']) ? '' : $args['archive'];

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 page
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $module = Module::load($module_name);
    $get = $request->getQueryParams();
    $Perm = $Auth->get_permission($module_name);
    $is_archive = $archive === 'archive';
    $data = $module->get_cms_list($get, $is_archive, $Perm);
    $uri = $request->getUri();

    //save sort params to session
    $cookie = $module->get_session_name().'_admin';
    $session = $Auth->get_session();
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

    $response = set_headers($response, array('Link' => $header));
    return $response->withJson($data);
};

/**
 * $route_data_login
 *
 * Handles a GET request to retrieve the login page data.
 *
 */
$route_data_login = function($request, $response, $args)  {
    //error_log('GET /tpl/login'."\n", 3, ERROR_LOG);

    $fields = array(
        'user' => array(
            'type' => 'text',
            'valid' => array(
                'required' => true,
                'email' => true
            )
        ),
        'pass' => array(
            'type' => 'text',
            'valid' => array(
                'required' => true
            )
        )
    );

    $template = <<<HTML

<div id="abstract-content" class="abstract-login">
  <div class="abstract-login-cnt">
    <form id="form-signin" role="form">
      <h2 class="form-signin-heading">Please Sign In</h2>
      <div class="login-error"></div>
      <div>
        <input name="user" type="email" class="form-control" placeholder="Email address" required autofocus />
      </div>
      <div>
        <input name="pass" type="password" class="form-control" placeholder="Password" required />
      </div>
      <label class="checkbox">
        <input name="is_remember" type="checkbox" value="1" /> Remember me
      </label>
      <button id="submit-login" class="btn btn-lg btn-primary btn-block" type="submit">Login</button>
    </form><!--close #form-signin-->
  </div>
</div><!--close #abstract-login-->

HTML;

    $data = array();
    $data['fields'] = $fields;
    $data['template'] = $template;
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
    $slug = empty($args['slug']) ? '' : $args['slug'];
    $data = app_data_pages($slug);
    if ($data === NULL) {
        //page not found, 404 page
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_data_template
 *
 * Handles a GET request to retrieve the page template data.
 *
 */
$route_data_template = function($request, $response, $args)  {
    $data = app_template();
    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_delete_file
 *
 * Handles a DELETE request to delete a file or image associated with a module item.
 *
 */
$route_delete_file = function($request, $response, $args) use ($App, $ERROR_DELIMETER) {
    $module_name = empty($args['module']) ? '' : $args['module'];

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 page
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $post = $request->getParsedBody();
    $errors = array();
    if ( empty($post['file']) ) {
        $errors[] = 'POST[file] filename parameter undefined or empty';
    }
    if ( empty($post['cfg']) ) {
        $errors[] = 'POST[cfg] file upload config name parameter undefined or empty';
    }
    if ( ! empty($errors) ) {
        throw new \Exception( implode($ERROR_DELIMETER, $errors) );
    }

    $config_name = $post['cfg'];
    $file = $post['file'];
    $is_image = empty($post['img']) ? false : true;
    $has_deleted = 0;
    $data = array(
        'OK' => 0
    );

    $config = $App->upload_config($config_name, $is_image);
    if ($config === false) {
        $errors[] = ($is_image ? 'Image' : 'File').' upload config ['.$config_name.'] not found';
    }

    if ( empty($errors) ) {
        $filepath = $config['upload_path'].DIRECTORY_SEPARATOR.$file;
        $web_root = DOC_ROOT;

        if ( @is_file($web_root.$filepath) ) {
            unlink($web_root.$filepath);
            $has_deleted = 1;
        }

        if ( $is_image && $config['create_thumb']) {
            $ext_pos = strrpos($file, '.');
            $ext = substr($file, $ext_pos);
            $file = substr($file, 0, $ext_pos).$config['thumb_ext'].$ext;
            $filepath = $config['upload_path'].DIRECTORY_SEPARATOR.$file;

            if ( @is_file($web_root.$filepath) ) {
                unlink($web_root.$filepath);
            }
        }
        $data['OK'] = $has_deleted;
    } else {
        throw new \Exception( implode($ERROR_DELIMETER, $errors) );
    }

    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_delete_item
 *
 * Handles a DELETE request to delete a module item.
 *
 */
$route_delete_item = function($request, $response, $args) use ($ERROR_DELIMETER) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $id = empty($args['id']) ? false : $args['id'];

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 page
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $data = 0;
    $module = Module::load($module_name);
    $result = $module->delete($id);
    if ( is_array($result) ) {
        throw new \Exception( implode($ERROR_DELIMETER, $result['errors']) );
    } else {
        $data = 1;
    }

    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_form_arrange_save
 *
 * Handles a PUT request to save sorting for module items.
 *
 */
$route_form_arrange_save = function($request, $response, $args) use ($App, $ERROR_DELIMETER) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $field_name = empty($args['field_name']) ? '' : $args['field_name'];
    $relation_id = empty($args['id']) ? false : $args['id'];

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 error
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $module = Module::load($module_name);
    $post = $request->getParsedBody();
    $ids = json_decode($post['ids'], true);
    $data = $module->set_sort($ids, $field_name, $relation_id);
    if ( isset($data['errors']) ) {
        throw new \Exception( implode($ERROR_DELIMETER, $data['errors']) );
    }

    $json = $data ? 1 : 0;
    $response = set_headers($response);
    return $response->withJson($json);
};

/**
 * $route_form_custom_func
 *
 * Handles a GET, PUT or POST request to retrieve data or process a custom form.
 *
 */
$route_form_custom_func = function($request, $response, $args) use ($App, $ERROR_DELIMETER) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $fn = empty($args['fn']) ? '' : $args['fn'];
    $params = empty($args['params']) ? array() : get_params_from_uri($args['params']);

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 error
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $module = Module::load($module_name);
    $function = 'admin_func_'.$fn;
    $data = array();

    $method = $request->getMethod();
    $vars = array();
    if ($method === 'GET') {
        $vars = $request->getQueryParams();
    } else {
        $vars = $request->getParsedBody();
    }

    $var = array($module, $function);
    if ( is_callable($var) ) {
        $data = $module->$function($params, $vars);
        if ( ! empty($data['errors']) ) {
            throw new \Exception( implode($ERROR_DELIMETER, $data['errors']) );
        }
    } else {
        throw new \Exception('Module_'.$module_name.'->'.$function.'() undefined');
    }

    $response = set_headers($response);
    return $response->withJson($data);
};


/**
 * $route_form_defaults
 *
 * Handles a GET request to retrieve form field default values.
 *
 */
$route_form_defaults = function($request, $response, $args) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $params = empty($args['params']) ? array() : get_params_from_uri($args['params']);

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 error
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $module = Module::load($module_name);
    $data = $module->get_default_field_values($params);
    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_form_field_custom
 *
 * Handles a GET request to retrieve a custom form field HTML.
 *
 */
$route_form_field_custom = function($request, $response, $args) use ($Auth, $App) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $params = empty($args['params']) ? array() : get_params_from_uri($args['params']);
    $get = $request->getQueryParams();

    if ( Module::is_module($module_name) === false || empty($get['module']) || empty($get['field']) ) {
        // module not found, 404 error
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $field_module_name = $get['module'];
    $field = $get['field'];
    $value = $get['value'];
    $id = empty($get['id']) ? false : $get['id'];
    $function = 'form_field_'.$field;
    $module = Module::load($field_module_name);
    $Perm = $Auth->get_permission($module_name);
    $data = array();

    $var = array($module, $function);
    if ( is_callable($var) ) {
        try {
            $data['html'] = $module->$function($id, $value, $Perm, $params);
        } catch (Exception $e) {
            throw $e;
        }
    } else {
        $error = 'Module_'.$field_module_name.'->'.$function.'() undefined for field ['.$field.']';
        throw new \Exception($error);
    }

    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_form_save
 *
 * Handles a POST or PUT request to save module form data.
 *
 */
$route_form_save = function($request, $response, $args) use ($ERROR_DELIMETER) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $id = empty($args['id']) ? false : $args['id'];
    $params = empty($args['params']) ? array() : get_params_from_uri($args['params']);

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 page
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $module = Module::load($module_name);
    $post = $request->getParsedBody();
    $module_data = $module->get_module_data();
    $is_options = $module->is_options();
    $pk_field = $module_data['pk_field'];
    if ( ! $is_options && empty($post[$pk_field]) ) {
        $post[$pk_field] = $id;
    }
    $data = Form::form_data_values($module_name, $post);

    $result = NULL;
    if ($is_options) {
        $result = $module->update_options($data);
    } else if ( $module->is_main_module() ) {
        $result = empty($id) ? $module->create($data) : $module->modify($data);
    } else {
        $result = empty($id) ? $module->add($data) : $module->update($data);
    }
    $data = is_array($result) && isset($result['errors']) ? $result : $post;

    if ( isset($data['errors']) ) {
        throw new \Exception( implode($ERROR_DELIMETER, $data['errors']) );
    }

    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_logout
 *
 * Handles a GET request to logout an admin user.
 *
 */
$route_logout = function($request, $response, $args) use ($Auth) {
    $Auth->invalidate();
    $data = array("logged_out" => true);
    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_page_form
 *
 * Handles a GET request to return module form page data.
 *
 */
$route_page_form = function($request, $response, $args) use ($Auth) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $id = empty($args['id']) ? false : $args['id'];

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 page
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $module = Module::load($module_name);
    $Perm = $Auth->get_permission($module_name);
    $data = $module->get_cms_form($id, $Perm);

    if ( ! empty($id) && empty($data['model']) ) {
        //module row with $id does not exist
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_page_list
 *
 * Handles a GET request to return module list item (rows) data.
 *
 */
$route_page_list = function($request, $response, $args) use ($Auth) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $archive = empty($args['archive']) ? '' : $args['archive'];

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 error
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $module = Module::load($module_name);
    $Perm = $Auth->get_permission($module_name);
    $is_archive = $archive === 'archive';
    $data = $module->get_list_template($Perm, $is_archive);
    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_session_ping
 *
 * Handles a GET request to ping and keep a session alive.
 *
 */
$route_session_ping = function($request, $response, $args) use ($Auth) {
    $session_active = $Auth->get_session()->touch();
    $data = array("session_active" => $session_active);
    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_session_poll
 *
 * Handles a GET request to poll a session and return the time left.
 *
 */
$route_session_poll = function($request, $response, $args) use ($App, $Auth) {
    $session = $Auth->get_session();
    $time_left = $session->session_poll();

    if ($time_left <= 0) {
        $App->get_csrf()->invalidate();
    }
    $data = array();
    $data['session_active'] = $time_left > 0;
    $data['time_left'] = $time_left;
    $response = set_headers($response);
    return $response->withJson($data);
};

/**
 * $route_upload_file
 *
 * Handles a POST request to upload a file or image associated with a module item.
 *
 */
$route_upload_file = function($request, $response, $args) use ($App, $Auth, $ERROR_DELIMETER) {
    $module_name = empty($args['module']) ? '' : $args['module'];
    $config = empty($args['config']) ? '' : $args['config'];
    $type = empty($args['type']) ? '' : $args['type'];
    $pk = empty($args['pk']) ? false : $args['pk'];

    if ( Module::is_module($module_name) === false ) {
        // module not found, 404 page
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $Perm = $Auth->get_permission($module_name);
    if ( ( empty($pk) && $Perm->has_add() === false) || ( is_numeric($pk) && $Perm->has_update() === false) ) {
        //insufficient permissions
        throw new \Exception('You do not have upload permissions for module ['.$module_name.']');
    }

    $is_image = -1;
    $errors = array();
    $data = array(
        'filename' => '',
        'filesize' => 0
    );

    if ($type === 'i') {
        $is_image = true;
    } else if ($type === 'f') {
        $is_image = false;
    }
    if ( empty($config) ) {
        $errors[] = 'Upload config name must be specified';
    }
    if ($is_image === -1) {
        $errors[] = 'Upload type [file|image] must be specified';
    }

    $file_cfg = $App->upload_config($config, $is_image);
    if ($file_cfg === false) {
        $errors[] = ($is_image ? 'Image' : 'File').' upload config ['.$config.'] not found';
    }
    if ( ! empty($errors) ) {
        throw new \Exception( implode($ERROR_DELIMETER, $errors) );
    }

    //see https://github.com/brandonsavage/Upload

    /*
        TODO:
        - standardize error messages
    */

    require('Upload/Autoloader.php');
    \Upload\Autoloader::register();
    $file_path = DOC_ROOT.$file_cfg['upload_path'];
    $Upload = new \Upload\FileUpload($file_cfg, $file_path, 'file');
    $Upload->noCacheHeaders();
    $Upload->corsHeader();
    $data = $Upload->upload();
    $response = set_headers($response);
    return $response->withJson($data);
};


// Initialize Slim
$Slim = new \Slim\App($Container);

// GET routes
$Slim->get('/admin/page/{slug}', $route_data_pages);             //
$Slim->get('/admin/authenticate/logout', $route_logout);			 //
$Slim->get('/admin/session_poll', $route_session_poll);			 //Do not need authentication since
$Slim->get('/admin/session_poll/ping', $route_session_ping);		 //are global access template, form
$Slim->get('/admin/data/app', $route_data_template);				 //
$Slim->get('/admin/data/authenticate/login', $route_data_login);	 //

$Slim->get('/admin/data/{module}/form[/{id}]', $route_page_form)->add($mw_authorize);
$Slim->get('/admin/data/{module}/list[/{archive}]', $route_page_list)->add($mw_authorize);
$Slim->get('/admin/data/{module}/sort[/{field_name}[/{id}]]', $route_data_form_arrange)->add($mw_authorize);
$Slim->get('/admin/form_field_custom/{module}[/{params:.*}]', $route_form_field_custom)->add($mw_authorize);
$Slim->get('/admin/{module}/add[/{params:.*}]', $route_form_defaults)->add($mw_authorize);
$Slim->get('/admin/{module}/defaults[/{params:.*}]', $route_form_defaults)->add($mw_authorize);
$Slim->get('/admin/{module}/edit/{id}[/{params:.*}]', $route_data_form)->add($mw_authorize);
$Slim->get('/admin/{module}/list[/{archive}]', $route_data_list)->add($mw_authorize);
$Slim->get('/admin/{module}/update', $route_data_form)->add($mw_authorize);
$Slim->get('/admin/{module}/{fn}[/{params:.*}]', $route_form_custom_func)->add($mw_authorize);

// PUT routes
$Slim->put('/admin/bulk_update/{module}', $route_bulk_update)->add($mw_authorize);
$Slim->put('/admin/{module}/sort[/{field_name}[/{id}]]', $route_form_arrange_save)->add($mw_authorize);
$Slim->put('/admin/{module}/edit/{id}[/{params:.*}]', $route_form_save)->add($mw_authorize);
$Slim->put('/admin/{module}/update', $route_form_save)->add($mw_authorize);
$Slim->put('/admin/{module}/{fn}[/{params:.*}]', $route_form_custom_func)->add($mw_authorize);

// POST routes
$Slim->post('/admin/authenticate/login', $route_authenticate);
$Slim->post('/admin/upload_file/{module}/{config}/{type}[/{pk}]', $route_upload_file)->add($mw_authorize);
$Slim->post('/admin/{module}/add[/{params:.*}]', $route_form_save)->add($mw_authorize);
$Slim->post('/admin/{module}/update', $route_form_save)->add($mw_authorize);
$Slim->post('/admin/{module}/{fn}[/{params:.*}]', 'authorize', $route_form_custom_func)->add($mw_authorize);

// DELETE routes
$Slim->delete('/admin/delete_file/{module}', $route_delete_file)->add($mw_authorize);
$Slim->delete('/admin/{module}/delete/{id}', $route_delete_item)->add($mw_authorize);
$Slim->delete('/admin/{module}/{id}', $route_delete_item)->add($mw_authorize);

$Slim->run();