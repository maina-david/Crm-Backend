<?php

namespace App\Mail;

use App\Models\OTPTable;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserEmailChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $user, $otp;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, OTPTable $otp)
    {
        $this->user = $user;
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
        ->subject('Confirm new email')
        ->markdown('emails.user_email_changed')
        ->with('name', $this->user->name)
        ->with('link', $this->otp->OTP_code);
    }
}
