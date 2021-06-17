<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Auth\Events\Registered;
class EmailVerificationListener
{
    /**
     * Create the event listener.  
     *
     * @return void
     */
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        EmailVerification::dispatch($event);
    }
}
