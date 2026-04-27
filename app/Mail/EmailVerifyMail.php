<?php

namespace App\Mail;

use App\Models\AppConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class EmailVerifyMail extends Mailable
{
    use Queueable, SerializesModels;
    public $name, $email, $otp, $expired, $company;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $email, $otp, $expired)
    {
        $this->name     = $name;
        $this->email    = $email;
        $this->otp      = $otp;
        $this->expired  = $expired;
        $this->company  = AppConfig::get_config('company_details');
        $this->locale   = App::getLocale();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $companyName = $this->company->company_name ?? config('app.name');
        return new Envelope(
            from: new Address(config('mail.from.address'), $companyName),
            subject: $companyName . ' — ' . __('email.otp.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.sendOTP',
            with: [
                'company' => $this->company,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
