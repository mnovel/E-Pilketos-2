<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()
                ->constrained('classes')->nullOnDelete();
            $table->unsignedSmallInteger('no_urut');
            $table->string('nama');
            $table->string('foto')->nullable();
            $table->text('visi');
            $table->text('misi');
            $table->text('program_kerja')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['election_id', 'no_urut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
