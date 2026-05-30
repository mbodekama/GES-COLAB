<?php

namespace App\Mail;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CongeApprouve extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Leave $leave) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.config('app.name').'] Votre '.$this->leave->type_label.' a été approuvé(e)',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.conge_approuve');
    }
}
