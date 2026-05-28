<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Services\CurrentWorkspace;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index()
    {
        $workspace = CurrentWorkspace::get();
        $classrooms = collect();

        if ($workspace) {
            $classrooms = Classroom::where('workspace_id', $workspace->id)
                ->withCount(['students', 'assignments'])
                ->with(['assignments' => fn ($q) => $q->latest('created_at')->limit(1)])
                ->latest()
                ->get();
        }

        return view('dashboard', compact('classrooms', 'workspace'));
    }

    public function create()
    {
        return view('classrooms.create');
    }

    public function store(Request $request)
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));

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

        return view('classrooms.show', compact('classroom'));
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

    private function ensureAccess(Classroom $classroom): void
    {
        abort_unless($classroom->canBeAccessedBy(auth()->user()), 403);
    }
}
