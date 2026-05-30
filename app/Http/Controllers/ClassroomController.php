<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
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
        }

        return view('dashboard', compact('classrooms', 'workspace', 'quota'));
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
