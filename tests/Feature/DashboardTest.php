<?php

namespace Tests\Feature;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\CollectionReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_dashboard_summarises_the_current_month(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create(['name' => 'Amara Silva']);

        $fee = Fee::factory()->for($student)->create(['amount' => 2000]);
        Payment::factory()->forFee($fee)->create(['amount' => 750]);

        $response = $this->actingAs($tutor)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('2,000.00')  // billed
            ->assertSee('750.00')    // collected
            ->assertSee('1,250.00')  // outstanding
            ->assertSee('Amara Silva');
    }

    public function test_the_monthly_summary_service_reports_totals(): void
    {
        $tutor = User::factory()->create();
        $month = Carbon::create(2026, 3, 1);

        $paid = Fee::factory()->for(Student::factory()->for($tutor))->forMonth($month)->create(['amount' => 1000]);
        $unpaid = Fee::factory()->for(Student::factory()->for($tutor))->forMonth($month)->create(['amount' => 500]);
        Payment::factory()->forFee($paid)->create(['amount' => 1000]);

        $summary = app(CollectionReport::class)->monthlySummary($tutor, $month);

        $this->assertSame(1500.0, $summary['billed']);
        $this->assertSame(1000.0, $summary['collected']);
        $this->assertSame(500.0, $summary['outstanding']);
        $this->assertSame(2, $summary['students_billed']);
        $this->assertSame(1, $summary['fully_paid']);
        $this->assertSame(500.0, app(CollectionReport::class)->totalOutstanding($tutor));
        $this->assertTrue($unpaid->fresh()->outstanding() === 500.0);
    }

    public function test_the_monthly_report_page_renders(): void
    {
        $tutor = User::factory()->create();
        Fee::factory()->for(Student::factory()->for($tutor))->create(['amount' => 1000]);

        $this->actingAs($tutor)
            ->get(route('reports.monthly'))
            ->assertOk()
            ->assertSee(Carbon::now()->format('F Y'));
    }
}
