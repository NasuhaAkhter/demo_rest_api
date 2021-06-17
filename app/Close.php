<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Close extends Model
{
    protected $fillable = [
        'case_general_id',	'user_id','is_accept', 'to_user'
    ];
    public function user_info(){
        return $this->belongsTo('App\User', 'user_id');
    }
    public function case_info(){
        return $this->belongsTo('App\CaseGeneral', 'case_general_id');
     }
}
