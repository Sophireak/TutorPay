<?php

namespace Tests\Feature\Fees;

use App\Models\Fee;
use App\Models\Student;
use App\Models\User;
use App\Services\MonthlyFeeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonthlyFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_fees_are_generated_for_active_students_only(): void
    {
        $tutor = User::factory()->create();
        $month = Carbon::create(2026, 3, 1);

        Student::factory()->count(2)->for($tutor)->create([
            'monthly_fee' => 2000,
            'enrolled_on' => '2026-01-01',
        ]);
        Student::factory()->for($tutor)->inactive()->create(['enrolled_on' => '2026-01-01']);
        Student::factory()->create(['enrolled_on' => '2026-01-01']); // another tutor

        $created = app(MonthlyFeeGenerator::class)->generate($tutor, $month, 10);

        $this->assertSame(2, $created);
        $this->assertDatabaseCount('fees', 2);

        $fee = Fee::firstOrFail();
        $this->assertSame('2026-03-01', $fee->period_month->toDateString());
        $this->assertSame('2026-03-10', $fee->due_date->toDateString());
        $this->assertSame(2000.0, (float) $fee->amount);
    }

    public function test_generation_is_idempotent_for_the_same_month(): void
    {
        $tutor = User::factory()->create();
        $month = Carbon::create(2026, 3, 1);
        Student::factory()->for($tutor)->create(['enrolled_on' => '2026-01-01']);

        $generator = app(MonthlyFeeGenerator::class);

        $this->assertSame(1, $generator->generate($tutor, $month));
        $this->assertSame(0, $generator->generate($tutor, $month));
        $this->assertDatabaseCount('fees', 1);
    }

    public function test_students_enrolled_after_the_billing_month_are_skipped(): void
    {
        $tutor = User::factory()->create();
        Student::factory()->for($tutor)->create(['enrolled_on' => '2026-05-01']);

        $created = app(MonthlyFeeGenerator::class)->generate($tutor, Carbon::create(2026, 3, 1));

        $this->assertSame(0, $created);
    }

    public function test_a_tutor_can_generate_fees_from_the_fees_page(): void
    {
        $tutor = User::factory()->create();
        Student::factory()->for($tutor)->create(['enrolled_on' => '2026-01-01']);

        $this->actingAs($tutor)
            ->post(route('fees.generate'), ['month' => '2026-03', 'due_day' => 10])
            ->assertRedirect(route('fees.index', ['month' => '2026-03']))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('fees', 1);
    }

    public function test_a_single_fee_can_be_added_once_per_month(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create();

        $payload = [
            'student_id' => $student->id,
            'period_month' => '2026-04',
            'amount' => '1750.50',
        ];

        $this->actingAs($tutor)->post(route('fees.store'), $payload)
            ->assertRedirect(route('fees.index', ['month' => '2026-04']));

        $fee = Fee::firstOrFail();
        $this->assertSame($student->id, $fee->student_id);
        $this->assertSame('2026-04-01', $fee->period_month->toDateString());
        $this->assertSame(1750.50, (float) $fee->amount);

        $this->actingAs($tutor)->post(route('fees.store'), $payload)
            ->assertSessionHasErrors('period_month');

        $this->assertDatabaseCount('fees', 1);
    }

    public function test_a_tutor_cannot_bill_another_tutors_student(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->create();

        $this->actingAs($tutor)
            ->post(route('fees.store'), [
                'student_id' => $student->id,
                'period_month' => '2026-04',
                'amount' => '1000',
            ])
            ->assertSessionHasErrors('student_id');
    }

    public function test_the_fees_page_lists_the_selected_month(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create(['name' => 'Amara Silva']);
        Fee::factory()->for($student)->forMonth(Carbon::create(2026, 3, 1))->create(['amount' => 2000]);

        $this->actingAs($tutor)
            ->get(route('fees.index', ['month' => '2026-03']))
            ->assertOk()
            ->assertSee('Amara Silva')
            ->assertSee('March 2026');
    }
}
