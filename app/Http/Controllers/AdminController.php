<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users' => User::count(),
            'admins' => User::where('is_admin', true)->count(),
            'classrooms' => Classroom::count(),
            'assignments' => Assignment::count(),
            'submissions' => Submission::count(),
        ];

        $recentUsers = User::latest()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers'));
    }

    public function users(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->withCount('classrooms')
            ->when($q !== '', fn ($qb) => $qb->where(fn ($w) =>
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', compact('users', 'q'));
    }

    public function toggleAdmin(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('app.admin.cannotChangeSelf'));

        $user->is_admin = ! $user->is_admin;
        $user->save();

        return back()->with('status', $user->is_admin
            ? __('app.admin.promoted', ['name' => $user->name])
            : __('app.admin.demoted', ['name' => $user->name]));
    }
}
