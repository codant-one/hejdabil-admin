<?php

namespace App\Mail;

use App\Models\Agreement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignedDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * La instancia del contrato.
     *
     * @var \App\Models\Agreement
     */
    public $agreement;

    /**
     * La ruta completa al PDF firmado.
     *
     * @var string
     */
    public $pdfPath;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Agreement $agreement, string $pdfPath)
    {
        $this->agreement = $agreement;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Ditt signerade avtal: #' . $this->agreement->agreement_id,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.agreements.signed-document', 
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as(basename($this->pdfPath)) // El nombre que verá el cliente
                ->withMime('application/pdf'),
        ];
    }
}