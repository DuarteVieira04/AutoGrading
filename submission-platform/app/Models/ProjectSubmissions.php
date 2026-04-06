<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSubmissions extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'file_path',
        'status',
        'submitted_at',
        'feedback',
        'grade',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'feedback' => 'array', 
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}