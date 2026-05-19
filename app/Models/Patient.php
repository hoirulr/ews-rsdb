<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'nama_pasien',
        'no_rm',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'no_telp',
        'faskes_asal_id',
    ];

    public function faskesAsal(): BelongsTo
    {
        return $this->belongsTo(Faskes::class, 'faskes_asal_id');
    }

    public function ewsAssessments(): HasMany
    {
        return $this->hasMany(EwsAssessment::class);
    }

    public function getUmurAttribute(): int
    {
        return $this->tanggal_lahir->age;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }
}
