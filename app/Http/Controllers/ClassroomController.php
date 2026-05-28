<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::where('user_id', auth()->id())
            ->withCount('students')
            ->latest()
            ->get();

        return view('dashboard', compact('classrooms'));
    }

    public function create()
    {
        return view('classrooms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'grade_level' => 'nullable|string|max:40',
            'description' => 'nullable|string|max:1000',
        ]);

        $classroom = Classroom::create([
            ...$data,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.classrooms.created'));
    }

    public function show(Classroom $classroom)
    {
        $this->ensureOwner($classroom);
        $classroom->load('students', 'assignments');

        return view('classrooms.show', compact('classroom'));
    }

    public function edit(Classroom $classroom)
    {
        $this->ensureOwner($classroom);

        return view('classrooms.edit', compact('classroom'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->ensureOwner($classroom);

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
        $this->ensureOwner($classroom);
        $classroom->delete();

        return redirect()->route('dashboard')
            ->with('status', __('app.classrooms.deleted'));
    }

    private function ensureOwner(Classroom $classroom): void
    {
        abort_if($classroom->user_id !== auth()->id(), 403);
    }
}
