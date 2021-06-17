<?php 

namespace App;

use Illuminate\Database\Eloquent\Model;  
   
class NotificationSetting extends Model  
{   
    protected $fillable = [
        'user_id',
        'email_for_mentioned_in_chat',	'push_for_mentioned_in_chat',	
        'email_for_mentioned_in_log', 'push_for_mentioned_in_log',	
        'email_for_invited_to_join_case', 'push_for_invited_to_join_case',
        'email_for_removed_from_case',	'push_for_removed_from_case',	
        'email_for_someone_request_to_join_case', 'push_for_someone_request_to_join_case',	
        // 'email_for_join_in_case',
        // 'push_for_join_in_case',
        'email_for_someone_joins_case'	, 'push_for_someone_joins_case', 
        'email_for_someone_reject_removal',	  'push_for_someone_reject_removal',
        'email_for_join_request_reject',   'push_for_join_request_reject'
   ];
}
 