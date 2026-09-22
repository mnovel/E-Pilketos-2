<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()
                ->constrained('election_sessions')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()
                ->constrained('classes')->nullOnDelete();
            $table->string('qr_token', 64)->unique();

            $table->boolean('checked_in')->default(false);
            $table->timestamp('checked_in_at')->nullable();
            $table->boolean('has_voted')->default(false);
            $table->timestamp('voted_at')->nullable();

            $table->timestamps();

            $table->unique(['election_id', 'user_id']);
            $table->index(['election_id', 'class_id']);
            $table->index(['session_id', 'checked_in']);
            $table->index(['session_id', 'has_voted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voters');
    }
};
