<?php
namespace Canvastack\Canvastack\Controllers\Core\Craft\Includes;

use Canvastack\Canvastack\Library\Constants\ControllerConstants;

// Exception classes for error handling
use Canvastack\Canvastack\Exceptions\Controller\FileUploadException;

/**
 * File Upload Management Trait
 * 
 * First Created on 27 Mar 2021
 * Time Created : 01:43:45
 * 
 * Provides comprehensive file upload functionality for the Canvastack Origin framework.
 * This trait handles file validation, security checks, chunked uploads for large files,
 * thumbnail generation, and memory-efficient file processing.
 * 
 * Core Responsibilities:
 * - File upload validation (extension, size, MIME type)
 * - Security checks (filename sanitization, path traversal prevention)
 * - Chunked upload processing for large files
 * - Automatic thumbnail generation for images
 * - Memory management and optimization
 * - File attribute configuration (validation rules, thumbnails)
 * 
 * Security Features:
 * - File extension whitelist validation
 * - MIME type verification to prevent file type spoofing
 * - Filename sanitization to prevent directory traversal
 * - File size limits to prevent DoS attacks
 * - CSRF protection (inherited from Controller::callAction())
 * - Security event logging for suspicious uploads
 * 
 * Performance Features:
 * - Chunked upload for large files (prevents memory exhaustion)
 * - Memory usage monitoring before upload
 * - Efficient thumbnail generation with memory cleanup
 * - Validation result caching
 * - Configurable chunk sizes and thresholds
 * 
 * Configuration Options:
 * - canvastack.controller.security.allowed_file_extensions: Whitelist of allowed extensions
 * - canvastack.controller.security.max_file_size: Maximum file size in bytes (default: 10MB)
 * - canvastack.controller.security.sanitize_filenames: Enable filename sanitization
 * - canvastack.controller.validation.validate_mime_types: Enable MIME type validation
 * - canvastack.controller.file_upload.enable_chunking: Enable chunked uploads
 * - canvastack.controller.file_upload.chunk_size: Chunk size in bytes (default: 1MB)
 * - canvastack.controller.file_upload.enable_thumbnails: Enable thumbnail generation
 * - canvastack.controller.file_upload.thumbnail_width: Thumbnail width (default: 150px)
 * - canvastack.controller.file_upload.thumbnail_height: Thumbnail height (default: 150px)
 * - canvastack.controller.file_upload.image_quality: JPEG quality (default: 85)
 * - canvastack.controller.performance.memory_limit: PHP memory limit
 * - canvastack.controller.logging.log_file_uploads: Enable upload logging
 * - canvastack.controller.logging.log_security_events: Enable security logging
 * 
 * Usage Example:
 * ```php
 * class ProductController extends Controller {
 *     use FileUpload;
 *     
 *     public function __construct() {
 *         parent::__construct();
 *         
 *         // Configure image upload with thumbnail
 *         $this->setImageElements(
 *             'product_image',  // Field name
 *             5,                // Max size in MB
 *             true,             // Generate thumbnail
 *             [200, 200]        // Thumbnail size
 *         );
 *         
 *         // Configure document upload
 *         $this->setFileElements(
 *             'product_manual',     // Field name
 *             'file',               // Type
 *             'pdf,doc,docx',       // Allowed extensions
 *             10                    // Max size in MB
 *         );
 *         
 *         // Prevent thumbnail from being saved to database
 *         $this->preventInsertDbThumbnail('product_image');
 *     }
 *     
 *     public function store(Request $request) {
 *         // Upload files with validation
 *         $data = $this->uploadFiles('uploads/products', $request);
 *         
 *         // Create product with file paths
 *         Product::create($data);
 *         
 *         return redirect()->route('products.index');
 *     }
 * }
 * ```
 * 
 * Chunked Upload Example:
 * ```php
 * // Large file upload (automatically uses chunking if file > 10MB)
 * $data = $this->uploadFiles('uploads/videos', $request);
 * 
 * // Files are processed in 1MB chunks to prevent memory issues
 * // Thumbnail is generated efficiently for images
 * ```
 * 
 * File Attribute Structure:
 * ```php
 * $this->fileAttributes = [
 *     'product_image' => [
 *         'file_type' => 'image',
 *         'file_validation' => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
 *         'thumb_name' => 'product_image_thumb',
 *         'thumb_size' => [200, 200],
 *     ],
 *     'product_manual' => [
 *         'file_type' => 'file',
 *         'file_validation' => 'file|mimes:pdf,doc,docx|max:10240',
 *     ],
 * ];
 * ```
 * 
 * @package    Canvastack\Canvastack\Controllers\Core\Craft\Includes
 * @category   File Management
 * @author     wisnuwidi@canvastack.com
 * @copyright  2021 Canvastack
 * @license    Proprietary
 * @version    2.0.0
 * @since      1.0.0
 * 
 * @property array $inputFiles Statically defined input file types
 * @property array $fileAttributes File attribute collections (validation, thumbnails)
 * @property array $dropDbThumbnail Files to exclude thumbnail from database
 * 
 * @security   CRITICAL - Validates all file uploads to prevent malicious files
 * @security   Sanitizes filenames to prevent directory traversal attacks
 * @security   Validates MIME types to prevent file type spoofing
 * @security   Enforces file size limits to prevent DoS attacks
 * @security   CSRF protection inherited from Controller::callAction()
 * @security   Logs all security-relevant events for audit trails
 * 
 * @performance Chunked uploads prevent memory exhaustion for large files
 * @performance Memory monitoring before upload to prevent OOM errors
 * @performance Efficient thumbnail generation with immediate cleanup
 * @performance Validation result caching reduces repeated checks
 * @performance Configurable chunk sizes for performance tuning
 * 
 * @see ControllerConstants For file type constants
 * @see Controller For CSRF protection
 * @see canvastack_image_validations() For image validation helper
 * @see canvastack_set_filesize() For file size helper
 * 
 * @filesource FileUpload.php
 */
 
trait FileUpload {
	
	/**
	 * Statically define input file type
	 * @var array
	 */
	protected $inputFiles		= [];
	
	/**
	 * File Attribute Collections
	 */
	protected $fileAttributes	= [];
	
	/**
	 * Active upload sessions for tracking concurrent uploads
	 * @var array
	 */
	private static $activeUploads = [];
	
	/**
	 * Upload progress tracking data
	 * @var array
	 */
	private $uploadProgress = [];
	
	/**
	 * Set Image Validation
	 *
	 * created @Sep 8, 2018
	 * author: wisnuwidi
	 *
	 * @param string $filename Field name
	 * @param int $size File size in MegaByte
	 *
	 * @return void
	 */
	private function setImageValidation(string $filename, int $size = 1): void {
		$this->fileAttributes[$filename]['file_validation'] = canvastack_image_validations(canvastack_set_filesize($size));
	}
	
	/**
	 * Set File Validations
	 * 
	 * @param string $filename Field name
	 * @param string $type File type
	 * @param string|bool $validation Validation rules
	 * @param int $size File size in MegaByte
	 * @return void
	 */
	private function setFileValidation(string $filename, string $type, string|bool $validation = false, int $size = 1): void {
		if (!empty($size)) $max = '|max:' . canvastack_set_filesize($size);
		if (!empty($validation)) $this->fileAttributes[$filename]['file_validation'] = "{$type}|mimes:{$validation}{$max}";
	}
	
	/**
	 * Set File Type
	 * 
	 * @param string $filename Field name
	 * @param string $filetype File type
	 * @return void
	 */
	private function setFileType(string $filename, string $filetype): void {
		$this->fileAttributes[$filename]['file_type'] = $filetype;
	}
	
	/**
	 * Set Image Thumbnail
	 * 
	 * @param string $filename Field name
	 * @param string|bool $thumb Thumbnail name or true for default
	 * @param array $thumb_size Thumbnail size [width, height]
	 * @return void
	 */
	private function setImageThumb(string $filename, string|bool $thumb = false, array $thumb_size = [100, null]): void {
		$thumbName = false;

		if (!empty($thumb)) {
			if (true === $thumb) {
				$thumbName = "{$filename}_thumb";
			} else {
				$thumbName = $thumb;
			}
		} else {
			$thumbName    = "{$filename}_thumb";
		}

		if (!empty($thumbName)) {
			$this->fileAttributes[$filename]['thumb_name'] = $thumbName;
			$this->fileAttributes[$filename]['thumb_size'] = $thumb_size;
		}
	}
	
	/**
	 * Data Image Setted for Prevent Adding Thumbnail To Database
	 * @var array
	 */
	private $dropDbThumbnail = [];
	
	/**
	 * Prevent Inserting Image Thumbnail To The Database
	 * 
	 * @param string $file_target File field name
	 * @return void
	 */
	public function preventInsertDbThumbnail(string $file_target): void {
		$this->dropDbThumbnail[$file_target] = $file_target;
	}
	
	/**
	 * Set Image Elements
	 * 
	 * To set some file elements like file type, image validations, image thumbnail
	 * Set this function in constructor function [__construct()] class
	 * 
	 * @param string $fieldname Field name
	 * @param int $file_max_size Maximum file size in MB
	 * @param string|bool $file_thumb Thumbnail name or true for default
	 * @param array $thumb_size Thumbnail size [width, height]
	 * @return void
	 */
	public function setImageElements(string $fieldname, int $file_max_size = 1, string|bool $file_thumb = false, array $thumb_size = [100, null]): void {
		$this->setFileType($fieldname, ControllerConstants::FILE_TYPE_IMAGE);
		$this->setImageValidation($fieldname, $file_max_size);

		if (!empty($file_thumb)) {
			$this->setImageThumb($fieldname, $file_thumb, $thumb_size);
		}
	}
	
/**
	 * Set File Elements
	 *
	 * To set some file elements like file type and file validations
	 * Set this function in constructor function [__construct()] class
	 *
	 * @param string $fieldname Field name
	 * @param string $type File type
	 * @param string|bool $validation Validation rules
	 * @param int $size Maximum file size in MB
	 * @return void
	 */
	public function setFileElements(string $fieldname, string $type, string|bool $validation = false, int $size = 1): void {
		$this->setFileType($fieldname, $type);
		$this->setFileValidation($fieldname, $type, $validation, $size);
	}
	
	/**
	 * Upload files from request
	 * 
	 * Processes file uploads with validation and security checks.
	 * Supports chunked uploads for large files to prevent memory exhaustion.
	 * 
	 * @param string $upload_path Path to upload directory
	 * @param \Illuminate\Http\Request $request HTTP request with files
	 * @param array $file_data Additional file data configuration
	 * @return array|\Illuminate\Http\RedirectResponse Merged request data with file paths or redirect
	 * 
	 * @security CSRF Protection - Token is verified in Controller::callAction() before this method is called
	 *           All file uploads via POST requests are automatically protected by CSRF verification
	 * @security File Validation - All files are validated for extension, size, and MIME type
	 * @security Filename Sanitization - All filenames are sanitized to prevent directory traversal
	 * @performance Memory Management - Uses chunked uploads for large files to prevent memory exhaustion
	 */
	public function uploadFiles(string $upload_path, $request, array $file_data = []): array|\Illuminate\Http\RedirectResponse {
		\Log::info('FileUpload: Starting upload process', [
			'upload_path' => $upload_path,
			'has_files' => !empty($request->files),
			'file_count' => count($request->files ?? [])
		]);

		// Generate unique session ID for this upload
		$sessionId = uniqid('upload_', true);
		
		try {
			// Check concurrent upload limit
			$this->checkConcurrentUploadLimit();
			
			// Register upload session
			$this->registerUploadSession($sessionId);
			
			// Calculate total files and size for progress tracking
			$totalFiles = 0;
			$totalSize = 0;
			foreach ($request->files as $file) {
				if ($file && $file->isValid()) {
					$totalFiles++;
					$totalSize += $file->getSize();
				}
			}
			
			// Initialize progress tracking
			$this->initializeUploadProgress($sessionId, $totalFiles, $totalSize);
			
			// Check memory before processing
			$this->checkMemoryBeforeUpload($request);

			// Validate all uploaded files before processing
			$this->validateUploadedFiles($request);

			// Check if chunked upload is enabled and needed
			$enableChunking = config('canvastack.controller.file_upload.enable_chunking', true);
			$chunkSize = config('canvastack.controller.file_upload.chunk_size', 1048576); // 1MB default

			// Process chunked uploads if enabled and file is large
			if ($enableChunking && $this->shouldUseChunkedUpload($request, $chunkSize)) {
				\Log::info('FileUpload: Using chunked upload');
				$result = $this->processChunkedUpload($upload_path, $request, $file_data, $sessionId);
				
				// Unregister session on success
				$this->unregisterUploadSession($sessionId);
				
				return $result;
			}

			\Log::info('FileUpload: Using standard upload');

			// Upload file to asset resources folder (standard upload)
			$this->form->fileUpload($upload_path, $request, $file_data);

			\Log::info('FileUpload: After fileUpload call', [
				'getFileUploads_empty' => empty($this->form->getFileUploads),
				'getFileUploads' => $this->form->getFileUploads
			]);

			if (empty($this->form->getFileUploads)) {
				\Log::warning('FileUpload: No files uploaded, redirecting back');
				
				// Unregister session
				$this->unregisterUploadSession($sessionId);
				
				$routeBack = str_replace('.', "/", str_replace('store', 'create', current_route()));
				return redirect($routeBack);
			}

			// Data Insert Collection
			if (is_array($file_data)) {
				$dataFiles = [];
				$dataExceptions = [];
				$uploadedBytes = 0;

				foreach ($this->form->getFileUploads as $file_name => $file_data) {
					$dataExceptions[] = $file_name;
					
					// Update progress
					if (isset($file_data['file'])) {
						$fileSize = @filesize(public_path($file_data['file'])) ?: 0;
						$uploadedBytes += $fileSize;
						$this->updateUploadProgress($sessionId, $file_name, $uploadedBytes);
					}
					
					if (!empty($file_data['thumbnail'])) {

						// Check if any drop filename setted
						$checkDropField = false;
						if (isset($this->dropDbThumbnail[$file_name])) {
							$checkDropField = $this->dropDbThumbnail[$file_name];
						}

						if ($file_name === $checkDropField) {
							// check for unset image thumbnail
							$dataFiles[$file_name] = $file_data['file'];
						} else {
							// insert image file with thumbnail
							$dataFiles[$file_name] = $file_data['file'];
							$dataFiles["{$file_name}_thumb"] = $file_data['thumbnail'];
						}
					} else {
						$dataFiles[$file_name] = $file_data['file'];
					}

					// Free memory after processing each file
					unset($file_data);
				}

				// Merge and return, then free memory
				$result = array_merge_recursive($request->except($dataExceptions), $dataFiles);
				unset($dataFiles, $dataExceptions);

				// Unregister session on success
				$this->unregisterUploadSession($sessionId);

				return $result;
			}
			
			// Unregister session on success
			$this->unregisterUploadSession($sessionId);
			
		} catch (\Exception $e) {
			// Unregister session on error
			$this->unregisterUploadSession($sessionId);
			
			// Re-throw exception
			throw $e;
		}
	}
	
	/**
	 * Validate uploaded files
	 * 
	 * Validates file extensions, MIME types, and file sizes for all uploaded files.
	 * Throws exception if any file fails validation.
	 * 
	 * @param \Illuminate\Http\Request $request The request containing uploaded files
	 * @return bool True if all files are valid
	 * @throws \InvalidArgumentException If any file fails validation
	 * 
	 * @security CRITICAL - Validates file uploads to prevent malicious file uploads
	 */
	private function validateUploadedFiles($request): bool {
		// Get configuration
		$allowedExtensions = config('canvastack.controller.security.allowed_file_extensions', [
			'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'
		]);
		$maxFileSize = config('canvastack.controller.security.max_file_size', 10485760); // 10MB
		$validateMimeTypes = config('canvastack.controller.validation.validate_mime_types', true);
		
		// Check if there are any files to validate
		if (empty($request->files) || !$request->hasFile(array_keys($request->files->all()))) {
			return true;
		}
		
		// Validate each uploaded file
		foreach ($request->files as $fieldName => $file) {
			if (!$file || !$file->isValid()) {
				continue;
			}
			
			// Validate file extension
			$extension = strtolower($file->getClientOriginalExtension());
			if (!in_array($extension, $allowedExtensions)) {
				// Log security event
				if (config('canvastack.controller.logging.log_security_events', true)) {
					\Illuminate\Support\Facades\Log::warning('File Upload Validation Failed: Invalid extension', [
						'field_name' => $fieldName,
						'extension' => $extension,
						'allowed_extensions' => $allowedExtensions,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw FileUploadException::invalidFileType(
					$file->getClientOriginalName(),
					$extension,
					$allowedExtensions,
					[
						'field' => $fieldName,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
			
			// Validate file size
			if ($file->getSize() > $maxFileSize) {
				$maxSizeMB = round($maxFileSize / 1048576, 2);
				$fileSizeMB = round($file->getSize() / 1048576, 2);
				
				// Log security event
				if (config('canvastack.controller.logging.log_security_events', true)) {
					\Illuminate\Support\Facades\Log::warning('File Upload Validation Failed: File too large', [
						'field_name' => $fieldName,
						'file_size' => $file->getSize(),
						'max_size' => $maxFileSize,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw FileUploadException::fileTooLarge(
					$file->getClientOriginalName(),
					$file->getSize(),
					$maxFileSize,
					[
						'field' => $fieldName,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
			
			// Validate MIME type matches extension
			if ($validateMimeTypes) {
				$mimeType = $file->getMimeType();
				$expectedMimeTypes = $this->getExpectedMimeTypes($extension);
				
				if (!empty($expectedMimeTypes) && !in_array($mimeType, $expectedMimeTypes)) {
					// Log security event
					if (config('canvastack.controller.logging.log_security_events', true)) {
						\Illuminate\Support\Facades\Log::warning('File Upload Validation Failed: MIME type mismatch', [
							'field_name' => $fieldName,
							'extension' => $extension,
							'mime_type' => $mimeType,
							'expected_mime_types' => $expectedMimeTypes,
							'user_id' => session('id'),
							'ip_address' => request()->ip(),
						]);
					}
					
					throw FileUploadException::invalidFileType(
						$file->getClientOriginalName(),
						$mimeType,
						$expectedMimeTypes,
						[
							'field' => $fieldName,
							'extension' => $extension,
							'user_id' => session('id'),
							'ip_address' => request()->ip(),
						]
					);
				}
			}
			
			// Validate image dimensions if this is an image file
			if ($this->isImageFile($extension)) {
				$this->validateImageDimensions($file, $fieldName);
			}
			
			// Scan for malicious content
			$this->scanFileForMaliciousContent($file, $fieldName);
			
			// Sanitize filename
			$originalName = $file->getClientOriginalName();
			$sanitizedName = $this->sanitizeFilename($originalName);
			
			// Log successful validation
			if (config('canvastack.controller.logging.log_file_uploads', true)) {
				\Illuminate\Support\Facades\Log::info('File Upload Validated', [
					'field_name' => $fieldName,
					'original_name' => $originalName,
					'sanitized_name' => $sanitizedName,
					'extension' => $extension,
					'size' => $file->getSize(),
					'mime_type' => $file->getMimeType(),
					'user_id' => session('id'),
				]);
			}
		}
		
		return true;
	}
	
	/**
	 * Get expected MIME types for a file extension
	 * 
	 * Returns an array of valid MIME types for the given file extension.
	 * Results are cached to improve performance for repeated validations.
	 * 
	 * @param string $extension File extension
	 * @return array Array of valid MIME types
	 * 
	 * @performance Caches MIME type mappings to reduce repeated lookups
	 *              Cache key format: file_validation_mime_{extension}
	 *              TTL: Configurable via canvastack.controller.caching.file_validation_cache_ttl
	 */
	private function getExpectedMimeTypes(string $extension): array {
		// Check if file validation caching is enabled
		$cacheEnabled = config('canvastack.controller.caching.file_validation_cache_enabled', true);
		$cacheTtl = config('canvastack.controller.caching.file_validation_cache_ttl', 7200);
		
		if ($cacheEnabled) {
			// Try to get from cache
			return canvastack_controller_cache_remember(
				"file_validation_mime_{$extension}",
				function() use ($extension) {
					return $this->getMimeTypeMapping($extension);
				},
				$cacheTtl
			);
		} else {
			// Caching disabled - return directly
			return $this->getMimeTypeMapping($extension);
		}
	}
	
	/**
	 * Get MIME type mapping for a file extension
	 * 
	 * Internal method that contains the actual MIME type mapping logic.
	 * Separated from getExpectedMimeTypes() to allow caching.
	 * 
	 * @param string $extension File extension
	 * @return array Array of valid MIME types
	 */
	private function getMimeTypeMapping(string $extension): array {
		$mimeTypeMap = [
			// Images
			'jpg' => ['image/jpeg', 'image/pjpeg'],
			'jpeg' => ['image/jpeg', 'image/pjpeg'],
			'png' => ['image/png', 'image/x-png'],
			'gif' => ['image/gif'],
			'bmp' => ['image/bmp', 'image/x-bmp', 'image/x-ms-bmp'],
			'webp' => ['image/webp'],
			'svg' => ['image/svg+xml'],
			
			// Documents
			'pdf' => ['application/pdf', 'application/x-pdf'],
			'doc' => ['application/msword'],
			'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			'xls' => ['application/vnd.ms-excel'],
			'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
			'ppt' => ['application/vnd.ms-powerpoint'],
			'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
			
			// Text
			'txt' => ['text/plain'],
			'csv' => ['text/csv', 'text/plain', 'application/csv'],
			
			// Archives
			'zip' => ['application/zip', 'application/x-zip', 'application/x-zip-compressed'],
			'rar' => ['application/x-rar', 'application/x-rar-compressed'],
		];
		
		return $mimeTypeMap[$extension] ?? [];
	}
	
	/**
	 * Sanitize filename
	 * 
	 * Removes dangerous characters from filename to prevent directory traversal
	 * and other file system attacks.
	 * 
	 * @param string $filename Original filename
	 * @return string Sanitized filename
	 * 
	 * @security CRITICAL - Prevents directory traversal and file system attacks
	 */
	private function sanitizeFilename(string $filename): string {
		// Check if sanitization is enabled
		if (!config('canvastack.controller.security.sanitize_filenames', true)) {
			return $filename;
		}
		
		// Remove directory traversal attempts
		$filename = basename($filename);
		
		// Remove null bytes
		$filename = str_replace(chr(0), '', $filename);
		
		// Remove dangerous characters
		$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
		
		// Remove multiple dots (except before extension)
		$parts = explode('.', $filename);
		if (count($parts) > 2) {
			$extension = array_pop($parts);
			$basename = implode('_', $parts);
			$filename = $basename . '.' . $extension;
		}
		
		// Ensure filename is not empty
		if (empty($filename) || $filename === '.') {
			$filename = 'file_' . time();
		}
		
		return $filename;
	}
	
	/**
	 * Invalidate file validation cache
	 * 
	 * Call this method when file validation rules change to ensure
	 * cached validation data is refreshed.
	 * 
	 * @param string|null $extension File extension to invalidate (null = invalidate all)
	 * @return bool True if cache was invalidated successfully
	 * 
	 * @performance Cache invalidation ensures data consistency after validation rule changes
	 * 
	 * @example
	 * // After updating MIME type mappings
	 * $this->invalidateFileValidationCache('jpg');
	 */
	public function invalidateFileValidationCache(?string $extension = null): bool {
		if ($extension === null) {
			// Invalidate all file validation caches
			return canvastack_controller_cache_flush('file_validation_');
		} else {
			// Invalidate specific extension's cache
			return canvastack_controller_cache_forget("file_validation_mime_{$extension}");
		}
	}
	
	/**
	 * Check memory before upload
	 * 
	 * Checks available memory before processing file uploads.
	 * Warns if memory is low and may cause issues.
	 * 
	 * @param \Illuminate\Http\Request $request Request with files
	 * @return void
	 * @throws \RuntimeException If memory is critically low
	 * 
	 * @performance Memory Management - Prevents out-of-memory errors
	 */
	private function checkMemoryBeforeUpload($request): void {
		$memoryLimit = config('canvastack.controller.performance.memory_limit', '256M');
		if ($memoryLimit) {
			// Convert memory limit to bytes
			$limitBytes = $this->convertToBytes($memoryLimit);
			$currentUsage = memory_get_usage(true);
			$availableMemory = $limitBytes - $currentUsage;
			
			// Calculate total upload size
			$totalUploadSize = 0;
			foreach ($request->files as $file) {
				if ($file && $file->isValid()) {
					$totalUploadSize += $file->getSize();
				}
			}
			
			// Check if we have enough memory (need 2x file size for processing)
			$requiredMemory = $totalUploadSize * 2;
			
			if ($availableMemory < $requiredMemory) {
				// Log memory warning
				if (config('canvastack.controller.logging.log_performance_issues', true)) {
					\Illuminate\Support\Facades\Log::warning('Low memory for file upload', [
						'available_memory' => $availableMemory,
						'required_memory' => $requiredMemory,
						'total_upload_size' => $totalUploadSize,
						'current_usage' => $currentUsage,
						'memory_limit' => $limitBytes,
						'user_id' => session('id'),
					]);
				}
				
				// If critically low, throw exception
				if ($availableMemory < ($requiredMemory / 2)) {
					throw new \RuntimeException('Insufficient memory for file upload. Please upload smaller files or increase memory limit.');
				}
			}
		}
	}
	
	/**
	 * Check if chunked upload should be used
	 * 
	 * Determines if chunked upload should be used based on file size.
	 * 
	 * @param \Illuminate\Http\Request $request Request with files
	 * @param int $chunkSize Chunk size in bytes
	 * @return bool True if chunked upload should be used
	 * 
	 * @performance Memory Management - Uses chunking for large files
	 */
	private function shouldUseChunkedUpload($request, int $chunkSize): bool {
		// Check if any file is larger than 10MB (threshold for chunking)
		$chunkThreshold = $chunkSize * 10; // 10MB if chunk size is 1MB
		
		foreach ($request->files as $file) {
			if ($file && $file->isValid() && $file->getSize() > $chunkThreshold) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Check concurrent upload limit
	 * 
	 * Checks if the concurrent upload limit has been reached.
	 * Prevents too many simultaneous uploads from overwhelming the server.
	 * 
	 * @return bool True if upload can proceed
	 * @throws FileUploadException If concurrent upload limit is reached
	 * 
	 * @performance Prevents server overload from too many concurrent uploads
	 * @security Prevents DoS attacks via concurrent upload flooding
	 */
	private function checkConcurrentUploadLimit(): bool {
		$maxConcurrentUploads = config('canvastack.controller.file_upload.max_concurrent_uploads', 5);
		
		// Clean up stale upload sessions (older than 5 minutes)
		$this->cleanupStaleUploadSessions();
		
		// Count active uploads for current user
		$userId = session('id', 'guest');
		$userUploads = 0;
		
		foreach (self::$activeUploads as $sessionId => $uploadData) {
			if ($uploadData['user_id'] === $userId) {
				$userUploads++;
			}
		}
		
		// Check if limit is reached
		if ($userUploads >= $maxConcurrentUploads) {
			// Log concurrent upload limit reached
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::warning('Concurrent upload limit reached', [
					'user_id' => $userId,
					'active_uploads' => $userUploads,
					'max_concurrent_uploads' => $maxConcurrentUploads,
					'ip_address' => request()->ip(),
				]);
			}
			
			throw FileUploadException::concurrentUploadLimitReached(
				$maxConcurrentUploads,
				[
					'user_id' => $userId,
					'active_uploads' => $userUploads,
					'ip_address' => request()->ip(),
				]
			);
		}
		
		return true;
	}
	
	/**
	 * Register upload session
	 * 
	 * Registers a new upload session for concurrent upload tracking.
	 * 
	 * @param string $sessionId Unique session identifier
	 * @return void
	 * 
	 * @performance Tracks concurrent uploads to prevent server overload
	 */
	private function registerUploadSession(string $sessionId): void {
		self::$activeUploads[$sessionId] = [
			'user_id' => session('id', 'guest'),
			'started_at' => time(),
			'ip_address' => request()->ip(),
		];
	}
	
	/**
	 * Unregister upload session
	 * 
	 * Removes an upload session from concurrent upload tracking.
	 * 
	 * @param string $sessionId Unique session identifier
	 * @return void
	 * 
	 * @performance Cleans up completed uploads from tracking
	 */
	private function unregisterUploadSession(string $sessionId): void {
		unset(self::$activeUploads[$sessionId]);
	}
	
	/**
	 * Clean up stale upload sessions
	 * 
	 * Removes upload sessions that have been active for too long.
	 * This prevents memory leaks from abandoned uploads.
	 * 
	 * @return void
	 * 
	 * @performance Prevents memory leaks from abandoned upload sessions
	 */
	private function cleanupStaleUploadSessions(): void {
		$maxAge = 300; // 5 minutes
		$now = time();
		
		foreach (self::$activeUploads as $sessionId => $uploadData) {
			if (($now - $uploadData['started_at']) > $maxAge) {
				unset(self::$activeUploads[$sessionId]);
			}
		}
	}
	
	/**
	 * Initialize upload progress tracking
	 * 
	 * Sets up progress tracking for the current upload session.
	 * 
	 * @param string $sessionId Unique session identifier
	 * @param int $totalFiles Total number of files to upload
	 * @param int $totalSize Total size of all files in bytes
	 * @return void
	 * 
	 * @performance Enables real-time upload progress monitoring
	 */
	private function initializeUploadProgress(string $sessionId, int $totalFiles, int $totalSize): void {
		$this->uploadProgress[$sessionId] = [
			'total_files' => $totalFiles,
			'total_size' => $totalSize,
			'uploaded_files' => 0,
			'uploaded_bytes' => 0,
			'current_file' => null,
			'started_at' => time(),
			'last_update' => time(),
		];
	}
	
	/**
	 * Update upload progress
	 * 
	 * Updates progress tracking for the current upload.
	 * 
	 * @param string $sessionId Unique session identifier
	 * @param string $fileName Current file name
	 * @param int $uploadedBytes Bytes uploaded so far
	 * @return void
	 * 
	 * @performance Provides real-time upload progress updates
	 */
	private function updateUploadProgress(string $sessionId, string $fileName, int $uploadedBytes): void {
		if (isset($this->uploadProgress[$sessionId])) {
			$this->uploadProgress[$sessionId]['current_file'] = $fileName;
			$this->uploadProgress[$sessionId]['uploaded_bytes'] = $uploadedBytes;
			$this->uploadProgress[$sessionId]['last_update'] = time();
			
			// Log progress at intervals (every 10% or every 5 seconds)
			if ($this->shouldLogProgress($sessionId)) {
				$progress = $this->getUploadProgress($sessionId);
				
				if (config('canvastack.controller.logging.log_file_uploads', true)) {
					\Illuminate\Support\Facades\Log::info('Upload progress update', [
						'session_id' => $sessionId,
						'progress_percent' => $progress['percent'],
						'uploaded_files' => $progress['uploaded_files'],
						'total_files' => $progress['total_files'],
						'uploaded_mb' => round($progress['uploaded_bytes'] / 1048576, 2),
						'total_mb' => round($progress['total_bytes'] / 1048576, 2),
						'user_id' => session('id'),
					]);
				}
			}
		}
	}
	
	/**
	 * Check if progress should be logged
	 * 
	 * Determines if progress should be logged based on time and percentage thresholds.
	 * 
	 * @param string $sessionId Unique session identifier
	 * @return bool True if progress should be logged
	 */
	private function shouldLogProgress(string $sessionId): bool {
		if (!isset($this->uploadProgress[$sessionId])) {
			return false;
		}
		
		$progress = $this->uploadProgress[$sessionId];
		$now = time();
		
		// Log every 5 seconds
		if (($now - $progress['last_update']) >= 5) {
			return true;
		}
		
		// Log at 10% intervals
		$percent = ($progress['uploaded_bytes'] / $progress['total_size']) * 100;
		$lastPercent = (($progress['uploaded_bytes'] - 1048576) / $progress['total_size']) * 100; // Approximate last percent
		
		if (floor($percent / 10) > floor($lastPercent / 10)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get upload progress
	 * 
	 * Returns current upload progress information.
	 * 
	 * @param string $sessionId Unique session identifier
	 * @return array Progress information
	 * 
	 * @performance Provides real-time upload progress data
	 */
	public function getUploadProgress(string $sessionId): array {
		if (!isset($this->uploadProgress[$sessionId])) {
			return [
				'percent' => 0,
				'uploaded_files' => 0,
				'total_files' => 0,
				'uploaded_bytes' => 0,
				'total_bytes' => 0,
				'current_file' => null,
				'elapsed_seconds' => 0,
			];
		}
		
		$progress = $this->uploadProgress[$sessionId];
		$percent = $progress['total_size'] > 0 
			? ($progress['uploaded_bytes'] / $progress['total_size']) * 100 
			: 0;
		
		return [
			'percent' => round($percent, 2),
			'uploaded_files' => $progress['uploaded_files'],
			'total_files' => $progress['total_files'],
			'uploaded_bytes' => $progress['uploaded_bytes'],
			'total_bytes' => $progress['total_size'],
			'current_file' => $progress['current_file'],
			'elapsed_seconds' => time() - $progress['started_at'],
		];
	}
	
	/**
	 * Handle upload timeout
	 * 
	 * Handles upload timeout gracefully by cleaning up partial files
	 * and providing user-friendly error message.
	 * 
	 * @param string $sessionId Unique session identifier
	 * @param string $filePath Path to partial file
	 * @return void
	 * @throws FileUploadException Upload timeout exception
	 * 
	 * @performance Graceful degradation - cleans up resources on timeout
	 */
	private function handleUploadTimeout(string $sessionId, string $filePath): void {
		// Clean up partial file
		if (file_exists($filePath)) {
			@unlink($filePath);
		}
		
		// Get progress info for logging
		$progress = $this->getUploadProgress($sessionId);
		
		// Log timeout
		if (config('canvastack.controller.logging.log_performance_issues', true)) {
			\Illuminate\Support\Facades\Log::warning('Upload timeout', [
				'session_id' => $sessionId,
				'file_path' => $filePath,
				'progress_percent' => $progress['percent'],
				'elapsed_seconds' => $progress['elapsed_seconds'],
				'user_id' => session('id'),
				'ip_address' => request()->ip(),
			]);
		}
		
		// Unregister session
		$this->unregisterUploadSession($sessionId);
		
		// Throw user-friendly exception
		throw FileUploadException::uploadTimeout(
			$progress['current_file'] ?? 'unknown',
			$progress['elapsed_seconds'],
			[
				'session_id' => $sessionId,
				'progress_percent' => $progress['percent'],
				'user_id' => session('id'),
				'ip_address' => request()->ip(),
			]
		);
	}
	
	/**
	 * Check upload timeout
	 * 
	 * Checks if upload has exceeded the configured timeout limit.
	 * 
	 * @param string $sessionId Unique session identifier
	 * @return bool True if timeout has not been exceeded
	 * 
	 * @performance Prevents hung uploads from consuming resources
	 */
	private function checkUploadTimeout(string $sessionId): bool {
		if (!isset($this->uploadProgress[$sessionId])) {
			return true;
		}
		
		$maxUploadTime = config('canvastack.controller.file_upload.max_upload_time', 300); // 5 minutes default
		$progress = $this->uploadProgress[$sessionId];
		$elapsed = time() - $progress['started_at'];
		
		return $elapsed < $maxUploadTime;
	}
	
	/**
	 * Process chunked upload
	 * 
	 * Processes large file uploads in chunks to prevent memory exhaustion.
	 * Reads and writes file in small chunks instead of loading entire file into memory.
	 * 
	 * @param string $upload_path Upload directory path
	 * @param \Illuminate\Http\Request $request Request with files
	 * @param array $file_data File configuration data
	 * @return array Merged request data with file paths
	 * 
	 * @performance Memory Management - Processes files in chunks to minimize memory usage
	 */
	/**
	 * Process chunked upload with comprehensive file system error handling
	 * 
	 * Processes large file uploads in chunks to prevent memory exhaustion.
	 * Reads and writes file in small chunks instead of loading entire file into memory.
	 * Includes comprehensive error handling for file system operations with graceful degradation.
	 * 
	 * Error Handling Strategy:
	 * - Directory creation failures: Log error, throw FileUploadException
	 * - File open failures: Log error, clean up partial files, throw FileUploadException
	 * - File write failures: Log error, clean up partial files, throw FileUploadException
	 * - Disk space errors: Log error, clean up partial files, throw FileUploadException
	 * - Permission errors: Log error with detailed context, throw FileUploadException
	 * - All errors logged with user context for debugging
	 * 
	 * @param string $upload_path Upload directory path
	 * @param \Illuminate\Http\Request $request Request with files
	 * @param array $file_data File configuration data
	 * @return array Merged request data with file paths
	 * 
	 * @throws FileUploadException If file system operation fails
	 * 
	 * @performance Memory Management - Processes files in chunks to minimize memory usage
	 */
	private function processChunkedUpload(string $upload_path, $request, array $file_data, string $sessionId): array {
		$chunkSize = config('canvastack.controller.file_upload.chunk_size', 1048576); // 1MB
		$dataFiles = [];
		$dataExceptions = [];
		
		foreach ($request->files as $fieldName => $file) {
			if (!$file || !$file->isValid()) {
				continue;
			}
			
			$dataExceptions[] = $fieldName;
			
			// Generate unique filename
			$originalName = $file->getClientOriginalName();
			$extension = $file->getClientOriginalExtension();
			$uniqueName = time() . '_' . uniqid() . '.' . $extension;
			
			// Create upload directory if it doesn't exist
			$fullUploadPath = public_path($upload_path);
			
			try {
				if (!file_exists($fullUploadPath)) {
					// Attempt to create directory with error handling
					if (!mkdir($fullUploadPath, 0755, true)) {
						throw new FileUploadException(
							"Failed to create upload directory: {$upload_path}",
							500
						);
					}
				}
				
				// Check if directory is writable
				if (!is_writable($fullUploadPath)) {
					throw new FileUploadException(
						"Upload directory is not writable: {$upload_path}",
						500
					);
				}
				
			} catch (\ErrorException $e) {
				// Directory creation error
				\Illuminate\Support\Facades\Log::error('Failed to create upload directory', [
					'upload_path' => $upload_path,
					'full_path' => $fullUploadPath,
					'error_message' => $e->getMessage(),
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]);
				
				throw new FileUploadException(
					'Unable to create upload directory. Please check file system permissions.',
					500,
					$e
				);
			}
			
			$destinationPath = $fullUploadPath . '/' . $uniqueName;
			$sourceHandle = null;
			$destHandle = null;
			$uploadedBytes = 0;
			
			try {
				// Check timeout before starting
				if (!$this->checkUploadTimeout($sessionId)) {
					$this->handleUploadTimeout($sessionId, $destinationPath);
				}
				
				// Process file in chunks with error handling
				$sourceHandle = @fopen($file->getRealPath(), 'rb');
				if ($sourceHandle === false) {
					throw new FileUploadException(
						"Failed to open source file for reading: {$originalName}",
						500
					);
				}
				
				$destHandle = @fopen($destinationPath, 'wb');
				if ($destHandle === false) {
					throw new FileUploadException(
						"Failed to open destination file for writing: {$uniqueName}",
						500
					);
				}
				
				// Read and write in chunks
				while (!feof($sourceHandle)) {
					// Check timeout periodically
					if (!$this->checkUploadTimeout($sessionId)) {
						$this->handleUploadTimeout($sessionId, $destinationPath);
					}
					
					// Read chunk
					$chunk = fread($sourceHandle, $chunkSize);
					if ($chunk === false) {
						throw new FileUploadException(
							"Failed to read chunk from source file: {$originalName}",
							500
						);
					}
					
					// Write chunk
					$bytesWritten = fwrite($destHandle, $chunk);
					if ($bytesWritten === false) {
						throw new FileUploadException(
							"Failed to write chunk to destination file: {$uniqueName}",
							500
						);
					}
					
					// Update progress
					$uploadedBytes += $bytesWritten;
					$this->updateUploadProgress($sessionId, $originalName, $uploadedBytes);
					
					// Free memory immediately
					unset($chunk);
				}
				
				// Close file handles
				if ($sourceHandle) {
					fclose($sourceHandle);
					$sourceHandle = null;
				}
				if ($destHandle) {
					fclose($destHandle);
					$destHandle = null;
				}
				
				// Store file path
				$dataFiles[$fieldName] = $upload_path . '/' . $uniqueName;
				
				// Generate thumbnail if this is an image
				if ($this->isImageFile($extension)) {
					try {
						$thumbPath = $this->generateThumbnailChunked($destinationPath, $upload_path, $uniqueName);
						if ($thumbPath) {
							$dataFiles["{$fieldName}_thumb"] = $thumbPath;
						}
					} catch (\Exception $e) {
						// Thumbnail generation failed - log warning but continue
						\Illuminate\Support\Facades\Log::warning('Thumbnail generation failed', [
							'field_name' => $fieldName,
							'file_path' => $destinationPath,
							'error_message' => $e->getMessage(),
							'user_id' => session('id'),
						]);
						// Continue without thumbnail - not a critical error
					}
				}
				
				// Log successful upload
				if (config('canvastack.controller.logging.log_file_uploads', true)) {
					\Illuminate\Support\Facades\Log::info('Chunked file upload completed', [
						'field_name' => $fieldName,
						'original_name' => $originalName,
						'stored_name' => $uniqueName,
						'file_size' => $file->getSize(),
						'chunk_size' => $chunkSize,
						'user_id' => session('id'),
					]);
				}
				
			} catch (FileUploadException $e) {
				// Clean up file handles
				if ($sourceHandle) {
					@fclose($sourceHandle);
				}
				if ($destHandle) {
					@fclose($destHandle);
				}
				
				// Clean up partial file
				if (file_exists($destinationPath)) {
					@unlink($destinationPath);
				}
				
				// Log error
				\Illuminate\Support\Facades\Log::error('Chunked file upload failed', [
					'field_name' => $fieldName,
					'original_name' => $originalName,
					'destination_path' => $destinationPath,
					'error_message' => $e->getMessage(),
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]);
				
				// Re-throw with user-friendly message
				throw new FileUploadException(
					"File upload failed for '{$originalName}'. Please try again or contact support if the problem persists.",
					500,
					$e
				);
				
			} catch (\Exception $e) {
				// Clean up file handles
				if ($sourceHandle) {
					@fclose($sourceHandle);
				}
				if ($destHandle) {
					@fclose($destHandle);
				}
				
				// Clean up partial file
				if (file_exists($destinationPath)) {
					@unlink($destinationPath);
				}
				
				// Log unexpected error
				\Illuminate\Support\Facades\Log::error('Unexpected error during chunked file upload', [
					'field_name' => $fieldName,
					'original_name' => $originalName,
					'destination_path' => $destinationPath,
					'error_message' => $e->getMessage(),
					'error_type' => get_class($e),
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]);
				
				// Throw user-friendly exception
				throw new FileUploadException(
					"An unexpected error occurred while uploading '{$originalName}'. Please try again.",
					500,
					$e
				);
			}
		}
		
		// Merge and return
		$result = array_merge_recursive($request->except($dataExceptions), $dataFiles);
		
		// Free memory
		unset($dataFiles, $dataExceptions);
		
		return $result;
	}
	
	/**
	 * Check if file is an image
	 * 
	 * @param string $extension File extension
	 * @return bool True if file is an image
	 */
	private function isImageFile(string $extension): bool {
		$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
		return in_array(strtolower($extension), $imageExtensions);
	}
	
	/**
	 * Generate thumbnail using chunked processing
	 * 
	 * Generates image thumbnail with memory-efficient processing.
	 * 
	 * @param string $sourcePath Source image path
	 * @param string $uploadPath Upload directory
	 * @param string $uniqueName Unique filename
	 * @return string|null Thumbnail path or null on failure
	 * 
	 * @performance Memory Management - Uses efficient image processing
	 */
	private function generateThumbnailChunked(string $sourcePath, string $uploadPath, string $uniqueName): ?string {
		if (!config('canvastack.controller.file_upload.enable_thumbnails', true)) {
			return null;
		}
		
		$thumbWidth = config('canvastack.controller.file_upload.thumbnail_width', 150);
		$thumbHeight = config('canvastack.controller.file_upload.thumbnail_height', 150);
		
		try {
			// Get image info without loading entire image
			$imageInfo = getimagesize($sourcePath);
			if (!$imageInfo) {
				return null;
			}
			
			list($width, $height, $type) = $imageInfo;
			
			// Calculate thumbnail dimensions
			$ratio = min($thumbWidth / $width, $thumbHeight / $height);
			$newWidth = (int)($width * $ratio);
			$newHeight = (int)($height * $ratio);
			
			// Create thumbnail
			$thumbnail = imagecreatetruecolor($newWidth, $newHeight);
			
			// Load source image based on type
			switch ($type) {
				case IMAGETYPE_JPEG:
					$source = imagecreatefromjpeg($sourcePath);
					break;
				case IMAGETYPE_PNG:
					$source = imagecreatefrompng($sourcePath);
					// Preserve transparency
					imagealphablending($thumbnail, false);
					imagesavealpha($thumbnail, true);
					break;
				case IMAGETYPE_GIF:
					$source = imagecreatefromgif($sourcePath);
					break;
				default:
					return null;
			}
			
			// Resize
			imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
			
			// Save thumbnail
			$thumbName = 'thumb_' . $uniqueName;
			$thumbPath = public_path($uploadPath) . '/' . $thumbName;
			
			$quality = config('canvastack.controller.file_upload.image_quality', 85);
			
			switch ($type) {
				case IMAGETYPE_JPEG:
					imagejpeg($thumbnail, $thumbPath, $quality);
					break;
				case IMAGETYPE_PNG:
					imagepng($thumbnail, $thumbPath, (int)(9 - ($quality / 10)));
					break;
				case IMAGETYPE_GIF:
					imagegif($thumbnail, $thumbPath);
					break;
			}
			
			// Free memory
			imagedestroy($source);
			imagedestroy($thumbnail);
			
			return $uploadPath . '/' . $thumbName;
			
		} catch (\Exception $e) {
			// Log error
			if (config('canvastack.controller.logging.log_file_uploads', true)) {
				\Illuminate\Support\Facades\Log::error('Failed to generate thumbnail', [
					'source_path' => $sourcePath,
					'error' => $e->getMessage(),
					'user_id' => session('id'),
				]);
			}
			
			return null;
		}
	}
	
	/**
	 * Convert memory string to bytes
	 * 
	 * Converts memory limit string (e.g., "256M") to bytes.
	 * 
	 * @param string $value Memory value (e.g., "256M", "1G")
	 * @return int Memory in bytes
	 */
	private function convertToBytes(string $value): int {
		$value = trim($value);
		$unit = strtoupper(substr($value, -1));
		$number = (int)substr($value, 0, -1);
		
		switch ($unit) {
			case 'G':
				return $number * 1024 * 1024 * 1024;
			case 'M':
				return $number * 1024 * 1024;
			case 'K':
				return $number * 1024;
			default:
				return (int)$value;
		}
	}
	
	/**
	 * Validate image dimensions
	 * 
	 * Validates that uploaded images meet minimum and maximum dimension requirements.
	 * Throws exception if dimensions are invalid.
	 * 
	 * @param \Illuminate\Http\UploadedFile $file The uploaded image file
	 * @param string $fieldName Field name for error reporting
	 * @return bool True if dimensions are valid
	 * @throws FileUploadException If dimensions are invalid
	 * 
	 * @security Validates image dimensions to prevent oversized images
	 * @performance Uses getimagesize() which reads only image headers, not full file
	 */
	private function validateImageDimensions($file, string $fieldName): bool {
		// Check if image dimension validation is enabled
		if (!config('canvastack.controller.validation.validate_image_dimensions', true)) {
			return true;
		}
		
		// Get dimension limits from configuration
		$maxWidth = config('canvastack.controller.file_upload.max_image_width', null);
		$maxHeight = config('canvastack.controller.file_upload.max_image_height', null);
		
		// Skip validation if no limits are configured
		if ($maxWidth === null && $maxHeight === null) {
			return true;
		}
		
		try {
			// Get image dimensions without loading entire image into memory
			$imageInfo = @getimagesize($file->getRealPath());
			
			if ($imageInfo === false) {
				// Not a valid image or cannot read dimensions
				if (config('canvastack.controller.logging.log_security_events', true)) {
					\Illuminate\Support\Facades\Log::warning('Image dimension validation failed: Cannot read image', [
						'field_name' => $fieldName,
						'file_name' => $file->getClientOriginalName(),
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw FileUploadException::invalidImage(
					$file->getClientOriginalName(),
					'Cannot read image dimensions',
					[
						'field' => $fieldName,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
			
			list($width, $height) = $imageInfo;
			
			// Validate maximum width
			if ($maxWidth !== null && $width > $maxWidth) {
				if (config('canvastack.controller.logging.log_security_events', true)) {
					\Illuminate\Support\Facades\Log::warning('Image dimension validation failed: Width exceeds maximum', [
						'field_name' => $fieldName,
						'file_name' => $file->getClientOriginalName(),
						'width' => $width,
						'max_width' => $maxWidth,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw FileUploadException::imageDimensionsTooLarge(
					$file->getClientOriginalName(),
					$width,
					$height,
					$maxWidth,
					$maxHeight,
					[
						'field' => $fieldName,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
			
			// Validate maximum height
			if ($maxHeight !== null && $height > $maxHeight) {
				if (config('canvastack.controller.logging.log_security_events', true)) {
					\Illuminate\Support\Facades\Log::warning('Image dimension validation failed: Height exceeds maximum', [
						'field_name' => $fieldName,
						'file_name' => $file->getClientOriginalName(),
						'height' => $height,
						'max_height' => $maxHeight,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw FileUploadException::imageDimensionsTooLarge(
					$file->getClientOriginalName(),
					$width,
					$height,
					$maxWidth,
					$maxHeight,
					[
						'field' => $fieldName,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
			
			// Log successful validation
			if (config('canvastack.controller.logging.log_file_uploads', true)) {
				\Illuminate\Support\Facades\Log::info('Image dimensions validated', [
					'field_name' => $fieldName,
					'file_name' => $file->getClientOriginalName(),
					'width' => $width,
					'height' => $height,
					'user_id' => session('id'),
				]);
			}
			
			return true;
			
		} catch (FileUploadException $e) {
			// Re-throw FileUploadException
			throw $e;
		} catch (\Exception $e) {
			// Log unexpected error
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::error('Unexpected error during image dimension validation', [
					'field_name' => $fieldName,
					'file_name' => $file->getClientOriginalName(),
					'error_message' => $e->getMessage(),
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]);
			}
			
			throw new FileUploadException(
				"Failed to validate image dimensions for '{$file->getClientOriginalName()}'",
				500,
				$e
			);
		}
	}
	
	/**
	 * Scan file for malicious content
	 * 
	 * Performs basic content scanning to detect potentially malicious files.
	 * This includes checking for:
	 * - PHP code in image files
	 * - Executable content in documents
	 * - Suspicious file signatures
	 * - Embedded scripts
	 * 
	 * Note: This is a basic implementation. For production environments,
	 * consider integrating with professional antivirus solutions like ClamAV.
	 * 
	 * @param \Illuminate\Http\UploadedFile $file The uploaded file
	 * @param string $fieldName Field name for error reporting
	 * @return bool True if file appears safe
	 * @throws FileUploadException If malicious content is detected
	 * 
	 * @security CRITICAL - Scans for malicious content in uploaded files
	 * @performance Reads only first 8KB of file for performance
	 */
	private function scanFileForMaliciousContent($file, string $fieldName): bool {
		// Check if malicious content scanning is enabled
		if (!config('canvastack.controller.security.scan_malicious_content', true)) {
			return true;
		}
		
		try {
			// Read first 8KB of file for scanning (enough to detect most threats)
			$handle = @fopen($file->getRealPath(), 'rb');
			if ($handle === false) {
				// Cannot open file for scanning - log warning but allow upload
				if (config('canvastack.controller.logging.log_security_events', true)) {
					\Illuminate\Support\Facades\Log::warning('Cannot open file for malicious content scanning', [
						'field_name' => $fieldName,
						'file_name' => $file->getClientOriginalName(),
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				return true;
			}
			
			$content = fread($handle, 8192); // Read first 8KB
			fclose($handle);
			
			if ($content === false) {
				// Cannot read file - log warning but allow upload
				if (config('canvastack.controller.logging.log_security_events', true)) {
					\Illuminate\Support\Facades\Log::warning('Cannot read file for malicious content scanning', [
						'field_name' => $fieldName,
						'file_name' => $file->getClientOriginalName(),
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				return true;
			}
			
			// Define suspicious patterns
			$suspiciousPatterns = [
				'/<\?php/i',                    // PHP opening tag
				'/<script/i',                   // Script tags
				'/eval\s*\(/i',                 // eval() function
				'/base64_decode\s*\(/i',        // base64_decode() function
				'/system\s*\(/i',               // system() function
				'/exec\s*\(/i',                 // exec() function
				'/shell_exec\s*\(/i',           // shell_exec() function
				'/passthru\s*\(/i',             // passthru() function
				'/proc_open\s*\(/i',            // proc_open() function
				'/popen\s*\(/i',                // popen() function
				'/curl_exec\s*\(/i',            // curl_exec() function
				'/curl_multi_exec\s*\(/i',      // curl_multi_exec() function
				'/parse_ini_file\s*\(/i',       // parse_ini_file() function
				'/show_source\s*\(/i',          // show_source() function
			];
			
			// Check for suspicious patterns
			foreach ($suspiciousPatterns as $pattern) {
				if (preg_match($pattern, $content)) {
					// Malicious content detected
					if (config('canvastack.controller.logging.log_security_events', true)) {
						\Illuminate\Support\Facades\Log::warning('Malicious content detected in uploaded file', [
							'field_name' => $fieldName,
							'file_name' => $file->getClientOriginalName(),
							'pattern' => $pattern,
							'user_id' => session('id'),
							'ip_address' => request()->ip(),
						]);
					}
					
					throw FileUploadException::maliciousContentDetected(
						$file->getClientOriginalName(),
						'Suspicious content pattern detected',
						[
							'field' => $fieldName,
							'pattern' => $pattern,
							'user_id' => session('id'),
							'ip_address' => request()->ip(),
						]
					);
				}
			}
			
			// Check for null bytes (often used in file upload attacks)
			// BUT: Skip this check for binary files (images, etc.) as they legitimately contain null bytes
			$extension = strtolower($file->getClientOriginalExtension());
			$binaryExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 
			                     'pdf', 'zip', 'rar', '7z', 'tar', 'gz',
			                     'mp3', 'mp4', 'avi', 'mov', 'wmv', 'flv',
			                     'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
			
			$isBinaryFile = in_array($extension, $binaryExtensions);
			
			if (!$isBinaryFile && strpos($content, "\0") !== false) {
				if (config('canvastack.controller.logging.log_security_events', true)) {
					\Illuminate\Support\Facades\Log::warning('Null byte detected in uploaded file', [
						'field_name' => $fieldName,
						'file_name' => $file->getClientOriginalName(),
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw FileUploadException::maliciousContentDetected(
					$file->getClientOriginalName(),
					'Null byte detected in file content',
					[
						'field' => $fieldName,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
			
			// Log successful scan
			if (config('canvastack.controller.logging.log_file_uploads', true)) {
				\Illuminate\Support\Facades\Log::info('File scanned for malicious content - clean', [
					'field_name' => $fieldName,
					'file_name' => $file->getClientOriginalName(),
					'bytes_scanned' => strlen($content),
					'user_id' => session('id'),
				]);
			}
			
			return true;
			
		} catch (FileUploadException $e) {
			// Re-throw FileUploadException
			throw $e;
		} catch (\Exception $e) {
			// Log unexpected error but allow upload (fail open for availability)
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::error('Unexpected error during malicious content scanning', [
					'field_name' => $fieldName,
					'file_name' => $file->getClientOriginalName(),
					'error_message' => $e->getMessage(),
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]);
			}
			
			// Fail open - allow upload but log the error
			return true;
		}
	}
}