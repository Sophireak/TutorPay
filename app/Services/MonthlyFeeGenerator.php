<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates the monthly fee records for a tutor's active students.
 *
 * Generation is idempotent: a student can only ever have one fee record
 * per billing month, so re-running it for the same month tops up any
 * students that were added after the first run.
 */
class MonthlyFeeGenerator
{
    /**
     * @return int the number of fee records created
     */
    public function generate(User $tutor, Carbon $month, ?int $dueDay = null): int
    {
        $period = $month->copy()->startOfMonth();
        $dueDate = $dueDay !== null
            ? $period->copy()->day(min($dueDay, $period->daysInMonth))
            : null;

        $alreadyBilled = Fee::query()
            ->forMonth($period)
            ->whereIn('student_id', Student::query()->ownedBy($tutor)->select('id'))
            ->pluck('student_id')
            ->all();

        $students = Student::query()
            ->ownedBy($tutor)
            ->active()
            ->whereNotIn('id', $alreadyBilled)
            ->whereDate('enrolled_on', '<=', $period->copy()->endOfMonth())
            ->get();

        if ($students->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($students, $period, $dueDate): int {
            $created = 0;

            foreach ($students as $student) {
                $student->fees()->create([
                    'period_month' => $period,
                    'amount' => $student->monthly_fee,
                    'due_date' => $dueDate,
                ]);

                $created++;
            }

            return $created;
        });
    }
}
