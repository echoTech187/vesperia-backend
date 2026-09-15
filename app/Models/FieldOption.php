<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldOption extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable= [
        'id',
        'field_id',
        'label',
        'value'
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'field_id', 'id');
    }
}
