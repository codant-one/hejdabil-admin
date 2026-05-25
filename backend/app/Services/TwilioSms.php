<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioSms
{
    public const INVALID_RECIPIENT_MESSAGE = 'SMS-kunde inte skickas. Kontrollera att telefonnumret är korrekt och försök igen.';
    private const EUROPEAN_DESTINATION_ISOS = [
        'AD', 'AL', 'AT', 'BA', 'BE', 'BG', 'BY', 'CH', 'CY', 'CZ', 'DE', 'DK',
        'EE', 'ES', 'FI', 'FO', 'FR', 'GB', 'GG', 'GI', 'GR', 'HR', 'HU', 'IE',
        'IM', 'IS', 'IT', 'JE', 'LI', 'LT', 'LU', 'LV', 'MC', 'MD', 'ME', 'MK',
        'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'RS', 'SE', 'SI', 'SJ', 'SK', 'SM',
        'UA', 'VA', 'XK',
    ];

    protected $sid;
    protected $authToken;
    protected $messagingServiceSid;
    protected $phoneNumber;
    protected $client;
    protected $countriesByPhoneCode;

    public function __construct()
    {
        $this->sid = env('TWILIO_SID');
        $this->authToken = env('TWILIO_AUTH_TOKEN');
        $this->messagingServiceSid = env('TWILIO_MESSAGING_SERVICE_SID');
        $this->phoneNumber = env('TWILIO_PHONE_NUMBER');
        $this->client = $this->sid && $this->authToken
            ? new Client($this->sid, $this->authToken)
            : null;
    }

    public function sendMessage($to, $message, $smsSender = null)
    {
        $sanitizedSmsSender = $smsSender !== null
            ? $this->sanitizeSmsSender($smsSender)
            : null;
        $destinationCountry = $this->resolveCountryFromPhone((string) $to);
        $usesSmsSender = $sanitizedSmsSender !== null
            && $this->isEuropeanDestinationCountry($destinationCountry['iso'] ?? null);
        $smsSenderBlockedReason = null;
        $resolvedFrom = null;
        $resolvedMessagingServiceSid = null;

        if ($sanitizedSmsSender !== null && !$usesSmsSender) {
            $smsSenderBlockedReason = $destinationCountry === null
                ? 'destination_country_unresolved'
                : 'destination_not_in_europe';
        }

        try {
            if (!$this->client || (!$usesSmsSender && !$this->messagingServiceSid && !$this->phoneNumber)) {
                return 'Twilio är inte korrekt konfigurerat.';
            }

            $payload = [
                'body' => $message,
            ];

            if ($usesSmsSender) {
                $resolvedFrom = $sanitizedSmsSender;
                $payload['from'] = $resolvedFrom;
            } elseif ($this->messagingServiceSid) {
                $resolvedMessagingServiceSid = $this->messagingServiceSid;
                $payload['messagingServiceSid'] = $resolvedMessagingServiceSid;
            } else {
                $resolvedFrom = $this->phoneNumber;
                $payload['from'] = $resolvedFrom;
            }

            $this->client->messages->create($to, $payload);

            return true;
        } catch (\Throwable $exception) {
            $resolvedMessage = $this->resolveFailureMessage($exception);

            Log::error('Twilio SMS send failed', [
                'to' => $to,
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'resolved_message' => $resolvedMessage,
                'attempted_sms_sender' => $smsSender,
                'sanitized_sms_sender' => $sanitizedSmsSender,
                'uses_sms_sender' => $usesSmsSender,
                'sms_sender_blocked_reason' => $smsSenderBlockedReason,
                'destination_country_id' => $destinationCountry['id'] ?? null,
                'destination_country_iso' => $destinationCountry['iso'] ?? null,
                'destination_country_phonecode' => $destinationCountry['phonecode'] ?? null,
                'from' => $resolvedFrom,
                'messaging_service_sid' => $resolvedMessagingServiceSid,
            ]);

            return $resolvedMessage;
        }
    }

    private function resolveCountryFromPhone(string $phone): ?array
    {
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

    private function getCountriesByPhoneCode()
    {
        if ($this->countriesByPhoneCode !== null) {
            return $this->countriesByPhoneCode;
        }

        $this->countriesByPhoneCode = Country::query()
            ->select('id', 'iso', 'phonecode')
            ->get()
            ->map(function ($country) {
                return [
                    'id' => $country->id,
                    'iso' => $country->iso ? Str::upper((string) $country->iso) : null,
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

    private function isEuropeanDestinationCountry(?string $iso): bool
    {
        if ($iso === null) {
            return false;
        }

        return in_array(Str::upper($iso), self::EUROPEAN_DESTINATION_ISOS, true);
    }

    private function hasExplicitInternationalPrefix(string $phone): bool
    {
        return str_starts_with(ltrim($phone), '+');
    }

    private function extractPhoneDigits($value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function isInvalidRecipientMessage(?string $message): bool
    {
        return $message === self::INVALID_RECIPIENT_MESSAGE;
    }

    private function sanitizeSmsSender($name): ?string
    {
        $normalizedName = strtr((string) $name, [
            'å' => 'a',
            'ä' => 'a',
            'ö' => 'o',
            'Å' => 'A',
            'Ä' => 'A',
            'Ö' => 'O',
        ]);

        $normalizedName = preg_replace('/[^A-Za-z0-9 ]+/', '', $normalizedName);
        $normalizedName = Str::squish((string) $normalizedName);

        if ($normalizedName === '') {
            return null;
        }

        return substr($normalizedName, 0, 11);
    }

    private function resolveFailureMessage(\Throwable $exception): string
    {
        $errorCode = (int) $exception->getCode();
        $errorMessage = (string) $exception->getMessage();
        $normalizedMessage = Str::lower($errorMessage);

        if (in_array($errorCode, [21211, 21614], true)) {
            return self::INVALID_RECIPIENT_MESSAGE;
        }

        if (Str::contains($normalizedMessage, [
            'invalid to phone number',
            'invalid phone number',
            'not a valid mobile number',
            'not a valid phone number',
            'landline',
        ])) {
            return self::INVALID_RECIPIENT_MESSAGE;
        }

        return $errorMessage;
    }
}