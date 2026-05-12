<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grupo de testes associado a um Process (pasta/suite).
 *
 * @property array|null $visibility Regras de visibilidade deste grupo no relatório (opcional).
 */
class ProcessTestGroup extends Model
{
    protected $fillable = [
        'process_id',
        'name',
        'path_pattern',
        'visibility',
    ];

    protected $casts = [
        'visibility' => 'array',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'process_test_group_id');
    }
}
