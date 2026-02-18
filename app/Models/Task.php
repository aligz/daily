<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'reference',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function history(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TaskHistory::class);
    }
}
