<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerification extends Mailable
{
    use Queueable, SerializesModels;
    private $data ;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    // public function __construct()
    // {
        
    // }
    public function __construct($data)
    {
        $this->data = $data;
    }
 
    /**
     * Build the message.
     *
     * @return $this
     */
    // public function build()
    // {
    //     return $this->view('email.test');
    // }
    public function build()
   {
        return $this->view('email.verification_code')
           ->from('app@homewardbase.com')
           ->subject("Welcome To Homewardbase") 
           ->with([
                'data' => $this->data,
            ]);
   }
}
