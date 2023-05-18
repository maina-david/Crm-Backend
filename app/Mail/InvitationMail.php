<?php

namespace App\Mail;

use App\Models\Invitation;
use App\Models\OTPTable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $invitation,$otp;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Invitation $invitation,OTPTable $otp)
    {
        $this->invitation = $invitation;
        $this->otp = $otp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('You have been invited to use Callcenter system')
            ->markdown('emails.invitation')
            ->with('email', $this->invitation->email)
            ->with('link', $this->otp->OTP_code);
    }
}
