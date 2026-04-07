<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        return Student::with('user')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'student_number' => 'required|string|unique:students',
            'class' => 'nullable|string',
        ]);

        $student = Student::create($request->all());
        return response()->json($student, 201);
    }

    public function show(Student $student)
    {
        return $student->load('user', 'codeDeliveries');
    }

 
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'student_number' => 'required|string|unique:students,student_number,' . $student->id,
            'class' => 'nullable|string',
        ]);

        $student->update($request->only(['student_number', 'class']));
        return response()->json($student);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(null, 204);
    }
}
