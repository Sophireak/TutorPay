<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'student_code' => 'STU-'.strtoupper(Str::random(6)),
            'name' => fake()->name(),
            'guardian_name' => fake()->name(),
            'phone' => fake()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'batch' => fake()->randomElement(['Grade 9 - Maths', 'Grade 10 - Physics', 'Grade 11 - Chemistry']),
            'grade' => fake()->randomElement(['9', '10', '11', '12']),
            'class_time' => fake()->randomElement(['Sat 16:00 - 18:00', 'Sun 09:00 - 11:00', 'Mon 18:00 - 20:00']),
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
