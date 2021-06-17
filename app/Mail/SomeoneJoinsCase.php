<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SomeoneJoinsCase extends Mailable 
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
        return $this->view('email.someone_joins_a_case_notification')
           ->from('app@homewardbase.com')
           ->subject("New Member") 
           ->with([
                'data' => $this->data,
            ]);
   }
}
