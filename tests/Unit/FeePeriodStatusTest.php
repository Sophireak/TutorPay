<?php

namespace Tests\Unit;

use App\Enums\FeeStatus;
use App\Models\Fee;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeePeriodStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_fee_period_is_unpaid(): void
    {
        $fee = Fee::factory()->create(['amount' => 2000]);

        $this->assertSame(FeeStatus::Unpaid, $fee->status);
        $this->assertSame('unpaid', $fee->status());
        $this->assertSame(0.0, $fee->paidAmount());
        $this->assertSame(2000.0, $fee->outstanding());
    }

    public function test_status_becomes_partial_after_a_partial_payment(): void
    {
        $fee = Fee::factory()->create(['amount' => 2000]);

        Payment::factory()->forFee($fee)->create(['amount' => 800]);

        $fee->refresh();

        $this->assertSame(FeeStatus::Partial, $fee->status);
        $this->assertSame('partial', $fee->status());
        $this->assertSame(800.0, $fee->paidAmount());
        $this->assertSame(1200.0, $fee->outstanding());
    }

    public function test_status_becomes_paid_once_fully_settled(): void
    {
        $fee = Fee::factory()->create(['amount' => 2000]);

        Payment::factory()->forFee($fee)->create(['amount' => 1200]);
        Payment::factory()->forFee($fee)->create(['amount' => 800]);

        $fee->refresh();

        $this->assertSame(FeeStatus::Paid, $fee->status);
        $this->assertSame('paid', $fee->status());
        $this->assertSame(2000.0, $fee->paidAmount());
        $this->assertSame(0.0, $fee->outstanding());
        $this->assertTrue($fee->isSettled());
    }

    public function test_status_reverts_when_a_payment_is_deleted(): void
    {
        $fee = Fee::factory()->create(['amount' => 2000]);
        Payment::factory()->forFee($fee)->create(['amount' => 1200]);
        $second = Payment::factory()->forFee($fee)->create(['amount' => 800]);

        $this->assertSame(FeeStatus::Paid, $fee->fresh()->status);

        $second->delete();

        $fee->refresh();

        $this->assertSame(FeeStatus::Partial, $fee->status);
        $this->assertSame('partial', $fee->status());
        $this->assertSame(800.0, $fee->outstanding());
    }

    public function test_fee_periods_can_be_queried_by_status(): void
    {
        $unpaid = Fee::factory()->create(['amount' => 1000]);
        $partial = Fee::factory()->create(['amount' => 1000]);
        $paid = Fee::factory()->create(['amount' => 1000]);

        Payment::factory()->forFee($partial)->create(['amount' => 400]);
        Payment::factory()->forFee($paid)->create(['amount' => 1000]);

        $this->assertTrue(Fee::byStatus(FeeStatus::Unpaid)->get()->contains('id', $unpaid->id));
        $this->assertTrue(Fee::byStatus(FeeStatus::Partial)->get()->contains('id', $partial->id));
        $this->assertTrue(Fee::byStatus(FeeStatus::Paid)->get()->contains('id', $paid->id));
        $this->assertSame(1, Fee::byStatus('partial')->count());
        $this->assertSame(3, Fee::count());
    }
}
