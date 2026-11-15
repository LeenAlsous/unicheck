<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\SpreadsheetReader;
use Illuminate\Console\Command;

// php artisan import:enrollments enrollments.xlsx
//
// Puts students in sections, from an Excel or CSV file with the headings:
//   University ID, Course code, Section, Semester      (needed)
//   Course name, Instructor ID                          (only needed for a section that doesn't exist yet)
// Example row: S0001, CS330, 1, 2026-1, Database Systems, I1001
// The students must already have accounts (import them first with import:users).
class ImportEnrollments extends Command
{
    protected $signature = 'import:enrollments {file : Excel or CSV file with University ID, Course code, Section, Semester}';

    protected $description = 'Enroll students in sections from an Excel or CSV file';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! file_exists($path)) {
            $this->error("File not found: $path");

            return self::FAILURE;
        }

        $sheet = SpreadsheetReader::read($path, pathinfo($path, PATHINFO_EXTENSION));

        foreach (['university id', 'course code', 'section', 'semester'] as $heading) {
            if (! in_array($heading, $sheet['headings'])) {
                $this->error('The first row must have the headings: University ID, Course code, Section, Semester (and optionally Course name, Instructor ID).');

                return self::FAILURE;
            }
        }

        $added = 0;
        $alreadyEnrolled = 0;

        foreach ($sheet['rows'] as $rowNumber => $row) {
            $student = User::where('university_id', $row['university id'])->where('role', 'student')->first();
            if (! $student) {
                $this->warn("Row $rowNumber: no student account with ID \"{$row['university id']}\". Skipped.");

                continue;
            }

            $section = $this->findOrCreateSection($row, $rowNumber);
            if (! $section) {
                continue;
            }

            if ($section->hasStudent($student)) {
                $alreadyEnrolled++;
            } else {
                $section->students()->attach($student->id);
                $added++;
            }
        }

        $this->info("Enrolled $added students. $alreadyEnrolled were already enrolled.");

        return self::SUCCESS;
    }

    // Finds the section in the row, or creates it (and its course) if the row has
    // the course name and the instructor's ID. Returns null and warns if it can't.
    private function findOrCreateSection(array $row, int $rowNumber): ?Section
    {
        $course = Course::where('code', $row['course code'])->first();

        $section = $course?->sections()
            ->where('number', $row['section'])
            ->where('semester', $row['semester'])
            ->first();

        if ($section) {
            return $section;
        }

        $courseName = $row['course name'] ?? '';
        $instructor = User::where('university_id', $row['instructor id'] ?? '')->where('role', 'instructor')->first();

        if ((! $course && $courseName === '') || ! $instructor) {
            $this->warn("Row $rowNumber: section {$row['course code']}/{$row['section']} ({$row['semester']}) doesn't exist. "
                .'Add "Course name" and "Instructor ID" columns to create it. Skipped.');

            return null;
        }

        $course ??= Course::create(['code' => $row['course code'], 'name' => $courseName]);

        return $course->sections()->create([
            'number' => $row['section'],
            'semester' => $row['semester'],
            'instructor_id' => $instructor->id,
        ]);
    }
}
