<?php
/**
 * Created on Dec 10, 2022
 * 
 * Time Created : 7:16:14 PM
 * Filename     : canvas.connections.php
 *
 * @filesource canvas.connections.php
 *
 * @author     wisnuwidi @CanvaStack - 2022
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 */
 
return [
	'sources' => [
		'mysql_mantra_etl_server' => [
			'label'           => 'Mantra Server 37',
			'connection_name' => 'mysql_mantra_etl_server'
		],
		'mysql_mantra_etl_server_dev' => [
			'label'           => 'Mantra Server Hostinger',
			'connection_name' => 'mysql_mantra_etl_server_dev'
		]
	]
];