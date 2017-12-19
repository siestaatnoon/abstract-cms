<?php

use
App\App,
App\Html\Template\Template,
App\Module\Module;


function get_content() {
    return <<<HTML

<p>Cras eget metus eu magna porttitor dapibus a id mauris. Proin pulvinar a justo in maximus. 
Integer blandit sem vitae efficitur lacinia. Praesent ac ante tincidunt, vulputate tortor et, 
laoreet ex. Nunc aliquet ullamcorper libero, maximus dapibus lorem tincidunt at.</p>

<p>Fusce pellentesque porta ultricies. Praesent ac enim est. Cras quis metus egestas, rutrum 
nisi sit amet, porta magna. Morbi a mollis ex, vitae dictum lectus. Nullam porttitor sem sit 
amet nulla blandit, consectetur commodo risus congue. Vestibulum nec mauris eros.</p>

HTML;
}

function get_meta_keywords() {
    return "abstract, meta, keywords, test";
}

function get_meta_description() {
    return "Meta description for a test page";
}

function get_title() {
    return "Lorem ipsum dolor sit amet";
}


/**
 * template_app_path
 *
 * Returns the current page URL (HTTP referrer, not from AJAX call) without the
 * hostname and, optionally, without web root. Note that the leading slash is included.
 *
 * @param bool True to strip the application web root
 * @return The current page URL
 */
if ( ! function_exists('template_app_path'))
{
    function template_app_path($strip_webroot=false) {
        $referrer = $_SERVER['HTTP_REFERER'];
        $base_url = template_app_url();
        $path = str_replace($base_url, '', $referrer);
        return $strip_webroot ? $path : WEB_BASE.$path;
    }
}


/**
 * template_app_url
 *
 * Returns the base URL for the application, including web root. Note that the trailing slash is omitted.
 *
 * @return The application base URL
 */
if ( ! function_exists('template_app_url'))
{
    function template_app_url() {
        $protocol = strtolower( substr($_SERVER["SERVER_PROTOCOL"],0,5) ) === 'https' ? 'https' : 'http';
        return $protocol.'://'.$_SERVER['HTTP_HOST'].WEB_BASE;
    }
}


/**
 * template_breadcrumbs
 *
 * Generates the breadcrumbs HTML.
 *
 * @param string $url The current page URL
 * @param array $add_before_page Array of url => title crumbs to add between second to last
 * crumb and current page crumb
 * @param array $replace Array of url => title crumbs to replace between Home page
 * crumb and current page crumb
 * @return The breadcrumbs HTML
 */
if ( ! function_exists('template_breadcrumbs'))
{
    function template_breadcrumbs($add_before_page=array(), $replace=array()) {
        $App = App::get_instance();
        $url = template_app_path(true);
        $Pages = Module::load('pages');
        $module_item_segments = array('detail', 'item');
        $bc = array(WEB_BASE.'/home' => 'Home');
        $add_bc = array();
        $Module = NULL;
        $module_data = array();

        $uri = substr($url, 0, 1) === '/' ? substr($url, 1) : $url;
        $mod_func = '';
        $id_or_slug = '';
        $segments = explode('/', $uri);
        if ( count($segments) > 1 ) {
            $uri = $segments[0];
            $mod_func = $segments[1];
            if ( ! empty($segments[2]) ) {
                $id_or_slug = $segments[2];
            }
        }

        if ( $Pages->is_page($uri) ) {
            $model = $Pages->get_model();
            $page_id = $model->get_page_id_by_slug($uri);
            $add_bc = $model->get_for_breadcrumbs($page_id);

            // check if has module list associated
            $page = $Pages->get_data($uri, true);
            if ( ! empty($page['module_id_list']) ) {
                $M = Module::load();
                $module_data = $M->get_data($page['module_id_list']);
                if ( ! empty($module_data) ) {
                    $Module = Module::load($module_data['name']);
                }
            }
        } else if ( Module::is_module($uri) && Module::is_core($uri) === false ) {
            $Module = Module::load($uri);
            $module_data = $Module->get_module_data();
            $add_bc[$uri] = $module_data['label_plural'];
        } else {
            return '';
        }

        if ( $Module !== NULL && ! empty($module_data) && in_array($mod_func, $module_item_segments) ) {
            $item = empty($id_or_slug) ? array() : $Module->get_data($id_or_slug, $Module->has_slug() );
            if ( ! empty($item) ) {
                $add_bc[$url] = $item[ $module_data['title_field'] ];
            }
        }

        if ( ! empty($replace) || ! empty($add_before_page) ) {
            $end = end($add_bc);
            $key = key($add_bc);
            if ( ! empty($replace) ) {
                $bc = $bc + $replace;
            } else {
                unset($add_bc[$key]);
                $bc = $bc + $add_bc + $add_before_page;
            }
            $bc[$key] = $end;
        } else {
            $bc = $bc + $add_bc;
        }

        $framework = $App->config('front_framework');
        $func_file = empty($framework) ? 'template_bootstrap' : 'template_'.$framework;
        $App->load_util($func_file);
        $html = '';
        if ( is_callable('fw_breadcrumbs') ) {
            $html = fw_breadcrumbs($bc);
        }
        return $html;
    }
}

/**
 * template_pagination
 *
 * Generates pagination HTML.
 *
 * @param int $total_pages Total pages of pagination links
 * @param int $page_num The current page number
 */
if ( ! function_exists('template_pagination'))
{
    function template_pagination($total_pages, $page_num=1) {
        if ($total_pages <= 1) {
            return '';
        } else if ($page_num > $total_pages || $page_num < 1) {
            $page_num = 1;
        }

        $SINGLE_PAGE_NUM_LINKS = 8;
        $pagination = array();
        if ($page_num > 1) {
            if ($total_pages > 2) {
                $pagination[] = array(
                    'label' => '&laquo;',
                    'li_class' => 'arrow',
                    'class' => 'pager-control page-first',
                    'title' => 'First'
                );
            }
            $pagination[] = array(
                'label' => '&lt;',
                'li_class' => 'arrow',
                'class' => 'pager-control page-prev',
                'title' => 'Previous'
            );
        }

        $step = 1;
        if ($total_pages >= 15) {
            $step = 5;
        } else if ($total_pages > 25 && $total_pages <= 100) {
            $step = 10;
        } else if ($total_pages > 100) {
            $step = (int) ( (10 * floor($total_pages/100) ) + (10 * round(($total_pages % 100) / 100) ) );
        }


        $page_start = $page_num - floor($SINGLE_PAGE_NUM_LINKS / 2);
        if ($page_start < 0) {
            $page_start = 0;
        }
        $page_end = $page_start + $SINGLE_PAGE_NUM_LINKS;
        if ($page_end > $total_pages) {
            $start = $total_pages - $SINGLE_PAGE_NUM_LINKS;
            $page_start = $start < 0 ? 0 : $start;
            $page_end = $total_pages;
        }
        for ($s=$step; $s < $total_pages; $s+=$step) {
            $is_between_steps = $page_start >= $s && $page_start <= ($s + $step);
            $has_page = false;
            $page_step = array(
                'label' => $s,
                'li_class' => '',
                'class' => 'pager-selector',
                'data-page' => $s
            );
            if ( ($s === $step && $s > $page_start && $s > $page_end) || ($page_start < $s && $s <= $page_end) || $is_between_steps) {
                $i = $page_start == 0 ? 1 : $page_start;
                if ($s < $page_start) {
                    $pagination[] = $page_step;
                }
                for ($i; $i <= $page_end; $i++) {
                    if ($i % $step === 0) {
                        $s = $i;
                        $has_page = true;
                    }
                    $pagination[] = array(
                        'label' => $i,
                        'li_class' => $i == $page_num ? 'current active' : '',
                        'class' => 'pager-selector',
                        'data-page' => $i
                    );
                }
            }

            if ( ! $has_page && ! $is_between_steps ) {
                $pagination[] = $page_step;
            }
        }

        if ($page_num < $total_pages) {
            $pagination[] = array(
                'label' => '&gt;',
                'li_class' => 'arrow',
                'class' => 'pager-control page-next',
                'title' => 'Next'
            );
            if ($total_pages > 2) {
                $pagination[] = array(
                    'label' => '&raquo;',
                    'li_class' => 'arrow',
                    'class' => 'pager-control page-last',
                    'title' => 'Last'
                );
            }
        }

        $App = App::get_instance();
        $framework = $App->config('front_framework');
        $func_file = empty($framework) ? 'template_bootstrap' : 'template_'.$framework;
        $App->load_util($func_file);
        $html = '';
        if ( is_callable('fw_pagination') ) {
            $html = fw_pagination($pagination);
        }
        return $html;
    }
}

/**
 * template_css
 *
 * Adds CSS include(s) to a template. Helper function for Template class.
 *
 * @param mixed $files Source path or array of CSS source paths from template <strong>static</strong> directory
 */
if ( ! function_exists('template_css'))
{
    function template_css($files) {
        if (empty($files)) {
            return;
        }

        Template::css($files);
    }
}

/**
 * template_file_root
 *
 * Returns the full root-relative path to a file given the application relative directory.
 *
 * @param string $filepath Path to file within application directory
 * @return string The full root-relative path to file
 */
if ( ! function_exists('template_file_root'))
{
    function template_file_root($filepath) {
        if (empty($filepath)) {
            return $filepath;
        } else if (substr($filepath, 0, 1) !== '/') {
            $filepath = '/' . $filepath;
        }

        return Template::get_web_root() . $filepath;
    }
}

/**
 * template_file_static
 *
 * Returns the root-relative path to a file from the template <strong>static</strong> directory.
 *
 * @param string $filepath Path to file within template <strong>static</strong> directory
 * @return string The full root-relative path to file
 */
if ( ! function_exists('template_file_static'))
{
    function template_file_static($filepath) {
        if (empty($filepath)) {
            return $filepath;
        } else if (substr($filepath, 0, 1) !== '/') {
            $filepath = '/' . $filepath;
        }

        return Template::get_static_dir() . $filepath;
    }
}

/**
 * template_var
 *
 * Returns the template variables defined in ./abstract.json or, if $name parameter
 * provided, returns that value.
 *
 * @param string $name Name of the variable to retrieve, blank to retrieve all variables
 * @return string The template variables or variable by name
 */
if ( ! function_exists('template_var'))
{
    function template_var($name='') {
        return Template::get_var($name);
    }
}

/**
 * template_head_tags
 *
 * Adds tags to head tag in page DOM. Helper function for Template class.
 *
 * @param mixed $head Array for head tag configuration
 * @see Template::head_tags() for parameter format
 */
if ( ! function_exists('template_head_tags'))
{
    function template_head_tags($head) {
        if (empty($head)) {
            return;
        }

        Template::head_tags($head);
    }
}

/**
 * template_file_path
 *
 * Returns the full root-relative path to the file upload directory given a config name
 * from ./App/config/uploads.php. Note that the path returned has a slash at the end.
 *
 * @param string $cfg_name Path to file within application directory
 * @return string The full root-relative file path
 */
if ( ! function_exists('template_file_path'))
{
    function template_file_path($cfg_name) {
        if ( empty($cfg_name) ) {
            return '';
        }

        return Template::get_file_path($cfg_name);
    }
}

/**
 * template_img_path
 *
 * Returns the full root-relative path to the image upload directory given a config name
 * from ./App/config/uploads.php. Note that the path returned has a slash at the end.
 *
 * @param string $cfg_name Path to image within application directory
 * @return string The full root-relative image path
 */
if ( ! function_exists('template_img_path'))
{
    function template_img_path($cfg_name) {
        if ( empty($cfg_name) ) {
            return '';
        }

        return Template::get_img_path($cfg_name);
    }
}

/**
 * template_include
 *
 * Adds CSS and/or Javascript source include(s) to a template.
 *
 * @param array $arr Associative array of:<br/><br/>
 * <ul>
 *   <li>css => Source path or array of CSS source paths from template <strong>static</strong> directory</li>
 *   <li>js => Source path or array of Javascript source paths from template <strong>static</strong> directory</li>
 * </ul>
 */
if ( ! function_exists('template_include'))
{
    function template_include($arr) {
        if ( empty($arr) || ! is_array($arr) ) {
            return;
        }

        if ( ! empty($arr['css']) ) {
            Template::css($arr['css']);
        }

        if ( ! empty($arr['js']) ) {
            Template::js($arr['js']);
        }

        if ( ! empty($arr['onload']) ) {
            Template::onload($arr['onload']);
        }

        if ( ! empty($arr['unload']) ) {
            Template::unload($arr['unload']);
        }
    }
}

/**
 * template_is_external_url
 *
 * Determines if given link to another web page.
 *
 * @param string $url The URL to check
 * @return bool True if URL is external web page
 */
if ( ! function_exists('template_is_external_url'))
{
    function template_is_external_url($url) {
        if ( empty($url) || ( substr($url, 0, 5) !== 'http:' && substr($url, 0, 6) !== 'https:') ) {
            return false;
        }

        $non_ssl_url = str_replace('https:', 'http:', template_app_url() );
        $ssl_url = str_replace('http:', 'https:', $non_ssl_url);
        return strpos($url, $ssl_url) !== false || strpos($url, $non_ssl_url) !== false;
    }
}

/**
 * template_js
 *
 * Adds CSS include(s) to a template. Helper function for Template class.
 *
 * @param mixed $files Source path or array of Javascript source paths from template <strong>static</strong> directory
 */
if ( ! function_exists('template_js'))
{
    function template_js($files) {
        if (empty($files)) {
            return;
        }

        Template::js($files);
    }
}

/**
 * template_navigation
 *
 * Generates the navigation bar HTML.
 *
 * @return string The navbar HTML
 */
if ( ! function_exists('template_navigation'))
{
    function template_navigation() {
        $App = App::get_instance();
        $Pages = Module::load('pages');
        $model = $Pages->get_model();
        $nav_config = $App->config('front_nav_config');
        $pages = $model->get_tree();
        $nav = array();

        $get_nav_array = function($subpages, $add_items) use (&$get_nav_array) {
            if ( empty($subpages) && empty($add_items) ) {
                return array();
            }

            $items = array();
            $before = array();
            $after = array();
            $last = array();

            if ( ! empty($add_items) ) {
                foreach ($add_items as $i => $item) {
                    $new = array(
                        'label' => empty($item['label']) ? 'Item '.($i + 1) : $item['label'],
                        'url' => empty($item['url']) ? '#' : template_root_relative_url($item['url'])
                    );
                    if ( ! empty($item['items']) ) {
                        $new['items'] = $get_nav_array(array(), $item['items']);
                    }
                    if ( ! empty($item['position']) ) {
                        $pos = $item['position'];
                        unset($item['position']);
                        if ($pos === 'first') {                 // "first" goes to top of list
                            $items[] = $new;
                        } else {                                // "last" or other string, but defaults to last in list
                            $last[] = $new;
                        }
                    } else if ( ! empty($item['before']) ) {
                        $page_id = $item['before'];
                        unset($item['before']);
                        if ( ! isset($before[$page_id]) ) {
                            $before[$page_id] = array();
                        }
                        $before[$page_id][] = $new;
                    } else if ( ! empty($item['after']) ) {
                        $page_id = $item['after'];
                        unset($item['after']);
                        if ( ! isset($after[$page_id]) ) {
                            $after[$page_id] = array();
                        }
                        $after[$page_id][] = $new;
                    }
                }
            }

            if ( empty($subpages) ) {
                $bef = array();
                foreach ($before as $id => $arr) {
                    $bef = array_merge($bef, $arr);
                }
                $aft = array();
                foreach ($after as $id => $arr) {
                    $aft = array_merge($aft, $arr);
                }
                $items = array_merge($items, $bef, $aft, $last);
            } else {
                foreach ($subpages as $page) {
                    if ( empty($page['is_active']) ) {
                        continue;
                    }
                    $page_id = $page['page_id'];
                    $item = array(
                        'label' => $page['short_title'],
                        'url' => template_root_relative_url($page['slug']),
                        'items' => $get_nav_array($page['subpages'], array() )
                    );
                    if ( isset($before[$page_id]) ) {
                        $items = array_merge($items, $before[$page_id]);
                    }
                    $items[] = $item;
                    if ( isset($after[$page_id]) ) {
                        $items = array_merge($items, $after[$page_id]);
                    }

                }
                if ( ! empty($last) ) {
                    $items = array_merge($items, $last);
                }
            }

            return $items;
        };

        foreach ($nav_config as $i => $cfg) {
            $page_id = empty($cfg['page_id']) ? 0 : $cfg['page_id'];
            $page =  isset($pages[$page_id]) ? $pages[$page_id] : array();
            $item = array();

            $label = 'Menu Item '.($i + 1);
            if ( ! empty($cfg['label']) ) {
                $label = $cfg['label'];
            } else if ( ! empty($page) ) {
                $label = $page['short_title'];
            }
            $item['label'] = $label;

            $url = '#';
            if ( ! empty($cfg['url']) ) {
                $url = template_root_relative_url($cfg['url']);
            } else if ( ! empty($page) ) {
                $url = template_root_relative_url($page['slug']);
            }
            $item['url'] = $url;

            if ( ! empty($cfg['items']) || ! empty($page['subpages']) ) {
                $subpages = empty($page['subpages']) ? array() : $page['subpages'];
                $add_items = empty($cfg['items']) ? array() : $cfg['items'];
                $item['items'] = $get_nav_array($subpages, $add_items);
            }


            $nav[] = $item;
        }

        $framework = $App->config('front_framework');
        $func_file = empty($framework) ? 'template_bootstrap' : 'template_'.$framework;
        $App->load_util($func_file);
        $has_search = $App->config('front_nav_has_search');
        $html = '';
        if ( is_callable('fw_navbar') ) {
            $html = fw_navbar($nav, $has_search);
        }
        return $html;
    }
}

/**
 * template_onload
 *
 * Adds javascript executable upon page load. Helper function for Template class.
 *
 * @param string $js_code The executable javascript
 */
if ( ! function_exists('template_onload'))
{
    function template_onload($js_code) {
        if ( empty($js_code) ) {
            return;
        }

        Template::onload($js_code);
    }
}

/**
 * template_root_relative_url
 *
 * Converts a slug-type uri string into an application root relative URL. Note that
 * this will return all full-domain paths and javascript:, mailto:, tel:
 *
 * @param string $param The URL to convert
 * @return string The root relative URL or $param if a full path or special URL
 */
if ( ! function_exists('template_root_relative_url')) {
    function template_root_relative_url($param) {
        if ( empty($param) ) {
            return '';
        }

        $base_url = WEB_BASE;
        $uri = (substr($param, 0, 1) === '/' ? '' : '/').$param;
        if ( $param === '#' ||
            substr($param, 0, 5) === 'http:' ||
            substr($param, 0, 6) === 'https:' ||
            substr($param, 0, 11) === 'javascript:' ||
            substr($param, 0, 7) === 'mailto:' ||
            substr($param, 0, 4) === 'tel:' ||
            substr($uri, 0, strlen($base_url) ) === $base_url ) {
            return $param;
        }

        return $base_url.$uri;
    }
}

/**
 * template_unload
 *
 * Adds javascript executable upon page load. Helper function for Template class.
 *
 * @param string $js_code The executable javascript
 */
if ( ! function_exists('template_unload'))
{
    function template_unload($js_code) {
        if ( empty($js_code) ) {
            return;
        }

        Template::unload($js_code);
    }
}

/**
 * template_use_jqm
 *
 * Sets true/false if template uses jQuery Mobile.
 *
 * @param bool $flag True/false to use jQuery Mobile
 */
if ( ! function_exists('template_use_jqm'))
{
    function template_use_jqm($flag = true) {
        Template::use_jqm($flag);
    }
}

/**
 * get_web_root
 *
 * Returns the path to the web root.
 *
 * @return string The web root path
 */
if ( ! function_exists('template_web_root'))
{
    function template_web_root() {
        return Template::get_web_root();
    }
}

/* End of file template.php */
/* Location: ./App/Util/template.php */