<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        $classes = ClassRoom::active()
            ->orderBy('tingkat')
            ->orderBy('name')
            ->get();

        return view('auth.register', compact('classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nis'           => ['required', 'string', 'max:20', 'unique:users,nis'],
            'name'          => ['required', 'string', 'max:255'],
            'class_id'      => ['required', 'exists:classes,id'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
            'kartu_pelajar' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'nis.unique'          => 'NIS sudah terdaftar.',
            'class_id.required'   => 'Kelas wajib dipilih.',
            'class_id.exists'     => 'Kelas tidak valid.',
            'email.unique'        => 'Email sudah terdaftar.',
            'kartu_pelajar.image' => 'File harus berupa gambar.',
            'kartu_pelajar.max'   => 'Ukuran gambar maksimal 2MB.',
        ]);

        // Upload kartu pelajar
        $kartuPath = $request->file('kartu_pelajar')
            ->store('kartu-pelajar', 'public');

        // Simpan user
        $user = User::create([
            'nis'           => $validated['nis'],
            'name'          => $validated['name'],
            'class_id'      => $validated['class_id'],
            'email'         => $validated['email'],
            'password'      => Hash::make($validated['password']),
            'role'          => UserRole::VOTER,
            'status'        => VoterStatus::PENDING,
            'kartu_pelajar' => $kartuPath,
        ]);

        try {
            $user->assignRole('voter');
        } catch (\Throwable $e) {
            Log::warning('Role voter belum ada: ' . $e->getMessage());
        }

        Log::info("Voter registered: {$user->nis} ({$user->email})");

        // ✅ Activity Log — user_id eksplisit (user baru, belum login)
        ActivityLog::log('voter.register', [
            'subject_type' => User::class,
            'subject_id'   => $user->id,
            'meta'         => [
                'nis'          => $user->nis,
                'name'         => $user->name,
                'email'        => $user->email,
                'kelas'        => $user->classRoom?->name ?? '-',
                'class_id'     => $user->class_id,
                'has_kartu'    => (bool) $user->kartu_pelajar,
            ],
            'user_id' => $user->id,
        ]);

        return redirect()
            ->route('register.success')
            ->with('registered_email', $user->email)
            ->with('registered_nis', $user->nis);
    }

    public function success(): View|RedirectResponse
    {
        if (!session('registered_email')) {
            return redirect()->route('register');
        }

        return view('auth.register-success');
    }
}
