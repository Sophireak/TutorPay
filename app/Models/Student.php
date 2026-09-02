<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'guardian_name',
    'phone',
    'email',
    'batch',
    'grade',
    'class_time',
    'student_code',
    'monthly_fee',
    'status',
    'enrolled_on',
    'notes',
])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'enrolled_on' => 'date',
            'status' => StudentStatus::class,
        ];
    }

    protected static function booted(): void
    {
        // Every student needs a unique, human-friendly code. When one is
        // not supplied (form, factory, seeder) a deterministic one is
        // generated from the next expected primary key.
        static::creating(function (Student $student): void {
            if (blank($student->student_code)) {
                $student->student_code = static::nextStudentCode();
            }
        });
    }

    /**
     * The next available student code, e.g. "STU-0042".
     */
    public static function nextStudentCode(): string
    {
        $next = (int) static::query()->max('id') + 1;

        return 'STU-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * The tutor the student belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Fee, $this>
     */
    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @param  Builder<Student>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', StudentStatus::Active);
    }

    /**
     * @param  Builder<Student>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<Student>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('student_code', 'like', "%{$term}%")
                ->orWhere('guardian_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('batch', 'like', "%{$term}%")
                ->orWhere('grade', 'like', "%{$term}%");
        });
    }

    public function isActive(): bool
    {
        return $this->status === StudentStatus::Active;
    }

    /**
     * Total amount billed to the student across every fee record.
     */
    public function totalBilled(): float
    {
        return (float) $this->fees()->sum('amount');
    }

    /**
     * Total amount the student has paid so far.
     */
    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Amount still owed by the student (never negative).
     */
    public function outstanding(): float
    {
        return round(max($this->totalBilled() - $this->totalPaid(), 0), 2);
    }
}
