<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateFeesRequest;
use App\Services\MonthlyFeeGenerator;
use Illuminate\Http\RedirectResponse;

class GenerateFeesController extends Controller
{
    public function __invoke(GenerateFeesRequest $request, MonthlyFeeGenerator $generator): RedirectResponse
    {
        $month = $request->month();

        $created = $generator->generate($request->user(), $month, $request->dueDay());

        return redirect()
            ->route('fees.index', ['month' => $month->format('Y-m')])
            ->with('status', $created === 0
                ? 'Every active student is already billed for '.$month->format('F Y').'.'
                : "Generated {$created} fee record(s) for ".$month->format('F Y').'.');
    }
}
