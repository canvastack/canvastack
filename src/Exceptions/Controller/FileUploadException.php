<?php
namespace Canvastack\Canvastack\Exceptions\Controller;

/**
 * File Upload Exception
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Exception thrown when file upload operations fail.
 * This includes validation failures, storage errors, and processing issues.
 * 
 * @package Canvastack\Canvastack\Exceptions\Controller
 * @category File Upload
 * @version 1.0.0
 * 
 * @security File upload errors may indicate security issues
 *           Monitor for repeated upload failures from same user
 * 
 * @example
 * ```php
 * // In file upload handler
 * if (!$file->isValid()) {
 *     throw new FileUploadException(
 *         'Invalid file upload',
 *         [
 *             'field' => 'avatar',
 *             'error' => $file->getError(),
 *             'size' => $file->getSize(),
 *             'mime' => $file->getMimeType()
 *         ]
 *     );
 * }
 * ```
 */
class FileUploadException extends ControllerException
{
    /**
     * @var string Upload error type
     */
    protected string $uploadErrorType = 'unknown';
    
    /**
     * Constructor
     * 
     * @param string $message Technical error message for logging
     * @param array $context Additional context data including file details
     * @param int $code HTTP status code (default: 422 Unprocessable Entity)
     * @param \Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'File upload failed',
        array $context = [],
        int $code = 422,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->uploadErrorType = $context['error_type'] ?? 'unknown';
        $this->userMessage = 'File upload failed. Please check the file and try again.';
    }
    
    /**
     * Get upload error type
     * 
     * Returns the type of upload error (e.g., 'size', 'type', 'validation', 'storage').
     * 
     * @return string Upload error type
     */
    public function getUploadErrorType(): string
    {
        return $this->uploadErrorType;
    }
    
    /**
     * Get file upload details
     * 
     * Returns detailed information about the failed file upload.
     * 
     * @return array File upload details
     */
    public function getFileDetails(): array
    {
        return [
            'field' => $this->context['field'] ?? 'unknown',
            'filename' => $this->context['filename'] ?? 'unknown',
            'size' => $this->context['size'] ?? 0,
            'mime_type' => $this->context['mime'] ?? 'unknown',
            'extension' => $this->context['extension'] ?? 'unknown',
            'error_type' => $this->uploadErrorType,
            'error_code' => $this->context['error'] ?? null,
        ];
    }
    
    /**
     * Get user-friendly error message based on error type
     * 
     * @return string Contextual user-friendly message
     */
    public function getUserMessage(): string
    {
        switch ($this->uploadErrorType) {
            case 'size':
                $maxSize = $this->context['max_size'] ?? 'unknown';
                return "The file is too large. Maximum allowed size is {$maxSize}.";
                
            case 'type':
                $allowedTypes = $this->context['allowed_types'] ?? 'allowed types';
                return "The file type is not allowed. Please upload: {$allowedTypes}.";
                
            case 'mime':
                return "The file type does not match its extension. Please upload a valid file.";
                
            case 'dimensions':
                $maxWidth = $this->context['max_width'] ?? 'unknown';
                $maxHeight = $this->context['max_height'] ?? 'unknown';
                return "The image dimensions are too large. Maximum allowed: {$maxWidth}x{$maxHeight} pixels.";
                
            case 'storage':
                return "Failed to save the file. Please try again or contact support.";
                
            case 'validation':
                return "The file failed validation checks. Please ensure the file is not corrupted.";
                
            case 'malware':
                return "The file was rejected due to security concerns. Please scan your file for viruses.";
                
            case 'concurrent_limit':
                $maxUploads = $this->context['max_concurrent_uploads'] ?? 'allowed';
                return "Too many uploads in progress. Maximum {$maxUploads} concurrent uploads allowed. Please wait for current uploads to complete.";
                
            case 'timeout':
                return "The upload took too long and was cancelled. Please try uploading a smaller file or check your internet connection.";
                
            default:
                return $this->userMessage;
        }
    }
    
    /**
     * Create exception for file size error
     * 
     * @param string $filename File name
     * @param int $size Actual file size in bytes
     * @param int $maxSize Maximum allowed size in bytes
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function fileTooLarge(
        string $filename,
        int $size,
        int $maxSize,
        array $additionalContext = []
    ): self {
        return new self(
            "File too large: {$filename} ({$size} bytes exceeds {$maxSize} bytes)",
            array_merge([
                'error_type' => 'size',
                'filename' => $filename,
                'size' => $size,
                'max_size' => self::formatBytes($maxSize),
            ], $additionalContext)
        );
    }
    
    /**
     * Create exception for invalid file type
     * 
     * @param string $filename File name
     * @param string $actualType Actual file type
     * @param array $allowedTypes Allowed file types
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function invalidFileType(
        string $filename,
        string $actualType,
        array $allowedTypes,
        array $additionalContext = []
    ): self {
        return new self(
            "Invalid file type: {$filename} ({$actualType} not in allowed types)",
            array_merge([
                'error_type' => 'type',
                'filename' => $filename,
                'mime' => $actualType,
                'allowed_types' => implode(', ', $allowedTypes),
            ], $additionalContext)
        );
    }
    
    /**
     * Create exception for storage failure
     * 
     * @param string $filename File name
     * @param string $reason Failure reason
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function storageFailed(
        string $filename,
        string $reason,
        array $additionalContext = []
    ): self {
        return new self(
            "Storage failed: {$filename} - {$reason}",
            array_merge([
                'error_type' => 'storage',
                'filename' => $filename,
                'reason' => $reason,
            ], $additionalContext)
        );
    }
    
    /**
     * Create exception for invalid image
     * 
     * @param string $filename File name
     * @param string $reason Failure reason
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function invalidImage(
        string $filename,
        string $reason,
        array $additionalContext = []
    ): self {
        return new self(
            "Invalid image: {$filename} - {$reason}",
            array_merge([
                'error_type' => 'validation',
                'filename' => $filename,
                'reason' => $reason,
            ], $additionalContext)
        );
    }
    
    /**
     * Create exception for image dimensions too large
     * 
     * @param string $filename File name
     * @param int $width Actual width
     * @param int $height Actual height
     * @param int|null $maxWidth Maximum allowed width
     * @param int|null $maxHeight Maximum allowed height
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function imageDimensionsTooLarge(
        string $filename,
        int $width,
        int $height,
        ?int $maxWidth,
        ?int $maxHeight,
        array $additionalContext = []
    ): self {
        return new self(
            "Image dimensions too large: {$filename} ({$width}x{$height} exceeds {$maxWidth}x{$maxHeight})",
            array_merge([
                'error_type' => 'dimensions',
                'filename' => $filename,
                'width' => $width,
                'height' => $height,
                'max_width' => $maxWidth,
                'max_height' => $maxHeight,
            ], $additionalContext)
        );
    }
    
    /**
     * Create exception for malicious content detected
     * 
     * @param string $filename File name
     * @param string $reason Detection reason
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function maliciousContentDetected(
        string $filename,
        string $reason,
        array $additionalContext = []
    ): self {
        return new self(
            "Malicious content detected: {$filename} - {$reason}",
            array_merge([
                'error_type' => 'malware',
                'filename' => $filename,
                'reason' => $reason,
            ], $additionalContext),
            403 // Forbidden
        );
    }
    
    /**
     * Create exception for concurrent upload limit reached
     * 
     * @param int $maxConcurrentUploads Maximum allowed concurrent uploads
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function concurrentUploadLimitReached(
        int $maxConcurrentUploads,
        array $additionalContext = []
    ): self {
        return new self(
            "Concurrent upload limit reached: {$maxConcurrentUploads} uploads already in progress",
            array_merge([
                'error_type' => 'concurrent_limit',
                'max_concurrent_uploads' => $maxConcurrentUploads,
            ], $additionalContext),
            429 // Too Many Requests
        );
    }
    
    /**
     * Create exception for upload timeout
     * 
     * @param string $filename File name
     * @param int $elapsedSeconds Elapsed time in seconds
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function uploadTimeout(
        string $filename,
        int $elapsedSeconds,
        array $additionalContext = []
    ): self {
        return new self(
            "Upload timeout: {$filename} - exceeded time limit after {$elapsedSeconds} seconds",
            array_merge([
                'error_type' => 'timeout',
                'filename' => $filename,
                'elapsed_seconds' => $elapsedSeconds,
            ], $additionalContext),
            408 // Request Timeout
        );
    }
    
    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes Bytes
     * @return string Formatted size
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
