<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\MonthlyFeeGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a demo tutor with students, three months of fees and part payments.
     *
     * Model events are disabled while seeding (see WithoutModelEvents), so
     * the denormalised fields that are normally maintained by model events
     * (student_code, fee period status) are kept consistent explicitly.
     */
    public function run(MonthlyFeeGenerator $generator): void
    {
        $tutor = User::factory()->create([
            'name' => 'Demo Tutor',
            'email' => 'tutor@tutorpay.test',
        ]);

        Student::factory()
            ->count(8)
            ->for($tutor)
            ->create(['enrolled_on' => Carbon::now()->subMonths(6)->toDateString()]);

        // Safety net: any student without a code gets a deterministic one.
        $tutor->students()->whereNull('student_code')->get()->each(function (Student $student): void {
            $student->forceFill([
                'student_code' => 'STU-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT),
            ])->saveQuietly();
        });

        foreach ([2, 1, 0] as $offset) {
            $month = Carbon::now()->startOfMonth()->subMonths($offset);
            $generator->generate($tutor, $month, config('tutorpay.default_due_day'));
        }

        $tutor->students()->with('fees')->get()->each(function (Student $student): void {
            foreach ($student->fees as $index => $fee) {
                if ($index === 0 && $student->id % 3 === 0) {
                    continue; // leave the oldest month unpaid for a few students
                }

                $payment = new Payment([
                    'amount' => $student->id % 4 === 0 ? round((float) $fee->amount / 2, 2) : $fee->amount,
                    'paid_on' => $fee->period_month->copy()->day(min(12, $fee->period_month->daysInMonth)),
                    'method' => PaymentMethod::Cash,
                ]);

                $payment->student()->associate($student);
                $payment->user()->associate($student->user_id);

                $fee->payments()->save($payment);
            }
        });

        // Refresh the denormalised status of every seeded fee period so
        // the stored status matches the payments that were created.
        Fee::query()
            ->whereHas('student', fn ($query) => $query->where('user_id', $tutor->id))
            ->get()
            ->each->refreshStatus();
    }
}
