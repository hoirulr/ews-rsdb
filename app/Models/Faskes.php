<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faskes extends Model
{
    protected $fillable = [
        'nama_faskes',
        'tipe',
        'kode_faskes',
        'alamat',
        'no_telp',
        'is_active',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'faskes_asal_id');
    }

    public function ewsAssessments(): HasMany
    {
        return $this->hasMany(EwsAssessment::class);
    }

    public function getTipeLabelAttribute(): string
    {
        return match ($this->tipe) {
            'rsud' => 'RSUD',
            'puskesmas' => 'Puskesmas',
            'rs_perujuk' => 'RS Perujuk',
            default => 'Faskes',
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
