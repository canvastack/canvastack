<?php

namespace Canvastack\Canvastack\Components\Form\Features\FileUpload;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * FileValidator - Validate uploaded files.
 *
 * This class handles file validation including:
 * - File size validation
 * - File extension validation
 * - MIME type validation
 * - Security checks
 */
class FileValidator
{
    /**
     * MIME type mapping for common file extensions.
     *
     * @var array<string, array<string>>
     */
    protected array $mimeMap = [
        'jpg' => ['image/jpeg', 'image/jpg'],
        'jpeg' => ['image/jpeg', 'image/jpg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'txt' => ['text/plain'],
        'csv' => ['text/csv', 'text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ];

    /**
     * Validate uploaded file.
     *
     * @param UploadedFile $file The uploaded file
     * @param array $options Validation options
     * @return void
     * @throws ValidationException
     */
    public function validate(UploadedFile $file, array $options): void
    {
        // Check file size
        $this->validateFileSize($file, $options);

        // Check file extension
        $this->validateFileExtension($file, $options);

        // Check MIME type
        $this->validateMimeType($file, $options);
    }

    /**
     * Validate file size against maximum allowed size.
     *
     * @param UploadedFile $file The uploaded file
     * @param array $options Validation options
     * @return void
     * @throws ValidationException
     */
    protected function validateFileSize(UploadedFile $file, array $options): void
    {
        $maxSize = ($options['max_size'] ?? 5120) * 1024; // Convert KB to bytes

        if ($file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 2);
            $fileSizeMB = round($file->getSize() / 1024 / 1024, 2);

            throw ValidationException::withMessages([
                'file' => "File size ({$fileSizeMB}MB) exceeds maximum allowed size of {$maxSizeMB}MB",
            ]);
        }
    }

    /**
     * Validate file extension against allowed types.
     *
     * @param UploadedFile $file The uploaded file
     * @param array $options Validation options
     * @return void
     * @throws ValidationException
     */
    protected function validateFileExtension(UploadedFile $file, array $options): void
    {
        $allowedTypes = $options['allowed_types'] ?? [];

        if (empty($allowedTypes)) {
            return;
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedTypes)) {
            throw ValidationException::withMessages([
                'file' => "File type .{$extension} is not allowed. Allowed types: " . implode(', ', $allowedTypes),
            ]);
        }
    }

    /**
     * Validate MIME type matches extension.
     *
     * This provides an additional security layer to prevent malicious files
     * from being uploaded with fake extensions.
     *
     * @param UploadedFile $file The uploaded file
     * @param array $options Validation options
     * @return void
     * @throws ValidationException
     */
    protected function validateMimeType(UploadedFile $file, array $options): void
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Skip MIME validation if extension is not in our map
        if (!isset($this->mimeMap[$extension])) {
            return;
        }

        $allowedMimeTypes = $this->mimeMap[$extension];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw ValidationException::withMessages([
                'file' => "File MIME type ({$mimeType}) does not match extension (.{$extension}). Possible file type mismatch or security issue.",
            ]);
        }
    }

    /**
     * Get the MIME type map.
     *
     * @return array<string, array<string>>
     */
    public function getMimeMap(): array
    {
        return $this->mimeMap;
    }

    /**
     * Add custom MIME type mapping.
     *
     * @param string $extension File extension
     * @param array $mimeTypes Allowed MIME types for this extension
     * @return void
     */
    public function addMimeMapping(string $extension, array $mimeTypes): void
    {
        $this->mimeMap[strtolower($extension)] = $mimeTypes;
    }
}
