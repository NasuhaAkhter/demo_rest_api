<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mention extends Model
{
    protected $fillable = [
        'chat_id','mention_id'
   ];
   public function user(){
        return $this->belongsTo('App\User', 'mention_id');
    }
}
