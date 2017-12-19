<?php

namespace App\Exception;

use App\Exception\AppException;

class SQLException extends AppException {	

	public function __construct($message, $code=AppException::ERROR_RUNTIME, Exception $previous=NULL) {
		parent::__construct($message, $code, $previous);
		$class = get_class($this);
		$this->message  = empty($message) ? "An unknown ".$class." has occurred" : $class.": ".$message;
	}
}

/* End of file SQLException.php */
/* Location: ./App/Exception/SQLException.php */