<?php

namespace App\Models;

use App\Enums\PaymentMethod;
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
}
