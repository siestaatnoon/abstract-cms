<?php

namespace App\Exception;

use Exception;

/**
 * AppException class
 *
 * This class extends the PHP \Exception class and is the primary exception that occurs within
 * this application.
 *
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Exception
 */
class AppException extends Exception {
	
	const ERROR_FATAL = 1776;

    const ERROR_RUNTIME = 1992;
	
	public function __construct($message, $code=self::ERROR_RUNTIME, Exception $previous=NULL) {
		if ($code !== $code=self::ERROR_FATAL && $code !== $code=self::ERROR_RUNTIME) {
			$code = self::ERROR_RUNTIME;
		}
		
		$class = get_class($this);
		$message = empty($message) ? error_str('error.exception.unknown', $class) : $class.": ".$message;
		parent::__construct($message, $code, $previous);
	}
}

/* End of file AppException.php */
/* Location: ./App/Exception/AppException.php */