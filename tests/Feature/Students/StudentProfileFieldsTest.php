<?php

namespace Tests\Feature\Students;

use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_code_is_generated_when_not_provided(): void
    {
        $student = Student::factory()->create(['student_code' => null]);

        $this->assertMatchesRegularExpression(
            '/^STU-\d{4,}$/',
            $student->fresh()->student_code
        );
    }

    public function test_generated_student_codes_are_unique(): void
    {
        $first = Student::factory()->create(['student_code' => null]);
        $second = Student::factory()->create(['student_code' => null]);

        $this->assertNotSame(
            $first->fresh()->student_code,
            $second->fresh()->student_code
        );
    }

    public function test_a_student_can_be_created_with_profile_fields(): void
    {
        $tutor = User::factory()->create();

        $this->actingAs($tutor)->post(route('students.store'), [
            'name' => 'Amara Silva',
            'student_code' => 'STU-0042',
            'grade' => '10',
            'class_time' => 'Sat 16:00 - 18:00',
            'monthly_fee' => '2500',
            'status' => StudentStatus::Active->value,
            'enrolled_on' => '2026-01-15',
        ])->assertRedirect();

        $student = Student::firstOrFail();

        $this->assertSame('STU-0042', $student->student_code);
        $this->assertSame('10', $student->grade);
        $this->assertSame('Sat 16:00 - 18:00', $student->class_time);
    }

    public function test_duplicate_student_codes_are_rejected(): void
    {
        $tutor = User::factory()->create();
        Student::factory()->for($tutor)->create(['student_code' => 'STU-0001']);

        $this->actingAs($tutor)->post(route('students.store'), [
            'name' => 'Other Student',
            'student_code' => 'STU-0001',
            'monthly_fee' => '2500',
            'status' => StudentStatus::Active->value,
            'enrolled_on' => '2026-01-15',
        ])->assertSessionHasErrors('student_code');

        $this->assertDatabaseCount('students', 1);
    }

    public function test_student_code_cannot_be_updated_to_an_existing_code(): void
    {
        $tutor = User::factory()->create();
        $a = Student::factory()->for($tutor)->create(['student_code' => 'STU-0001']);
        $b = Student::factory()->for($tutor)->create(['student_code' => 'STU-0002']);

        $this->actingAs($tutor)->put(route('students.update', $a), [
            'name' => $a->name,
            'student_code' => 'STU-0002',
            'monthly_fee' => $a->monthly_fee,
            'status' => StudentStatus::Active->value,
            'enrolled_on' => $a->enrolled_on->toDateString(),
        ])->assertSessionHasErrors('student_code');

        $this->assertSame('STU-0001', $a->fresh()->student_code);
    }

    public function test_students_can_be_searched_by_their_code(): void
    {
        $tutor = User::factory()->create();
        Student::factory()->for($tutor)->create([
            'name' => 'Amara Silva',
            'student_code' => 'STU-777',
        ]);
        Student::factory()->for($tutor)->create([
            'name' => 'Ravi Perera',
            'student_code' => 'STU-888',
        ]);

        $this->actingAs($tutor)
            ->get(route('students.index', ['search' => 'STU-777']))
            ->assertOk()
            ->assertSee('Amara Silva')
            ->assertDontSee('Ravi Perera');
    }
}
