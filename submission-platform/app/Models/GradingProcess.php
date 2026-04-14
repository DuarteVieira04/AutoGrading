<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingProcess extends Model
{
    protected $fillable = [
        'name',
        'description',
        'components',
        'is_active',
    ];

    protected $casts = [
        'components' => 'array',
        'is_active' => 'boolean',
    ];

    public function projectSubmission(): HasMany
    {
        return $this->hasMany(ProjectSubmission::class, 'grading_process_id');
    }

    public static function active(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->first();
    }

    protected static function booted(): void
    {
        static::saved(function (GradingProcess $process) {
            if ($process->is_active) {
                static::query()
                    ->where('id', '!=', $process->id)
                    ->update(['is_active' => false]);
            }
        });
    }
}
