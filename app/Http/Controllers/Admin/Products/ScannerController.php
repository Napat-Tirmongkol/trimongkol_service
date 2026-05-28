<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Submission;
use App\Services\AuditLog;
use Illuminate\Http\Request;

class ScannerController extends Controller
{
    public function dashboard()
    {
        $now = now();

        $stats = [
            'classrooms' => Classroom::count(),
            'assignments' => Assignment::count(),
            'submissions' => Submission::count(),
            'submissions_today' => Submission::whereDate('created_at', $now->toDateString())->count(),
            'submissions_week' => Submission::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'classrooms_week' => Classroom::where('created_at', '>=', $now->copy()->subDays(7))->count(),
        ];

        $topClassrooms = Classroom::query()
            ->with('user:id,name,email')
            ->withCount(['students', 'assignments'])
            ->orderByDesc('assignments_count')
            ->limit(5)
            ->get();

        return view('admin.products.scanner.dashboard', compact('stats', 'topClassrooms'));
    }

    public function classrooms(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $classrooms = Classroom::query()
            ->with('user:id,name,email')
            ->withCount(['students', 'assignments'])
            ->when($q !== '', fn ($qb) => $qb->where(fn ($w) =>
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%")
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$q}%"))
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.products.scanner.classrooms', compact('classrooms', 'q'));
    }

    public function showClassroom(Classroom $classroom)
    {
        $classroom->load('user:id,name,email');
        $classroom->loadCount(['students', 'assignments']);
        $assignments = $classroom->assignments()->withCount('submissions')->limit(20)->get();

        return view('admin.products.scanner.classroom_show', compact('classroom', 'assignments'));
    }

    public function destroyClassroom(Classroom $classroom)
    {
        $label = $classroom->name . ' (' . optional($classroom->user)->email . ')';
        $meta = [
            'students' => $classroom->students()->count(),
            'assignments' => $classroom->assignments()->count(),
            'owner_id' => $classroom->user_id,
        ];

        $classroom->delete();

        AuditLog::record('classroom.delete', null, $label, $meta);

        return redirect()->route('admin.scanner.classrooms')
            ->with('status', __('app.admin.classroom_deleted', ['name' => $classroom->name]));
    }
}
