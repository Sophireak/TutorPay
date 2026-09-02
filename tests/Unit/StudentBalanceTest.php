<?php

namespace Tests\Unit;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_outstanding_is_billed_minus_paid(): void
    {
        $student = Student::factory()->for(User::factory())->create();

        $march = Fee::factory()->for($student)->create(['amount' => 2000]);
        Fee::factory()->for($student)->create([
            'amount' => 2000,
            'period_month' => now()->startOfMonth()->subMonth(),
        ]);
        Payment::factory()->forFee($march)->create(['amount' => 1500]);

        $this->assertSame(4000.0, $student->totalBilled());
        $this->assertSame(1500.0, $student->totalPaid());
        $this->assertSame(2500.0, $student->outstanding());
    }

    public function test_outstanding_is_never_negative(): void
    {
        $student = Student::factory()->for(User::factory())->create();
        $fee = Fee::factory()->for($student)->create(['amount' => 1000]);
        Payment::factory()->forFee($fee)->create(['amount' => 1000]);

        $this->assertSame(0.0, $student->outstanding());
    }
}
