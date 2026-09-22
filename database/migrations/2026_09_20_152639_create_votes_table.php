<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')
                ->constrained('election_sessions')->cascadeOnDelete();
            $table->foreignId('candidate_id')
                ->constrained()->cascadeOnDelete();

            // ⚠️ TIDAK ADA user_id / voter_id!
            // Hanya hash untuk audit internal (opsional)
            $table->string('hash', 64)->unique();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['election_id', 'candidate_id']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
