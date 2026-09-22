<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ClassRoomController extends Controller
{
    /**
     * List kelas.
     */
    public function index(Request $request): View
    {
        $query = ClassRoom::query()->withCount(['users', 'voters', 'sessions']);

        // Filter tingkat
        $tingkat = $request->input('tingkat', 'all');
        if ($tingkat !== 'all') {
            $query->where('tingkat', $tingkat);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $classes = $query->orderBy('tingkat')
            ->orderBy('name')
            ->get();

        // Stats
        $stats = [
            'total'     => ClassRoom::count(),
            'active'    => ClassRoom::where('is_active', true)->count(),
            'inactive'  => ClassRoom::where('is_active', false)->count(),
            'with_user' => ClassRoom::has('users')->count(),
        ];

        // List tingkat unik (untuk filter dropdown)
        $tingkatList = ClassRoom::select('tingkat')
            ->distinct()
            ->orderBy('tingkat')
            ->pluck('tingkat');

        return view('admin.classes.index', compact('classes', 'stats', 'tingkat', 'tingkatList'));
    }

    /**
     * Form create.
     */
    public function create(): View
    {
        return view('admin.classes.create');
    }

    /**
     * Store kelas.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:50', 'unique:classes,name'],
            'tingkat'   => ['required', 'string', 'max:5'],
            'jurusan'   => ['nullable', 'string', 'max:20'],
            'rombel'    => ['nullable', 'string', 'max:5'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required'    => 'Nama kelas wajib diisi.',
            'name.unique'      => 'Nama kelas sudah ada.',
            'tingkat.required' => 'Tingkat wajib diisi.',
        ]);

        $class = ClassRoom::create([
            'name'      => strtoupper($validated['name']),
            'tingkat'   => strtoupper($validated['tingkat']),
            'jurusan'   => $validated['jurusan'] ? strtoupper($validated['jurusan']) : null,
            'rombel'    => $validated['rombel'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        Log::info("ClassRoom created: {$class->name} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('class.created', [
            'subject_type' => ClassRoom::class,
            'subject_id'   => $class->id,
            'meta'         => [
                'name'      => $class->name,
                'tingkat'   => $class->tingkat,
                'jurusan'   => $class->jurusan,
                'rombel'    => $class->rombel,
                'is_active' => $class->is_active,
            ],
        ]);

        return redirect()
            ->route('admin.classes.index')
            ->with('success', "Kelas \"{$class->name}\" berhasil ditambahkan.");
    }

    /**
     * Show kelas.
     */
    public function show(ClassRoom $class): View
    {
        $class->loadCount(['users', 'voters', 'sessions']);

        $users = User::where('class_id', $class->id)
            ->orderBy('name')
            ->paginate(20);

        return view('admin.classes.show', compact('class', 'users'));
    }

    /**
     * Form edit.
     */
    public function edit(ClassRoom $class): View
    {
        return view('admin.classes.edit', compact('class'));
    }

    /**
     * Update kelas.
     */
    public function update(Request $request, ClassRoom $class): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:50', 'unique:classes,name,' . $class->id],
            'tingkat'   => ['required', 'string', 'max:5'],
            'jurusan'   => ['nullable', 'string', 'max:20'],
            'rombel'    => ['nullable', 'string', 'max:5'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Data baru yang akan disimpan
        $newData = [
            'name'      => strtoupper($validated['name']),
            'tingkat'   => strtoupper($validated['tingkat']),
            'jurusan'   => $validated['jurusan'] ? strtoupper($validated['jurusan']) : null,
            'rombel'    => $validated['rombel'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        // ✅ Capture perubahan sebelum update
        $changes = [];
        foreach ($newData as $field => $newValue) {
            $oldValue = $class->$field;

            if ((string) $oldValue !== (string) $newValue) {
                $changes[$field] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        $class->update($newData);

        Log::info("ClassRoom updated: {$class->name} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('class.updated', [
            'subject_type' => ClassRoom::class,
            'subject_id'   => $class->id,
            'meta'         => [
                'name'    => $class->name,
                'changes' => $changes,
            ],
        ]);

        return redirect()
            ->route('admin.classes.index')
            ->with('success', "Kelas \"{$class->name}\" berhasil diperbarui.");
    }

    /**
     * Delete kelas.
     */
    public function destroy(ClassRoom $class): RedirectResponse
    {
        // Cek: kelas punya user aktif?
        $userCount = User::where('class_id', $class->id)->count();

        if ($userCount > 0) {
            return back()->with(
                'error',
                "Tidak bisa hapus kelas \"{$class->name}\". "
                    . "Masih ada {$userCount} user terdaftar di kelas ini. "
                    . "Pindahkan atau nonaktifkan kelas ini saja."
            );
        }

        $name = $class->name;

        // ✅ Capture metadata sebelum delete
        $meta = [
            'name'      => $class->name,
            'tingkat'   => $class->tingkat,
            'jurusan'   => $class->jurusan,
            'rombel'    => $class->rombel,
            'is_active' => $class->is_active,
        ];

        $class->delete();

        Log::warning("ClassRoom deleted: {$name} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('class.deleted', [
            'subject_type' => ClassRoom::class,
            'subject_id'   => $class->id,
            'meta'         => $meta,
        ]);

        return redirect()
            ->route('admin.classes.index')
            ->with('success', "Kelas \"{$name}\" berhasil dihapus.");
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(ClassRoom $class): RedirectResponse
    {
        $oldStatus = $class->is_active;

        $class->update(['is_active' => !$class->is_active]);

        $status = $class->is_active ? 'diaktifkan' : 'dinonaktifkan';

        Log::info("ClassRoom {$status}: {$class->name} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('class.toggled', [
            'subject_type' => ClassRoom::class,
            'subject_id'   => $class->id,
            'meta'         => [
                'name'   => $class->name,
                'status' => $status,
                'from'   => $oldStatus,
                'to'     => $class->is_active,
            ],
        ]);

        return back()->with('success', "Kelas \"{$class->name}\" berhasil {$status}.");
    }
}
