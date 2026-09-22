<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ElectionStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CandidateController extends Controller
{
    /**
     * List candidates dengan election selector.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $elections = Election::orderBy('created_at', 'desc')->get();

        if ($elections->isEmpty()) {
            return redirect()
                ->route('admin.elections.index')
                ->with('warning', 'Belum ada pemilihan. Buat pemilihan dulu.');
        }

        $electionId = $request->input('election_id', $elections->first()->id);
        $election   = Election::findOrFail($electionId);

        $candidates = Candidate::where('election_id', $election->id)
            ->with('classRoom')
            ->orderBy('no_urut')
            ->get();

        return view('admin.candidates.index', compact('elections', 'election', 'candidates'));
    }

    /**
     * Form create.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $electionId = $request->input('election_id');

        if (!$electionId) {
            return redirect()
                ->route('admin.candidates.index')
                ->with('warning', 'Pilih pemilihan dulu.');
        }

        $election = Election::findOrFail($electionId);

        if ($election->status !== ElectionStatus::DRAFT) {
            return redirect()
                ->route('admin.candidates.index', ['election_id' => $election->id])
                ->with('error', 'Kandidat hanya dapat ditambah saat pemilihan berstatus draft.');
        }

        $nextNo = ($election->candidates()->max('no_urut') ?? 0) + 1;

        $classes = ClassRoom::active()
            ->orderBy('tingkat')
            ->orderBy('name')
            ->get();

        return view('admin.candidates.create', compact('election', 'nextNo', 'classes'));
    }

    /**
     * Store candidate.
     */
    public function store(Request $request): RedirectResponse
    {
        $election = Election::findOrFail($request->input('election_id'));

        if ($election->status !== ElectionStatus::DRAFT) {
            return back()->with('error', 'Kandidat hanya dapat ditambah saat pemilihan berstatus draft.');
        }

        $validated = $request->validate([
            'no_urut'       => ['required', 'integer', 'min:1', 'unique:candidates,no_urut,NULL,id,election_id,' . $election->id],
            'nama'          => ['required', 'string', 'max:255'],
            'class_id'      => ['required', 'exists:classes,id'],
            'visi'          => ['required', 'string', 'max:1000'],
            'misi'          => ['required', 'string', 'max:2000'],
            'program_kerja' => ['nullable', 'string', 'max:2000'],
            'foto'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'no_urut.required'  => 'Nomor urut wajib diisi.',
            'no_urut.unique'    => 'Nomor urut sudah dipakai kandidat lain.',
            'nama.required'     => 'Nama kandidat wajib diisi.',
            'class_id.required' => 'Kelas wajib dipilih.',
            'class_id.exists'   => 'Kelas tidak valid.',
            'visi.required'     => 'Visi wajib diisi.',
            'misi.required'     => 'Misi wajib diisi.',
            'foto.image'        => 'File harus berupa gambar.',
            'foto.max'          => 'Ukuran foto maksimal 2MB.',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('candidates', 'public');
        }

        $candidate = Candidate::create([
            'election_id'   => $election->id,
            'no_urut'       => $validated['no_urut'],
            'nama'          => $validated['nama'],
            'class_id'      => $validated['class_id'],
            'visi'          => $validated['visi'],
            'misi'          => $validated['misi'],
            'program_kerja' => $validated['program_kerja'] ?? null,
            'foto'          => $fotoPath,
        ]);

        Log::info("Candidate created: {$candidate->nama} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('candidate.created', [
            'subject_type' => Candidate::class,
            'subject_id'   => $candidate->id,
            'meta'         => [
                'nama'        => $candidate->nama,
                'no_urut'     => $candidate->no_urut,
                'election_id' => $election->id,
                'election'    => $election->title,
            ],
        ]);

        return redirect()
            ->route('admin.candidates.index', ['election_id' => $election->id])
            ->with('success', "Kandidat \"{$candidate->nama}\" berhasil ditambahkan.");
    }

    /**
     * Show candidate.
     */
    public function show(Candidate $candidate): View
    {
        $candidate->load('election', 'classRoom');

        return view('admin.candidates.show', compact('candidate'));
    }

    /**
     * Form edit.
     */
    public function edit(Candidate $candidate): View|RedirectResponse
    {
        $candidate->load('election');

        if ($candidate->election->status !== ElectionStatus::DRAFT) {
            return redirect()
                ->route('admin.candidates.show', $candidate)
                ->with('error', 'Kandidat hanya dapat diedit saat pemilihan berstatus draft.');
        }

        $classes = ClassRoom::active()
            ->orderBy('tingkat')
            ->orderBy('name')
            ->get();

        return view('admin.candidates.edit', compact('candidate', 'classes'));
    }

    /**
     * Update candidate.
     */
    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $candidate->load('election');

        if ($candidate->election->status !== ElectionStatus::DRAFT) {
            return back()->with('error', 'Kandidat hanya dapat diedit saat pemilihan berstatus draft.');
        }

        $validated = $request->validate([
            'no_urut'       => ['required', 'integer', 'min:1', 'unique:candidates,no_urut,' . $candidate->id . ',id,election_id,' . $candidate->election_id],
            'nama'          => ['required', 'string', 'max:255'],
            'class_id'      => ['required', 'exists:classes,id'],
            'visi'          => ['required', 'string', 'max:1000'],
            'misi'          => ['required', 'string', 'max:2000'],
            'program_kerja' => ['nullable', 'string', 'max:2000'],
            'foto'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data = [
            'no_urut'       => $validated['no_urut'],
            'nama'          => $validated['nama'],
            'class_id'      => $validated['class_id'],
            'visi'          => $validated['visi'],
            'misi'          => $validated['misi'],
            'program_kerja' => $validated['program_kerja'] ?? null,
        ];

        // ✅ Capture perubahan sebelum update
        $changes = [];
        foreach (['no_urut', 'nama', 'class_id', 'visi', 'misi', 'program_kerja'] as $field) {
            $oldValue = $candidate->$field;
            $newValue = $data[$field];

            if ((string) $oldValue !== (string) $newValue) {
                $changes[$field] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        if ($request->hasFile('foto')) {
            if ($candidate->foto && Storage::disk('public')->exists($candidate->foto)) {
                Storage::disk('public')->delete($candidate->foto);
            }
            $data['foto'] = $request->file('foto')->store('candidates', 'public');
            $changes['foto'] = ['from' => $candidate->foto, 'to' => $data['foto']];
        }

        $candidate->update($data);

        Log::info("Candidate updated: {$candidate->nama} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('candidate.updated', [
            'subject_type' => Candidate::class,
            'subject_id'   => $candidate->id,
            'meta'         => [
                'nama'        => $candidate->nama,
                'no_urut'     => $candidate->no_urut,
                'election_id' => $candidate->election_id,
                'election'    => $candidate->election->title,
                'changes'     => $changes,
            ],
        ]);

        return redirect()
            ->route('admin.candidates.index', ['election_id' => $candidate->election_id])
            ->with('success', 'Kandidat berhasil diperbarui.');
    }

    /**
     * Delete candidate.
     */
    public function destroy(Candidate $candidate): RedirectResponse
    {
        $candidate->load('election');

        if ($candidate->election->status !== ElectionStatus::DRAFT) {
            return back()->with('error', 'Kandidat hanya dapat dihapus saat pemilihan berstatus draft.');
        }

        $electionId = $candidate->election_id;
        $nama       = $candidate->nama;

        // ✅ Capture metadata sebelum delete
        $meta = [
            'nama'        => $candidate->nama,
            'no_urut'     => $candidate->no_urut,
            'election_id' => $candidate->election_id,
            'election'    => $candidate->election->title,
            'has_foto'    => (bool) $candidate->foto,
        ];

        if ($candidate->foto && Storage::disk('public')->exists($candidate->foto)) {
            Storage::disk('public')->delete($candidate->foto);
        }

        $candidate->delete();

        Log::warning("Candidate deleted: {$nama} by " . auth()->user()->name);

        // ✅ Activity Log
        ActivityLog::log('candidate.deleted', [
            'subject_type' => Candidate::class,
            'subject_id'   => $candidate->id,
            'meta'         => $meta,
        ]);

        return redirect()
            ->route('admin.candidates.index', ['election_id' => $electionId])
            ->with('success', "Kandidat \"{$nama}\" berhasil dihapus.");
    }
}
