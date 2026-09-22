<?php

use Illuminate\Support\Facades\Route;

// ============================================
// ADMIN CONTROLLERS
// ============================================
use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\ClassRoomController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\ElectionController;
use App\Http\Controllers\Admin\ElectionSessionController;
use App\Http\Controllers\Admin\OperatorController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\VoterController;
use App\Http\Controllers\Admin\VoterImportController;
use App\Http\Controllers\Admin\ActivityLogController;

// ============================================
// AUTH CONTROLLERS
// ============================================
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

// ============================================
// DEVICE CONTROLLERS
// ============================================
use App\Http\Controllers\Device\CheckinDeviceController;
use App\Http\Controllers\Device\VotingDeviceController;

// ============================================
// OPERATOR CONTROLLERS
// ============================================
use App\Http\Controllers\Operator\DashboardController as OperatorDashboard;

// ============================================
// PUBLIC CONTROLLERS
// ============================================
use App\Http\Controllers\Public\CheckStatusController;
use App\Http\Controllers\Public\ResultController as PublicResultController;

// ============================================
// VOTER CONTROLLERS
// ============================================
use App\Http\Controllers\Voter\DashboardController as VoterDashboard;
use App\Http\Controllers\Voter\ScanController;

use App\Http\Controllers\Public\HomeController;

use App\Http\Controllers\Admin\DeviceLogController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (tanpa login)
|--------------------------------------------------------------------------
*/

// Landing page (ganti yang lama)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Cek Status Pendaftaran
Route::prefix('cek-status')->name('cek-status.')->group(function () {
    Route::get('/', [CheckStatusController::class, 'index'])->name('index');
    Route::post('/', [CheckStatusController::class, 'check'])->name('check');
});

// Hasil Pemilihan (publik)
Route::prefix('hasil')->name('hasil.')->group(function () {
    Route::get('/', [PublicResultController::class, 'index'])->name('index');
    Route::get('/{election}', [PublicResultController::class, 'show'])->name('show');
});

// Register Success
Route::get('/register/success', [RegisterController::class, 'success'])
    ->name('register.success');


/*
|--------------------------------------------------------------------------
| GUEST ROUTES (belum login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Register
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    // Login
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});


/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES (semua role)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Logout
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // ==========================================
    // PROFILE (semua role)
    // ==========================================
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });

    // ==========================================
    // ADMIN ONLY
    // ==========================================
    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            // Dashboard
            Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
            Route::get('/dashboard/live-stats', [AdminDashboard::class, 'liveStats'])->name('dashboard.live-stats');

            // ==== Voters ====
            // Import (harus sebelum resource voters/{voter})
            Route::prefix('voters/import')->name('voters.import.')->group(function () {
                Route::get('/', [VoterImportController::class, 'index'])->name('index');
                Route::get('/template', [VoterImportController::class, 'downloadTemplate'])->name('template');
                Route::post('/preview', [VoterImportController::class, 'preview'])->name('preview');
                Route::post('/import', [VoterImportController::class, 'import'])->name('import');
                Route::get('/report', [VoterImportController::class, 'report'])->name('report');
            });

            Route::get('/voters', [VoterController::class, 'index'])->name('voters.index');
            Route::post('/voters/bulk-approve', [VoterController::class, 'bulkApprove'])->name('voters.bulk-approve');
            Route::post('/voters/bulk-reject', [VoterController::class, 'bulkReject'])->name('voters.bulk-reject');
            Route::post('/voters/{voter}/reset-password', [VoterController::class, 'resetPassword'])->name('voters.reset-password');
            Route::post('/voters/{voter}/generate-password', [VoterController::class, 'generatePassword'])->name('voters.generate-password');
            Route::get('/voters/{voter}', [VoterController::class, 'show'])->name('voters.show');
            Route::post('/voters/{voter}/approve', [VoterController::class, 'approve'])->name('voters.approve');
            Route::post('/voters/{voter}/reject', [VoterController::class, 'reject'])->name('voters.reject');

            // ==== Elections ====
            Route::post('elections/{election}/close', [ElectionController::class, 'close'])->name('elections.close');
            Route::post('elections/{election}/publish', [ElectionController::class, 'publish'])->name('elections.publish');

            // Hasil & Export (taruh sebelum resource elections)
            Route::get('elections/{election}/hasil', [ResultController::class, 'show'])->name('elections.hasil');
            Route::get('elections/{election}/export-pdf', [ResultController::class, 'exportPdf'])->name('elections.export-pdf');
            Route::get('elections/{election}/export-excel', [ResultController::class, 'exportExcel'])->name('elections.export-excel');

            Route::resource('elections', ElectionController::class);

            // ==== Candidates ====
            Route::resource('candidates', CandidateController::class);

            // ==== Sessions ====
            Route::post('sessions/{session}/assign-voters', [ElectionSessionController::class, 'assignVoters'])->name('sessions.assign-voters');
            Route::post('sessions/{session}/close', [ElectionSessionController::class, 'close'])->name('sessions.close');
            Route::resource('sessions', ElectionSessionController::class)
                ->parameters(['sessions' => 'session']);

            // ==== Kelas ====
            Route::post('classes/{class}/toggle-active', [ClassRoomController::class, 'toggleActive'])->name('classes.toggle-active');
            Route::resource('classes', ClassRoomController::class);

            // ==== Operators ====
            Route::post('operators/{operator}/reset-password', [OperatorController::class, 'resetPassword'])->name('operators.reset-password');
            Route::resource('operators', OperatorController::class);

            // Activity Log
            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
            Route::get('activity-logs/{log}', [ActivityLogController::class, 'show'])->name('activity-logs.show');

            // ==== Device Logs ====
            Route::prefix('device-logs')->name('device-logs.')->group(function () {
                Route::get('/', [DeviceLogController::class, 'index'])->name('index');
                Route::delete('checkin/{device}', [DeviceLogController::class, 'destroyCheckin'])->name('destroy-checkin');
                Route::delete('voting/{device}', [DeviceLogController::class, 'destroyVoting'])->name('destroy-voting');
                Route::post('purge-offline', [DeviceLogController::class, 'purgeOffline'])->name('purge');
            });
        });

    // ==========================================
    // OPERATOR ONLY
    // ==========================================
    Route::middleware('role:operator')
        ->prefix('operator')
        ->name('operator.')
        ->group(function () {
            Route::get('/dashboard', [OperatorDashboard::class, 'index'])->name('dashboard');
        });

    // ==========================================
    // VOTER ONLY
    // ==========================================
    Route::middleware('role:voter')
        ->prefix('voter')
        ->name('voter.')
        ->group(function () {
            Route::get('/dashboard', [VoterDashboard::class, 'index'])->name('dashboard');
            Route::view('/scan', 'voter.scan.index')->name('scan');
        });

    // ==========================================
    // SCAN DARI HP SISWA — voter only
    // ==========================================
    Route::middleware('role:voter')
        ->prefix('scan')
        ->name('scan.')
        ->group(function () {
            Route::get('/checkin/{token}', [ScanController::class, 'checkin'])->name('checkin');
            Route::get('/voting/{token}', [ScanController::class, 'voting'])->name('voting');
        });

    // ==========================================
    // DEVICE — operator & admin
    // ==========================================
    Route::middleware('role:operator,admin')->group(function () {

        // Device Check-in
        Route::prefix('device/checkin')
            ->name('device.checkin.')
            ->group(function () {
                Route::get('/', [CheckinDeviceController::class, 'index'])->name('index');
                Route::get('/status', [CheckinDeviceController::class, 'status'])->name('status');
                Route::post('/close', [CheckinDeviceController::class, 'close'])->name('close');
                Route::post('/reopen', [CheckinDeviceController::class, 'reopen'])->name('reopen');
            });

        // Device Voting
        Route::prefix('device/voting')
            ->name('device.voting.')
            ->group(function () {
                Route::get('/', [VotingDeviceController::class, 'index'])->name('index');
                Route::get('/status', [VotingDeviceController::class, 'status'])->name('status');
                Route::post('/submit', [VotingDeviceController::class, 'submit'])->name('submit');
                Route::post('/reset', [VotingDeviceController::class, 'reset'])->name('reset');
                Route::post('/close', [VotingDeviceController::class, 'close'])->name('close');
                Route::post('/reopen', [VotingDeviceController::class, 'reopen'])->name('reopen');
            });
    });
});
