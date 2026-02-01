<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestSwishConnection extends Command
{
    protected $signature = 'swish:test-connection';
    protected $description = 'Test mTLS connection to Swish API';

    public function handle()
    {
        $this->info("=== SWISH mTLS CONNECTION TEST ===\n");

        $baseUrl = config('services.swish_payout.base_url');
        $clientCert = config('services.swish_payout.client_cert');
        $clientKey = config('services.swish_payout.client_key');
        $clientKeyPassword = config('services.swish_payout.client_key_password');
        $caCert = config('services.swish_payout.ca_cert');

        $this->line("Base URL: {$baseUrl}");
        $this->line("Client Cert: {$clientCert}");
        $this->line("Client Key: {$clientKey}");
        $this->line("CA Cert: {$caCert}\n");

        // Verify files exist
        if (!file_exists($clientCert)) {
            $this->error("❌ Client cert file not found!");
            return 1;
        }
        if (!file_exists($clientKey)) {
            $this->error("❌ Client key file not found!");
            return 1;
        }
        if (!file_exists($caCert)) {
            $this->error("❌ CA cert file not found!");
            return 1;
        }

        $this->info("✅ All certificate files exist\n");

        // Test 1: Verify cert and key match
        $this->line("🔍 Test 1: Verify cert and key match...");
        if (!$this->verifyCertKeyMatch($clientCert, $clientKey, $clientKeyPassword)) {
            $this->error("❌ Certificate and key do NOT match!");
            return 1;
        }
        $this->info("✅ Certificate and key match\n");

        // Test 2: Try to connect to Swish
        $this->line("🔍 Test 2: Testing connection to Swish API...");
        
        $certOption = $clientKeyPassword ? [$clientCert, $clientKeyPassword] : $clientCert;
        $keyOption = $clientKeyPassword ? [$clientKey, $clientKeyPassword] : $clientKey;

        try {
            $response = Http::timeout(30)
                ->withOptions([
                    'cert'    => $certOption,
                    'ssl_key' => $keyOption,
                    'verify'  => $caCert,
                    'debug'   => false
                ])
                ->get($baseUrl . '/v1/payouts/test'); // Endpoint que no existe pero debería conectar

            $this->line("Response status: " . $response->status());
            $this->line("Response body: " . $response->body());
            
            if ($response->status() === 401) {
                $this->error("\n❌ Got 401 - Authentication failed!");
                $this->line("This means the mTLS handshake succeeded but Swish rejected the request.");
            } elseif ($response->status() === 404) {
                $this->info("\n✅ Connection successful! (404 is expected for test endpoint)");
            } else {
                $this->line("\n⚠️  Unexpected status: " . $response->status());
            }

        } catch (\Exception $e) {
            $this->error("\n❌ Connection failed!");
            $this->error("Error: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'SSL')) {
                $this->line("\n💡 SSL Error detected. Possible causes:");
                $this->line("  - Certificate/key mismatch");
                $this->line("  - Invalid CA certificate");
                $this->line("  - Certificate expired or not yet valid");
            }
            
            return 1;
        }

        return 0;
    }

    private function verifyCertKeyMatch($certPath, $keyPath, $password)
    {
        try {
            // Get cert modulus
            $certContent = file_get_contents($certPath);
            $cert = openssl_x509_read($certContent);
            $certPublicKey = openssl_pkey_get_public($cert);
            $certDetails = openssl_pkey_get_details($certPublicKey);
            $certModulus = $certDetails['rsa']['n'] ?? null;

            // Get key modulus
            $keyContent = file_get_contents($keyPath);
            $privateKey = @openssl_pkey_get_private($keyContent);
            if (!$privateKey && $password) {
                $privateKey = @openssl_pkey_get_private($keyContent, $password);
            }
            
            if (!$privateKey) {
                $this->error("Cannot load private key");
                return false;
            }

            $keyDetails = openssl_pkey_get_details($privateKey);
            $keyModulus = $keyDetails['rsa']['n'] ?? null;

            return $certModulus === $keyModulus;

        } catch (\Exception $e) {
            $this->error("Error verifying: " . $e->getMessage());
            return false;
        }
    }
}
