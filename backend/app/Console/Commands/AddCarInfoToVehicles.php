<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Vehicle;
use App\Models\CarModel;
use App\Services\CarInfo;

class AddCarInfoToVehicles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vehicles:add-car-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch data from CarInfo API by reg_num and update vehicles table';

    protected CarInfo $carInfoService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CarInfo $carInfoService)
    {
        parent::__construct();

        $this->carInfoService = $carInfoService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $total = Vehicle::query()->count();

        if ($total === 0) {
            $this->info('No vehicles found.');
            return self::SUCCESS;
        }

        $processed = 0;
        $updated = 0;
        $skippedNoPlate = 0;
        $skippedNoResult = 0;
        $failed = 0;

        $this->info("Starting CarInfo sync for {$total} vehicles...");

        $vehicles = Vehicle::query()
            ->select(['id', 'reg_num', 'model_id', 'year', 'generation', 'car_body_id', 'fuel_id', 'gearbox_id', 'color', 'mileage', 'control_inspection', 'chassis', 'engine', 'car_name'])
            ->orderBy('id')
            ->get();

        foreach ($vehicles as $vehicle) {
            $processed++;

            $plate = strtoupper(trim((string) $vehicle->reg_num));

            if ($plate === '') {
                $skippedNoPlate++;
                continue;
            }

            try {
                $carRes = $this->carInfoService->getByLicensePlate($plate);

                $isSuccess = (($carRes['success'] ?? false) === true) || (($carRes['sucess'] ?? false) === true);
                $result = $carRes['result'] ?? null;

                if (!$isSuccess || !is_array($result)) {
                    $skippedNoResult++;
                    continue;
                }

                $dataToUpdate = [];

                if (!empty($result['model_year'])) {
                    $dataToUpdate['year'] = $result['model_year'];
                }

                if (!empty($result['generation'])) {
                    $dataToUpdate['generation'] = $result['generation'];
                }

                if (!empty($result['model_id'])) {
                    $dataToUpdate['model_id'] = $result['model_id'];
                } elseif (!empty($result['model_name']) && !empty($result['brand_id'])) {
                    $modelName = trim((string) $result['model_name']);
                    $brandId = (int) $result['brand_id'];

                    if ($modelName !== '' && $brandId > 0) {
                        $carModel = CarModel::firstOrCreate(
                            [
                                'brand_id' => $brandId,
                                'name' => $modelName,
                            ],
                            [
                                'brand_id' => $brandId,
                                'name' => $modelName,
                            ]
                        );

                        $dataToUpdate['model_id'] = $carModel->id;
                    }
                }

                if (!empty($result['car_body_id'])) {
                    $dataToUpdate['car_body_id'] = $result['car_body_id'];
                }

                if (!empty($result['fuel_id'])) {
                    $dataToUpdate['fuel_id'] = $result['fuel_id'];
                }

                if (!empty($result['gearbox_id'])) {
                    $dataToUpdate['gearbox_id'] = $result['gearbox_id'];
                }

                if (!empty($result['color'])) {
                    $dataToUpdate['color'] = $result['color'];
                }

                if (!empty($result['mileage'])) {
                    $dataToUpdate['mileage'] = $result['mileage'];
                }

                if (!empty($result['control_inspection'])) {
                    $dataToUpdate['control_inspection'] = $result['control_inspection'];
                }

                if (!empty($result['chassis_number'])) {
                    $dataToUpdate['chassis'] = $result['chassis_number'];
                }

                if (!empty($result['engine'])) {
                    $dataToUpdate['engine'] = $result['engine'];
                }

                if (!empty($result['car_name'])) {
                    $dataToUpdate['car_name'] = $result['car_name'];
                }

                if (empty($dataToUpdate)) {
                    $skippedNoResult++;
                    continue;
                }

                $vehicle->fill($dataToUpdate);

                if ($vehicle->isDirty()) {
                    $vehicle->save();
                    $updated++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("Vehicle ID {$vehicle->id} ({$plate}) failed: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('CarInfo sync completed.');
        $this->line("Processed: {$processed}");
        $this->line("Updated: {$updated}");
        $this->line("Skipped (no plate): {$skippedNoPlate}");
        $this->line("Skipped (no API data): {$skippedNoResult}");
        $this->line("Failed: {$failed}");

        return self::SUCCESS;
    }
}
