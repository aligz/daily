<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Routine extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'position',
        'starts_on',
        'archived_on',
    ];

    protected $casts = [
        'starts_on' => 'date:Y-m-d',
        'archived_on' => 'date:Y-m-d',
        'position' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(RoutineEntry::class);
    }

    /**
     * A routine counts for a date from the day it starts until the day
     * before it is archived. Archived on a date means "not active" that day.
     */
    public function isActiveOn(string $date): bool
    {
        $day = CarbonImmutable::parse($date, config('tasks.display_timezone'))->toDateString();

        if ($this->starts_on->toDateString() > $day) {
            return false;
        }

        return $this->archived_on === null || $day < $this->archived_on->toDateString();
    }

    public function scopeActiveOn(Builder $query, string $date): Builder
    {
        return $query
            ->whereDate('starts_on', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('archived_on')
                    ->orWhereDate('archived_on', '>', $date);
            });
    }
}
