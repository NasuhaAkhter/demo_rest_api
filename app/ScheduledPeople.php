<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScheduledPeople extends Model
{
    protected $fillable = [
        	'appointment_id',	'user_id'
    ];
    public function people_info(){
        return $this->belongsTo('App\User', 'user_id');
    }
}
