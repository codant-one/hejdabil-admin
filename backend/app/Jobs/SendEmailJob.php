<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [60, 120, 300]; // Reintentar después de 1min, 2min, 5min

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $view,
        protected array $data,
        protected string $to,
        protected string $subject,
        protected ?string $from = null,
        protected ?string $fromName = null,
        protected ?array $attachments = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $from = $this->from ?? env('MAIL_FROM_ADDRESS');
            $fromName = $this->fromName ?? env('MAIL_FROM_NAME');
            $attachments = $this->attachments;

            Mail::send(
                $this->view,
                $this->data,
                function ($message) use ($from, $fromName, $attachments) {
                    $message->from($from, $fromName);
                    $message->to($this->to)->subject($this->subject);
                    
                    // Adjuntar archivos si existen
                    if ($attachments && is_array($attachments)) {
                        foreach ($attachments as $attachment) {
                            if (isset($attachment['path']) && file_exists($attachment['path'])) {
                                $options = [];
                                if (isset($attachment['as'])) {
                                    $options['as'] = $attachment['as'];
                                }
                                if (isset($attachment['mime'])) {
                                    $options['mime'] = $attachment['mime'];
                                }
                                $message->attach($attachment['path'], $options);
                            }
                        }
                    }
                }
            );

        } catch (\Exception $e) {
            Log::error("Error al enviar email", [
                'to' => $this->to,
                'subject' => $this->subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-lanzar la excepción para que Laravel maneje los reintentos
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Email job falló después de {$this->tries} intentos", [
            'to' => $this->to,
            'subject' => $this->subject,
            'error' => $exception->getMessage()
        ]);
    }
}
