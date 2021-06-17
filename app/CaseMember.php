<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CaseMember extends Model
{
    protected $fillable = [
        'case_general_id',	'member_id' , 'status'
    ];
    public function user_info(){
        return $this->belongsTo('App\User', 'member_id');
    }
    public function case_details(){
        return $this->belongsTo('App\CaseGeneral', 'case_general_id');
    }
    
}
