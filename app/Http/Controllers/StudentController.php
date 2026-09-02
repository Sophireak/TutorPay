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
        $students = Student::query()
            ->ownedBy($request->user())
            ->search($request->string('search')->value())
            ->when(
                in_array($request->string('status')->value(), StudentStatus::values(), true),
                fn ($query) => $query->where('status', $request->string('status')->value())
            )
            ->withSum('fees as billed_total', 'amount')
            ->withSum('payments as paid_total', 'amount')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('students.index', [
            'students' => $students,
            'search' => $request->string('search')->value(),
            'status' => $request->string('status')->value(),
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
            'fees' => fn ($query) => $query->withSum('payments as payments_total', 'amount')->orderByDesc('period_month'),
            'payments' => fn ($query) => $query->with('fee')->latest('paid_on')->latest('id'),
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
