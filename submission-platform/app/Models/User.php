<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class, 'created_by_teacher_id');
    }

    public function processes()
    {
        return $this->hasMany(Process::class, 'teacher_id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    public function groupOwnership()
    {
        return $this->hasMany(GroupOwner::class);
    }

    public function memberGroups()
    {
        return $this->belongsToMany(Group::class, 'group_owners', 'user_id', 'group_id');
    }
    
    public function hasRole(string $role)
    {
        if ($role === 'teacher') {
            return $this->teacher()->exists();
        }
        if ($role === 'student') {
            return $this->student()->exists();
        }
        return false;
    }
}
