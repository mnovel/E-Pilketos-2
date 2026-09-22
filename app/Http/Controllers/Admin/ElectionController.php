<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Election;
use App\Models\ElectionSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ElectionController extends Controller
{
    /**
     * List semua election.
     */
    public function index(Request $request): View
    {
        // ✅ AUTO-ACTIVATE: draft → active kalau waktunya tiba
        Election::where('status', ElectionStatus::DRAFT)
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->get()
            ->each(fn($e) => $e->autoActivateIfReady());

        // ✅ AUTO-CLOSE: active/draft → closed kalau end_at lewat
        Election::whereIn('status', [ElectionStatus::ACTIVE, ElectionStatus::DRAFT])
            ->where('end_at', '<', now())
            ->get()
            ->each(fn($e) => $e->autoCloseIfEnded());

        $query = Election::with('creator')->withCount(['candidates', 'voters', 'votes']);

        $status = $request->input('status', 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('tahun_ajaran', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 20, 50])) {
            $perPage = 10;
        }

        $elections = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $counts = [
            'all'       => Election::count(),
            'draft'     => Election::where('status', ElectionStatus::DRAFT)->count(),
            'active'    => Election::where('status', ElectionStatus::ACTIVE)->count(),
            'closed'    => Election::where('status', ElectionStatus::CLOSED)->count(),
            'published' => Election::where('status', ElectionStatus::PUBLISHED)->count(),
        ];

        return view('admin.elections.index', compact('elections', 'counts', 'status', 'perPage'));
    }

    /**
     * Form create.
     */
    public function create(): View
    {
        return view('admin.elections.create');
    }

    /**
     * Simpan election baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'deskripsi'    => ['nullable', 'string', 'max:1000'],
            'start_at'     => ['required', 'date', 'after:now'],
            'end_at'       => ['required', 'date', 'after:start_at'],
        ], [
            'title.required'        => 'Judul pemilihan wajib diisi.',
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi.',
            'start_at.required'     => 'Tanggal mulai wajib diisi.',
            'start_at.after'        => 'Tanggal mulai harus setelah sekarang.',
            'end_at.required'       => 'Tanggal selesai wajib diisi.',
            'end_at.after'          => 'Tanggal selesai harus setelah tanggal mulai.',
        ]);

        $election = Election::create([
            'title'        => $validated['title'],
            'tahun_ajaran' => $validated['tahun_ajaran'],
            'deskripsi'    => $validated['deskripsi'] ?? null,
            'start_at'     => $validated['start_at'],
            'end_at'       => $validated['end_at'],
            'status'       => ElectionStatus::DRAFT,
            'created_by'   => auth()->id(),
        ]);

        Log::info("Election created: {$election->id} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('election.created', [
            'subject_type' => Election::class,
            'subject_id'   => $election->id,
            'meta'         => [
                'title'        => $election->title,
                'tahun_ajaran' => $election->tahun_ajaran,
                'start_at'     => $election->start_at->toIso8601String(),
                'end_at'       => $election->end_at->toIso8601String(),
            ],
        ]);

        return redirect()
            ->route('admin.elections.show', $election)
            ->with('success', "Pemilihan \"{$election->title}\" berhasil dibuat.");
    }

    /**
     * Detail election.
     */
    public function show(Election $election): View|RedirectResponse
    {
        // ✅ AUTO-ACTIVATE: draft → active kalau waktunya tiba
        if ($election->autoActivateIfReady()) {
            return redirect()
                ->route('admin.elections.show', $election)
                ->with('success', 'Pemilihan otomatis diaktifkan karena waktu sudah masuk.');
        }

        // ✅ AUTO-CLOSE: active/draft → closed kalau end_at lewat
        if ($election->autoCloseIfEnded()) {
            return redirect()
                ->route('admin.elections.show', $election)
                ->with('warning', 'Waktu pemilihan sudah berakhir. Status diubah otomatis menjadi Ditutup.');
        }

        $election->load([
            'creator',
            'candidates' => fn($q) => $q->orderBy('no_urut'),
        ]);

        $stats = [
            'total_candidates' => $election->candidates->count(),
            'total_voters'     => $election->voters()->count(),
            'total_verified'   => $election->voters()
                ->whereHas('user', fn($q) => $q->where('status', 'verified'))
                ->count(),
            'total_voted'      => $election->voters()->where('has_voted', true)->count(),
            'total_sessions'   => $election->sessions()->count(),
            'total_votes'      => $election->votes()->count(),
        ];

        return view('admin.elections.show', compact('election', 'stats'));
    }

    /**
     * Form edit.
     */
    public function edit(Election $election): View|RedirectResponse
    {
        if ($election->status !== ElectionStatus::DRAFT) {
            return redirect()
                ->route('admin.elections.show', $election)
                ->with('warning', 'Hanya pemilihan berstatus draft yang dapat diedit.');
        }

        // Safety: kalau waktu sudah lewat, tidak bisa edit
        if ($election->hasEnded()) {
            return redirect()
                ->route('admin.elections.show', $election)
                ->with('error', 'Waktu pemilihan sudah berakhir.');
        }

        return view('admin.elections.edit', compact('election'));
    }

    /**
     * Update election.
     */
    public function update(Request $request, Election $election): RedirectResponse
    {
        if ($election->status !== ElectionStatus::DRAFT) {
            return redirect()
                ->route('admin.elections.show', $election)
                ->with('warning', 'Hanya pemilihan berstatus draft yang dapat diedit.');
        }

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'deskripsi'    => ['nullable', 'string', 'max:1000'],
            'start_at'     => ['required', 'date'],
            'end_at'       => ['required', 'date', 'after:start_at'],
        ]);

        // ✅ Capture perubahan sebelum update
        $changes = [];
        foreach ($validated as $field => $newValue) {
            $oldValue = $election->$field;

            $oldStr = $oldValue instanceof \Carbon\Carbon
                ? $oldValue->toIso8601String()
                : (string) $oldValue;

            $newStr = $newValue instanceof \Carbon\Carbon
                ? $newValue->toIso8601String()
                : (string) $newValue;

            if ($oldStr !== $newStr) {
                $changes[$field] = ['from' => $oldStr, 'to' => $newStr];
            }
        }

        $election->update($validated);

        Log::info("Election updated: {$election->id} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('election.updated', [
            'subject_type' => Election::class,
            'subject_id'   => $election->id,
            'meta'         => [
                'title'   => $election->title,
                'changes' => $changes,
            ],
        ]);

        return redirect()
            ->route('admin.elections.show', $election)
            ->with('success', 'Pemilihan berhasil diperbarui.');
    }

    /**
     * Hapus election (draft only).
     */
    public function destroy(Election $election): RedirectResponse
    {
        if ($election->status !== ElectionStatus::DRAFT) {
            return back()->with('error', 'Hanya pemilihan berstatus draft yang dapat dihapus.');
        }

        $title = $election->title;

        // ✅ Capture metadata sebelum delete
        $meta = [
            'title'        => $election->title,
            'tahun_ajaran' => $election->tahun_ajaran,
            'candidates'   => $election->candidates()->count(),
            'sessions'     => $election->sessions()->count(),
            'voters'       => $election->voters()->count(),
        ];

        $election->delete();

        Log::warning("Election deleted: {$title} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('election.deleted', [
            'subject_type' => Election::class,
            'subject_id'   => $election->id,
            'meta'         => $meta,
        ]);

        return redirect()
            ->route('admin.elections.index')
            ->with('success', "Pemilihan \"{$title}\" berhasil dihapus.");
    }

    // ==========================================
    // STATUS TRANSITIONS
    // ==========================================

    /**
     * Tutup pemilihan secara manual (active → closed).
     */
    public function close(Election $election): RedirectResponse
    {
        if ($election->status !== ElectionStatus::ACTIVE) {
            return back()->with('error', 'Hanya pemilihan aktif yang dapat ditutup.');
        }

        $election->update(['status' => ElectionStatus::CLOSED]);

        // Tutup semua sesi yang masih active
        $closedSessions = ElectionSession::where('election_id', $election->id)
            ->where('status', SessionStatus::ACTIVE)
            ->update([
                'status'    => SessionStatus::CLOSED,
                'closed_at' => now(),
            ]);

        Log::info("Election manually closed: {$election->id} (+{$closedSessions} sessions) by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('election.closed', [
            'subject_type' => Election::class,
            'subject_id'   => $election->id,
            'meta'         => [
                'title'           => $election->title,
                'closed_sessions' => $closedSessions,
                'manual'          => true,
            ],
        ]);

        return back()->with('success', 'Pemilihan berhasil ditutup.');
    }

    /**
     * Publikasi hasil (closed → published).
     */
    public function publish(Election $election): RedirectResponse
    {
        if ($election->status !== ElectionStatus::CLOSED) {
            return back()->with('error', 'Hanya pemilihan yang sudah ditutup yang dapat dipublikasikan.');
        }

        if ($election->votes()->count() === 0) {
            return back()->with('error', 'Tidak bisa publikasi: belum ada suara yang masuk.');
        }

        $election->update([
            'status'             => ElectionStatus::PUBLISHED,
            'hasil_published_at' => now(),
        ]);

        Log::info("Election published: {$election->id} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('election.published', [
            'subject_type' => Election::class,
            'subject_id'   => $election->id,
            'meta'         => [
                'title' => $election->title,
                'votes' => $election->votes()->count(),
            ],
        ]);

        return back()->with('success', 'Hasil pemilihan berhasil dipublikasikan.');
    }
}
