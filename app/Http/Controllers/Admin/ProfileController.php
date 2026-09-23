<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Halaman profile.
     */
    public function index(): View
    {
        $user = auth()->user();

        // ✅ Load relasi untuk voter (biar bisa tampilkan kelas & verifier)
        if ($user->isVoter()) {
            $user->load(['classRoom', 'verifier']);
        }

        return view('admin.profile.index', compact('user'));
    }

    /**
     * Update data profile.
     *
     * Hanya boleh update: name & email.
     * Field lain (nis, class_id, status, role) → read-only.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ], [
            'name.required'  => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique'   => 'Email sudah digunakan user lain.',
        ]);

        // ✅ Capture perubahan sebelum update
        $changes = [];
        foreach ($validated as $field => $newValue) {
            $oldValue = $user->$field;

            if ((string) $oldValue !== (string) $newValue) {
                $changes[$field] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        $user->update($validated);

        Log::info("Profile updated: {$user->email}");

        // ✅ Activity Log — hanya kalau ada perubahan
        if (!empty($changes)) {
            ActivityLog::log('profile.updated', [
                'subject_type' => User::class,
                'subject_id'   => $user->id,
                'meta'         => [
                    'changes' => $changes,
                ],
            ]);
        }

        return back()->with('success', 'Data profile berhasil diperbarui.');
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.confirmed'        => 'Konfirmasi password tidak cocok.',
        ]);

        // Cek password lama
        if (!Hash::check($validated['current_password'], auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Password lama salah.']);
        }

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        Log::info("Password changed: " . auth()->user()->email);

        // ✅ Activity Log — JANGAN log password!
        ActivityLog::log('profile.password', [
            'subject_type' => User::class,
            'subject_id'   => auth()->id(),
            'meta'         => [
                'email' => auth()->user()->email,
            ],
        ]);

        return back()->with('success', 'Password berhasil diubah.');
    }
}
