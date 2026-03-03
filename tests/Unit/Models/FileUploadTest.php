<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Models;

use Canvastack\Canvastack\Models\FileUpload;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Unit tests for FileUpload model.
 *
 * Tests model functionality including:
 * - Mass assignment
 * - Relationships
 * - Scopes
 * - Helper methods
 * - File operations
 */
class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users table first (required by migrations)
        \Illuminate\Support\Facades\Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Load and run migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');

        // Setup fake storage
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        // Drop tables in reverse order to avoid foreign key issues
        \Illuminate\Support\Facades\Schema::dropIfExists('form_file_uploads');
        \Illuminate\Support\Facades\Schema::dropIfExists('form_ajax_cache');
        \Illuminate\Support\Facades\Schema::dropIfExists('users');

        parent::tearDown();
    }

    /** @test */
    public function it_can_create_file_upload_record(): void
    {
        $fileUpload = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'thumbnail_path' => 'uploads/thumb/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
            'disk' => 'public',
        ]);

        $this->assertInstanceOf(FileUpload::class, $fileUpload);
        $this->assertEquals('App\\Models\\User', $fileUpload->model_type);
        $this->assertEquals(1, $fileUpload->model_id);
        $this->assertEquals('avatar', $fileUpload->field_name);
        $this->assertEquals('profile.jpg', $fileUpload->original_filename);
    }

    /** @test */
    public function it_casts_attributes_correctly(): void
    {
        $fileUpload = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => '1',
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => '102400',
            'disk' => 'public',
        ]);

        $this->assertIsInt($fileUpload->model_id);
        $this->assertIsInt($fileUpload->file_size);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fileUpload->created_at);
    }

    /** @test */
    public function it_can_scope_by_model(): void
    {
        FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
        ]);

        FileUpload::create([
            'model_type' => 'App\\Models\\Post',
            'model_id' => 2,
            'field_name' => 'image',
            'original_filename' => 'post.jpg',
            'stored_filename' => '9876543210_fedcba.jpg',
            'file_path' => 'uploads/9876543210_fedcba.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 204800,
        ]);

        $userFiles = FileUpload::forModel('App\\Models\\User', 1)->get();
        $this->assertCount(1, $userFiles);
        $this->assertEquals('profile.jpg', $userFiles->first()->original_filename);
    }

    /** @test */
    public function it_can_scope_by_field(): void
    {
        FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
        ]);

        FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'document',
            'original_filename' => 'resume.pdf',
            'stored_filename' => '9876543210_fedcba.pdf',
            'file_path' => 'uploads/9876543210_fedcba.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 204800,
        ]);

        $avatarFiles = FileUpload::forField('avatar')->get();
        $this->assertCount(1, $avatarFiles);
        $this->assertEquals('profile.jpg', $avatarFiles->first()->original_filename);
    }

    /** @test */
    public function it_can_scope_images_only(): void
    {
        FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
        ]);

        FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'document',
            'original_filename' => 'resume.pdf',
            'stored_filename' => '9876543210_fedcba.pdf',
            'file_path' => 'uploads/9876543210_fedcba.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 204800,
        ]);

        $images = FileUpload::images()->get();
        $this->assertCount(1, $images);
        $this->assertEquals('image/jpeg', $images->first()->mime_type);
    }

    /** @test */
    public function it_can_detect_if_file_is_image(): void
    {
        $imageFile = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
        ]);

        $pdfFile = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'document',
            'original_filename' => 'resume.pdf',
            'stored_filename' => '9876543210_fedcba.pdf',
            'file_path' => 'uploads/9876543210_fedcba.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 204800,
        ]);

        $this->assertTrue($imageFile->isImage());
        $this->assertFalse($pdfFile->isImage());
    }

    /** @test */
    public function it_can_detect_if_has_thumbnail(): void
    {
        $withThumbnail = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'thumbnail_path' => 'uploads/thumb/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
        ]);

        $withoutThumbnail = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'document',
            'original_filename' => 'resume.pdf',
            'stored_filename' => '9876543210_fedcba.pdf',
            'file_path' => 'uploads/9876543210_fedcba.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 204800,
        ]);

        $this->assertTrue($withThumbnail->hasThumbnail());
        $this->assertFalse($withoutThumbnail->hasThumbnail());
    }

    /** @test */
    public function it_formats_file_size_in_human_readable_format(): void
    {
        $tests = [
            ['size' => 500, 'expected' => '500 B'],
            ['size' => 1024, 'expected' => '1 KB'],
            ['size' => 1536, 'expected' => '1.5 KB'],
            ['size' => 1048576, 'expected' => '1 MB'],
            ['size' => 1572864, 'expected' => '1.5 MB'],
            ['size' => 1073741824, 'expected' => '1 GB'],
        ];

        foreach ($tests as $test) {
            $fileUpload = FileUpload::create([
                'model_type' => 'App\\Models\\User',
                'model_id' => 1,
                'field_name' => 'file',
                'original_filename' => 'test.jpg',
                'stored_filename' => 'test.jpg',
                'file_path' => 'uploads/test.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => $test['size'],
            ]);

            $this->assertEquals($test['expected'], $fileUpload->getFileSizeHuman());
            $fileUpload->delete();
        }
    }

    /** @test */
    public function it_can_get_file_extension(): void
    {
        $fileUpload = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
        ]);

        $this->assertEquals('jpg', $fileUpload->getExtension());
    }

    /** @test */
    public function it_can_get_filename_without_extension(): void
    {
        $fileUpload = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
        ]);

        $this->assertEquals('profile', $fileUpload->getFilenameWithoutExtension());
    }

    /** @test */
    public function it_can_get_file_url(): void
    {
        $fileUpload = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
            'disk' => 'public',
        ]);

        $url = $fileUpload->getFileUrl();
        $this->assertStringContainsString('uploads/1234567890_abcdef.jpg', $url);
    }

    /** @test */
    public function it_can_get_thumbnail_url(): void
    {
        $fileUpload = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'thumbnail_path' => 'uploads/thumb/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
            'disk' => 'public',
        ]);

        $url = $fileUpload->getThumbnailUrl();
        $this->assertNotNull($url);
        $this->assertStringContainsString('uploads/thumb/1234567890_abcdef.jpg', $url);
    }

    /** @test */
    public function it_returns_null_thumbnail_url_when_no_thumbnail(): void
    {
        $fileUpload = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'document',
            'original_filename' => 'resume.pdf',
            'stored_filename' => '9876543210_fedcba.pdf',
            'file_path' => 'uploads/9876543210_fedcba.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 204800,
        ]);

        $this->assertNull($fileUpload->getThumbnailUrl());
    }

    /** @test */
    public function it_uses_soft_deletes(): void
    {
        $fileUpload = FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
        ]);

        $id = $fileUpload->id;

        // Soft delete
        $fileUpload->delete();

        // Should not be found in normal queries
        $this->assertNull(FileUpload::find($id));

        // Should be found with trashed
        $this->assertNotNull(FileUpload::withTrashed()->find($id));
        $this->assertNotNull($fileUpload->fresh()->deleted_at);
    }

    /** @test */
    public function it_can_get_statistics(): void
    {
        FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'avatar',
            'original_filename' => 'profile.jpg',
            'stored_filename' => '1234567890_abcdef.jpg',
            'file_path' => 'uploads/1234567890_abcdef.jpg',
            'thumbnail_path' => 'uploads/thumb/1234567890_abcdef.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400,
        ]);

        FileUpload::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
            'field_name' => 'document',
            'original_filename' => 'resume.pdf',
            'stored_filename' => '9876543210_fedcba.pdf',
            'file_path' => 'uploads/9876543210_fedcba.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 204800,
        ]);

        $stats = FileUpload::getStatistics();

        $this->assertEquals(2, $stats['total_files']);
        $this->assertEquals(1, $stats['total_images']);
        $this->assertEquals(307200, $stats['total_size']);
        $this->assertEquals(1, $stats['files_with_thumbnails']);
        $this->assertNotNull($stats['oldest_upload']);
        $this->assertNotNull($stats['newest_upload']);
    }
}
