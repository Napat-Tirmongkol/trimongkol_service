<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Services\AttendanceSummary;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request, Classroom $classroom)
    {
        $this->ensureAccess($classroom);

        $monthInput = $request->query('month');
        $month = $this->parseMonth($monthInput);

        $summary = (new AttendanceSummary($classroom, $month))->build();
        $todaySession = $classroom->attendanceSessions()->whereDate('date', now()->toDateString())->first();

        return view('attendance.index', compact('classroom', 'summary', 'todaySession'));
    }

    public function today(Request $request, Classroom $classroom)
    {
        $this->ensureAccess($classroom);

        $session = $classroom->attendanceSessions()
            ->whereDate('date', now()->toDateString())
            ->first();

        if (! $session) {
            $session = $this->createSessionWithDefaultRecords($classroom, now()->toDateString(), $request->user()->id);
        }

        return redirect()->route('classrooms.attendance.show', [$classroom, $session]);
    }

    public function show(Classroom $classroom, AttendanceSession $session)
    {
        $this->ensureAccess($classroom);
        $this->ensureBelongs($classroom, $session);

        $students = $classroom->students()->get();
        $records = $session->records()->get()->keyBy('student_id');

        // Auto-create records for new students who joined after the session was made.
        foreach ($students as $student) {
            if (! isset($records[$student->id])) {
                $records[$student->id] = $session->records()->create([
                    'student_id' => $student->id,
                    'status' => AttendanceRecord::STATUS_PRESENT,
                ]);
            }
        }

        return view('attendance.show', compact('classroom', 'session', 'students', 'records'));
    }

    public function update(Request $request, Classroom $classroom, AttendanceSession $session)
    {
        $this->ensureAccess($classroom);
        $this->ensureBelongs($classroom, $session);

        $data = $request->validate([
            'records' => 'required|array',
            'records.*.status' => ['required', Rule::in(AttendanceRecord::STATUSES)],
            'records.*.note' => 'nullable|string|max:200',
            'note' => 'nullable|string|max:200',
        ]);

        $validIds = $classroom->students()->pluck('id')->all();

        foreach ($data['records'] as $studentId => $row) {
            $studentId = (int) $studentId;
            if (! in_array($studentId, $validIds, true)) {
                continue;
            }
            $session->records()->updateOrCreate(
                ['student_id' => $studentId],
                [
                    'status' => $row['status'],
                    'note' => $row['note'] ?? null,
                    'marked_at' => now(),
                ]
            );
        }

        $session->update(['note' => $data['note'] ?? $session->note]);

        return redirect()->route('classrooms.attendance.index', $classroom)
            ->with('status', __('app.attendance.saved', ['date' => $session->date->format('d M Y')]));
    }

    public function destroy(Classroom $classroom, AttendanceSession $session)
    {
        $this->ensureAccess($classroom);
        $this->ensureBelongs($classroom, $session);

        $label = $session->date->format('d M Y');
        $session->delete();

        return redirect()->route('classrooms.attendance.index', $classroom)
            ->with('status', __('app.attendance.deleted', ['date' => $label]));
    }

    public function studentHistory(Request $request, Classroom $classroom, int $studentId)
    {
        $this->ensureAccess($classroom);
        $student = $classroom->students()->where('id', $studentId)->firstOrFail();

        $month = $this->parseMonth($request->query('month'));
        $summary = (new AttendanceSummary($classroom, $month))->forStudent($student->id);

        $records = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereHas('session', fn ($q) => $q->where('classroom_id', $classroom->id)
                ->whereBetween('date', [$month->startOfMonth(), $month->endOfMonth()]))
            ->with('session:id,date,note')
            ->get()
            ->sortByDesc(fn ($r) => $r->session->date)
            ->values();

        return view('attendance.student-history', compact('classroom', 'student', 'summary', 'records', 'month'));
    }

    private function createSessionWithDefaultRecords(Classroom $classroom, string $date, int $userId): AttendanceSession
    {
        $session = $classroom->attendanceSessions()->create([
            'date' => $date,
            'created_by' => $userId,
        ]);

        $students = $classroom->students()->get();
        foreach ($students as $student) {
            $session->records()->create([
                'student_id' => $student->id,
                'status' => AttendanceRecord::STATUS_PRESENT,
            ]);
        }

        return $session;
    }

    private function parseMonth(?string $input): CarbonImmutable
    {
        if (! $input || ! preg_match('/^\d{4}-\d{2}$/', $input)) {
            return CarbonImmutable::now();
        }
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $input.'-01');
        } catch (\Throwable) {
            return CarbonImmutable::now();
        }
    }

    private function ensureAccess(Classroom $classroom): void
    {
        abort_unless($classroom->canBeAccessedBy(auth()->user()), 403);
    }

    private function ensureBelongs(Classroom $classroom, AttendanceSession $session): void
    {
        abort_if($session->classroom_id !== $classroom->id, 404);
    }
}
