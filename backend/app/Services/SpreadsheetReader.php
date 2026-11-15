<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

// Reads an Excel (.xlsx, .xls) or .csv file whose first row has the column headings.
class SpreadsheetReader
{
    // Returns the headings and the rows. Headings are in lowercase and are the keys of each row:
    //   'headings' => ['university id', 'name'],
    //   'rows'     => [2 => ['university id' => 'S0001', 'name' => 'Ali Ahmad'], 3 => [...]]
    // Each row's key is its row number in the file (the headings are row 1), so error
    // messages can say "Row 7: ...". Empty rows are skipped.
    public static function read(string $path, string $extension): array
    {
        $type = match (strtolower($extension)) {
            'xlsx' => 'Xlsx',
            'xls' => 'Xls',
            default => 'Csv',
        };

        $cells = IOFactory::createReader($type)->load($path)->getActiveSheet()->toArray(null, true, false);

        $headings = array_map(fn ($heading) => strtolower(trim((string) $heading)), $cells[0] ?? []);

        $rows = [];
        foreach (array_slice($cells, 1) as $index => $row) {
            $values = array_map(fn ($value) => trim((string) $value), $row);

            if (implode('', $values) === '') {
                continue; // empty row
            }

            $rows[$index + 2] = array_combine($headings, $values);
        }

        return ['headings' => $headings, 'rows' => $rows];
    }
}
