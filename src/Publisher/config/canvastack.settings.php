<?php
/**
 * Created on Mar 30, 2017
 * Time Created	: 11:31:38 AM
 * Filename		: settings.php
 *
 * @filesource	settings.php
 *
 * @author		wisnuwidi @CanvaStack - 2017
 * @copyright	wisnuwidi, canvastack
 * @email		wisnuwidi@canvastack.com,
 *              wisnuwidi@canvastack.com
 */

$multiPlatform		     = false;

$platform                = [];
$platform['type']        = 'single';
$platform['table']       = false;
$platform['key']         = false;
$platform['name']        = false;
$platform['label']       = false;
$platform['route']       = false;

if (true === $multiPlatform) {
	// You can be free to change this variable value
	$platform['type']     = 'multiple';
	$platform['table']    = 'base_masjid';
	$platform['key']      = 'masjid_id';
	$platform['name']     = 'masjid';
	$platform['label']    = 'Masjid';
	$platform['route']    = 'modules.masjid';
}

return [
	'baseURL'             => env('APP_URL', 'http://your/domain/web/public'),
	'index_folder'        => 'public',
	'template'            => 'default',
	'base_template'       => 'assets/templates',
	'base_resources'      => 'assets/resources',
	'app_name'            => 'CanvaStack',
	'app_desc'            => 'CanvaStack Application Website',
	'version'             => 'cbxpsscdeis-v3.0.0',
	'lang'                => 'en',
	'charset'             => 'UTF-8',
	'encryption_key'      => 'IDRIS',
	'encode_separate'     => '|',
	'maintenance'         => false,
	// maintenance: if true, we can bypass with this code[login?as=username|email]
	// this set config file used to make sure if set database maintenance status changed by others or hacked or crashed database
	// so, the application will be read based on this file set.
		
	// PLATFORM
	'platform_type'       => $platform['type'],	 // ['single', 'multiple']
	'platform_table'      => $platform['table'], // if single = false
	'platform_key'        => $platform['key'],	 // if single = false
	'platform_name'       => $platform['name'],  // if single = false
	'platform_label'      => $platform['label'], // if single = false
	'platform_route'      => $platform['route'], // if single = false
	
	// COPYRIGHT INFO
	'copyrights'          => 'CanvaStack & All Muslim in the world',
	'location'            => 'Jakarta',
	'location_abbr'       => 'ID',
	'created_at'          => '2017 - ' . date('Y'),
	'email'               => 'wisnuwidi@canvastack.com',
	'website'             => 'canvastack.com',

	// Meta Tags
	'meta_author'         => 'Wisnu Widiantoko',
	'meta_title'          => 'CanvaStack',
	'meta_keywords'       => 'CanvaStack',
	'meta_description'    => 'CanvaStack Application Website',
	'meta_viewport'       => 'width=device-width, initial-scale=1.0, maximum-scale=1.0',
	'meta_http_equiv'     => [
		'type'            => 'X-UA-Compatible',
		'content'         => 'IE=edge,chrome=1'
	],

	// Table Config
	'canvalib_table'      => [
		'method'          => 'POST'
	],
	
	'user' => [
		'alias_label'     => null
	],
	
	'log_activity'        => [
		'run_status'      => 'unexceptions',
		'exceptions'      => [
			'controllers' => [
				App\Http\Controllers\Admin\System\LogController::class
			],
			'groups'      => [
				'admin'
			]
		]
	],
	
	'email' => [
		'from' => [
			'address' => env('MAIL_FROM_ADDRESS', 'wisnuwidi@canvastack.com'),
			'name'    => env('MAIL_FROM_NAME', 'CanvaStack')
		],
		'cc' => [
			'address' => 'dev@canvastack.com',
			'name'    => 'CanvaStack Developer'
		],
		'feet' => [
			'text'      => 'Best Regards',
			'signature' => 'CanvaStack'
		]
	],
		
	'role' => [
		'group' => [
			'formatIdentity' => [
				'view' => 'group_info|group_alias',
				'separator' => ', '
			]
		]
	],

	/*
	 * Canvastack Table Components - Caching Configuration
	 * =====================================================
	 * Controls caching behaviour for the Canvastack table library.
	 * All TTL values are in seconds.
	 */
	'canvastack_cache' => [

		/*
		 * Master switch. Set to false to disable all Canvastack table caching
		 * (useful during development or debugging).
		 */
		'enabled' => env('CANVASTACK_TABLE_CACHE_ENABLED', true),

		/*
		 * Cache driver to use for table data.
		 * Defaults to the application's default cache driver.
		 * Set to 'array' for request-scoped (in-memory) caching only.
		 */
		'driver' => env('CANVASTACK_TABLE_CACHE_DRIVER', null),

		/*
		 * TTL for table schema cache (column names + types).
		 * Schema rarely changes; a long TTL reduces DB round-trips.
		 * Invalidate manually after migrations:
		 *   canvastack_table_invalidate_schema_cache('table_name');
		 */
		'schema_ttl' => env('CANVASTACK_TABLE_CACHE_SCHEMA_TTL', 21600), // 6 hours

		/*
		 * TTL for table column-list cache (names only).
		 */
		'columns_ttl' => env('CANVASTACK_TABLE_CACHE_COLUMNS_TTL', 21600), // 6 hours

		/*
		 * TTL for table configuration cache (column defs, actions, filters).
		 * Shorter than schema TTL because configs may change more often.
		 */
		'config_ttl' => env('CANVASTACK_TABLE_CACHE_CONFIG_TTL', 1800), // 30 minutes

		/*
		 * TTL for validation result cache (e.g. image file existence checks).
		 * Short TTL because files can be added/removed at any time.
		 */
		'validation_ttl' => env('CANVASTACK_TABLE_CACHE_VALIDATION_TTL', 600), // 10 minutes

		/*
		 * Whitelist of table names allowed for DataTables server-side processing.
		 * Set to null to allow any table that exists in the database.
		 * Example: ['users', 'products', 'orders']
		 */
		'allowed_tables' => null,
	],
];
