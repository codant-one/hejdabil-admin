<?php

namespace App\Services;

use Exception;

class OpenSslService
{
    /**
     * Generates a Private Key and a CSR (Certificate Signing Request).
     * Uses the openssl command directly to ensure compatibility with:
     * openssl req -nodes -newkey rsa:4096 -keyout MySwishKey.key -out MySwishCSR.csr
     *
     * @param string $commonName The Common Name (CN) for the certificate (e.g., payout number).
     * @param array $dnOptions Optional Distinguished Name attributes (countryName, stateOrProvinceName, etc.).
     * @param string|null $challengePassword Optional challenge password for the CSR (e.g., "swish").
     * @param int $keyBits The size of the private key in bits (default: 4096).
     * @return array Returns an array containing 'private_key' and 'csr' strings.
     * @throws Exception If generation fails.
     */
    public function generateCsrAndKey(string $commonName, array $dnOptions = [], ?string $challengePassword = null, int $keyBits = 4096): array
    {
        // Create temporary files for key and csr
        $tempDir = sys_get_temp_dir();
        $keyFile = $tempDir . DIRECTORY_SEPARATOR . 'temp_key_' . uniqid() . '.key';
        $csrFile = $tempDir . DIRECTORY_SEPARATOR . 'temp_csr_' . uniqid() . '.csr';

        try {
            // Get OpenSSL executable path
            $opensslPath = $this->getOpenSslExecutablePath();
            
            // Get OpenSSL config path
            $configPath = $this->getOpenSslConfigPath();

            // Build the subject string
            $subject = $this->buildSubjectString($commonName, $dnOptions);

            // Build the openssl command
            // openssl req -nodes -newkey rsa:4096 -keyout MySwishKey.key -out MySwishCSR.csr -subj "..."
            $command = sprintf(
                '"%s" req -nodes -newkey rsa:%d -keyout "%s" -out "%s" -subj "%s"',
                $opensslPath,
                $keyBits,
                $keyFile,
                $csrFile,
                $subject
            );

            // Add config file if found
            if ($configPath) {
                $command .= sprintf(' -config "%s"', $configPath);
            }

            // Execute the command
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception("OpenSSL command failed: " . implode("\n", $output));
            }

            // Read the generated files
            if (!file_exists($keyFile) || !file_exists($csrFile)) {
                throw new Exception("Failed to generate key or CSR files");
            }

            $privateKeyString = file_get_contents($keyFile);
            $csrString = file_get_contents($csrFile);

            if ($privateKeyString === false || $csrString === false) {
                throw new Exception("Failed to read generated files");
            }

            return [
                'private_key' => $privateKeyString,
                'csr' => $csrString,
            ];

        } finally {
            // Clean up temporary files
            if (file_exists($keyFile)) {
                unlink($keyFile);
            }
            if (file_exists($csrFile)) {
                unlink($csrFile);
            }
        }
    }

    /**
     * Build the subject string for openssl command
     */
    private function buildSubjectString(string $commonName, array $dnOptions): string
    {
        $defaults = [
            'C' => 'SE',
        ];

        // Map full names to short names
        $mapping = [
            'countryName' => 'C',
            'stateOrProvinceName' => 'ST',
            'localityName' => 'L',
            'organizationName' => 'O',
            'organizationalUnitName' => 'OU',
            'commonName' => 'CN',
            'emailAddress' => 'emailAddress',
        ];

        $subject = [];

        // Add defaults
        foreach ($defaults as $key => $value) {
            $subject[$key] = $value;
        }

        // Add provided options
        foreach ($dnOptions as $key => $value) {
            $shortKey = $mapping[$key] ?? $key;
            $subject[$shortKey] = $value;
        }

        // Always set Common Name
        $subject['CN'] = $commonName;

        // Build the subject string
        $subjectString = '';
        foreach ($subject as $key => $value) {
            $subjectString .= '/' . $key . '=' . $value;
        }

        return $subjectString;
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
     * Find the OpenSSL executable path for Windows/Laragon and Linux servers
     */
    private function getOpenSslExecutablePath(): string
    {
        // Check if we're on Windows
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            // Windows/Laragon paths
            $possiblePaths = [
                'C:/laragon/bin/openssl/openssl.exe',
                'C:/laragon/bin/git/usr/bin/openssl.exe',
                'C:/Program Files/Git/usr/bin/openssl.exe',
                'C:/Program Files (x86)/Git/usr/bin/openssl.exe',
            ];

            // Search for Apache bin directories dynamically in Laragon
            $apacheDir = 'C:/laragon/bin/apache';
            if (is_dir($apacheDir)) {
                $dirs = scandir($apacheDir);
                foreach ($dirs as $dir) {
                    if ($dir !== '.' && $dir !== '..') {
                        $opensslPath = $apacheDir . '/' . $dir . '/bin/openssl.exe';
                        if (file_exists($opensslPath)) {
                            return $opensslPath;
                        }
                    }
                }
            }

            // Check predefined Windows paths
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            // Try to find openssl in PATH on Windows
            $output = [];
            exec('where openssl 2>&1', $output, $returnCode);
            if ($returnCode === 0 && !empty($output[0]) && file_exists($output[0])) {
                return $output[0];
            }
        } else {
            // Linux/Unix - openssl is typically in PATH
            // Check common Linux paths first
            $linuxPaths = [
                '/usr/bin/openssl',
                '/usr/local/bin/openssl',
                '/bin/openssl',
            ];

            foreach ($linuxPaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            // Try 'which openssl' command
            $output = [];
            exec('which openssl 2>&1', $output, $returnCode);
            if ($returnCode === 0 && !empty($output[0]) && file_exists($output[0])) {
                return $output[0];
            }
        }

        // Fallback to just 'openssl' hoping it's in PATH
        return 'openssl';
    }

    /**
     * Helper to find openssl.cnf on Windows/Laragon and Linux.
     */
    private function getOpenSslConfigPath(): ?string
    {
        // Check environment variable first (works on both Windows and Linux)
        if (getenv('OPENSSL_CONF') && file_exists(getenv('OPENSSL_CONF'))) {
            return getenv('OPENSSL_CONF');
        }

        // Check if we're on Windows
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            // Windows/Laragon paths
            $possiblePaths = [
                'C:/laragon/etc/ssl/openssl.cnf',
            ];

            // Search for Apache conf directories dynamically
            $apacheDir = 'C:/laragon/bin/apache';
            if (is_dir($apacheDir)) {
                $dirs = scandir($apacheDir);
                foreach ($dirs as $dir) {
                    if ($dir !== '.' && $dir !== '..') {
                        $configPath = $apacheDir . '/' . $dir . '/conf/openssl.cnf';
                        if (file_exists($configPath)) {
                            return $configPath;
                        }
                    }
                }
            }

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            // Check relative to php.ini (Standard for Laragon/XAMPP)
            $phpIni = php_ini_loaded_file();
            if ($phpIni) {
                $extrasPath = dirname($phpIni) . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf';
                if (file_exists($extrasPath)) {
                    return $extrasPath;
                }
            }
        } else {
            // Linux paths
            $linuxPaths = [
                '/etc/ssl/openssl.cnf',
                '/etc/pki/tls/openssl.cnf',
                '/usr/lib/ssl/openssl.cnf',
                '/usr/local/ssl/openssl.cnf',
            ];

            foreach ($linuxPaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        // On Linux, openssl usually works without explicit config
        return null;
    }
}
