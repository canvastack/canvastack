<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('test_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('test_provinces')->onDelete('cascade');
            $table->string('name');
            $table->string('code', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_cities');
    }
};
