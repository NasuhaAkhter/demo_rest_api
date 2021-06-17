<?php 

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    // Type => Member, Appointment, 
    protected $fillable = [
         'user_id','case_general_id','title','message','link','type','is_seen'
    ];
}
 