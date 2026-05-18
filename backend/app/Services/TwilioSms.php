<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioSms
{
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
            Log::error('Twilio SMS send failed', [
                'to' => $to,
                'message' => $exception->getMessage(),
            ]);

            return $exception->getMessage();
        }
    }
}