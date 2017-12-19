<?php

namespace App\Html\Template;

use App\App;

/**
 * Template class
 *
 * Designed to separate the complex logic of the Backbone javascript and server API from frontend page design,
 * this class generates the template HTML for frontend pages with minimal use of either. Templates are organized
 * into three default directories: home, page and module and can contain tags in the format {TAG_NAME} (letters,
 * underscores and numbers, no spaces) in the files within. The tags are parsed in lowercase as directories
 * within the template directories and contain files to parse to fill in place of the originating tags. Note
 * that PHP code contained within the templates is evaluated and functions created in the App\Util\template.php
 * file can be used within them.
 *
 * @author      Johnny Spence <info@projectabstractcms.org>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.org
 * @version     0.1.0
 * @package		App\Html\Template
 */
class Template {

    /**
     * @var string Directory within template directories containing the main content files
     */
    protected static $CONTENT_DIR = 'content';

    /**
     * @var string Regex to extract comment tags (removing clutter)
     */
    protected static $COMMENT_REGEX = '/<!--([\s\S]*?)-->/m';

    /**
     * @var string Template tag to insert CSS links
     */
    protected static $CSS_TAG = '{CSS_LINKS}';

    /**
     * @var string Default template file in template directories
     */
    protected static $DEFAULT_FILE = 'default';

    /**
     * @var string Default home template (directory)
     */
    protected static $DEFAULT_HOME_TMPL = 'home';

    /**
     * @var string Default template (directory)
     */
    protected static $DEFAULT_TMPL = 'page';

    /**
     * @var string File extension of template files
     */
    protected static $FILE_EXT = '.phtml';

    /**
     * @var string Directory within template directories containing form action scripts
     */
    protected static $FORM_ACTION_DIR = 'actions';

    /**
     * @var string Directory within form action scripts directory for script includes
     */
    protected static $FORM_ACTION_INCLUDES_DIR = 'includes';

    /**
     * @var string Template tag to insert CSS links
     */
    protected static $JS_TAG = '{JS_SCRIPTS}';

    /**
     * @var string Regex used to extract non-file include javascript within templates
     */
    protected static $ONLOAD_REGEX = '/<script[\s]*(((type="text\/javascript"[\s]*)?(data-onload="1")?)|((data-onload="1"[\s]*)?(type="text\/javascript")?))[\s]*>([\s\S]*?)<\/script>/im';

    /**
    * @var array Template tags reserved for CSS links and Javascript script tags
    */
    protected static $RESERVED_TAGS = array();

    /**
     * @var string Directory containing images, css, javascript and plugin files, not used to contain template files
     */
    protected static $STATIC_DIR = 'static';

    /**
     * @var string Regex used to extract tags within templates
     */
    protected static $TAG_REGEX = '/{([A-Za-z0-9_]+)}/';

    /**
     * @var string Regex used to extract non-file include javascript within templates
     */
    protected static $UNLOAD_REGEX = '/<script[\s]*(((type="text\/javascript")?[\s]*data-unload="1")|(data-unload="1"[\s]*(type="text\/javascript")?))[\s]*>([\s\S]*?)<\/script>/im';

    /**
     * @var string Directory in main template directory for form action scripts
     */
    protected static $actions_dir;

    /**
     * @var string Directory in form action scripts directory used for script plugins
     */
    protected static $actions_includes_dir;

    /**
     * @var array CSS link tag src to include in template
     */
    protected static $css = array();

    /**
     * @var bool True if template uses jQuery Mobile
     */
    protected static $has_jqm = false;

    /**
     * @var Array for DOM head tag configuration
     */
    protected static $head_tags = array();

    /**
     * @var \App\Html\Template\Template Singleton instance of this class
     */
    protected static $instance = NULL;

    /**
     * @var array Javascript script tag src to include in template
     */
    protected static $js = array();

    /**
     * @var string Executable javascript to run on page load, also extracted from <script> tags within template
     */
    protected static $onload = "";

    /**
     * @var bool True to remove comments from rendered template
     */
    protected static $remove_comments = true;

    /**
     * @var string Directory in main template directory containing images, css, javascript and plugin sources
     */
    protected static $static_dir;

    /**
     * @var string Template name/directory containing template files and tag directories
     */
    protected static $template;

    /**
     * @var string Absolute path to main template directory
     */
    protected static $tmpl_dir;

    /**
     * @var string Executable javascript to run on page unload, also extracted from <script> tags within template
     */
    protected static $unload = "";


    /**
     * Constructor
     *
     * Since this class is meant for use with static calls to methods, the constructor is private and
     * initialized once with a call by of the public static methods. Initializes the template directory
     * location and static files directory within it.s
     *
     * @access private
     */
    private function __construct() {
        $App = App::get_instance();
        $App->load_util('template');
        $tmpl_dir = $App->config('templates_dir');
        $tmpl_dir = rtrim($tmpl_dir, '/');
        self::$tmpl_dir = WEB_ROOT.'/'.$tmpl_dir;
        self::$actions_dir = WEB_ROOT.'/'.$tmpl_dir.'/'.self::$FORM_ACTION_DIR;
        self::$actions_includes_dir = self::$actions_dir.'/'.self::$FORM_ACTION_INCLUDES_DIR;
        self::$static_dir = WEB_BASE.'/'.$tmpl_dir.'/'.self::$STATIC_DIR;
        self::$RESERVED_TAGS = array(self::$CSS_TAG, self::$JS_TAG);
    }

    /**
     * css
     *
     * Adds a css link tag to the template. Since this is a static method, this can be called
     * multiple times from template and tag files and added all at once to the generated template.
     *
     * @access public
     * @param mixed $files Source path from <strong>static</strong> directory or array of source paths
     */
    public static function css($files) {
        self::instance();
        self::file_include(self::$css, $files);
    }


    /**
     * get
     *
     * Retrieves the template file and evaluates the {TAG_NAME} style tags and PHP code within, returning
     * the final HTML. Note that the template directory takes on the following structure:<br/><br/>
     *
     * <ul>
     *   <li>
     *     + page (directory for page template)
     *     <ul>
     *       <li>
     *         + content (directory for {CONTENT} tag within page template directory)
     *         <ul>
     *           <li>
     *             + title (directory for {TITLE} tag within content template directory)
     *             <ul>
     *               <li>- default.phtml (contains HTML content for {TITLE} template tag)</li>
     *               <li>- sample-page.phtml (HTML content for {TITLE} template tag for specific page)</li>
     *             </ul>
     *           </li>
     *           <li>- default.phtml (contains HTML content for {CONTENT} template tag)</li>
     *           <li>- sample-page.phtml (HTML content for {CONTENT} template tag for specific page)</li>
     *         </ul>
     *       </li>
     *       <li>
     *         + footer (directory for {FOOTER} tag within page template directory)
     *         <ul>
     *           <li>- default.phtml (contains HTML content for {FOOTER} template tag)</li>
     *           <li>- sample-page.phtml (HTML content for {FOOTER} template tag for specific page)</li>
     *         </ul>
     *       </li>
     *       <li>
     *         + header (directory for {HEADER} tag within page template directory)
     *         <ul>
     *           <li>- default.phtml (contains HTML content for {HEADER} template tag)</li>
     *           <li>- sample-page.phtml (HTML content for {HEADER} template tag for specific page)</li>
     *         </ul>
     *       </li>
     *       <li>- default.phtml (file containing {HEADER}, {CONTENT} and {FOOTER} tags)</li>
     *       <li>- sample-page.phtml (HTML content for {CONTENT} template tag for specific page)</li>
     *     </ul>
     *   </li>
     *   <li>
     *     + home (or) module (or) other_page (directory of page template for custom page)
     *     <ul>
     *       <li>
     *         + content (directory for {CONTENT} tag within home template directory)
     *         <ul>
     *           <li>- default.phtml (contains HTML content for {CONTENT} template tag)</li>
     *         </ul>
     *       </li>
     *       <li>
     *         + header (directory for {HEADER} tag within custom template directory)
     *         <ul>
     *           <li>- default.phtml (contains HTML content for {HEADER} template tag in this template)</li>
     *         </ul>
     *       </li>
     *       <li>- default.phtml (file can use {CONTENT} and {FOOTER} tags which defaults to those in "page" template)</li>
     *     </ul>
     *   </li>
     * </ul><br/><br/>
     *
     * The template structure is designed with the "page" directory as the default primary template directory and
     * any other directories may inherit the tags within the page directory. The "home" and "modules" directories
     * are added to further organize the templates by home page and module content, respectively, although are not
     * required. Further notes:<br/><br/>
     *
     * <ul>
     *   <li>
     *     If $name param is not <strong>page</strong> then a directory matching the name will be searched. If
     *     not found, then it will default to the <strong>page</strong> directory for template retrieval.
     *   </li>
     *   <li>
     *     If $file param is empty, then a default.phtml file will be searched in the the directory given by
     *     the $name parameter or the <strong>page</strong> directory if $name directory does not exist or is
     *     not defined.
     *   </li>
     *   <li>
     *     The <strong>page</strong> directory should contain templates and tags common to all templates
     *     (e.g. home, modules) or may be utilized solely. Either way, it should not be removed or renamed.
     *   </li>
     *   <li>
     *     A <strong>content</strong> directory should be created in each template directory to utilize the
     *     Template::get_content() function to retrieve partial template content instead of an entire template
     *     (e.g for use in AJAX applications).
     *   </li>
     *   <li>
     *     Note that for greater portability for template files, a <strong>static</strong> directory is contained
     *     within the main template directory to add images, css, javascript and plugin files. No template files
     *     should be stored and will not be evaluated in this directory.
     *   </li>
     * </ul>
     *
     * @access public
     * @param string $name The directory within the main template directory where template is retrieved
     * @param string $file The file within the $name directory of the template file, or searches for
     * default.phtml if empty
     * @param array $data Associative array of variable => value data extracted for template using extract()
     * function
     * @param bool $return_parts True to return separate tag values, css/js includes and other data along with
     * the template, NOTE: CSS/javascript will not be rendered in the template if true
     * @return mixed The template HTML, evaluated for template tags, PHP and/or template functions OR NULL
     * if $name parameter empty OR if $return_parts param is true, associative array with the following:
     * <br/><br/>
     * <ul>
     *   <li>html => The template HTML</li>
     *   <li>tags => The tag values in the template, including values within tags themselves</li>
     *   <li>params => JSON string of template directory and template used, e.g. {[DIRECTORY]:[FILE]}</li>
     *   <li>head_tags => Array of head tags configuration, <strong>note that tags will not be added to template if
     *   $return_parts parameter is false</strong></li>
     *   <li>css => Array of CSS source paths from template <strong>static</strong> directory</li>
     *   <li>js => Array of Javascript source paths from template <strong>static</strong> directory</li>
     *   <li>include_dir => Path from web root to template <strong>static</strong> directory</li>
     * </ul><br/><br/>
     * NOTE: If a tag name is repeated within the template and/or tags, it will be appended with an underscore
     * and the outer tag name and, if still repeating, an underscore plus a number starting from one;
     * ordered by outer most tag to inner.
     */
    public static function get($name, $file='', $data=array(), $return_parts=false) {
        self::instance();
        if ( empty($name) || in_array($name, array(self::$FORM_ACTION_DIR, self::$STATIC_DIR, '..') ) ||
            @is_dir(self::$tmpl_dir.'/'.$name) === false ) {
            return NULL;
        } else if ( empty($file) ) {
            $file = self::$DEFAULT_FILE;
        }

        self::$template = $name;
        $default_dir = self::$tmpl_dir.'/'.self::$DEFAULT_TMPL;
        $tmpl = self::parse_template(self::$tmpl_dir, $default_dir, $name, $file, $data);

        // add CSS links
        $css_links = $return_parts ? '' : self::link_tag_html();
        $tmpl['html'] = str_replace(self::$CSS_TAG, $css_links, $tmpl['html']);

        // add Javascript script tags
        $script_tags = $return_parts ? '' : self::script_tag_html();
        $tmpl['html'] = str_replace(self::$JS_TAG, $script_tags, $tmpl['html']);

        if ($return_parts) {
            $tmpl['html'] = self::parse_load_scripts($tmpl['html']);
            $tmpl['head_tags'] = self::$head_tags;
            $tmpl['css'] = self::$css;
            $tmpl['js'] = self::$js;
            $tmpl['onload'] = self::$onload;
            $tmpl['unload'] = self::$unload;
            $tmpl['include_dir'] = self::$static_dir;
            $tmpl['use_jqm'] = self::$has_jqm;
            $tmpl['params'] = self::is_template($name, $file) ? array($name => $file) : array($name => self::$DEFAULT_FILE);
            return $tmpl;
        }

        return $tmpl['html'];
    }


    /**
     * get_actions_path
     *
     * Returns the full path to the form actions directory within the main template directory.
     *
     * @access public
     * @return string The form actions path
     */
    public static function get_actions_path() {
        self::instance();
        return self::$actions_dir;
    }


    /**
     * get_actions_includes_path
     *
     * Returns the full path to the includes directory within the form actions directory.
     *
     * @access public
     * @return string The form actions path
     */
    public static function get_actions_includes_path() {
        self::instance();
        return self::$actions_includes_dir;
    }


    /**
     * get_actions_script_file_path
     *
     * Returns the script path from the action scripts directory within the main template directory for use
     * in frontend form submits. Method checks for one of the following files:<br/><br/>
     * <ul>
     *   <li>The file [$module_name].phtml OR /[$module_name]/default.phtml, if $func_name param is empty</li>
     *   <li>The file /[$module_name]/[$func_name].phtml, if $func_name param is not empty</li>
     *   <li>Note that $module_name param cannot be name of "includes" directory within the action
     *   scripts directory</li>
     * </ul>
     *
     * @access public
     * @param string $module_name The directory within the action scripts directory corresponding to the
     * module name OR name of file within the action scripts directory with .phtml extension
     * @param string $func_name The file within the $module_name directory of the action scripts directory
     * @return bool True if the script was included
     */
    public static function get_actions_script_file_path($module_name, $func_name = '') {
        self::instance();
        if ( self::is_action_script($module_name, $func_name) === false ) {
            return false;
        }

        $path = '';
        if ( @is_file(self::$actions_dir.'/'.$module_name.self::$FILE_EXT) ) {
            $path = self::$actions_dir.'/'.$module_name.self::$FILE_EXT;
        } else {
            $func_file = empty($func_name) ? self::$DEFAULT_FILE.self::$FILE_EXT : $func_name;
            if (substr($func_file, -(strlen(self::$FILE_EXT))) !== self::$FILE_EXT) {
                $func_file .= self::$FILE_EXT;
            }
            $path = self::$actions_dir.'/'.$module_name.'/'.$func_file;
        }

        return $path;
    }


    /**
     * get_content
     *
     * Retrieves the "content" part of a template contained in files located in the <strong>content</strong>
     * directory within a template directory.
     *
     * @access public
     * @param string $name The directory within the main template directory where template is retrieved
     * @param string $file The file within the <strong>content</strong> directory (which is within $name directory)
     * of the template file
     * @param array $data Associative array of variable => value data extracted for template using extract()
     * function
     * @param bool $return_parts True to return separate tag values, css/js includes and other data along with
     * the template, NOTE: CSS/javascript will not be rendered in the template if true
     * @return mixed The template HTML, evaluated for template tags, PHP and/or template functions OR NULL
     * if $name parameter empty OR if $return_parts param is true, associative array with the following:
     * <br/><br/>
     * <ul>
     *   <li>html => The template HTML</li>
     *   <li>tags => The tag values in the template, including values within tags themselves</li>
     *   <li>params => JSON string of template directory and template used, e.g. {[DIRECTORY]:[FILE]}</li>
     *   <li>head_tags => Array of head tags configuration, <strong>note that tags will not be added to template if
     *   $return_parts parameter is false</strong></li>
     *   <li>css => Array of CSS source paths from template <strong>static</strong> directory</li>
     *   <li>js => Array of Javascript source paths from template <strong>static</strong> directory</li>
     *   <li>include_dir => Path from web root to template <strong>static</strong> directory</li>
     * </ul><br/><br/>
     * NOTE: If a tag name is repeated within the template and/or tags, it will be appended with an underscore
     * and the outer tag name and, if still repeating, an underscore plus a number starting from one;
     * ordered by outer most tag to inner.
     */
    public static function get_content($name, $file, $data=array(), $return_parts=false) {
        self::instance();
        if ( empty($file) || empty($name) || in_array($name, array(self::$FORM_ACTION_DIR, self::$STATIC_DIR, '..') ) ||
            @is_dir(self::$tmpl_dir.'/'.$name) === false ) {
            return NULL;
        }

        self::$template = $name;
        $template_dir = self::$tmpl_dir.'/'.$name;
        $default_dir = $template_dir.'/'.self::$CONTENT_DIR;
        $tmpl = self::parse_template($template_dir, $default_dir, self::$CONTENT_DIR, $file, $data);

        // add CSS links
        $css_links = $return_parts ? '' : self::link_tag_html();
        $tmpl['html'] = str_replace(self::$CSS_TAG, $css_links, $tmpl['html']);

        // add Javascript script tags
        $script_tags = $return_parts ? '' : self::script_tag_html();
        $tmpl['html'] = str_replace(self::$JS_TAG, $script_tags, $tmpl['html']);

        if ($return_parts) {
            $tmpl['html'] = self::parse_load_scripts($tmpl['html']);
            $tmpl['head_tags'] = self::$head_tags;
            $tmpl['css'] = self::$css;
            $tmpl['js'] = self::$js;
            $tmpl['onload'] = self::$onload;
            $tmpl['unload'] = self::$unload;
            $tmpl['include_dir'] = self::$static_dir;
            $tmpl['use_jqm'] = self::$has_jqm;
            $tmpl['params'] = self::is_template($name, $file) ? array($name => $file) : array($name => self::$DEFAULT_FILE);
            return $tmpl;
        }

        return $tmpl['html'];
    }


    /**
     * get_default_filename
     *
     * Returns the default filename for template/content files.
     *
     * @access public
     * @param bool $use_ext True to include file extension
     * @return string The default template filename
     */
    public static function get_default_filename($use_ext=false) {
        self::instance();
        return self::$DEFAULT_FILE.($use_ext ? self::$FILE_EXT : '');
    }


    /**
     * get_default_home_template
     *
     * Returns the default directory for the home template/content files.
     *
     * @access public
     * @return string The default home template directory
     */
    public static function get_default_home_template() {
        self::instance();
        return self::$DEFAULT_HOME_TMPL;
    }


    /**
     * get_default_template
     *
     * Returns the default directory for template/content files.
     *
     * @access public
     * @return string The default template directory
     */
    public static function get_default_template() {
        self::instance();
        return self::$DEFAULT_TMPL;
    }


    /**
     * get_file_path
     *
     * Returns the full root-relative path to the file upload directory given a config name
     * from ./App/config/uploads.php. Note that the path returned has a slash at the end.
     *
     * @access public
     * @param string $cfg_name The executable javascript
     * @param string $cfg_name Path to file within application directory
     * @return string The full root-relative file path
     */
    public static function get_file_path($cfg_name) {
        if ( empty($cfg_name) ) {
            return '';
        }

        self::instance();
        $path = '';
        $App = App::get_instance();
        $config = $App->upload_config($cfg_name, false);
        if ( ! empty($config) ) {
            $path = $config['upload_path'].'/';
        }
        return $path;
    }


    /**
     * get_img_path
     *
     * Returns the full root-relative path to the image upload directory given a config name
     * from ./App/config/uploads.php. Note that the path returned has a slash at the end.
     *
     * @access public
     * @param string $cfg_name The executable javascript
     * @param string $cfg_name Path to file within application directory
     * @return string The full root-relative image path
     */
    public static function get_img_path($cfg_name) {
        if ( empty($cfg_name) ) {
            return '';
        }

        self::instance();
        $path = '';
        $App = App::get_instance();
        $config = $App->upload_config($cfg_name, true);
        if ( ! empty($config) ) {
            $path = $config['upload_path'].'/';
        }
        return $path;
    }


    /**
     * get_static_dir
     *
     * Returns the root-relative path to the static directory within the main template directory.
     *
     * @access public
     * @return string The static directory path
     */
    public static function get_static_dir() {
        self::instance();
        return self::$static_dir;
    }


    /**
     * get_var
     *
     * Returns the template variables defined in ./abstract.json or, if $name parameter
     * provided, returns that value.
     *
     * @access public
     * @param string $name Name of the variable to retrieve, blank to retrieve all variables
     * @return string The template variables or variable by name
     */
    public static function get_var($name='') {
        self::instance();
        $App = App::get_instance();
        $config = $App->config('front_template_vars');
        return empty($name) ? $config : (empty($config[$name]) ? NULL : $config[$name]);
    }


    /**
     * get_web_root
     *
     * Returns the web root directory.
     *
     * @access public
     * @return string The web root
     */
    public static function get_web_root() {
        self::instance();
        return WEB_BASE;
    }


    /**
     * has_jqm
     *
     * Returns true if template uses jQuery Mobile.
     *
     * @access public
     * @return bool True if template uses jQuery Mobile
     */
    public static function has_jqm() {
        self::instance();
        return self::$has_jqm;
    }


    /**
     * head_tags
     *
     * Adds tags, such as meta and link to the head tag of the page DOM.
     *
     * @access public
     * @param array $head Assoc array of head tag configuration in the following format::<br/><br/>
     * array(
     *    [tag name 1 (e.g meta)] => array(
     *        [main tag attribute 1 (e.g. name)] => array(
     *            [main tag attribute value (e.g. keywords)] => array(
     *                [attribute 1 name (e.g. content)] => [attribute 1 value],
     *                [attribute 2 name] => ...
     *            )
     *        ),
     *        [main tag attribute 2 (e.g. property)] => array(
     *            ...
     *        )
     *    ),
     *    [tag name 2 (e.g link)] => ...
     * )
     */
    public static function head_tags($head) {
        self::instance();
        if ( empty($head) ) {
            return;
        }

        foreach ($head as $tag_name => $attr_or_str) {
            if ( isset(self::$head_tags[$tag_name]) && is_array($attr_or_str) ) {
                foreach ($attr_or_str as $prop => $prop_vals) {
                    if ( isset(self::$head_tags[$tag_name][$prop]) && is_array($prop_vals) ) {
                        foreach ($prop_vals as $prop_val => $attr_vals) {
                            self::$head_tags[$tag_name][$prop][$prop_val] = $attr_vals;
                        }
                    } else {
                        self::$head_tags[$tag_name][$prop] = $prop_vals;
                    }
                }
            } else {
            // title tag or tag not set
                self::$head_tags[$tag_name] = $attr_or_str;
            }
        }
    }


    /**
     * is_action_script
     *
     * Checks the action scripts directory within the main template directory for one of the following:<br/><br/>
     * <ul>
     *   <li>The file [$module_name].phtml OR /[$module_name]/default.phtml, if $func_name param is empty</li>
     *   <li>The file /[$module_name]/[$func_name].phtml, if $func_name param is not empty</li>
     *   <li>Note that $module_name param cannot be name of "includes" directory within the action
     *   scripts directory</li>
     * </ul>
     *
     * @access public
     * @param string $module_name The directory within the action scripts directory corresponding to the
     * module name OR name of file within the action scripts directory with .phtml extension
     * @param string $func_name The file within the $module_name directory of the action scripts directory
     * @return bool True if the script exists
     */
    public static function is_action_script($module_name, $func_name='') {
        self::instance();
        if ( empty($module_name) || in_array($module_name, array(self::$FORM_ACTION_INCLUDES_DIR, '..') ) ) {
            return false;
        }

        $func_file = empty($func_name) ? self::$DEFAULT_FILE.self::$FILE_EXT : $func_name;
        if ( substr($func_file, -(strlen(self::$FILE_EXT) ) ) !== self::$FILE_EXT) {
            $func_file .= self::$FILE_EXT;
        }

        $is_script = (empty($func_name) && @is_file(self::$actions_dir.'/'.$module_name.self::$FILE_EXT) ) ||
                     @is_file(self::$actions_dir.'/'.$module_name.'/'.$func_file);
        return $is_script;
    }


    /**
     * is_template
     *
     * Checks the main template directory for a template ($name param) and file ($file param)
     * corresponding to a template OR checks the "content" directory within the template directory
     * if the file exists if ($check_content) parameter is true.
     *
     * @access public
     * @param string $name The directory within the main template directory where template is retrieved
     * @param string $file The file within the $name directory of the template file, or searches for
     * @param bool $check_content True to check, in addition, content directory for $file template
     * @return bool True if $file exists within $name directory in main template directory
     */
    public static function is_template($name, $file, $check_content=false) {
        self::instance();
        if ( empty($name) || empty($file) || in_array($name, array(self::$FORM_ACTION_DIR, self::$STATIC_DIR, '..') ) ) {
            return false;
        }

        if ( substr($file, -(strlen(self::$FILE_EXT) ) ) !== self::$FILE_EXT) {
            $file .= self::$FILE_EXT;
        }

        $is_template = @is_file(self::$tmpl_dir.'/'.$name.'/'.$file);
        if ($check_content) {
            $is_template = $is_template || @is_file(self::$tmpl_dir.'/'.$name.'/'.self::$CONTENT_DIR.'/'.$file);
        }
        return $is_template;
    }


    /**
     * js
     *
     * Adds a javascript script tag to the template. Since this is a static method, this can be called
     * multiple times from template and tag files and added all at once to the generated template.
     *
     * @access public
     * @param mixed $files Source path from <strong>static</strong> directory or array of source paths
     */
    public static function js($files) {
        self::instance();
        self::file_include(self::$js, $files);
    }


    /**
     * onload
     *
     * Adds excutable javascript for a template page's onload call.
     *
     * @access public
     * @param string $js_code The executable javascript
     */
    public static function onload($js_code) {
        if ( empty($js_code) ) {
            return;
        }
        self::instance();
        self::$onload .= $js_code."\n\n";
    }


    /**
     * remove_comments
     *
     * Sets the ability to keep or remove comments from the rendered template.
     *
     * @access public
     * @param bool $remove_comments True to remove comments (default)
     */
    public static function remove_comments($remove_comments=true) {
        self::instance();
        self::$remove_comments = $remove_comments;
    }


    /**
     * reset
     *
     * Resets this class, typically to render additional Template(s) within the same script.
     *
     * @access public
     */
    public static function reset() {
        self::instance();
        self::$css = array();
        self::$js = array();
        self::$head_tags = array();
        self::$onload = '';
        self::$unload = '';
        self::$has_jqm = false;
    }


    /**
     * unload
     *
     * Adds excutable javascript for a template page's unload call.
     *
     * @access public
     * @param string $js_code The executable javascript
     */
    public static function unload($js_code) {
        if ( empty($js_code) ) {
            return;
        }
        self::instance();
        self::$unload .= $js_code."\n\n";
    }


    /**
     * use_jqm
     *
     * Sets true/false if template uses jQuery Mobile.
     *
     * @access public
     * @param bool $flag True if template uses jQuery Mobile
     */
    public static function use_jqm($flag) {
        self::instance();
        self::$has_jqm = ! empty($flag);
    }


    /**
     * file_include
     *
     * Adds CSS or Javascript source include(s) to their respective static arrays. Note that this
     * will check for a duplicate before adding to avoid repeat includes.
     *
     * @access protected
     * @param array $arr Static array containing include paths
     * @param mixed $files Source path from <strong>static</strong> directory or array of source paths
     */
    protected static function file_include(&$arr, $files) {
        if ( empty($files) ) {
            return;
        } else if ( ! is_array($files) ) {
            $files = array($files);
        }

        $check_script_url = function($url) {
            if ( substr($url, 0, 7) !== 'http://' &&
                substr($url, 0, 8) !== 'https://' &&
                substr($url, 0, 2) !== '//' ) {
                if ( substr($url, 0, 1) === '/' ) {
                    $url = substr($url, 1);
                }
            }
            return $url;
        };

        foreach ($files as $mixed) {
            $file = '';
            $to_check = '';

            if ( is_array($mixed) ) {
                $file = $check_script_url( key($mixed) );
                $deps = $mixed[ key($mixed) ];
                if ( is_array($deps) ) {
                    foreach ($deps as &$depend) {
                        $depend = $check_script_url($depend);
                    }
                } else {
                    $deps = $check_script_url($deps);
                }
                $to_check = $file;
                $file = array($file => $deps);
            } else {
                $file = $check_script_url($mixed);
                $to_check = $file;
            }

            if ( ! in_array($to_check, $arr) ) {
                $arr[] = $file;
            }
        }
    }


    /**
     * parse_load_scripts
     *
     * Extracts javascript within &lt;script&gt; in template HTML to use for executing onload/unload
     * scripts upon template page code via RequireJS. Removes the &lt;script&gt; tags and javascript
     * from the template itself upon extraction. Note the format of tags for extraction:<br/><br/>
     * <strong>To use for <em>onload</em> execution</strong><br/><br/>
     * &lt;script&gt;...&lt;/script&gt;
     * <strong>To use for <em>unload</em> execution</strong><br/><br/>
     * &lt;script data-unload="1"&gt;...&lt;/script&gt;
     *
     * @access protected
     * @param string $template The template HTML to parse for executable javascript
     * @return string The template HTML with executable javascript parsed and removed
     */
    protected static function parse_load_scripts($template) {
        if ( empty($template) ) {
            return $template;
        }

        // extract onload javascript
        $template = preg_replace_callback(self::$ONLOAD_REGEX,
            function($matches) {
                self::$onload .= $matches[8]."\n\n";
                return '';
            },
            $template
        );

        // extract unload javascript
        $template = preg_replace_callback(self::$UNLOAD_REGEX,
            function($matches) {
                self::$unload .= $matches[6]."\n\n";
                return '';
            },
            $template
        );

        return $template;
    }


    /**
     * parse_template
     *
     * Recursively retrieves the template file and evaluates the {TAG_NAME} style tags and PHP code within, returning
     * the final template HTML.
     *
     * @access protected
     * @param string $tmpl_dir The main template directory
     * @param string $default_dir The default template directory to fall back on for template/tags
     * @param string $name The template/tag directory to retrieve template
     * @param string $file The template file within the directory
     * @param array $data Associative array of variable => value data extracted for template using extract()
     * function
     * @param bool $is_init Flag used for recursion, true if function called for first time
     * @return array Associative array with the following:<br/><br/>
     * <ul>
     *   <li>html => The template HTML, evaluated for template tags, PHP and/or template functions</li>
     *   <li>tags => Assoc array of tags (lowercase) and their values</li>
     * </ul><br/><br/>
     * NOTE: If a tag name is repeated within the template and/or tags, it will be appended with an underscore
     * and the outer tag name and, if still repeating, an underscore plus a number starting from one;
     * ordered by outer most tag to inner.
     */
    protected static function parse_template($tmpl_dir, $default_dir, $name, $file, $data, $is_init=true) {
        $tmpl_base_dir = $tmpl_dir.'/'.$name;
        $tmpl_file = '';
        $tmpl_html = '';
        $tmpl_tags = array();
        $tmpl_name_param = $name;
        $tmpl_file_param = $file;

        if ( ! $is_init) {
            $default_dir .= '/'.$name;
        }

        if ( substr($file, -(strlen(self::$FILE_EXT) ) ) !== self::$FILE_EXT) {
            $file .= self::$FILE_EXT;
        } else {
            $tmpl_file_param = substr($file, -(strlen(self::$FILE_EXT) ) );
        }

        if ( @is_dir($tmpl_base_dir) || @is_dir($default_dir) ) {
            if ( @file_exists($tmpl_base_dir.'/'.$file) ) {
             // first check for template file in given directory
                $tmpl_file = $tmpl_base_dir.'/'.$file;
            } else if ( @file_exists($tmpl_base_dir.'/'.self::$DEFAULT_FILE.self::$FILE_EXT) ) {
             // check for default file in given directory
                $tmpl_file = $tmpl_base_dir.'/'.self::$DEFAULT_FILE.self::$FILE_EXT;
                $tmpl_file_param = self::$DEFAULT_FILE;
            } else if (self::$template !== self::$DEFAULT_TMPL) {
            // check default template directory for tag/template file
                if ( @file_exists($default_dir.'/'.$file) ) {
                    $tmpl_name_param = substr($default_dir, strrpos($default_dir, '/') + 1);
                    $tmpl_file = $default_dir.'/'.$file;
                } else if (@file_exists($default_dir.'/'.self::$DEFAULT_FILE.self::$FILE_EXT)) {
                    $tmpl_file = $default_dir.'/'.self::$DEFAULT_FILE.self::$FILE_EXT;
                    $tmpl_name_param = self::$DEFAULT_TMPL;
                    $tmpl_file_param = self::$DEFAULT_FILE;
                }
            }

            if ( ! empty($tmpl_file) ) {
                if ( ! empty($data) ) {
                    extract($data);
                }

                ob_start();
                include($tmpl_file);
                $tmpl_html = ob_get_clean();

                //remove HTML comments
                if (self::$remove_comments) {
                    $tmpl_html = preg_replace(self::$COMMENT_REGEX, '', $tmpl_html);
                }

                preg_match_all(self::$TAG_REGEX, $tmpl_html, $matches);
                $tags = empty($matches[0]) ? array() : $matches[0];
                $dirs = empty($matches[1]) ? array() : $matches[1];

                foreach ($tags as $i => $tag) {
                    if ( empty($dirs[$i]) || in_array($tag, self::$RESERVED_TAGS) ) {
                        continue;
                    }

                    $dir = strtolower($dirs[$i]);
                    $tmpl = self::parse_template($tmpl_base_dir, $default_dir, $dir, $file, $data, false);

                    $tmpl_tags[$dir] = $tmpl['html'];
                    $rec_tags = $tmpl['tags'];
                    $tags = array();
                    foreach ($rec_tags as $_tag => $val) {
                        if ( isset($tmpl_tags[$_tag]) ) {
                            $count = 1;
                            $new_tag = $_tag.'_'.$dir;
                            while ( isset($tmpl_tags[$new_tag]) ) {
                                $new_tag = $_tag.'_'.$dir.'_'.($count++);
                            }
                            $tags[$new_tag] = $val;
                        } else {
                            $tags[$_tag] = $val;
                        }
                    }
                    $tmpl_tags = $tmpl_tags + $tags;
                    $tmpl_html = str_replace($tag, $tmpl['html'], $tmpl_html);
                }
            }
        }

        $ret = array(
            'html' => $tmpl_html,
            'tags' => $tmpl_tags
        );

        return $ret;
    }

    /**
     * instance
     *
     * Creates a singleton instance of this class.
     *
     * @access private
     */
    private static function instance() {
        if ( self::$instance === NULL ) {
            self::$instance = new self();
        }
    }


    /**
     * link_tag_html
     *
     * Generates the template CSS link tag(s).
     *
     * @access private
     * @return string The CSS link tag HTML
     */
    private static function link_tag_html() {
        $html = '';
        $count = count(self::$css);
        foreach (self::$css as $i => $link) {
            if ( substr($link, 0, 7) !== 'http://' &&
                substr($link, 0, 8) !== 'https://' &&
                substr($link, 0, 2) !== '//' ) {
                $link = self::$static_dir.'/'.$link;
            }
            $html .= '<link href="'.$link.'" rel="stylesheet" type="text/css" media="screen" />';
            if ($i < $count - 1) {
                $html .= "\n";
            }
        }
        return $html;
    }


    /**
     * script_tag_html
     *
     * Generates the template javascript script tag(s).
     *
     * @access private
     * @return string The javascript script tag HTML
     */
    private static function script_tag_html() {
        $html = '';
        $count = count(self::$js);
        foreach (self::$js as $i => $link) {
            if ( substr($link, 0, 7) !== 'http://' &&
                substr($link, 0, 8) !== 'https://' &&
                substr($link, 0, 2) !== '//' ) {
                $link = self::$static_dir.'/'.$link;
            }
            $html .= '<script src="'.$link.'" type="text/javascript"></script>';
            if ($i < $count - 1) {
                $html .= "\n";
            }
        }
        return $html;
    }
}

/* End of file Template.php */
/* Location: ./App/Html/Template/Template.php */