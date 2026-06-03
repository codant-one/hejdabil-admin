<?php

namespace App\Services;

use App\Models\Country;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Auth;
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

    public function sendMessage($to, $message, $smsSender = null, array $context = [])
    {
        $sanitizedSmsSender = $smsSender !== null
            ? $this->sanitizeSmsSender($smsSender)
            : null;
        $normalizedContext = $this->normalizeMessageContext($context);
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
                $failureMessage = 'Twilio är inte korrekt konfigurerat.';

                $this->storeMessageLog(
                    context: $normalizedContext,
                    to: $to,
                    message: $message,
                    smsSender: $sanitizedSmsSender,
                    fromNumber: $resolvedFrom,
                    messagingServiceSid: $resolvedMessagingServiceSid,
                    status: SmsMessage::STATUS_FAILED,
                    billableCount: 0,
                    providerErrorMessage: $failureMessage,
                    outcomeAt: now()
                );

                return $failureMessage;
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

            $twilioMessage = $this->client->messages->create($to, $payload);

            $this->storeMessageLog(
                context: $normalizedContext,
                to: $to,
                message: $message,
                smsSender: $sanitizedSmsSender,
                fromNumber: $resolvedFrom,
                messagingServiceSid: $resolvedMessagingServiceSid,
                status: $this->normalizeNullableString($twilioMessage->status ?? null) ?? SmsMessage::STATUS_ACCEPTED,
                billableCount: 1,
                providerMessageSid: $this->normalizeNullableString($twilioMessage->sid ?? null),
                outcomeAt: now()
            );

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

            $this->storeMessageLog(
                context: $normalizedContext,
                to: $to,
                message: $message,
                smsSender: $sanitizedSmsSender,
                fromNumber: $resolvedFrom,
                messagingServiceSid: $resolvedMessagingServiceSid,
                status: SmsMessage::STATUS_FAILED,
                billableCount: 0,
                providerErrorCode: $this->normalizeNullableString((string) $exception->getCode()),
                providerErrorMessage: $resolvedMessage,
                outcomeAt: now()
            );

            return $resolvedMessage;
        }
    }

    private function storeMessageLog(
        array $context,
        $to,
        $message,
        ?string $smsSender,
        ?string $fromNumber,
        ?string $messagingServiceSid,
        string $status,
        int $billableCount,
        ?string $providerMessageSid = null,
        ?string $providerErrorCode = null,
        ?string $providerErrorMessage = null,
        $outcomeAt = null,
    ): void {
        try {
            $timestamp = $outcomeAt ?? now();

            SmsMessage::create([
                'supplier_id' => $context['supplier_id'],
                'user_id' => $context['user_id'],
                'source_type' => $context['source_type'],
                'source_id' => $context['source_id'],
                'action_type' => $context['action_type'],
                'provider' => 'twilio',
                'to_number' => (string) $to,
                'message' => (string) $message,
                'sms_sender' => $smsSender,
                'from_number' => $fromNumber,
                'messaging_service_sid' => $messagingServiceSid,
                'provider_message_sid' => $providerMessageSid,
                'status' => $status,
                'billing_month' => SmsMessage::resolveBillingMonth($timestamp),
                'billable_count' => $billableCount,
                'sent_at' => $billableCount > 0 ? $timestamp : null,
                'failed_at' => $billableCount === 0 ? $timestamp : null,
                'provider_error_code' => $providerErrorCode,
                'provider_error_message' => $providerErrorMessage,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Twilio SMS log persistence failed', [
                'to' => $to,
                'status' => $status,
                'supplier_id' => $context['supplier_id'] ?? null,
                'user_id' => $context['user_id'] ?? null,
                'source_type' => $context['source_type'] ?? null,
                'source_id' => $context['source_id'] ?? null,
                'action_type' => $context['action_type'] ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function normalizeMessageContext(array $context): array
    {
        return [
            'supplier_id' => $this->resolveSupplierId($context),
            'user_id' => $this->resolveUserId($context),
            'source_type' => $this->normalizeNullableString($context['source_type'] ?? null),
            'source_id' => $this->normalizeNullableInteger($context['source_id'] ?? null),
            'action_type' => $this->normalizeNullableString($context['action_type'] ?? null),
        ];
    }

    private function resolveSupplierId(array $context): ?int
    {
        if (array_key_exists('supplier_id', $context)) {
            return $this->normalizeNullableInteger($context['supplier_id']);
        }

        $user = Auth::user();

        if (!$user) {
            return null;
        }

        $user->loadMissing('supplier.boss');

        if ($user->hasRole('Supplier')) {
            return $this->normalizeNullableInteger($user->supplier?->id);
        }

        if ($user->hasRole('User')) {
            return $this->normalizeNullableInteger($user->supplier?->boss_id)
                ?? $this->normalizeNullableInteger($user->supplier?->id);
        }

        return null;
    }

    private function resolveUserId(array $context): ?int
    {
        $userId = $this->normalizeNullableInteger($context['user_id'] ?? null);

        if ($userId !== null) {
            return $userId;
        }

        return $this->normalizeNullableInteger(Auth::id());
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '' || Str::lower($normalizedValue) === 'null') {
            return null;
        }

        return $normalizedValue;
    }

    private function normalizeNullableInteger($value): ?int
    {
        $normalizedValue = $this->normalizeNullableString($value);

        if ($normalizedValue === null || !is_numeric($normalizedValue)) {
            return null;
        }

        return (int) $normalizedValue;
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