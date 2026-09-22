<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class OperatorController extends Controller
{
    /**
     * List operator.
     */
    public function index(Request $request): View
    {
        $query = User::where('role', UserRole::OPERATOR)
            ->withCount('operatedSessions');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }

        $operators = $query->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $stats = [
            'total'      => User::where('role', UserRole::OPERATOR)->count(),
            'with_session' => User::where('role', UserRole::OPERATOR)
                ->has('operatedSessions')->count(),
            'total_sessions' => \App\Models\ElectionSession::whereNotNull('operator_id')->count(),
        ];

        return view('admin.operators.index', compact('operators', 'stats', 'perPage'));
    }

    /**
     * Form create.
     */
    public function create(): View
    {
        return view('admin.operators.create');
    }

    /**
     * Store operator.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required'   => 'Nama wajib diisi.',
            'email.required'  => 'Email wajib diisi.',
            'email.unique'    => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $operator = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => UserRole::OPERATOR,
            'status'   => VoterStatus::VERIFIED,
            'class_id' => null,
        ]);

        // Assign role Spatie
        try {
            $operator->assignRole('operator');
        } catch (\Throwable $e) {
            Log::warning('Role operator belum ada: ' . $e->getMessage());
        }

        Log::info("Operator created: {$operator->email} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('operator.created', [
            'subject_type' => User::class,
            'subject_id'   => $operator->id,
            'meta'         => [
                'name'  => $operator->name,
                'email' => $operator->email,
            ],
        ]);

        return redirect()
            ->route('admin.operators.index')
            ->with('success', "Operator \"{$operator->name}\" berhasil ditambahkan.");
    }

    /**
     * Show operator.
     */
    public function show(User $operator): View
    {
        abort_if($operator->role !== UserRole::OPERATOR, 404);

        $operator->load(['operatedSessions.election', 'operatedSessions.classRoom']);

        $stats = [
            'total_sessions' => $operator->operatedSessions->count(),
            'active_sessions' => $operator->operatedSessions
                ->filter(fn($s) => $s->status->value === 'active')->count(),
            'closed_sessions' => $operator->operatedSessions
                ->filter(fn($s) => $s->status->value === 'closed')->count(),
            'last_login' => $operator->last_login_at,
        ];

        return view('admin.operators.show', compact('operator', 'stats'));
    }

    /**
     * Form edit.
     */
    public function edit(User $operator): View
    {
        abort_if($operator->role !== UserRole::OPERATOR, 404);

        return view('admin.operators.edit', compact('operator'));
    }

    /**
     * Update operator.
     */
    public function update(Request $request, User $operator): RedirectResponse
    {
        abort_if($operator->role !== UserRole::OPERATOR, 404);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $operator->id],
        ]);

        // ✅ Capture perubahan sebelum update
        $changes = [];
        foreach ($validated as $field => $newValue) {
            $oldValue = $operator->$field;

            if ((string) $oldValue !== (string) $newValue) {
                $changes[$field] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        $operator->update($validated);

        Log::info("Operator updated: {$operator->email} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('operator.updated', [
            'subject_type' => User::class,
            'subject_id'   => $operator->id,
            'meta'         => [
                'name'    => $operator->name,
                'email'   => $operator->email,
                'changes' => $changes,
            ],
        ]);

        return redirect()
            ->route('admin.operators.show', $operator)
            ->with('success', 'Data operator berhasil diperbarui.');
    }

    /**
     * Delete operator.
     */
    public function destroy(User $operator): RedirectResponse
    {
        abort_if($operator->role !== UserRole::OPERATOR, 404);

        // Cek operator punya sesi?
        $sessionCount = $operator->operatedSessions()->count();

        if ($sessionCount > 0) {
            return back()->with(
                'error',
                "Tidak bisa hapus operator \"{$operator->name}\". "
                    . "Operator masih terkait dengan {$sessionCount} sesi voting. "
                    . "Pindahkan operator di sesi-sesi tersebut dulu."
            );
        }

        $name  = $operator->name;
        $email = $operator->email;

        // Hapus role Spatie
        try {
            $operator->removeRole('operator');
        } catch (\Throwable $e) {
            // skip
        }

        $operator->delete();

        Log::warning("Operator deleted: {$name} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('operator.deleted', [
            'subject_type' => User::class,
            'subject_id'   => $operator->id,
            'meta'         => [
                'name'  => $name,
                'email' => $email,
            ],
        ]);

        return redirect()
            ->route('admin.operators.index')
            ->with('success', "Operator \"{$name}\" berhasil dihapus.");
    }

    /**
     * Reset password operator.
     */
    public function resetPassword(Request $request, User $operator): RedirectResponse
    {
        abort_if($operator->role !== UserRole::OPERATOR, 404);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $operator->update([
            'password' => Hash::make($validated['password']),
        ]);

        Log::info("Operator password reset: {$operator->email} by " . auth()->user()->name);

        // ✅ Activity Log — JANGAN log password!
        ActivityLog::log('operator.password_reset', [
            'subject_type' => User::class,
            'subject_id'   => $operator->id,
            'meta'         => [
                'name'  => $operator->name,
                'email' => $operator->email,
            ],
        ]);

        return back()->with('success', 'Password operator berhasil direset.');
    }
}
