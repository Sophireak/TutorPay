<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeeRequest;
use App\Models\Fee;
use App\Models\Student;
use App\Services\CollectionReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FeeController extends Controller
{
    public function index(Request $request, CollectionReport $report): View
    {
        $tutor = $request->user();
        $month = $this->resolveMonth($request->string('month')->value());

        $fees = Fee::query()
            ->ownedBy($tutor)
            ->forMonth($month)
            ->with('student')
            ->withSum('payments as payments_total', 'amount')
            ->join('students', 'students.id', '=', 'fees.student_id')
            ->orderBy('students.name')
            ->select('fees.*')
            ->get();

        return view('fees.index', [
            'month' => $month,
            'fees' => $fees,
            'summary' => $report->monthlySummary($tutor, $month),
            'students' => Student::query()->ownedBy($tutor)->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreFeeRequest $request): RedirectResponse
    {
        $student = Student::query()
            ->ownedBy($request->user())
            ->findOrFail($request->integer('student_id'));

        $fee = $student->fees()->create([
            'period_month' => $request->periodMonth(),
            'amount' => $request->validated('amount'),
            'due_date' => $request->validated('due_date'),
            'notes' => $request->validated('notes'),
        ]);

        return redirect()
            ->route('fees.index', ['month' => $fee->period_month->format('Y-m')])
            ->with('status', "Fee added for {$student->name}.");
    }

    public function destroy(Fee $fee): RedirectResponse
    {
        $this->authorize('delete', $fee);

        $month = $fee->period_month->format('Y-m');

        // Deleting a fee period removes its payments as well; keep the
        // whole financial record consistent.
        DB::transaction(function () use ($fee): void {
            $fee->delete();
        });

        return redirect()
            ->route('fees.index', ['month' => $month])
            ->with('status', 'Fee removed.');
    }

    private function resolveMonth(?string $month): Carbon
    {
        try {
            return $month
                ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Throwable) {
            return Carbon::now()->startOfMonth();
        }
    }
}
