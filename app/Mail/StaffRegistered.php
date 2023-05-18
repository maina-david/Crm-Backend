<?php

namespace App\Mail;

use App\Models\Internal\Staff;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffRegistered extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The staff instance.
     *
     * @var \App\Models\Internal\Staff
     */
    protected $user;

    protected $password;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\Internal\Staff  $user
     * @return void
     */
    public function __construct(Staff $user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome to ' . config('app.name'))
            ->markdown('emails.staff.registered')
            ->with([
                'name' => $this->user->name,
                'password' => $this->password
            ]);
    }
}