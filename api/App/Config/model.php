<?php

$config['data_types'] = array(
	'varchar' => array(
		'max_length'	=> 255,
		'length'		=> 128,
		'lang'			=> 'sql.varchar'
	),
	'text' => array(
		'lang'			=> 'sql.text'
	),
	'mediumtext' => array(
		'lang'			=> 'sql.mediumtext'
	),
	'tinyint' => array(
		'length'		=> 1,
		'default'		=> 0,
		'lang'			=> 'sql.tinyint'
	),
	'int' => array(
		'max_length' 	=> 255,
		'length'		=> 15,
        'default'		=> NULL,
		'lang'			=> 'sql.int'
	),
	'float' => array(
		'max_length' 	=> 255,
		'length'		=> 15,
        'default'		=> NULL,
		'lang'			=> 'sql.float'
	),
	'decimal' => array(
		'length' 	 	=> 15,
		'decimals' 	 	=> 2,
        'default'		=> NULL,
		'lang'			=> 'sql.decimal'
	),
	'char' => array(
		'max_length' 	=> 255,
		'length'		=> 8,
		'lang'			=> 'sql.char'
	),
	'date' => array(
        'default'		=> NULL,
		'lang'			=> 'sql.date'
	),
	'time' => array(
        'default'		=> NULL,
		'lang'			=> 'sql.time'
	),
	'datetime' => array(
        'default'		=> NULL,
		'lang'			=> 'sql.datetime'
	),
	'enum' => array(
		'values'		=> array(),
		'lang'			=> 'sql.enum'
	)
);

/* End of file model.php */
/* Location: ./App/Config/model.php */