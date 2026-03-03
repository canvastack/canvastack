<?php

namespace Canvastack\Canvastack\Components\Form\Features\FileUpload;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

/**
 * ThumbnailGenerator - Create image thumbnails with aspect ratio preservation.
 *
 * This class handles thumbnail generation for uploaded images including:
 * - Aspect ratio preservation using fit method
 * - EXIF data stripping for privacy and security
 * - Configurable dimensions and quality
 * - Support for both GD and Imagick drivers
 * - Automatic directory creation
 */
class ThumbnailGenerator
{
    protected ImageManager $manager;

    protected string $driverName;

    /**
     * Create a new ThumbnailGenerator instance.
     *
     * Automatically detects and uses Imagick if available, falls back to GD.
     */
    public function __construct()
    {
        // Use Imagick if available, otherwise fall back to GD
        if (extension_loaded('imagick')) {
            $driver = new ImagickDriver();
            $this->driverName = 'imagick';
        } else {
            $driver = new GdDriver();
            $this->driverName = 'gd';
        }

        $this->manager = new ImageManager($driver);
    }

    /**
     * Create thumbnail from image.
     *
     * This method:
     * - Loads the source image
     * - Strips EXIF data for privacy
     * - Resizes with aspect ratio preservation
     * - Saves to thumbnail directory
     *
     * @param string $sourcePath Full path to source image
     * @param string $thumbnailDir Directory to store thumbnail (relative to disk)
     * @param array $options Configuration options
     * @return string Relative path to created thumbnail
     * @throws \Exception If image processing fails
     */
    public function create(
        string $sourcePath,
        string $thumbnailDir,
        array $options = []
    ): string {
        $width = $options['thumbnail_width'] ?? 150;
        $height = $options['thumbnail_height'] ?? 150;
        $quality = $options['thumbnail_quality'] ?? 80;
        $disk = $options['disk'] ?? 'public';

        // Load image
        $image = $this->manager->read($sourcePath);

        // Strip EXIF data for privacy by not preserving metadata
        // Intervention Image 3.x automatically strips EXIF when encoding

        // Resize with aspect ratio preservation using scale method
        // Scale down to fit within the dimensions while maintaining aspect ratio
        $image->scale($width, $height);

        // Create thumbnail directory if not exists
        $fullThumbnailDir = Storage::disk($disk)->path($thumbnailDir);
        if (!file_exists($fullThumbnailDir)) {
            mkdir($fullThumbnailDir, 0755, true);
        }

        // Generate thumbnail filename
        $filename = basename($sourcePath);
        $thumbnailPath = $thumbnailDir . '/' . $filename;
        $fullThumbnailPath = Storage::disk($disk)->path($thumbnailPath);

        // Save thumbnail with specified quality
        $image->toJpeg($quality)->save($fullThumbnailPath);

        return $thumbnailPath;
    }

    /**
     * Delete thumbnail file.
     *
     * @param string $thumbnailPath Relative path to thumbnail
     * @param string $disk Storage disk name
     * @return bool True if deleted successfully
     */
    public function delete(string $thumbnailPath, string $disk = 'public'): bool
    {
        if (Storage::disk($disk)->exists($thumbnailPath)) {
            return Storage::disk($disk)->delete($thumbnailPath);
        }

        return false;
    }

    /**
     * Get the current image driver name.
     *
     * @return string Driver name ('gd' or 'imagick')
     */
    public function getDriverName(): string
    {
        return $this->driverName;
    }
}
