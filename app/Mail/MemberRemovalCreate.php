<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MemberRemovalCreate extends Mailable
{
    use Queueable, SerializesModels;
    private $data ;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = $this->data;
        return $this->view('email.removed_from_a_case_requested')
           ->from('app@homewardbase.com')
           ->subject("Removal Suggestion") 
           ->with([
                'data' => $this->data,
            ]);
    }
}
