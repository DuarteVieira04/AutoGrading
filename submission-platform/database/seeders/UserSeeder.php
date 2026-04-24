<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ======================
        // STUDENT
        // ======================
        $studentUser = User::create([
            'name' => 'student',
            'email' => 'student@local.test',
            'password' => Hash::make('student'),
            'role' => 'student',
        ]);

        Student::create([
            'user_id' => $studentUser->id,
            'student_number' => 'S001',
            'class' => 'A1',
        ]);

        // ======================
        // TEACHER
        // ======================
        $teacherUser = User::create([
            'name' => 'teacher',
            'email' => 'teacher@local.test',
            'password' => Hash::make('teacher'),
            'role' => 'teacher',
        ]);

        Teacher::create([
            'user_id' => $teacherUser->id,
        ]);
    }
}