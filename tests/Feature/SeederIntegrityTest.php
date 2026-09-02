<?php

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Models\Fee;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_students_have_unique_codes(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tutor = User::where('email', 'tutor@tutorpay.test')->firstOrFail();
        $students = $tutor->students()->get();

        $this->assertDatabaseCount('students', 8);
        $this->assertTrue($students->every(fn (Student $student) => filled($student->student_code)));
        $this->assertSame(8, $students->pluck('student_code')->unique()->count());
    }

    public function test_seeded_fee_periods_have_consistent_statuses(): void
    {
        $this->seed(DatabaseSeeder::class);

        $fees = Fee::with('payments')->all();
        $this->assertTrue($fees->isNotEmpty());

        $this->assertTrue($fees->contains(
            fn (Fee $fee) => $fee->status === FeeStatus::Unpaid
        ), 'expected at least one unpaid fee period in the seed data');

        foreach ($fees as $fee) {
            $paid = (float) $fee->payments->sum('amount');

            $expected = match (true) {
                $paid <= 0.0 => FeeStatus::Unpaid,
                $paid >= (float) $fee->amount => FeeStatus::Paid,
                default => FeeStatus::Partial,
            };

            $this->assertSame(
                $expected,
                $fee->fresh()->status,
                "Fee period #{$fee->id}: stored status does not match its payments."
            );

            $this->assertGreaterThanOrEqual(
                0.0,
                (float) $fee->amount - $paid,
                "Fee period #{$fee->id} was overpaid by the seeder."
            );
        }
    }
}
