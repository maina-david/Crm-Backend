<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Subscribe extends Mailable
{
    use Queueable, SerializesModels;
    public $email;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this 
     */
    public function build()
    {
        return $this
            ->subject('Welcome to our Callcenter system')
            ->markdown('emails.subscribers')
            ->with('name', "Kidanermariam")
            ->action('Thanks', url('http://127.0.0.1:8000'))
            ->with('link', "http://127.0.0.1:8000");
    }
}
