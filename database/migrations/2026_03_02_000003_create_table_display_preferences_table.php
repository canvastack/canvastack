<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for table_display_preferences table.
 * 
 * This table stores display preferences for TableBuilder.
 * Allows persistence of display limit (rows per page) across sessions.
 * Supports integer values (10, 25, 50, 100) and special values ('all', '*').
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('table_display_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 255)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('table_name', 255)->index();
            $table->string('display_limit', 10)->default('10');
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            // Composite index for faster lookups
            $table->index(['session_id', 'table_name'], 'idx_session_table');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_display_preferences');
    }
};
