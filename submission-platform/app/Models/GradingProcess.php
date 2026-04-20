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
        'start_date',
        'submission_start_date',
        'submission_end_date',
        'end_date',
    ];

    protected $casts = [
        'components' => 'array',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'submission_start_date' => 'datetime',
        'submission_end_date' => 'datetime',
        'end_date' => 'datetime',
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

}
