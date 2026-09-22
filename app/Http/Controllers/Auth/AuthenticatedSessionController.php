<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = auth()->user();

        // Voter PENDING / REJECTED tidak boleh login
        if (
            $user->role === UserRole::VOTER
            && $user->status !== VoterStatus::VERIFIED
        ) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => match ($user->status) {
                    VoterStatus::PENDING  => 'Akun Anda masih menunggu verifikasi panitia.',
                    VoterStatus::REJECTED => 'Akun Anda ditolak. Alasan: ' . ($user->alasan_reject ?? '-'),
                    default               => 'Akun Anda tidak dapat login.',
                },
            ]);
        }

        $request->session()->regenerate();

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Redirect sesuai role
        return redirect()->intended(match ($user->role) {
            UserRole::ADMIN    => route('admin.dashboard'),
            UserRole::OPERATOR => route('operator.dashboard'),
            UserRole::VOTER    => route('voter.dashboard'),
        });
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
