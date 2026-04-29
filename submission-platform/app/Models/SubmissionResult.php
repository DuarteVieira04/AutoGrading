<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionResult extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'submissions_id',
        'final_grade',
        'report_sent',
        'notified_student',
        'notified_teacher',
        'created_at',
    ];

    protected $casts = [
        'notified_student' => 'boolean',
        'notified_teacher' => 'boolean',
    ];

    public function getReportSentPayloadAttribute()
    {
        if (empty($this->report_sent)) {
            return null;
        }

        try {
            return json_decode($this->report_sent, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }
    }

    public function submission()
    {
        return $this->belongsTo(Submission::class, 'submissions_id');
    }

    public function testExecutions()
    {
        return $this->hasMany(TestExecution::class, 'submission_result_id');
    }
}
