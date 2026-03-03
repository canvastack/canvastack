<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * FileUpload Model.
 *
 * Represents file uploads associated with form fields and models.
 * Tracks metadata about uploaded files including paths, thumbnails, and file information.
 *
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property string $field_name
 * @property string $original_filename
 * @property string $stored_filename
 * @property string $file_path
 * @property string|null $thumbnail_path
 * @property string $mime_type
 * @property int $file_size
 * @property string $disk
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class FileUpload extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'form_file_uploads';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'field_name',
        'original_filename',
        'stored_filename',
        'file_path',
        'thumbnail_path',
        'mime_type',
        'file_size',
        'disk',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'model_id' => 'integer',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the owning model (polymorphic relationship).
     *
     * @return MorphTo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Get files for a specific model.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $modelType
     * @param int $modelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModel($query, string $modelType, int $modelId)
    {
        return $query->where('model_type', $modelType)
                     ->where('model_id', $modelId);
    }

    /**
     * Scope: Get files for a specific field.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $fieldName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForField($query, string $fieldName)
    {
        return $query->where('field_name', $fieldName);
    }

    /**
     * Scope: Get only image files.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope: Get files by MIME type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $mimeType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMimeType($query, string $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * Check if this file is an image.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if this file has a thumbnail.
     *
     * @return bool
     */
    public function hasThumbnail(): bool
    {
        return !empty($this->thumbnail_path);
    }

    /**
     * Get the file size in human-readable format.
     *
     * @return string
     */
    public function getFileSizeHuman(): string
    {
        $bytes = $this->file_size;

        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
    }

    /**
     * Get the full URL to the file.
     *
     * @return string
     */
    public function getFileUrl(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }

    /**
     * Get the full URL to the thumbnail.
     *
     * @return string|null
     */
    public function getThumbnailUrl(): ?string
    {
        if (!$this->hasThumbnail()) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->thumbnail_path);
    }

    /**
     * Check if the file exists on disk.
     *
     * @return bool
     */
    public function fileExists(): bool
    {
        return Storage::disk($this->disk)->exists($this->file_path);
    }

    /**
     * Check if the thumbnail exists on disk.
     *
     * @return bool
     */
    public function thumbnailExists(): bool
    {
        if (!$this->hasThumbnail()) {
            return false;
        }

        return Storage::disk($this->disk)->exists($this->thumbnail_path);
    }

    /**
     * Delete the file from storage.
     *
     * @return bool
     */
    public function deleteFile(): bool
    {
        $deleted = true;

        // Delete main file
        if ($this->fileExists()) {
            $deleted = Storage::disk($this->disk)->delete($this->file_path);
        }

        // Delete thumbnail if exists
        if ($this->hasThumbnail() && $this->thumbnailExists()) {
            $deleted = $deleted && Storage::disk($this->disk)->delete($this->thumbnail_path);
        }

        return $deleted;
    }

    /**
     * Get the file extension.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->original_filename, PATHINFO_EXTENSION);
    }

    /**
     * Get the file name without extension.
     *
     * @return string
     */
    public function getFilenameWithoutExtension(): string
    {
        return pathinfo($this->original_filename, PATHINFO_FILENAME);
    }

    /**
     * Delete all files for a specific model.
     *
     * @param string $modelType
     * @param int $modelId
     * @return int Number of deleted files
     */
    public static function deleteForModel(string $modelType, int $modelId): int
    {
        $files = static::forModel($modelType, $modelId)->get();

        $count = 0;
        foreach ($files as $file) {
            if ($file->deleteFile()) {
                $file->delete();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get upload statistics.
     *
     * @return array
     */
    public static function getStatistics(): array
    {
        return [
            'total_files' => static::count(),
            'total_images' => static::images()->count(),
            'total_size' => static::sum('file_size'),
            'total_size_human' => static::getTotalSizeHuman(),
            'files_with_thumbnails' => static::whereNotNull('thumbnail_path')->count(),
            'oldest_upload' => static::orderBy('created_at', 'asc')->first()?->created_at,
            'newest_upload' => static::orderBy('created_at', 'desc')->first()?->created_at,
        ];
    }

    /**
     * Get total size of all files in human-readable format.
     *
     * @return string
     */
    protected static function getTotalSizeHuman(): string
    {
        $bytes = static::sum('file_size');

        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Automatically delete files from storage when model is force deleted
        static::forceDeleted(function ($fileUpload) {
            $fileUpload->deleteFile();
        });
    }
}
