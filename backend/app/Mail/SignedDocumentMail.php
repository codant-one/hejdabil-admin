<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use App\Models\Agreement;
use App\Models\Document;

class SignedDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The instance of the agreement.
     *
     * @var \App\Models\Agreement|null
     */
    public $agreement;

    /**
     * The instance of the document.
     *
     * @var \App\Models\Document|null
     */
    public $document;

    /**
     * The full path to the signed PDF (for attachment).
     *
     * @var string
     */
    public $pdfPath;

    /**
     * Public download URL.
     *
     * @var string|null
     */
    public $downloadUrl;

    /**
     * Whether the file is attached or not.
     *
     * @var bool
     */
    public $attachFile;

    /**
     * Email title.
     *
     * @var string
     */
    public $title;

    /**
     * Email icon.
     *
     * @var string
     */
    public $icon;

    /**
     * User logo.
     *
     * @var string|null
     */
    public $logo;

     /**
     * Fullname.
     *
     * @var string|null
     */
    public $fullname;

    /**
     * Company information.
     *
     * @var mixed|null
     */
    public $company;

    /**
     * Create a new message instance.
     */
    public function __construct(?Agreement $agreement, string $pdfPath = '', ?Document $document = null, ?string $downloadUrl = null, bool $attachFile = true, $company = null)
    {
        $this->agreement = $agreement;
        $this->document = $document;
        $this->pdfPath = $pdfPath;
        $this->downloadUrl = $downloadUrl;
        $this->attachFile = $attachFile;
        $this->company = $company;

        // Configure variables for the view
        $this->title = 'Dokumentet är nu signerat';
        
        if ($this->agreement) {
            $this->title = 'Avtal signerat';
            $this->icon = asset('/images/agreements-two.png');
            $this->fullname = $this->agreement->agreement_client->fullname ?? null;
        } elseif ($this->document) {
            $this->title = 'Dokumentet är nu signerat';
            $this->icon = asset('/images/check.png');
            $this->fullname = null;
        }
        
        // Obtain the logo of the user who owns the document/agreement
        $user = null;
        if ($this->agreement && $this->agreement->user) {
            $user = $this->agreement->user;
        } elseif ($this->document && $this->document->user) {
            $user = $this->document->user;
        }
        
        $this->logo = $user && $user->userDetail ? $user->userDetail->logo_url : null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope()
    {
        $subject = 'Dokumentet är nu signerat';

        if ($this->agreement) {
            $subject = 'Avtal signerat - bekräftelse';
        } elseif ($this->document) {
            $subject = 'Dokumentet är nu signerat';
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