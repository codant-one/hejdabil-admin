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
        $types = ['Externt dokument', 'Försäljningskvitto', 'Försäljningsavtal'];
        $labels = ['external_document', 'sales_receipt', 'sales_agreement',];

        foreach($types as $key => $type) {
            DocumentType::updateOrCreate(
                ['name' => $type],
                ['label' => strtolower($labels[$key])] 
            );
        }

    }
}
