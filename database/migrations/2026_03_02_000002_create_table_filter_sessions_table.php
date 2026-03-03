<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for table_filter_sessions table.
 * 
 * This table stores filter state for TableBuilder filter functionality.
 * Allows persistence of applied filters across page reloads and sessions.
 * Uses JSON column to store flexible filter data structure.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('table_filter_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 255)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('table_name', 255)->index();
            $table->json('filters');
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
        Schema::dropIfExists('table_filter_sessions');
    }
};
