<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voting_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()
                ->constrained('election_sessions')->nullOnDelete();
            $table->string('device_label', 50);            // "Bilik-01"
            $table->string('device_token', 16)->unique();  // rotate 30s
            $table->timestamp('token_expired_at');
            $table->foreignId('assigned_voter_id')->nullable()
                ->constrained('voters')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->enum('status', ['idle', 'assigned', 'voting', 'done'])
                ->default('idle');
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();

            $table->index(['election_id', 'status']);
            $table->index('device_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_devices');
    }
};
