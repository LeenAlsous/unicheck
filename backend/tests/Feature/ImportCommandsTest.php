<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportCommandsTest extends TestCase
{
    use RefreshDatabase;

    private function csvFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import').'.csv';
        file_put_contents($path, $content);

        return $path;
    }

    public function test_import_users_creates_accounts(): void
    {
        User::factory()->create(['university_id' => 'S0001']);

        $file = $this->csvFile("University ID,Name,Role\nS0001,Old Student,student\nS0002,New Student,student\nI3000,Dr. New,Instructor\nX1,Someone,teacher\n");

        $this->artisan('import:users', ['file' => $file])
            ->expectsOutputToContain('Row 5: role must be student, instructor or admin. Skipped.')
            ->expectsOutputToContain('Created 2 accounts. 1 already existed.')
            ->assertSuccessful();

        $this->assertSame('instructor', User::where('university_id', 'I3000')->first()->role);
        $this->assertTrue(User::where('university_id', 'S0002')->first()->must_change_password);
    }

    public function test_import_enrollments_into_existing_and_new_sections(): void
    {
        $section = Section::factory()->create(['number' => '1', 'semester' => '2026-1']);
        $courseCode = $section->course->code;
        User::factory()->create(['university_id' => 'S0001']);
        User::factory()->create(['university_id' => 'S0002']);
        User::factory()->instructor()->create(['university_id' => 'I1001']);

        $file = $this->csvFile(
            "University ID,Course code,Section,Semester,Course name,Instructor ID\n"
            ."S0001,$courseCode,1,2026-1,,\n"            // existing section
            ."S0002,CS999,2,2026-1,AI Basics,I1001\n"   // new course and section
            ."S0002,CS888,1,2026-1,,\n"                 // new section without the details: skipped
            ."S9999,$courseCode,1,2026-1,,\n"            // no such student: skipped
        );

        $this->artisan('import:enrollments', ['file' => $file])
            ->expectsOutputToContain('Row 4: section CS888/1 (2026-1) doesn\'t exist.')
            ->expectsOutputToContain('Row 5: no student account with ID "S9999". Skipped.')
            ->expectsOutputToContain('Enrolled 2 students. 0 were already enrolled.')
            ->assertSuccessful();

        $newSection = Course::where('code', 'CS999')->first()->sections()->first();
        $this->assertSame('AI Basics', $newSection->course->name);
        $this->assertSame('I1001', $newSection->instructor->university_id);
        $this->assertSame(1, $newSection->students()->count());
        $this->assertSame(1, $section->students()->count());
    }

    public function test_missing_file_or_headings_fail(): void
    {
        $this->artisan('import:users', ['file' => '/no/such/file.csv'])->assertFailed();
        $this->artisan('import:users', ['file' => $this->csvFile("ID,Name\nS1,A\n")])->assertFailed();
    }
}
