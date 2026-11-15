<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SpreadsheetReader;
use Illuminate\Console\Command;

// php artisan import:users students.xlsx
//
// Creates accounts from an Excel or CSV file with the headings: University ID, Name, Role.
// Role is "student", "instructor" or "admin". The first password is the university ID,
// and the app asks the user to change it. Accounts that already exist are skipped.
class ImportUsers extends Command
{
    protected $signature = 'import:users {file : Excel or CSV file with University ID, Name, Role}';

    protected $description = 'Create student and instructor accounts from an Excel or CSV file';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! file_exists($path)) {
            $this->error("File not found: $path");

            return self::FAILURE;
        }

        $sheet = SpreadsheetReader::read($path, pathinfo($path, PATHINFO_EXTENSION));

        foreach (['university id', 'name', 'role'] as $heading) {
            if (! in_array($heading, $sheet['headings'])) {
                $this->error('The first row must have the headings: University ID, Name, Role.');

                return self::FAILURE;
            }
        }

        $created = 0;
        $alreadyExist = 0;

        foreach ($sheet['rows'] as $rowNumber => $row) {
            $universityId = $row['university id'];
            $name = $row['name'];
            $role = strtolower($row['role']);

            if ($universityId === '' || $name === '') {
                $this->warn("Row $rowNumber: university ID and name are both needed. Skipped.");

                continue;
            }
            if (! in_array($role, ['student', 'instructor', 'admin'])) {
                $this->warn("Row $rowNumber: role must be student, instructor or admin. Skipped.");

                continue;
            }
            if (User::where('university_id', $universityId)->exists()) {
                $alreadyExist++;

                continue;
            }

            User::newAccount($universityId, $name, $role);
            $created++;
        }

        $this->info("Created $created accounts. $alreadyExist already existed.");

        return self::SUCCESS;
    }
}
