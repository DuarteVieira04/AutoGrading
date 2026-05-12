<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_process_id',
        'process_test_group_id',
        'student_id',
        'zip_file_path',
        'status',
        'submission_date',
    ];

    protected $casts = [
        'submission_date' => 'datetime',
    ];

    public function process()
    {
        return $this->belongsTo(Process::class, 'evaluation_process_id');
    }

    public function processTestGroup()
    {
        return $this->belongsTo(ProcessTestGroup::class, 'process_test_group_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class);
    }

    public function submissionResult()
    {
        return $this->hasOne(SubmissionResult::class, 'submissions_id');
    }
}
