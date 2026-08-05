<?php

namespace App\Mail;

use App\Models\Nda;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NdaSignedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Nda $nda,
        public bool $forOperator = false,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->forOperator
            ? 'NDA unterzeichnet: ' . $this->nda->volunteer_name . ' (' . $this->nda->role_name . ')'
            : ($this->nda->locale === 'en'
                ? 'Your signed agreement - Wolffiles.eu'
                : 'Deine unterzeichnete Vereinbarung - Wolffiles.eu');

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nda-signed',
            with: [
                'nda' => $this->nda,
                'forOperator' => $this->forOperator,
            ],
        );
    }
}
