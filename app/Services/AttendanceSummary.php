<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Class-wide attendance aggregates for a given month — feeds the
 * attendance calendar page and the per-student profile.
 *
 * "Attendance rate" here counts present + late as showing up (a late
 * student is still in class), and absent + excused as missing. Excused
 * is tracked separately so a teacher can see whose absences are
 * justified vs not.
 */
class AttendanceSummary
{
    public CarbonImmutable $monthStart;
    public CarbonImmutable $monthEnd;

    public function __construct(public readonly Classroom $classroom, ?CarbonImmutable $month = null)
    {
        $month ??= CarbonImmutable::now();
        $this->monthStart = $month->startOfMonth();
        $this->monthEnd = $month->endOfMonth();
    }

    /**
     * @return array{
     *     month_label: string,
     *     prev_month: string,
     *     next_month: string,
     *     calendar: array<int, array<int, ?array{date: string, session_id: int, present: int, absent: int, late: int, excused: int, rate: ?float}>>,
     *     session_count: int,
     *     overall_rate_percent: ?float,
     *     top_absentees: array<int, array{student_id: int, name: string, absent_count: int}>,
     * }
     */
    public function build(): array
    {
        $sessions = $this->classroom->attendanceSessions()
            ->whereBetween('date', [$this->monthStart, $this->monthEnd])
            ->with('records')
            ->get();

        $byDate = $sessions->keyBy(fn (AttendanceSession $s) => $s->date->format('Y-m-d'));
        $studentCount = $this->classroom->students()->count();

        $dayBuckets = $sessions->map(function (AttendanceSession $s) use ($studentCount) {
            $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
            foreach ($s->records as $r) {
                if (isset($counts[$r->status])) {
                    $counts[$r->status]++;
                }
            }
            $showUp = $counts['present'] + $counts['late'];
            $accounted = $showUp + $counts['absent'] + $counts['excused'];
            $rate = $accounted > 0 ? round($showUp / $accounted * 100, 1) : null;

            return [
                'date' => $s->date->format('Y-m-d'),
                'session_id' => $s->id,
                'present' => $counts['present'],
                'absent' => $counts['absent'],
                'late' => $counts['late'],
                'excused' => $counts['excused'],
                'rate' => $rate,
            ];
        })->keyBy('date');

        $calendar = $this->buildCalendarGrid($dayBuckets);

        $rates = $dayBuckets->pluck('rate')->filter(fn ($v) => $v !== null);
        $overallRate = $rates->isNotEmpty() ? round($rates->avg(), 1) : null;

        $topAbsentees = $this->topAbsentees($sessions);

        return [
            'month_label' => $this->monthStart->translatedFormat('F Y'),
            'prev_month' => $this->monthStart->subMonth()->format('Y-m'),
            'next_month' => $this->monthStart->addMonth()->format('Y-m'),
            'calendar' => $calendar,
            'session_count' => $sessions->count(),
            'overall_rate_percent' => $overallRate,
            'top_absentees' => $topAbsentees,
        ];
    }

    /**
     * Per-student counts across the month — for the per-student profile card.
     *
     * @return array{present: int, absent: int, late: int, excused: int, rate_percent: ?float, total_sessions: int}
     */
    public function forStudent(int $studentId): array
    {
        $rows = AttendanceRecord::query()
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
            ->where('attendance_sessions.classroom_id', $this->classroom->id)
            ->whereBetween('attendance_sessions.date', [$this->monthStart, $this->monthEnd])
            ->where('attendance_records.student_id', $studentId)
            ->pluck('attendance_records.status');

        $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
        foreach ($rows as $status) {
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }
        $showUp = $counts['present'] + $counts['late'];
        $accounted = $showUp + $counts['absent'] + $counts['excused'];
        $rate = $accounted > 0 ? round($showUp / $accounted * 100, 1) : null;

        return [
            'present' => $counts['present'],
            'absent' => $counts['absent'],
            'late' => $counts['late'],
            'excused' => $counts['excused'],
            'rate_percent' => $rate,
            'total_sessions' => $accounted,
        ];
    }

    /**
     * Builds a Sunday-first grid of weeks containing the days of the month.
     * Cells outside the month are null so the view can render placeholders.
     *
     * @param Collection<string, array> $dayBuckets
     */
    private function buildCalendarGrid(Collection $dayBuckets): array
    {
        // Pad the first week so day-of-week aligns (0 = Sunday).
        $gridStart = $this->monthStart->startOfWeek(CarbonImmutable::SUNDAY);
        $gridEnd = $this->monthEnd->endOfWeek(CarbonImmutable::SATURDAY);

        $weeks = [];
        $current = $gridStart;
        while ($current <= $gridEnd) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $iso = $current->format('Y-m-d');
                $inMonth = $current->month === $this->monthStart->month;
                if (! $inMonth) {
                    $week[] = null;
                } else {
                    $week[] = $dayBuckets[$iso] ?? [
                        'date' => $iso,
                        'session_id' => null,
                        'present' => 0,
                        'absent' => 0,
                        'late' => 0,
                        'excused' => 0,
                        'rate' => null,
                    ];
                }
                $current = $current->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * @return array<int, array{student_id: int, name: string, absent_count: int}>
     */
    private function topAbsentees($sessions): array
    {
        if ($sessions->isEmpty()) {
            return [];
        }

        $sessionIds = $sessions->pluck('id');
        $absentCounts = AttendanceRecord::query()
            ->whereIn('attendance_session_id', $sessionIds)
            ->where('status', AttendanceRecord::STATUS_ABSENT)
            ->groupBy('student_id')
            ->selectRaw('student_id, COUNT(*) as absent_count')
            ->orderByDesc('absent_count')
            ->limit(5)
            ->get();

        if ($absentCounts->isEmpty()) {
            return [];
        }

        $students = $this->classroom->students()
            ->whereIn('id', $absentCounts->pluck('student_id'))
            ->get()
            ->keyBy('id');

        return $absentCounts->map(fn ($row) => [
            'student_id' => $row->student_id,
            'name' => $students[$row->student_id]?->name ?? '—',
            'absent_count' => (int) $row->absent_count,
        ])->all();
    }
}
