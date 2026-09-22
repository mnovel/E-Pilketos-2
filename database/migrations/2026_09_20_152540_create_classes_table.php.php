<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('tingkat', 5);
            $table->string('jurusan', 20)->nullable();
            $table->string('rombel', 5)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tingkat');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
