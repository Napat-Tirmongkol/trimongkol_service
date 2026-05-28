<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Classroom;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function create(Classroom $classroom)
    {
        $this->ensureOwner($classroom);
        return view('assignments.create', compact('classroom'));
    }

    public function store(Request $request, Classroom $classroom)
    {
        $this->ensureOwner($classroom);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'due_date' => 'nullable|date',
            'scoring_mode' => 'required|in:check,fixed,custom',
            'default_score' => 'nullable|integer|min:0|max:100',
            'description' => 'nullable|string|max:1000',
        ]);

        $assignment = $classroom->assignments()->create($data);

        return redirect()->route('classrooms.assignments.show', [$classroom, $assignment])
            ->with('status', __('app.assignments.created'));
    }

    public function show(Classroom $classroom, Assignment $assignment)
    {
        $this->ensureOwner($classroom);
        $this->ensureBelongs($classroom, $assignment);

        return view('assignments.show', compact('classroom', 'assignment'));
    }

    public function edit(Classroom $classroom, Assignment $assignment)
    {
        $this->ensureOwner($classroom);
        $this->ensureBelongs($classroom, $assignment);

        return view('assignments.edit', compact('classroom', 'assignment'));
    }

    public function update(Request $request, Classroom $classroom, Assignment $assignment)
    {
        $this->ensureOwner($classroom);
        $this->ensureBelongs($classroom, $assignment);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'due_date' => 'nullable|date',
            'scoring_mode' => 'required|in:check,fixed,custom',
            'default_score' => 'nullable|integer|min:0|max:100',
            'description' => 'nullable|string|max:1000',
        ]);

        $assignment->update($data);

        return redirect()->route('classrooms.assignments.show', [$classroom, $assignment])
            ->with('status', __('app.assignments.updated'));
    }

    public function destroy(Classroom $classroom, Assignment $assignment)
    {
        $this->ensureOwner($classroom);
        $this->ensureBelongs($classroom, $assignment);

        $assignment->delete();

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.assignments.deleted'));
    }

    public function scan(Classroom $classroom, Assignment $assignment)
    {
        $this->ensureOwner($classroom);
        $this->ensureBelongs($classroom, $assignment);

        return view('assignments.scan', compact('classroom', 'assignment'));
    }

    private function ensureOwner(Classroom $classroom): void
    {
        abort_if($classroom->user_id !== auth()->id(), 403);
    }

    private function ensureBelongs(Classroom $classroom, Assignment $assignment): void
    {
        abort_if($assignment->classroom_id !== $classroom->id, 404);
    }
}
