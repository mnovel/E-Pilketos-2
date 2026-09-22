<?php

namespace App\Exports;

use App\Models\Election;
use App\Models\ElectionSession;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ElectionResultExport implements WithMultipleSheets
{
    protected Election $election;

    public function __construct(Election $election)
    {
        $this->election = $election;
    }

    public function sheets(): array
    {
        return [
            new ElectionSummarySheet($this->election),
            new CandidateVotesSheet($this->election),
            new SessionRecapSheet($this->election),
        ];
    }
}

// ==========================================
// SHEET 1 — SUMMARY
// ==========================================
class ElectionSummarySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected Election $election;

    public function __construct(Election $election)
    {
        $this->election = $election;
    }

    public function collection()
    {
        $totalVoters = $this->election->voters()->count();
        $totalVoted  = $this->election->votes()->count();
        $golput      = max(0, $totalVoters - $totalVoted);

        return collect([
            ['Judul Pemilihan', $this->election->title],
            ['Tahun Ajaran', $this->election->tahun_ajaran],
            ['Periode', $this->election->start_at->translatedFormat('d M Y H:i') . ' s/d ' . $this->election->end_at->translatedFormat('d M Y H:i')],
            ['Dipublikasi', $this->election->hasil_published_at?->translatedFormat('d M Y, H:i') ?? '-'],
            ['', ''],
            ['Total Pemilih', $totalVoters],
            ['Total Suara Sah', $totalVoted],
            ['Golput', $golput],
            ['Partisipasi', $totalVoters > 0 ? round(($totalVoted / $totalVoters) * 100, 2) . '%' : '0%'],
        ]);
    }

    public function headings(): array
    {
        return ['Item', 'Keterangan'];
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}

// ==========================================
// SHEET 2 — PEROLEHAN SUARA
// ==========================================
class CandidateVotesSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected Election $election;

    public function __construct(Election $election)
    {
        $this->election = $election;
    }

    public function collection()
    {
        $totalVotes = $this->election->votes()->count();

        return $this->election->candidates()
            ->orderBy('no_urut')
            ->withCount('votes')
            ->with('classRoom')
            ->get()
            ->map(function ($c) use ($totalVotes) {
                return [
                    'No Urut'     => $c->no_urut,
                    'Nama'        => $c->nama,
                    'Kelas'       => $c->classRoom?->name ?? '-',
                    'Jumlah Suara' => $c->votes_count,
                    'Persentase'  => $totalVotes > 0
                        ? round(($c->votes_count / $totalVotes) * 100, 2) . '%'
                        : '0%',
                ];
            });
    }

    public function headings(): array
    {
        return ['No Urut', 'Nama', 'Kelas', 'Jumlah Suara', 'Persentase'];
    }

    public function title(): string
    {
        return 'Perolehan Suara';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '198754']]
            ],
        ];
    }
}

// ==========================================
// SHEET 3 — REKAP PER KELAS
// ==========================================
class SessionRecapSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected Election $election;

    public function __construct(Election $election)
    {
        $this->election = $election;
    }

    public function collection()
    {
        return ElectionSession::where('election_id', $this->election->id)
            ->with('classRoom')
            ->withCount([
                'voters',
                'voters as voted_count' => fn($q) => $q->where('has_voted', true),
            ])
            ->orderBy('class_id')
            ->get()
            ->map(function ($s) {
                $total = $s->voters_count;
                $voted = $s->voted_count;

                return [
                    'Kelas'        => $s->classRoom?->name ?? '-',
                    'Total Pemilih' => $total,
                    'Memilih'      => $voted,
                    'Golput'       => max(0, $total - $voted),
                    'Partisipasi'  => $total > 0
                        ? round(($voted / $total) * 100, 2) . '%'
                        : '0%',
                ];
            });
    }

    public function headings(): array
    {
        return ['Kelas', 'Total Pemilih', 'Memilih', 'Golput', 'Partisipasi'];
    }

    public function title(): string
    {
        return 'Rekap Per Kelas';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '0D6EFD']]
            ],
        ];
    }
}
