<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutineEntry extends Model
{
    use HasFactory;

    public const STATUS_DONE = 'done';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUSES = [
        self::STATUS_DONE,
        self::STATUS_SKIPPED,
    ];

    protected $fillable = [
        'check_date',
        'status',
        'checked_at',
    ];

    protected $casts = [
        'check_date' => 'date:Y-m-d',
        'checked_at' => 'datetime',
    ];

    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }
}
