<?php

namespace Database\Factories;

use App\Enums\FeeStatus;
use App\Models\Fee;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Fee>
 */
class FeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $month = Carbon::now()->startOfMonth();

        return [
            'student_id' => Student::factory(),
            'period_month' => $month,
            'amount' => fake()->randomElement([1500, 2000, 2500, 3000]),
            'status' => FeeStatus::Unpaid,
            'due_date' => $month->copy()->day(10),
            'notes' => null,
        ];
    }

    public function forMonth(Carbon $month): static
    {
        return $this->state(fn (array $attributes) => [
            'period_month' => $month->copy()->startOfMonth(),
            'due_date' => $month->copy()->startOfMonth()->day(10),
        ]);
    }
}
