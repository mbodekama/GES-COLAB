<?php

namespace App\Mail;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CongeRefuse extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Leave $leave,
        public readonly string $motif,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.config('app.name').'] Votre '.$this->leave->type_label.' a été refusé(e)',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.conge_refuse');
    }
}
