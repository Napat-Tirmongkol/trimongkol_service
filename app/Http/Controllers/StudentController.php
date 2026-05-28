<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Services\Gradebook;
use App\Services\PlanGate;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function create(Classroom $classroom)
    {
        $this->ensureAccess($classroom);

        return view('students.create', compact('classroom'));
    }

    public function show(Classroom $classroom, Student $student)
    {
        $this->ensureAccess($classroom);
        $this->ensureBelongs($classroom, $student);

        $gradebook = (new Gradebook($classroom))->build();

        return view('students.show', compact('classroom', 'student', 'gradebook'));
    }

    public function store(Request $request, Classroom $classroom)
    {
        $this->ensureAccess($classroom);

        if ($reason = PlanGate::reasonCannotAddStudent($classroom)) {
            return redirect()->route('plans.index')
                ->with('error', $reason);
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'number' => 'nullable|string|max:10',
            'code' => 'nullable|string|max:32|alpha_dash',
        ]);

        $classroom->students()->create($data);

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.students.created'));
    }

    public function edit(Classroom $classroom, Student $student)
    {
        $this->ensureAccess($classroom);
        $this->ensureBelongs($classroom, $student);

        return view('students.edit', compact('classroom', 'student'));
    }

    public function update(Request $request, Classroom $classroom, Student $student)
    {
        $this->ensureAccess($classroom);
        $this->ensureBelongs($classroom, $student);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'number' => 'nullable|string|max:10',
            'code' => 'required|string|max:32|alpha_dash',
        ]);

        $student->update($data);

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.students.updated'));
    }

    public function destroy(Classroom $classroom, Student $student)
    {
        $this->ensureAccess($classroom);
        $this->ensureBelongs($classroom, $student);

        $student->delete();

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.students.deleted'));
    }

    public function bulkCreate(Classroom $classroom)
    {
        $this->ensureAccess($classroom);

        return view('students.bulk', compact('classroom'));
    }

    public function bulkStore(Request $request, Classroom $classroom)
    {
        $this->ensureAccess($classroom);

        $data = $request->validate([
            'roster' => 'required|string|max:50000',
        ]);

        // Pre-flight: peek at the roster size and refuse the whole batch
        // when it would push past the plan limit, rather than importing
        // a partial set.
        $lineCount = count(array_filter(preg_split('/\R/', $data['roster']), fn ($l) => trim($l) !== ''));
        if ($reason = PlanGate::reasonCannotAddStudent($classroom, $lineCount)) {
            return redirect()->route('plans.index')
                ->with('error', $reason);
        }

        $created = 0;
        $skipped = 0;

        foreach (preg_split('/\R/', $data['roster']) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Try "<num><sep><name>" where sep is comma, tab, dot, ), :, or whitespace.
            if (preg_match('/^(\d{1,4})[\s,\t.):]+(.+)$/u', $line, $m)) {
                $number = $m[1];
                $name = trim($m[2]);
            } else {
                $number = '';
                $name = trim($line);
            }

            if ($name === '' || mb_strlen($name) > 120) {
                $skipped++;
                continue;
            }

            $classroom->students()->create([
                'name' => $name,
                'number' => $number !== '' ? $number : null,
            ]);
            $created++;
        }

        return redirect()->route('classrooms.show', $classroom)
            ->with('status', __('app.students.bulk_done', ['created' => $created, 'skipped' => $skipped]));
    }

    public function print(Classroom $classroom)
    {
        $this->ensureAccess($classroom);

        $students = $classroom->students()->get();

        return view('students.print', compact('classroom', 'students'));
    }

    public function printQr(Classroom $classroom, Student $student)
    {
        $this->ensureAccess($classroom);
        $this->ensureBelongs($classroom, $student);

        return view('students.qr', compact('classroom', 'student'));
    }

    private function ensureAccess(Classroom $classroom): void
    {
        abort_unless($classroom->canBeAccessedBy(auth()->user()), 403);
    }

    private function ensureBelongs(Classroom $classroom, Student $student): void
    {
        abort_if($student->classroom_id !== $classroom->id, 404);
    }
}
