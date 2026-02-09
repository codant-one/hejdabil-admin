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
        protected ?string $fromName = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $from = $this->from ?? env('MAIL_FROM_ADDRESS');
            $fromName = $this->fromName ?? env('MAIL_FROM_NAME');

            Mail::send(
                $this->view,
                $this->data,
                function ($message) use ($from, $fromName) {
                    $message->from($from, $fromName);
                    $message->to($this->to)->subject($this->subject);
                }
            );

            Log::info("Email enviado exitosamente", [
                'to' => $this->to,
                'subject' => $this->subject,
                'view' => $this->view
            ]);
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
