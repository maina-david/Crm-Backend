<?php

namespace App\Mail;

use App\Models\ConversationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyOutboundMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $message;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(ConversationMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.conversation.messages')
            ->with(['message' => $this->message]);
    }
}