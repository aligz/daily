<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'name',
    ];

    public function featureRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FeatureRequest::class);
    }
}
