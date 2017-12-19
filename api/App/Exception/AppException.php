<?php

namespace App\Exception;

use Exception;

class AppException extends Exception {
	
	const ERROR_FATAL = 1776;

    const ERROR_RUNTIME = 1992;
	
	public function __construct($message, $code=self::ERROR_RUNTIME, Exception $previous=NULL) {
		if ($code !== $code=self::ERROR_FATAL && $code !== $code=self::ERROR_RUNTIME) {
			$code = self::ERROR_RUNTIME;
		}
		
		$class = get_class($this);
		$message = empty($message) ? "An unknown ".$class." has occurred" : $class.": ".$message;
		parent::__construct($message, $code, $previous);
	}
}

/* End of file AppException.php */
/* Location: ./App/Exception/AppException.php */