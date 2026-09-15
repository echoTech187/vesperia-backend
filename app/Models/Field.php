<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Field extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable= [
        'id',
        'parent_id',
        'label',
        'type',
        'sub_type',
        'description',
        'orm_only',
        'order',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'parent_id', 'id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(FieldOption::class, 'field_id', 'id');
    }

    public function answer(): HasMany
    {
        return $this->hasMany(Answer::class, 'field_id', 'id');
    }
}
