<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

use App\Models\HelpModule;


class HelpModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            ['name' => 'Översikt', 'label' => 'summary', 'icon' => 'custom-tabler'],
            ['name' => 'Kunder', 'label' => 'clients', 'icon' => 'custom-clients'],
            ['name' => 'Fakturor', 'label' => 'billings', 'icon' => 'custom-facture'],
            ['name' => 'Leverantörer', 'label' => 'suppliers', 'icon' => 'custom-supplier'],
            ['name' => 'Mitt Fordonslager', 'label' => 'vehicles', 'icon' => 'custom-car'],
            ['name' => 'I Lager', 'label' => 'stock', 'icon' => 'custom-lager'],
            ['name' => 'Sålda Fordon', 'label' => 'sold', 'icon' => 'custom-sold'],
            ['name' => 'Avtal', 'label' => 'agreements', 'icon' => 'custom-contract'],
            ['name' => 'E-signering', 'label' => 'documents', 'icon' => 'custom-signature'],
            ['name' => 'Swish', 'label' => 'payouts', 'icon' => 'custom-swish'],
            ['name' => 'Mina Värderingar', 'label' => 'notes', 'icon' => 'custom-cash'],
            ['name' => 'Attribut', 'label' => 'invoices', 'icon' => 'custom-attribut'],
            ['name' => 'Valuta', 'label' => 'currencies', 'icon' => 'custom-pris-information'],
            ['name' => 'Land', 'label' => 'countries', 'icon' => 'custom-flag'],
            ['name' => 'Planer', 'label' => 'plans', 'icon' => 'custom-cash'],
        ]; 
        // Crear o actualizar módulos
        $this->createOrUpdateModules($modules);

    }

    /**
     * Create or update modules in the database
     *
     * @param array $modules
     * @return void
     */
    private function createOrUpdateModules(array $modules)
    {
        foreach ($modules as $moduleData) {
            HelpModule::updateOrCreate(
                ['name' => $moduleData['name']], // Search by name
                [
                    'label' => $moduleData['label'],
                    'icon' => $moduleData['icon'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}
