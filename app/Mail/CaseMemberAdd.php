<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CaseMemberAdd extends Mailable
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
        return $this->view('email.invited_to_join_a_case_without_account')
           ->from('app@homewardbase.com')
           ->subject("Welcome To Homewardbase") 
           ->with([
                'data' => $this->data,
            ]);
   }
}
