<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
    ];

    protected $casts = [
        // Eloquent auto-decodes JSON → PHP array on read, encodes on write
        'properties'  => 'array',
        // Format the timestamp nicely for direct display in the DataTable
        'created_at'  => 'datetime:Y-m-d H:i',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Polymorphic: $log->subject returns the actual User/Product/etc. model instance
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
