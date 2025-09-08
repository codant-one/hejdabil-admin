<?php
namespace App\Mail;
use App\Models\Token;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignatureRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Token $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Solicitud para Firmar su Contrato');
    }

    public function content(): Content
    {
        $signingUrl = env('FRONTEND_URL') . '/sign/' . $this->token->signing_token;
        return new Content(
            view: 'emails.agreements.signature_request',
            with: ['signingUrl' => $signingUrl]
        );
    }
}