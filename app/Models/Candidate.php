<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'election_id',
        'class_id',
        'no_urut',
        'nama',
        'foto',
        'visi',
        'misi',
        'program_kerja',
    ];

    // ==================== RELATIONS ====================

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function totalVotes(): int
    {
        return $this->votes()->count();
    }

    public function percentage(int $totalVotes): float
    {
        return $totalVotes > 0
            ? round(($this->totalVotes() / $totalVotes) * 100, 2)
            : 0;
    }
}
