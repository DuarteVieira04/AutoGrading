<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by_teacher_id',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'created_by_teacher_id');
    }

    public function groupOwners()
    {
        return $this->hasMany(GroupOwner::class, 'group_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_owners', 'group_id', 'user_id');
    }

    public function processGroups()
    {
        return $this->hasMany(ProcessGroup::class, 'group_id');
    }

    public function processes()
    {
        return $this->belongsToMany(Process::class, 'process_groups', 'group_id', 'process_id');
    }
}
