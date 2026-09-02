<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'guardian_name' => fake()->name(),
            'phone' => fake()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'batch' => fake()->randomElement(['Grade 9 - Maths', 'Grade 10 - Physics', 'Grade 11 - Chemistry']),
            'monthly_fee' => fake()->randomElement([1500, 2000, 2500, 3000]),
            'status' => StudentStatus::Active,
            'enrolled_on' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatus::Inactive,
        ]);
    }
}
