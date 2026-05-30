<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Submission;
use App\Services\Billing;
use App\Services\ClassInsights;
use App\Services\CurrentWorkspace;
use App\Services\DemoWorkspaceSeeder;
use App\Services\PlanGate;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index()
    {
        $workspace = CurrentWorkspace::get();
        $classrooms = collect();
        $quota = null;
        $today = null;
        $recentActivity = collect();
        $todayAttendanceByClassroom = [];

        if ($workspace) {
            $classrooms = Classroom::where('workspace_id', $workspace->id)
                ->withCount(['students', 'assignments'])
                ->with(['assignments' => fn ($q) => $q->latest('created_at')->limit(1)])
                ->latest()
                ->get();

            $freeMode = Billing::freeMode();
            $plan = $workspace->currentPlan();
            $classroomLimit = $freeMode ? Billing::launchLimit('max_classrooms') : $plan->limit('max_classrooms');
            $memberLimit = $freeMode ? Billing::launchLimit('max_members') : $plan->limit('max_members');
            $studentLimit = $freeMode ? Billing::launchLimit('max_students_per_classroom') : $plan->limit('max_students_per_classroom');

            $quota = [
                'free_mode' => $freeMode,
                'plan_name' => $plan->name,
                'classroom_used' => $classrooms->count(),
                'classroom_limit' => $classroomLimit,
                'member_used' => $workspace->memberships()->count(),
                'member_limit' => $memberLimit,
                'student_limit_per_room' => $studentLimit,
            ];

            [$today, $recentActivity, $todayAttendanceByClassroom] = $this->buildTodayOverview($classrooms);
        }

        return view('dashboard', compact(
            'classrooms', 'workspace', 'quota', 'today', 'recentActivity', 'todayAttendanceByClassroom'
        ));
    }

    /**
     * Aggregate "what's happening right now" data for the dashboard:
     * how many classrooms have taken attendance today, today's submissions,
     * upcoming homework deadlines, plus a recent-activity feed of
     * submissions across all of this workspace's classrooms.
     *
     * @param  \Illuminate\Support\Collection<Classroom>  $classrooms
     * @return array{0: array, 1: \Illuminate\Support\Collection, 2: array<int, AttendanceSession>}
     */
    private function buildTodayOverview($classrooms): array
    {
        if ($classrooms->isEmpty()) {
            return [null, collect(), []];
        }

        $classroomIds = $classrooms->pluck('id');
        $todayStr = now()->toDateString();

        $todaySessions = AttendanceSession::whereIn('classroom_id', $classroomIds)
            ->whereDate('date', $todayStr)
            ->get()
            ->keyBy('classroom_id');

        $assignmentIds = \App\Models\Assignment::whereIn('classroom_id', $classroomIds)->pluck('id');

        $submissionsToday = Submission::whereIn('assignment_id', $assignmentIds)
            ->whereDate('submitted_at', $todayStr)
            ->count();

        $upcomingDue = \App\Models\Assignment::whereIn('classroom_id', $classroomIds)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->count();

        $today = [
            'classrooms_total' => $classrooms->count(),
            'classrooms_attended' => $todaySessions->count(),
            'submissions_today' => $submissionsToday,
            'upcoming_due_count' => $upcomingDue,
        ];

        $recentActivity = Submission::whereIn('assignment_id', $assignmentIds)
            ->with(['student:id,name,number,classroom_id', 'assignment:id,name,classroom_id,scoring_mode'])
            ->latest('submitted_at')
            ->limit(8)
            ->get();

        return [$today, $recentActivity, $todaySessions->all()];
    }

    public function create()
    {
        return view('classrooms.create');
    }

    public function store(Request $request)
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));

        if ($reason = PlanGate::reasonCannotAddClassroom($workspace)) {
            return redirect()->route('plans.index')
                ->with('error', $reason);
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'grade_level' => 'nullable|string|max:40',
            'description' => 'nullable|string|max:1000',
        ]);

        $classroom = Classroom::create([
            ...$data,
            'user_id' => auth()->id(),
            'workspace_id' => $workspace->id,
        ]);

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.classrooms.created'));
    }

    public function show(Classroom $classroom)
    {
        $this->ensureAccess($classroom);
        $classroom->load('students', 'assignments');
        $insights = (new ClassInsights($classroom))->build();

        return view('classrooms.show', compact('classroom', 'insights'));
    }

    public function edit(Classroom $classroom)
    {
        $this->ensureAccess($classroom);

        return view('classrooms.edit', compact('classroom'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->ensureAccess($classroom);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'grade_level' => 'nullable|string|max:40',
            'description' => 'nullable|string|max:1000',
        ]);

        $classroom->update($data);

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.classrooms.updated'));
    }

    public function destroy(Classroom $classroom)
    {
        $this->ensureAccess($classroom);
        $classroom->delete();

        return redirect()->route('dashboard')
            ->with('status', __('app.classrooms.deleted'));
    }

    public function demo(Request $request)
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));

        if ($reason = PlanGate::reasonCannotAddClassroom($workspace)) {
            return redirect()->route('plans.index')->with('error', $reason);
        }

        $classroom = (new DemoWorkspaceSeeder($workspace, $request->user()))->seed();

        if (! $classroom) {
            return redirect()->route('dashboard')->with('error', __('app.landing.demo_failed'));
        }

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.landing.demo_created'));
    }

    private function ensureAccess(Classroom $classroom): void
    {
        abort_unless($classroom->canBeAccessedBy(auth()->user()), 403);
    }
}
