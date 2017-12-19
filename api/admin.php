<?php
use App\App;
use App\Html\Form\Form;
use App\Html\Navigation\AdminMenu;
use App\Module\Module;
use App\User\Authenticate;
use Slim\Slim;

require 'Slim/Slim.php';
require 'App/App.php';
require 'common.php';

define('ERROR_LOG', '../logs/errors.log');
Slim::registerAutoloader();
App::register_autoload();
$Slim = new Slim();
$App = App::get_instance();
$Auth = new Authenticate();

$Slim->get('/admin/page/:slug', 'page_data');

$Slim->get('/admin/authenticate/logout', 'logout');			//
$Slim->get('/admin/session_poll', 'session_poll');			//Do not need authentication since
$Slim->get('/admin/session_poll/ping', 'session_ping');		//are global access template, form
$Slim->get('/admin/data/app', 'template_app');				//
$Slim->get('/admin/data/authenticate/login', 'login_data');	//
$Slim->post('/admin/authenticate/login', 'authenticate');	//

$Slim->get('/admin/data/:module/sort(/:field_name)(/:id)', 'authorize', 'form_arrange_data');
$Slim->get('/admin/data/:module/form(/:id)', 'authorize', 'form_page');
$Slim->get('/admin/data/:module/list(/:archive)', 'authorize', 'list_data');
$Slim->get('/admin/form_field_custom/:module(/:params+)', 'authorize', 'form_field_custom');
$Slim->get('/admin/:module/(add|defaults)(/:params+)', 'form_defaults');
$Slim->get('/admin/:module/edit/:id(/:params+)', 'authorize', 'form_data');
$Slim->get('/admin/:module/list(/:archive)', 'authorize', 'list_page');
$Slim->get('/admin/:module/:fn(/:params+)', 'authorize', 'form_custom_func');

$Slim->put('/admin/bulk_update/:module', 'authorize', 'bulk_update');
$Slim->put('/admin/:module/sort(/:field_name)(/:id)', 'authorize', 'form_arrange_save');
$Slim->put('/admin/:module/edit/:id(/:params+)', 'authorize', 'form_save');
$Slim->put('/admin/:module/update', 'authorize', 'form_save');
$Slim->put('/admin/:module/:fn(/:params+)', 'authorize', 'form_custom_func');

$Slim->post('/admin/upload_file/:module/:config/:type(/:pk)', 'authorize', 'upload_file');
$Slim->post('/admin/:module/add(/:params+)', 'authorize', 'form_save_put');
$Slim->post('/admin/:module/update', 'authorize', 'form_save');
$Slim->post('/admin/:module/:fn(/:params+)', 'authorize', 'form_custom_func');

$Slim->delete('/admin/delete_file/:module', 'authorize', 'delete_file');
$Slim->delete('/admin/:module(/delete)/:id', 'authorize', 'delete_item');

$Slim->run();


function authenticate() {
	global $Slim, $Auth;
	$user = $Slim->request->post('user');
	$pass = $Slim->request->post('pass');
	$is_remember = ((int) $Slim->request->post('is_remember')) === 1;

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
        $dialogs = template_dialogs();
        $navigation = template_navigation();
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
    set_headers();
	echo json_encode($data);
}


function authorize($route) {
	global $Slim, $Auth;
	$module_name = $route->getParam('module');
	$method = $Slim->request->getMethod();

	if ( $Auth->authorize($module_name, $method) === false ) {
		$Slim->halt(401);
	}
}


function bulk_update($module_name) {
	global $Slim;
    if ( Module::is_module($module_name) === false ) {
    //module not found
        $Slim->halt(404);
    }

    $module = Module::load($module_name);
    $request = $Slim->request;
    $json = $request->getBody();
    $post = json_decode($json, true);
    $result = $module->bulk_update($post['task'], $post['ids']);
    $response = array('success' => false);
    if ( is_array($result) ) {
        $Slim->halt(500, json_encode($result) );
    } else {
        $response['success'] = $result;
    }

    set_headers();
	echo json_encode($response);
}


function delete_file($module_name) {
	global $Slim, $App;
	$request = $Slim->request;
	$body = $request->getBody();
	$args = explode('&', $body); 
	$post = array();
	foreach ($args as $arg) {
		$pair = explode('=', $arg); 
		if ( count($pair) === 2 ) {
			$post[ $pair[0] ] = $pair[1];
		}
	}

	if ( ! empty($post['file']) &&  ! empty($post['cfg']) ) {
		$config_name = $post['cfg'];
		$is_image = empty($post['img']) ? false : true;
		$file = $post['file'];
		$has_deleted = 0;
		$config = array();
		$errors = array();
		$response = array(
			'OK' => 0
		);
		
		$config = $App->upload_config($config_name, $is_image);
		if ($config === false) {
			$errors[] = ($is_image ? 'Image' : 'File').' upload config ['.$config_name.'] not found';
		}
		
		if ( empty($errors) ) {
			$filepath = $config['upload_path'].DIRECTORY_SEPARATOR.$file;
			$web_root = $_SERVER['DOCUMENT_ROOT'];

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
			$response['OK'] = $has_deleted;
		} else {
			$response['errors'] = $errors;
		}

        set_headers();
		echo json_encode($response);
	}
}


function delete_item($module_name, $id) {
	global $Slim;
	$response = NULL;
	if ( Module::is_module($module_name) ) {
		$module = Module::load($module_name);
		$result = $module->delete($id);
		if ( is_array($result) ) {
            $Slim->halt(500, json_encode($result) );
		} else {
			$response = 1;
		}
	} else {
		$Slim->halt(404);
	}
	
	set_headers();
	echo $response;
}


function form_arrange_data($module_name, $field_name=false, $id=false) {
	global $Slim, $Auth;

    if ( Module::is_module($module_name) === false ) {
    //module not found
        $Slim->halt(404);
    }

    $module = Module::load($module_name);
    $module_data = $module->get_module_data();
    if ( $module->has_sort() === false ) {
        //sorting not used in module
        $Slim->halt(404);
    }

    $Perm = $Auth->get_permission($module_name);
    $data = $module->get_cms_sort_form($field_name, $id, $Perm);
	set_headers();
	echo json_encode($data);
}


function form_arrange_save($module_name, $field_name='', $relation_id=false) {
	global $Slim;
	
	if ( Module::is_module($module_name) === false ) {
	//module not found
		$Slim->halt(404);
	}
	
	$module = Module::load($module_name);
	$request = $Slim->request;
	$json = $request->getBody();
	$post = json_decode($json, true);
    $ids = json_decode($post['ids'], true);

	$resp = $module->set_sort($ids, $field_name, $relation_id);
    if ( isset($resp['errors']) ) {
        $Slim->halt(500, json_encode($resp) );
    }

	set_headers();
	echo $resp ? 1 : 0;
}


function form_custom_func($module_name, $fn, $params=array()) {
	global $Slim, $App;
	
	if ( Module::is_module($module_name) === false ) {
	//module not found
		$Slim->halt(404);
	}
	
	$module = Module::load($module_name);
	$request = $Slim->request;
	$method = $request->getMethod();
	$vars = array();
	
	if ( strtoupper($method) === 'GET' ) {
		$vars = $request->get();
	} else {
		$req_body = $request->getBody();
		$vars = json_decode($req_body, true);
	}

	$function = 'admin_func_'.$fn;
	$var = array($module, $function);
	$response = array();
			
	if ( is_callable($var) ) {
        try {
            $response = $module->$function($params, $vars);
        } catch (Exception $e) {
            $error = 'An error has occurred in '.$module_name.'->'.$function.'()';
            if ( $App->config('debug') ) {
                $error .= ":<br/>\n".$e->getMessage();
            }
            $response['errors'] = array($error);
        }
	} else {
		$error[] = 'Module_'.$module_name.'->'.$function.'() undefined';
		$response['errors'] = array($error);
	}

    set_headers();
	echo json_encode($response);
}


function form_data($module_name, $id, $params=array()) {
	global $Slim;
	
	if ( Module::is_module($module_name) === false ) {
	//module not found
		$Slim->halt(404);
	}
	
	$module = Module::load($module_name);
	$data = $module->get_field_values($id, $params);
	
	set_headers();
	echo json_encode($data);
}


function form_defaults($module_name, $params=array()) {
	global $Slim;
	
	if ( Module::is_module($module_name) === false ) {
	//module not found
		$Slim->halt(404);
	}

	$module = Module::load($module_name);
	$data = $module->get_default_field_values($params);
	
	set_headers();
	echo json_encode($data);
}


function form_field_custom($module_name, $params=array()) {
	global $Slim, $Auth, $App;
	
	if ( ! empty($_GET['module']) && ! empty($_GET['field']) ) {
		if ( Module::is_module($_GET['module']) === false ) {
		//module not found
			$Slim->halt(404);
		}
		
		$field_module_name = $_GET['module'];
		$field = $_GET['field'];
		$value = $_GET['value'];
		$id = empty($_GET['id']) ? false : $_GET['id'];
		$function = 'form_field_'.$field;
		$module = Module::load($field_module_name);
		$Perm = $Auth->get_permission($module_name);
		$var = array($module, $function);
		$response = array();
			
		if ( is_callable($var) ) {
		    try {
                $response['html'] = $module->$function($id, $value, $Perm, $params);
            } catch (Exception $e) {
                $error = 'Field ['.$field.'] could not load due to error';
                if ( $App->config('debug') ) {
                    $error .= ":<br/>\n".$e->getMessage();
                }
                $response['errors'] = array($error);
            }
		} else {
			$error = 'Module_'.$field_module_name.'->'.$function.'() undefined for field ['.$field.']';
			$response['errors'] = array($error);
		}

        set_headers();
		echo json_encode($response);
	}
}


function form_page($module_name, $id=false) {
	global $Slim, $Auth;
	$data = false;
	if ( Module::is_module($module_name) ) {
		$module = Module::load($module_name);
		$Perm = $Auth->get_permission($module_name);
		$data = $module->get_cms_form($id, $Perm);
	} 
	
	if ( empty($module) || ( ! empty($id) && empty($data['model']) ) ) {
	//module not found or module row with $id does not exist
		$Slim->halt(404);
	}

	set_headers();
	echo json_encode($data);
}


function form_save($module_name, $id='', $params=array()) {
	global $Slim;
	
	if ( Module::is_module($module_name) === false ) {
	//module not found
		$Slim->halt(404);
	}
	
	$module = Module::load($module_name);
	$request = $Slim->request;
	$json = $request->getBody();
	$post = json_decode($json, true);
	
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
	$resp = is_array($result) && isset($result['errors']) ? $result : $post;
	
	if ( isset($resp['errors']) ) {
		$Slim->halt(500, json_encode($resp) );
	}
	
	set_headers();
	echo json_encode($resp);
}


function form_save_put($module_name, $params=array()) {
	form_save($module_name, false, $params);
}


function list_data($module_name, $archive='') {
	global $Slim, $Auth;
	if ( Module::is_module($module_name) === false ) {
	//module not found
		$Slim->halt(404);
	}
	
	$module = Module::load($module_name);
	$Perm = $Auth->get_permission($module_name);
    $is_archive = $archive === 'archive';
	$data = $module->get_list_template($Perm, $is_archive);
	
	set_headers();
	echo json_encode($data);
}


function list_page($module_name, $archive='') {
	global $Slim, $Auth;
	
	if ( Module::is_module($module_name) === false ) {
	//module not found
		$Slim->halt(404);
	}

	$module = Module::load($module_name);
	$request = $Slim->request;
	$get = $request->get();
	$Perm = $Auth->get_permission($module_name);
    $is_archive = $archive === 'archive';
	$data = $module->get_cms_list($get, $is_archive, $Perm);
	
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
	$base_url = $request->getRootUri().$request->getResourceUri().'?';
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
	
	set_headers();
	$Slim->response->headers->set('Link', $header);
	echo json_encode($data);
}


function login_data() {
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
	$json_data = array();
	$json_data['fields'] = $fields;
	$json_data['template'] = $template;
	set_headers();
	echo json_encode($json_data);
}


function logout() {
	global $Auth;
	$Auth->invalidate();
	$data = array("logged_out" => true);
	echo json_encode($data);
}


function page_data($slug) {
	global $Slim;
	$pages = array('home', 'sample');
	if ( ! in_array($slug, $pages) ) {
		$Slim->halt(404);
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

	$data = array(
	    'data' => array(),
        'blocks' => array(),
        'scripts' => array(),
        'template' => $content
    );
    echo json_encode($data);
}


function session_ping() {
	global $Auth;
	$session_active = $Auth->get_session()->touch();
	$data = array("session_active" => $session_active);
	set_headers();
	echo json_encode($data);
}


function session_poll() {
	global $App, $Auth;
	$session = $Auth->get_session();
	$time_left = $session->session_poll();
	
	if ($time_left <= 0) {
		$App->get_csrf()->invalidate();
	}
	$data = array();
	$data['session_active'] = $time_left > 0;
	$data['time_left'] = $time_left;
	
	set_headers();
	echo json_encode($data);
}


function template_app() {
    global $App;
	$template = <<<HTML



HTML;

    $navigation = template_navigation();
    $dialogs = template_dialogs();
	$data['blocks'] = empty($navigation) ? array($dialogs) : array($dialogs, $navigation);
	$data['template'] = $template;
	
	set_headers();
	echo json_encode($data);
}

function template_dialogs() {
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


function template_navigation() {
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


function upload_file($module_name, $config, $type, $pk=false) {
    global $App, $Auth, $Slim;

    if ( Module::is_module($module_name) === false ) {
        //module not found
        $Slim->halt(404);
    }

    $Perm = $Auth->get_permission($module_name);
    if ( ( empty($pk) && $Perm->has_add() === false) || ( is_numeric($pk) && $Perm->has_update() === false) ) {
        //insufficient permissions
        $response['errors'] = array('You do not have upload permissions for module ['.$module_name.']');
        die( json_encode($response) );
    }

	$is_image = -1;
	$errors = array();
	$response = array(
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
		$response['errors'] = $errors;
		die( json_encode($response) );
	}
	
	//see https://github.com/brandonsavage/Upload
	
	/*
		TODO: 
		- standardize error messages
	*/
	
	require('Upload/Autoloader.php');
	\Upload\Autoloader::register();
	$file_path = $_SERVER['DOCUMENT_ROOT'].$file_cfg['upload_path'];
	$Upload = new \Upload\FileUpload($file_cfg, $file_path, 'file');
	$Upload->noCacheHeaders();
	$Upload->corsHeader();
	$response = $Upload->upload();
	echo json_encode($response);
}
