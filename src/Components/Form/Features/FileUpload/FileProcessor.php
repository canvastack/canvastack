<?php

namespace Canvastack\Canvastack\Components\Form\Features\FileUpload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * FileProcessor - Process file uploads with validation and thumbnail generation.
 *
 * This class handles file upload processing including:
 * - File validation
 * - Unique filename generation
 * - File storage using Laravel Storage
 * - Thumbnail generation for images
 * - Legacy API compatibility
 */
class FileProcessor
{
    protected FileValidator $validator;

    protected ?ThumbnailGenerator $thumbnailGenerator;

    protected array $config;

    /**
     * Create a new FileProcessor instance.
     */
    public function __construct(
        FileValidator $validator,
        ?ThumbnailGenerator $thumbnailGenerator = null,
        array $config = []
    ) {
        $this->validator = $validator;
        $this->thumbnailGenerator = $thumbnailGenerator;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Process uploaded file.
     *
     * @param UploadedFile $file The uploaded file
     * @param string $uploadPath The path to store the file
     * @param array $options Additional options for processing
     * @return array File information array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function process(
        UploadedFile $file,
        string $uploadPath,
        array $options = []
    ): array {
        // Merge options with config
        $options = array_merge($this->config, $options);

        // Validate file
        $this->validator->validate($file, $options);

        // Generate unique filename
        $filename = $this->generateFilename($file);

        // Store file
        $path = $file->storeAs($uploadPath, $filename, $options['disk'] ?? 'public');

        $result = [
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $filename,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];

        // Create thumbnail if image and thumbnail generator is available
        if ($this->thumbnailGenerator && $this->isImage($file) && ($options['thumbnail'] ?? true)) {
            $thumbnailPath = $this->thumbnailGenerator->create(
                Storage::disk($options['disk'] ?? 'public')->path($path),
                $uploadPath . '/thumb',
                $options
            );

            $result['thumbnail_path'] = $thumbnailPath;
        }

        return $result;
    }

    /**
     * Legacy API compatibility method.
     *
     * @param string $uploadPath The path to store the file
     * @param mixed $request The request object containing the file
     * @param array $fileInfo File information including field name
     * @return array File information array
     * @throws \InvalidArgumentException
     */
    public function fileUpload(string $uploadPath, $request, array $fileInfo): array
    {
        $fieldName = $fileInfo['field'] ?? 'file';
        $file = $request->file($fieldName);

        if (!$file) {
            throw new \InvalidArgumentException("No file uploaded for field: {$fieldName}");
        }

        return $this->process($file, $uploadPath, $fileInfo);
    }

    /**
     * Generate unique filename using timestamp and random string.
     *
     * Format: {timestamp}_{random}.{extension}
     *
     * @param UploadedFile $file The uploaded file
     * @return string The generated filename
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = time();
        $random = bin2hex(random_bytes(8));

        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Check if file is an image based on MIME type.
     *
     * @param UploadedFile $file The uploaded file
     * @return bool True if file is an image
     */
    protected function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Get default configuration.
     *
     * @return array Default configuration array
     */
    protected function getDefaultConfig(): array
    {
        return [
            'disk' => 'public',
            'max_size' => 5120, // 5MB in KB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            'thumbnail' => true,
            'thumbnail_width' => 150,
            'thumbnail_height' => 150,
            'thumbnail_quality' => 80,
        ];
    }
}
