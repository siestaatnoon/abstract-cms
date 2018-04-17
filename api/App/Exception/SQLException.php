<?php

namespace App\Exception;

use App\Exception\AppException;

/**
 * SQLException class
 *
 * This class extends the PHP AppException class and is the exception that occurs
 * for SQL and other database related errors within this application.
 *
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Exception
 */
class SQLException extends AppException {	

	public function __construct($message, $code=AppException::ERROR_RUNTIME, Exception $previous=NULL) {
		parent::__construct($message, $code, $previous);
		$class = get_class($this);
		$this->message  = empty($message) ? error_str('error.exception.unknown', $class) : $class.": ".$message;
	}
}

/* End of file SQLException.php */
/* Location: ./App/Exception/SQLException.php */