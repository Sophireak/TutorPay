<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use App\Services\CollectionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CollectionReport $report): View
    {
        $tutor = $request->user();
        $month = Carbon::now()->startOfMonth();

        return view('dashboard', [
            'month' => $month,
            'summary' => $report->monthlySummary($tutor, $month),
            'cashReceived' => $report->cashReceivedInMonth($tutor, $month),
            'totalOutstanding' => $report->totalOutstanding($tutor),
            'activeStudents' => Student::query()->ownedBy($tutor)->active()->count(),
            'studentsWithBalance' => $report->studentsWithBalance($tutor),
            'recentPayments' => Payment::query()
                ->ownedBy($tutor)
                ->with(['student', 'fee'])
                ->latest('paid_on')
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }
}
