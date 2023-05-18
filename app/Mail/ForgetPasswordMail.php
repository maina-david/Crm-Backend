<?php

namespace App\Mail;

use App\Models\OTPTable;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgetPasswordMail extends Mailable
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
            ->subject('Password reset request')
            ->markdown('emails.forgetpassword')
            ->with('name', $this->user->name)
            ->with('link', $this->otp->OTP_code);
    }
}