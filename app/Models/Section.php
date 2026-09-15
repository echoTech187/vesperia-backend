<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable= [
        'id',
        'name',
        'order',
        'is_active'
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class, 'parent_id','id')->orderBy('order');
    }
}
