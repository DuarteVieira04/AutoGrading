<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'submissions_id',
        'final_grade',
        'report_sent',
        'notified_student',
        'notified_teacher',
    ];

    protected $casts = [
        'notified_student' => 'boolean',
        'notified_teacher' => 'boolean',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class, 'submissions_id');
    }

    public function testExecutions()
    {
        return $this->hasMany(TestExecution::class, 'submission_result_id');
    }
}
