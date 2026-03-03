<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('permission_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->enum('rule_type', ['row', 'column', 'json_attribute', 'conditional'])
                ->comment('Type of permission rule');
            $table->json('rule_config')
                ->comment('JSON configuration for the rule');
            $table->integer('priority')
                ->default(0)
                ->comment('Rule evaluation priority (higher = evaluated first)');
            $table->timestamps();

            // Composite index for efficient querying
            $table->index(['permission_id', 'rule_type'], 'idx_permission_rule_type');

            // Additional index on priority for ordering
            $table->index('priority', 'idx_priority');
        });

        // Add foreign key constraint separately to ensure it's created
        Schema::table('permission_rules', function (Blueprint $table) {
            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_rules');
    }
};
