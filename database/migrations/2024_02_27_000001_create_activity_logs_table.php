<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create activity_logs table migration.
 *
 * This table stores user activity logs for auditing and monitoring purposes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // User information
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('username')->nullable();
            $table->string('user_fullname')->nullable();
            $table->string('user_email')->nullable();

            // Group/Role information
            $table->unsignedBigInteger('user_group_id')->nullable();
            $table->string('user_group_name')->nullable();
            $table->string('user_group_info')->nullable();

            // Request information
            $table->string('route_path')->nullable();
            $table->string('module_name')->nullable();
            $table->string('page_info')->nullable();
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();

            // Context information
            $table->string('context', 50)->default('admin'); // admin, public, api
            $table->string('action', 100)->nullable(); // create, update, delete, view, login, logout
            $table->string('description')->nullable();

            // Technical information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->text('sql_dump')->nullable();

            // Performance metrics
            $table->unsignedInteger('duration_ms')->nullable(); // Request duration in milliseconds
            $table->unsignedInteger('memory_usage')->nullable(); // Memory usage in bytes

            // Status
            $table->string('status', 20)->default('success'); // success, failed, error

            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('user_group_id');
            $table->index('context');
            $table->index('action');
            $table->index('status');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['context', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
