<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Migration: Create translations table
 *
 * This table stores translations for dynamic content (database content).
 * Uses polymorphic relationship to support any model with translatable attributes.
 *
 * Example use cases:
 * - Product names and descriptions in multiple languages
 * - Category names and descriptions
 * - User-generated content translations
 * - Dynamic page content
 *
 * Performance considerations:
 * - Indexed on translatable_type, translatable_id, attribute, and locale
 * - Cached translations expire after 1 hour (configurable)
 * - Supports fallback to default locale
 */
return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to any model
            $table->string('translatable_type', 255);
            $table->unsignedBigInteger('translatable_id');

            // Translation details
            $table->string('attribute', 100); // The attribute being translated (e.g., 'name', 'description')
            $table->string('locale', 10); // Language code (e.g., 'en', 'id', 'es')
            $table->text('value'); // The translated value

            // Timestamps
            $table->timestamps();

            // Indexes for performance
            $table->index(['translatable_type', 'translatable_id'], 'idx_translatable');
            $table->index('locale', 'idx_locale');
            $table->index('attribute', 'idx_attribute');

            // Unique constraint: one translation per model + attribute + locale
            $table->unique(
                ['translatable_type', 'translatable_id', 'attribute', 'locale'],
                'unique_translation'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
