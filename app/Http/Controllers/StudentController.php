<?php

namespace App\Http\Controllers;

use App\Enums\StudentStatus;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $tutor = $request->user();

        $gradeFilter = $request->string('grade')->value();
        $statusFilter = $request->string('status')->value();

        $students = Student::query()
            ->ownedBy($tutor)
            ->search($request->string('search')->value())
            ->when(
                $gradeFilter !== '',
                fn ($query) => $query->where('grade', $gradeFilter)
            )
            ->when(
                in_array($statusFilter, StudentStatus::values(), true),
                fn ($query) => $query->where('status', $statusFilter)
            )
            ->withSum('fees as billed_total', 'amount')
            ->withSum('payments as paid_total', 'amount')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Distinct grades for the filter dropdown (only this tutor's students)
        $grades = Student::query()
            ->ownedBy($tutor)
            ->whereNotNull('grade')
            ->where('grade', '!=', '')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade');

        return view('students.index', [
            'students' => $students,
            'grades' => $grades,
            'search' => $request->string('search')->value(),
            'grade' => $gradeFilter,
            'status' => $statusFilter,
        ]);
    }

    public function create(): View
    {
        return view('students.create', [
            'student' => new Student(['status' => StudentStatus::Active, 'enrolled_on' => now()]),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $student = $request->user()->students()->create($request->validated());

        return redirect()
            ->route('students.show', $student)
            ->with('status', 'Student added.');
    }

    public function show(Request $request, Student $student): View
    {
        $this->authorize('view', $student);

        $student->load([
            'fees' => fn ($query) => $query
                ->with('payments')
                ->orderByDesc('period_month'),
        ]);

        return view('students.show', [
            'student' => $student,
        ]);
    }

    public function edit(Student $student): View
    {
        $this->authorize('update', $student);

        return view('students.edit', [
            'student' => $student,
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $student->update($request->validated());

        return redirect()
            ->route('students.show', $student)
            ->with('status', 'Student updated.');
    }

    /**
     * Toggle a student's active / inactive status.
     */
    public function toggle(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $student->update([
            'status' => $student->isActive() ? StudentStatus::Inactive : StudentStatus::Active,
        ]);

        $label = $student->fresh()->isActive() ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('status', "{$student->name} has been {$label}.");
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        if ($student->fees()->exists()) {
            return redirect()
                ->route('students.show', $student)
                ->with('status', 'This student has billing history and cannot be deleted. Mark them inactive instead.');
        }

        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('status', 'Student removed.');
    }
}
