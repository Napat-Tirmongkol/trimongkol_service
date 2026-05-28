<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Services\Gradebook;

class GradebookController extends Controller
{
    public function show(Classroom $classroom)
    {
        $this->ensureAccess($classroom);

        $gradebook = (new Gradebook($classroom))->build();

        return view('gradebook.show', compact('classroom', 'gradebook'));
    }

    public function export(Classroom $classroom)
    {
        $this->ensureAccess($classroom);

        $g = (new Gradebook($classroom))->build();
        $filename = preg_replace('/[^A-Za-z0-9_\-]/u', '_', $classroom->name . '_gradebook') . '.csv';

        return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($g) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $header = [
                __('app.students.col_number'),
                __('app.students.col_name'),
                __('app.students.col_code'),
            ];
            foreach ($g['assignments'] as $a) {
                $header[] = $a->name . ' (/' . rtrim(rtrim(number_format($a->effectiveMaxScore(), 2), '0'), '.') . ')';
            }
            $header[] = __('app.gradebook.col_submitted');
            $header[] = __('app.gradebook.col_weighted');
            fputcsv($out, $header);

            foreach ($g['students'] as $student) {
                $row = [$student->number, $student->name, $student->code];
                foreach ($g['assignments'] as $a) {
                    $cell = $g['cells'][$student->id][$a->id];
                    $row[] = $cell['score'] !== null ? rtrim(rtrim(number_format($cell['score'], 2), '0'), '.') : '';
                }
                $t = $g['student_totals'][$student->id];
                $row[] = $t['submitted'] . '/' . $t['total'];
                $row[] = $t['weighted_percent'] !== null ? $t['weighted_percent'] . '%' : '';
                fputcsv($out, $row);
            }

            // Footer: per-assignment stats
            $statsRow = ['', __('app.gradebook.row_avg'), ''];
            foreach ($g['assignments'] as $a) {
                $s = $g['assignment_stats'][$a->id];
                $statsRow[] = $s['avg'] !== null ? rtrim(rtrim(number_format($s['avg'], 2), '0'), '.') : '';
            }
            $statsRow[] = '';
            $statsRow[] = '';
            fputcsv($out, $statsRow);

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function ensureAccess(Classroom $classroom): void
    {
        abort_unless($classroom->canBeAccessedBy(auth()->user()), 403);
    }
}
