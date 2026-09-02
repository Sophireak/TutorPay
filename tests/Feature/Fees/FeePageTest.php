<?php

namespace Tests\Feature\Fees;

use App\Enums\StudentStatus;
use App\Models\Fee;
use App\Models\Student;
use App\Models\User;
use App\Services\MonthlyFeeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FeePageTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Page rendering
    // ----------------------------------------------------------------

    public function test_fee_page_shows_student_grade_and_class_time(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create([
            'name' => 'Amara Silva',
            'grade' => '10',
            'class_time' => 'Sat 16:00-18:00',
            'enrolled_on' => '2026-01-01',
        ]);
        Fee::factory()->for($student)->create([
            'period_month' => '2026-09-01',
            'amount' => 2500,
        ]);

        $this->actingAs($tutor)
            ->get(route('fees.index', ['month' => '2026-09']))
            ->assertOk()
            ->assertSee('Amara Silva')
            ->assertSee('10')
            ->assertSee('Sat 16:00-18:00');
    }

    public function test_fee_page_shows_fee_paid_remaining_status_columns(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create([
            'name' => 'Ravi Perera',
            'enrolled_on' => '2026-01-01',
        ]);
        Fee::factory()->for($student)->create([
            'period_month' => '2026-09-01',
            'amount' => 3000,
            'status' => 'unpaid',
        ]);

        $this->actingAs($tutor)
            ->get(route('fees.index', ['month' => '2026-09']))
            ->assertOk()
            ->assertSee('Ravi Perera')
            ->assertSee('3,000.00')
            ->assertSee('Unpaid');
    }

    // ----------------------------------------------------------------
    // Generation rules
    // ----------------------------------------------------------------

    public function test_generate_only_includes_active_students(): void
    {
        $tutor = User::factory()->create();
        $month = Carbon::create(2026, 9, 1);

        Student::factory()->for($tutor)->create([
            'status' => StudentStatus::Active,
            'monthly_fee' => 2000,
            'enrolled_on' => '2026-01-01',
        ]);
        Student::factory()->for($tutor)->create([
            'status' => StudentStatus::Inactive,
            'monthly_fee' => 2000,
            'enrolled_on' => '2026-01-01',
        ]);

        $created = app(MonthlyFeeGenerator::class)->generate($tutor, $month);

        $this->assertSame(1, $created);
    }

    public function test_duplicate_generation_is_prevented(): void
    {
        $tutor = User::factory()->create();
        $month = Carbon::create(2026, 9, 1);

        Student::factory()->for($tutor)->create([
            'status' => StudentStatus::Active,
            'enrolled_on' => '2026-01-01',
        ]);

        $generator = app(MonthlyFeeGenerator::class);

        $this->assertSame(1, $generator->generate($tutor, $month));
        $this->assertSame(0, $generator->generate($tutor, $month));
        $this->assertDatabaseCount('fees', 1);
    }

    public function test_generate_via_http_reports_duplicate_gracefully(): void
    {
        $tutor = User::factory()->create();
        Student::factory()->for($tutor)->create(['enrolled_on' => '2026-01-01']);

        // First generation — creates fees
        $this->actingAs($tutor)
            ->post(route('fees.generate'), ['month' => '2026-09'])
            ->assertRedirect(route('fees.index', ['month' => '2026-09']))
            ->assertSessionHas('status');

        $count1 = Fee::count();

        // Second generation — no new fees, but no error either
        $this->actingAs($tutor)
            ->post(route('fees.generate'), ['month' => '2026-09'])
            ->assertRedirect(route('fees.index', ['month' => '2026-09']))
            ->assertSessionHas('status');

        $this->assertSame($count1, Fee::count());
    }

    public function test_fee_amount_stored_at_generation_time(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create([
            'monthly_fee' => 2500,
            'enrolled_on' => '2026-01-01',
        ]);

        app(MonthlyFeeGenerator::class)->generate($tutor, Carbon::create(2026, 9, 1));

        $fee = Fee::firstOrFail();
        $this->assertSame(2500.0, (float) $fee->amount);

        // Changing monthly fee must NOT alter the historical fee record
        $student->update(['monthly_fee' => 9999]);
        $fee->refresh();
        $this->assertSame(2500.0, (float) $fee->amount);
    }

    public function test_fee_amount_is_preserved_after_student_fee_change(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create([
            'monthly_fee' => 1500,
            'enrolled_on' => '2026-01-01',
        ]);

        $generator = app(MonthlyFeeGenerator::class);
        $generator->generate($tutor, Carbon::create(2026, 8, 1));

        // Update the student's fee
        $student->update(['monthly_fee' => 3000]);

        // Generate next month — new fee uses the current (updated) amount
        $generator->generate($tutor, Carbon::create(2026, 9, 1));

        $fees = Fee::orderBy('period_month')->get();

        $this->assertSame(1500.0, (float) $fees->get(0)->amount, 'August fee should remain at original amount');
        $this->assertSame(3000.0, (float) $fees->get(1)->amount, 'September fee should use updated amount');
    }
}
