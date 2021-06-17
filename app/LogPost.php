<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogPost extends Model
{
    protected $fillable = [
        'post_id', 'seen','to_user'   
    ];
    public function user_info(){
        return $this->belongsTo('App\User', 'to_user');
    }  
}  
