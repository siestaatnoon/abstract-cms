<?php

/**
 * Name of cookie holding user data after CMS login, may be changed to avoid collisions
 */
$config['cms_user_cookie'] = 'abstract_user';

/**
 * Default charset of application (not including database)
 */
$config['charset'] = 'UTF-8';

/**
 * Database configuration for application
 */
$config['database']['default'] = array(
	'driver' 	=> 'mysqli', // (pdo|mysqli)
	'host' 		=> 'localhost',
	'port' 		=> 3306,
	'username' 	=> 'test',
	'password' 	=> 'test',
	'db_name' 	=> 'test',
	'charset' 	=> 'utf8',
	'debug' 	=> true
);

$config['database']['test'] = array(
	'driver' 	=> 'pdo',
	'host' 		=> 'localhost',
	'port' 		=> 3306,
	'username' 	=> 'test',
	'password' 	=> 'test',
	'db_name' 	=> 'test',
	'charset' 	=> 'utf8',
	'debug' 	=> true
);

/**
 * Database confguration from above to use(e.g. $config['database'][xxx])
 */
$config['db_config'] = 'test';

/**
 * Path to database query log file
 */
$config['db_query_log'] = '../logs/queries.log';

/**
 * Prefix to all table names in database
 */
$config['db_table_prefix'] = 'aa_';

/**
 * Path to error log file
 */
$config['error_log'] = '../logs/errors.log';

/**
 * Locale to use for I18n internacionalization
 */
$config['locale'] = 'en_US';

/**
 * Set true to display all application and php errors and should
 * only be set to true in development environment
 */
$config['log_errors'] = true;

/**
 * Set true to log all database queries and should
 * only be set to true in development environment
 */
$config['log_queries'] = true;

/**
 * Maximum login attempts before CMS user is locked out
 * NOTE: set to false to disable lockout
 */
$config['login_max_attempts'] = 20;

/**
 * Number of seconds CMS user locked out after reaching max login attempts
 * NOTE: set to zero or false to disable lockout
 */
$config['login_timeout_lock'] = 3600;

/**
 * Allows for updates to immutable fields in modules module
 *
 * WARNING: Setting to true may cause unintended affects to database tables/fields
 */
$config['modules_immutable_updates'] = false;

/**
 * Maximum depth of subpages in pages module
 */
$config['pages_max_depth'] = 3;

/**
 * String used to hash user passwords
 */
$config['pass_hash_salt'] = 'W7a*wA47RAj?d6abraD*eBRumesAn4Th';

/**
 * Name of cookie holding general session key (non-CMS)
 */
$config['session_cookie'] = 'abs_session';

/**
 * Name of cookie holding CMS session key
 */
$config['session_cms_cookie'] = 'abs_cms_session';

/**
 * Max session life in seconds for CMS user (if checks "Remember Me" in login)
 */
$config['session_cms_max_time'] = (60*60*24*7); //7 days

/**
 * Default session time in seconds for session
 */
$config['session_max_time'] = 3600; //1 hour

/**
 * String used to create a session cookie hash for encryption
 */
$config['session_hash_salt'] = 'zouxl8slAzia#oecRlukoaFroeTiAtr?';

/**
 * Set true to strip HTML and script tags from POST input
 */
$config['strip_post_tags'] = true;

/**
 * Directory in web root containing template files
 */
$config['templates_dir'] = 'templates';


/* End of file config.php */
/* Location: ./App/Config/config.php */