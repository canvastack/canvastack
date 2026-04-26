<?php
namespace Canvastack\Canvastack\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Canvastack\Canvastack\Models\Admin\System\Preference;

/**
 * Mail Configuration Service
 * 
 * Handles dynamic SMTP configuration from database preferences.
 * Integrates preference-based SMTP settings with Laravel's mail system.
 * 
 * @filesource MailConfigService.php
 * @author wisnuwidi@canvastack.com
 * @copyright wisnuwidi
 * @email wisnuwidi@canvastack.com
 */
class MailConfigService {
	
	/**
	 * Load SMTP configuration from preference and apply to Laravel mail config
	 * 
	 * @return bool True if configuration was loaded successfully
	 */
	public function loadSmtpFromPreference(): bool {
		try {
			// Check if feature is enabled
			if (!config('canvastack.mail.use_preference', true)) {
				return false;
			}
			
			// Get preference data (uses existing cache mechanism)
			$preference = getPreference();
			
			// Check if SMTP settings exist in preference
			if (empty($preference['smtp_host'])) {
				// No SMTP settings in preference, use default from .env
				if (config('canvastack.mail.fallback_to_env', true)) {
					return false; // Let Laravel use default config
				}
			}
			
			// Build SMTP configuration
			$smtpConfig = [
				'transport' => 'smtp',
				'host' => $preference['smtp_host'] ?? env('MAIL_HOST', 'smtp.mailgun.org'),
				'port' => (int) ($preference['smtp_port'] ?? env('MAIL_PORT', 587)),
				'encryption' => $this->normalizeEncryption($preference['smtp_secure'] ?? env('MAIL_ENCRYPTION', 'tls')),
				'username' => $preference['smtp_user'] ?? env('MAIL_USERNAME'),
				'password' => $this->decryptPassword($preference['smtp_password'] ?? env('MAIL_PASSWORD')),
				'timeout' => null,
				'local_domain' => env('MAIL_EHLO_DOMAIN'),
			];
			
			// Apply configuration to Laravel
			Config::set('mail.mailers.smtp', $smtpConfig);
			
			// Update from address if available
			if (!empty($preference['email_address'])) {
				Config::set('mail.from.address', $preference['email_address']);
			}
			if (!empty($preference['email_person'])) {
				Config::set('mail.from.name', $preference['email_person']);
			}
			
			return true;
			
		} catch (\Exception $e) {
			// Log error but don't break application
			Log::error('Failed to load SMTP configuration from preference', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			
			return false;
		}
	}
	
	/**
	 * Encrypt SMTP password before saving to database
	 * 
	 * @param string|null $password Plain text password
	 * @return string|null Encrypted password or null
	 */
	public function encryptPassword(?string $password): ?string {
		if (empty($password)) {
			return null;
		}
		
		// Check if encryption is enabled
		if (!config('canvastack.mail.encrypt_password', true)) {
			return $password;
		}
		
		try {
			return Crypt::encryptString($password);
		} catch (\Exception $e) {
			Log::error('Failed to encrypt SMTP password', [
				'error' => $e->getMessage()
			]);
			return $password;
		}
	}
	
	/**
	 * Decrypt SMTP password from database
	 * 
	 * @param string|null $encrypted Encrypted password
	 * @return string|null Decrypted password or null
	 */
	public function decryptPassword(?string $encrypted): ?string {
		if (empty($encrypted)) {
			return null;
		}
		
		// Check if encryption is enabled
		if (!config('canvastack.mail.encrypt_password', true)) {
			return $encrypted;
		}
		
		try {
			return Crypt::decryptString($encrypted);
		} catch (\Exception $e) {
			// If decryption fails, assume it's plain text (backward compatibility)
			Log::warning('Failed to decrypt SMTP password, using as plain text', [
				'error' => $e->getMessage()
			]);
			return $encrypted;
		}
	}
	
	/**
	 * Normalize encryption type to Laravel standard
	 * 
	 * Converts database integer value to Laravel encryption string.
	 * Database stores: 0=none, 1=tls, 2=ssl
	 * 
	 * @param int|string|null $encryption Encryption type from preference (integer or string)
	 * @return string|null Normalized encryption type (tls, ssl, or null)
	 */
	private function normalizeEncryption(int|string|null $encryption): ?string {
		if ($encryption === null || $encryption === '') {
			return null;
		}
		
		// If already string, map common variations
		if (is_string($encryption)) {
			$encryption = strtolower(trim($encryption));
			$stringMap = [
				'tls' => 'tls',
				'ssl' => 'ssl',
				'starttls' => 'tls',
				'none' => null,
				'no' => null,
				'' => null,
			];
			return $stringMap[$encryption] ?? 'tls';
		}
		
		// Convert integer to encryption string
		// Database mapping: 0=none, 1=tls, 2=ssl
		$intMap = [
			0 => null,  // none
			1 => 'tls', // tls
			2 => 'ssl', // ssl
		];
		
		return $intMap[(int)$encryption] ?? null;
	}
	
	/**
	 * Test SMTP connection with current configuration
	 * 
	 * Actually connects to SMTP server and verifies credentials.
	 * 
	 * @param array|null $config Optional config to test (if null, uses current config)
	 * @return array Result with 'success' boolean and 'message' string
	 */
	public function testConnection(?array $config = null): array {
		$originalConfig = null;
		
		try {
			// If config provided, temporarily apply it
			if ($config !== null) {
				$originalConfig = Config::get('mail.mailers.smtp');
				Config::set('mail.mailers.smtp', array_merge($originalConfig, $config));
			}
			
			// Get transport instance
			$transport = Mail::mailer('smtp')->getSymfonyTransport();
			
			// Actually test the connection by starting it
			// This will throw exception if connection fails
			$transport->start();
			
			// If we got here, connection is successful
			// Stop the connection
			$transport->stop();
			
			// Restore original config if we changed it
			if ($originalConfig !== null) {
				Config::set('mail.mailers.smtp', $originalConfig);
			}
			
			return [
				'success' => true,
				'message' => 'SMTP connection successful. Server is reachable and credentials are valid.'
			];
			
		} catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
			// SMTP-specific errors (connection, authentication, etc.)
			$message = $e->getMessage();
			
			// Make error messages more user-friendly
			if (str_contains($message, 'Username and Password not accepted') || str_contains($message, 'BadCredentials')) {
				$message = 'Authentication failed. Gmail requires an App Password (not your regular password). Please generate an App Password at: https://myaccount.google.com/apppasswords';
			} elseif (str_contains($message, 'Could not authenticate')) {
				$message = 'Authentication failed. Please check your username and password. For Gmail, use App Password instead of regular password.';
			} elseif (str_contains($message, 'Connection could not be established')) {
				$message = 'Could not connect to SMTP server. Please check host and port.';
			} elseif (str_contains($message, 'Connection refused')) {
				$message = 'Connection refused. Please check if the port is correct and not blocked by firewall.';
			} elseif (str_contains($message, 'timed out')) {
				$message = 'Connection timed out. Please check your network connection.';
			}
			
			// Restore original config
			if ($originalConfig !== null) {
				Config::set('mail.mailers.smtp', $originalConfig);
			}
			
			return [
				'success' => false,
				'message' => $message
			];
			
		} catch (\Exception $e) {
			// Generic errors
			// Restore original config
			if ($originalConfig !== null) {
				Config::set('mail.mailers.smtp', $originalConfig);
			}
			
			return [
				'success' => false,
				'message' => 'Connection test failed: ' . $e->getMessage()
			];
		}
	}
	
	/**
	 * Reload mail configuration from preference
	 * Called after preference update to refresh runtime config
	 * 
	 * @return bool True if reload was successful
	 */
	public function reloadConfig(): bool {
		// Invalidate preference cache first
		canvastack_invalidate_preference_cache();
		
		// Reload SMTP configuration
		return $this->loadSmtpFromPreference();
	}
}
