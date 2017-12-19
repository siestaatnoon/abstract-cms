<?php

namespace App\Exception;

use App\Exception\AppException;

class ErrorException extends AppException {	
	public function __construct($message, $file, $line) {
		parent::__construct($message, AppException::ERROR_FATAL);
		$this->message  = empty($message) ? "An unknown ".get_class($this)." has occurred" : "PHP Error: ".$message;
		$this->file = $file;
		$this->line = $line;
	}
}

/* End of file ErrorException.php */
/* Location: ./App/Exception/ErrorException.php */