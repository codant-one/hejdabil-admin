<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SwishPayout
{
    protected string $baseUrl;
    protected string $callbackUrl;
    protected string $payerAlias;
    protected ?string $signingCert;
    protected ?string $signingKey;
    protected string $signingKeyPassword;
    protected ?string $clientKeyPassword;
    protected ?string $clientCertPassword;

    public function __construct()
    {
        $this->baseUrl            = config('services.swish_payout.base_url');
        $this->callbackUrl         = config('services.swish_payout.callback_url');
        $this->payerAlias          = config('services.swish_payout.payer_alias');
        $this->signingCert         = config('services.swish_payout.signing_cert');
        $this->signingKey          = config('services.swish_payout.signing_key');
        $this->signingKeyPassword  = config('services.swish_payout.signing_key_password', 'swish');
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
            // Para PEM + KEY separados: el cert no suele requerir password; la KEY sí puede requerirla
            'cert'    => $certOption,
            'ssl_key' => $sslKeyOption,
            'verify'  => $caPath ?: true, // si no hay CA en .env, usa el bundle por defecto del sistema
        ])->baseUrl($this->baseUrl);
    }

    /**
     * Crea un payout en Swish según la documentación oficial.
     * Estructura: { payload: {...}, signature: "...", callbackUrl: "..." }
     */
    public function createPayout(string $payeeAlias, float $amount, string $payoutRef, ?string $payeeSSN = null): \Illuminate\Http\Client\Response
    {
        $payoutInstructionUUID = strtoupper(str_replace('-', '', Str::uuid()->toString()));

        // El payload contiene todos los datos del payout
        $payload = [
            'payoutInstructionUUID'          => $payoutInstructionUUID,
            'payerPaymentReference'          => $payoutRef,
            'payerAlias'                     => $this->payerAlias,
            'payeeAlias'                     => $payeeAlias,
            'amount'                         => number_format($amount, 2, '.', ''),
            'currency'                       => 'SEK',
            'payoutType'                     => 'PAYOUT',
            'message'                        => 'Payout from ' . config('app.name'),
            'instructionDate'                => Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z'),
        ];

        // Serial del certificado de firma
        if ($serial = $this->getSigningCertificateSerialNumber()) {
            $payload['signingCertificateSerialNumber'] = $serial;
        }

        // SSN obligatorio para pagos a personas naturales
        if (!empty($payeeSSN)) {
            $payload['payeeSSN'] = $payeeSSN;
        }

        // Estructura según documentación oficial: payload + signature + callbackUrl
        $body = [
            'payload'       => $payload,
            'callbackUrl'   => $this->callbackUrl,
        ];

        // Firma del payload
        $signature = $this->generateSignature($payload);
        if ($signature) {
            $body['signature'] = $signature;
        }

        Log::info('Swish Payout request: ' . print_r($body, true));

        // CallbackIdentifier opcional - va como header HTTP, NO en el body
        $headers = [];
        if (config('services.swish_payout.use_callback_identifier', false)) {
            $headers['Swish-Callback-Identifier'] = strtoupper(str_replace('-', '', Str::uuid()->toString()));
        }

        $response = $this->client()->withHeaders($headers)->post('/v1/payouts', $body);

        return $response;
    }

    /**
     * Genera la firma Base64 del payload según la documentación de Swish.
     * IMPORTANTE: JSON encode SIN ordenar alfabéticamente, luego utf8_encode, luego SHA-512.
     */
    protected function generateSignature(array $payload): ?string
    {
        if (!$this->signingCert || !$this->signingKey) {
            Log::warning('Swish Payout: Signing certificate or key not configured');
            return null;
        }

        try {
            // NO ordenar el payload - usar el orden original
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            if ($jsonPayload === false) {
                Log::error('Swish Payout: Failed to encode payload to JSON');
                return null;
            }
            
            // PASO 1: utf8_encode del JSON
            $utf8Encoded = utf8_encode($jsonPayload);
            
            // PASO 2: Hash SHA-512
            $hashedPayload = hash('sha512', $utf8Encoded, true); // true = binary output
            
            Log::info('Swish Payout: Payload for signing', [
                'payload' => $payload,
                'json' => $jsonPayload,
                'json_length' => strlen($jsonPayload),
                'utf8_length' => strlen($utf8Encoded),
                'sha512_hash_hex' => bin2hex($hashedPayload)
            ]);
            
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
            
            // PASO 3: Firmar el hash
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
     * Obtiene el serial del certificado de firma en formato hexadecimal.
     */
    protected function getSigningCertificateSerialNumber(): ?string
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
