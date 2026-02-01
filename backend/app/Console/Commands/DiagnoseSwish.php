<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Supplier;
use Illuminate\Support\Facades\Storage;

class DiagnoseSwish extends Command
{
    protected $signature = 'swish:diagnose {payer_alias=1235453436}';
    protected $description = 'Diagnose Swish configuration differences';

    public function handle()
    {
        $payerAlias = $this->argument('payer_alias');
        
        $this->info("=== SWISH DIAGNOSTIC FOR {$payerAlias} ===\n");

        // 1. ENV Variables
        $this->info("📋 ENV CONFIGURATION:");
        $this->line("SWISH_PAYOUT_BASE_URL: " . config('services.swish_payout.base_url'));
        $this->line("SWISH_PAYOUT_CLIENT_CERT: " . config('services.swish_payout.client_cert'));
        $this->line("SWISH_PAYOUT_CLIENT_KEY: " . config('services.swish_payout.client_key'));
        $this->line("SWISH_PAYOUT_CLIENT_KEY_PASSWORD: " . (config('services.swish_payout.client_key_password') ? 'SET' : 'EMPTY'));
        $this->line("SWISH_PAYOUT_SIGNING_KEY_PASSWORD: " . (config('services.swish_payout.signing_key_password') ? config('services.swish_payout.signing_key_password') : 'EMPTY'));
        $this->line("SWISH_PAYOUT_CA_CERT: " . config('services.swish_payout.ca_cert'));
        
        // Check if provider certs exist
        $this->line("\n🔐 PROVIDER CERTIFICATES (mTLS):");
        $clientCert = config('services.swish_payout.client_cert');
        $clientKey = config('services.swish_payout.client_key');
        $caCert = config('services.swish_payout.ca_cert');
        
        $this->checkFile($clientCert, 'Client Cert');
        $this->checkFile($clientKey, 'Client Key');
        $this->checkFile($caCert, 'CA Cert');

        // 2. Database - Supplier
        $this->line("\n🏢 SUPPLIER DATABASE:");
        $supplier = Supplier::where('payer_alias', $payerAlias)->first();
        
        if (!$supplier) {
            $this->error("❌ Supplier with payer_alias '{$payerAlias}' NOT FOUND in database!");
            return 1;
        }
        
        $this->info("✅ Supplier found: {$supplier->name} (ID: {$supplier->id})");
        $this->line("pem_url: " . ($supplier->pem_url ?? 'NULL'));
        $this->line("key_url: " . ($supplier->key_url ?? 'NULL'));
        $this->line("master_password: " . ($supplier->master_password ? 'SET' : 'NOT SET'));
        
        // 3. Check client signing certificates
        $this->line("\n📝 CLIENT SIGNING CERTIFICATES:");
        
        if (!$supplier->pem_url) {
            $this->error("❌ pem_url is NULL in database!");
        } else {
            $pemPath = storage_path('app/public/' . $supplier->pem_url);
            $this->checkFile($pemPath, 'Signing Cert (PEM)', true);
            
            if (file_exists($pemPath)) {
                $this->checkCertificateDetails($pemPath);
            }
        }
        
        if (!$supplier->key_url) {
            $this->error("❌ key_url is NULL in database!");
        } else {
            $keyPath = storage_path('app/public/' . $supplier->key_url);
            $this->checkFile($keyPath, 'Signing Key', true);
            
            if (file_exists($keyPath)) {
                $this->checkKeyPassword($keyPath);
            }
        }

        $this->line("\n✅ Diagnostic complete!");
        return 0;
    }

    private function checkFile($path, $label, $checkPerms = false)
    {
        if (!$path) {
            $this->error("❌ {$label}: Path is NULL");
            return;
        }

        if (!file_exists($path)) {
            $this->error("❌ {$label}: NOT FOUND");
            $this->line("   Expected: {$path}");
            return;
        }

        $this->info("✅ {$label}: EXISTS");
        $this->line("   Path: {$path}");
        
        if ($checkPerms) {
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            $readable = is_readable($path);
            $this->line("   Permissions: {$perms} | Readable: " . ($readable ? 'YES' : 'NO'));
            
            if (!$readable) {
                $this->warn("   ⚠️  File exists but is NOT readable by PHP!");
            }
        }
    }

    private function checkCertificateDetails($pemPath)
    {
        try {
            $certContent = file_get_contents($pemPath);
            $cert = openssl_x509_read($certContent);
            
            if ($cert === false) {
                $this->error("   ❌ Cannot parse certificate (invalid format?)");
                return;
            }
            
            $parsed = openssl_x509_parse($cert);
            $serial = $parsed['serialNumberHex'] ?? null;
            $subject = $parsed['subject']['CN'] ?? 'N/A';
            $validFrom = date('Y-m-d H:i:s', $parsed['validFrom_time_t']);
            $validTo = date('Y-m-d H:i:s', $parsed['validTo_time_t']);
            $now = time();
            
            $this->line("   Serial: " . strtoupper($serial));
            $this->line("   CN: {$subject}");
            $this->line("   Valid from: {$validFrom}");
            $this->line("   Valid to: {$validTo}");
            
            if ($now < $parsed['validFrom_time_t']) {
                $this->error("   ❌ Certificate is NOT YET VALID!");
            } elseif ($now > $parsed['validTo_time_t']) {
                $this->error("   ❌ Certificate is EXPIRED!");
            } else {
                $this->info("   ✅ Certificate is valid");
            }
            
        } catch (\Exception $e) {
            $this->error("   ❌ Error reading certificate: " . $e->getMessage());
        }
    }

    private function checkKeyPassword($keyPath)
    {
        try {
            $keyContent = file_get_contents($keyPath);
            $password = config('services.swish_payout.signing_key_password');
            
            // Try without password
            $keyNoPass = @openssl_pkey_get_private($keyContent);
            if ($keyNoPass !== false) {
                $this->info("   ✅ Key can be loaded WITHOUT password");
                openssl_free_key($keyNoPass);
                
                if ($password && $password !== 'swish') {
                    $this->warn("   ⚠️  But SWISH_PAYOUT_SIGNING_KEY_PASSWORD is set to: '{$password}'");
                    $this->warn("   ⚠️  This key doesn't need a password - remove or leave empty!");
                }
                return;
            }
            
            // Try with password
            if ($password) {
                $keyWithPass = @openssl_pkey_get_private($keyContent, $password);
                if ($keyWithPass !== false) {
                    $this->info("   ✅ Key can be loaded WITH password: '{$password}'");
                    openssl_free_key($keyWithPass);
                    return;
                }
            }
            
            $this->error("   ❌ Cannot load private key (wrong password or corrupted?)");
            $this->line("   Current password config: " . ($password ?: 'EMPTY'));
            
        } catch (\Exception $e) {
            $this->error("   ❌ Error reading key: " . $e->getMessage());
        }
    }
}
