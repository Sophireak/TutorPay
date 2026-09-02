<?php

namespace Tests\Feature\Students;

use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_can_be_filtered_by_grade(): void
    {
        $tutor = User::factory()->create();

        Student::factory()->for($tutor)->create([
            'name' => 'Amara Silva',
            'grade' => '10',
        ]);
        Student::factory()->for($tutor)->create([
            'name' => 'Ravi Perera',
            'grade' => '11',
        ]);

        $this->actingAs($tutor)
            ->get(route('students.index', ['grade' => '10']))
            ->assertOk()
            ->assertSee('Amara Silva')
            ->assertDontSee('Ravi Perera');
    }

    public function test_students_can_be_filtered_by_active_status(): void
    {
        $tutor = User::factory()->create();

        Student::factory()->for($tutor)->create([
            'name' => 'Active Student',
            'status' => StudentStatus::Active,
        ]);
        Student::factory()->for($tutor)->create([
            'name' => 'Inactive Student',
            'status' => StudentStatus::Inactive,
        ]);

        $this->actingAs($tutor)
            ->get(route('students.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Active Student')
            ->assertDontSee('Inactive Student');
    }

    public function test_students_can_be_filtered_by_inactive_status(): void
    {
        $tutor = User::factory()->create();

        Student::factory()->for($tutor)->create([
            'name' => 'Active Student',
            'status' => StudentStatus::Active,
        ]);
        Student::factory()->for($tutor)->create([
            'name' => 'Inactive Student',
            'status' => StudentStatus::Inactive,
        ]);

        $this->actingAs($tutor)
            ->get(route('students.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Inactive Student')
            ->assertDontSee('Active Student');
    }

    public function test_students_can_be_searched_by_name(): void
    {
        $tutor = User::factory()->create();

        Student::factory()->for($tutor)->create(['name' => 'Amara Silva', 'grade' => '10']);
        Student::factory()->for($tutor)->create(['name' => 'Ravi Perera', 'grade' => '11']);

        $this->actingAs($tutor)
            ->get(route('students.index', ['search' => 'Amara']))
            ->assertOk()
            ->assertSee('Amara Silva')
            ->assertDontSee('Ravi Perera');
    }

    public function test_students_can_be_searched_by_student_code(): void
    {
        $tutor = User::factory()->create();

        Student::factory()->for($tutor)->create([
            'name' => 'Amara Silva',
            'student_code' => 'STU-0100',
        ]);
        Student::factory()->for($tutor)->create([
            'name' => 'Ravi Perera',
            'student_code' => 'STU-0200',
        ]);

        $this->actingAs($tutor)
            ->get(route('students.index', ['search' => 'STU-0100']))
            ->assertOk()
            ->assertSee('Amara Silva')
            ->assertDontSee('Ravi Perera');
    }

    public function test_grade_filter_and_search_can_be_combined(): void
    {
        $tutor = User::factory()->create();

        Student::factory()->for($tutor)->create(['name' => 'Amara Grade 10', 'grade' => '10']);
        Student::factory()->for($tutor)->create(['name' => 'Ravi Grade 10', 'grade' => '10']);
        Student::factory()->for($tutor)->create(['name' => 'Sasha Grade 11', 'grade' => '11']);

        $this->actingAs($tutor)
            ->get(route('students.index', ['grade' => '10', 'search' => 'Amara']))
            ->assertOk()
            ->assertSee('Amara Grade 10')
            ->assertDontSee('Ravi Grade 10')
            ->assertDontSee('Sasha Grade 11');
    }

    public function test_student_index_shows_grade_column(): void
    {
        $tutor = User::factory()->create();
        Student::factory()->for($tutor)->create(['name' => 'Test Student', 'grade' => '12']);

        $this->actingAs($tutor)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('12');
    }

    public function test_student_index_shows_class_time_column(): void
    {
        $tutor = User::factory()->create();
        Student::factory()->for($tutor)->create([
            'name' => 'Test Student',
            'class_time' => 'Mon 18:00-20:00',
        ]);

        $this->actingAs($tutor)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Mon 18:00-20:00');
    }
}
