<?php
/**
 * Abstract CMS
 *
 * @author      Johnny Spence <johnny@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @license     http://www.projectabstractcms.com/license
 * @version     0.1.0
 * @package     App
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
 
namespace App;

use
Exception,
App\Exception\AppException,
App\Exception\ErrorException;

/**
 * App class
 * 
 * This is the entry point for Abstract application utilizing the REST API.
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @license     http://www.projectabstractcms.com/license
 * @version     0.1.0
 * @package		App
 */
class App {

    /**
     * @const string Abstract version
     */
	const VERSION = '0.1.0';
	
    /**
     * @var array Configuration for this application
     */
	private $config;
	
    /**
     * @var array Storage for errors, including stack trace, for debug display
     */
	private static $debug_errors;
	
    /**
     * @var array Storage for errors occurred for output to JSON
     */
	private static $errors;
	
    /**
     * @var \App\App Singleton instance of this class
     */
	private static $instance = NULL;
	
    /**
     * @var array Language translations for current locale
     */
	private $lang;
	
	
	/**
	* autoload
	* 
	* Creates the autloader for this application.
	* 
	* @access public
	* @param string $class_name The class name
	* @return void
	*/
	public static function autoload($class_name) {
		$class_path = str_replace("\\", DIRECTORY_SEPARATOR, $class_name).'.php';
		if (substr($class_path, 0, 1) !== DIRECTORY_SEPARATOR) {
			$class_path = DIRECTORY_SEPARATOR.$class_path;
		}

		$class_path = substr(APP_PATH, 0, strrpos(APP_PATH, DIRECTORY_SEPARATOR)).$class_path;
		if (file_exists($class_path)) {
            require $class_path;
        }
	}
	
	
	/**
	* register_autoload
	* 
	* Registers the autloader for this application.
	* 
	* @access public
	* @return void
	*/
	public static function register_autoload() {
		spl_autoload_register(__NAMESPACE__."\\App::autoload");
	}
	
	
	/**
	* Constructor
	* 
	* Loads the main configuration file, registers the application autoloader,
	* creates the user session and sets the application to display all PHP
	* errors if set to debug. An instance of this class will be a singleton.
	* 
	* @access private
	* @return void
    * @throws \App\Exception\AppException if an application error occurred, handled in this class
	*/
	private function __construct() {
		$doc_root = $_SERVER['DOCUMENT_ROOT'];
		$app_path = __DIR__;
		$web_root = str_replace('\\', '/', realpath($app_path.'/../../') );
		$web_base = str_replace($doc_root, '', $web_root);
		
		$parts = explode(DIRECTORY_SEPARATOR, realpath($app_path.'/../') );
		$count = count($parts);
		$api_dir = $count > 0 ? $parts[$count-1] : '';
		
		//set webroot and path to App
        define('DOC_ROOT', $doc_root);  // /full/path/to/docroot
		define('API_DIR', $api_dir);    // api
		define('APP_PATH', $app_path);  // [/baseurl]/api/App
		define('WEB_ROOT', $web_root);  // /full/path/to/docroot[/baseurl]
		define('WEB_BASE', $web_base);  // [/baseurl]
		
		//set all PHP errors and exceptions to behandled by this class
		set_error_handler( array($this, 'error_handler') );
		set_exception_handler( array($this, 'exception_handler') );
		
		$this->load_util('functions');
		$this->config = $this->load_config('config');
		$this->lang = $this->load_lang($this->config['locale']);
		self::$debug_errors = array();
		self::$errors = array();
		
		//set default charset for application
		$charset = empty($this->config['charset']) ? 'UTF-8' : $this->config['charset'];
		ini_set('default_charset', $charset);
		
		/*
		if ( ! empty($this->config['debug']) ) {
		//display all PHP errors in application
			ini_set('display_errors', 1);
			ini_set('display_startup_errors', 1);
			error_reporting(-1);
		}
		*/
		
	}
	

	/**
	* Destructor
	* 
	* If errors occur in the application, this will display the errors if set to debug
	* mode in main application config file or will echo JSON output containing them.
	* Note that the debug display will include the stack trace while the JSON output
	* will not.
	* 
	* @access public
	* @return void
	*/
	public function __destruct() {
		if ( ! empty($this->config['debug']) && ! empty(self::$debug_errors) ) {
			$title = count(self::$debug_errors) === 1 ? 'An Application Error Has' : 'Application Errors Have';
			$out = '<div style="font-family:Arial;position:absolute;top:0;left:0;margin:15px;">';
			$out .= '<p><strong>'.$title.' Occurred</strong></p>';
			
			foreach (self::$debug_errors as $error) {
				$out .=  '<p>'.nl2br($error).'</p>';
			}
			
			$out .= '</div>';
			echo $out;
		} else if ( ! empty(self::$errors)) {
			echo json_encode( array('errors' => self::$errors) );
		}
	}
	

	/**
	* config
	* 
	* Returns the value given a name from the main application config.
	* 
	* @access public
	* @param string $name The config parameter name
	* @return mixed The config value from the given parameter or false if undefined
	*/
	public function config($name) {
		if ( empty($name) ) {
			return false;
		}
		
		return isset($this->config[$name]) ? $this->config[$name] : false;
	}
	

	/**
	* error_handler
	* 
	* Handles all application PHP errors by converting to an \App\Exception\ErrorException
	* which is thrown and to be handled in App::exception_handler.
	* 
	* @access public
	* @param int $code Error reporting level of the error
	* @param string $message The error message
	* @param string $file The filename with full path
	* @param int $line The line inside the file where the error occurred
	* @return void
	* @throws \App\Exception\ErrorException
	*/
	public function error_handler($code, $message, $file, $line) {
		throw new ErrorException($message, $file, $line);
	}
	

	/**
	* exception_handler
	* 
	* Handles all application Exceptions and can be set to display and/or log a message
	* and/or exit the application.
	* 
	* @access public
	* @param Exception $e The Exception thrown in the application and caught here
	* @return void
	* @throws Exception if an Exception occurs within this method
	*/
	public function exception_handler(Exception $e) {
		if ( empty($e) ) {
		//then random call, so return
			return;
		}
		
		$is_debug = ! empty($this->config['debug']);
		
		try {
			$message = $e->getMessage()." in ".$e->getFile().", line ".$e->getLine();
			$is_log = ! empty($this->config['log_errors']);
			$is_fatal = $e->getCode() === AppException::ERROR_FATAL;
			self::$errors[] = $message;
			
			if ($is_debug || $is_log) {
				$trace = $e->getTrace();
				$count = count($trace);
				
				foreach ($trace as $i => $tr_line) {
					$file = empty($tr_line['file']) ? '' : $tr_line['file'];
					$line = empty($tr_line['line']) ? '' : $tr_line['line'];
					if ( empty($file) && empty($line) ) {
						continue;
					}
					
					$message .= "\n".($i+1).".";
					$message .= empty($file) ? "" : " ".$file;
					$message .= empty($line) || $line == $file ? "" : ", line ".$line;
				}
				
				if ($is_debug) {
					self::$debug_errors[] = $message;
				}

				if ($is_log) {
					$message = "[".date('Y-m-d H:i:s')."] ".$message;
					$log_file = empty($this->config['error_log']) ? '../errors.log' : $this->config['error_log'];
					error_log($message."\n\n", 3, $log_file);
				}
				
				if ($is_fatal) {
					exit;
				}
			}
		} catch (Exception $e) {
			$message = get_class($e)." thrown within exception handler. Message: ";
			$message .= $e->getMessage()." in ".$e->getFile().", line ".$e->getLine();
			self::$errors[] = $message;
			if ($is_debug) {
				self::$debug_errors[] = $message;
			}
		}
	}
	
	
	/**
	* fileinfo
	* 
	* Returns the filename, filesize and mime type for a given file or array of files.
	* Note that this does not verify if a file exists on the server.
	* 
	* @access public
	* @param string $config_name The file config key_name in \App\Config\uploads.php
	* @param mixed $files The filename or array of filenames
	* @param bool $is_image True if image upload configuration, false for other file types
	* @return mixed The upload configuration parameters or false if file config not found
	*/
	public function fileinfo($config_name, $files, $is_image) {
		if ( empty($config_name) || empty($files) ) {
			return false;
		}
		
		$config = $this->upload_config($config_name, $is_image);
		$fileinfo = array();
		$is_single = false;
		if ( ! is_array($files) ) {
			$files = array($files);
			$is_single = true;
		}

		if ( ! empty($config) ) {
			require_once(realpath(__DIR__.'/../').'/Upload/FileUpload.php');
			foreach ($files as $file) {
				$info = false;
				$ext = substr($file, strrpos($file, '.') + 1 );
				$filepath = $_SERVER['DOCUMENT_ROOT'].$config['upload_path'].'/'.$file;
				
				if ( @is_file($filepath) ) { 
					$filesize =  @filesize($filepath);
					$filetype = \Upload\FileUpload::getMimeDescr($ext);
					$info = array(
						'filename' 	=> $file,
						'filesize' 	=> $filesize === false ? 
									   '0 B' : 
									   \Upload\FileUpload::bytesToHumanReadable($filesize),
						'filetype'	=> $filetype === false ? strtoupper($ext).' File' : $filetype
					);
					
				}
				$fileinfo[] = $info;
			}
		}
		
		return $is_single ? $fileinfo[0] : $fileinfo;
	}
	
	
	/**
	* get_csrf
	* 
	* Returns a singleton instance of the Crsf class.
	*
	* @access public
	* @return \App\User\Csrf The Csrf singleton class instance
	*/
	public static function get_csrf() {
		return \App\User\Csrf::get_instance();
	}
	
	
	/**
	* get_instance
	* 
	* Returns a singleton instance of this App class.
	*
	* @access public
	* @return \App\App The singleton instance
    * @throws \App\Exception\AppException if an application error occurred, handled in this class
	*/
	public static function get_instance() {
		if ( is_null(self::$instance) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	
	/**
	* lang
	* 
	* Returns the I18n translation for the current locale.
	* 
	* @access public
	* @param string $name The lang parameter name
	* @return mixed The text translation value from the given parameter or the string parameter if undefined
	*/
	public function lang($name) {
		if ( empty($name) ) {
			return $name;
		}
		
		return isset($this->lang[$name]) ? $this->lang[$name] : $name;
	}


    /**
     * lang_text
     *
     * Returns the text of a language translation (.txt) file from the current locale from the
     * ./App/Lang/[lang]/[country]/text directory with a given filename parameter. This method
     * is used for text translations for HTML or, in general, text too long to be placed in the
     * main translation file.
     *
     * @access public
     * @param string $filename The language file name, optionally minus .txt extension
     * @return string The corresponding text from $filename or an empty string if
     * $filename not found or parameter is empty
     */
    public function lang_text($filename) {
        if ( empty($filename) ) {
            return '';
        }

        $text = '';
        $locale = empty($this->config['locale']) ? '' : $this->config['locale'];
        $parts = explode('_', $locale);
        if ( count($parts) === 2 ) {
            $ext_check = strtolower( substr($filename, -4) );
            if ($ext_check !== '.txt') {
                $filename .= '.txt';
            }
            $dir =  dirname(__FILE__).DIRECTORY_SEPARATOR.'Lang'.DIRECTORY_SEPARATOR.$parts[0];
            $dir .= DIRECTORY_SEPARATOR.$parts[1].DIRECTORY_SEPARATOR.'text'.DIRECTORY_SEPARATOR;
            if ( is_file($dir.$filename) ) {
                $text = file_get_contents($dir.$filename);
            }
        }

        return empty($text) ? '' : $text;
    }
	
	
	/**
	 * load_config
	 *
	 * Loads a configuration file from the application ./App/Config directory.
	 * Note that the main local file, named ./App/Lang/Config/config.php is loaded by
	 * the application at runtime and this function reads and loads additional config
     * values from the main application ./abstract.json file:<br/><br/>
     * <ul>
     * <li>debug: True to show errors, used in development</li>
     * <li>admin_uri_segment: Admin URL segment</li>
     * <li>csrf_token: CSRF token used in application AJAX calls</li>
     * <li>admin_list_per_page: Default number of items to show in admin list pages</li>
     * <li>front_list_per_page: Default number of items to show in frontend list pages</li>
     * </ul>
	 *
	 * @access public
	 * @param string $name The config file name, minus .php extension
	 * @return array The configuration array or empty array if not defined
	 * @throws \App\Exception\AppException if $name empty or configuration file not found
	 */
	public function load_config($name) {
		$error = '';
		if ( empty($name) ) {
			$error = 'Invalid param (string) $name: must not be empty value';
		} else {
			$dir =  dirname(__FILE__).DIRECTORY_SEPARATOR.'Config'.DIRECTORY_SEPARATOR;
			$filename = $name.'.php';
			if ( is_file($dir.$filename) ) {
				include($dir.$filename);

                if ($name === 'config') {
                // Reads the main config JSON file for the app and populates
                // common values between javascript and API components;
                // Updates to any of these values should be made in ./abstract.json

                    $json = file_get_contents(WEB_ROOT.'/abstract.json');
                    $cfg = empty($json) ? array() : json_decode($json, true);

                    // True to display all php errors in developement environment,
                    // false for production environment
                    $config['debug'] = $cfg['debug'];

                    // Number of default items to initially show in admin list pages
                    $config['admin_list_per_page'] = $cfg['adminPagerPerPage'];

                    // URL segment for CMS (e.g. http://www.website.com/admin)
                    $config['admin_uri_segment'] = $cfg['adminUri'];

                    // CSRF token cookie name used in app
                    $config['csrf_token'] = $cfg['csrfToken'];

                    // Number of default items to initially show in frontend list pages
                    $config['front_list_per_page'] = $cfg['frontPagerPerPage'];

                    // Framework used for frontend web templates (e.g. bootstrap, foundation)
                    $config['front_framework'] = $cfg['frontFramework'];

                    // Configuration for frontend navigation bar
                    $config['front_nav_config'] = $cfg['frontNavConfig'];

                    // True if navigation bar shows search field (for Foundation)
                    $config['front_nav_has_search'] = $cfg['frontNavHasSearch'];

                    // Variables used in frontend templates
                    $config['front_template_vars'] = $cfg['frontTemplateVars'];

                    // DOM ID for container containing the page content, loaded by AJAX
                    $config['page_content_id'] = $cfg['pageContentId'];

                    // Header to retrieve parameters of previous page template
                    $config['tpl_info_header'] = $cfg['tplInfoHeader'];
                }

				return isset($config) ? $config : array();
			} else {
				$error = 'Configuration file not found: '.$dir.$filename;
			}
		}
		
		if ( ! empty($error) ) {
			throw new AppException($error, AppException::ERROR_FATAL);
		}
	}
	
	
	/**
	* load_lang
	* 
	* Loads a language translation file from the application using the current locale from the 
	* ./App/Lang/[lang]/[country] directory. Note that the main local file, named 
	* ./App/Lang/[lang]/[country]/[locale].php is loaded by the application at runtime.
	* 
	* @access public
	* @param string $name The language file name, minus .php extension
	* @return array The language array or empty array if not defined
	* @throws \App\Exception\AppException if $name empty or configuration file not found
	*/
	public function load_lang($name) {
		$error = '';
		if ( empty($name) ) {
			$error = 'Invalid param (string) $name: must not be empty value';
		} else {
			$locale = empty($this->config['locale']) ? '' : $this->config['locale'];
			$parts = explode('_', $locale);
			if (count($parts) !== 2) {
				$error = 'Locale ($config[locale]) not set in ./App/Config/config.php';
			} else {
				$dir =  dirname(__FILE__).DIRECTORY_SEPARATOR.'Lang'.DIRECTORY_SEPARATOR;
				$dir .= $parts[0].DIRECTORY_SEPARATOR.$parts[1].DIRECTORY_SEPARATOR;
				$filename = $name.'.php';
				if ( is_file($dir.$filename) ) {
					include($dir.$filename);
					return isset($lang) ? $lang : array();
				} else {
					$error = 'Language file not found: '.$dir.$filename;
				}
			}
		}
		
		if ( ! empty($error) ) {
			throw new AppException($error, AppException::ERROR_FATAL);
		}
	}
	
	
	/**
	* load_util
	* 
	* Includes a utility functions file from the application ./App/Util directory.
	* Note that the application file, ./App/Util/functions.php is loaded by
	* the application at runtime.
	* 
	* @access public
	* @param string $name The file name, minus .php extension
	* @return void
	* @throws \App\Exception\AppException if $name empty or utility file not found
	*/
	public function load_util($name) {
		$error = '';
		if ( empty($name) ) {
			$error = 'Invalid param (string) $name: must not be empty value';
		} else {
			$dir =  dirname(__FILE__).DIRECTORY_SEPARATOR.'Util'.DIRECTORY_SEPARATOR;
			$filename = $name.'.php';
			if ( is_file($dir.$filename) ) {
				require_once($dir.$filename);
			} else {
				$error = 'Utility file not found: '.$dir.$filename;
			}
		}
		
		if ( ! empty($error) ) {
			throw new AppException($error, AppException::ERROR_FATAL);
		}
	}
	
	
	/**
	 * load_view
	 *
	 * Loads a view file from the application ./App/View directory and returns
	 * the contents as a string. Files are passed through the output buffer so any
     * PHP echo statements are executed prior to returning.
     * Note that view files must have a ".phtml" extension.
	 *
	 * @access public
	 * @param string $name The view file name, minus .php extension
     * @param array $data Assoc array which is extracted and used as vars in the template
	 * @return string The contents of the view file
	 * @throws \App\Exception\AppException if $name empty or view file not found
	 */
	public function load_view($name, $data=array()) {
		$error = '';
		if ( empty($name) ) {
			$error = 'Invalid param (string) $name: must not be empty value';
		} else {
			$dir =  dirname(__FILE__).DIRECTORY_SEPARATOR.'View'.DIRECTORY_SEPARATOR;
			$filename = $name.'.phtml';
			if ( is_file($dir.$filename) ) {
			    if ( ! empty($data) ) {
			        extract($data);
                }
                ob_start();
                include($dir.$filename);
                return ob_get_clean();
			} else {
				$error = 'View file not found: '.$dir.$filename;
			}
		}
		
		if ( ! empty($error) ) {
			throw new AppException($error, AppException::ERROR_FATAL);
		}
	}
	
	
	/**
	* session
	* 
	* Returns a singleton instance of the application session.
	*
	* @access public
	* @param string $sess_cookie Optional name of the cookie holding the session ID
	* @param int $sess_timeout Session lifetime in seconds, zero or false for default
	* @return \App\User\Session The session singleton instance
    * @throws \App\Exception\AppException if an application error occurred, handled in this class
	*/
	public static function session($sess_cookie='', $sess_timeout=0) {
		return \App\User\Session::get_instance($sess_cookie, $sess_timeout);
	}
	
	
	/**
	* upload_config
	* 
	* Returns file upload configuration parameters for a given name
	* and whether an image or file configuration from \App\Config\uploads.php.
	* 
	* @access public
	* @param string $name The config key_name (minus "_image" or "_file" extension)
	* @param bool $is_image True if image upload configuration, false for other file types
	* @return mixed The upload configuration parameters or false if param $name not a key
    * @throws \App\Exception\AppException if an application error occurred, handled in this class
	*/
	public function upload_config($name, $is_image) {
		if ( empty($name) ) {
			return false;
		}
		
		$config_ext = $is_image ? '_image' : '_file';
		$upload_cfg = $this->load_config('uploads');
		$config = false;
		
		if ( ! empty($upload_cfg[$name.$config_ext]) ) {
			$config = $upload_cfg[$name.$config_ext] + $upload_cfg['default'.$config_ext];
			$config['is_image'] = $is_image;
		}
		
		return $config;
	}
	
}

/* End of file App.php */
/* Location: ./App/App.php */