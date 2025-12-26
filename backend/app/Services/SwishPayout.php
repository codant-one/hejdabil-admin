<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SwishPayout
{
    protected string $baseUrl;
    protected string $callbackUrl;
    protected string $signingCert;
    protected string $signingKey;
    protected string $signingKeyPassword;
    protected string $clientKeyPassword;
    protected string $clientCertPassword;

    public function __construct()
    {
        $user = Auth::user();
        $supplier = $user->supplier;

        $this->baseUrl            = config('services.swish_payout.base_url');
        $this->callbackUrl         = config('services.swish_payout.callback_url');
        $this->signingCert         = str_replace('\\', '/', storage_path('app/public/' . $supplier->pem_url));
        $this->signingKey          = str_replace('\\', '/', storage_path('app/public/' . $supplier->key_url));
        $this->signingKeyPassword  = config('services.swish_payout.signing_key_password', 'swish');

        // Log para verificar las rutas
        Log::info('SwishPayout: Rutas de certificados' . PHP_EOL . json_encode([
            'signingCert' => $this->signingCert,
            'signingKey' => $this->signingKey,
            'signingCert_exists' => file_exists($this->signingCert),
            'signingKey_exists' => file_exists($this->signingKey),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->clientKeyPassword   = config('services.swish_payout.client_key_password');
        $this->clientCertPassword  = config('services.swish_payout.client_cert_password');
    }

    protected function client()
    {
        $caPath = config('services.swish_payout.ca_cert');
        $sslKey = config('services.swish_payout.client_key');
        $sslKeyOption = $this->clientKeyPassword ? [$sslKey, $this->clientKeyPassword] : $sslKey;
        $cert = config('services.swish_payout.client_cert');
        $certOption = $this->clientCertPassword ? [$cert, $this->clientCertPassword] : $cert;

        return Http::withOptions([
            'cert'    => $certOption,
            'ssl_key' => $sslKeyOption,
            'verify'  => $caPath
        ])->baseUrl($this->baseUrl);
    }

    /**
     * Creates a payout in Swish according to the official documentation.
     * Structure: { payload: {...}, signature: “...”, callbackUrl: “...” }
     */
    public function createPayout($request): \Illuminate\Http\Client\Response
    {
        $payload = [
            'payoutInstructionUUID' => $request->payout_instruction_uuid,
            'payerPaymentReference' => $request->reference,
            'payerAlias'            => $request->payer_alias,
            'payeeAlias'            => $request->payee_alias,
            'payeeSSN'              => $request->payee_ssn,
            'amount'                => $request->amount,
            'currency'              => $request->currency,
            'payoutType'            => $request->payout_type,
            'message'               => $request->message,
            'instructionDate'       => $request->instruction_date,
            'signingCertificateSerialNumber' => $request->signing_certificate_serial_number
        ];

        // Structure according to official documentation: payload + signature + callbackUrl
        $body = [
            'payload'       => $payload,
            'callbackUrl'   => $this->callbackUrl,
        ];

        // Payload signature
        $signature = $this->generateSignature($payload);
        if ($signature) {
            $body['signature'] = $signature;
        }

        $response = $this->client()->post('/v1/payouts', $body);

        Log::info('Swish Payout: Create Payout Request' . PHP_EOL . json_encode([
            'request_body' => $body,
            'response_status' => $response->status(),
            'response_body' => $response->body(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        
        
        return $response;
    }

    /**
     * Generates the Base64 signature of the payload according to Swish documentation.
     * IMPORTANT: JSON encode WITHOUT sorting alphabetically, then utf8_encode, then SHA-512.
     */
    protected function generateSignature(array $payload): ?string
    {
        if (!$this->signingCert || !$this->signingKey) {
            Log::warning('Swish Payout: Signing certificate or key not configured');
            return null;
        }

        try {
            // JSON encode WITHOUT sorting alphabetically
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            if ($jsonPayload === false) {
                Log::error('Swish Payout: Failed to encode payload to JSON');
                return null;
            }
            
            // STEP 1: utf8_encode the JSON
            $utf8Encoded = utf8_encode($jsonPayload);
            
            // STEP 2: SHA-512 Hash
            $hashedPayload = hash('sha512', $utf8Encoded, true); // true = binary output
            
            $keyContent = file_get_contents($this->signingKey);
            if ($keyContent === false) {
                Log::error('Swish Payout: Failed to read signing key file');
                return null;
            }
            
            $privateKey = openssl_pkey_get_private($keyContent, $this->signingKeyPassword);
            if (!$privateKey) {
                Log::error('Swish Payout: Failed to load signing private key');
                return null;
            }
            
            // STEP 3: Sign the hash
            $signature = '';
            $success = openssl_sign($hashedPayload, $signature, $privateKey, OPENSSL_ALGO_SHA512);
            openssl_free_key($privateKey);
            
            if (!$success) {
                Log::error('Swish Payout: Failed to sign payload');
                return null;
            }
            
            return base64_encode($signature);
            
        } catch (\Exception $e) {
            Log::error('Swish Payout: Exception generating signature', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Gets the serial number of the signing certificate in hexadecimal format.
     */
    public function getSigningCertificateSerialNumber(): ?string
    {
        try {
            if (!$this->signingCert || !is_file($this->signingCert)) {
                return null;
            }
            $certContent = file_get_contents($this->signingCert);
            if ($certContent === false) {
                return null;
            }
            $cert = openssl_x509_read($certContent);
            if ($cert === false) {
                return null;
            }
            $parsed = openssl_x509_parse($cert);
            if ($parsed === false) {
                return null;
            }
            $serialHex = $parsed['serialNumberHex'] ?? null;
            if (!$serialHex) {
                return null;
            }
            return strtoupper($serialHex);
        } catch (\Throwable $e) {
            Log::warning('Swish Payout: unable to read signing cert serial');
            return null;
        }
    }
}
