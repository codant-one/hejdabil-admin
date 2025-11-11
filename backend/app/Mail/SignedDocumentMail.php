<?php

namespace App\Mail;

use App\Models\Agreement;
use App\Models\Document;
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
     * @var \App\Models\Agreement|null
     */
    public $agreement;

    /**
     * La instancia del documento.
     *
     * @var \App\Models\Document|null
     */
    public $document;

    /**
     * La ruta completa al PDF firmado (para adjuntar).
     *
     * @var string
     */
    public $pdfPath;

    /**
     * URL pública de descarga.
     *
     * @var string|null
     */
    public $downloadUrl;

    /**
     * Si se adjunta el archivo o no.
     *
     * @var bool
     */
    public $attachFile;

    /**
     * Create a new message instance.
     */
    public function __construct(?Agreement $agreement, string $pdfPath = '', ?Document $document = null, ?string $downloadUrl = null, bool $attachFile = true)
    {
        $this->agreement = $agreement;
        $this->document = $document;
        $this->pdfPath = $pdfPath;
        $this->downloadUrl = $downloadUrl;
        $this->attachFile = $attachFile;
    }

    /**
     * Get the message envelope.
     */
    public function envelope()
    {
        $subject = 'Ditt signerade dokument';
        if ($this->agreement) {
            $subject = 'Ditt signerade avtal: #' . $this->agreement->agreement_id;
        } elseif ($this->document) {
            $subject = 'Ditt signerade dokument: ' . $this->document->title;
        }
        
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content()
    {
        return new Content(
            view: 'emails.agreements.signed-document', 
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments()
    {
        if (!$this->attachFile || !$this->pdfPath) {
            return [];
        }

        return [
            Attachment::fromPath($this->pdfPath)
                ->as(basename($this->pdfPath))
                ->withMime('application/pdf'),
        ];
    }
}