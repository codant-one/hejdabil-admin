<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

use App\Models\Country;

class UpdateNameCountries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'countries:update-name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the country name to the new name when the name field is different from the old name';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filePath = storage_path('app/files/countries.xlsx');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();

            $translationsById = [];

            for ($row = 2; $row <= $highestRow; $row++) {
                $id = (int) $sheet->getCellByColumnAndRow(1, $row)->getValue();
                $translation = trim((string) $sheet->getCellByColumnAndRow(3, $row)->getValue());

                if ($id > 0 && $translation !== '') {
                    $translationsById[$id] = $translation;
                }
            }

            if (empty($translationsById)) {
                $this->warn('No valid data found in the Excel file.');
                return self::SUCCESS;
            }

            $updated = 0;
            $notFoundInExcel = 0;

            foreach (Country::select('id', 'name')->cursor() as $country) {
                if (!isset($translationsById[$country->id])) {
                    $notFoundInExcel++;
                    continue;
                }

                $newName = $translationsById[$country->id];

                if ($country->name !== $newName) {
                    $country->name = $newName;
                    $country->save();
                    $updated++;
                }
            }

            $this->info("Updated countries: {$updated}");
            $this->info("Countries without match in Excel: {$notFoundInExcel}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Error processing Excel file: ' . $exception->getMessage());
            return self::FAILURE;
        }

    }
}
