<?php

namespace Database\Seeders;

use App\Models\HelpModule;
use App\Models\HelpVideo;
use Illuminate\Database\Seeder;

class HelpVideoSeeder extends Seeder
{
    public function run(): void
    {
        $tutorials = [
            'summary' => ['Så läser du översikten', 'Anpassa din översikt', 'Följ upp dina resultat'],
            'clients' => ['Skapa en ny kund', 'Redigera kunduppgifter', 'Hitta kundens historik'],
            'billings' => ['Skapa en faktura', 'Skicka en faktura', 'Följ upp betalningar'],
            'vehicles' => ['Lägg till ett fordon', 'Redigera fordonsuppgifter', 'Markera ett fordon som sålt'],
            'agreements' => ['Skapa ett avtal', 'Redigera ett avtalsutkast', 'Hitta dina avtal'],
            'documents' => ['Skicka dokument för signering', 'Följ signeringsstatus', 'Hämta signerade dokument'],
        ];

        foreach ($tutorials as $label => $titles) {
            $module = HelpModule::where('label', $label)->firstOrFail();

            foreach ($titles as $title) {
                HelpVideo::updateOrCreate(
                    ['help_module_id' => $module->id, 'title' => $title],
                    [
                        'description' => 'En steg-för-steg-guide: '.$title.'.',
                        'video_url' => 'https://www.youtube.com/embed/aqz-KE-bpKQ',
                        'provider' => 'youtube',
                        'thumbnail_url' => 'https://img.youtube.com/vi/aqz-KE-bpKQ/hqdefault.jpg',
                        'state_id' => 2,
                    ]
                );
            }
        }
    }
}
