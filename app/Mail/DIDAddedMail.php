<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DIDAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $user, $phone_number;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $phone_number)
    {
        $this->user = $user;
        $this->phone_number = $phone_number;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('New number allocated')
            ->markdown('emails.did_added')
            ->with('name', $this->user->name)
            ->with('phone_number', $this->phone_number);
    }
}