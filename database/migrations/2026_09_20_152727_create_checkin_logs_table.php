<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')
                ->constrained('election_sessions')->cascadeOnDelete();
            $table->foreignId('voter_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('checkin_device_id')->nullable()
                ->constrained('checkin_devices')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('scanned_at')->useCurrent();

            $table->index(['session_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkin_logs');
    }
};
