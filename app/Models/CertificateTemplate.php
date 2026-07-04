<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'name',
        'file_path',
        'is_default',
        'name_y_percent',
        'font_size',
        'font_color',
        'font_family',
        'font_style',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'name_y_percent' => 'decimal:2',
            'font_size' => 'integer',
        ];
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Only one template can be the default at a time
        static::saving(function (CertificateTemplate $template) {
            if ($template->is_default) {
                static::where('id', '!=', $template->id)->update(['is_default' => false]);
            }
        });
    }
}
