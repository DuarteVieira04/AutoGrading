@extends('layouts.app')

@section('title', 'Create Group')

@section('content')
<div class="space-y-6">

    <h1 class="text-xl font-semibold">Create Group</h1>

    <form method="POST" action="{{ route('groups.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="text-sm">Group name</label>
            <input name="name"
                   class="border w-full px-3 py-2"
                   required>
        </div>

        <button class="border px-4 py-2 hover:bg-gray-100">
            Save
        </button>
    </form>

</div>
@endsection