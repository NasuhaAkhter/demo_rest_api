<?php

namespace App; 

use Illuminate\Database\Eloquent\Model;
 
class ConversationChatSeen extends Model
{
    protected $fillable = [
        'chat_id',	'user_id'
        ];
         
}
