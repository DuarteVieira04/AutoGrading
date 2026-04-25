<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessType extends Model
{
    use HasFactory;

    public const DEFAULT_NAME = 'Default';

    protected $fillable = [
        'name',
    ];

    public function processes()
    {
        return $this->hasMany(Process::class);
    }

    public function isDefault(): bool
    {
        return $this->name === self::DEFAULT_NAME;
    }
}
