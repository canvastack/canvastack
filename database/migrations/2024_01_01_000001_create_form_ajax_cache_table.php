<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Migration: Create form_ajax_cache table
 *
 * This table stores cached Ajax sync responses for cascading dropdown fields.
 * Caching improves performance by reducing database queries for frequently
 * accessed dropdown options.
 *
 * Cache entries expire after 5 minutes (300 seconds) and are automatically
 * cleaned up by a scheduled job.
 */
return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('form_ajax_cache', function (Blueprint $table) {
            $table->id();

            // Cache key (MD5 hash of query + source value)
            $table->string('cache_key', 255)->unique();

            // Source field information
            $table->string('source_field', 100);
            $table->string('source_value', 255);

            // Cached response data (JSON)
            $table->json('response_data');

            // Expiration timestamp
            $table->timestamp('expires_at');

            // Timestamps
            $table->timestamps();

            // Indexes for performance
            $table->index('cache_key', 'idx_cache_key');
            $table->index('expires_at', 'idx_expires_at');
            $table->index(['source_field', 'source_value'], 'idx_source_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_ajax_cache');
    }
};
