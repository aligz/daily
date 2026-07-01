<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureRequest extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'user_id',
        'division_id',
        'requester_name',
        'requester_email',
        'requester_phone',
        'title',
        'description',
        'priority',
        'status',
        'deadline',
        'notes',
    ];

    protected $casts = [
        'deadline' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function division(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'planning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'development' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'done' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'released' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        };
    }

    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
            'medium' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        };
    }
}
