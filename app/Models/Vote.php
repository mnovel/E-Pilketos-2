<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    use HasFactory;

    // ⚠️ TIDAK ADA user_id / voter_id!
    protected $fillable = [
        'election_id',
        'session_id',
        'candidate_id',
        'hash',
    ];

    public $timestamps = false;   // hanya created_at, tanpa updated_at

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ==================== RELATIONS ====================

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ElectionSession::class, 'session_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
