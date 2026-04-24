<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_result_id',
        'test_name',
        'status',
        'error_message',
        'execution_logs',
    ];

    public function submissionResult()
    {
        return $this->belongsTo(SubmissionResult::class);
    }
}
