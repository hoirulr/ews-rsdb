<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EwsBroadcastFailure extends Model
{
    protected $fillable = [
        'ews_assessment_id',
        'attempt',
        'error_message',
        'failed_at',
        'resolved',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(EwsAssessment::class, 'ews_assessment_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'failed_at' => 'datetime',
            'resolved' => 'boolean',
        ];
    }
}
