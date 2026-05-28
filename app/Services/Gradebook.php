<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Student;

/**
 * Computes per-student and per-assignment grade aggregates for a classroom.
 *
 * The data structure returned by `build()` is:
 *   [
 *     'students' => Collection<Student>,
 *     'assignments' => Collection<Assignment>,
 *     'cells' => array<int student_id, array<int assignment_id, array{
 *         submission_id: ?int,
 *         score: ?float,             // raw earned score (1.0 for check-mode submitted, null if missing)
 *         max_score: float,          // effective max for the assignment
 *         percent: ?float,           // 0..100, null if missing
 *     }>>,
 *     'student_totals' => array<int student_id, array{
 *         submitted: int,
 *         total: int,
 *         weighted_percent: ?float,  // 0..100 (null if no graded assignments)
 *     }>,
 *     'assignment_stats' => array<int assignment_id, array{
 *         submitted: int,
 *         total_students: int,
 *         avg: ?float,        // average earned score (raw, not percent)
 *         min: ?float,
 *         max: ?float,
 *         avg_percent: ?float, // average percent across submissions
 *     }>,
 *   ]
 */
class Gradebook
{
    public function __construct(public readonly Classroom $classroom)
    {
    }

    public function build(): array
    {
        $students = $this->classroom->students()->get();
        $assignments = $this->classroom->assignments()
            ->orderBy('created_at')
            ->get();

        $submissionsByAssignment = [];
        foreach ($assignments as $a) {
            $submissionsByAssignment[$a->id] = $a->submissions()->get()->keyBy('student_id');
        }

        $cells = [];
        $studentTotals = [];

        foreach ($students as $student) {
            $cells[$student->id] = [];
            $weightedSumNumer = 0.0;
            $weightedSumDenom = 0.0;
            $submittedCount = 0;

            foreach ($assignments as $a) {
                $sub = $submissionsByAssignment[$a->id][$student->id] ?? null;
                $earned = $a->earnedScore($sub);
                $max = $a->effectiveMaxScore();
                $percent = ($earned !== null && $max > 0) ? ($earned / $max * 100.0) : null;

                $cells[$student->id][$a->id] = [
                    'submission_id' => $sub?->id,
                    'score' => $earned,
                    'max_score' => $max,
                    'percent' => $percent,
                ];

                if ($sub) {
                    $submittedCount++;
                }

                $w = (float) ($a->weight ?? 1.0);
                if ($w > 0) {
                    $weightedSumDenom += $w;
                    if ($earned !== null && $max > 0) {
                        $weightedSumNumer += ($earned / $max) * $w;
                    }
                    // Missing submission counts as 0 toward the denominator.
                }
            }

            $studentTotals[$student->id] = [
                'submitted' => $submittedCount,
                'total' => $assignments->count(),
                'weighted_percent' => $weightedSumDenom > 0 ? round($weightedSumNumer / $weightedSumDenom * 100.0, 2) : null,
            ];
        }

        $assignmentStats = [];
        foreach ($assignments as $a) {
            $scores = [];
            $percents = [];
            $submittedCount = 0;
            foreach ($students as $student) {
                $cell = $cells[$student->id][$a->id];
                if ($cell['score'] !== null) {
                    $scores[] = $cell['score'];
                    $percents[] = $cell['percent'];
                    $submittedCount++;
                }
            }
            $assignmentStats[$a->id] = [
                'submitted' => $submittedCount,
                'total_students' => $students->count(),
                'avg' => $scores ? round(array_sum($scores) / count($scores), 2) : null,
                'min' => $scores ? round(min($scores), 2) : null,
                'max' => $scores ? round(max($scores), 2) : null,
                'avg_percent' => $percents ? round(array_sum($percents) / count($percents), 2) : null,
            ];
        }

        return [
            'students' => $students,
            'assignments' => $assignments,
            'cells' => $cells,
            'student_totals' => $studentTotals,
            'assignment_stats' => $assignmentStats,
        ];
    }
}
