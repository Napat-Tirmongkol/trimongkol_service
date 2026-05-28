<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function create(Classroom $classroom)
    {
        $this->ensureOwner($classroom);

        return view('students.create', compact('classroom'));
    }

    public function store(Request $request, Classroom $classroom)
    {
        $this->ensureOwner($classroom);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'number' => 'nullable|string|max:10',
            'code' => 'nullable|string|max:32|alpha_dash',
        ]);

        $classroom->students()->create($data);

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.students.created'));
    }

    public function edit(Classroom $classroom, Student $student)
    {
        $this->ensureOwner($classroom);
        $this->ensureBelongs($classroom, $student);

        return view('students.edit', compact('classroom', 'student'));
    }

    public function update(Request $request, Classroom $classroom, Student $student)
    {
        $this->ensureOwner($classroom);
        $this->ensureBelongs($classroom, $student);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'number' => 'nullable|string|max:10',
            'code' => 'required|string|max:32|alpha_dash',
        ]);

        $student->update($data);

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.students.updated'));
    }

    public function destroy(Classroom $classroom, Student $student)
    {
        $this->ensureOwner($classroom);
        $this->ensureBelongs($classroom, $student);

        $student->delete();

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.students.deleted'));
    }

    private function ensureOwner(Classroom $classroom): void
    {
        abort_if($classroom->user_id !== auth()->id(), 403);
    }

    private function ensureBelongs(Classroom $classroom, Student $student): void
    {
        abort_if($student->classroom_id !== $classroom->id, 404);
    }
}
