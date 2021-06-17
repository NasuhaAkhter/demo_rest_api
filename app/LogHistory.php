<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
   
class LogHistory extends Model
{
    protected $fillable = [
        'case_general_id','user_id', 'doc_id', 'to_user',	'message',	'tag' ,'seen'
    ];
    public function user_info(){
        return $this->belongsTo('App\User', 'user_id');
    }
    public function log_documents() {
        return $this->hasMany('App\CaseDocument','id','doc_id');
    }
}
