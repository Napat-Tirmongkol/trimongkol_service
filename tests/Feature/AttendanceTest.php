<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceSummary;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function classroomWithStudents(int $count = 5): array
    {
        $user = User::factory()->create();
        $classroom = Classroom::create(['user_id' => $user->id, 'name' => 'C']);
        $students = collect(range(1, $count))->map(fn ($i) => $classroom->students()->create([
            'name' => "Student $i",
            'number' => (string) $i,
        ]));
        return [$user, $classroom, $students];
    }

    public function test_today_creates_session_and_default_present_records_for_all_students(): void
    {
        [$user, $classroom, $students] = $this->classroomWithStudents(4);

        $this->actingAs($user)
            ->post(route('classrooms.attendance.today', $classroom))
            ->assertRedirect();

        $session = $classroom->attendanceSessions()->firstOrFail();
        $this->assertSame(now()->toDateString(), $session->date->toDateString());
        $this->assertSame(4, $session->records()->count());
        $this->assertSame(4, $session->records()->where('status', 'present')->count());
    }

    public function test_today_reuses_existing_session_for_the_same_day(): void
    {
        [$user, $classroom] = $this->classroomWithStudents(2);
        $this->actingAs($user)->post(route('classrooms.attendance.today', $classroom))->assertRedirect();
        $this->actingAs($user)->post(route('classrooms.attendance.today', $classroom))->assertRedirect();

        $this->assertSame(1, $classroom->attendanceSessions()->count(), 'should not create a second session for the same date');
    }

    public function test_update_persists_status_changes_per_student(): void
    {
        [$user, $classroom, $students] = $this->classroomWithStudents(3);
        $this->actingAs($user)->post(route('classrooms.attendance.today', $classroom));
        $session = $classroom->attendanceSessions()->firstOrFail();

        $payload = [
            'records' => [
                (string) $students[0]->id => ['status' => 'absent'],
                (string) $students[1]->id => ['status' => 'late', 'note' => 'late by 10 min'],
                (string) $students[2]->id => ['status' => 'present'],
            ],
        ];

        $this->actingAs($user)
            ->patch(route('classrooms.attendance.update', [$classroom, $session]), $payload)
            ->assertRedirect(route('classrooms.attendance.index', $classroom));

        $byStudent = $session->records()->get()->keyBy('student_id');
        $this->assertSame('absent', $byStudent[$students[0]->id]->status);
        $this->assertSame('late', $byStudent[$students[1]->id]->status);
        $this->assertSame('late by 10 min', $byStudent[$students[1]->id]->note);
        $this->assertSame('present', $byStudent[$students[2]->id]->status);
    }

    public function test_update_rejects_unknown_status_values(): void
    {
        [$user, $classroom, $students] = $this->classroomWithStudents(2);
        $this->actingAs($user)->post(route('classrooms.attendance.today', $classroom));
        $session = $classroom->attendanceSessions()->firstOrFail();

        $this->actingAs($user)
            ->patch(route('classrooms.attendance.update', [$classroom, $session]), [
                'records' => [(string) $students[0]->id => ['status' => 'invalid']],
            ])
            ->assertSessionHasErrors();
    }

    public function test_summary_computes_overall_rate_and_top_absentees(): void
    {
        [$user, $classroom, $students] = $this->classroomWithStudents(4);

        $session = AttendanceSession::create([
            'classroom_id' => $classroom->id,
            'date' => now()->toDateString(),
        ]);
        AttendanceRecord::create(['attendance_session_id' => $session->id, 'student_id' => $students[0]->id, 'status' => 'present']);
        AttendanceRecord::create(['attendance_session_id' => $session->id, 'student_id' => $students[1]->id, 'status' => 'late']);
        AttendanceRecord::create(['attendance_session_id' => $session->id, 'student_id' => $students[2]->id, 'status' => 'absent']);
        AttendanceRecord::create(['attendance_session_id' => $session->id, 'student_id' => $students[3]->id, 'status' => 'absent']);

        $summary = (new AttendanceSummary($classroom))->build();
        $this->assertSame(1, $summary['session_count']);
        $this->assertEqualsWithDelta(50.0, $summary['overall_rate_percent'], 0.1, '2 of 4 are present/late');
        $this->assertNotEmpty($summary['top_absentees']);
        $this->assertSame(1, $summary['top_absentees'][0]['absent_count']);
    }

    public function test_per_student_summary_counts_each_status_in_the_window(): void
    {
        [$user, $classroom, $students] = $this->classroomWithStudents(2);
        $target = $students[0];

        $dates = [now()->subDays(2), now()->subDays(1), now()];
        $statuses = ['present', 'absent', 'late'];
        foreach ($dates as $i => $date) {
            $session = AttendanceSession::create([
                'classroom_id' => $classroom->id,
                'date' => $date->toDateString(),
            ]);
            AttendanceRecord::create([
                'attendance_session_id' => $session->id,
                'student_id' => $target->id,
                'status' => $statuses[$i],
            ]);
        }

        $summary = (new AttendanceSummary($classroom))->forStudent($target->id);
        $this->assertSame(1, $summary['present']);
        $this->assertSame(1, $summary['absent']);
        $this->assertSame(1, $summary['late']);
        $this->assertSame(3, $summary['total_sessions']);
        $this->assertEqualsWithDelta(66.67, $summary['rate_percent'], 0.1);
    }

    public function test_calendar_grid_aligns_first_day_to_sunday_column(): void
    {
        $user = User::factory()->create();
        $classroom = Classroom::create(['user_id' => $user->id, 'name' => 'C']);

        // June 2026 starts on a Monday — first week's Sunday cell should be a null pad.
        $summary = (new AttendanceSummary($classroom, CarbonImmutable::parse('2026-06-15')))->build();
        $firstWeek = $summary['calendar'][0];
        $this->assertNull($firstWeek[0], 'Sunday before June 1 2026 (Monday) should be null padding');
        $this->assertNotNull($firstWeek[1], 'Monday June 1 should be a day cell');
        $this->assertSame('2026-06-01', $firstWeek[1]['date']);
    }

    public function test_destroy_removes_session_and_its_records(): void
    {
        [$user, $classroom, $students] = $this->classroomWithStudents(2);
        $this->actingAs($user)->post(route('classrooms.attendance.today', $classroom));
        $session = $classroom->attendanceSessions()->firstOrFail();
        $this->assertSame(2, AttendanceRecord::count());

        $this->actingAs($user)
            ->delete(route('classrooms.attendance.destroy', [$classroom, $session]))
            ->assertRedirect();

        $this->assertSame(0, AttendanceSession::count());
        $this->assertSame(0, AttendanceRecord::count(), 'cascade should remove records too');
    }

    public function test_access_denied_for_user_outside_workspace(): void
    {
        [$owner, $classroom] = $this->classroomWithStudents(1);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('classrooms.attendance.index', $classroom))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->post(route('classrooms.attendance.today', $classroom))
            ->assertForbidden();
    }

    public function test_student_history_renders_log_entries(): void
    {
        [$user, $classroom, $students] = $this->classroomWithStudents(1);
        $target = $students[0];

        $session = AttendanceSession::create([
            'classroom_id' => $classroom->id,
            'date' => now()->toDateString(),
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $target->id,
            'status' => 'absent',
            'note' => 'ป่วย',
        ]);

        $this->actingAs($user)
            ->get(route('classrooms.students.attendance', [$classroom, $target->id]))
            ->assertOk()
            ->assertSee($target->name)
            ->assertSee('ป่วย');
    }

    public function test_new_students_get_default_present_records_when_session_opened_later(): void
    {
        [$user, $classroom, $students] = $this->classroomWithStudents(2);

        $this->actingAs($user)->post(route('classrooms.attendance.today', $classroom));
        $session = $classroom->attendanceSessions()->firstOrFail();
        $this->assertSame(2, $session->records()->count());

        // A new student joins after the session was created.
        $classroom->students()->create(['name' => 'Late Joiner']);

        $this->actingAs($user)
            ->get(route('classrooms.attendance.show', [$classroom, $session]))
            ->assertOk();

        $this->assertSame(3, $session->fresh()->records()->count(), 'show should backfill a record for the new student');
    }
}
