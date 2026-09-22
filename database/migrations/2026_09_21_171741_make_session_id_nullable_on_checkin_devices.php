<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin_devices', function (Blueprint $table) {
            // Drop foreign key dulu
            $table->dropForeign(['session_id']);
        });

        Schema::table('checkin_devices', function (Blueprint $table) {
            // Ubah jadi nullable
            $table->foreignId('session_id')->nullable()->change();

            // Re-add foreign key
            $table->foreign('session_id')
                ->references('id')->on('election_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('checkin_devices', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
        });

        Schema::table('checkin_devices', function (Blueprint $table) {
            $table->foreignId('session_id')->nullable(false)->change();

            $table->foreign('session_id')
                ->references('id')->on('election_sessions')
                ->cascadeOnDelete();
        });
    }
};
