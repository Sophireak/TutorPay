<?php

namespace Tests\Feature\Students;

use App\Enums\StudentStatus;
use App\Models\Fee;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_students(): void
    {
        $this->get(route('students.index'))->assertRedirect(route('login'));
    }

    public function test_a_tutor_only_sees_their_own_students(): void
    {
        $tutor = User::factory()->create();
        $other = User::factory()->create();

        Student::factory()->for($tutor)->create(['name' => 'Amara Silva']);
        Student::factory()->for($other)->create(['name' => 'Ravi Perera']);

        $this->actingAs($tutor)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Amara Silva')
            ->assertDontSee('Ravi Perera');
    }

    public function test_a_student_can_be_created(): void
    {
        $tutor = User::factory()->create();

        $response = $this->actingAs($tutor)->post(route('students.store'), [
            'name' => 'Amara Silva',
            'guardian_name' => 'Nimal Silva',
            'phone' => '0771234567',
            'email' => 'amara@example.com',
            'batch' => 'Grade 10 - Physics',
            'monthly_fee' => '2500',
            'status' => StudentStatus::Active->value,
            'enrolled_on' => '2026-01-15',
        ]);

        $student = Student::firstOrFail();

        $response->assertRedirect(route('students.show', $student));

        $this->assertSame($tutor->id, $student->user_id);
        $this->assertSame('Amara Silva', $student->name);
        $this->assertSame(2500.0, (float) $student->monthly_fee);
        $this->assertSame(StudentStatus::Active, $student->status);
        $this->assertSame('2026-01-15', $student->enrolled_on->toDateString());
    }

    public function test_student_creation_requires_valid_data(): void
    {
        $tutor = User::factory()->create();

        $this->actingAs($tutor)
            ->post(route('students.store'), [
                'name' => '',
                'monthly_fee' => 'abc',
                'status' => 'unknown',
                'enrolled_on' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['name', 'monthly_fee', 'status', 'enrolled_on']);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_a_student_can_be_updated(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create(['monthly_fee' => 1500]);

        $this->actingAs($tutor)
            ->put(route('students.update', $student), [
                'name' => $student->name,
                'monthly_fee' => '1800',
                'status' => StudentStatus::Inactive->value,
                'enrolled_on' => $student->enrolled_on->toDateString(),
            ])
            ->assertRedirect(route('students.show', $student));

        $student->refresh();
        $this->assertSame(1800.0, (float) $student->monthly_fee);
        $this->assertSame(StudentStatus::Inactive, $student->status);
    }

    public function test_a_tutor_cannot_view_or_update_another_tutors_student(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->create();

        $this->actingAs($tutor)->get(route('students.show', $student))->assertForbidden();
        $this->actingAs($tutor)->get(route('students.edit', $student))->assertForbidden();
        $this->actingAs($tutor)->delete(route('students.destroy', $student))->assertForbidden();
    }

    public function test_a_student_without_billing_history_can_be_deleted(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create();

        $this->actingAs($tutor)
            ->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseCount('students', 0);
    }

    public function test_a_student_with_billing_history_is_kept(): void
    {
        $tutor = User::factory()->create();
        $student = Student::factory()->for($tutor)->create();
        Fee::factory()->for($student)->create();

        $this->actingAs($tutor)
            ->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.show', $student));

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }
}
