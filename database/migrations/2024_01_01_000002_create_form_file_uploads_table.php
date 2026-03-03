<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Migration: Create form_file_uploads table
 *
 * This table tracks file uploads associated with form fields and models.
 * It stores metadata about uploaded files including original filename,
 * stored filename, file paths, thumbnail paths, and file metadata.
 *
 * This enables:
 * - Tracking which files belong to which models
 * - Managing file cleanup when models are deleted
 * - Displaying file history and metadata
 * - Supporting multiple file uploads per field
 */
return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('form_file_uploads', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to owning model
            $table->string('model_type', 255);
            $table->unsignedBigInteger('model_id');

            // Field name this file belongs to
            $table->string('field_name', 100);

            // File information
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('file_path', 500);
            $table->string('thumbnail_path', 500)->nullable();

            // File metadata
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size'); // in bytes

            // Storage information
            $table->string('disk', 50)->default('public');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['model_type', 'model_id'], 'idx_model');
            $table->index('field_name', 'idx_field_name');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_file_uploads');
    }
};
