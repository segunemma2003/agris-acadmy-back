<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A learner's auto-generated CV (see CvService). */
class LearnerCv extends Model
{
    protected $fillable = ['user_id', 'data', 'details', 'template', 'target_country', 'pdf_path', 'open_to_work', 'generated_at'];

    protected $hidden = ['pdf_path'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'details' => 'array',
            'open_to_work' => 'boolean',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
