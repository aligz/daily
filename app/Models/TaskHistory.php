<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskHistory extends Model
{
    protected $fillable = [
        'task_id',
        'from_status',
        'to_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function task(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
