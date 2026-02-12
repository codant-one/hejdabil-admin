<?php

namespace App\Traits;

use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Log;

trait SendsAsyncEmails
{
    /**
     * Send an email asynchronously using a job
     *
     * @param string $view Email view template
     * @param array $data Data to pass to the view
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string|null $queue Queue name (default: 'emails')
     * @return bool
     */
    protected function sendEmailAsync(
        string $view,
        array $data,
        string $to,
        string $subject,
        ?string $queue = 'emails'
    ): bool {
        try {
            SendEmailJob::dispatch($view, $data, $to, $subject)
                ->onQueue($queue);

            Log::info("Email job queued", [
                'view' => $view,
                'to' => $to,
                'subject' => $subject
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to queue email", [
                'view' => $view,
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send an email asynchronously with delay
     *
     * @param string $view
     * @param array $data
     * @param string $to
     * @param string $subject
     * @param int $delaySeconds Delay in seconds
     * @param string|null $queue
     * @return bool
     */
    protected function sendEmailAsyncDelayed(
        string $view,
        array $data,
        string $to,
        string $subject,
        int $delaySeconds = 60,
        ?string $queue = 'emails'
    ): bool {
        try {
            SendEmailJob::dispatch($view, $data, $to, $subject)
                ->onQueue($queue)
                ->delay(now()->addSeconds($delaySeconds));

            Log::info("Delayed email job queued", [
                'view' => $view,
                'to' => $to,
                'subject' => $subject,
                'delay_seconds' => $delaySeconds
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to queue delayed email", [
                'view' => $view,
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
