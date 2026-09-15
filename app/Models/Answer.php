<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
   protected $fillable = [
        'submission_id',
        'field_id',
        'raw_answer',
        'text_value',
        'numeric_value',
        'date_value',
        'supporting_file'
   ];

   protected $casts = [
        'raw_answer' => 'array',
        'supporting_file' => 'array',
        'numeric_value' => 'decimal:2',
        'date_value' => 'date',
   ];

   public function submission(): BelongsTo
   {
        return $this->belongsTo(Submission::class);
   }
   
   public function field(): BelongsTo
   {
        return $this->belongsTo(Field::class, 'field_id', 'id');
   }
}
