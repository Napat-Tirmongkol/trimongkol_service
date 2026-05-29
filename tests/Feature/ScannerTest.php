<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScannerTest extends TestCase
{
    use RefreshDatabase;

    private function makeOwnerWithAssignment(string $mode = 'check', ?int $defaultScore = null): array
    {
        $user = User::factory()->create();
        $classroom = Classroom::create(['user_id' => $user->id, 'name' => 'Class A']);
        $student = $classroom->students()->create(['name' => 'Ann', 'number' => '1']);
        $assignment = $classroom->assignments()->create([
            'name' => 'HW1',
            'scoring_mode' => $mode,
            'default_score' => $defaultScore,
        ]);

        return [$user, $classroom, $assignment, $student];
    }

    public function test_scanner_dashboard_requires_auth(): void
    {
        $this->get('/scanner')->assertRedirect();
    }

    public function test_scan_check_mode_marks_submitted_without_score(): void
    {
        [$user, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment('check');

        Livewire::actingAs($user)
            ->test('scan-dashboard', ['assignment' => $assignment])
            ->set('code', $student->code)
            ->call('scan')
            ->assertSet('messageType', 'success');

        $sub = Submission::where('assignment_id', $assignment->id)->where('student_id', $student->id)->first();
        $this->assertNotNull($sub);
        $this->assertNull($sub->score);
    }

    public function test_scan_fixed_mode_saves_default_score(): void
    {
        [$user, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment('fixed', 5);

        Livewire::actingAs($user)
            ->test('scan-dashboard', ['assignment' => $assignment])
            ->set('code', $student->code)
            ->call('scan')
            ->assertSet('messageType', 'success')
            ->assertSet('lastScore', 5);

        $this->assertSame(5, (int) Submission::first()->score);
    }

    public function test_scan_custom_mode_prompts_for_score_and_saves_it(): void
    {
        [$user, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment('custom');

        $component = Livewire::actingAs($user)
            ->test('scan-dashboard', ['assignment' => $assignment])
            ->set('code', $student->code)
            ->call('scan')
            ->assertSet('askScore', true)
            ->assertSet('pendingStudentId', $student->id);

        $this->assertSame(0, Submission::count(), 'should not commit before saveScore');

        $component->set('pendingScore', '17')
            ->call('saveScore')
            ->assertSet('askScore', false);

        $this->assertSame(17, (int) Submission::first()->score);
    }

    public function test_scan_custom_mode_rejects_invalid_score(): void
    {
        [$user, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment('custom');

        Livewire::actingAs($user)
            ->test('scan-dashboard', ['assignment' => $assignment])
            ->set('code', $student->code)
            ->call('scan')
            ->set('pendingScore', 'abc')
            ->call('saveScore')
            ->assertSet('askScore', true);

        $this->assertSame(0, Submission::count());
    }

    public function test_scan_unknown_code_returns_error(): void
    {
        [$user, $classroom, $assignment] = $this->makeOwnerWithAssignment();

        Livewire::actingAs($user)
            ->test('scan-dashboard', ['assignment' => $assignment])
            ->set('code', 'ZZZZZZZZ')
            ->call('scan')
            ->assertSet('messageType', 'error');

        $this->assertSame(0, Submission::count());
    }

    public function test_scan_duplicate_does_not_double_record(): void
    {
        [$user, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment();

        $c = Livewire::actingAs($user)->test('scan-dashboard', ['assignment' => $assignment])
            ->set('code', $student->code)->call('scan');

        $c->set('code', $student->code)->call('scan')->assertSet('messageType', 'duplicate');

        $this->assertSame(1, Submission::count());
    }

    public function test_undo_deletes_submission(): void
    {
        [$user, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment();
        Submission::create(['assignment_id' => $assignment->id, 'student_id' => $student->id, 'submitted_at' => now()]);

        Livewire::actingAs($user)
            ->test('scan-dashboard', ['assignment' => $assignment])
            ->call('undo', $student->id)
            ->assertSet('messageType', 'undo');

        $this->assertSame(0, Submission::count());
    }

    public function test_bulk_import_parses_mixed_formats(): void
    {
        $user = User::factory()->create();
        $classroom = Classroom::create(['user_id' => $user->id, 'name' => 'C']);

        $roster = "1 Anna\n2, Ben\nChai\n  \n";

        $this->actingAs($user)
            ->post(route('classrooms.students.bulk.store', $classroom), ['roster' => $roster])
            ->assertRedirect(route('classrooms.show', $classroom));

        $students = Student::where('classroom_id', $classroom->id)->orderBy('id')->get();
        $this->assertSame(3, $students->count());
        $this->assertSame('Anna', $students[0]->name);
        $this->assertSame('1', $students[0]->number);
        $this->assertSame('Ben', $students[1]->name);
        $this->assertSame('2', $students[1]->number);
        $this->assertSame('Chai', $students[2]->name);
        $this->assertNull($students[2]->number);
    }

    public function test_print_page_renders(): void
    {
        $user = User::factory()->create();
        $classroom = Classroom::create(['user_id' => $user->id, 'name' => 'C']);
        $classroom->students()->create(['name' => 'Ann']);

        $this->actingAs($user)
            ->get(route('classrooms.students.print', $classroom))
            ->assertOk()
            ->assertSee('Ann');
    }

    public function test_export_csv_streams_with_bom(): void
    {
        [$user, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment('fixed', 10);
        Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'submitted_at' => now(),
            'score' => 10,
        ]);

        $response = $this->actingAs($user)->get(route('classrooms.assignments.export', [$classroom, $assignment]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $body = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'CSV should start with UTF-8 BOM');
        $this->assertStringContainsString('Ann', $body);
        $this->assertStringContainsString('10', $body);
    }

    public function test_submission_controller_create_and_delete(): void
    {
        [$user, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment('fixed', 5);

        $this->actingAs($user)
            ->post(route('classrooms.assignments.submissions.store', [$classroom, $assignment]), [
                'student_id' => $student->id,
            ])
            ->assertRedirect();

        $sub = Submission::firstOrFail();
        $this->assertSame(5, (int) $sub->score, 'fixed mode score is auto-applied');

        $this->actingAs($user)
            ->delete(route('classrooms.assignments.submissions.destroy', [$classroom, $assignment, $sub]))
            ->assertRedirect();

        $this->assertSame(0, Submission::count());
    }

    public function test_cannot_scan_into_another_users_assignment(): void
    {
        [$owner, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('classrooms.assignments.scan', [$classroom, $assignment]))
            ->assertForbidden();
    }

    public function test_make_admin_command_grants_and_revokes(): void
    {
        $u = User::factory()->create(['email' => 'foo@bar.com']);
        $this->assertFalse((bool) $u->is_admin);

        $this->artisan('app:make-admin', ['email' => 'foo@bar.com'])->assertExitCode(0);
        $this->assertTrue((bool) $u->fresh()->is_admin);

        $this->artisan('app:make-admin', ['email' => 'foo@bar.com', '--demote' => true])->assertExitCode(0);
        $this->assertFalse((bool) $u->fresh()->is_admin);
    }

    public function test_make_admin_command_fails_on_unknown_email(): void
    {
        $this->artisan('app:make-admin', ['email' => 'nobody@nowhere.com'])->assertExitCode(1);
    }

    public function test_assign_role_actually_persists(): void
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        $target = User::factory()->create();
        $roleId = \App\Models\Role::where('key', 'admin')->value('id');

        $this->actingAs($admin)
            ->post(route('admin.users.assign-role', $target), ['role_id' => $roleId])
            ->assertRedirect();

        $fresh = $target->fresh();
        $this->assertSame($roleId, $fresh->role_id, 'assignRole must persist role_id past the Fillable attribute');
        $this->assertTrue((bool) $fresh->is_admin, 'assigning a role flips is_admin on');
    }

    public function test_admin_emails_env_auto_promotes_on_authentication(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $this->assertFalse((bool) $user->is_admin);

        putenv('ADMIN_EMAILS=owner@example.com,ops@example.com');
        try {
            $this->actingAs($user)->get('/scanner')->assertOk();
        } finally {
            putenv('ADMIN_EMAILS');
        }

        $this->assertTrue((bool) $user->fresh()->is_admin);
    }

    public function test_admin_emails_env_does_not_promote_other_users(): void
    {
        $user = User::factory()->create(['email' => 'random@example.com']);

        putenv('ADMIN_EMAILS=owner@example.com');
        try {
            $this->actingAs($user)->get('/scanner')->assertOk();
        } finally {
            putenv('ADMIN_EMAILS');
        }

        $this->assertFalse((bool) $user->fresh()->is_admin);
    }

    public function test_gradebook_aggregates_weighted_percent_across_modes(): void
    {
        $user = User::factory()->create();
        $classroom = \App\Models\Classroom::create(['user_id' => $user->id, 'name' => 'Mix']);
        $alice = $classroom->students()->create(['name' => 'Alice']);
        $bob = $classroom->students()->create(['name' => 'Bob']);

        // Assignment 1: custom mode, max 20, weight 1, Alice 16/20=80%, Bob missing.
        $a1 = $classroom->assignments()->create([
            'name' => 'HW1', 'scoring_mode' => 'custom', 'max_score' => 20, 'weight' => 1,
        ]);
        Submission::create(['assignment_id' => $a1->id, 'student_id' => $alice->id, 'submitted_at' => now(), 'score' => 16]);

        // Assignment 2: fixed 5 points, weight 1, both submitted = 100%.
        $a2 = $classroom->assignments()->create([
            'name' => 'HW2', 'scoring_mode' => 'fixed', 'default_score' => 5, 'weight' => 1,
        ]);
        Submission::create(['assignment_id' => $a2->id, 'student_id' => $alice->id, 'submitted_at' => now(), 'score' => 5]);
        Submission::create(['assignment_id' => $a2->id, 'student_id' => $bob->id, 'submitted_at' => now(), 'score' => 5]);

        // Assignment 3: midterm — custom max 100 weight 2, Alice 90, Bob 70.
        $a3 = $classroom->assignments()->create([
            'name' => 'Midterm', 'scoring_mode' => 'custom', 'max_score' => 100, 'weight' => 2,
        ]);
        Submission::create(['assignment_id' => $a3->id, 'student_id' => $alice->id, 'submitted_at' => now(), 'score' => 90]);
        Submission::create(['assignment_id' => $a3->id, 'student_id' => $bob->id, 'submitted_at' => now(), 'score' => 70]);

        $g = (new \App\Services\Gradebook($classroom))->build();

        // Alice: (0.8 * 1 + 1.0 * 1 + 0.9 * 2) / 4 = 3.6/4 = 90.0
        // Bob:   (0   * 1 + 1.0 * 1 + 0.7 * 2) / 4 = 2.4/4 = 60.0
        $this->assertEqualsWithDelta(90.0, $g['student_totals'][$alice->id]['weighted_percent'], 0.01);
        $this->assertEqualsWithDelta(60.0, $g['student_totals'][$bob->id]['weighted_percent'], 0.01);

        // Assignment stats for HW1: only Alice submitted, 16
        $this->assertSame(16.0, $g['assignment_stats'][$a1->id]['avg']);
        $this->assertSame(1, $g['assignment_stats'][$a1->id]['submitted']);
        $this->assertSame(2, $g['assignment_stats'][$a1->id]['total_students']);
    }

    public function test_gradebook_check_mode_treats_submission_as_one_point(): void
    {
        $user = User::factory()->create();
        $classroom = \App\Models\Classroom::create(['user_id' => $user->id, 'name' => 'C']);
        $s = $classroom->students()->create(['name' => 'S']);
        $a = $classroom->assignments()->create(['name' => 'A', 'scoring_mode' => 'check']);
        Submission::create(['assignment_id' => $a->id, 'student_id' => $s->id, 'submitted_at' => now()]);

        $g = (new \App\Services\Gradebook($classroom))->build();

        $this->assertSame(1.0, $g['cells'][$s->id][$a->id]['score']);
        $this->assertSame(1.0, $g['cells'][$s->id][$a->id]['max_score']);
        $this->assertEqualsWithDelta(100.0, $g['student_totals'][$s->id]['weighted_percent'], 0.01);
    }

    public function test_gradebook_page_renders_with_inline_score_inputs(): void
    {
        [$user, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment('custom');
        Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'submitted_at' => now(),
            'score' => 42,
        ]);

        $this->actingAs($user)
            ->get(route('classrooms.gradebook', $classroom))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee($assignment->name)
            ->assertSee('42'); // raw score visible in input or cell
    }

    public function test_gradebook_csv_export_includes_assignment_columns_and_weighted_row(): void
    {
        [$user, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment('fixed', 10);
        Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'submitted_at' => now(),
            'score' => 10,
        ]);

        $response = $this->actingAs($user)->get(route('classrooms.gradebook.export', $classroom));
        $response->assertOk();
        $body = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('HW1', $body);
        $this->assertStringContainsString('Ann', $body);
        $this->assertStringContainsString('100%', $body); // weighted result
    }

    public function test_student_profile_renders_per_assignment_breakdown(): void
    {
        [$user, $classroom, $assignment, $student] = $this->makeOwnerWithAssignment('custom');
        Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'submitted_at' => now(),
            'score' => 73,
        ]);

        $this->actingAs($user)
            ->get(route('classrooms.students.show', [$classroom, $student]))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('HW1')
            ->assertSee('73');
    }

    public function test_gradebook_blocked_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $classroom = \App\Models\Classroom::create(['user_id' => $owner->id, 'name' => 'X']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('classrooms.gradebook', $classroom))
            ->assertForbidden();
    }

    public function test_bulk_route_still_resolves_after_adding_show_route(): void
    {
        $user = User::factory()->create();
        $classroom = \App\Models\Classroom::create(['user_id' => $user->id, 'name' => 'C']);

        // Ensure /students/bulk is matched as the bulk page, not the show route with student='bulk'.
        $this->actingAs($user)
            ->get(route('classrooms.students.bulk', $classroom))
            ->assertOk();
    }
}
