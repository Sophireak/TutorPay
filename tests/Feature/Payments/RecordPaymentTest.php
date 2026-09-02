<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentMethod;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecordPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function feeFor(User $tutor, float $amount = 2000): Fee
    {
        $student = Student::factory()->for($tutor)->create(['monthly_fee' => $amount]);

        return Fee::factory()->for($student)->create(['amount' => $amount]);
    }

    public function test_a_payment_can_be_recorded_against_a_fee(): void
    {
        $tutor = User::factory()->create();
        $fee = $this->feeFor($tutor);

        $this->actingAs($tutor)
            ->post(route('payments.store'), [
                'fee_id' => $fee->id,
                'amount' => '1200',
                'paid_on' => '2026-03-05',
                'method' => PaymentMethod::Cash->value,
                'reference' => 'RCPT-001',
            ])
            ->assertRedirect(route('students.show', $fee->student))
            ->assertSessionHas('status');

        $payment = Payment::firstOrFail();
        $this->assertSame($fee->id, $payment->fee_id);
        $this->assertSame($fee->student_id, $payment->student_id);
        $this->assertSame($tutor->id, $payment->user_id);
        $this->assertSame(1200.0, (float) $payment->amount);
        $this->assertSame('RCPT-001', $payment->reference);
        $this->assertSame('2026-03-05', $payment->paid_on->toDateString());

        $this->assertSame(800.0, $fee->fresh()->outstanding());
        $this->assertSame('partial', $fee->fresh()->status());
    }

    public function test_a_fee_is_settled_once_fully_paid(): void
    {
        $tutor = User::factory()->create();
        $fee = $this->feeFor($tutor);

        Payment::factory()->forFee($fee)->create(['amount' => 2000]);

        $this->assertTrue($fee->fresh()->isSettled());
        $this->assertSame(0.0, $fee->fresh()->outstanding());
        $this->assertSame('paid', $fee->fresh()->status());
    }

    public function test_a_payment_cannot_exceed_the_outstanding_balance(): void
    {
        $tutor = User::factory()->create();
        $fee = $this->feeFor($tutor);
        Payment::factory()->forFee($fee)->create(['amount' => 1500]);

        $this->actingAs($tutor)
            ->post(route('payments.store'), [
                'fee_id' => $fee->id,
                'amount' => '600',
                'paid_on' => '2026-03-05',
                'method' => PaymentMethod::Cash->value,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_validation_rejects_missing_fields(): void
    {
        $tutor = User::factory()->create();

        $this->actingAs($tutor)
            ->post(route('payments.store'), [])
            ->assertSessionHasErrors(['fee_id', 'amount', 'paid_on', 'method']);
    }

    public function test_a_tutor_cannot_pay_another_tutors_fee(): void
    {
        $tutor = User::factory()->create();
        $fee = Fee::factory()->create();

        $this->actingAs($tutor)
            ->post(route('payments.store'), [
                'fee_id' => $fee->id,
                'amount' => '100',
                'paid_on' => '2026-03-05',
                'method' => PaymentMethod::Cash->value,
            ])
            ->assertForbidden();

        $this->actingAs($tutor)
            ->get(route('payments.create', ['fee' => $fee->id]))
            ->assertForbidden();
    }

    public function test_a_payment_can_be_deleted_by_its_owner_only(): void
    {
        $tutor = User::factory()->create();
        $fee = $this->feeFor($tutor);
        $payment = Payment::factory()->forFee($fee)->create(['amount' => 500]);

        $this->actingAs(User::factory()->create())
            ->delete(route('payments.destroy', $payment))
            ->assertForbidden();

        $this->actingAs($tutor)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('students.show', $fee->student));

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_history_can_be_filtered(): void
    {
        $tutor = User::factory()->create();
        $fee = $this->feeFor($tutor, 5000);
        $fee->student->update(['name' => 'Amara Silva']);

        Payment::factory()->forFee($fee)->create([
            'amount' => 1000,
            'paid_on' => Carbon::create(2026, 3, 5),
        ]);
        Payment::factory()->forFee($fee)->create([
            'amount' => 2000,
            'paid_on' => Carbon::create(2026, 5, 5),
        ]);

        $response = $this->actingAs($tutor)->get(route('payments.index', [
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ]));

        $response->assertOk()
            ->assertSee('Amara Silva')
            ->assertSee('1,000.00')
            ->assertDontSee('2,000.00');
    }

    public function test_payments_of_other_tutors_are_hidden(): void
    {
        $tutor = User::factory()->create();
        $otherFee = Fee::factory()->create();
        $otherFee->student->update(['name' => 'Ravi Perera']);
        Payment::factory()->forFee($otherFee)->create(['amount' => 999]);

        $this->actingAs($tutor)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertDontSee('Ravi Perera');
    }
}
