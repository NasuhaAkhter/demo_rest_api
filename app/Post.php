<?php

namespace App; 

use Illuminate\Database\Eloquent\Model;
  
class Post extends Model  
{ 
    protected $fillable = [
        'case_general_id','user_id','description', 'date'
    ];
    public function log_to_users(){
        return $this->hasMany('App\LogPost', 'post_id');
    }
    public function log_documents(){
        return $this->hasMany('App\LogDocument', 'post_id');
    }
    public function log_tags(){
        return $this->hasMany('App\LogTag', 'post_id');
    }
    public function user_info(){
        return $this->belongsTo('App\User', 'user_id');
    }
   
}
