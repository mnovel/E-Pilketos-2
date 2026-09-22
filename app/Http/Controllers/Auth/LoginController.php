<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
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

        if (
            $user->role === UserRole::VOTER
            && $user->status !== VoterStatus::VERIFIED
        ) {

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

        \App\Models\ActivityLog::log('auth.login', [
            'meta' => ['email' => $user->email, 'role' => $user->role->value],
        ]);

        return redirect()->intended(match ($user->role) {
            UserRole::ADMIN    => route('admin.dashboard'),
            UserRole::OPERATOR => route('operator.dashboard'),
            UserRole::VOTER    => route('voter.dashboard'),
        });
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        \App\Models\ActivityLog::log('auth.logout');

        return redirect()->route('login')->with('success', 'Anda berhasil logout.');
    }
}
