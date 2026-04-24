@extends('layouts.app')

@section('title', 'Gerir Estudantes')

@section('content')
<div class="space-y-10">

    <header class="border-b border-gray-300 pb-6">
        <h1 class="text-xl font-semibold text-gray-900">
            Gerir Estudantes - {{ $group->name }}
        </h1>
    </header>

    {{-- ADD STUDENT --}}
    <form method="POST" action="{{ route('groups.addStudent', $group) }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-800">Add Student</label>
            <select name="user_id" class="mt-2 block w-full border px-3 py-2 text-sm">
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">
                        {{ $student->name }} ({{ $student->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <button class="border border-gray-400 px-4 py-2 text-sm hover:bg-gray-100">
            Adicionar ao Grupo
        </button>
    </form>

    {{-- CURRENT STUDENTS --}}
    <section>
        <h2 class="text-base font-medium text-gray-900">Grupo Estudantes</h2>

        @if ($group->users->isEmpty())
            <p class="mt-3 text-sm text-gray-600">Sem estudantes neste grupo.</p>
        @else
            <div class="mt-4 overflow-x-auto border border-gray-300">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-100 text-gray-800">
                        <tr>
                            <th class="px-4 py-2">Nome</th>
                            <th class="px-4 py-2">Email</th>
                            <th class="px-4 py-2">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y bg-white">
                        @foreach ($group->users as $user)
                            <tr>
                                <td class="px-4 py-2">{{ $user->name }}</td>
                                <td class="px-4 py-2">{{ $user->email }}</td>
                                <td class="px-4 py-2">
                                    <form method="POST"
                                          action="{{ route('groups.removeStudent', [$group, $user]) }}">
                                        @csrf
                                        @method('DELETE')

                                        <button class="text-red-600 text-sm hover:underline">
                                            Remover
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        @endif
    </section>

</div>
@endsection