<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = Section::factory()->create();
        Sanctum::actingAs($this->section->instructor);
    }

    // Makes a real .xlsx file with these rows (the first row is the headings).
    private function excelFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'students').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'students.xlsx', null, null, true);
    }

    private function import(UploadedFile $file)
    {
        return $this->postJson("/api/sections/{$this->section->id}/students/import", ['file' => $file]);
    }

    public function test_instructor_imports_students_from_excel(): void
    {
        $existing = User::factory()->create(['university_id' => 'S0001']);
        $alreadyInClass = User::factory()->create(['university_id' => 'S0002']);
        $this->section->students()->attach($alreadyInClass);

        $this->import($this->excelFile([
            ['University ID', 'Name'],
            ['S0001', 'Student One'],
            ['S0002', 'Student Two'],
            ['S0003', 'New Student'],
            [20231234, 'Number ID'], // Excel often stores IDs as numbers
        ]))
            ->assertOk()
            ->assertJson(['added' => 3, 'already_in_class' => 1, 'new_accounts' => 2, 'problems' => []]);

        $this->assertTrue($this->section->hasStudent($existing));
        $this->assertSame(4, $this->section->students()->count());

        $newStudent = User::where('university_id', 'S0003')->first();
        $this->assertSame('New Student', $newStudent->name);
        $this->assertSame('student', $newStudent->role);
        $this->assertTrue($newStudent->must_change_password);
        $this->assertTrue(Hash::check('S0003', $newStudent->password)); // first password = university ID
        $this->assertNotNull(User::where('university_id', '20231234')->first());
    }

    public function test_csv_files_work_too(): void
    {
        $file = UploadedFile::fake()->createWithContent('students.csv', "University ID,Name\nS0005,Student Five\n");

        $this->import($file)->assertOk()->assertJson(['added' => 1, 'new_accounts' => 1]);
    }

    public function test_bad_rows_are_skipped_with_a_reason(): void
    {
        User::factory()->instructor()->create(['university_id' => 'I2000']);

        $this->import($this->excelFile([
            ['Name', 'University ID'], // columns in any order
            ['No ID', ''],
            ['Dr. Someone', 'I2000'],
            ['', 'S0009'],
            ['', ''], // empty rows are ignored
            ['Good Student', 'S0010'],
        ]))
            ->assertOk()
            ->assertJson(['added' => 1, 'new_accounts' => 1])
            ->assertJsonPath('problems', [
                'Row 2: no university ID.',
                'Row 3: I2000 is not a student account.',
                'Row 4: S0009 has no account yet, so a name is needed.',
            ]);
    }

    public function test_file_needs_the_headings(): void
    {
        $this->import($this->excelFile([['ID', 'Student'], ['S0001', 'A']]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The first row of the file must have the headings "University ID" and "Name".');
    }

    public function test_only_the_sections_instructor_can_import(): void
    {
        Sanctum::actingAs(User::factory()->instructor()->create());
        $this->import($this->excelFile([['University ID', 'Name'], ['S0001', 'A']]))->assertForbidden();

        Sanctum::actingAs(User::factory()->create());
        $this->import($this->excelFile([['University ID', 'Name'], ['S0001', 'A']]))->assertForbidden();

        $this->assertDatabaseCount('enrollments', 0);
    }
}
