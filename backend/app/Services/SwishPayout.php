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
     * Crea un payout en Swish y devuelve la Response completa para poder leer headers, body, etc.
     * Formato basado en la documentación de Swish Payout (payload anidado + callback).
     */
    public function createPayout(string $payeeAlias, float $amount, string $payoutRef, ?string $payeeSSN = null): \Illuminate\Http\Client\Response
    {
        
        // Segun la doc, el cuerpo debe tener un objeto "payload" y datos de callback al mismo nivel.
        // Swish Payouts requiere el payoutInstructionUUID en MAYÚSCULAS y SIN guiones.
        // Ejemplo: 16E8AFF8977F42D0BD16BF39D3B9B3C4
        $payoutInstructionUUID = strtoupper(str_replace('-', '', Str::uuid()->toString()));

        $payload = [
            'payoutInstructionUUID'          => $payoutInstructionUUID,
            'payerPaymentReference'          => $payoutRef,
            // En Payout, el "payer" es tu empresa (alias de Swish Payout)
            'payerAlias'                     => $this->payerAlias,
            // El "payee" es el número Swish del destinatario (tester, cliente, etc.)
            'payeeAlias'                     => $payeeAlias,
            'amount'                         => number_format($amount, 2, '.', ''), // string con 2 decimales
            'currency'                       => 'SEK',
            'payoutType'                     => 'PAYOUT',
            'message'                        => 'Payout from ' . config('app.name'),
            // Fecha/hora en UTC no futura (restamos 10s para evitar desfases de reloj)
            'instructionDate'                => Carbon::now('UTC')->subSeconds(10)->format('Y-m-d\TH:i:s\Z'),
            // Campos opcionales como signingCertificateSerialNumber se pueden añadir abajo si está disponible
        ];

        // Si podemos leer el serial del certificado de firma, lo incluimos (muchas integraciones lo requieren)
        if ($serial = $this->getSigningCertificateSerialNumber()) {
            $payload['signingCertificateSerialNumber'] = $serial;
        }

        // Incluir SSN del beneficiario si es provisto (12 dígitos: YYYYMMDDXXXX)
        if (!empty($payeeSSN)) {
            $payload['payeeSSN'] = $payeeSSN;
        }

        // Según la doc de Swish Payouts v1, a nivel raíz deben ir:
        // - payload                     (con todos los campos del payout)
        // - callbackUrl                 (URL pública que Swish llamará)
        // - callbackIdentifier (opcional) identificador 32-36 [0-9A-Za-z-], se devuelve en el callback
        // - signature                   (firma Base64 del payload, requerida)
        // Estructura con objeto payload + datos de callback
        $body = [
            'payload'       => $payload,
            'callbackUrl'   => $this->callbackUrl,
        ];
        if (config('services.swish_payout.use_callback_identifier', false)) {
            // Generamos UUID sin guiones en mayúsculas (32 chars) para cumplir ^[0-9A-Za-z-]{32,36}$
            $body['callbackIdentifier'] = strtoupper(str_replace('-', '', Str::uuid()->toString()));
        }

        // Generar la firma solo del payload, como indica la doc
        $signature = $this->generateSignature($payload);
        if ($signature) {
            $body['signature'] = $signature;
        }

        Log::info('Swish Payout payload: ' . print_r($payload, true));
        Log::info('Swish Payout body: ' . print_r($body, true));

        $response = $this->client()->post('/v1/payouts', $body); // ruta usada por sandbox

        return $response;
    }

    /**
     * Genera la firma Base64 del payload según la documentación de Swish Payout.
     * 
     * Proceso:
     * 1. Ordenar el payload alfabéticamente por claves
    * 2. Serializar el payload a JSON (sin espacios, sin escape de slashes)
    * 3. Firmar el JSON con la clave privada usando RSA-SHA512 (sin pre-hash manual)
    * 4. Codificar en Base64
     * 
     * @param array $payload
     * @return string|null Base64 encoded signature (684 caracteres)
     */
    protected function generateSignature(array $payload): ?string
    {
        if (!$this->signingCert || !$this->signingKey) {
            Log::warning('Swish Payout: Signing certificate or key not configured');
            return null;
        }

        try {
            // 1. Ordenar el payload alfabéticamente por claves (requerido por Swish)
            ksort($payload);
            
            // 2. Serializar el payload a JSON sin espacios y sin escape de slashes
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            if ($jsonPayload === false) {
                Log::error('Swish Payout: Failed to encode payload to JSON', [
                    'error' => json_last_error_msg()
                ]);
                return null;
            }
            
            // 3. Cargar la clave privada del certificado de firma
            $keyContent = file_get_contents($this->signingKey);
            if ($keyContent === false) {
                Log::error('Swish Payout: Failed to read signing key file');
                return null;
            }
            
            $privateKey = openssl_pkey_get_private($keyContent, $this->signingKeyPassword);
            
            if (!$privateKey) {
                $errors = [];
                while (($error = openssl_error_string()) !== false) {
                    $errors[] = $error;
                }
                Log::error('Swish Payout: Failed to load signing private key', [
                    'errors' => $errors
                ]);
                return null;
            }
            
            // 4. Firmar el JSON directamente con RSA-SHA512 (openssl realiza el hash internamente)
            $signature = '';
            $success = openssl_sign($jsonPayload, $signature, $privateKey, OPENSSL_ALGO_SHA512);
            
            openssl_free_key($privateKey);
            
            if (!$success) {
                $errors = [];
                while (($error = openssl_error_string()) !== false) {
                    $errors[] = $error;
                }
                Log::error('Swish Payout: Failed to sign payload', [
                    'errors' => $errors
                ]);
                return null;
            }
            
            // 5. Codificar en Base64
            $base64Signature = base64_encode($signature);
            
            Log::info('Swish Payout signature generated', [
                'signature_length' => strlen($base64Signature),
                'json_length' => strlen($jsonPayload),
                'expected_length' => 684
            ]);
            
            return $base64Signature;
            
        } catch (\Exception $e) {
            Log::error('Swish Payout: Exception generating signature', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Lee el serial (hex) del certificado de firma si está disponible.
     * @return string|null
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
            // serialNumberHex sin dos puntos y en mayúsculas (según ejemplo de documentación)
            $serialHex = $parsed['serialNumberHex'] ?? null;
            if (!$serialHex) {
                return null;
            }
            return strtoupper($serialHex);
        } catch (\Throwable $e) {
            Log::warning('Swish Payout: unable to read signing cert serial', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }
}
