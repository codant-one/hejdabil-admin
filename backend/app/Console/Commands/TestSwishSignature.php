<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TestSwishSignature extends Command
{
    protected $signature = 'swish:test-signature {payout_number=123545-3436}';
    protected $description = 'Test Swish signature generation';

    public function handle()
    {
        $payoutNumber = $this->argument('payout_number');
        $supplier = Supplier::where('payout_number', $payoutNumber)->first();
        
        if (!$supplier) {
            $this->error("Supplier not found!");
            return 1;
        }

        $this->info("=== SWISH SIGNATURE TEST ===\n");

        // Simular payload real
        $payload = [
            'payoutInstructionUUID' => '2BFDA3B5B016499C981CB689C9A5A30B',
            'payerPaymentReference' => 'REFU3OVKPGBG',
            'payerAlias' => str_replace('-', '', $supplier->payout_number),
            'payeeAlias' => '46736161633',
            'payeeSSN' => '199408303957',
            'amount' => '1.00',
            'currency' => 'SEK',
            'payoutType' => 'PAYOUT',
            'message' => 'test',
            'instructionDate' => Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z'),
            'signingCertificateSerialNumber' => null // lo obtendremos
        ];

        // 1. Get serial
        $pemPath = storage_path('app/public/' . $supplier->pem_url);
        $keyPath = storage_path('app/public/' . $supplier->key_url);
        
        $this->line("📄 Certificate path: {$pemPath}");
        $this->line("🔑 Key path: {$keyPath}\n");

        $serial = $this->getCertSerial($pemPath);
        if (!$serial) {
            $this->error("Cannot read certificate serial!");
            return 1;
        }
        
        $payload['signingCertificateSerialNumber'] = $serial;
        $this->info("📋 Serial: {$serial}\n");

        // 2. Generate signature
        $this->line("🔐 Generating signature...\n");
        
        $signature = $this->generateSignature($payload, $keyPath);
        
        if (!$signature) {
            $this->error("Failed to generate signature!");
            return 1;
        }

        $this->info("✅ Signature generated successfully!");
        $this->line("Length: " . strlen($signature) . " bytes");
        $this->line("\nSignature (first 100 chars):");
        $this->line(substr($signature, 0, 100) . "...\n");

        // 3. Show payload that will be sent
        $this->line("📤 PAYLOAD TO BE SENT:");
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        $this->line("\n🎯 Next step: Try calling Swish API with this signature");
        
        return 0;
    }

    private function getCertSerial($pemPath)
    {
        try {
            $certContent = file_get_contents($pemPath);
            $cert = openssl_x509_read($certContent);
            $parsed = openssl_x509_parse($cert);
            return strtoupper($parsed['serialNumberHex'] ?? null);
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return null;
        }
    }

    private function generateSignature(array $payload, $keyPath)
    {
        try {
            // Mismo algoritmo que SwishPayout
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            $this->line("JSON payload:");
            $this->line($jsonPayload . "\n");
            
            $utf8Encoded = utf8_encode($jsonPayload);
            $hashedPayload = hash('sha512', $utf8Encoded, true);
            
            $this->line("SHA-512 hash length: " . strlen($hashedPayload) . " bytes\n");
            
            $keyContent = file_get_contents($keyPath);
            $password = config('services.swish_payout.signing_key_password');
            
            // Try without password first
            $privateKey = @openssl_pkey_get_private($keyContent);
            
            if (!$privateKey && $password) {
                $this->line("⚠️  Trying with password: '{$password}'");
                $privateKey = @openssl_pkey_get_private($keyContent, $password);
            }
            
            if (!$privateKey) {
                $this->error("Cannot load private key!");
                $this->line("OpenSSL error: " . openssl_error_string());
                return null;
            }
            
            $this->info("✅ Private key loaded successfully\n");
            
            $signature = '';
            $success = openssl_sign($hashedPayload, $signature, $privateKey, OPENSSL_ALGO_SHA512);
            openssl_free_key($privateKey);
            
            if (!$success) {
                $this->error("Signing failed!");
                return null;
            }
            
            return base64_encode($signature);
            
        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());
            return null;
        }
    }
}
