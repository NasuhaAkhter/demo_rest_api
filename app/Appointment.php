<?php
 
namespace App;

use Illuminate\Database\Eloquent\Model;
 
class Appointment extends Model
{
    protected $fillable = [
        'case_general_id',	'doctor_id','title','type',	'location',	'message','from_date',	'from_time',	'to_date',	'to_time'
    ];
    public function attachments(){
        return $this->hasMany('App\AppointmentAttachment', 'appointment_id');
    }
    public function people(){
        return $this->hasMany('App\ScheduledPeople', 'appointment_id');
    }
    public function user_info(){
        return $this->belongsTo('App\User', 'appointment_id');

    }
}
