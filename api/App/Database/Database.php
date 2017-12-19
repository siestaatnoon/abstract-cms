<?php

namespace App\Database;

use 
App\App,
App\Database\Driver\PdoMysql\DriverPdoMysql,
App\Database\Driver\Mysqli\DriverMysqli,
App\Exception\AppException;

/**
 * Database class
 * 
 * This provides the driver-dependant database connection(s) for the application. A call
 * to \App\Database\Database::connection() will connect to a database with a driver specified
 * in ./Api/config.php. Additional connections can be made if the call is made with a 
 * $config array parameter containing the following parameters:<br/><br/>
 * <ul>
 * <li>driver => pdo|mysqli (MySQL)</li>
 * <li>host => Database host</li>
 * <li>port => Port number</li>
 * <li>username => Database username</li>
 * <li>password => Database password</li>
 * <li>db_name => Database name</li>
 * <li>charset => (optional) utf8 default</li>
 * </ul>
 * 
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Database
 */
class Database {
	
	/**
	 * @var \App\Database\Driver\Driver The default database connection object (singleton).
	 */
	private static $db;
	
	/**
	 * @var array Stores all db connnections initialized.
	 */
	private static $connections = array();
	

	/**
	 * Constructor
	 * 
	 * This class is a static class so doesn't serve anything here.
	 * 
	 * @access public
	 */
	private function __construct() { }
	

	/**
	 * Destructor
	 * 
	 * Ensures all database connections made are closed upon script exit.
	 * 
	 * @access public
	 */
	public function __destruct() {
		foreach (self::$connections as &$db) {
			$db->close();
			$db = NULL;
		}
	}
	

	/**
	 * connection
	 * 
	 * Static method that returns a database connection. Using a $config_name param will return a
	 * database object using those parameters from the application config file in ./Api/config.php.
	 * If called without the $config_name, the connection will be created using the default connection
	 * parameters. For example:<br/><br/>
	 * <pre>
	 * $config['database'['default'] = array(...); //default connection parameters<br/>
	 * $config['database'['my_connection'] = array(...); //parameters for other connection(s)
	 # </pre>
	 * <br/><br/>
	 * The configuration array in ./Api/config.php has the following parameters:<br/><br/>
	 * <ul>
	 * <li>driver => pdo|mysqli (MySQL)</li>
	 * <li>host => Database host</li>
	 * <li>port => Port number</li>
	 * <li>username => Database username</li>
	 * <li>password => Database password</li>
	 * <li>db_name => Database name</li>
	 * <li>charset => (optional) utf8 default</li>
	 * </ul>
	 * 
	 * @access public
	 * @param string $config_name The database configuration parameters specified in 
	 * $config[database][$config_name] in application config file
	 * @return \App\Database\Driver\Driver The driver-dependant database connection
	 * @throws \App\Exception\AppException if $config_name param invalid
	 */
	public static function connection($config_name='default') {
		$app = App::get_instance();
		$config = $app->config('database');
	
		if ( empty($config[$config_name]) ) {
			$message = 'Invalid param (string) $config_name "'.$config_name.'", connection ';
			$message .= 'parameters not defined in main config file';
			throw new AppException($message, AppException::ERROR_FATAL);
		}

		$is_default = empty($config_name);
		$db = NULL;
		
		if ($is_default) {
			self::$db = self::db_connect();
			self::$connections[] = self::$db;
		} else {
			$db = self::db_connect($config[$config_name]);
			self::$connections[] = $db;
		}
		
		return $is_default ? self::$db : $db;
	}
	

	/**
	 * db_connect
	 * 
	 * Static method that creates a database connection with a specific driver. 
	 * 
	 * @access public
	 * @param array $config The database configuration parameters
	 * @return \App\Database\Driver\Driver The driver-dependant database connection
	 * @see \App\Model\Database\Driver\Driver The database driver abstract class definition
	 * @see \App\Model\Database\Driver\Mysqli\DriverMysqli The mysqli driver
	 * @see \App\Model\Database\Driver\PdoMysql\DriverPdoMysql The PDO MySQL driver
	 */
	private static function db_connect($config) {
		$db = NULL;
		$driver = empty($config['driver']) ? 'pdo' : $config['driver'];
		switch($driver) {
			case 'pdo' :
				$db = new DriverPdoMysql($config);
				break;
			case 'mysqli' :
				$db = new DriverMysqli($config);
				break;
			default :
				$db = new DriverPdoMysql($config);
		}
		
		return $db;
	}
}

/* End of file Database.php */
/* Location: ./App/Database/Database.php */