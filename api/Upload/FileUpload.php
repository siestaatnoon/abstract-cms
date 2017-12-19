<?php
/**
 * FileUpload
 *
 * @author      Johnny Spence <johnny@projectabstractcms.org>
 * @copyright   2015 John Spence
 * @link        http://www.projectabstractcms.org
 * @version     1.0.0
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
namespace Upload;

/**
 * FileUpload
 *
 * FileUpload provides an  by validating the upload and, if an image, can be set to
 * modify it based on configuration parameters. Upon completion, an array of errors
 * or an array with file information will be returned.
 *
 * @author  Johnny Spence <johnny@projectabstractcms.org>
 * @since   1.0.0
 * @package Upload
 */
class FileUpload
{
    /**
     * Array of file extension => mime type(s)
     * @var array
     */
    protected static $MIMES = array(
		'avi'	=>	'video/x-msvideo',
		'bmp'	=>	array('image/bmp', 'image/x-windows-bmp'),
		'csv'	=>	array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
		'doc'	=>	'application/msword',
		'docx'	=>	array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'),
		'gif'	=>	'image/gif',
		'jpeg'	=>	array('image/jpeg', 'image/pjpeg'),
		'jpg'	=>	array('image/jpeg', 'image/pjpeg'),
		'jpe'	=>	array('image/jpeg', 'image/pjpeg'),
		'mov'	=>	'video/quicktime',
		'mp3'	=>	array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
		'mp4'	=>	'video/mp4',
		'mpeg'	=>	'video/mpeg',
		'mpg'	=>	'video/mpeg',
		'mpe'	=>	'video/mpeg',
		'pdf'	=>	array('application/pdf', 'application/x-download'),
		'png'	=>	array('image/png',  'image/x-png'),
		'psd'	=>	'application/x-photoshop',
		'qt'	=>	'video/quicktime',
		'rtf'	=>	'text/rtf',
		'tiff'	=>	'image/tiff',
		'tif'	=>	'image/tiff',
		'txt'	=>	'text/plain',
		'xls'	=>	'application/excel',
		'xlsx'	=>	array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'),
		'word'	=>	array('application/msword', 'application/octet-stream'),
		'xml'	=>	'text/xml',
		'zip'	=>  array('application/x-zip', 'application/zip', 'application/x-zip-compressed')
	);
    
    /**
     * Array of file extension => mime type description
     * @var array
     */
    protected static $MIME_DESCR = array(
		'avi'	=>	'Windows Video File',
		'bmp'	=>	'Bitmap Image',
		'csv'	=>	'Comma Delimited File',
		'doc'	=>	'MS Word Document',
		'docx'	=>	'MS Word Document',
		'gif'	=>	'GIF Image',
		'jpeg'	=>	'JPEG Image',
		'jpg'	=>	'JPEG Image',
		'jpe'	=>	'JPEG Image',
		'mov'	=>	'Quicktime Movie',
		'mp3'	=>	'MPEG-1 Audio Layer-3 File',
		'mp4'	=>	'MPEG-4 Video File',
		'mpeg'	=>	'MPEG Video File',
		'mpg'	=>	'MPEG Video File',
		'mpe'	=>	'MPEG Video File',
		'pdf'	=>	'PDF Document',
		'png'	=>	'PNG Image',
		'psd'	=>	'Photoshop Document',
		'qt'	=>	'Quicktime Video',
		'rtf'	=>	'Rich Text Format Document',
		'tiff'	=>	'Tag Image File',
		'tif'	=>	'Tag Image File',
		'txt'	=>	'Text File',
		'xls'	=>	'MS Excel Document',
		'xlsx'	=>	'MS Excel Document',
		'word'	=>	'MS Word Document',
		'xml'	=>	'XML File',
		'zip'	=>  'ZIP Compressed File'
	);
	
    /**
     * Parameters for file/image upload
     * @var array
     */
    protected $config = array();

    /**
     * Validation errors
     * @var array[String]
     */
    protected $errors = array();
    
    /**
     * File upload object
     * @var \Upload\File
     */
    protected $File;
    
    /**
     * Full upload path
     * @var string
     */
    protected $filepath;
    
    /**
     * True if file upload an image
     * @var bool
     */
    protected $isImage = false;
    
    /**
     * Resulting filename, filesize and errors from each upload
     * @var array[array]
     */
    protected $results = array();


    /**
     * Constructor
     *
     * 
     * @param array $config The parameters for the file/image upload
     * @param string $filepath The full path to the upload directory
     * @param string $field_name The $_FILES[] key
     * @throws \InvalidArgumentException  If $filepath or $file_name params are empty
     */
    public function __construct($config, $filepath, $field_name) {
    	if ( empty($filepath) ) {
    		$this->errors[] = 'A server error has occurred [FileUpload]';
            throw new \InvalidArgumentException('Param $filepath empty, must be full path to upload directory');
        }
        if ( empty($field_name) ) {
        	$this->errors[] = 'A server error has occurred [FileUpload]';
            throw new \InvalidArgumentException('Param $field_name empty, must be $_FILES[] key');
        }
        
		$this->config = array( //defaults
			'allowed_types' => 'csv,doc,docx,pdf,rtf,txt,zip',
			'max_size' 		=> false,
			'max_uploads' 	=> 1,
			'overwrite' 	=> false
		);
		$this->config = $config + $this->config;
		$this->filepath = $filepath;
		$this->isImage = ! empty($this->config['is_image']);

		$storage = new \Upload\Storage\FileSystem($filepath, $this->config['overwrite']);
		$this->File = new \Upload\File('file', $storage);
    }
    
    /**
     * bytesToHumanReadable
     * 
     * Convert file size in bytes to human readable format
     *
     * @param  int $input File size in bytes
     * @return string
     */
    public static function bytesToHumanReadable($input) {
    	if ( ! is_numeric($input) ) {
			return '';
		}
        $input = (int) $input;
        $display = '';
        $units = array(
            'gb' => 1073741824,
            'mb' => 1048576,
            'kb' => 1024,
            'b' => 1,
        );
        
        foreach ($units as $unit => $bytes) {
			if ($input > $bytes) {
				$display = number_format( ($input / $bytes), 1).' '.strtoupper($unit);
				break;
			}
		}

        return $display;
    }

    /**
     * corsHeaders 
     * 
     * Enable cross-origin resource sharing
     *
     * @return void
     */
	public function corsHeader($headers = array(), $origin = '*') {
		$allow_origin_present = false;

		if (!empty($headers)) {
			foreach ($headers as $header => $value) {
				if (strtolower($header) == 'access-control-allow-origin') {
					$allow_origin_present = true;
				}
				header("$header: $value");
			}
		}

		if ($origin && !$allow_origin_present) {
			header("Access-Control-Allow-Origin: $origin");
		}

		// other CORS headers if any...
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
			exit; // finish preflight CORS requests here
		}
	}

    /**
     * getMimeDescr 
     * 
     * Returns the mime type description given a file extension
     *
     * @param string $ext The file extension
     * @return string The mime type description or false if param unknown extension
     */
	public static function getMimeDescr($ext) {
		if ( empty($ext) ) {
			return false;
		}
		$ext = strtolower($ext);
		return empty(self::$MIME_DESCR[$ext]) ? false : self::$MIME_DESCR[$ext];
	}

    /**
     * getMimeType 
     * 
     * Returns the mime type given a file extension
     *
     * @param string $ext The file extension
     * @return string The mime type or false if param unknown extension
     */
	public static function getMimeType($ext) {
		if ( empty($ext) ) {
			return false;
		}
		$ext = strtolower($ext);
		return empty(self::$MIMES[$ext]) ? false : self::$MIMES[$ext];
	}

    /**
     * noCacheHeaders 
     * 
     * Add headers to response to prevent caching of upload
     *
     * @return void
     */
	public function noCacheHeaders() {
		// Make sure this file is not cached (as it might happen on iOS devices, for example)
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}

    /**
     * Upload file (delegated to storage object)
     *
     * @return array An array of filename, filesize, file description 
     * and array of errors, if occurred
     */
    public function upload() {
		$result = array(
			'filename' 	=> '',
			'filesize' 	=> 0,
			'filetype'	=> '',
			'errors'	=> array()	
		);
		if ( ! empty($this->errors) ) {
			$result['errors'] = $this->errors;
			return $result;
		}
		
		//mime type validation
		$exts = explode(',', $this->config['allowed_types']);
		$valid_mimes = array();
		foreach ($exts as $ext) {
			$ext = trim( strtolower($ext) );
			$mimes = $this->getMimeType($ext);
			
			if ( ! empty($mimes) ) {
				if ( is_string($mimes) ) {
					$valid_mimes[] = $mimes;
				} else if ( is_array($mimes) ) {
					$valid_mimes = array_merge($valid_mimes, $mimes);
				}
			}
		}
		if ( ! empty($valid_mimes) ) {
			$this->File->addValidation( new \Upload\Validation\Mimetype($valid_mimes) );
		}
		
		//max filesize validation
		if ( ! empty($this->config['max_size']) ) {
			$max_size = $this->config['max_size'];
			$unit = strtoupper( substr($max_size, (strlen($max_size) - 2) ) );
			if ( in_array($unit, array('KB', 'MB', 'GB') ) ) {
				$max_size = substr($max_size, 0, (strlen($max_size) - 1) );
			}
			$this->File->addValidation( new \Upload\Validation\Size($max_size) );
		}
		
		$this->File->beforeUpload(function($fileInfo) {
			$filename = $this->sanitizeName( $fileInfo->getNameWithExtension() );
			
			//if not set to overwrite file, create a uniquename
			if ( ! $this->config['overwrite'] ) {
				$filename = $this->uniqueName($filename);
			}
			
			$fileInfo->setName($filename);
		});
		
		$this->File->afterUpload(function($fileInfo) {
			$errors = array();
			$filename = $fileInfo->getNameWithExtension();
			$fullpath = $this->filepath.DIRECTORY_SEPARATOR.$filename;
			$extension = strtolower( $fileInfo->getExtension() );
			$filesize = 0;
			
			if ($this->isImage) {
				$IE = new \Upload\Image\ImageEditor($this->config, $this->filepath, $filename);
				$img = $IE->update();
				if ($img === false) {
					$errors = $IE->getErrors();
					
					//delete uploaded image on error
					if ( @is_file($fullpath) ) {
						unlink($fullpath);
					}
				} else {
					$filesize = $this->bytesToHumanReadable($img['filesize']);
				}
			} else {
				$filesize = $this->bytesToHumanReadable( filesize($fullpath) );
			}
			
			$has_errors = count($errors) > 0;
			$filetype = $this->getMimeDescr($extension);
			$result = array(
				'filename' 	=> $has_errors ? '' : $filename,
				'filesize' 	=> $has_errors ? 0 : $filesize,
				'filetype'	=> empty($filetype) ? 'File' : $filetype,
				'errors'	=> $errors
			);
			
			$this->results[] = $result;
		});
	
		try {
		    $this->File->upload();
		} catch (\Exception $e) {
		    $result['errors'] = $this->File->getErrors();
			$this->results[] = $result;
		}
		
		return count($this->results) === 1 ? $this->results[0] : $results;
    }

    /**
     * sanitizeName
     * 
     * Updates a file name by updating foreign chars to English equivalents
     * and eliminating all other char besides letters, numbers, dash and
     * underscore.
     *
     * @param  string $filename The filename
     * @return string The sanitized filename
     */
    protected function sanitizeName($filename) {
    	if ( empty($filename) ) {
			return $filename;
		}
		
    	$ext_pos = strrpos($filename, '.');
		$ext = substr($filename, $ext_pos);
		$name = substr($filename, 0, $ext_pos);

		//replace foreign chars with english equivalents
		$name = preg_replace(
			'~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', 
			'$1', 
			htmlentities($name, ENT_QUOTES, 'UTF-8')
		);
		
		//change spaces to dash
		$name = preg_replace('/[ ]/', '-', $name);
		
		//allow only letters, numbers, dash and underscore
		$name = preg_replace('/[^A-Za-z0-9\-\_]/', '', $name);
		
		return $name.$ext;
	}
    

    /**
     * uniqueName
     * 
     * Creates a unique name for a file from class filepath. If a file
     * exists, it will be appended with a "-[int value]" starting at 1
     * until a unique name is created.
     *
     * @param  string $filename The filename
     * @return string A unique filename
     */
    protected function uniqueName($filename) {
		if ( empty($filename) ) {
			return $filename;
		}
		
		$dir = $this->filepath.DIRECTORY_SEPARATOR;
		$ext_pos = strrpos($filename, '.');
		$ext = substr($filename, $ext_pos);
		$name = substr($filename, 0, $ext_pos);
		$count = 1;

		while ( @is_file($dir.$name.$ext) ) {
			$parts = explode('-', $name);
			if ( count($parts) > 1 ) {
				$last_index = count($parts) - 1;
				$possible_num = $parts[$last_index];
				if ( is_numeric($possible_num) ) {
					$possible_num = ((int) $possible_num) + 1;
					$parts[$last_index] = $possible_num;
				} else {
					$parts[] = $count;
				}
			} else {
				$parts[] = $count;
			}
			
			$name = implode('-', $parts);
			$count++;
		}
		
		return $name;
	}
}
