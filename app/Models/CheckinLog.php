<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckinLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'session_id',
        'voter_id',
        'checkin_device_id',
        'operator_id',
        'ip_address',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public $timestamps = false;

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ElectionSession::class, 'session_id');
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(CheckinDevice::class, 'checkin_device_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
