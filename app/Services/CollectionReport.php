<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates billing and collection figures used by the dashboard and reports.
 */
class CollectionReport
{
    /**
     * Headline figures for a single billing month.
     *
     * @return array{billed: float, collected: float, outstanding: float, students_billed: int, fully_paid: int}
     */
    public function monthlySummary(User $tutor, Carbon $month): array
    {
        $period = $month->copy()->startOfMonth();

        $fees = Fee::query()
            ->ownedBy($tutor)
            ->forMonth($period)
            ->withSum('payments as payments_total', 'amount')
            ->get();

        $billed = round((float) $fees->sum('amount'), 2);
        $collected = round((float) $fees->sum('payments_total'), 2);

        return [
            'billed' => $billed,
            'collected' => $collected,
            'outstanding' => round(max($billed - $collected, 0), 2),
            'students_billed' => $fees->count(),
            'fully_paid' => $fees->filter(
                fn (Fee $fee) => (float) $fee->amount - (float) $fee->payments_total <= 0
            )->count(),
        ];
    }

    /**
     * Cash actually received during a calendar month, regardless of the
     * billing month the payment was applied to.
     */
    public function cashReceivedInMonth(User $tutor, Carbon $month): float
    {
        return round((float) Payment::query()
            ->ownedBy($tutor)
            ->whereBetween('paid_on', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->sum('amount'), 2);
    }

    /**
     * Total unpaid balance across every billing month.
     */
    public function totalOutstanding(User $tutor): float
    {
        $billed = (float) Fee::query()->ownedBy($tutor)->sum('amount');
        $paid = (float) Payment::query()->ownedBy($tutor)->sum('amount');

        return round(max($billed - $paid, 0), 2);
    }

    /**
     * Students with the largest unpaid balance.
     *
     * @return Collection<int, Student>
     */
    public function studentsWithBalance(User $tutor, int $limit = 5): Collection
    {
        return Student::query()
            ->ownedBy($tutor)
            ->withSum('fees as billed_total', 'amount')
            ->withSum('payments as paid_total', 'amount')
            ->get()
            ->each(function (Student $student): void {
                $student->balance = round(
                    max((float) $student->billed_total - (float) $student->paid_total, 0),
                    2
                );
            })
            ->filter(fn (Student $student) => $student->balance > 0)
            ->sortByDesc('balance')
            ->take($limit)
            ->values();
    }

    /**
     * Billed vs collected for the last N months, oldest first.
     *
     * @return Collection<int, array{month: Carbon, billed: float, collected: float, outstanding: float}>
     */
    public function trend(User $tutor, int $months = 6): Collection
    {
        return collect(range($months - 1, 0))
            ->map(function (int $offset) use ($tutor): array {
                $month = Carbon::now()->startOfMonth()->subMonths($offset);
                $summary = $this->monthlySummary($tutor, $month);

                return [
                    'month' => $month,
                    'billed' => $summary['billed'],
                    'collected' => $summary['collected'],
                    'outstanding' => $summary['outstanding'],
                ];
            });
    }
}
