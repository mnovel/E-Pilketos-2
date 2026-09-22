<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRoom extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'tingkat',
        'jurusan',
        'rombel',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ==================== RELATIONS ====================

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class, 'class_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ElectionSession::class, 'class_id');
    }
}
