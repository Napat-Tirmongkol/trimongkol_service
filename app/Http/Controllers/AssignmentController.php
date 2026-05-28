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

    public function export(Classroom $classroom, Assignment $assignment)
    {
        $this->ensureOwner($classroom);
        $this->ensureBelongs($classroom, $assignment);

        $students = $classroom->students()->get();
        $subs = $assignment->submissions()->get()->keyBy('student_id');

        $filename = preg_replace('/[^A-Za-z0-9_\-]/u', '_', $classroom->name . '_' . $assignment->name) . '.csv';

        return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($students, $subs, $assignment) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders Thai correctly
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('app.students.col_number'),
                __('app.students.col_name'),
                __('app.students.col_code'),
                __('app.scan.status'),
                __('app.scan.submitted_at'),
                __('app.export.score'),
            ]);

            foreach ($students as $student) {
                $sub = $subs->get($student->id);
                fputcsv($out, [
                    $student->number,
                    $student->name,
                    $student->code,
                    $sub ? __('app.scan.submitted_short') : __('app.scan.pending'),
                    $sub?->submitted_at?->format('Y-m-d H:i:s'),
                    $sub?->score,
                ]);
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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
