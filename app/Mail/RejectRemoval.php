<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RejectRemoval extends Mailable
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
        return $this->view('email.someone_reject_removal')
            ->from('app@homewardbase.com')
            ->subject("Removal Suggestion Rejected") 
            ->with([ 
                    'data' => $this->data,
                ]);
   }
}
