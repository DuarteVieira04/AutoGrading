<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'process_id',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}
