<?php

namespace Tests\Unit;

use App\Exceptions\InvalidPaymentAmount;
use App\Exceptions\OverpaymentException;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The critical financial rules enforced at the data layer, independent
 * of the HTTP layer.
 */
class PaymentGuardTest extends TestCase
{
    use RefreshDatabase;

    private function feeFor(float $amount): Fee
    {
        $student = Student::factory()->for(User::factory())->create(['monthly_fee' => $amount]);

        return Fee::factory()->for($student)->create(['amount' => $amount]);
    }

    public function test_a_zero_amount_payment_is_rejected(): void
    {
        $fee = $this->feeFor(2000);

        $this->expectException(InvalidPaymentAmount::class);

        Payment::factory()->forFee($fee)->create(['amount' => 0]);
    }

    public function test_a_negative_amount_payment_is_rejected(): void
    {
        $fee = $this->feeFor(2000);

        $this->expectException(InvalidPaymentAmount::class);

        Payment::factory()->forFee($fee)->create(['amount' => -50]);
    }

    public function test_a_payment_cannot_exceed_the_outstanding_balance_at_the_data_layer(): void
    {
        $fee = $this->feeFor(2000);
        Payment::factory()->forFee($fee)->create(['amount' => 1500]);

        $this->expectException(OverpaymentException::class);

        Payment::factory()->forFee($fee)->create(['amount' => 600]);
    }

    public function test_overpayment_is_prevented_across_multiple_payments(): void
    {
        $fee = $this->feeFor(2000);
        Payment::factory()->forFee($fee)->create(['amount' => 900]);
        Payment::factory()->forFee($fee)->create(['amount' => 900]);

        try {
            Payment::factory()->forFee($fee)->create(['amount' => 200.01]);
            $this->fail('Expected OverpaymentException was not thrown.');
        } catch (OverpaymentException) {
            // expected
        }

        // The rejected payment must not have been persisted.
        $this->assertSame(2, Payment::count());
        $this->assertSame(200.0, $fee->fresh()->outstanding());
    }

    public function test_a_payment_exactly_matching_the_balance_is_accepted(): void
    {
        $fee = $this->feeFor(2000);
        Payment::factory()->forFee($fee)->create(['amount' => 1999.99]);

        Payment::factory()->forFee($fee)->create(['amount' => 0.01]);

        $this->assertSame(0.0, $fee->fresh()->outstanding());
        $this->assertSame(2000.0, $fee->fresh()->paidAmount());
    }

    public function test_multiple_partial_payments_for_one_fee_period_are_supported(): void
    {
        $fee = $this->feeFor(2000);

        Payment::factory()->forFee($fee)->create(['amount' => 700, 'paid_on' => '2026-03-05']);
        Payment::factory()->forFee($fee)->create(['amount' => 500, 'paid_on' => '2026-03-18']);
        Payment::factory()->forFee($fee)->create(['amount' => 800, 'paid_on' => '2026-04-01']);

        $this->assertSame(3, $fee->fresh()->payments()->count());
        $this->assertSame(2000.0, $fee->fresh()->paidAmount());
        $this->assertSame(0.0, $fee->fresh()->outstanding());
    }

    public function test_historical_fee_amounts_survive_monthly_fee_changes(): void
    {
        $student = Student::factory()->for(User::factory())->create(['monthly_fee' => 2000]);
        $fee = Fee::factory()
            ->for($student)
            ->forMonth(Carbon::create(2026, 3, 1))
            ->create(['amount' => 2000]);

        $student->update(['monthly_fee' => 3000]);

        // The March fee period keeps the amount it was billed with...
        $this->assertSame(2000.0, $fee->fresh()->amount);

        // ...while a later fee period uses the new monthly fee.
        $nextMonth = Fee::factory()
            ->for($student)
            ->forMonth(Carbon::create(2026, 4, 1))
            ->create(['amount' => $student->monthly_fee]);

        $this->assertSame(3000.0, $nextMonth->amount);
    }

    public function test_duplicate_fee_periods_for_the_same_student_and_month_are_rejected(): void
    {
        $student = Student::factory()->for(User::factory())->create();

        Fee::factory()->for($student)->forMonth(Carbon::create(2026, 3, 1))->create();

        $this->expectException(QueryException::class);

        Fee::factory()->for($student)->forMonth(Carbon::create(2026, 3, 1))->create();
    }

    public function test_the_same_student_can_be_billed_in_consecutive_months(): void
    {
        $student = Student::factory()->for(User::factory())->create(['monthly_fee' => 2000]);

        Fee::factory()->for($student)->forMonth(Carbon::create(2026, 3, 1))->create(['amount' => 2000]);
        Fee::factory()->for($student)->forMonth(Carbon::create(2026, 4, 1))->create(['amount' => 2000]);

        $this->assertSame(2, $student->fees()->count());
    }
}
