<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = ['Serviceprotokoll', 'Köpeavtal', 'Besiktningsintyg', 'Övrigt'];
        $labels = ['service_log', 'purchase_agreement', 'inspection_certificate', 'other'];

        foreach($types as $key => $type) {
            DocumentType::updateOrCreate(
                ['name' => $type],
                ['label' => strtolower($labels[$key])] 
            );
        }

    }
}
