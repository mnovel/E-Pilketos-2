<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Attempt login
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        $user = Auth::user();

        // ✅ Voter PENDING / REJECTED tidak boleh login
        if (
            $user->role === UserRole::VOTER
            && $user->status !== VoterStatus::VERIFIED
        ) {
            // ✅ Log SEBELUM logout (masih ada auth)
            ActivityLog::log('auth.login_rejected', [
                'subject_type' => User::class,
                'subject_id'   => $user->id,
                'meta'         => [
                    'email'  => $user->email,
                    'role'   => $user->role->value,
                    'status' => $user->status->value,
                    'reason' => $user->status === VoterStatus::REJECTED
                        ? ($user->alasan_reject ?? 'Tidak ada alasan')
                        : 'Menunggu verifikasi',
                ],
                'user_id' => $user->id,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => match ($user->status) {
                    VoterStatus::PENDING  => 'Akun Anda masih menunggu verifikasi panitia.',
                    VoterStatus::REJECTED => 'Akun Anda ditolak. Alasan: ' . ($user->alasan_reject ?? '-'),
                    default               => 'Akun Anda tidak dapat login.',
                },
            ]);
        }

        $request->session()->regenerate();

        $user->update(['last_login_at' => now()]);

        // ✅ Log login berhasil
        ActivityLog::log('auth.login', [
            'subject_type' => User::class,
            'subject_id'   => $user->id,
            'meta'         => [
                'email' => $user->email,
                'role'  => $user->role->value,
            ],
            'user_id' => $user->id,
        ]);

        return redirect()->intended(match ($user->role) {
            UserRole::ADMIN    => route('admin.dashboard'),
            UserRole::OPERATOR => route('operator.dashboard'),
            UserRole::VOTER    => route('voter.dashboard'),
        });
    }

    public function destroy(Request $request): RedirectResponse
    {
        // ✅ Capture user SEBELUM logout
        $user = Auth::user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ✅ Log logout dengan user_id eksplisit
        if ($user) {
            ActivityLog::log('auth.logout', [
                'subject_type' => User::class,
                'subject_id'   => $user->id,
                'meta'         => [
                    'email' => $user->email,
                    'role'  => $user->role->value,
                ],
                'user_id' => $user->id,
            ]);
        }

        return redirect()->route('login')->with('success', 'Anda berhasil logout.');
    }
}
