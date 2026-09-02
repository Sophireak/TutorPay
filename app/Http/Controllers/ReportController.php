<?php

namespace App\Http\Controllers;

use App\Services\CollectionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function monthly(Request $request, CollectionReport $report): View
    {
        $tutor = $request->user();

        return view('reports.monthly', [
            'trend' => $report->trend($tutor, 6),
            'totalOutstanding' => $report->totalOutstanding($tutor),
            'currentMonth' => Carbon::now()->startOfMonth(),
            'studentsWithBalance' => $report->studentsWithBalance($tutor, 10),
        ]);
    }
}
