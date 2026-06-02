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
        'max_file_size_byte',
        'email_notification',
        'config',
        'project_zip_path',
        'project_base_path',
        'project_working_path',
        'project_status',
        'project_error',
        'project_prepared_at',
        'project_log',
    ];

    protected $casts = [
        'open_date' => 'datetime',
        'close_date' => 'datetime',
        'email_notification' => 'boolean',
        'config' => 'array',
        'project_prepared_at' => 'datetime',
    ];

    public const PROJECT_STATUS_PENDING = 'pending';
    public const PROJECT_STATUS_PREPARING = 'preparing';
    public const PROJECT_STATUS_READY = 'ready';
    public const PROJECT_STATUS_FAILED = 'failed';

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

    public function processTestGroups()
    {
        return $this->hasMany(ProcessTestGroup::class)->orderBy('id');
    }

    /**
     * Número máximo de submissões permitidas por aluno neste processo.
     * 0 (ou ausente) significa sem limite.
     */
    public function submissionLimit(): int
    {
        $value = data_get($this->config, 'submission_limit', 0);

        return is_numeric($value) ? max(0, (int) $value) : 0;
    }

    /**
     * Indica se o aluno indicado pode submeter (ainda não atingiu o limite).
     */
    public function studentCanSubmit(int $studentId): bool
    {
        $limit = $this->submissionLimit();
        if ($limit <= 0) {
            return true;
        }

        return $this->submissions()->where('student_id', $studentId)->count() < $limit;
    }

    /**
     * Quantas submissões o aluno já efectuou neste processo.
     */
    public function submissionsCountForStudent(int $studentId): int
    {
        return (int) $this->submissions()->where('student_id', $studentId)->count();
    }
}
