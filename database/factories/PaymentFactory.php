<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Fee;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fee_id' => Fee::factory(),
            'student_id' => fn (array $attributes) => Fee::find($attributes['fee_id'])?->student_id,
            'user_id' => fn (array $attributes) => Fee::find($attributes['fee_id'])?->student?->user_id,
            'amount' => 1000,
            'paid_on' => Carbon::now()->toDateString(),
            'method' => PaymentMethod::Cash,
            'reference' => null,
            'notes' => null,
        ];
    }

    public function forFee(Fee $fee): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_id' => $fee->id,
            'student_id' => $fee->student_id,
            'user_id' => $fee->student->user_id,
        ]);
    }
}
