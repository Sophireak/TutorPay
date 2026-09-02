<?php

namespace Tests\Feature\Students;

use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tutor_can_deactivate_an_active_student(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create(['status' => StudentStatus::Active]);

        $this->actingAs($tutor)
            ->patch(route('students.toggle', $student))
            ->assertRedirect();

        $this->assertSame(StudentStatus::Inactive, $student->fresh()->status);
    }

    public function test_a_tutor_can_activate_an_inactive_student(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create(['status' => StudentStatus::Inactive]);

        $this->actingAs($tutor)
            ->patch(route('students.toggle', $student))
            ->assertRedirect();

        $this->assertSame(StudentStatus::Active, $student->fresh()->status);
    }

    public function test_a_tutor_cannot_toggle_another_tutors_student(): void
    {
        $tutor = User::factory()->create();
        $other = User::factory()->create();
        $student = Student::factory()->for($other)->create(['status' => StudentStatus::Active]);

        $this->actingAs($tutor)
            ->patch(route('students.toggle', $student))
            ->assertForbidden();

        $this->assertSame(StudentStatus::Active, $student->fresh()->status);
    }

    public function test_guests_cannot_toggle_student_status(): void
    {
        $student = Student::factory()->create(['status' => StudentStatus::Active]);

        $this->patch(route('students.toggle', $student))
            ->assertRedirect(route('login'));

        $this->assertSame(StudentStatus::Active, $student->fresh()->status);
    }

    public function test_toggle_flashes_a_confirmation_message(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create([
            'name' => 'Amara Silva',
            'status' => StudentStatus::Active,
        ]);

        $this->actingAs($tutor)
            ->patch(route('students.toggle', $student))
            ->assertSessionHas('status');
    }

    public function test_inactive_student_is_excluded_from_fee_generation(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create([
            'status' => StudentStatus::Active,
            'enrolled_on' => '2026-01-01',
        ]);

        // Deactivate via toggle
        $this->actingAs($tutor)
            ->patch(route('students.toggle', $student));

        $this->assertSame(StudentStatus::Inactive, $student->fresh()->status);

        // Generate fees — should produce zero records
        $this->actingAs($tutor)
            ->post(route('fees.generate'), ['month' => '2026-09'])
            ->assertSessionHas('status');

        $this->assertDatabaseCount('fees', 0);
    }
}
