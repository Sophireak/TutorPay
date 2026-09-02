<?php

namespace App\Models;

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
 */
#[Fillable(['period_month', 'amount', 'due_date', 'notes'])]
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

    public function status(): string
    {
        return match (true) {
            $this->isSettled() => 'paid',
            $this->isPartiallyPaid() => 'partial',
            default => 'unpaid',
        };
    }

    public function periodLabel(): string
    {
        return $this->period_month->translatedFormat('F Y');
    }
}
