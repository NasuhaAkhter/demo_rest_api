<?php

namespace App; 

use Illuminate\Database\Eloquent\Model; 

class Chat extends Model
{ 
    protected $fillable = [
        'case_general_id',	'message',	'sender',	'conversation_id',	'files'
    ];
    public function seenFor(){ 
        return $this->hasMany('App\ConversationChatSeen', 'chat_id', 'id');
    }
    public function mention_list(){ 
        return $this->hasMany('App\Mention', 'chat_id');
    }
    public function user_details(){
        return $this->belongsTo('App\User', 'sender');
    }
   
}
