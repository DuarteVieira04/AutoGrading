@extends('layouts.app')

@section('title', 'Edit Group')

@section('content')
<div class="space-y-6">

    <h1 class="text-xl font-semibold">Edit Group</h1>

    <form method="POST" action="{{ route('groups.update', $group) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <input name="name"
               value="{{ $group->name }}"
               class="border w-full px-3 py-2">

        <button class="border px-4 py-2">Update</button>
    </form>

    <hr>

    <h2 class="font-medium">Add Student</h2>

    <form method="POST" action="{{ route('groups.addStudent', $group) }}">
        @csrf

        <select name="user_id" class="border w-full px-3 py-2">
            @foreach ($students as $student)
                <option value="{{ $student->id }}">
                    {{ $student->name }} ({{ $student->email }})
                </option>
            @endforeach
        </select>

        <button class="mt-2 border px-4 py-2">
            Add Student
        </button>
    </form>

</div>
@endsection