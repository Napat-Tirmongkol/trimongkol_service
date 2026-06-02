<?php

namespace App\Http\Controllers;

use App\Models\AdminAction;
use App\Models\Classroom;
use App\Models\Lead;
use App\Models\LoginAttempt;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AuditLog;
use App\Services\QueuePlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    /** Plans & Billing control panel — plan distribution + free-launch toggle. */
    public function billing()
    {
        $queueByPlan = Workspace::query()
            ->selectRaw('queue_plan, COUNT(*) as c')
            ->groupBy('queue_plan')
            ->pluck('c', 'queue_plan')
            ->all();

        $queueMrr = 0;
        foreach ($queueByPlan as $key => $count) {
            $queueMrr += (int) (QueuePlan::price($key) ?? 0) * (int) $count;
        }

        $scannerByPlan = Subscription::query()
            ->whereIn('status', ['active', 'trial'])
            ->selectRaw('plan_key, COUNT(*) as c')
            ->groupBy('plan_key')
            ->pluck('c', 'plan_key')
            ->all();

        $stats = [
            'workspaces' => Workspace::count(),
            'free_mode' => \App\Services\Billing::freeMode(),
            'queue_by_plan' => $queueByPlan,
            'queue_mrr' => $queueMrr,
            'scanner_by_plan' => $scannerByPlan,
        ];

        return view('admin.billing', compact('stats', 'queuePlans'));
    }

    public function updateBilling(Request $request)
    {
        $on = $request->boolean('free_mode');
        Setting::updateOrCreate(['key' => 'billing.free_mode'], ['value' => $on ? '1' : '0']);
        AuditLog::record('billing.free_mode', null, $on ? 'on' : 'off');

        return back()->with('status', __('app.admin.billing.saved'));
    }

    public function dashboard()
    {
        $now = now();
        $stats = [
            'users' => User::count(),
            'admins' => User::where('is_admin', true)->count(),
            'suspended' => User::where('is_active', false)->count(),
            'new_users_week' => User::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'new_users_month' => User::where('created_at', '>=', $now->copy()->subDays(30))->count(),
            'active_30d' => User::where('last_login_at', '>=', $now->copy()->subDays(30))->count(),
            'leads_new' => Lead::where('status', 'new')->count(),
            'leads_week' => Lead::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'workspaces' => Workspace::count(),
        ];

        $recentUsers = User::latest()->limit(5)->get();

        // Products are surfaced as launcher links; each product keeps its own
        // dashboard + metrics under /admin/products/* (see docs/ADMIN.md).
        $products = config('admin-products', []);

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

        return view('admin.dashboard', compact('stats', 'recentUsers', 'signupTrend', 'products'));
    }

    public function users(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $role = $request->query('role');     // null | admin | teacher
        $status = $request->query('status'); // null | active | suspended
        $sort = $request->query('sort', 'recent');

        $users = User::query()
            ->with('role')
            ->withCount('classrooms')
            ->when($q !== '', fn ($qb) => $qb->where(fn ($w) =>
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
            ))
            ->when($role === 'staff', fn ($qb) => $qb->whereNotNull('role_id'))
            ->when($role === 'teacher', fn ($qb) => $qb->whereNull('role_id'))
            ->when($role && ! in_array($role, ['staff', 'teacher'], true),
                fn ($qb) => $qb->whereHas('role', fn ($r) => $r->where('key', $role)))
            ->when($status === 'active', fn ($qb) => $qb->where('is_active', true))
            ->when($status === 'suspended', fn ($qb) => $qb->where('is_active', false))
            ->when($sort === 'classrooms', fn ($qb) => $qb->orderByDesc('classrooms_count'))
            ->when($sort === 'last_login', fn ($qb) => $qb->orderByDesc('last_login_at'))
            ->when($sort === 'name', fn ($qb) => $qb->orderBy('name'))
            ->when(! in_array($sort, ['classrooms', 'last_login', 'name'], true),
                fn ($qb) => $qb->latest())
            ->paginate(20)
            ->withQueryString();

        $roles = Role::orderByDesc('is_system')->orderBy('name')->get();

        return view('admin.users', compact('users', 'q', 'role', 'status', 'sort', 'roles'));
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

        $workspaces = $user->workspaces()->orderBy('name')->get();

        $user->load('role');
        $roles = Role::orderByDesc('is_system')->orderBy('name')->get();

        return view('admin.user_show', compact('user', 'classrooms', 'assignmentCount', 'studentCount', 'workspaces', 'roles'));
    }

    public function assignRole(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('app.admin.cannotChangeSelf'));

        $data = $request->validate([
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $newRoleId = ! empty($data['role_id']) ? (int) $data['role_id'] : null;

        if ($this->wouldRemoveLastSuperAdmin($user, $newRoleId)) {
            return back()->with('error', __('app.roles.last_super_admin'));
        }

        $user->role_id = $newRoleId;
        $user->is_admin = $newRoleId !== null;
        $user->save();

        $roleName = $user->role()->first()?->name ?? __('app.roles.none');

        AuditLog::record('user.assign_role', $user, $user->email, ['role' => $roleName]);

        return back()->with('status', __('app.roles.assigned', ['name' => $user->name, 'role' => $roleName]));
    }

    /** Block stripping Super Admin from the only remaining super admin. */
    private function wouldRemoveLastSuperAdmin(User $user, ?int $newRoleId): bool
    {
        if (! $user->role()->first()?->isSuper()) {
            return false;
        }
        if ($newRoleId && Role::find($newRoleId)?->isSuper()) {
            return false;
        }

        return User::whereHas('role', fn ($r) => $r->where('key', Role::SUPER))->count() <= 1;
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
        // Never impersonate other back-office staff — that would let a lower
        // role inherit a higher one's access (e.g. Admin -> Super Admin).
        abort_if($user->is_admin, 403, __('app.admin.cannot_impersonate_staff'));
        abort_if(! $user->is_active, 422, __('app.admin.account_suspended'));

        $adminId = $request->user()->id;
        AuditLog::record('user.impersonate_start', $user, $user->email);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
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
        $request->session()->regenerate();

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

    public function security(Request $request)
    {
        $now = now();
        $since24h = $now->copy()->subDay();
        $since7d = $now->copy()->subDays(7);

        $stats = [
            'failed_24h' => LoginAttempt::where('success', false)->where('created_at', '>=', $since24h)->count(),
            'failed_7d' => LoginAttempt::where('success', false)->where('created_at', '>=', $since7d)->count(),
            'success_24h' => LoginAttempt::where('success', true)->where('created_at', '>=', $since24h)->count(),
            'success_7d' => LoginAttempt::where('success', true)->where('created_at', '>=', $since7d)->count(),
        ];

        // Top failing IPs in last 7 days — surface brute-force-style probes.
        $topFailingIps = LoginAttempt::query()
            ->selectRaw('ip, COUNT(*) as attempts, MAX(created_at) as last_seen')
            ->where('success', false)
            ->where('created_at', '>=', $since7d)
            ->whereNotNull('ip')
            ->groupBy('ip')
            ->orderByDesc('attempts')
            ->limit(10)
            ->get();

        $recentAttempts = LoginAttempt::query()
            ->with('user:id,name,email,is_admin')
            ->when($request->query('only') === 'failed', fn ($qb) => $qb->where('success', false))
            ->when($request->query('only') === 'success', fn ($qb) => $qb->where('success', true))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('admin.security', compact('stats', 'topFailingIps', 'recentAttempts'));
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
                ->with('role')
                ->withCount('classrooms')
                ->orderBy('id')
                ->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $u) {
                        fputcsv($out, [
                            $u->id,
                            $u->name,
                            $u->email,
                            $u->role?->name ?? 'teacher',
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
