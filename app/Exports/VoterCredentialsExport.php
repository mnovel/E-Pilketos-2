<?php

namespace App\Exports;

use App\Models\User;
use App\Enums\UserRole;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VoterCredentialsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
     * @param array $voterIds — IDs voter yang akan diexport
     * @param array $plainPasswords — [voter_id => plain_password] (opsional)
     */
    public function __construct(
        protected array $voterIds,
        protected array $plainPasswords = []
    ) {}

    public function collection()
    {
        return User::whereIn('id', $this->voterIds)
            ->with('classRoom')
            ->orderBy('class_id')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'NIS',
            'Nama',
            'Kelas',
            'Email',
            'Password',
            'Status',
        ];
    }

    public function map($voter): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $voter->nis ?? '-',
            $voter->name,
            $voter->classRoom?->name ?? '-',
            $voter->email,
            $this->plainPasswords[$voter->id] ?? '(password tidak direset)',
            $voter->status->label(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
