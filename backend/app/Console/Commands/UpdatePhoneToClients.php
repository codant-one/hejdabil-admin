<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Client;
use App\Models\CommissionClient;
use App\Models\VehicleClient;
use App\Models\AgreementClient;
use App\Models\Country;
use App\Models\Note;
use App\Models\UserDetails;

class UpdatePhoneToClients extends Command
{
    private $countriesByPhoneCode = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:update-phone';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update phone numbers for clients';

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
        $this->clients();
        $this->commission_clients();
        $this->vehicle_clients();
        $this->agreement_clients();
        $this->notes();
        $this->user_details();

        return 0;
    }

    private function clients() {
        $clients = Client::withTrashed()
            ->with('country:id,phonecode')
            ->whereNotNull('phone')
            ->get();

        foreach ($clients as $client) {
            $this->logPhoneAudit('clients', $client, $client->client_type_id, $client->country?->phonecode);
        }

        return 0;
    }

    private function logPhoneAudit(string $source, $model, $clientTypeId = null, $countryPhoneCode = null) {
        $originalPhone = trim((string) $model->phone);

        if ($originalPhone === '') {
            return;
        }

        $hasExplicitInternationalPrefix = $this->hasExplicitInternationalPrefix($originalPhone);
        $matchedCountry = $this->resolveCountryFromPhone($originalPhone);
        $effectiveClientTypeId = $clientTypeId;
        $effectiveCountryPhoneCode = $countryPhoneCode;
        $shouldSaveModel = false;

        if ($matchedCountry !== null) {
            $effectiveClientTypeId = 3;
            $effectiveCountryPhoneCode = $matchedCountry['phonecode'];

            if ($this->supportsClientTypeId($model) && (int) $model->client_type_id !== 3) {
                $model->client_type_id = 3;
                $shouldSaveModel = true;
            }

            if ($this->supportsCountryId($model) && (int) $model->country_id !== (int) $matchedCountry['id']) {
                $model->country_id = $matchedCountry['id'];
                $shouldSaveModel = true;
            }
        }

        $normalizedPhone = $this->normalizePhoneForAudit(
            $originalPhone,
            $effectiveClientTypeId,
            $effectiveCountryPhoneCode,
            $hasExplicitInternationalPrefix
        );

        if ($normalizedPhone === null) {
            return;
        }

        if ($originalPhone !== $normalizedPhone) {
            $model->phone = $normalizedPhone;
            $shouldSaveModel = true;
        }

        if ($shouldSaveModel) {
            $model->saveQuietly();
        }

        $this->info(
            'Tabla: ' . $source
            . ' | Numero actual: ' . $this->formatInfoValue($originalPhone)
            . ' | Numero actualizado: ' . $this->formatInfoValue($normalizedPhone)
        );
    }

    private function normalizePhoneForAudit(
        string $originalPhone,
        $clientTypeId = null,
        $countryPhoneCode = null,
        bool $hasExplicitInternationalPrefix = false
    ) {
        $cleanedPhone = $this->extractPhoneDigits($originalPhone);

        if ($cleanedPhone === '') {
            return null;
        }

        if ((int) $clientTypeId === 3) {
            $phoneCode = $this->extractPhoneDigits($countryPhoneCode);

            if ($phoneCode !== '') {
                return $this->prependPhoneCode($cleanedPhone, $phoneCode);
            }

            return $hasExplicitInternationalPrefix ? '+' . $cleanedPhone : $cleanedPhone;
        }

        if ($hasExplicitInternationalPrefix) {
            return '+' . $cleanedPhone;
        }

        if ($clientTypeId === null || in_array((int) $clientTypeId, [1, 2], true)) {
            return $this->prependPhoneCode($cleanedPhone, '46');
        }

        return $cleanedPhone;
    }

    private function hasExplicitInternationalPrefix(string $phone) {
        return str_starts_with(ltrim($phone), '+');
    }

    private function resolveCountryFromPhone(string $phone) {
        if (!$this->hasExplicitInternationalPrefix($phone)) {
            return null;
        }

        $normalizedPhone = $this->extractPhoneDigits($phone);

        if ($normalizedPhone === '') {
            return null;
        }

        return $this->getCountriesByPhoneCode()->first(function ($country) use ($normalizedPhone) {
            return str_starts_with($normalizedPhone, $country['phonecode']);
        });
    }

    private function getCountriesByPhoneCode() {
        if ($this->countriesByPhoneCode !== null) {
            return $this->countriesByPhoneCode;
        }

        $this->countriesByPhoneCode = Country::query()
            ->select('id', 'phonecode')
            ->get()
            ->map(function ($country) {
                return [
                    'id' => $country->id,
                    'phonecode' => $this->extractPhoneDigits($country->phonecode),
                ];
            })
            ->filter(function ($country) {
                return $country['phonecode'] !== '';
            })
            ->sortByDesc(function ($country) {
                return strlen($country['phonecode']);
            })
            ->values();

        return $this->countriesByPhoneCode;
    }

    private function supportsClientTypeId($model) {
        return $model instanceof Client
            || $model instanceof CommissionClient
            || $model instanceof VehicleClient
            || $model instanceof AgreementClient;
    }

    private function supportsCountryId($model) {
        return $model instanceof Client
            || $model instanceof VehicleClient
            || $model instanceof AgreementClient;
    }

    private function extractPhoneDigits($value) {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function prependPhoneCode(string $digits, string $phoneCode) {
        $normalizedDigits = $this->extractPhoneDigits($digits);
        $normalizedPhoneCode = $this->extractPhoneDigits($phoneCode);

        if ($normalizedDigits === '' || $normalizedPhoneCode === '') {
            return $normalizedDigits;
        }

        if ($normalizedPhoneCode === '46') {
            if (str_starts_with($normalizedDigits, $normalizedPhoneCode)) {
                $normalizedDigits = $normalizedPhoneCode . preg_replace('/^0/', '', substr($normalizedDigits, strlen($normalizedPhoneCode)));
            } else {
                $normalizedDigits = preg_replace('/^0/', '', $normalizedDigits);
            }
        }

        if (str_starts_with($normalizedDigits, $normalizedPhoneCode)) {
            return '+' . $normalizedDigits;
        }

        return '+' . $normalizedPhoneCode . $normalizedDigits;
    }

    private function formatInfoValue($value) {
        return json_encode((string) $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    private function commission_clients() {
        $commissionClients = CommissionClient::whereNotNull('phone')->get();

        foreach ($commissionClients as $commissionClient) {
            $this->logPhoneAudit('commission_clients', $commissionClient, $commissionClient->client_type_id);
        }

        return 0;
    }

    private function agreement_clients() {
        $agreementClients = AgreementClient::with('country:id,phonecode')
            ->whereNotNull('phone')
            ->get();

        foreach ($agreementClients as $agreementClient) {
            $this->logPhoneAudit('agreement_clients', $agreementClient, $agreementClient->client_type_id, $agreementClient->country?->phonecode);
        }

        return 0;
    }

    private function vehicle_clients() {
        $vehicleClients = VehicleClient::with('country:id,phonecode')
            ->whereNotNull('phone')
            ->get();

        foreach ($vehicleClients as $vehicleClient) {
            $this->logPhoneAudit('vehicle_clients', $vehicleClient, $vehicleClient->client_type_id, $vehicleClient->country?->phonecode);
        }

        return 0;
    }

    private function notes() {
        $notes = Note::whereNotNull('phone')->get();

        foreach ($notes as $note) {
            $this->logPhoneAudit('notes', $note);
        }

        return 0;
    }

    private function user_details() {
        $userDetails = UserDetails::whereNotNull('phone')->get();

        foreach ($userDetails as $userDetail) {
            $this->logPhoneAudit('user_details', $userDetail);
        }

        return 0;
    }
}
