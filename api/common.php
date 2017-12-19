<?php

$Slim = NULL;

function module_form_defaults($module_name, $params=array()) {
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


function module_form_save($module_name, $id='', $params=array()) {
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


function module_form_save_put($module_name, $params=array()) {
    module_form_save($module_name, false, $params);
}


function set_headers($add=array()) {
    global $Slim;
    $Slim->response->headers->set('Content-Type', 'application/json');
    $Slim->response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    $Slim->response->headers->set('Pragma', 'no-cache');
    $Slim->response->headers->set('Expires', '0');

    if ( ! empty($add) ) {
        foreach ($add as $name => $value) {
            $Slim->response->headers->set($name, $value);
        }
    }
}