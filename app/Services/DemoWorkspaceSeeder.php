<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Submission;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;

/**
 * Seeds a brand-new workspace with one demo classroom plus enough
 * students, assignments, and submissions to make the dashboard, gradebook,
 * and insights views feel populated on first login.
 *
 * Designed to fail safe — if anything throws (locale missing, FK race,
 * disk full) the surrounding registration flow must still succeed.
 */
class DemoWorkspaceSeeder
{
    public function __construct(
        public readonly Workspace $workspace,
        public readonly User $owner,
    ) {}

    public function seed(): ?Classroom
    {
        try {
            return $this->doSeed();
        } catch (\Throwable $e) {
            Log::warning('Demo workspace seed failed', [
                'workspace_id' => $this->workspace->id,
                'owner_id' => $this->owner->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function doSeed(): Classroom
    {
        $classroom = Classroom::create([
            'name' => __('app.demo.classroom_name'),
            'grade_level' => __('app.demo.classroom_grade'),
            'description' => __('app.demo.classroom_description'),
            'user_id' => $this->owner->id,
            'workspace_id' => $this->workspace->id,
        ]);

        $studentNames = (array) __('app.demo.student_names');
        $students = [];
        foreach ($studentNames as $i => $name) {
            $students[] = $classroom->students()->create([
                'name' => $name,
                'number' => (string) ($i + 1),
            ]);
        }

        // Yesterday's attendance — full house, demonstrates attendance mode.
        $attendance = $classroom->assignments()->create([
            'name' => __('app.demo.attendance_name'),
            'category' => 'other',
            'scoring_mode' => 'attendance',
            'description' => __('app.demo.attendance_description'),
        ]);
        foreach ($students as $student) {
            Submission::create([
                'assignment_id' => $attendance->id,
                'student_id' => $student->id,
                'submitted_at' => now()->subDay(),
            ]);
        }

        // Homework — partial submissions across the score range so the
        // insights card has a real average + a "needs attention" entry.
        $homework = $classroom->assignments()->create([
            'name' => __('app.demo.homework_name'),
            'category' => 'homework',
            'scoring_mode' => 'custom',
            'max_score' => 10,
            'description' => __('app.demo.homework_description'),
        ]);
        $scores = [9, 8, 6];
        foreach ($scores as $i => $score) {
            if (! isset($students[$i])) {
                break;
            }
            Submission::create([
                'assignment_id' => $homework->id,
                'student_id' => $students[$i]->id,
                'submitted_at' => now()->subHours(2),
                'score' => $score,
            ]);
        }

        $this->seedAttendance($classroom, $students);

        return $classroom;
    }

    /**
     * Two demo attendance days so the calendar lights up and the per-student
     * profile already has an absence story to read.
     *
     * @param array<int, \App\Models\Student> $students
     */
    private function seedAttendance(Classroom $classroom, array $students): void
    {
        $days = [
            ['offset' => 1, 'absents' => [0]],   // yesterday: one student absent
            ['offset' => 2, 'absents' => [1]],   // day before: a different student absent
        ];

        foreach ($days as $day) {
            $session = AttendanceSession::create([
                'classroom_id' => $classroom->id,
                'date' => now()->subDays($day['offset'])->toDateString(),
                'created_by' => $this->owner->id,
            ]);

            foreach ($students as $i => $student) {
                $status = in_array($i, $day['absents'], true)
                    ? AttendanceRecord::STATUS_ABSENT
                    : AttendanceRecord::STATUS_PRESENT;
                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'student_id' => $student->id,
                    'status' => $status,
                    'marked_at' => now()->subDays($day['offset']),
                ]);
            }
        }
    }
}
