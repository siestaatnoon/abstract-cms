<?php
/**
 * ImageEditor
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
namespace Upload\Image;

/**
 * Image Editor
 *
 * This class crops, resizes, rotates and creates thumbnails of image files given
 * an array of configuraton parameters.
 *
 * @author  Johnny Spence <johnny@projectabstractcms.org>
 * @since   1.0.0
 * @package Upload
 */
class ImageEditor {
	
    /**
     * Image update parameters
     * @var array
     */
    protected $config;
    
    /**
     * Errors that have occurred in image update
     * @var array
     */
    protected $errors;

    /**
     * Image upload information
     * @var array
     */
    protected $image;

    /**
     * Constructor
     *
     * @param  \Upload\File               $File instance of file uploaded
     * @param  array                      $config parameters for updating image files
     */
    public function __construct($config, $filepath, $filename) {
    	if ( empty($filename) ) {
    		$this->errors[] = 'Image filename does not exist';
        }

        $this->errors = array();
        
        $this->config = array( //defaults
			'quality' 			=> 85,
			'max_width' 		=> 0,
			'max_height' 		=> 0,
			'min_width' 		=> 0,
			'min_height' 		=> 0,
			'thumb_width' 		=> 0,
			'thumb_height' 		=> 0,
			'thumb_ext' 		=> '_thumb',
			'exact_dimensions' 	=> false,
			'cropped' 			=> false,
			'create_thumb' 		=> false
		);
		$this->config = $config + $this->config;
       
        if ( ! is_file($filepath.DIRECTORY_SEPARATOR.$filename) ) {
        	$this->errors[] = 'File ['.$filename.'] not found';
		}
        
        $info = getimagesize($filepath.DIRECTORY_SEPARATOR.$filename);
        $valid_mimes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png');
        if ( ! $info) {
        	$this->errors[] = 'File ['.$filename.'] not a valid image';
        }
        if ( ! in_array($info['mime'], $valid_mimes) ) {
        	$this->errors[] = 'File ['.$filename.'] must be of image type';
        }
        
        $this->image = array(
			'file' 		=> $filename,
			'file_path' => $filepath,
			'mime'		=> $info['mime'],
			'dims' 		=> array('width' => $info[0], 'height' => $info[1])
		);
			
		$this->validate();
    }

    /**
     * getErrors
     * 
     * Returns the errors occurred while updating uploaded image
     *
     * @return array The array of errors
     */
    public function getErrors() {
		return $this->errors;
	}

    /**
     * update
     * 
     * Performs the crop and/or resize and/or create thumbnail for the uploaded image
     *
     * @return mixed Array with filename and filesize (plu thumb name/size) or false if errors occurred
     */
    public function update() {
		if ( ! empty($this->errors) ) {
			return false;
		}
		
		$response = array();
		if ( $this->crop() || $this->resize() ) {
			clearstatcache();
			$response['filename'] = $this->image['file'];
			$response['filesize'] = filesize($this->image['file_path'].DIRECTORY_SEPARATOR.$this->image['file']);
		}
		
		if ( ($thumb = $this->thumbnail()) !== false ) {
			$response['thumb'] = array(
				'filename' => $thumb,
				'filesize' => filesize($this->image['file_path'].DIRECTORY_SEPARATOR.$thumb)
			);
		}
		return empty($this->errors) ? $response : false;
	}

    /**
     * crop
     * 
     * Performs a crop of the uploaded image
     *
     * @return bool True if crop performed
     */
    protected function crop() {
    	if ( ! $this->config['cropped'] || ($this->config['min_width'] <= 0 && $this->config['min_height'] <= 0) ) {
			return false;
		}
    	$filepath =  $this->image['file_path'].DIRECTORY_SEPARATOR.$this->image['file'];
    	$width = $this->image['dims']['width'];
    	$height = $this->image['dims']['height'];
    	$crop_width = $this->config['min_width'] <= 0 ? $width : $this->config['min_width'];
    	$crop_height = $this->config['min_height'] <= 0 ? $height : $this->config['min_height'];
    	$diff_x = $width - $crop_width;
    	$diff_y = $height - $crop_height;
    	
    	if ($diff_x > 0 || $diff_y > 0) {
	    	if ($diff_x > $diff_y) {
				$this->scale(0, $crop_height);
			} else {
				$this->scale($crop_width, 0);
			}
			$this->reset_dims();
			$width = $this->image['dims']['width'];
    		$height = $this->image['dims']['height'];
	    	$x_offset = round( ($width - $crop_width) / 2 );
	    	$y_offset = round( ($height - $crop_height) / 2 );
	    	$this->image($filepath, $crop_width, $crop_height, $crop_width, $crop_height, $x_offset, $y_offset);
	    	$this->reset_dims();
		}
		return true;
	}

    /**
     * image
     * 
     * Creates the updated image
     *
     * @param string $img_path The full path to destination image
     * @param int $width The width of the new image
     * @param int $height The height of the new image
     * @param int $src_width The width of the source image
     * @param int $src_height The height of the source image
     * @param int $x_offset The width offset to copy from source image
     * @param int $y_offset The height offset to copy from source image
     * @return bool True if image updated
     */
	protected function image($img_path, $width, $height, $src_width=0, $src_height=0, $x_offset=0, $y_offset=0) {
		$src_path = $this->image['file_path'].DIRECTORY_SEPARATOR.$this->image['file'];
		$dims = $this->image['dims'];
		$mime = $this->image['mime'];
		$quality = $this->config['quality'];
		
		if( ! is_file($src_path) ){
			$this->errors[] = 'Image '.$this->image['file'].' not found';	
		} else if ( empty($this->errors) ) {
			$dest_image = imagecreatetruecolor($width, $height);
			$src_image = NULL;
			
			switch($mime) {
				case 'image/jpeg':
			    case 'image/pjpeg':
					imageantialias($dest_image, true);
					$src_image = imagecreatefromjpeg($src_path);
			        break;
			    case 'image/png':
					$src_image = imagecreatefrompng($src_path);
					break;
			    case 'image/gif':
					$src_image = imagecreatefromgif($src_path);
			        break;
			    default: 
			    	$this->errors[] = 'Image file type ['.$mime.'] not supported';
			    	return;
			}
			
			imagecolortransparent($dest_image, imagecolorallocate($dest_image, 0, 0, 0) );
			imagecopyresampled(
				$dest_image, 
				$src_image, 0, 0, 
				$x_offset, 
				$y_offset, 
				$width, 
				$height, 
				($src_width <= 0 ? $dims['width'] : $src_width), 
				($src_height <= 0 ? $dims['height'] : $src_height)
			);
			
			switch($mime) {
				case 'image/jpeg':
		    	case 'image/pjpeg':
					imagejpeg($dest_image, $img_path, $quality);
					break;													
				case 'image/png':
					imagepng($dest_image, $img_path, round( ($quality / 10), 0, PHP_ROUND_HALF_DOWN) );
					break;									
				case 'image/gif':		
					imagegif($dest_image, $img_path);
					break;								
			}
		}
		
		imagedestroy($dest_image);
		return empty($this->errors);
	}

    /**
     * resize
     * 
     * Performs a resize of the uploaded image
     *
     * @return bool True if resize performed
     */
    protected function resize() {
    	if ($this->config['min_width'] > 0 || $this->config['min_height'] > 0 || 
    		($this->config['max_width'] <= 0 && $this->config['max_height'] <= 0) ) {
			return false;
		}
		$this->scale($this->config['max_width'], $this->config['max_height']);
		$this->reset_dims();
		return true;
	}

    /**
     * scale
     * 
     * Scales an image to fit a max width and max height boundary
     *
     * @param int $max_width The maximum width of the image resize
     * @param int $max_height The maximum height of the image resize
     * @param string $filepath Optional full path to destination file
     * @return void
     */
	protected function scale($max_width, $max_height, $filepath='') {
		if ( empty($filepath) ) {
			$filepath =  $this->image['file_path'].DIRECTORY_SEPARATOR.$this->image['file'];
		}
		
		$img_width = $this->image['dims']['width'];
		$img_height = $this->image['dims']['height'];
		$new_width = 0;
		$new_height = 0;
		
		$rs_width = $max_width;
		if ($rs_width <= 0) {
			$rs_width = $max_height <= 0 ? $img_width : round( ($img_width / $img_height) * $max_height);
		}
		$rs_height = $max_height;
		if ($rs_height <= 0) {
			$rs_height = $max_width <= 0 ? $img_height : round( ($img_height / $img_width) * $max_width);
		}

		$dim_ratio = round($img_width / $img_height, 2);
		$resize_ratio = round($rs_width / $rs_height, 2);
		
		if ($dim_ratio == $resize_ratio) {
			$new_width = $rs_width;
			$new_height = $rs_height;
		} else if ($dim_ratio < $resize_ratio) {
			$new_height = $rs_height;
			$new_width = round( ($img_width / $img_height) * $rs_height );
		} else {
			$new_width = $rs_width;
			$new_height = round( ($img_height / $img_width) * $rs_width);
		}
	
		return $this->image($filepath, $new_width, $new_height);
	}

    /**
     * thumbnail
     * 
     * Creates a thumbnail of the uploaded image
     *
     * @return mixed The thumbnail filename or false if thumbnail not created
     */
    protected function thumbnail() {
    	if ( ! $this->config['create_thumb'] || 
    		($this->config['thumb_width'] <= 0 && $this->config['thumb_height'] <= 0) ) {
			return false;
		}
    	$filename = $this->image['file'];
    	$ext_pos = strrpos($filename, '.');
		$ext = substr($filename, $ext_pos);
		$filename = substr($filename, 0, $ext_pos);
		$filename .= (empty($this->config['thumb_ext']) ? '_thumb' : $this->config['thumb_ext']).$ext;
		$filepath =  $this->image['file_path'].DIRECTORY_SEPARATOR.$filename;
		$this->scale($this->config['thumb_width'], $this->config['thumb_height'], $filepath);
		return $filename;
	}

    /**
     * validate
     * 
     * Validates the uploaded image parameters to the configuration of the updates to it.
     * Populates th classe member error array if errors found.
     *
     * @return bool True if uploaded image validates, false if errors found
     */
    protected function validate() {
    	$width = $this->image['dims']['width'];
    	$height = $this->image['dims']['height'];
    	$min_width = $this->config['min_width'];
    	$min_height = $this->config['min_height'];
    	
    	if ($min_width > 0 || $min_height > 0) {
    		$w_msg = $min_width.' pixels in width';
    		$h_msg = $min_height.' pixels in height';
    		$msg = array();

			if ($this->config['exact_dimensions']) {
				if ($min_width > 0 && $width !== $min_width) {
					$msg[] = $w_msg;
				}
				if ($min_height > 0 && $height !== $min_height) {
					$msg[] = $h_msg;
				}
				if ( ! empty($msg) ) {
					$this->errors[] = 'Image ['.$this->image['file'].'] must be exactly '.implode(' and ', $msg);
					$msg = array();
				}
			} else {
				if ($min_width > 0 && $width < $min_width) {
					$msg[] = $w_msg;
				}
				if ($min_height > 0 && $height < $min_height) {
					$msg[] = $h_msg;
				}
				if ( ! empty($msg) ) {
					$this->errors[] = 'Image ['.$this->image['file'].'] must be minimum '.implode(' and ', $msg);
				}
			}
		}
		return empty($this->errors);
	}
	
    /**
     * reset_dims
     * 
     * Resets the the global width and height, saved for the image, after a
     * resize or crop has been made
     *
     * @return void
     */
	protected function reset_dims() {
		list($width, $height) = getimagesize($this->image['file_path'].DIRECTORY_SEPARATOR.$this->image['file']);
		$this->image['dims']['width'] = $width;
		$this->image['dims']['height'] = $height;
	}
}
