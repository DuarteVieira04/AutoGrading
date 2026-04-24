@extends('layouts.app')

@section('title', 'Groups')

@section('content')
<div class="space-y-10">

    <header class="border-b border-gray-300 pb-6">
        <h1 class="text-xl font-semibold text-gray-900">Groups</h1>
        <p class="mt-2 text-sm text-gray-600">
            Manage student groups and assignments.
        </p>
        <a href="{{ route('groups.create') }}"
           class="mt-4 inline-block border border-gray-400 px-4 py-2 text-sm hover:bg-gray-100">
            + Create Group
        </a>
    </header>

    <section>
        @if ($groups->isEmpty())
            <p class="text-sm text-gray-600">No groups created yet.</p>
        @else
            <div class="mt-4 overflow-x-auto border border-gray-300">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-100 text-gray-800">
                        <tr>
                            <th class="px-4 py-2 font-medium">Grupo</th>
                            <th class="px-4 py-2 font-medium">Estudantes</th>
                            <th class="px-4 py-2 font-medium">Gerir Estudantes</th>
                            <th class="px-4 py-2 font-medium">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($groups as $group)
                            <tr>
                                {{-- GROUP NAME --}}
                                <td class="px-4 py-2 text-gray-800 font-medium">
                                    {{ $group->name }}
                                </td>

                                {{-- STUDENTS --}}
                                <td class="px-4 py-2 text-gray-800">
                                    @forelse ($group->users as $user)
                                        <div class="flex justify-between items-center mb-1">
                                            <span>{{ $user->name }}</span>
                                        </div>
                                    @empty
                                        <span class="text-gray-500 text-sm">Sem estudantes</span>
                                    @endforelse
                                </td>

                                <td class="px-4 py-2">
                                    @if ($group->users->isEmpty())
                                        <a href="{{ route('groups.students', $group) }}"
                                        class="text-green-600 text-sm hover:underline">
                                            Adicionar estudantes
                                        </a>
                                    @else
                                        <a href="{{ route('groups.students', $group) }}"
                                        class="text-indigo-600 text-sm hover:underline">
                                            Gerir
                                        </a>
                                    @endif
                                </td>

                                {{-- ACTIONS --}}
                                <td class="px-4 py-2">
                                    <div class="flex gap-3 items-center">

                                        {{-- EDIT --}}
                                        <a href="{{ route('groups.edit', $group) }}"
                                        class="text-blue-600 text-sm hover:underline">
                                            Editar
                                        </a>

                                        {{-- DELETE --}}
                                        <form method="POST" action="{{ route('groups.destroy', $group) }}">
                                            @csrf
                                            @method('DELETE')

                                            <button class="text-red-600 text-sm hover:underline"
                                                    onclick="return confirm('Are you sure?')">
                                                Apagar
                                            </button>
                                        </form>

                                    </div>
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