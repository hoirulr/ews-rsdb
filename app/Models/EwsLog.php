<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EwsLog extends Model
{
    protected $fillable = [
        'ews_assessment_id',
        'user_id',
        'aksi',
        'status',
        'keterangan',
        'payload',
        'ip_address',
        'user_agent',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(EwsAssessment::class, 'ews_assessment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
