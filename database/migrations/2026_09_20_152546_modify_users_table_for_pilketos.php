<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nis', 20)->unique()->nullable()->after('id');
            $table->foreignId('class_id')->nullable()->after('name')
                ->constrained('classes')->nullOnDelete();
            $table->enum('role', ['admin', 'operator', 'voter'])
                ->default('voter')->after('password');
            $table->enum('status', ['pending', 'verified', 'rejected'])
                ->default('pending')->after('role');
            $table->string('kartu_pelajar')->nullable()->after('status');
            $table->foreignId('verified_by')->nullable()->after('kartu_pelajar')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('alasan_reject')->nullable()->after('verified_at');
            $table->timestamp('last_login_at')->nullable()->after('alasan_reject');

            $table->index(['role', 'status']);
            $table->index('class_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'nis',
                'class_id',
                'role',
                'status',
                'kartu_pelajar',
                'verified_by',
                'verified_at',
                'alasan_reject',
                'last_login_at',
            ]);
        });
    }
};
