<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function index(Request $request) : View
    {
        $query = User::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->input('email') . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('blocked')) {
            $query->where('blocked', $request->input('blocked'));
        }

        if($request->filled('gender')) {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->filled('nif')) {
            $query->where('nif', $request->input('nif'));
        }
        $users = $query->paginate(20)->appends($request->except('page'));

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $newUser = User::create($request->validated());

        $url = route('users.show', ['user' => $newUser]);
        $htmlMessage = "User <a href='$url'><strong>{$newUser->id}</strong>
                    - </a> New User has been created successfully!";
        return redirect()->route('users.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', $htmlMessage);
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $user->update($request->validated());

        return redirect()->route('users.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', 'User deleted successfully!');
    }
}
