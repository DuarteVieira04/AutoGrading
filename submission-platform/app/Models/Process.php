<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    use HasFactory;

    protected $fillable = [
        'process_name',
        'process_type_id',
        'teacher_id',
        'open_date',
        'close_date',
        'execution_environment',
        'results_visibility',
        'results_criteria',
        'weighting',
        'max_file_size_byte',
        'email_notification',
    ];

    protected $casts = [
        'open_date' => 'datetime',
        'close_date' => 'datetime',
        'email_notification' => 'boolean',
    ];

    public function processType()
    {
        return $this->belongsTo(ProcessType::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'evaluation_process_id');
    }

    public function processGroups()
    {
        return $this->hasMany(ProcessGroup::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'process_groups', 'process_id', 'group_id');
    }

}
