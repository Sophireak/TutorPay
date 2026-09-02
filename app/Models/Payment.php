<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Exceptions\InvalidPaymentAmount;
use App\Exceptions\OverpaymentException;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['amount', 'paid_on', 'method', 'reference', 'notes'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_on' => 'date',
            'method' => PaymentMethod::class,
        ];
    }

    protected static function booted(): void
    {
        // Data-layer guard: reject payments that are invalid or would
        // overpay the fee period, no matter how the payment is created.
        static::creating(function (Payment $payment): void {
            self::assertValidPayment($payment);
        });

        // Keep the denormalised fee-period status in sync with the
        // payments that exist for it.
        static::created(function (Payment $payment): void {
            self::syncFeeStatus($payment);
        });

        static::deleted(function (Payment $payment): void {
            self::syncFeeStatus($payment);
        });
    }

    /**
     * @return BelongsTo<Fee, $this>
     */
    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * The tutor who recorded the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Payment>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->whereHas('student', fn (Builder $student) => $student->where('user_id', $user->id));
    }

    /**
     * @param  Builder<Payment>  $query
     */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): void
    {
        $query->when($from, fn (Builder $query) => $query->whereDate('paid_on', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('paid_on', '<=', $to));
    }

    /**
     * Reject payments with a non-positive amount and payments that
     * would exceed the outstanding balance of their fee period.
     */
    protected static function assertValidPayment(Payment $payment): void
    {
        $amount = (float) $payment->amount;

        if ($amount <= 0) {
            throw new InvalidPaymentAmount('A payment amount must be greater than zero.');
        }

        if ($payment->fee_id === null) {
            return;
        }

        $fee = Fee::query()->find($payment->fee_id);

        if ($fee === null) {
            return; // an unknown fee_id is rejected by the foreign key
        }

        if ($amount > $fee->outstanding()) {
            throw new OverpaymentException(sprintf(
                'Payment of %s exceeds the outstanding balance of %s for fee period #%d.',
                number_format($amount, 2),
                number_format($fee->outstanding(), 2),
                $fee->id,
            ));
        }
    }

    /**
     * Refresh the denormalised status of the fee period this payment
     * belongs to.
     */
    protected static function syncFeeStatus(Payment $payment): void
    {
        if ($payment->fee_id === null) {
            return;
        }

        $fee = Fee::query()->find($payment->fee_id);

        $fee?->refreshStatus();
    }
}
