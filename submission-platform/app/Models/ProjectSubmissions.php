<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSubmissions extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'grading_process_id',
        'file_path',
        'status',
        'submitted_at',
        'feedback',
        'grade',
        'grading_log',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'feedback' => 'array', 
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function gradingProcess()
    {
        return $this->belongsTo(GradingProcess::class, 'grading_process_id');
    }
}