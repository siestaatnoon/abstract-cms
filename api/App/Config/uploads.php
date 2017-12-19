<?php
/*
File upload configuration

The "default_file" and "default_image" are the default upload parameters used for
files and images respectively. You may add your own configurations for any upload
with parameters you want changed which is then merged with the default configuration.
Note that "upload_path" is required for every upload configuration.

File upload config must be suffixed with a "_file"
Image upload config must be suffixed with a "_image"

Examples:

	$config['pdf_file'] = array(
	...
	);
	
	$config['product_image'] = array(
	...
	);

*/


/*
 * Default file upload settings. Adjust as necesary but DO NOT delete any indexes.
 */
$config['default_file'] = array(
	'upload_path' 	=> '',								//- root relative path to image directory e.g. /path/to/directory
	'allowed_types' => 'csv,doc,docx,pdf,rtf,txt,xlxs,zip',	//- allowed file extensions, comma separated
	'max_size' 		=> '5mb',							//- max filesize in mb, kb, b
	'max_uploads' 	=> 1,								// - numbers of files allowed for upload
	'overwrite' 	=> false							//- set true to overwrite an existing file with the same name
);

/*
 * Default image upload settings. Adjust as necessary but DO NOT delete any indexes.
 */
$config['default_image'] = array(
	'upload_path' 		=> '',					// - root relative path to image directory e.g. /path/to/directory
	'allowed_types' 	=> 'gif,jpg,jpeg,png',	// - allowed file extensions
	'thumb_ext' 		=> '_thumb',			// - extension for thumb file (e.g. filename_thumb.jpg)
	'max_size' 			=> '5mb',				// - max filesize in mb, kb, b
	'max_uploads' 		=> 1,					// - numbers of files allowed for upload
	'quality' 			=> 85,					// - JPEG compression (divided by 10 for PNG)
	'min_width' 		=> 0,					// - min width of image upload, overrides max_width if set
	'min_height' 		=> 0,					// - min height of image upload, overrides max_height if set
	'max_width' 		=> 0,					// - max dimensions image/thumb upload will resize to.
	'max_height' 		=> 0,					//      if any dimension is set to zero then the
	'thumb_width' 		=> 0,					//      max dimension will default to the dimension size
	'thumb_height' 		=> 0,					//      of the uploaded image
	'exact_dimensions' 	=> false,				// - if set true, dimensions must be min_width and min_height
	'cropped' 			=> false,				// - if set true, image will crop to min_width and min_height
	'create_thumb' 		=> false,				// - set true to create a thumb file
	'overwrite' 		=> false				// - set true to overwrite an existing file with the same name
);

$config['test_file'] = array(
	'upload_path' 	=> WEB_BASE.'/media/files',
	'allowed_types' => 'doc,docx,pdf,zip',
	'max_uploads' 	=> 10
);

$config['test_image'] = array(
	'upload_path' 	=> WEB_BASE.'/media/images',
	'min_width' 	=> 300,
	'min_height' 	=> 300,
	'cropped' 		=> true,
	'thumb_width' 	=> 100,
	'thumb_height' 	=> 100,
	'create_thumb' 	=> true,
	'max_uploads' 	=> 5
);

/* End of file uploads.php */
/* Location: ./App/Config/uploads.php */