<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;

use Carbon\Carbon;
use Str;

use App\Models\HelpCategory;

class HelpCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Översikt', 'label' => 'summary', 'icon' => 'help-categories/summary.svg'],
            ['name' => 'Kunder', 'label' => 'clients', 'icon' => 'help-categories/clients.svg'],
            ['name' => 'Fakturor', 'label' => 'billings', 'icon' => 'help-categories/billings.svg'],
            ['name' => 'Leverantörer', 'label' => 'suppliers', 'icon' => 'help-categories/suppliers.svg'],
            ['name' => 'Mitt Fordonslager', 'label' => 'vehicles', 'icon' => 'help-categories/vehicles.svg'],
            ['name' => 'I Lager', 'label' => 'stock', 'icon' => 'help-categories/stock.svg'],
            ['name' => 'Sålda Fordon', 'label' => 'sold', 'icon' => 'help-categories/sold.svg'],
            ['name' => 'Avtal', 'label' => 'agreements', 'icon' => 'help-categories/agreements.svg'],
            ['name' => 'E-signering', 'label' => 'documents', 'icon' => 'help-categories/documents.svg'],
            ['name' => 'Swish', 'label' => 'payouts', 'icon' => 'help-categories/payouts.svg'],
            ['name' => 'Mina Värderingar', 'label' => 'notes', 'icon' => 'help-categories/notes.svg'],
            ['name' => 'Attribut', 'label' => 'invoices', 'icon' => 'help-categories/invoices.svg'],
            ['name' => 'Valuta', 'label' => 'currencies', 'icon' => 'help-categories/currencies.svg'],
            ['name' => 'Land', 'label' => 'countries', 'icon' => 'help-categories/countries.svg'],
            ['name' => 'Planer', 'label' => 'plans', 'icon' => 'help-categories/plans.svg'],
            ['name' => 'Mitt team', 'label' => 'teams', 'icon' => 'help-categories/teams.svg'],
            ['name' => 'Användare', 'label' => 'users', 'icon' => 'help-categories/users.svg'],
            ['name' => 'Roller', 'label' => 'roles', 'icon' => 'help-categories/roles.svg'],
        ]; 


        // Crear o actualizar módulos
        $this->createOrUpdateCategories($categories);

    }

    /**
     * Create or update categories in the database
     *
     * @param array $categories
     * @return void
     */
    private function createOrUpdateCategories(array $categories)
    {
        if (!file_exists(storage_path('app/public/help-categories'))) {
            mkdir(storage_path('app/public/help-categories'), 0755,true);
        } //create a folder

        $file = new Filesystem;
        $file->cleanDirectory('storage/app/public/help-categories');

        foreach ($categories as $categoryData) {
            HelpCategory::updateOrCreate(
                ['name' => $categoryData['name']], // Search by name
                [
                    'label' => $categoryData['label'],
                    'icon' => $categoryData['icon'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );

            if($categoryData['icon'] !== 'null' && $categoryData['icon'] !== null)
                copy(public_path($categoryData['icon']), storage_path('app/public/').$categoryData['icon']);
        }
    }
}
