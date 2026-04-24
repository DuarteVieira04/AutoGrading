<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::with('users')->get();

        return view('groups.index', compact('groups'));
    }

    public function create(): View
    {
        return view('groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
        ]);

        Group::create([
            'name' => $validated['name'],
            'created_by_teacher_id' => auth()->id(), 
        ]);

        return redirect()
            ->route('groups.index')
            ->with('status', __('Grupo Criado.'));
    }

    public function show(Group $group)
    {
        $group->load('users', 'processes', 'teacher');

        return view('groups.show', compact('group'));
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
        ]);

        $group->update($validated);

        return redirect()
            ->route('groups.index')
            ->with('status', __('Grupo atualizado.'));
    }

    public function edit(Group $group)
    {
        $students = User::whereHas('student')->get();

        return view('groups.edit', compact('group', 'students'));
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return redirect()
            ->route('groups.index')
            ->with('status', __('Grupo removido.'));
    }

    public function addStudent(Request $request, Group $group)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $group->users()->syncWithoutDetaching($validated['user_id']);

        return redirect()
            ->route('groups.index')
            ->with('status', __('Utilizador adicionado ao grupo.'));
    }

    public function removeStudent(Group $group, User $user)
    {
        $group->users()->detach($user->id);

        return redirect()
            ->route('groups.index')
            ->with('status', __('Utilizador removido ao grupo.'));
    }

    public function students(Group $group)
    {
        $group->load('users');

        $students = User::whereHas('student')->get();

        return view('groups.students', [
            'group' => $group,
            'students' => $students,
        ]);
    }
}
