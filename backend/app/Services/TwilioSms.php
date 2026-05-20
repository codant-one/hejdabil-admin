<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioSms
{
    public const INVALID_RECIPIENT_MESSAGE = 'SMS-kunde inte skickas. Kontrollera att telefonnumret är korrekt och försök igen.';

    protected $sid;
    protected $authToken;
    protected $phoneNumber;
    protected $client;

    public function __construct()
    {
        $this->sid = env('TWILIO_SID');
        $this->authToken = env('TWILIO_AUTH_TOKEN');
        $this->phoneNumber = env('TWILIO_PHONE_NUMBER');
        $this->client = $this->sid && $this->authToken
            ? new Client($this->sid, $this->authToken)
            : null;
    }

    public function sendMessage($to, $message)
    {
        try {
            if (!$this->client || !$this->phoneNumber) {
                return 'Twilio är inte korrekt konfigurerat.';
            }

            $this->client->messages->create($to, [
                'from' => $this->phoneNumber,
                'body' => $message,
            ]);

            return true;
        } catch (\Throwable $exception) {
            $resolvedMessage = $this->resolveFailureMessage($exception);

            Log::error('Twilio SMS send failed', [
                'to' => $to,
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'resolved_message' => $resolvedMessage,
            ]);

            return $resolvedMessage;
        }
    }

    public static function isInvalidRecipientMessage(?string $message): bool
    {
        return $message === self::INVALID_RECIPIENT_MESSAGE;
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