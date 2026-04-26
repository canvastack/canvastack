<?php
/**
 * Canvastack Mail Configuration
 * 
 * Configuration for dynamic SMTP settings from database preferences.
 * 
 * @filesource canvastack.mail.php
 * @author wisnuwidi@canvastack.com
 * @copyright wisnuwidi
 * @email wisnuwidi@canvastack.com
 */

return [
	
	/**
	 * Use Preference for SMTP Configuration
	 * 
	 * When enabled, SMTP settings will be loaded from database preferences
	 * instead of .env file. This allows runtime configuration changes.
	 * 
	 * Set to false to always use .env configuration.
	 */
	'use_preference' => env('CANVASTACK_MAIL_USE_PREFERENCE', true),
	
	/**
	 * Fallback to Environment Variables
	 * 
	 * When enabled, if SMTP settings are not found in preferences,
	 * the system will fallback to .env configuration.
	 * 
	 * When disabled, missing preference settings will cause mail to fail.
	 */
	'fallback_to_env' => env('CANVASTACK_MAIL_FALLBACK_TO_ENV', true),
	
	/**
	 * Encrypt SMTP Password
	 * 
	 * When enabled, SMTP passwords are encrypted before storing in database
	 * using Laravel's Crypt facade.
	 * 
	 * Recommended: true for security
	 */
	'encrypt_password' => env('CANVASTACK_MAIL_ENCRYPT_PASSWORD', true),
	
	/**
	 * Test Connection on Save
	 * 
	 * When enabled, SMTP connection will be tested after saving preferences.
	 * If test fails, user will be notified but settings will still be saved.
	 * 
	 * Set to false to skip connection testing (faster but less safe).
	 */
	'test_on_save' => env('CANVASTACK_MAIL_TEST_ON_SAVE', false),
	
	/**
	 * Cache TTL for Mail Configuration
	 * 
	 * Time in seconds to cache mail configuration from preferences.
	 * Uses the same cache mechanism as preference cache.
	 * 
	 * Default: 7200 seconds (2 hours)
	 */
	'cache_ttl' => env('CANVASTACK_MAIL_CACHE_TTL', 7200),
	
];
