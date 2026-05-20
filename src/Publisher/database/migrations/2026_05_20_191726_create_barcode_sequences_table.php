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
        Schema::create('barcode_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50)->unique()->comment('Entity type: product, customer, etc.');
            $table->string('prefix', 10)->comment('Barcode prefix: PRD, CUST, etc.');
            $table->unsignedInteger('current_value')->default(0)->comment('Current counter value');
            $table->unsignedTinyInteger('padding')->default(7)->comment('Number padding length');
            $table->string('description')->nullable()->comment('Description of this sequence');
            $table->boolean('active')->default(true)->comment('Is this sequence active?');
            $table->timestamps();
            
            // Index for faster lookups
            $table->index('entity_type');
            $table->index('active');
        });
        
        // Seed initial data for products
        DB::table('barcode_sequences')->insert([
            [
                'entity_type' => 'product',
                'prefix' => 'PRD',
                'current_value' => 0,
                'padding' => 10,
                'description' => 'Product barcode sequence',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'entity_type' => 'customer',
                'prefix' => 'CUST',
                'current_value' => 0,
                'padding' => 8,
                'description' => 'Customer barcode sequence',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'entity_type' => 'supplier',
                'prefix' => 'SUPP',
                'current_value' => 0,
                'padding' => 8,
                'description' => 'Supplier barcode sequence',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('barcode_sequences');
    }
};
