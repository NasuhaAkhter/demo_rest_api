<?php

namespace App;
 
use Illuminate\Database\Eloquent\Model;

class MemberRequestToUser extends Model 
{  
    protected $fillable = [
        'case_general_id',	'user_id','to_user', 'email'
    ]; 
    public function user_info(){
        return $this->belongsTo('App\User','to_user');
     }
    public function from_user_info(){
        return $this->belongsTo('App\User','user_id');
     }
    public function case_info(){
        return $this->belongsTo('App\CaseGeneral', 'case_general_id');
     }
}
