<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogAppointment extends Model   
{
    protected $fillable = [
        'case_general_id', 'appointment_id', 'to_user',	'message',	'tag'	,'seen'
    ];
    public function user_info(){
        return $this->belongsTo('App\User', 'to_user');
    }

}
