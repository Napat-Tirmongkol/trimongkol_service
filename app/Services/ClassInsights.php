<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Submission;

/**
 * Top-level class metrics built on top of Gradebook — what a teacher
 * wants to glance at: class average, submission/attendance rate, who's
 * leading, who needs help, and how active the room is right now.
 */
class ClassInsights
{
    public function __construct(public readonly Classroom $classroom)
    {
    }

    /**
     * @return array{
     *     has_data: bool,
     *     class_average_percent: ?float,
     *     submission_rate_percent: ?float,
     *     total_submissions: int,
     *     attendance_rate_percent: ?float,
     *     attendance_assignments: int,
     *     recent_scan_count: int,
     *     recent_window_days: int,
     *     top_students: array<int, array{id: int, name: string, percent: float}>,
     *     struggling_students: array<int, array{id: int, name: string, percent: float}>,
     * }
     */
    public function build(): array
    {
        $g = (new Gradebook($this->classroom))->build();
        $students = $g['students'];
        $assignments = $g['assignments'];

        if ($students->isEmpty() || $assignments->isEmpty()) {
            return $this->empty();
        }

        $totalSubmissions = 0;
        $percents = [];
        foreach ($g['student_totals'] as $t) {
            $totalSubmissions += $t['submitted'];
            if ($t['weighted_percent'] !== null) {
                $percents[] = $t['weighted_percent'];
            }
        }
        $possibleSlots = $students->count() * $assignments->count();
        $submissionRate = $possibleSlots > 0 ? round($totalSubmissions / $possibleSlots * 100, 1) : null;
        $classAverage = $percents ? round(array_sum($percents) / count($percents), 1) : null;

        // Attendance rate is computed only from attendance-mode assignments so
        // teachers can read it cleanly even when most of the room is graded work.
        $attendanceAssignments = $assignments->where('scoring_mode', 'attendance');
        $attendanceRate = null;
        if ($attendanceAssignments->isNotEmpty()) {
            $attendanceSlots = $students->count() * $attendanceAssignments->count();
            $attendanceMarked = 0;
            foreach ($attendanceAssignments as $a) {
                foreach ($students as $s) {
                    if ($g['cells'][$s->id][$a->id]['score'] !== null) {
                        $attendanceMarked++;
                    }
                }
            }
            $attendanceRate = $attendanceSlots > 0 ? round($attendanceMarked / $attendanceSlots * 100, 1) : null;
        }

        $recentDays = 7;
        $recentScans = Submission::whereIn('assignment_id', $assignments->pluck('id'))
            ->where('submitted_at', '>=', now()->subDays($recentDays))
            ->count();

        $ranked = collect($g['student_totals'])
            ->map(function ($t, $id) use ($students) {
                $student = $students->firstWhere('id', $id);
                return [
                    'id' => $id,
                    'name' => $student?->name ?? '—',
                    'percent' => $t['weighted_percent'],
                ];
            })
            ->filter(fn ($row) => $row['percent'] !== null)
            ->sortByDesc('percent')
            ->values();

        $top = $ranked->take(3)->all();
        // Struggling = bottom 3 with a meaningful gap from the average — avoids
        // calling someone "struggling" in a 3-student class where everyone is 90+.
        $bottomCandidates = $ranked->reverse()->values()->take(3)->all();
        $struggling = $classAverage !== null
            ? array_values(array_filter($bottomCandidates, fn ($r) => $r['percent'] < $classAverage))
            : $bottomCandidates;

        return [
            'has_data' => $totalSubmissions > 0,
            'class_average_percent' => $classAverage,
            'submission_rate_percent' => $submissionRate,
            'total_submissions' => $totalSubmissions,
            'attendance_rate_percent' => $attendanceRate,
            'attendance_assignments' => $attendanceAssignments->count(),
            'recent_scan_count' => $recentScans,
            'recent_window_days' => $recentDays,
            'top_students' => $top,
            'struggling_students' => $struggling,
        ];
    }

    private function empty(): array
    {
        return [
            'has_data' => false,
            'class_average_percent' => null,
            'submission_rate_percent' => null,
            'total_submissions' => 0,
            'attendance_rate_percent' => null,
            'attendance_assignments' => 0,
            'recent_scan_count' => 0,
            'recent_window_days' => 7,
            'top_students' => [],
            'struggling_students' => [],
        ];
    }
}
