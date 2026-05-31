<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function store(Request $request, Classroom $classroom, Assignment $assignment)
    {
        $this->ensureAccess($classroom);
        $this->ensureBelongs($classroom, $assignment);

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'score' => 'nullable|numeric|min:0|max:1000',
        ]);

        $student = Student::where('classroom_id', $classroom->id)
            ->where('id', $data['student_id'])
            ->firstOrFail();

        $submission = Submission::firstOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'submitted_at' => now(),
                'score' => $this->resolveScore($assignment, $data['score'] ?? null),
            ]
        );

        return back()->with('status', __('app.scan.success') . ': ' . $student->name);
    }

    public function update(Request $request, Classroom $classroom, Assignment $assignment, Submission $submission)
    {
        $this->ensureAccess($classroom);
        $this->ensureBelongs($classroom, $assignment);
        abort_if($submission->assignment_id !== $assignment->id, 404);

        $data = $request->validate([
            'score' => 'nullable|numeric|min:0|max:1000',
        ]);

        $submission->update(['score' => $data['score']]);

        return back()->with('status', __('app.submissions.updated'));
    }

    public function destroy(Classroom $classroom, Assignment $assignment, Submission $submission)
    {
        $this->ensureAccess($classroom);
        $this->ensureBelongs($classroom, $assignment);
        abort_if($submission->assignment_id !== $assignment->id, 404);

        $submission->delete();

        return back()->with('status', __('app.submissions.deleted'));
    }

    private function resolveScore(Assignment $assignment, ?int $providedScore): ?int
    {
        return match ($assignment->scoring_mode) {
            'fixed' => $assignment->default_score,
            'custom' => $providedScore,
            default => null,
        };
    }

    private function ensureAccess(Classroom $classroom): void
    {
        abort_unless($classroom->canBeAccessedBy(auth()->user()), 403);
    }

    private function ensureBelongs(Classroom $classroom, Assignment $assignment): void
    {
        abort_if($assignment->classroom_id !== $classroom->id, 404);
    }
}
