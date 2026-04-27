<?php
namespace Canvastack\Canvastack\Controllers\Admin\System;

use Illuminate\Http\Request;

use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Models\Admin\System\Preference;
use Canvastack\Canvastack\Models\Admin\System\Language;
use Canvastack\Canvastack\Models\Admin\System\Timezone;

/**
 * Created on Mar 7, 2018
 * Time Created	: 9:41:31 AM
 * Filename		: PreferenceController.php
 *
 * @filesource	PreferenceController.php
 *
 * @author		wisnuwidi@canvastack.com - 2018
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
class PreferenceController extends Controller {
	public $data;
	
	public function __construct() {
		parent::__construct(Preference::class, 'system.config.preference');
		
		$this->setValidations([
			/* 
			'title'       => 'required|min:5|max:150',
			'sub_title'   => 'required|min:5|max:150',
			 */
			'template'         => 'required',
			'meta_author'      => 'required',
			'logo'             => canvastack_image_validations(5000),
			'login_background' => canvastack_image_validations(10000),
			// SMTP validations
			'smtp_host'        => 'nullable|string|max:255',
			'smtp_port'        => 'nullable|integer|min:1|max:65535',
			'smtp_secure'      => 'nullable|integer|in:0,1,2',
			'smtp_user'        => 'nullable|string|max:255',
			'smtp_password'    => 'nullable|string|max:500',
		]);
	}
	
	public function index() {
		return self::redirect('1/edit');
	}
	
	private function input_language() {
		return canvastack_selectbox(Language::all(), 'abbr', 'language');
	}
	
	private function input_timezone() {
		return canvastack_selectbox(Timezone::all(), 'id', 'timezone');
	}
	
	public function edit($id) {
		$this->setPage();
		$this->removeActionButtons(['add', 'view', 'delete', 'back']);
	
		$this->form->modelWithFile();
		
		$this->form->text('title');
		$this->form->text('sub_title');
		$this->form->file('logo', ['imagepreview']);
		
		$this->form->textarea('header', $this->model_data->header, ['class' => 'text-area-class limit-info', 'maxlength' => 20, 'placeholder' => 'Header']);
		$this->form->textarea('footer', $this->model_data->footer);
		$this->form->selectbox('template', canvastack_detect_templates(), $this->model_data->template);
		$this->form->selectbox('language', $this->input_language(), $this->model_data->language);
		$this->form->selectbox('timezone', $this->input_timezone(), $this->model_data->timezone);
		
		$this->form->openTab('Meta Tag');
		$this->form->text('meta_author', $this->model_data->meta_author, ['required']);
		$this->form->tags('meta_title', $this->model_data->meta_title);
		$this->form->tags('meta_keywords', $this->model_data->meta_keywords);
		$this->form->textarea('meta_description|limit:500', $this->model_data->meta_description);
		
		$this->form->openTab('Email Preference');
		$this->form->text('email_person', $this->model_data->email_person);
		$this->form->text('email_address', $this->model_data->email_address);
		
		$this->form->openTab('SMTP Setting');
		$this->form->text('smtp_host', $this->model_data->smtp_host, ['placeholder' => 'smtp.gmail.com'], 'SMTP Host');
		$this->form->number('smtp_port', $this->model_data->smtp_port, ['placeholder' => '587'], 'SMTP Port');
		$this->form->selectbox('smtp_secure', [
			'' => '-- Select Encryption --',
			'0' => 'None (Not Secure)',
			'1' => 'TLS (Recommended)',
			'2' => 'SSL'
		], $this->model_data->smtp_secure, [], 'SMTP Encryption');
		$this->form->text('smtp_user', $this->model_data->smtp_user, ['placeholder' => 'your-email@example.com'], 'SMTP User');
		$this->form->password('smtp_password', ['placeholder' => 'Leave empty to keep current password'], 'SMTP Password');
		
		// Add SMTP Test Button
		$this->addSmtpTestButton();
		
		$this->form->openTab('Session');
		$this->form->text('session_name', $this->model_data->session_name);
		$this->form->text('session_lifetime', $this->model_data->session_lifetime);
		
		$this->form->openTab('Login Preference');
		$this->form->text('login_title', $this->model_data->login_title);
		$this->form->file('login_background', ['imagepreview']);
		$this->form->number('login_attempts', $this->model_data->login_attempts);
		$this->form->text('change_password', $this->model_data->change_password);
		
		$this->form->openTab('Web Preference');
		$this->form->selectbox('debug', ['No', 'Yes'], $this->model_data->debug);
		$this->form->selectbox('maintenance', ['No', 'Yes'], $this->model_data->maintenance);
		$this->form->closeTab();
		
		$this->form->close('Submit', ['class' => 'btn btn-primary btn-slideright pull-right']);
		
		return $this->render();
	}
	
	public function update(Request $request, $id) {
		// Handle SMTP password encryption
		if ($request->filled('smtp_password')) {
			// Encrypt password if provided
			$request->merge([
				'smtp_password' => canvastack_mail_encrypt_password($request->smtp_password)
			]);
		} else {
			// If password is empty, remove it from request to keep existing password
			$request->offsetUnset('smtp_password');
		}
		
		// Update preference data
		$this->update_data($request, $id, false);
		
		// Reload mail configuration from updated preference
		canvastack_mail_reload_config();
		
		// Optional: Test SMTP connection if enabled
		if (config('canvastack.mail.test_on_save', false) && $request->filled('smtp_host')) {
			$testResult = canvastack_mail_test_smtp();
			if (!$testResult['success']) {
				// Log warning but don't fail the update
				\Log::warning('SMTP connection test failed after preference update', [
					'message' => $testResult['message']
				]);
			}
		}
		
		return self::redirect('edit', $request);
	}
	
	/**
	 * Add SMTP Test Button to Form
	 * 
	 * Injects custom HTML for SMTP connection test button.
	 * Button only appears when all required SMTP fields are filled.
	 * JavaScript logic is handled in firscripts.js
	 * 
	 * @return void
	 */
	private function addSmtpTestButton(): void {
		// Generate proper URL using Laravel route helper
		$testUrl = route('system.config.preference.test-smtp');
		
		$testButtonHtml = <<<HTML
		<div class="form-group row" id="smtp-test-container" style="display:none;" data-test-url="{$testUrl}">
			<label class="col-md-3 control-label"></label>
			<div class="col-md-9">
				<button type="button" id="test-smtp-btn" class="btn btn-info">
					<i class="fa fa-plug"></i> Test SMTP Connection
				</button>
				<span id="smtp-test-result" style="margin-left: 10px;"></span>
				<div id="smtp-test-details" class="alert" style="margin-top: 10px; display:none;"></div>
			</div>
		</div>
		HTML;
		
		$this->form->draw($testButtonHtml);
	}
	
	/**
	 * Test SMTP Connection
	 * 
	 * AJAX endpoint to test SMTP connection with provided credentials.
	 * If password is empty, uses password from database.
	 * 
	 * @param Request $request HTTP request with SMTP credentials
	 * @return \Illuminate\Http\JsonResponse JSON response with test result
	 */
	public function testSmtpConnection(Request $request) {
		try {
			// Validate input
			$request->validate([
				'smtp_host' => 'required|string|max:255',
				'smtp_port' => 'required|integer|min:1|max:65535',
				'smtp_secure' => 'required|integer|in:0,1,2',
				'smtp_user' => 'required|string|max:255',
				'smtp_password' => 'nullable|string',
			]);
			
			// Map encryption integer to string
			$encryption = match((int)$request->smtp_secure) {
				0 => null,
				1 => 'tls',
				2 => 'ssl',
				default => null,
			};
			
			// Get password from request or database
			$password = $request->smtp_password;
			
			// If password is empty, get from database
			if (empty($password)) {
				$preference = Preference::first();
				if ($preference && !empty($preference->smtp_password)) {
					// Decrypt password from database
					$password = canvastack_mail_config_service()->decryptPassword($preference->smtp_password);
				}
			} else {
				// Clean password (remove spaces if any)
				$password = trim(str_replace(' ', '', $password));
			}
			
			// Build test configuration
			$testConfig = [
				'host' => $request->smtp_host,
				'port' => (int) $request->smtp_port,
				'encryption' => $encryption,
				'username' => $request->smtp_user,
				'password' => $password,
			];
			
			// Log test attempt (without password)
			\Log::info('SMTP connection test attempt', [
				'host' => $request->smtp_host,
				'port' => $request->smtp_port,
				'encryption' => $encryption,
				'username' => $request->smtp_user,
				'password_length' => $password ? strlen($password) : 0,
				'password_source' => empty($request->smtp_password) ? 'database' : 'form',
				'user_id' => session('id'),
			]);
			
			// Test connection using helper
			$result = canvastack_mail_test_smtp($testConfig);
			
			// Log test result
			\Log::info('SMTP connection test result', [
				'success' => $result['success'],
				'message' => $result['message'],
				'user_id' => session('id'),
			]);
			
			return response()->json($result);
			
		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
			], 422);
		} catch (\Exception $e) {
			\Log::error('SMTP test connection error', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
				'user_id' => session('id'),
			]);
			
			return response()->json([
				'success' => false,
				'message' => 'Test failed: ' . $e->getMessage()
			], 500);
		}
	}
}