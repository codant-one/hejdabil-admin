<?php

namespace App\Services;

use Exception;

class OpenSslService
{
    /**
     * Generates a Private Key and a CSR (Certificate Signing Request).
     *
     * @param string $commonName The Common Name (CN) for the certificate (e.g., domain name or user name).
     * @param array $dnOptions Optional Distinguished Name attributes (countryName, stateOrProvinceName, etc.).
     * @param string|null $challengePassword Optional challenge password for the CSR.
     * @param int $keyBits The size of the private key in bits (default: 4096).
     * @return array Returns an array containing 'private_key' and 'csr' strings.
     * @throws Exception If generation fails.
     */
    public function generateCsrAndKey(string $commonName, array $dnOptions = [], ?string $challengePassword = null, int $keyBits = 4096): array
    {
        // 1. Configuration for the Private Key
        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => $keyBits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Fix for Windows/Laragon: Explicitly set openssl.cnf path if found
        $openSslConfig = $this->getOpenSslConfigPath();
        if ($openSslConfig) {
            $config['config'] = $openSslConfig;
        }

        // 2. Generate the Private Key
        $privateKeyResource = openssl_pkey_new($config);

        if ($privateKeyResource === false) {
            throw new Exception("Failed to generate private key: " . $this->getOpenSslError());
        }

        // 3. Prepare Distinguished Name (DN)
        // Merge defaults with provided options. Common Name is mandatory.
        // You can adjust these defaults as needed for your organization.
        $dn = array_merge([
            "countryName" => "SE"
        ], $dnOptions);

        // Overwrite or set Common Name specifically
        $dn["commonName"] = $commonName;

        // 4. Prepare Extra Attributes (Challenge Password)
        $extraAttributes = [];
        if ($challengePassword) {
            $extraAttributes["challengePassword"] = 'qwerty';
        }

        // 5. Generate the CSR
        // Note: $extraAttributes is passed as the 4th argument
        $csrResource = openssl_csr_new($dn, $privateKeyResource, $config, $extraAttributes);

        if ($csrResource === false) {
            throw new Exception("Failed to generate CSR: " . $this->getOpenSslError());
        }

        // 6. Export Private Key to String
        // We pass null as the passphrase to keep it unencrypted (equivalent to -nodes)
        $privateKeyString = '';
        if (!openssl_pkey_export($privateKeyResource, $privateKeyString, null, $config)) {
             throw new Exception("Failed to export private key: " . $this->getOpenSslError());
        }

        // 7. Export CSR to String
        $csrString = '';
        if (!openssl_csr_export($csrResource, $csrString)) {
            throw new Exception("Failed to export CSR: " . $this->getOpenSslError());
        }

        // 8. Return the results
        return [
            'private_key' => $privateKeyString,
            'csr' => $csrString,
        ];
    }

    /**
     * Helper to get OpenSSL error messages.
     */
    private function getOpenSslError(): string
    {
        $errors = [];
        while ($msg = openssl_error_string()) {
            $errors[] = $msg;
        }
        return implode(", ", $errors);
    }

    /**
     * Helper to find openssl.cnf on Windows/Laragon.
     */
    private function getOpenSslConfigPath(): ?string
    {
        // Check environment variable
        if (getenv('OPENSSL_CONF') && file_exists(getenv('OPENSSL_CONF'))) {
            return getenv('OPENSSL_CONF');
        }

        // Check relative to php.ini (Standard for Laragon/XAMPP)
        $phpIni = php_ini_loaded_file();
        if ($phpIni) {
            $extrasPath = dirname($phpIni) . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf';
            if (file_exists($extrasPath)) {
                return $extrasPath;
            }
        }

        return null;
    }
}
