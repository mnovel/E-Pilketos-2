<?php

namespace App\Http\Controllers\Public;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckStatusController extends Controller
{
    /**
     * Halaman form cek status.
     */
    public function index(): View
    {
        return view('public.check-status.index');
    }

    /**
     * Proses cek status.
     */
    public function check(Request $request): View
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:20'],
        ], [
            'nis.required' => 'NIS wajib diisi.',
        ]);

        // Cari user dengan role voter
        $voter = User::where('nis', $validated['nis'])
            ->where('role', UserRole::VOTER)
            ->with('classRoom', 'verifier')
            ->first();

        return view('public.check-status.result', [
            'nis'    => $validated['nis'],
            'voter'  => $voter,
        ]);
    }
}
