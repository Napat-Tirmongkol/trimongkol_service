<?php

namespace App\Http\Controllers;

use App\Models\AdminAction;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Lead;
use App\Models\Submission;
use App\Models\User;
use App\Services\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function dashboard()
    {
        $now = now();
        $stats = [
            'users' => User::count(),
            'admins' => User::where('is_admin', true)->count(),
            'suspended' => User::where('is_active', false)->count(),
            'classrooms' => Classroom::count(),
            'assignments' => Assignment::count(),
            'submissions' => Submission::count(),
            'new_users_week' => User::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'new_users_month' => User::where('created_at', '>=', $now->copy()->subDays(30))->count(),
            'submissions_today' => Submission::whereDate('created_at', $now->toDateString())->count(),
            'active_30d' => User::where('last_login_at', '>=', $now->copy()->subDays(30))->count(),
            'leads_new' => Lead::where('status', 'new')->count(),
            'leads_week' => Lead::where('created_at', '>=', $now->copy()->subDays(7))->count(),
        ];

        $recentUsers = User::latest()->limit(5)->get();

        $topUsers = User::query()
            ->withCount('classrooms')
            ->orderByDesc('classrooms_count')
            ->limit(5)
            ->get();

        // 30-day daily signup series for the trend sparkline.
        $signupSeries = User::query()
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->groupBy('d')
            ->pluck('c', 'd');

        $signupTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->toDateString();
            $signupTrend[] = ['date' => $date, 'count' => (int) ($signupSeries[$date] ?? 0)];
        }

        return view('admin.dashboard', compact('stats', 'recentUsers', 'topUsers', 'signupTrend'));
    }

    public function users(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $role = $request->query('role');     // null | admin | teacher
        $status = $request->query('status'); // null | active | suspended
        $sort = $request->query('sort', 'recent');

        $users = User::query()
            ->withCount('classrooms')
            ->when($q !== '', fn ($qb) => $qb->where(fn ($w) =>
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
            ))
            ->when($role === 'admin', fn ($qb) => $qb->where('is_admin', true))
            ->when($role === 'teacher', fn ($qb) => $qb->where('is_admin', false))
            ->when($status === 'active', fn ($qb) => $qb->where('is_active', true))
            ->when($status === 'suspended', fn ($qb) => $qb->where('is_active', false))
            ->when($sort === 'classrooms', fn ($qb) => $qb->orderByDesc('classrooms_count'))
            ->when($sort === 'last_login', fn ($qb) => $qb->orderByDesc('last_login_at'))
            ->when($sort === 'name', fn ($qb) => $qb->orderBy('name'))
            ->when(! in_array($sort, ['classrooms', 'last_login', 'name'], true),
                fn ($qb) => $qb->latest())
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', compact('users', 'q', 'role', 'status', 'sort'));
    }

    public function showUser(User $user)
    {
        $user->loadCount('classrooms');

        $classrooms = $user->classrooms()
            ->withCount(['students', 'assignments'])
            ->latest()
            ->get();

        $studentCount = $classrooms->sum('students_count');
        $assignmentCount = $classrooms->sum('assignments_count');

        return view('admin.user_show', compact('user', 'classrooms', 'assignmentCount', 'studentCount'));
    }

    public function toggleAdmin(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('app.admin.cannotChangeSelf'));

        $user->is_admin = ! $user->is_admin;
        $user->save();

        AuditLog::record(
            $user->is_admin ? 'user.promote' : 'user.demote',
            $user,
            $user->email,
        );

        return back()->with('status', $user->is_admin
            ? __('app.admin.promoted', ['name' => $user->name])
            : __('app.admin.demoted', ['name' => $user->name]));
    }

    public function toggleActive(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('app.admin.cannotChangeSelf'));

        $user->is_active = ! $user->is_active;
        $user->save();

        AuditLog::record(
            $user->is_active ? 'user.activate' : 'user.suspend',
            $user,
            $user->email,
        );

        return back()->with('status', $user->is_active
            ? __('app.admin.activated', ['name' => $user->name])
            : __('app.admin.suspended', ['name' => $user->name]));
    }

    public function destroyUser(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('app.admin.cannotChangeSelf'));

        $label = $user->email;
        $meta = [
            'name' => $user->name,
            'classrooms' => $user->classrooms()->count(),
        ];

        $user->delete();

        AuditLog::record('user.delete', null, $label, $meta);

        return redirect()->route('admin.users')
            ->with('status', __('app.admin.user_deleted', ['name' => $meta['name']]));
    }

    public function sendPasswordReset(User $user)
    {
        Password::sendResetLink(['email' => $user->email]);

        AuditLog::record('user.password_reset', $user, $user->email);

        return back()->with('status', __('app.admin.password_reset_sent', ['email' => $user->email]));
    }

    public function impersonate(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('app.admin.cannotChangeSelf'));
        abort_if(! $user->is_active, 422, __('app.admin.account_suspended'));

        $adminId = $request->user()->id;
        AuditLog::record('user.impersonate_start', $user, $user->email);

        Auth::guard('web')->login($user);
        $request->session()->put('impersonator_id', $adminId);

        return redirect()->route('dashboard');
    }

    public function stopImpersonating(Request $request)
    {
        $originalId = $request->session()->pull('impersonator_id');
        abort_unless($originalId, 404);

        $currentUser = $request->user();
        $original = User::findOrFail($originalId);

        Auth::guard('web')->login($original);

        // Manually log here — the impersonated user is who acted, but the admin is back in control.
        AdminAction::create([
            'admin_user_id' => $originalId,
            'action' => 'user.impersonate_stop',
            'target_type' => 'User',
            'target_id' => $currentUser?->id,
            'target_label' => $currentUser?->email,
            'metadata' => null,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('admin.dashboard');
    }

    public function logs(Request $request)
    {
        $action = $request->query('action');

        $logs = AdminAction::query()
            ->with('admin')
            ->when($action, fn ($qb) => $qb->where('action', $action))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $actions = AdminAction::query()->distinct()->orderBy('action')->pluck('action');

        return view('admin.logs', compact('logs', 'actions', 'action'));
    }

    public function exportUsers(): StreamedResponse
    {
        $filename = 'users_' . now()->format('Ymd_His') . '.csv';

        return new StreamedResponse(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['id', 'name', 'email', 'role', 'status', 'classrooms', 'last_login_at', 'created_at']);

            User::query()
                ->withCount('classrooms')
                ->orderBy('id')
                ->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $u) {
                        fputcsv($out, [
                            $u->id,
                            $u->name,
                            $u->email,
                            $u->is_admin ? 'admin' : 'teacher',
                            $u->is_active ? 'active' : 'suspended',
                            $u->classrooms_count,
                            optional($u->last_login_at)->format('Y-m-d H:i:s'),
                            $u->created_at->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
