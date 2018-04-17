<?php

namespace App\Exception;

use App\Exception\AppException;

/**
 * ErrorException class
 *
 * This class extends the PHP AppException class and is the exception that occurs
 * for fatal PHP errors within this application.
 *
 * @author      Johnny Spence <info@projectabstractcms.com>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.com
 * @version     0.1.0
 * @package		App\Exception
 */
class ErrorException extends AppException {	
	public function __construct($message, $file, $line) {
		parent::__construct($message, AppException::ERROR_FATAL);
        $class = get_class($this);
		$this->message  = empty($message) ? error_str('error.exception.unknown', $class) : "PHP Error: ".$message;
		$this->file = $file;
		$this->line = $line;
	}
}

/* End of file ErrorException.php */
/* Location: ./App/Exception/ErrorException.php */