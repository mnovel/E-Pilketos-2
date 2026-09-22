<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()
                ->constrained('classes')->nullOnDelete();
            $table->date('tanggal');
            $table->time('waktu_mulai');
            $table->time('waktu_selesai');
            $table->enum('status', ['scheduled', 'active', 'closed'])
                ->default('scheduled');
            $table->foreignId('operator_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['election_id', 'status']);
            $table->index(['class_id', 'tanggal']);
            $table->unique(['election_id', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_sessions');
    }
};
