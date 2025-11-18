<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SwishPayout;

class SwishTestPayout extends Command
{
    protected $signature = 'swish:test-payout {msisdn} {amount=10} {--ssn=}';
    protected $description = 'Run a Swish payout test to the given MSISDN without SSN and print response';

    public function handle(SwishPayout $swish)
    {
        $msisdn = $this->argument('msisdn');
        $amount = (float)$this->argument('amount');

        $this->info("Creating payout to {$msisdn} for {$amount} SEK...");

        try {
            $ssn = $this->option('ssn') ?: null;
            $response = $swish->createPayout($msisdn, $amount, 'REF'.strtoupper(\Illuminate\Support\Str::random(9)), $ssn);

            $status = $response->status();
            $headers = $response->headers();
            $body = $response->json();

            $this->line('Status: ' . $status);
            $this->line('Headers: ' . json_encode($headers));
            $this->line('Body: ' . json_encode($body));

            if ($status >= 200 && $status < 300) {
                $this->info('Swish payout created successfully.');
                return self::SUCCESS;
            }

            $this->error('Swish payout failed.');
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Exception: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
