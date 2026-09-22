<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkin_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()
                ->constrained('election_sessions')->nullOnDelete();
            $table->string('device_label', 50);            // "Pintu-01"
            $table->string('device_token', 16)->unique();
            $table->timestamp('token_expired_at');
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();

            $table->index(['election_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkin_devices');
    }
};
