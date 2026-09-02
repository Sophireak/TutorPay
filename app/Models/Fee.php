<?php

namespace App\Models;

use App\Enums\FeeStatus;
use Carbon\CarbonInterface;
use Database\Factories\FeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A monthly fee charged to a student for a single billing month.
 *
 * One fee record represents one fee period: exactly one per student
 * per month (enforced by the unique [student_id, period_month]
 * constraint). The billed amount is stored on the record itself, so
 * changing a student's monthly fee later never rewrites history.
 *
 * The `status` column is a denormalised cache of the computed payment
 * status (kept in sync whenever payments change) so fee periods can be
 * queried by status without joining `payments`. The computed
 * status()/isSettled()/outstanding() methods remain the source of truth.
 */
#[Fillable(['period_month', 'amount', 'due_date', 'notes', 'status'])]
class Fee extends Model
{
    /** @use HasFactory<FeeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'status' => FeeStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Limit the query to fees belonging to a tutor.
     *
     * @param  Builder<Fee>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->whereHas('student', fn (Builder $student) => $student->where('user_id', $user->id));
    }

    /**
     * Limit the query to a single billing month.
     *
     * @param  Builder<Fee>  $query
     */
    public function scopeForMonth(Builder $query, CarbonInterface $month): void
    {
        $query->whereDate('period_month', $month->copy()->startOfMonth());
    }

    /**
     * Limit the query to fee periods in a given payment status.
     *
     * @param  Builder<Fee>  $query
     */
    public function scopeByStatus(Builder $query, string|FeeStatus $status): void
    {
        $query->where('status', $status instanceof FeeStatus ? $status->value : $status);
    }

    public function paidAmount(): float
    {
        $paid = $this->relationLoaded('payments')
            ? $this->payments->sum('amount')
            : $this->payments()->sum('amount');

        return round((float) $paid, 2);
    }

    public function outstanding(): float
    {
        return round(max((float) $this->amount - $this->paidAmount(), 0), 2);
    }

    public function isSettled(): bool
    {
        return $this->outstanding() <= 0.0;
    }

    public function isPartiallyPaid(): bool
    {
        return ! $this->isSettled() && $this->paidAmount() > 0;
    }

    /**
     * The computed payment status for this fee period:
     * 'paid', 'partial' or 'unpaid'.
     */
    public function status(): string
    {
        return match (true) {
            $this->isSettled() => 'paid',
            $this->isPartiallyPaid() => 'partial',
            default => 'unpaid',
        };
    }

    /**
     * Persist the denormalised status column so it can be used for
     * queries and indexes. Uses saveQuietly() so this never triggers
     * other model events.
     */
    public function refreshStatus(): void
    {
        $this->forceFill(['status' => FeeStatus::from($this->status())]);

        $this->saveQuietly();
    }

    public function periodLabel(): string
    {
        return $this->period_month->translatedFormat('F Y');
    }
}
