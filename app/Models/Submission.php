<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    protected $fillable = [
        'title',
        'submitted_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function answers() : HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
