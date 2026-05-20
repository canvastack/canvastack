<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Rename table
        Schema::rename('barcode_sequences', 'base_barcode_sequences');
        
        // Add audit columns
        Schema::table('base_barcode_sequences', function (Blueprint $table) {
            $table->timestamp('last_generated_at')->nullable()->after('active');
            $table->unsignedBigInteger('last_generated_by')->nullable()->after('last_generated_at');
            $table->timestamp('last_reset_at')->nullable()->after('last_generated_by');
            $table->unsignedBigInteger('last_reset_by')->nullable()->after('last_reset_at');
            
            // Add indexes
            $table->index('last_generated_at');
            $table->index('last_reset_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove audit columns
        Schema::table('base_barcode_sequences', function (Blueprint $table) {
            $table->dropIndex(['last_generated_at']);
            $table->dropIndex(['last_reset_at']);
            $table->dropColumn(['last_generated_at', 'last_generated_by', 'last_reset_at', 'last_reset_by']);
        });
        
        // Rename back
        Schema::rename('base_barcode_sequences', 'barcode_sequences');
    }
};
