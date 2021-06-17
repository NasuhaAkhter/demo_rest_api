<?php

    namespace App\Http\Controllers;
    use App\ActivitiesAndInterest;
    use App\Allergy;
    use App\Appointment;
    use App\AppointmentAttachment;
    use App\ProductGeneral;
    use App\ProductMember;
    use App\ProductPlacement;
    use App\ProductDocument;
    use App\Chat;
    use App\Conversation;
    use App\ConversationChatSeen;
    use App\ConversationMember;
    use App\Close;
    use App\Doctor;
    use App\Education; 
    use App\Featured;
    use App\Insurance;
    use App\InsuranceDocument;
    use App\JoinRequestsFromUser;
    use App\Category;
    use App\Language;
    use App\LegalInformation;
    use App\Post;
    use App\LogPost;
    use App\LogHistory;
    use App\LogAppointment;
    use App\LogTag;
    use App\LogDocument;
    use App\Medication;
    use App\MedicinTakingMethod;
    use App\MemberRequestToUser;
    use App\Notification;
    use App\NotificationSetting;
    use App\PhysicalCondition;
    use App\PlacementPeople;
    use App\Race;
    use App\Removal;
    use App\ScheduledPeople;
    use App\Sibling;
    use App\TermsAndCondition;
    use App\Mention;
    use App\User;
    use DB;
    use JWTAuth;
    use Auth;
    use Mail;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Support\Carbon ;
    
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Support\Collection; 
    use App\Mail\MentionInProduct;
    use App\Mail\ProductMemberAdd;
    use App\Mail\MemberRemoved; 
    use App\Mail\InvitedToJoinProduct;
    use App\Mail\RejectJoinRequest;
    use App\Mail\RejectRemoval;
    use App\Mail\SomeoneJoinsProduct;
    use App\Mail\SomeoneRequestToJoinProduct;
    use App\Mail\MemberRemovalCreate;
    use App\Mail\JoinConfirmation;
    // use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
    use DateTime;
    class DataController extends Controller
    {
            public function notification_settings(Request $request){
                $data = $request->all();
                $result = NotificationSetting::updateOrCreate(
                    ['user_id' => $data['user_id']],
                    [
                        'email_for_mentioned_in_chat' => $data['email_for_mentioned_in_chat'],
                        'push_for_mentioned_in_chat' => $data['push_for_mentioned_in_chat'],	
                        'email_for_mentioned_in_log' =>  $data['email_for_mentioned_in_log'],
                        'push_for_mentioned_in_log' => $data['push_for_mentioned_in_log'],
                        'email_for_invited_to_join_Product' => $data['email_for_invited_to_join_Product'],
                        'push_for_invited_to_join_Product' => $data['push_for_invited_to_join_Product'],
                        'email_for_removed_from_Product' => $data['email_for_removed_from_Product'],
                        'push_for_removed_from_Product' => $data['push_for_removed_from_Product'],
                        'email_for_someone_request_to_join_Product' => $data['email_for_someone_request_to_join_Product'],
                        'push_for_someone_request_to_join_Product' =>$data['push_for_someone_request_to_join_Product'],
                        // 'email_for_join_in_Product' => $data['email_for_join_in_Product'],
                        // 'push_for_join_in_Product' => $data['push_for_join_in_Product'],
                        'email_for_someone_joins_Product' => $data['email_for_someone_joins_Product'],
                        'push_for_someone_joins_Product' => $data['push_for_someone_joins_Product'],
                        'push_for_someone_reject_removal' => $data['push_for_someone_reject_removal'],
                        'email_for_join_request_reject' => $data['email_for_join_request_reject'],
                        'push_for_join_request_reject' => $data['push_for_join_request_reject']
                    ]
                );
                if($result){
                    return response()->json([
                        'result' => $result,
                        'success' => true
                    ],200);
                }else{
                    return response()->json([
                        'msg' => "Something went wrong",
                        'success' => false
                    ],400);
                }
                
            }
            public function seen_all_log(Request $request){
                $data = $request->all();
                $Product_id =  $data['Product_general_id'];
                if(!$data){
                    $Product = ProductGeneral::where('id',$Product_id )->first();
                    if(!$Product){
                        return  response()->json([
                            'msg' => "Something went wrong ! Please try again later.",
                            'success' => false
                        ],400);
                    }
                }
                $log_history = LogHistory::where('to_user', $data['user_id'])
                                ->where('Product_general_id', $data['Product_general_id'])->update([
                                    'seen' => 1
                                ]);
                $log_appointment = LogAppointment::where('to_user', $data['user_id'])
                                ->where('Product_general_id', $data['Product_general_id'])->update([
                                    'seen' => 1
                                ]);
                $log_Post = LogPost::where('to_user', $data['user_id'])
                                ->whereIn('post_id', function($query)  use($Product_id){
                                    $query->select('id')->from('posts')
                                    ->where('Product_general_id', $Product_id);
                                })->update([
                                    'seen' => 1
                                ]);
                $user = $data['user_id'];
                $newData = new Collection([]);
                $log_tags = new Collection([]);
                $log_documents = new Collection([]);
                $Log_appointment = LogAppointment::where('Product_general_id',  $data['Product_general_id'])
                    ->where('to_user',  $data['user_id'])
                    ->with('user_info')
                    ->limit(10)
                    ->orderBy('id', 'desc')
                    ->get();
                    if(sizeof($Log_appointment)>0){
                    foreach($Log_appointment  as $value){
                        $value['LogType'] = "Event";
                        $value['log_tags'] = $log_tags;
                        $value['log_documents'] = $log_documents;
                        $newData->push($value);
                    }
                }
                $Log_history = LogHistory::where('Product_general_id',  $data['Product_general_id'])
                                ->where('to_user',  $data['user_id'])
                                ->with('user_info', 'log_documents')
                                ->limit(10)
                                ->orderBy('id', 'desc')
                                ->get();
                    if(sizeof($Log_history)>0){
                    foreach($Log_history  as $value){
                        $value['LogType'] = "Update";
                        if($value['user_id'] == $data['user_id'] )
                        $value['log_tags'] = $log_tags;
                        // $value['log_documents'] = $log_documents;
                        // unset($value['user_info']);
                        $newData->push($value);
                    }
                }
                $Log_posts = Post::where('Product_general_id',  $data['Product_general_id'])
                                ->with('log_documents', 'log_tags', 'log_to_users', 'log_to_users.user_info')
                                // ->whereHas('is_seen', function ($query) use ($user){
                                //     $query->where('to_user',  $user);
                                //  })
                                ->whereHas('log_to_users', function ($query) use ($user){
                                    $query->where('to_user',  $user);
                                    })
                                ->limit(10)
                                ->orderBy('id', 'desc')
                                ->get();
                if(sizeof($Log_posts)>0){
                    foreach($Log_posts  as $value){
                        $value['LogType'] = "Log";
                        if($value['log_to_users']){
                            $value['seen'] = $value['log_to_users'][0]['seen'];
                            $value['user_info'] = $value['log_to_users'][0]['user_info'];
                        } 
                        unset($value['log_to_users']);
                        $newData->push($value);
                    }
                }
                return response()->json([
                    'Log' => $newData,
                    'success' => true
                ],200);
            }
            public function filter_log(Request $request){
                // return $request->all();
                // date range[beginning , present], type:[event,update,Log], tag
                $data = $request->all();
                $get_type =$request->get('type');
                
                $tag =$request->get('tag');
                $text =$request->get('text');
                if($data['beginning'] != null){
                    $minDate = $data['beginning'];
                }else{
                    $Product = ProductGeneral::where('id', $data['Product_general_id'])->first();
                    if(!$Product){
                        return response()->json([
                            'msg' => "Something went wrong .",
                            'success' => false
                        ],400);
                    }
                    $minDate = $Product->created_at;
                }
                if($data['present'] != null){
                    $maxDate = $data['present'];
                }
                else{
                    $today = Carbon::now('Asia/Dhaka');
                    $maxDate = $today;
                    // $maxDate = Carbon::createMidnightDate($today);
                }
                $user = $data['user_id'];
                $newData = new Collection([]);
                $log_tags = new Collection([]);
                $log_documents = new Collection([]);
               
                $updates = LogHistory::where('created_at', '>=', $minDate)
                                ->where('created_at', '<=', $maxDate)
                                ->where('Product_general_id', $data['Product_general_id'])
                                ->where('to_user', $data['user_id'])
                                ->with('user_info','log_documents');
                $appointment = LogAppointment::whereBetween('created_at', [$minDate, $maxDate])
                                ->where('Product_general_id', $data['Product_general_id'])
                                ->where('to_user', $data['user_id'])
                                ->with('user_info');
                $logs = Post::where('Product_general_id',$data['Product_general_id'] )
                                ->with('log_documents', 'log_tags', 'log_to_users', 'log_to_users.user_info')
                                // ->with(['log_to_users' => function ($q)  use ($user) {
                                //                 $q->where('to_user',$user);
                                //             }])
                                ->whereHas('log_to_users', function ($query) use ($user){
                                    $query->where('to_user',  $user);
                                  });
                                             
                if($text){
                    $appointment->where(function ($appointment) use ($text){
                        $appointment->orWhere('message',  'like', "%$text%");
                    });
                    $updates->where(function ($updates) use ($text){
                        $updates->orWhere('message',  'like', "%$text%");
                    });
                    $logs->where(function ($logs) use ($text){
                        $logs->orWhere('description',  'like', "%$text%");
                    });
                }
                if($tag){
                    $appointment->where(function ($appointment) use ($tag){
                        $appointment->whereIn('tag',$tag);
                    });
                    $updates->where(function ($updates) use ($tag){
                        $updates->whereIn('tag',$tag);
                    });
                    $logs->with(['log_tags' => function ($logs)  use ($tag) {
                                    $logs->where('tag',$tag);
                                }]);
                }
                if($get_type){
                    $event = in_array("Event",$get_type );
                    $update = in_array('Update',$get_type ) ;
                    $log = in_array('Log',$get_type ) ;
                    if($event){
                        $appointment = $appointment->orderBy('id', "desc")->get();
                        if(sizeof($appointment)>0){
                            foreach($appointment as $value){
                                $value['LogType'] = "Event";
                                $value['log_tags'] = $log_tags;
                                $value['log_documents'] = $log_documents;
                                $newData->push($value);
                            }
                        }
                    }
                    if($update){
                        $updates = $updates->orderBy('id', "desc")->get();
                        return $updates;
                        if(sizeof($updates)>0){
                            foreach($updates as $value){
                                $value['LogType'] = "Update";
                                if($value['user_id'] == $data['user_id'] )
                                $value['log_tags'] = $log_tags;
                                $value['log_documents'] = $log_documents;
                                $newData->push($value);
                            }
                        }
                    }
                    if($log){
                        $logs = $logs->orderBy('id', "desc")->get();
                        if(sizeof($logs)>0){
                            foreach($logs as $value){
                                $value['LogType'] = "Log";
                                if($value['log_to_users']){
                                    $value['seen'] = $value['log_to_users'][0]['seen'];
                                    $value['user_info'] = $value['log_to_users'][0]['user_info'];
                                } 
                                unset($value['log_to_users']);
                                $newData->push($value);
                            }
                        }
                    }
                }
                else if(sizeof($get_type)<=0){
                    $appointment = $appointment->get();
                    $updates = $updates->get();
                    $logs = $logs->get();
                    if(sizeof($appointment)>0){
                        foreach($appointment as $value){
                            $value['LogType'] = "Event";
                            $value['log_tags'] = $log_tags;
                            $value['log_documents'] = $log_documents;
                            $newData->push($value);
                        }
                    }
                    if(sizeof($updates)>0){
                        foreach($updates as $value){
                            $value['LogType'] = "Update";
                            if($value['user_id'] == $data['user_id'] )
                            $value['log_tags'] = $log_tags;
                            // $value['log_documents'] = $log_documents;
                            $newData->push($value);
                        }
    
                    }
                    if(sizeof($logs)>0){
                        foreach($logs as $value){
                            $value['LogType'] = "Log";
                            if($value['log_to_users']){
                                $value['seen'] = $value['log_to_users'][0]['seen'];
                                $value['user_info'] = $value['log_to_users'][0]['user_info'];
                            } 
                            unset($value['log_to_users']);
                            $newData->push($value);
                        }
                    }
                }
                if($newData != null){
                    return response()->json([
                        'result' => $newData,
                        'success' => true
                    ],200);
                }else{
                    return response()->json([
                        'msg' => "Something went wrong .",
                        'success' => false
                    ],400);
                }
                
            }
            public function chat_search(Request $request){
                $data = $request->all();
                $text = $request->get('text');
                $query =  Chat::where('Product_general_id', $data['Product_general_id'])
                                    ->with('user_details'); 
                if($text){
                    $query->where(function ($query) use ($text){
                        $query->orWhere('message',  'like', "%$text%");
                     });
                }
                $result = $query->orderBy('id', "desc")->get();
                if(sizeof($result)>0){
                    foreach($result as $value){
                        $value['files'] = json_decode($value['files']);
                    }
                }
                return response()->json([
                    'result' => $result,
                    'success' => true
                ],200);
            }
            public function search_result(Request $request){
                $data = $request->all();
                $result =  Chat::where('Product_general_id', $data['Product_general_id'])
                                    ->where('id', '>=', $data['id'])
                                    ->with('user_details')
                                    ->orderBy('id', "desc")
                                    ->get(); 
                if(sizeof($result)>0){
                    foreach($result as $value){
                        $value['files'] = json_decode($value['files']);
                    }
                }
                if($result){
                    return  response()->json([
                        'data' => $result,
                        'success' => true
                    ],200); 
                }
                else{
                    return  response()->json([
                        'msg' => "Something went wrong ! Please try again later.",
                        'success' => false
                    ],400);
                }
            }
            public function get_notification_settings(Request $request){
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
                $result = NotificationSetting::where('user_id', $user_id)->first();
                if($result){
                    return response()->json([
                        'notification_settings' => $result,
                        'success' => true
                    ],200);
                }else{
                    return response()->json([
                        'msg' => "You are not authorized .",
                        'success' => false
                    ],400);
                }
            }
            public function get_all_Product_general_by_id_prior(Request $request){
                $Product_id = ProductMember::where('member_id',$request->id)
                                    ->where('status',"Current")
                                    ->orWhere('status', "PendingRemoval")
                                    ->pluck('Product_general_id');
                if( !sizeof($Product_id)>0 ){
                    $newone = JoinRequestsFromUser::where('user_id', $request->id)
                                            ->pluck('Product_general_id');
                    $withdrawalRequest = [];
                    if(sizeof($newone) >0 ){
                        $withdrawalRequest = ProductGeneral::WhereIn('id', $newone)->get();
                    }
                    return response()->json([
                        'ProductGeneral' => [],
                        'New_Product_Invite' => [],
                        'withdrawalRequest' => $withdrawalRequest,
                        'pendingRemovals' => [],
                        'withdrawCloseRequests' => [],
                        'pendingCloses' => [],
                        'newMemberRequest' => [],
                        'success' => true
                    ],200);
                }
                else{
                    $user = User::where('id', $request->id)->first();
                    $New_Product_Invite = MemberRequestToUser::where('email',$user->email)
                    ->with('from_user_info','Product_info','Product_info.placements')
                    ->get();
                    if( sizeof($New_Product_Invite)>0){
                        foreach ($New_Product_Invite as $key => $value) {
                            $value['Product_info'] = $this->age_count($value['Product_info']);
                        }
                    }
                    $ProductGeneral = ProductGeneral::WhereIn('id', $Product_id)
                                                ->with('placements')
                                                ->get();
                    if(sizeof($ProductGeneral)>0){
                        foreach ($ProductGeneral as $key => $value) {
                            $value = $this->age_count($value);
                            $value = $this->notification_checkings($value);
                        }
                    }
                    $pendingRemovals = Removal::WhereIn('Product_general_id', $Product_id)
                                                ->where('to_user', $request->id)
                                                ->with('from_user_info','Product_info','Product_info.placements')
                                                ->get();
                    if(sizeof($pendingRemovals)>0){
                        $index = 0;
                        $newData = new Collection([]);
                        foreach ($pendingRemovals as $value) {
                            $value['Product_info'] = $this->age_count($value['Product_info']);
                            $toDate = Carbon::now('Asia/Dhaka');
                            $fromDate = $value['created_at'];
                            // $duration =  date_diff($toDate,$fromDate);
                            $duration = $toDate->diff($fromDate);
                            if($duration->d!= 0){
                                $value['day'] = 6 - $duration->d;
                            }else{
                                $value['day'] = 6;
                            }
                            $time = $duration->h + 6;
                            $value['hour'] = $time;
                            if($time >= 24){
                                $value['hour'] = $time -24;
                            }
                            if($value['hour']!= 0){
                                $value['hour'] = 23 - $value['hour'];
                            }else{
                                $value['hour'] = 23;
                            }
                            $value['min'] = $duration->i;
                            if($value['min']!= 0){
                                $value['min'] = 59 - $value['min'];
                            }else{
                                $value['min'] = 59;
                            }
                            if($duration->d >=7){
                                Removal::where('Product_general_id', $value['Product_general_id'])
                                ->where('to_user',$value['to_user'])
                                ->delete();
                                ProductMember::where('Product_general_id', $value['Product_general_id'])
                                            ->where('member_id',$value['to_user'])
                                            ->update([
                                                'status' => 'Current'
                                            ]);
                                unset($pendingRemovals[$index]);
                            }
                            else{
                                $newData->push($value);
                            }
                            $index ++;
                        }
                        $pendingRemovals = $newData;
                    }
                    $pendingCloses = Close::WhereIn('Product_general_id', $Product_id)
                                            ->where('to_user', $request->id)
                                            ->where('is_accept', null)
                                            ->with('user_info','Product_info','Product_info.placements')
                                            ->get();
                    if(sizeof($pendingCloses)>0){
                        $index = 0;
                        $newData = new Collection([]);
                        foreach ($pendingCloses as $key => $value) {
                            $value['Product_info'] = $this->age_count($value['Product_info']);
                            $toDate = Carbon::now('Asia/Dhaka');
                            $fromDate =$value['created_at'];
                            $duration = $toDate->diff($fromDate);
                            if($duration->d!= 0){
                                $value['day'] = 6 - $duration->d;
                            }else{
                                $value['day'] = 6;
                            }
                            $time = $duration->h + 6;
                            $value['hour'] = $time;
                            if($time >= 24){
                                $value['hour'] = $time -24;
                            }
                            if($value['hour']!= 0){
                                $value['hour'] = 23 - $value['hour'];
                            }else{
                                $value['hour'] = 23;
                            }
                            $value['min'] = $duration->i;
                            if($value['min']!= 0){
                                $value['min'] = 59 - $value['min'];
                            }else{
                                $value['min'] = 59;
                            }
                            if($duration->d >=7){
                                Close::where('Product_general_id', $value['Product_general_id'])
                                ->delete();
                                unset($pendingCloses[$index]);
                            }else{
                                $newData->push($value);
                            }
                            $index ++;
                        }
                        $pendingCloses = $newData;
                    }      
                    $withdrawCloseRequests = Close::select('Product_general_id')
                                             ->where('user_id', $request->id)
                                             ->with('Product_info','Product_info.placements')
                                             ->groupBy('Product_general_id')
                                             ->get();
                    if(sizeof($withdrawCloseRequests)>0){
                        foreach($withdrawCloseRequests as $value){
                            $index = 0;
                            $newData = new Collection([]);
                            $Left_times = Close::where('Product_general_id', $value->Product_general_id)
                                         ->where('user_id', $request->id)
                                         ->first();
                            $toDate = Carbon::now('Asia/Dhaka');
                            $fromDate =$Left_times->created_at;
                            $duration = $toDate->diff($fromDate);
                            if($duration->d!= 0){
                                $value['day'] = 6 - $duration->d;
                            }else{
                                $value['day'] = 6;
                            }
                            $time = $duration->h + 6;
                            $value['hour'] = $time;
                            if($time >= 24){
                                $value['hour'] = $time -24;
                            }
                            if($value['hour']!= 0){
                                $value['hour'] = 23 - $value['hour'];
                            }else{
                                $value['hour'] = 23;
                            }
                            $value['min'] = $duration->i;
                            if($value['min']!= 0){
                                $value['min'] = 59 - $value['min'];
                            }else{
                                $value['min'] = 59;
                            }
                            if($duration->d >=7){
                                Close::where('Product_general_id', $value['Product_general_id'])
                                ->delete();
                                unset($withdrawCloseRequests[$index]);
                            }else{
                                $newData->push($value);
                                $index ++;
                            }
                            $withdrawCloseRequests = $newData;
                         }
                    }
                    $newone = JoinRequestsFromUser::where('user_id', $request->id)
                                            ->pluck('Product_general_id');
                    $withdrawalRequest = [];
                    if(sizeof($newone) >0 ){
                        $withdrawalRequest = ProductGeneral::WhereIn('id', $newone)->get();
                    }
                    $newMemberRequest = JoinRequestsFromUser::WhereIn('Product_general_id', $Product_id)
                                                                ->where('to_user', $request->id)
                                                                ->with('user_info', 'Product_info','Product_info.placements')
                                                                ->get();
                    if(sizeof($newMemberRequest)>0){
                        foreach ($newMemberRequest as $key => $value) {
                            $value['Product_info'] = $this->age_count($value['Product_info']);
                        }
                    }
                    return response()->json([
                        'ProductGeneral' => $ProductGeneral,
                        'New_Product_Invite' => $New_Product_Invite,
                        'withdrawalRequest' => $withdrawalRequest,
                        'pendingRemovals' => $pendingRemovals,
                        'withdrawCloseRequests' => $withdrawCloseRequests,
                        'pendingCloses' => $pendingCloses,
                        'newMemberRequest' => $newMemberRequest,
                        'success' => true
                    ],200);
                }
            }
            public function get_all_Product_general_by_id(Request $request){
                $user = Auth::user();
                $Products = ProductMember::where('member_id',$request->id)
                                    ->whereIn('status',["Current", "PendingRemoval"])
                                    ->orderBy('Product_general_id', "asc")
                                    ->limit(7)
                                    ->pluck('Product_general_id');
                $newRequest = JoinRequestsFromUser::where('user_id', $request->id)
                ->pluck('Product_general_id');
                $get_the_Product = [];
                if(sizeof($newRequest) >0 ){
                     $index = 0;
                    foreach($newRequest as $value){
                        $get_the_Product = ProductGeneral::where('id', $value)->first();
                        $newRequest[$index] = $get_the_Product;
                        $index++;
                    }
                } 
                $New_Product_Invite = MemberRequestToUser::where('email',$user->email)
                ->with('from_user_info','Product_info','Product_info.placements')
                ->get();
                if(sizeof($Products)>0){
                    $Productindex = 0;
                    foreach($Products as $value){
                        $general = ProductGeneral::where('id', $value)->with('placements')->first();
                        $new = [];
                        $general = $this->age_count($general);
                        $general = $this->notification_checkings($general);
                        $new['Product'] = $general;
                        $pendingRemovals=[] ;
                        $pendingRemovals =Removal::Where('Product_general_id', $value)
                            ->where('to_user', $request->id)
                            ->with('from_user_info')
                            ->get();
                        if(sizeof($pendingRemovals)>0){
                            $index = 0;
                            $newData = new Collection([]);
                            foreach ($pendingRemovals as $value2) {
                                $toDate = Carbon::now('Asia/Dhaka');
                                $fromDate = $value2['created_at'];
                                $duration = $toDate->diff($fromDate);
                                if($duration->d!= 0){
                                    $value2['day'] = 6 - $duration->d;
                                }else{
                                    $value2['day'] = 6;
                                }
                                $time = $duration->h + 6;
                                $value2['hour'] = $time;
                                if($time >= 24){
                                    $value2['hour'] = $time -24;
                                }
                                if($value2['hour']!= 0){
                                    $value2['hour'] = 23 - $value2['hour'];
                                }else{
                                    $value2['hour'] = 23;
                                }
                                $value2['min'] = $duration->i;
                                if($value2['min']!= 0){
                                    $value2['min'] = 59 - $value2['min'];
                                }else{
                                    $value2['min'] = 59;
                                }
                                if($duration->d >=7){
                                    Removal::where('Product_general_id', $value2['Product_general_id'])
                                    ->where('to_user',$value2['to_user'])
                                    ->delete();
                                    ProductMember::where('Product_general_id', $value2['Product_general_id'])
                                                ->where('member_id',$value2['to_user'])
                                                ->delete();
                                    unset($pendingRemovals[$index]);
                                }
                                else{
                                    $newData->push($value2);
                                }
                                $index ++;
                            }
                            $pendingRemovals = $newData;
                        } 
                        $new['pendingRemovals'] = $pendingRemovals;

                        $pendingCloses=[];
                        $pendingCloses = Close::where('Product_general_id', $value)
                                ->where('to_user', $request->id)
                                ->where('is_accept', null)
                                ->with('user_info')
                                ->get();
                        
                        if(sizeof($pendingCloses)>0){
                            $index = 0;
                            $newData = new Collection([]);
                            foreach ($pendingCloses as $key => $value3) {
                                $toDate = Carbon::now('Asia/Dhaka');
                                $fromDate =$value3['created_at'];
                                $duration = $toDate->diff($fromDate);
                                if($duration->d!= 0){
                                    $value3['day'] = 6 - $duration->d;
                                }else{
                                    $value3['day'] = 6;
                                }
                                $time = $duration->h + 6;
                                $value3['hour'] = $time;
                                if($time >= 24){
                                    $value3['hour'] = $time -24;
                                }
                                if($value3['hour']!= 0){
                                    $value3['hour'] = 23 - $value3['hour'];
                                }else{
                                    $value3['hour'] = 23;
                                }
                                $value3['min'] = $duration->i;
                                if($value3['min']!= 0){
                                    $value3['min'] = 59 - $value3['min'];
                                }else{
                                    $value3['min'] = 59;
                                }
                                if($duration->d >=7){
                                    Close::where('Product_general_id', $value3['Product_general_id'])
                                    ->delete();
                                    unset($pendingCloses[$index]);
                                }else{
                                    $newData->push($value3);
                                }
                                $index ++;
                            }
                            $pendingCloses = $newData;
                        }
                        $new['pendingCloses'] = $pendingCloses;

                        $withdrawCloseRequests=null ;
                        $withdrawCloseRequests = Close::where('Product_general_id', $value)
                                             ->where('user_id', $request->id)
                                             ->first();
                        if($withdrawCloseRequests){
                            $toDate = Carbon::now('Asia/Dhaka');
                            $fromDate =$withdrawCloseRequests->created_at;
                            $duration = $toDate->diff($fromDate);
                            if($duration->d!= 0){
                                $withdrawCloseRequests['day'] = 6 - $duration->d;
                            }else{
                                $withdrawCloseRequests['day'] = 6;
                            }
                            $time = $duration->h + 6;
                            $withdrawCloseRequests['hour'] = $time;
                            if($time >= 24){
                                $withdrawCloseRequests['hour'] = $time -24;
                            }
                            if($withdrawCloseRequests['hour']!= 0){
                                $withdrawCloseRequests['hour'] = 23 - $withdrawCloseRequests['hour'];
                            }else{
                                $withdrawCloseRequests['hour'] = 23;
                            }
                            $withdrawCloseRequests['min'] = $duration->i;
                            if($withdrawCloseRequests['min']!= 0){
                                $withdrawCloseRequests['min'] = 59 - $withdrawCloseRequests['min'];
                            }else{
                                $withdrawCloseRequests['min'] = 59;
                            }
                            if($duration->d >=7){
                                Close::where('Product_general_id', $withdrawCloseRequests['Product_general_id'])
                                ->delete();
                                $withdrawCloseRequests = [];
                            }
                        }
                        $new['withdrawCloseRequests'] = $withdrawCloseRequests;
                        $newMemberRequest=[] ;
                        $newMemberRequest = JoinRequestsFromUser::Where('Product_general_id', $value)
                                                                ->where('to_user', $request->id)
                                                                ->with('user_info')
                                                                ->get();
                        if(sizeof($newMemberRequest)>0){
                            $index = 0;
                            $newData = new Collection([]);
                            foreach ($newMemberRequest as $value4) {
                                $toDate = Carbon::now('Asia/Dhaka');
                                $fromDate = $value4['created_at'];
                                $duration = $toDate->diff($fromDate);
                                if($duration->d!= 0){
                                    $value4['day'] = 6 - $duration->d;
                                }else{
                                    $value4['day'] = 6;
                                }
                                $time = $duration->h + 6;
                                $value4['hour'] = $time;
                                if($time >= 24){
                                    $value4['hour'] = $time -24;
                                }
                                if($value4['hour']!= 0){
                                    $value4['hour'] = 23 - $value4['hour'];
                                }else{
                                    $value4['hour'] = 23;
                                }
                                $value4['min'] = $duration->i;
                                if($value4['min']!= 0){
                                    $value4['min'] = 59 - $value4['min'];
                                }else{
                                    $value4['min'] = 59;
                                }
                                if($duration->d >=7){
                                    JoinRequestsFromUser::where('Product_general_id', $value4['Product_general_id'])
                                    ->where('to_user',$value4['to_user'])
                                    ->delete();
                                    unset($newMemberRequest[$index]);
                                }
                                else{
                                    $newData->push($value4);
                                }
                                $index++;
                            }
                            $newMemberRequest = $newData;
                        }
                        $new['newMemberRequest'] = $newMemberRequest;
                        $Products[$Productindex] = $new;
                        $Productindex++;
                    }
                }
                return response()->json([
                    'Products' => $Products,
                    'New_Product_Invite' => $New_Product_Invite,
                    'withdrawalRequest' => $newRequest,
                    'success' => true
                ],200);
            }
            public function get_more_Products(Request $request){
                // latest id 
                $data = $request->all();
                $last_Product_id = $data['Product_general_id'];
                $user_id = $data['user_id'];
                 $Products = ProductMember::where('Product_general_id', '>', $last_Product_id)
                ->where('member_id',$user_id)
                ->whereIn('status',["Current", "PendingRemoval"])
                ->limit(7)
                ->pluck('Product_general_id');
                if(sizeof($Products)>0){
                    $index = 0;
                    foreach ($Products as $value){
                        $result = $this->get_Product_result($value, $user_id);
                        $Products[$index] = $result;
                        $index++;
                    }
                }else{
                    $Products = [];
                }
                return $Products;

            }
            public function duration_count($data){
                $toDate = Carbon::now('Asia/Dhaka');
                $fromDate =$data['created_at'];
                $duration =  date_diff($fromDate,$toDate);
                $time =  $duration->format("%H:%I:%S");
                $data['duration'] = $time;                
                return $data;

            }
            public function notification_checkings($data){
                $noti = "no";
                $user = JWTAuth::parseToken()->authenticate()->id;
                $today_date = date("Y/m/d");
                $get_appointment = Appointment::where('Product_general_id', $data['id'])
                                            ->where('from_date', '<',$today_date)
                                            ->get();
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id;
                $member_id =  ProductMember::where('Product_general_id', $data['id'])
                            ->whereIn('status', ["Current","PendingRemoval" ])
                            ->pluck('member_id');
                 foreach($get_appointment as $value){
                    if($value['from_date'] != null){
                        $time =new DateTime($value['from_date']); 
                        $value['from_date'] =  $time->format('m-d-Y');
                      }
                     if($value['title']){
                        $message ="An appointment ".$value['title']." took place on ".$value['from_date'].".";
                     }else{
                        $message ="An appointment took place on ".$value['from_date'].".";
                     }
                    foreach($member_id as $value2){
                        if($value2 == $AuthUserId){
                            $create = LogAppointment::firstOrCreate([
                                'Product_general_id' => $value['Product_general_id'],
                                'to_user' => $value2,
                                'appointment_id' => $value['id'],
                                'message' => $message,
                                'tag' =>$value['type'],
                                'seen' => 1
                                ]);
                        }else{
                            $create = LogAppointment::firstOrCreate([
                                'Product_general_id' => $value['Product_general_id'],
                                'appointment_id' => $value['id'],
                                'to_user' => $value2,
                                'message' => $message,
                                'tag' =>$value['type'],
                                'seen' => 0
                                ]);
                        }
                    }
                }
                $log_history = LogHistory::where('Product_general_id',$data['id'] )
                                            ->where('to_user',$user)
                                            ->where('seen',0)
                                            ->get();
                $member_add_remove = Notification::where('Product_general_id',$data['id'] )
                                        ->where('user_id',$user)
                                        ->where('type', "Member")
                                        ->where('is_seen',0)
                                        ->get();
                $pendingAdds = JoinRequestsFromUser::where('to_user', $user)
                                            ->where('Product_general_id',$data['id'] )
                                            ->get();

                $appointment_noti = LogAppointment::where('Product_general_id',$data['id'] )
                                        ->where('to_user',$user)
                                        ->where('seen',0)
                                        ->get();
                $new_chat = Chat::select('conversation_id', DB::raw('count(`conversation_id`) as total_msg'))
                                ->where('Product_general_id', $data['id'])
                                ->where('sender', '!=', $user)
                                ->whereNotIn('id', function($query) use($user){
                                    $query->select('chat_id')->from('conversation_chat_seens')
                                    ->where('user_id', $user);
                                })
                                ->groupBy('conversation_id')
                                ->first();
                $Log_appointments = LogAppointment::where('Product_general_id',$data['id'] )
                                        ->where('to_user',$user)
                                        ->where('seen',0)
                                        ->get();
                $Log_posts = Post::where('Product_general_id',$data['id'] )
                                    ->with('log_to_users')
                                    ->whereHas('log_to_users', function ($query) use ($user){
                                        $query->where('to_user',  $user);
                                        $query->where('seen',  0);
                                     })
                                    ->get();
                if(sizeof($log_history)>0 || sizeof($Log_appointments)>0 || sizeof($Log_posts)>0 || sizeof($member_add_remove)>0  || sizeof($pendingAdds)>0 ){
                    $noti = "yes";
                }
                if($new_chat && $new_chat->total_msg >0){
                    $noti = "yes";
                }
                $data['notification'] = $noti;                
                return $data;
            }
            public function age_count($data){
                $today = date("Y/m/d");
                $toDate = Carbon::createMidnightDate($today);
                $fromDate = Carbon::createMidnightDate($data['birthday']);
                        $year =  $fromDate->diffInYears($toDate);
                        $month =  $fromDate->diffInMonths($toDate);
                        $day =  $fromDate->diffInDays($toDate);
                        if($year>0){
                            if($month>=0 && $day >=0){
                                $age= $year;
                            }else{
                                $age = $year-1;
                            }
                        }else{
                            $age = 0;
                        }
                        $data['age'] = $age;                
                return $data;
            }
            public function get_Product_general_by_id(Request $request){
                $data = $request->all();
                $user = $data['user_id'];
                $ProductGeneral = ProductGeneral::where('id',$data['Product_general_id'])
                                    ->with('placements')
                                    ->first();
                if($ProductGeneral){
                    $ProductGeneral = $this->age_count($ProductGeneral);
                }
                $information = "no";
                $member = "no";
                $appointment = "no";
                $chat = 0;
                $log = "no";
                $log_history = LogHistory::where('Product_general_id',$data['Product_general_id'] )
                                            ->where('to_user',$user)
                                            ->where('seen',0)
                                            ->get();
                if(sizeof($log_history)>0){
                    $information = "yes";
                }
                $member_add_remove = Notification::where('Product_general_id',$data['Product_general_id'] )
                                        ->where('user_id',$user)
                                        ->where('type', "Member")
                                        ->where('is_seen',0)
                                        ->get();
                if(sizeof($member_add_remove)>0){
                    $member = "yes";
                }
                $pendingAdds = JoinRequestsFromUser::where('to_user', $user)
                                            ->where('Product_general_id',$data['Product_general_id'] )
                                            ->get();
                if(sizeof($pendingAdds)>0){
                    $member = "yes";
                }
                $appointment_noti = Notification::where('Product_general_id',$data['Product_general_id'] )
                                        ->where('user_id',$user)
                                        ->where('type', "Appointment")
                                        ->where('is_seen',0)
                                        ->get();
                if(sizeof($appointment_noti)>0){
                    $appointment = "yes";
                }
                $new_chat = Chat::select('conversation_id', DB::raw('count(`conversation_id`) as total_msg'))
                                ->where('Product_general_id', $data['Product_general_id'])
                                ->where('sender', '!=', $user)
                                ->whereNotIn('id', function($query) use($user){
                                    $query->select('chat_id')->from('conversation_chat_seens')
                                    ->where('user_id', $user);
                                })
                                ->groupBy('conversation_id')
                                ->first();
                if($new_chat){
                    if($new_chat->total_msg>0){
                        $chat = $new_chat->total_msg;
                    }
                }
                $Log_appointments = LogAppointment::where('Product_general_id',$data['Product_general_id'] )
                                        ->where('to_user',$user)
                                        ->where('seen',0)
                                        ->get();
                $Log_posts = Post::where('Product_general_id',$data['Product_general_id'] )
                                //    ->with(['log_to_users' => function ($q)  use ($user) {
                                //         $q->where('to_user',$user);
                                //     }])
                                    ->with('log_to_users')
                                    ->whereHas('log_to_users', function ($query) use ($user){
                                        $query->where('to_user',  $user);
                                        $query->where('seen',  0);
                                     })
                                    ->get();
                if(sizeof($log_history)>0 || sizeof($Log_appointments)>0 || sizeof($Log_posts)>0){
                    $log = "yes";
                }
                return response()->json([
                    'ProductGeneral' => $ProductGeneral,
                    'notification_in_information' => $information,
                    'notification_in_member' => $member,
                    'notification_in_appointment' => $appointment,
                    'notification_in_chat' => $chat,
                    'notification_in_log' => $log,
                    'success' => true
                ],200);
            }
            public function delete_Product_general(Request $request){
                $delete= ProductGeneral::where('id',$request->id)->delete();
                if($delete == 1){
                    return  response()->json([
                            'success' => true
                        ],200);
                }
                else{
                    return  response()->json([
                            'success' => false
                        ],200);
                }
            }
            public function update_Product_general(Request $request){
                $update = ProductGeneral::where('id',$request->id)->update([
                    'first_name' => $request->get('first_name'),
                    'last_name' => $request->get('last_name'),
                    'gender' => $request->get('gender'),
                    'birthday' => $request->get('birthday'),
                    'place_of_birth' => $request->get('place_of_birth'),
                    'race' => $request->get('race'),
                    'ethnicity' => $request->get('ethnicity'),
                    'ssn' => $request->get('ssn'),
                    'user_id' => $request->get('user_id'),
                ]);
                if($update == 1){
                    return  response()->json([
                            'success' => true
                        ],200);
                }
                else{
                    return  response()->json([
                            'success' => false
                        ],200);
                }

            }
            public function post_Product_general(Request $request){
                 $ProductGeneral = ProductGeneral::firstOrCreate([
                    'first_name' => $request->get('first_name'),
                    'last_name' => $request->get('last_name'),
                    'birthday' => $request->get('birthday'),
                    'user_id' => $request->get('user_id'),
                   ]);
                   if($ProductGeneral){
                       $conversation_create = Conversation::create([
                        'Product_general_id' => $ProductGeneral->id,
                       ]);
                       if($conversation_create){
                        $conversation_member_create = ConversationMember::create([
                            'conversation_id' => $conversation_create->id,
                            'user_id' => $request->get('user_id')
                           ]);
                       }
                       if($conversation_member_create){
                        $member_create = ProductMember::firstOrCreate([
                            'Product_general_id' => $ProductGeneral->id,
                            'member_id' => $request->get('user_id'),
                            'status' => "Current"
                           ]);
                       }
                       if($member_create){
                           $placement_create = ProductPlacement::create([
                            'Product_general_id' => $ProductGeneral->id,
                           ]); 
                        
                       }
                       if($placement_create){
                           $insurance_create =Insurance::create([
                            'Product_general_id' => $ProductGeneral->id,
                           ]);
                       }
                       if($insurance_create){
                           $legal_create = LegalInformation::create([
                            'Product_general_id' => $ProductGeneral->id,
                           ]);
                       }
                       if($legal_create){
                        return response()->json([
                            'ProductGeneral' => $ProductGeneral,
                            'success' => true
                        ],200);
                       }
                       else{
                        return response()->json([
                            'success' => false
                        ],200);
                       }
                   
                   }
                   else{
                    return response()->json([
                        'success' => false
                    ],200);
                   }
                   
            }
            public function get_by_filter(Request $request){
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
                $data = $request->all();
                $to_age= $data['to_age']; 
                $Product_name= $data['Product_name']; 
                $from_age= $data['from_age'];  
                $placement_type= $data['placement_type'];  
                $gender= $data['gender']; 
                $race=$data['race'];   
                $ethnicity= $data['ethnicity']; 
                $sibling_status=$data['sibling_status'];  
                $parental_right_status= $data['parental_right_status'];
                $language=  $data['language'];
                // $races=  $data['races'];
                $minDate = Carbon::today()->subYears($from_age);  
                $maxDate = Carbon::today()->subYears($to_age)->endOfDay();
                $query =  ProductGeneral::whereBetween('birthday', [$minDate, $maxDate])
                                    ->with('placements','siblings', 'legalInformations','language'); 
                if($Product_name){
                    $query->where(function ($query) use ($Product_name){
                        $query->orWhere('first_name',  'like', "%$Product_name%");
                        $query->orWhere('last_name',  'like', "%$Product_name%");
                    });
                    $query->orWhere(DB::raw('CONCAT_WS(" ", first_name, last_name)'), 'like', '%' . $Product_name . '%');
                }
                if(sizeof($placement_type)>0){
                    $query->whereHas('placements', function ($query) use ($placement_type){
                        $query->WhereIn('placement_type',$placement_type);
                     });
                 }
                if($language && sizeof($language)>0 ){
                    $query->whereHas('language', function ($query) use ($language){
                        $query->WhereIn('language',$language);
                     });
                 }
                if(sizeof($gender)>0){
                    $query->WhereIn('gender', $gender);
                }
                if($race && sizeof($race)>0 ){
                    $query->whereHas('races', function ($query) use ($race){
                        $query->WhereIn('races',$race);
                     });
                 }
                if(sizeof($ethnicity)>0){
                    $query->WhereIn('ethnicity', $ethnicity);
                }
                if(sizeof($parental_right_status)>0){
                    $query->whereHas('legalInformations', function ($query) use ($parental_right_status){
                        $query->WhereIn('right_status',$parental_right_status);
                     });
                }
                if(sizeof($sibling_status)>0){  
                    foreach($sibling_status as $value){
                        if($value['status'] == "In care"){
                            if($value['three_plus_siblings'] == "yes"){
                                $query->WhereIn('in_care_siblings', $value['sibling_number'])
                                ->orWhere('in_care_siblings' , '>=', 3);
        
                            }else{
                                $query->WhereIn('in_care_siblings', $value['sibling_number']);
                            }
                        }
                        else if($value['status'] == "Not in care"){
                            if($value['three_plus_siblings'] == "yes"){
                                $query->WhereIn('not_in_care_siblings', $value['sibling_number'])
                                ->orWhere('not_in_care_siblings' , '>=', 3);
                            }else{
                                $query->WhereIn('not_in_care_siblings', $value['sibling_number']);
        
                            }
                        }
                    }
                }
                // $data = $query->get();
                $data = $query->with('member_list')->get();
                foreach($data as $value){
                    foreach ($value['member_list'] as $value2){
                        if($value2['member_id'] == $user_id ){
                            $value['is_member'] = "yes";
                        }
                    }
                    if($value['is_member'] != "yes"){
                        $value['is_member'] = "no";

                    }
                    $value = $this->age_count($value);
                    unset($value['siblings']);
                    unset($value['legal_informations']);
                    unset($value['language']);
                    unset($value['member_list']);

                }

                return response()->json([
                    'data' => $data,
                    'success' => true
                ],200);
            }
            public function post_ProductMember(Request $request){
                $all_data = $request->all();
                $email = $all_data['emails'];
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id;
                $result = "NotDone";
                $Product = ProductGeneral::where('id',$request->get('Product_general_id'))->first();
                $user = User::where('id', $AuthUserId)->first();
                $data = [
                    'Product_first_name' => $Product->first_name,
                    'Product_last_name' => $Product->last_name, 
                    'user_first_name' => $user->first_name, 
                    'user_last_name' => $user->last_name, 
                    ];
                $member_id =  ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                ->where('member_id', '!=', $AuthUserId)
                ->whereIn('status', ["Current", "PendingRemoval"])
                ->pluck('member_id');
                if($email && sizeof($email)>0 ){
                    foreach($email as $value){
                     $find_user = User::where('email', $value)->first();
                        if($find_user){
                            $find_member = ProductMember::where('Product_general_id',  $all_data['Product_general_id'])
                            ->where('member_id', $find_user->id )
                            ->whereIn('status', ["Current", "PendingRemoval"])
                            ->count();
                            if($find_member>0){
                                $result = "Done";
                            }
                            else{
                                $checkEmail = NotificationSetting::where('user_id', $find_user->id)->where('email_for_invited_to_join_Product', 1)->count();
                                if($checkEmail > 0){
                                    $ProductMember = MemberRequestToUser::firstOrCreate([
                                        'Product_general_id' => $all_data['Product_general_id'],
                                        'user_id' => $AuthUserId,
                                        'to_user' =>$find_user->id,
                                        'email' => $value,
                                    ]);
                                    try{
                                        $mail = Mail::to($value)->send(new InvitedToJoinProduct($data));
                                    }catch (\Exception $e) {
                                        return 0;
                                    }
                                    if($ProductMember){
                                    $result = "Done";
                                    }
                                }
                                $checkNoti = NotificationSetting::where('user_id', $find_user->id)->where('push_for_invited_to_join_Product', 1)->count();
                                if($checkNoti != 0){
                                    $notification_create = Notification::create([
                                        'user_id' => $find_user->id,
                                        'Product_general_id' => $request->get('Product_general_id'),
                                        'title' => 'Notification at '.$Product->first_name." ".$Product->last_name."'s Product.",
                                        'message' => $user->first_name." ".$user->last_name." invited you to join ".$Product->first_name." ".$Product->last_name."'s Product.",
                                        'type' => "Member",
                                        'is_seen' => 0
                                    ]);
                                    $notification =$this->notification_check($notification_create);
                                }
                            }
                        }
                        else{
                            $validator = Validator::make($request->all(),[
                                $value => 'string|email',
                            ]);
                            if($validator->fails()){
                                return response()->json($validator->errors(), 400);
                            }
                            try{
                                $mail = Mail::to($value)->send(new ProductMemberAdd($data));
                            }catch (\Exception $e) {
                                return 0;
                            }
                            $ProductMember = MemberRequestToUser::firstOrCreate([
                                'Product_general_id' => $all_data['Product_general_id'],
                                'user_id' => $AuthUserId,
                                'to_user' =>0,
                                'email' => $value,
                              ]);
                            if($ProductMember){
                                $result = "Done";
                            }
                        } 
                         
                    }
                    if($result == "Done"){
                        return response()->json([
                            'success' => true
                        ],200);
                    }else{
                        return response()->json([
                            'msg' => "Something went wrong .",
                            'success' => false
                        ],400);
                    }
                }
                else{
                    return response()->json([
                        'msg' => "Something went wrong .",
                        'success' => false
                    ],400);
                }
            }
            public function withdrawRequest(Request $request){
                // Product_general_id, user_id
                $delete = JoinRequestsFromUser::where('Product_general_id',$request->get('Product_general_id'))
                                        ->where('user_id', $request->get('user_id'))
                                        ->delete();
                if($delete >0){
                    return response()->json([
                        'success' => true
                    ],200);
                }
                else
                {
                    return response()->json([
                        'success' => false
                    ],200);
                }


            }
            public function get_requested_member_list(Request $request){
                $newone = JoinRequestsFromUser::where('Product_general_id',$request->get('Product_general_id'))
                                        ->where('user_id', $request->user_id)
                                        ->pluck('to_user');

                if(sizeof($newone)>0){
                    $Product_info = ProductGeneral::where('id', $request->get('Product_general_id'))->first();
                    $user_list = User::WhereIn('id',$newone )
                    ->get();
                    return response()->json([
                        'Product_info' => $Product_info,
                        'user_list' => $user_list,
                        'success' => true
                    ],200);
                }
                else{
                    return response()->json([
                            'success' => false
                    ],200);
                }
                     
            }
            public function accepting_Product_invite(Request $request){
                $data =  $request->all();
                $Product = ProductGeneral::where('id',$data['Product_general_id'])->first();
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id;
                $user = User::where('id', $AuthUserId)->first();
                $invite = MemberRequestToUser::where('email',$data['email'])
                ->where('Product_general_id',$request->get('Product_general_id'))
                ->first();
                $member_id =  ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                    ->where('member_id', '!=', $request->get('to_user'))
                    ->where('member_id', '!=', $AuthUserId)
                    ->whereIn('status',["Current", "PendingRemoval"] )
                    ->pluck('member_id');
                $massData = [];
                $singleMemberNotiData = [
                    'Product_general_id' =>$request->get('Product_general_id'),
                    'appointment_id' => 0,
                    'tag' => "Biographical",
                ];
                $delete = MemberRequestToUser::where('email',$data['email'])
                ->where('Product_general_id',$data['Product_general_id'])->delete();
                if($delete > 0){
                    $already_Accepted = ProductMember::where('Product_general_id', $data['Product_general_id'])
                    ->where('member_id',  $AuthUserId)
                    ->first();
                    if($already_Accepted){
                        if($already_Accepted->status == "Current" || $already_Accepted->status == "PendingRemoval"){
                            return response()->json([
                                'msg'      => "You are already a member",
                                'success'  => true
                            ],200);
                        }
                        else if($already_Accepted->status == "Former"){
                                $conversation = Conversation::where('Product_general_id', $data['Product_general_id'] )
                                ->first();
                                if($conversation){
                                    $conversation_member_create = ConversationMember::create([
                                        'conversation_id' => $conversation->id,
                                        'user_id' => $AuthUserId
                                    ]);
                                }
                                ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                                            ->where('member_id', $request->get('to_user'))
                                            ->update([
                                                'status' => "Current"
                                            ]);
                                $actor = User::where('id',$invite->user_id)->first();
                                $email_data = [
                                    'Product_first_name' => $Product->first_name,
                                    'Product_last_name' => $Product->last_name,
                                    'user_first_name' => $user->first_name,
                                    'user_last_name' => $user->last_name,
                                    'actor_first_name' => $actor->first_name,
                                    'actor_last_name' => $actor->last_name,
                                ];
                                try{
                                    $mail = Mail::to($user->email)->send(new JoinConfirmation($email_data));
                                }catch (\Exception $e) {
                                    return 0;
                                }
                                $singleMemberNotiData['message'] = $user->first_name." ".$user->last_name." joined ".$Product->first_name." ".$Product->last_name."'s Product.";
                                foreach($member_id as $value){
                                    if( $value == $AuthUserId){
                                        $seen = 1;
                                    }else{
                                        $seen = 0;
                                    }
                                    $singleMemberNotiData['seen'] =  $seen;
                                    $singleMemberNotiData['to_user'] = $value;
                                    array_push($massData, $singleMemberNotiData);
                                    $checkNoti = NotificationSetting::where('user_id', $value)->where('push_for_someone_joins_Product', 1)->count();
                                    if($checkNoti != 0){
                                        $notification_create = Notification::create([
                                            'user_id' => $value,
                                            'Product_general_id' => $request->get('Product_general_id'),
                                            'title' => 'Notification at '.$Product->first_name." ".$Product->last_name."'s Product.",
                                            'message' => $user->first_name." ".$user->last_name." has just joined ".$Product->first_name." ".$Product->last_name."'s Product.",
                                            'type' => "Member",
                                            'is_seen' => 0
                                        ]);
                                        $notification =$this->notification_check($notification_create);
                                    }
                                    $checkEmail = NotificationSetting::where('user_id', $value)->where('email_for_someone_joins_Product', 1)->count();
                                    if($checkEmail != 0 ){
                                        $member_info = User::where('id', $value)->first();
                                        $email = $member_info->email;
                                        try{
                                            $mail = Mail::to($email)->send(new SomeoneJoinsProduct($email_data));
                                        }catch (\Exception $e) {
                                            return 0;
                                        }
                                     }
                                }
                                LogAppointment::insert($massData); 
                                $result = $this->get_Product_result($data['Product_general_id'],  $AuthUserId);
                                 return response()->json([
                                'result' => $result,
                                'success' => true
                                ],200);
                            }
                    }else{
                        $conversation = Conversation::where('Product_general_id', $data['Product_general_id'] )
                        ->first();
                        if($conversation){
                            $conversation_member_create = ConversationMember::create([
                                'conversation_id' => $conversation->id,
                                'user_id' => $AuthUserId
                            ]);
                        }
                        ProductMember::firstOrCreate([
                            'Product_general_id' => $data['Product_general_id'],
                            'member_id' =>  $AuthUserId,
                            'status' => "Current"
                        ]);
                        $actor = User::where('id',$invite->user_id)->first();
                        $email_data = [
                            'Product_first_name' => $Product->first_name,
                            'Product_last_name' => $Product->last_name,
                            'user_first_name' => $user->first_name,
                            'user_last_name' => $user->last_name ,
                            'actor_first_name' => $actor->first_name,
                            'actor_last_name' => $actor->last_name,
                        ];
                        try{
                            $mail = Mail::to($user->email)->send(new JoinConfirmation($email_data));
                        }catch (\Exception $e) {
                            return 0;
                        }
                        $singleMemberNotiData['message'] = $user->first_name." ".$user->last_name." joined ".$Product->first_name." ".$Product->last_name."'s Product.";
                        foreach($member_id as $value){
                            if( $value == $AuthUserId){
                                $seen = 1;
                            }else{
                                $seen = 0;
                            }
                            $singleMemberNotiData['seen'] =  $seen;
                            $singleMemberNotiData['to_user'] = $value;
                            array_push($massData, $singleMemberNotiData);
                            $checkNoti = NotificationSetting::where('user_id', $value)->where('push_for_someone_joins_Product', 1)->count();
                            if($checkNoti != 0){
                                $notification_create = Notification::create([
                                    'user_id' => $value,
                                    'Product_general_id' => $request->get('Product_general_id'),
                                    'title' => 'Notification at '.$Product->first_name." ".$Product->last_name."'s Product.",
                                    'message' => $user->first_name." ".$user->last_name." has just joined ".$Product->first_name." ".$Product->last_name."'s Product.",
                                    'type' => "Member",
                                    'is_seen' => 0
                                ]);
                                $notification =$this->notification_check($notification_create);
                            }
                            $checkEmail = NotificationSetting::where('user_id', $value)->where('email_for_someone_joins_Product', 1)->count();
                            if($checkEmail != 0 ){
                                $member_info = User::where('id', $value)->first();
                                $email = $member_info->email;
                                try{
                                    $mail = Mail::to($email)->send(new SomeoneJoinsProduct($email_data));
                                }catch (\Exception $e) {
                                    return 0;
                                }
                            }
                        }
                        $result = $this->get_Product_result($data['Product_general_id'],  $AuthUserId);
                        LogAppointment::insert($massData); 
                        return response()->json([
                            'result' => $result,
                            'success' => true
                        ],200);
                    }
                
                }else{
                    return response()->json([
                        'success' => false
                        ],200);
                }
            }
            public function get_Product_result($Product_id, $user_id){
                $general = ProductGeneral::where('id', $Product_id)->with('placements')->first();
                        $new = [];
                        $general = $this->age_count($general);
                        $general = $this->notification_checkings($general);
                        $new['Product'] = $general;
                        $pendingRemovals=[] ;
                        $pendingRemovals =Removal::Where('Product_general_id', $Product_id)
                            ->where('to_user', $user_id)
                            ->with('from_user_info')
                            ->get();
                        if(sizeof($pendingRemovals)>0){
                            $index = 0;
                            $newData = new Collection([]);
                            foreach ($pendingRemovals as $value2) {
                                $toDate = Carbon::now('Asia/Dhaka');
                                $fromDate = $value2['created_at'];
                                $duration = $toDate->diff($fromDate);
                                if($duration->d!= 0){
                                    $value2['day'] = 6 - $duration->d;
                                }else{
                                    $value2['day'] = 6;
                                }
                                $time = $duration->h + 6;
                                $value2['hour'] = $time;
                                if($time >= 24){
                                    $value2['hour'] = $time -24;
                                }
                                if($value2['hour']!= 0){
                                    $value2['hour'] = 23 - $value2['hour'];
                                }else{
                                    $value2['hour'] = 23;
                                }
                                $value2['min'] = $duration->i;
                                if($value2['min']!= 0){
                                    $value2['min'] = 59 - $value2['min'];
                                }else{
                                    $value2['min'] = 59;
                                }
                                if($duration->d >=7){
                                    Removal::where('Product_general_id', $value2['Product_general_id'])
                                    ->where('to_user',$value2['to_user'])
                                    ->delete();
                                    ProductMember::where('Product_general_id', $value2['Product_general_id'])
                                                ->where('member_id',$value2['to_user'])
                                                ->update([
                                                    'status' => 'Current'
                                                ]);
                                    unset($pendingRemovals[$index]);
                                }
                                else{
                                    $newData->push($value2);
                                }
                                $index ++;
                            }
                            $pendingRemovals = $newData;
                        } 
                        $new['pendingRemovals'] = $pendingRemovals;

                        $pendingCloses=[];
                        $pendingCloses = Close::where('Product_general_id', $Product_id)
                                ->where('to_user', $user_id)
                                ->where('is_accept', null)
                                ->with('user_info')
                                ->get();
                        
                        if(sizeof($pendingCloses)>0){
                            $index = 0;
                            $newData = new Collection([]);
                            foreach ($pendingCloses as $key => $value3) {
                                $toDate = Carbon::now('Asia/Dhaka');
                                $fromDate =$value3['created_at'];
                                $duration = $toDate->diff($fromDate);
                                if($duration->d!= 0){
                                    $value3['day'] = 6 - $duration->d;
                                }else{
                                    $value3['day'] = 6;
                                }
                                $time = $duration->h + 6;
                                $value3['hour'] = $time;
                                if($time >= 24){
                                    $value3['hour'] = $time -24;
                                }
                                if($value3['hour']!= 0){
                                    $value3['hour'] = 23 - $value3['hour'];
                                }else{
                                    $value3['hour'] = 23;
                                }
                                $value3['min'] = $duration->i;
                                if($value3['min']!= 0){
                                    $value3['min'] = 59 - $value3['min'];
                                }else{
                                    $value3['min'] = 59;
                                }
                                if($duration->d >=7){
                                    Close::where('Product_general_id', $value3['Product_general_id'])
                                    ->delete();
                                    unset($pendingCloses[$index]);
                                }else{
                                    $newData->push($value3);
                                }
                                $index ++;
                            }
                            $pendingCloses = $newData;
                        }
                        $new['pendingCloses'] = $pendingCloses;

                        $withdrawCloseRequests=null ;
                        $withdrawCloseRequests = Close::where('Product_general_id', $Product_id)
                                             ->where('user_id', $user_id)
                                             ->first();
                        if($withdrawCloseRequests){
                            $toDate = Carbon::now('Asia/Dhaka');
                            $fromDate =$withdrawCloseRequests->created_at;
                            $duration = $toDate->diff($fromDate);
                            if($duration->d!= 0){
                                $withdrawCloseRequests['day'] = 6 - $duration->d;
                            }else{
                                $withdrawCloseRequests['day'] = 6;
                            }
                            $time = $duration->h + 6;
                            $withdrawCloseRequests['hour'] = $time;
                            if($time >= 24){
                                $withdrawCloseRequests['hour'] = $time -24;
                            }
                            if($withdrawCloseRequests['hour']!= 0){
                                $withdrawCloseRequests['hour'] = 23 - $withdrawCloseRequests['hour'];
                            }else{
                                $withdrawCloseRequests['hour'] = 23;
                            }
                            $withdrawCloseRequests['min'] = $duration->i;
                            if($withdrawCloseRequests['min']!= 0){
                                $withdrawCloseRequests['min'] = 59 - $withdrawCloseRequests['min'];
                            }else{
                                $withdrawCloseRequests['min'] = 59;
                            }
                            if($duration->d >=7){
                                Close::where('Product_general_id', $withdrawCloseRequests['Product_general_id'])
                                ->delete();
                                $withdrawCloseRequests = [];
                            }
                        }
                        $new['withdrawCloseRequests'] = $withdrawCloseRequests;

                        $newMemberRequest=[] ;
                        $newMemberRequest = JoinRequestsFromUser::Where('Product_general_id', $Product_id)
                                                                ->where('to_user', $user_id)
                                                                ->with('user_info')
                                                                ->get();
                        if(sizeof($newMemberRequest)>0){
                            $index = 0;
                            $newData = new Collection([]);
                            foreach ($newMemberRequest as $value4) {
                                $toDate = Carbon::now('Asia/Dhaka');
                                $fromDate = $value4['created_at'];
                                $duration = $toDate->diff($fromDate);
                                if($duration->d!= 0){
                                    $value4['day'] = 6 - $duration->d;
                                }else{
                                    $value4['day'] = 6;
                                }
                                $time = $duration->h + 6;
                                $value4['hour'] = $time;
                                if($time >= 24){
                                    $value4['hour'] = $time -24;
                                }
                                if($value4['hour']!= 0){
                                    $value4['hour'] = 23 - $value4['hour'];
                                }else{
                                    $value4['hour'] = 23;
                                }
                                $value4['min'] = $duration->i;
                                if($value4['min']!= 0){
                                    $value4['min'] = 59 - $value4['min'];
                                }else{
                                    $value4['min'] = 59;
                                }
                                if($duration->d >=7){
                                    JoinRequestsFromUser::where('Product_general_id', $value4['Product_general_id'])
                                    ->where('to_user',$value4['to_user'])
                                    ->delete();
                                    unset($newMemberRequest[$index]);
                                }
                                else{
                                    $newData->push($value4);
                                }
                                $index++;
                            }
                            $newMemberRequest = $newData;
                        }
                        $new['newMemberRequest'] = $newMemberRequest;
                        return $new;
            }
            public function rejecting_Product_invite(Request $request){
                $noti_member = MemberRequestToUser::where('email',$request->get('email'))->where('Product_general_id',$request->get('Product_general_id'))->pluck('user_id');
                $delete = MemberRequestToUser::where('email',$request->get('email'))
                ->where('Product_general_id',$request->get('Product_general_id'))->delete();
                // $member_id =  ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                //      ->where('member_id', '!=', $AuthUserId)
                //      ->where('status', ["Current","PendingRemoval" ])
                //      ->pluck('member_id');
                if($delete != 0){
                    $user = User::where('email', $request->get('email'))->first();
                    $Product = ProductGeneral::where('id',$request->get('Product_general_id'))->first();
                    $AuthUserId = JWTAuth::parseToken()->authenticate()->id;
                    $data = [
                        'Product_first_name' => $Product->first_name,
                        'Product_last_name' => $Product->last_name,
                        'user_first_name' => $user->first_name,
                        'user_last_name' => $user->last_name,
                    ];
                     
                    foreach($noti_member as $value){
                        $checkEmail = NotificationSetting::where('user_id', $value)->where('email_for_join_request_reject', 1)->count();
                             if($checkEmail != 0 ){
                                $member_info = User::where('id', $value)->first();
                                $email = $member_info->email;
                                try{
                                    $mail = Mail::to($email)->send(new RejectJoinRequest($data));
                                }catch (\Exception $e) {
                                    return 0;
                                }
                            }
                        $checkNoti = NotificationSetting::where('user_id', $value)->where('push_for_join_request_reject', 1)->count();
                        if($checkNoti != 0){
                            $notification_create = Notification::create([
                                'user_id' => $value,
                                'Product_general_id' => $request->get('Product_general_id'),
                                'title' => 'Notification at '.$Product->first_name." ".$Product->last_name."'s Product.",
                                'message' =>$user->first_name." ".$user->last_name." has rejected your request to join ".$Product->first_name." ".$Product->last_name."'s Product.",
                                'type' => "Member",
                                'is_seen' => 0
                            ]);
                            $notification =$this->notification_check($notification_create);
                        }
                    }
                    return response()->json([
                        'success' => true
                    ],200);}
                else{
                    return response()->json([
                        'success' => false
                    ],200);
                }
            }
            public function accept_pending_add(Request $request){
                // join_request_frm_user->delete, Product_member->add
                $data = JoinRequestsFromUser::where('id',$request->get('newMemberRequest_id') )->delete();
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id;
                $authUser = User::where('id',$AuthUserId)->first();
                $member_id =  ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                 ->whereIn('status', ["Current","PendingRemoval" ])
                ->pluck('member_id');
                $Product = ProductGeneral::where('id',$request->get('Product_general_id'))->first();
                $user = User::where('id', $request->get('user_id'))->first();
                $massData = [];
                $singleMemberNotiData = [
                    'Product_general_id' =>$request->get('Product_general_id'),
                    'appointment_id' =>0,
                    'tag' => "Biographical",
                ];
                if($data == 1){
                    $already_Accepted = ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                    ->where('member_id', $request->get('user_id'))
                    ->first();
                    if($already_Accepted){
                        if($already_Accepted->status == "Current"){
                            return response()->json([
                           'msg'      => "Already Accepted",
                           'success'  => false
                           ],200);
                       }else if($already_Accepted->status == "Former"){
                            ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                            ->where('member_id', $request->get('user_id'))->update([
                                'status' => "Current"
                            ]);
                            $conversation = Conversation::where('Product_general_id', $request->get('Product_general_id') )->first();
                            if($conversation){
                                $conversation_member_create = ConversationMember::create([
                                    'conversation_id' => $conversation['id'],
                                    'user_id' => $request->get('user_id')
                                ]);
                            }
                       }
                    }
                    else{
                        $conversation = Conversation::where('Product_general_id', $request->get('Product_general_id') )->first();
                        if($conversation){
                            $conversation_member_create = ConversationMember::create([
                                'conversation_id' => $conversation['id'],
                                'user_id' => $request->get('user_id')
                            ]);
                        }
                        $create_member = ProductMember::firstOrCreate([
                            'Product_general_id' => $request->get('Product_general_id'),
                            'member_id' => $request->get('user_id'),
                            'status' => "Current"
                        ]);
                    }
                    $data = [
                        'Product_first_name' => $Product->first_name,
                        'Product_last_name' => $Product->last_name,
                        'user_first_name' => $user->first_name,
                        'user_last_name' => $user->last_name,
                        'actor_first_name' => $authUser->first_name,
                        'actor_last_name' => $authUser->last_name,
                    ];
                    $checkNoti = NotificationSetting::where('user_id', $request->get('user_id'))->where('push_for_someone_joins_Product', 1)->count();
                    if($checkNoti != 0){
                        $notification_create = Notification::create([
                            'user_id' => $request->get('user_id'),
                            'Product_general_id' => $request->get('Product_general_id'),
                            'title' => 'Notification at '.$Product->first_name." ".$Product->last_name."'s Product.",
                            'message' => $authUser->first_name." ".$authUser->last_name." has permitted you to join ".$Product->first_name." ".$Product->last_name."'s Product.",
                            'type' => "Profile",
                            'is_seen' => 0
                        ]);
                        $notification =$this->notification_check($notification_create);
                    }
                    $email_data = [
                        'Product_first_name' => $Product->first_name,
                        'Product_last_name' => $Product->last_name,
                        'user_first_name' => $user->first_name,
                        'user_last_name' => $user->last_name,
                        'actor_first_name' => $authUser->first_name,
                        'actor_last_name' => $authUser->last_name,
                    ];
                    try{
                        $mail = Mail::to($user->email)->send(new JoinConfirmation($email_data));
                    }catch (\Exception $e) {
                        return 0;
                    }
                    $singleMemberNotiData['message'] = $user->first_name." ".$user->last_name." joined ".$Product->first_name." ".$Product->last_name."'s Product.";
                    foreach($member_id as $value){
                        if( $value == $AuthUserId){
                            $seen = 1;
                        }else{
                            $seen = 0;
                        }
                        $singleMemberNotiData['seen'] =  $seen;
                        $singleMemberNotiData['to_user'] = $value;
                        array_push($massData, $singleMemberNotiData);

                        $checkEmail = NotificationSetting::where('user_id', $value)->where('email_for_someone_joins_Product', 1)->count();
                        if($checkEmail != 0 ){
                            $member_info = User::where('id', $value)->first();
                            $email = $member_info->email;
                            try{
                                $mail = Mail::to($email)->send(new SomeoneJoinsProduct($data));
                            }catch (\Exception $e) {
                                return 0;
                            }
                        }
                    }  
                    LogAppointment::insert($massData);
                        return response()->json([
                         'msg'      => "Accepted",
                        'success' => true
                    ],200);
                }else{
                    return response()->json([
                        'success' => false
                    ],200);
                }
            } 
            public function reject_pending_add(Request $request){
                // join_request_from_user ->delete
                $data = JoinRequestsFromUser::where('id',$request->get('newMemberRequest_id') )->delete();
                if($data == 1){
                    return response()->json([
                         'success' => true
                    ],200);
                }
                else{
                    return response()->json([
                        'success' => false
                   ],200);
                }
                
            }
            public function joining_a_Product(Request $request){
                // user_id, Product_general_id, to_user_id[]
                $to_user = $request->get('to_user_id');
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id;
                $Product = ProductGeneral::where('id',$request->get('Product_general_id'))->first();
                $user = User::where('id', $request->get('user_id'))->first();
                if(sizeof($to_user)>0){
                    foreach($to_user as $value){
                        $already_sent = JoinRequestsFromUser::where('to_user', $value)->where('user_id', $request->get('user_id'))->count();
                        if($already_sent == 1){
                            return response()->json([
                                'msg'      => "Already Sent",
                                'success'  => false
                           ],200);
                        }
                        else{
                            $create_join_request = JoinRequestsFromUser::create([
                                'Product_general_id' => $request->get('Product_general_id'),
                                'user_id' => $request->get('user_id'),
                                'to_user' => $value
                           ]);
                           $data = [
                            'Product_first_name' => $Product->first_name,
                            'Product_last_name' => $Product->last_name,
                            'user_first_name' => $user->first_name,
                            'user_last_name' => $user->last_name,
                        ];
                         foreach($to_user as $value){
                            $checkNoti = NotificationSetting::where('user_id', $value)->where('push_for_someone_request_to_join_Product', 1)->count();
                            if($checkNoti != 0){
                                $notification_create = Notification::create([
                                    'user_id' => $value,
                                    'Product_general_id' => $request->get('Product_general_id'),
                                    'title' => 'Notification at '.$Product->first_name." ".$Product->last_name."'s Product.",
                                    'message' => $user->first_name." ".$user->last_name."  has requested to join ".$Product->first_name." ".$Product->last_name. "'s Product.",
                                    'type' => "Member",
                                    'is_seen' => 0
                                ]);
                                $notification = $this->notification_check($notification_create);
                            }
                            $checkEmail = NotificationSetting::where('user_id', $value)->where('email_for_someone_request_to_join_Product', 1)->count();
                             if($checkEmail != 0 ){
                                $member_info = User::where('id', $value)->first();
                                $email = $member_info->email;
                                try{
                                    $mail = Mail::to($email)->send(new SomeoneRequestToJoinProduct($data));
                                }catch (\Exception $e) {
                                    return 0;
                                }
                                
                            }
                        }
                           return response()->json([
                            'success'  => true
                          ],200);
                        }
                    }        
                }        
            }
            public function remove_from_Product(Request $request){ 
                //   create remove  , status change for user 
                $Product = ProductGeneral::where('id',$request->get('Product_general_id'))->first();
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id;
                $member_info = User::where('id', $request->get('user_id'))->first();
                $user_info = User::where('id', $request->get('member_id'))->first();
                $member_id =  ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                ->whereIn('status', ["Current","PendingRemoval" ])
                ->pluck('member_id');
                // $massData = [];
                // $singleMemberNotiData = [
                //     'Product_general_id' =>$request->get('Product_general_id'),
                //     'appointment_id' =>0,
                //     'tag' => "Biographical",
                // ]; 
                $data = Removal::firstOrCreate([
                    'Product_general_id' => $request->get('Product_general_id'),
                    'user_id' => $request->get('user_id'),
                    'to_user' => $request->get('member_id'),
                ]);
                if($data){
                    $update = ProductMember::where('id', $request->get('id'))->update([
                        'status' =>'PendingRemoval'
                    ]);
                    $checkNoti = NotificationSetting::where('user_id', $request->get('member_id'))->where('push_for_someone_request_to_join_Product', 1)->count();
                    if($checkNoti != 0){
                        $notification_create = Notification::create([
                            'user_id' => $request->get('member_id'),
                            'Product_general_id' => $request->get('Product_general_id'),
                            'title' => 'Notification at '.$Product->first_name." ".$Product->last_name."'s Product.",
                            'message' => $member_info->first_name." ".$member_info->last_name." has suggested that you be removed from ".$Product->first_name." ".$Product->last_name. "'s Product.",
                            'type' => "Member",
                            'is_seen' => 0
                        ]);
                        $notification = $this->notification_check($notification_create);
                    }
                    $checkEmail = NotificationSetting::where('user_id', $request->get('member_id'))->where('email_for_removed_from_Product', 1)->count();
                    if($checkEmail != 0 ){
                        $email = $user_info->email;
                        $date = strtotime($data->created_at);
                        $date = strtotime("+7 day", $date);
                        $date = date('M d, Y', $date);
                        $email_data = [
                            'Product_first_name' => $Product->first_name,
                            'Product_last_name' => $Product->last_name,
                            'user_first_name' => $member_info->first_name,
                            'user_last_name' => $member_info->last_name,
                            'date' => $date 
                        ];
                        try{
                            $mail = Mail::to($email)->send(new MemberRemovalCreate($email_data)); 
                        }catch (\Exception $e) {
                            return 0;
                        }
                    }
                    
                }
                if($update == 0){
                    return response()->json([
                         'msg' => "Something went wrong. ",
                         'success' => false
                    ],400);
                }
                else{
                    return response()->json([
                         'success' => true
                    ],200);
                }
            }
            public function cancel_removal(Request $request){
                // removal cancel, status change 
                $data = Removal::where('Product_general_id', $request->get('Product_general_id'))->where('to_user', $request->get('member_id'))->delete();
                if($data == 1){
                   $statusUpdate = ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                                ->where('member_id', $request->get('member_id'))
                                ->update([
                                    'status' => "Current"
                                ]);
                    if($statusUpdate == 0){
                         return response()->json([
                             'msg' => "Something went wrong . ",
                             'success' =>  false
                        ],400);
                    }
                    else{
                        return response()->json([
                            'success' => true
                       ],200);
                    }
                }
                else{
                    return response()->json([
                        'msg' => "Something went wrong . ",
                        'success' => false
                   ],400);
                } 
               
            }
            public function accept_removal(Request $request){ 
                $initiate =   Removal::where('id', $request->get('id'))->first();         $initiate_from = User::where('id', $initiate->user_id)->first();
                $data = Removal::where('id', $request->get('id'))->delete();
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id;
                $member_id =  ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                ->whereIn('status', ["Current","PendingRemoval" ])
                ->pluck('member_id');
                $massData = [];
                $singleMemberNotiData = [
                    'Product_general_id' =>$request->get('Product_general_id'),
                    'appointment_id' =>0,
                    'tag' => "Biographical",
                ]; 
                $Product = ProductGeneral::where('id',$request->get('Product_general_id'))->first();
                $user = User::where('id', $request->get('member_id'))->first();
                $email_data = [
                    'Product_first_name' => $Product->first_name,
                    'Product_last_name' => $Product->last_name,
                    'user_first_name' => $initiate_from->first_name,
                    'user_last_name' => $initiate_from->last_name,
                    'date' => $initiate->updated_at
                ];
                if($data != 0){
                    $statusUpdate = ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                                ->where('member_id', $request->get('member_id'))
                                ->update([
                                    'status' => "Former"
                                ]);
                    $conversation = Conversation::where('Product_general_id', $request->get('Product_general_id') )
                    ->first();
                    if($conversation){
                        $conversation_member_delete = ConversationMember::where('conversation_id', $conversation['id'])
                        ->where('user_id', $request->get('member_id'))
                        ->delete();
                    }
                    if($statusUpdate != 0){
                        $closed = Close::where('Product_general_id', $request->get('Product_general_id'))
                        ->where('to_user', $request->get('member_id'))
                        ->delete();
                        $checkNoti = NotificationSetting::where('user_id', $user->id)->where('push_for_removed_from_Product', 1)->count();
                        if($checkNoti != 0){
                            $notification_create = Notification::create([
                                'user_id' => $user->id,
                                'Product_general_id' => $request->get('Product_general_id'),
                                'title' => 'Notification at '.$Product->first_name." ".$Product->last_name."'s Product.",
                                'message' => "You were successfully removed from ".$Product->first_name." ".$Product->last_name."' Product.",
                                'type' => "Member",
                                'is_seen' => 0
                            ]);
                            $notification = $this->notification_check($notification_create);
                        }
                        $checkEmail = NotificationSetting::where('user_id', $initiate->to_user)->where('email_for_removed_from_Product', 1)->count();
                        if($checkEmail != 0 ){
                                $member_info = User::where('id', $initiate->to_user)->first();
                                $email = $member_info->email;
                                try{
                                    $mail = Mail::to($email)->send(new MemberRemoved($email_data)); 
                                }catch (\Exception $e) {
                                    return 0;
                                }
                            }
                        $singleMemberNotiData['message'] = $user->first_name." ".$user->last_name." left ".$Product->first_name." ".$Product->last_name. "'s Product.";
                        foreach($member_id as $value){
                                if( $value == $AuthUserId){
                                    $seen = 1;
                                }else{
                                    $seen = 0;
                                }
                                $singleMemberNotiData['seen'] =  $seen;
                                $singleMemberNotiData['to_user'] = $value;
                                array_push($massData, $singleMemberNotiData);
                            }
                            LogAppointment::insert($massData);
                         return response()->json([
                             'success' => true
                        ],200);
                        
                    }
                    else{
                        return response()->json([
                             'success' => false
                       ],200);
                    }
                }
                else{
                    return response()->json([
                        'success' => false
                   ],200);
                } 
            }
            public function reject_removal(Request $request){
                // removal delete, member->status->current. notification
                // “[user name] has rejected your removal suggestion.” 
                $removal = Removal::where('id', $request->get('id'))->first();
                $data = Removal::where('id', $request->get('id'))->delete();
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id;
                $member_id =  ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                ->where('member_id', '!=', $AuthUserId)
                ->where('member_id', '!=', $request->get('member_id'))
                ->whereIn('status', ["Current", "PendingRemoval"]) 
                ->pluck('member_id');
                $massData = [];
                $singleMemberNotiData = [
                    'Product_general_id' =>$request->get('Product_general_id'),
                    'appointment_id' =>0,
                    'tag' => "Biographical",
                ]; 
                $Product = ProductGeneral::where('id',$request->get('Product_general_id'))->first();
                $user = User::where('id', $request->get('member_id'))->first();
                if($data == 1){
                   $statusUpdate = ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                                ->where('member_id', $request->get('member_id'))
                                ->update([
                                    'status' => "Current"
                                ]);
                    if($statusUpdate > 0){
                        $email_data = [
                            'Product_first_name' => $Product->first_name,
                            'Product_last_name' => $Product->last_name,
                            'user_first_name' => $user->first_name,
                            'user_last_name' => $user->last_name,
                        ];
                    LogAppointment::insert($massData);
                        foreach($member_id as $value){
                            $checkNoti = NotificationSetting::where('user_id', $value)->where('push_for_someone_reject_removal', 1)->count();
                            if($checkNoti != 0){
                                $notification_create = Notification::create([
                                    'user_id' => $value,
                                    'Product_general_id' => $request->get('Product_general_id'),
                                    'title' => 'Notification at '.$Product->first_name." ".$Product->last_name."'s Product.",
                                    'message' => $user->first_name." ".$user->last_name." has rejected removal from ".$Product->first_name." ".$Product->last_name."'s Product.",
                                    'type' => "Profile",
                                    'is_seen' => 0
                                ]);
                                $notification =$this->notification_check($notification_create);
                            }
                        }
                        $checkEmail = NotificationSetting::where('user_id', $removal->user_id)->where('email_for_someone_reject_removal', 1)->count();
                        if($checkEmail != 0 ){
                            $member_info = User::where('id', $removal->user_id)->first();
                            $email = $member_info->email;
                            try{
                                $mail = Mail::to($email)->send(new RejectRemoval($email_data));
                            }catch (\Exception $e) {
                                return 0;
                            }
                        }
                         return response()->json([
                             'success' => true
                        ],200);
                    }
                    else{
                        return response()->json([
                            'success' => false
                       ],200);
                    }
                }
                else{
                    return response()->json([
                        'success' => false
                   ],200);
                } 
            }
            public function close_Product(Request $request){
                //   create close  for every member
                // to_user,  'Product_general_id',	'user_id','is_accept'
                if(!$request->get('user_id') && !$request->get('Product_general_id')){
                    return response()->json([
                        'msg' => "Something went wrong .",
                        'success'  => false
                        ],400);
                }
                $created_close = Close::where('Product_general_id', $request->get('Product_general_id'))
                                        ->count();
                if($created_close >= 1){
                    return response()->json([
                        'msg'      => "Already Created",
                        'success'  => true
                   ],200);
                }
                else{
                    $to_user = ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                                ->where('member_id', '!=', $request->get('user_id'))
                                ->where('status', '!=', 'Former')
                                ->pluck('member_id');
                    foreach($to_user as $value){
                            $create_close = Close::create([
                                'Product_general_id' => $request->get('Product_general_id'),
                                'user_id' => $request->get('user_id'),
                                'to_user' => $value,
                                'is_accept' => null
                        ]);
                        }
                    return response()->json([
                    'success'  => true
                    ],200);
                }
                
                
            }
            public function leave_Product(Request $request){
                 // member->former,
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id;
                $member_id =  ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                ->whereIn('status', ["Current","PendingRemoval" ])
                ->pluck('member_id');
                $massData = [];
                $singleMemberNotiData = [
                    'Product_general_id' =>$request->get('Product_general_id'),
                    'appointment_id' =>0,
                    'tag' => "Biographical",
                ]; 
                $Product = ProductGeneral::where('id',$request->get('Product_general_id'))->first();
                $user = User::where('id', $request->get('user_id'))->first();
                 if(!$request->get('user_id') && !$request->get('Product_general_id')){
                    return response()->json([
                        'msg' => "Something went wrong .",
                        'success'  => false
                        ],400);
                }
                 $statusUpdate = ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                 ->where('member_id', $request->get('user_id'))
                 ->update([
                     'status' => "Former"
                 ]);
                 $conversation = Conversation::where('Product_general_id', $request->get('Product_general_id') )->first();
                    if($conversation){
                        $conversation_member_delete = ConversationMember::where('conversation_id', $conversation['id'])->where('user_id', $request->get('user_id'))->delete();
                    }
                if($statusUpdate == 1 ){
                    // $data = [
                    //     'Product_first_name' => $Product->first_name,
                    //     'Product_last_name' => $Product->last_name,
                    //     'user_first_name' => $user->first_name,
                    //     'user_last_name' => $user->last_name,
                    // ];
                    $checkNoti = NotificationSetting::where('user_id', $user->id)->where('push_for_removed_from_Product', 1)->count();
                    if($checkNoti != 0){
                        $notification_create = Notification::create([
                            'user_id' => $user->id,
                            'Product_general_id' => $request->get('Product_general_id'),
                            'title' => 'Notification at '.$Product->first_name." ".$Product->last_name."'s Product.",
                            'message' => "You were successfully removed from ".$Product->first_name." ".$Product->last_name."' Product.",
                            'type' => "Member",
                            'is_seen' => 0
                        ]); 
                            $notification =$this->notification_check($notification_create);
                    }
                    $singleMemberNotiData['message'] = $user->first_name." ".$user->last_name." left ".$Product->first_name." ".$Product->last_name. "'s Product.";
                        foreach($member_id as $value){
                            if( $value == $AuthUserId){
                                $seen = 1;
                            }else{
                                $seen = 0;
                            }
                            $singleMemberNotiData['seen'] =  $seen;
                            $singleMemberNotiData['to_user'] = $value;
                            array_push($massData, $singleMemberNotiData);
                        }
                        LogAppointment::insert($massData);
                    // foreach($member_id as $value){
                        
                        // $checkEmail = NotificationSetting::where('user_id', $value)->where('email_for_removed_from_Product', 1)->count();
                        //      if($checkEmail != 0 ){
                        //         $member_info = User::where('id', $value)->first();
                        //         $email = $member_info->email;
                        //         $mail = Mail::to($email)->send(new MemberRemoved($data)); 
                        //     }
                  
                        // }
                    return response()->json([
                        'success' => true
                    ],200);
                }
                else{
                    return response()->json([
                        'msg' => "Something went wrong .",
                        'success'  => false
                        ],400);
                }
            }
            public function accept_close(Request $request){
                // if everyone accept close than close this Product and delete all closes , Product member status=>former, 
                $data = Close::where('to_user', $request->get('to_user'))
                                ->where('Product_general_id', $request->get('Product_general_id'))
                                ->update([
                                    'is_accept' => 1,
                                ]);
                $count_close = Close::where('Product_general_id', $request->get('Product_general_id'))->count();
                $count_accept = Close::where('Product_general_id', $request->get('Product_general_id'))
                                        ->where('is_accept', 1)
                                        ->count();
                $count_response = Close::where('Product_general_id', $request->get('Product_general_id'))
                                ->where('is_accept', 1)
                                ->orWhere('is_accept', 0)
                                ->count();
                
                if($data == 1){
                    if($count_close == $count_accept){
                        $delete = Close::where('Product_general_id', $request->get('Product_general_id'))->delete();
                        $update = ProductMember::where('Product_general_id', $request->get('Product_general_id'))->update([
                            'status'=> "Former"
                        ]);
                        $conversation = Conversation::where('Product_general_id', $request->get('Product_general_id') )->first();
                        if($conversation){
                            $conversation_member_delete = ConversationMember::where('conversation_id', $conversation['id'])
                                                        ->delete();
                        }
                        return response()->json([
                            'success' => true
                           ],200);
                   }
                   else if($count_close == $count_response){
                       $delete = Close::where('Product_general_id', $request->get('Product_general_id'))->delete();
                   }
                   else{
                       return response()->json([
                        'success' => true
                       ],200);
                   }
                }
                else{
                    return response()->json([
                        'success' => false
                       ],200);
                }
            }
            public function reject_close(Request $request){ 
                // close rejected for this member and null count than close delete
                $data = Close::where('to_user', $request->get('to_user'))
                        ->where('Product_general_id', $request->get('Product_general_id'))
                        ->update([
                            'is_accept' => 0,
                        ]);
                $count_close = Close::where('Product_general_id', $request->get('Product_general_id'))->count();
                $count_response = Close::where('Product_general_id', $request->get('Product_general_id'))
                                ->where('is_accept', 1)
                                ->orWhere('is_accept', 0)
                                ->count();
                if($count_close == $count_response){
                    $delete = Close::where('Product_general_id', $request->get('Product_general_id'))->delete();
                }
                if($data == 1){
                    return response()->json([
                        'success' => true
                    ],200);
                }else{
                    return response()->json([
                        'success' => false
                    ],200);
                }
                
            }
            public function cancel_close(Request $request){
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
               $cancel = Close::where('Product_general_id', $request->get('Product_general_id'))->where('user_id', $user_id)->delete();
               if($cancel){
                    return response()->json([
                        'success' => true
                    ],200);
                }else{
                    return response()->json([
                        'success' => false
                    ],400);
                }      
            }
            public function notification_check($data){
                // $user = JWTAuth::parseToken()->authenticate();
                // $user_id = $user->id;
                $ids[0] = User::where('id',$data['user_id'])->value('device_id');
                $notification = $data;
                $url = 'https://fcm.googleapis.com/fcm/send';
                $fields = array (
                        'registration_ids' => $ids,
                        'data' => array (
                                'title' => $data['title'],
                                "message" =>  $data['Product_general_id'].' ,'.$data['type'],
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ),
                        'notification' => array (
                                'title' => $data['title'],
                                "body" => $data['message'],                 
                                "sound" => true, 
                                "badge" => 1,
                        ),
                        'time_to_live' => 6000,
                );
                $fields = json_encode ( $fields );
                $headers = array (
                        'Authorization: key=' . "AAAABj6MfPU:APA91bGcUQoIeAMfUfrb7dka-Uk2KFLjTCg3Vbyeg-dB0iUq5oowssu-VgBLIFEcZkVmtpAC4drKpxMdkbXAtdwEh9-uvq-GEBEFj7f4D5G4UofjhwoMF41eQg-c9ib2fVxxw1700SYH",
                        'Content-Type: application/json'
                );
                $ch = curl_init ();
                curl_setopt ( $ch, CURLOPT_URL, $url );
                curl_setopt ( $ch, CURLOPT_POST, true );
                curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
                curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
                curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
                $result = curl_exec ( $ch );
                curl_close ( $ch );
                if($result){
                    return $result;
                }
               
            }
            public function check_Product_general(Request $request){
                $firstName=  $request->get('first_name');
                $lastName=  $request->get('last_name');
                $dateOfBirth=  $request->get('birthday');
                $user_id=  $request->get('user_id');
                $query =  ProductGeneral::where('id', '!=', 0); 
                if($dateOfBirth){
                    $query->where('birthday', $dateOfBirth);
                }
                if($firstName){
                    $query->where(function ($query) use ($firstName){
                        $query->orWhere('first_name',  'like', "%$firstName%");
                    });
                }
                if($lastName){
                    $query->where(function ($query) use ($lastName){
                        $query->orWhere('last_name',  'like', "%$lastName%");
                    });
                }
                $data = $query->with('member_list')->get();
                foreach($data as $value){
                    foreach ($value['member_list'] as $value2){
                        if($value2['member_id'] == $user_id ){
                            $value['is_member'] = "yes";
                        }
                    }
                    if($value['is_member'] != "yes"){
                        $value['is_member'] = "no";

                    }
                    unset($value['member_list']);

                }
                return response()->json([
                    'data' => $data,
                    'success' => true
                ],200);
            }
            public function member_of_Product(Request $request){
                $member_id =  ProductMember::where('Product_general_id', $request->id)
                ->whereIn('status', ["Current", "PendingRemoval"])
                ->pluck('member_id');
                if(sizeof($member_id)>0){
                     $data = User::WhereIn('id', $member_id)->get();
                    if(sizeof($data)>0){
                        foreach($data as $value){
                            $value['checked'] = false;
                        }
                    }
                    return response()->json([
                        'data' => $data,
                        'success' => true
                    ],200);
                }else{
                    return response()->json([
                        'data' => [],
                        'success' => false
                    ],200);
                }                 
            }
            public function member_list_for_placement_people(Request $request){
                $placement_id = PlacementPeople::where('Product_general_id', $request->id)
                ->pluck('user_id');
                // return $placement_id;
                $member_id =  ProductMember::where('Product_general_id', $request->id)
                ->where('status','!=', "Former")
                // ->orWhere('status', "PendingRemoval")
                ->whereNotIn('member_id',$placement_id )
                ->pluck('member_id');
                // return $member_id;
                $data = User::WhereIn('id', $member_id)->get();
                if(sizeof($member_id)>0){
                    return response()->json([
                        'data' => $data,
                        'success' => true
                    ],200);
                }else{
                    return response()->json([
                        'data' => [],
                        'success' => false
                    ],200);
                }   
            }
            public function search_by_Product_name(Request $request){
                $ProductName=  $request->Product_name;
                if($ProductName){
                    $query = ProductGeneral::where(function ($query) use ($ProductName){
                        $query->orWhere('first_name',  'like', "%$ProductName%")
                            ->orWhere('last_name',  'like', "%$ProductName%");
                    });
                }
                $data = $query->get();
                return response()->json([
                    'data' => $data,
                    'success' => true
                ],200);
            }
            public function get_biographical_details(Request $request){
                $data = $request->all();
                $newData = new Collection([]);
                $log_tags = new Collection([]);
                $log_documents = new Collection([]);
                $user = $data['user_id'];
                $notification_seen = LogHistory::where('Product_general_id', $data['Product_general_id'])
                                        ->where('to_user', $data['user_id'])
                                        ->where('tag', "Biographical")
                                        ->update([
                                            'seen' => 1
                                        ]);
                $ProductBiographical = ProductGeneral::where('id', $data['Product_general_id'])
                ->with('Product_documents', 'placements', 'people_of_placements', 'people_of_placements.people_info','activities', 'language','races','siblings','Categorys')
                // ->with(['people_of_placements' => function ($q)  use ($user) {
                //             $q->where('user_id','!=', 0);
                //         }])
                ->first();
                $Log_history = LogHistory::where('Product_general_id',  $data['Product_general_id'])
                                    ->where('to_user',  $data['user_id'])
                                    ->where('tag', "Biographical")
                                    ->with('user_info', 'log_documents')
                                    ->limit(3)
                                    ->orderBy('id', 'desc')
                                    ->get();
                
                $log_post = Post::with('log_tags','log_documents')
                ->whereHas('log_tags', function ($query){
                    $query->where('tag',"Biographical");
                 })->limit(1)->get();
                if(sizeof($log_post)>0){
                    foreach($log_post  as $value){
                        $value['LogType'] = "Log";
                        $newData->push($value);
                    }
                }

                if(sizeof($Log_history)>0){
                    foreach($Log_history  as $value){
                        $value['LogType'] = "Update";
                        $newData->push($value);
                    }
                }
                return response()->json([
                    'ProductBiographical' => $ProductBiographical,
                    'Logs' => $newData,
                     'success' => true
                ],200);
            }  
            public function get_medical_details(Request $request){
                $data = $request->all();
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
                $newData = new Collection([]);
                $notification_seen = LogHistory::where('Product_general_id', $data['Product_general_id'])
                                        ->where('to_user', $user_id)
                                        ->where('tag', "Medicin")
                                        ->update([
                                            'seen' => 1
                                        ]);
                $doctor = Doctor::where([['Product_general_id',  $data['Product_general_id']], ['doctor_type', "Medical"]])->get();
                $Medication = Medication::where('Product_general_id',  $data['Product_general_id'])->with('medicin_taking_time')->get();
                foreach($Medication as $value){
                    foreach($value['medicin_taking_time'] as $value2){
                        $value2['time']  = date("g:iA", strtotime($value2['time']));
                    }
                }
                $Featured = Featured::where('Product_general_id',  $data['Product_general_id'])->get();
                $Allergy = Allergy::where('Product_general_id',  $data['Product_general_id'])->get();
                $PhysicalCondition = PhysicalCondition::where([['Product_general_id',  $data['Product_general_id']], ['type',"Medical"]])->get();
                $insurance =  Insurance::where('Product_general_id', $data['Product_general_id'])
                                ->with('front_photo', 'back_photo')
                                ->get();
                $Log_history = LogHistory::where('Product_general_id',  $data['Product_general_id'])
                                    ->where('to_user',  $user_id)
                                    ->where('tag', "Medical")
                                    ->with('user_info', 'log_documents')
                                    ->limit(3)
                                    ->orderBy('id', 'desc')
                                    ->get();
                if(sizeof($Log_history)>0){
                    foreach($Log_history  as $value){
                        $value['LogType'] = "Update";
                        $newData->push($value);
                    }
                }
                $log_post = Post::with('log_tags','log_documents')
                ->whereHas('log_tags', function ($query){
                    $query->where('tag',"Medical");
                 })->limit(1)->get();
                if(sizeof($log_post)>0){
                    foreach($log_post  as $value){
                        $value['LogType'] = "Log";
                        $newData->push($value);
                    }
                }
                return response()->json([
                    'doctor' => $doctor,
                    'Medication' => $Medication,
                    'Allergy' => $Allergy,
                    'Featured' => $Featured,
                    'insurance' => $insurance,
                    'PhysicalCondition' => $PhysicalCondition,
                    'Logs' => $newData,
                    'success' => true
                ],200);
            }
            public function get_dental_details(Request $request){
                // doctor,physical conditions, logs
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
                $newData = new Collection([]);
                $notification_seen = LogHistory::where('Product_general_id', $request->id)
                                        ->where('to_user', $user_id)
                                        ->where('tag', "Dental")
                                        ->update([
                                            'seen' => 1
                                        ]);
                $doctor = Doctor::where([['Product_general_id',  $request->id], ['doctor_type', "Dentist"]])->get();
                $PhysicalCondition = PhysicalCondition::where([['Product_general_id',  $request->id], ['type',"Dental"]])->get();
                $Log_history = LogHistory::where('Product_general_id',  $request->id)
                                    ->where('to_user',  $user_id)
                                    ->where('tag', "Dental")
                                    ->with('user_info','log_documents')
                                    ->limit(3)
                                    ->orderBy('id', 'desc')
                                    ->get();
                // \Log::info($Log_history->toArray());
                if(sizeof($Log_history)>0){
                    foreach($Log_history  as $value){
                        $value['LogType'] = "Update";
                        // if($value['user_id'] == $user_id )
                        // $value['log_tags'] = $log_tags;
                        // $value['log_documents'] = $log_documents;
                        // unset($value['user_info']);
                        $newData->push($value);
                    }
                }
                $log_post = Post::with('log_tags', 'log_documents')
                ->whereHas('log_tags', function ($query){
                    $query->where('tag',"Dental");
                 })->limit(1)->get();
                if(sizeof($log_post)>0){
                    foreach($log_post  as $value){
                        $value['LogType'] = "Log";
                        $newData->push($value);
                    }
                }
                return response()->json([
                    'doctor' => $doctor,
                    'PhysicalCondition' => $PhysicalCondition,
                    'Logs' => $newData,
                    'success' => true
                ],200);

            }
            public function get_therapy_details(Request $request){
                //doctors,logs
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
                $newData = new Collection([]);
                $notification_seen = LogHistory::where('Product_general_id', $request->id)
                                        ->where('to_user', $user_id)
                                        ->where('tag', "Therapy")
                                        ->update([
                                            'seen' => 1
                                        ]);
                $doctor = Doctor::where([['Product_general_id',  $request->id], ['doctor_type', "Therapiest"]])->get();
                $Log_history = LogHistory::where('Product_general_id',  $request->id)
                                    ->where('to_user',  $user_id)
                                    ->where('tag', "Therapy")
                                    ->with('user_info','log_documents')
                                    ->limit(3)
                                    ->orderBy('id', 'desc')
                                    ->get();
                if(sizeof($Log_history)>0){
                    foreach($Log_history  as $value){
                        $value['LogType'] = "Update";
                        // if($value['user_id'] == $user_id )
                        // $value['log_tags'] = $log_tags;
                        // $value['log_documents'] = $log_documents;
                        // unset($value['user_info']);
                        $newData->push($value);
                    }
                }
                $log_post = Post::with('log_tags','log_documents')
                ->whereHas('log_tags', function ($query){
                    $query->where('tag',"Therapy");
                 })->limit(1)->get();
                if(sizeof($log_post)>0){
                    foreach($log_post  as $value){
                        $value['LogType'] = "Log";
                        $newData->push($value);
                    }
                }
                return response()->json([
                    'doctor' => $doctor,
                    'Logs' => $newData,
                    'success' => true
                ],200); 
            }
            public function get_education_details(Request $request){
                //schools,logs
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
                $newData = new Collection([]);
                $notification_seen = LogHistory::where('Product_general_id', $request->id)
                                        ->where('to_user', $user_id)
                                        ->where('tag', "Education")
                                        ->update([
                                            'seen' => 1
                                        ]);
                $education = Education::where('Product_general_id', $request->id)->get();
                $Log_history = LogHistory::where('Product_general_id',  $request->id)
                                    ->where('to_user',  $user_id)
                                    ->where('tag', "Education")
                                    ->with('user_info','log_documents')
                                    ->limit(3)
                                    ->orderBy('id', 'desc')
                                    ->get();
                if(sizeof($Log_history)>0){
                    foreach($Log_history  as $value){
                        $value['LogType'] = "Update";
                        // if($value['user_id'] == $user_id )
                        // $value['log_tags'] = $log_tags;
                        // $value['log_documents'] = $log_documents;
                        // unset($value['user_info']);
                        $newData->push($value);
                    }
                }
                $log_post = Post::with('log_tags','log_documents')
                ->whereHas('log_tags', function ($query){
                    $query->where('tag',"Education");
                 })->limit(1)->get();
                if(sizeof($log_post)>0){
                    foreach($log_post  as $value){
                        $value['LogType'] = "Log";
                        $newData->push($value);
                    }
                }
                return response()->json([
                    'education' => $education,
                    'Logs' => $newData,
                    'success' => true
                ],200); 
            }
            public function get_legal_details(Request $request){
                $data = $request->all();
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
                $newData = new Collection([]);
                $notification_seen = LogHistory::where('Product_general_id', $data['Product_general_id'])
                                        ->where('to_user', $user_id)
                                        ->where('tag', "Legal")
                                        ->update([
                                            'seen' => 1
                                        ]);
                $data = $request->all();
                $Product_legal_details = LegalInformation::where('Product_general_id', $data['Product_general_id'])->first();
                $Log_history = LogHistory::where('Product_general_id',  $data['Product_general_id'])
                                    ->where('to_user',  $user_id)
                                    ->where('tag', "Legal")
                                    ->with('user_info','log_documents')
                                    ->limit(3)
                                    ->orderBy('id', 'desc')
                                    ->get();
                if(sizeof($Log_history)>0){
                    foreach($Log_history  as $value){
                        $value['LogType'] = "Update";
                        // if($value['user_id'] == $user_id )
                        // $value['log_tags'] = $log_tags;
                        // $value['log_documents'] = $log_documents;
                        // unset($value['user_info']);
                        $newData->push($value);
                    }
                }
                $log_post = Post::with('log_tags','log_documents')
                ->whereHas('log_tags', function ($query){
                    $query->where('tag',"Legal");
                 })->limit(1)->get();
                if(sizeof($log_post)>0){
                    foreach($log_post  as $value){
                        $value['LogType'] = "Log";
                        $newData->push($value);
                    }
                }
                if($Product_legal_details){
                    return response()->json([
                        'Product_legal_details' => $Product_legal_details,
                        'Logs' => $newData,
                        'success' => true
                    ],200); 
                }else{
                    return response()->json([
                        'msg' => "Something went wrong",
                        'success' => false
                    ],400); 
                }
                
            }
            public function get_all_Logs(Request $request){
                $data = $request->all();
                $user = $data['user_id'];
                $newData = new Collection([]);
                $log_tags = new Collection([]);
                $log_documents = new Collection([]);
                $Log_appointment = LogAppointment::where('Product_general_id',  $data['Product_general_id'])
                    ->where('to_user',  $data['user_id'])
                    ->with('user_info')
                    ->limit(10)
                    ->orderBy('id', 'desc')
                    ->get();
                    if(sizeof($Log_appointment)>0){
                    foreach($Log_appointment  as $value){
                        $value['LogType'] = "Event";
                        $value['log_tags'] = $log_tags;
                        $value['log_documents'] = $log_documents;
                        $newData->push($value);
                    }
                }
                $Log_history = LogHistory::where('Product_general_id',  $data['Product_general_id'])
                                ->where('to_user', $user)
                                ->with('user_info', 'log_documents')
                                ->limit(10)
                                ->orderBy('id', 'desc')
                                ->get();
                     if(sizeof($Log_history)>0){
                    foreach($Log_history  as $value){
                        $value['LogType'] = "Update";
                        if($value['user_id'] == $data['user_id'] )
                        $value['log_tags'] = $log_tags;
                        // $value['log_documents'] = $log_documents;
                        // unset($value['user_info']);
                        $newData->push($value);
                    }
                }
                $Log_posts = Post::where('Product_general_id',  $data['Product_general_id'])
                                ->with('log_documents', 'log_tags', 'log_to_users', 'log_to_users.user_info')
                                ->whereHas('log_to_users', function ($query) use ($user){
                                    $query->where('to_user',  $user);
                                 })
                                // ->with(['log_to_users' => function ($q)  use ($user) {
                                //             $q->where('to_user',$user);
                                //         }])
                                ->limit(10)
                                ->orderBy('id', 'desc')
                                ->get();
                if(sizeof($Log_posts)>0){
                    foreach($Log_posts  as $value){
                        $value['LogType'] = "Log";
                        if($value['log_to_users']){
                            $value['seen'] = $value['log_to_users'][0]['seen'];
                            $value['user_info'] = $value['log_to_users'][0]['user_info'];
                        } 
                        unset($value['log_to_users']);
                        $newData->push($value);
                    }
                }
                return response()->json([
                    'Log' => $newData,
                    'success' => true
                ],200); 
            }
            public function get_all_members(Request $request){
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
                $notification_seen = Notification::where('Product_general_id', $request->id)
                                        ->where('user_id', $user_id)
                                        ->where('type', "Member")
                                        ->update([
                                            'is_seen' => 1
                                        ]);
                $newData = new Collection([]);
                $Current = ProductMember::where('Product_general_id', $request->id)
                                        ->where('status',"Current")
                                        ->with('user_info')
                                        ->get();
                $user = JWTAuth::parseToken()->authenticate();
                $PendingAddList= JoinRequestsFromUser::where('Product_general_id', $request->id)
                                     ->where('to_user',$user->id)
                                     ->with('user_info')
                                      ->get();
                $PendingInviteList= MemberRequestToUser::where('Product_general_id', $request->id)
                                    //  ->where('to_user',$user->id)
                                     ->with('user_info')
                                      ->get();
                $PendingRemovalList= ProductMember::where('Product_general_id', $request->id)
                                      ->where('status',"PendingRemoval")          
                                      ->with('user_info')
                                      ->get();
                    
                if(sizeof($PendingInviteList)>0){
                    foreach($PendingInviteList as  $value){
                        $value['status'] = "PendingInvite";
                        $newData->push($value);
                    }
                }
                if(sizeof($PendingAddList)>0){
                    foreach($PendingAddList as  $value){
                        $value['status'] = "PendingAdd";
                        $newData->push($value);
                    }
                }
                if(sizeof($PendingRemovalList)>0){
                    foreach($PendingRemovalList as  $value){
                        $toDate = Carbon::now('Asia/Dhaka');
                        $fromDate = $value['created_at'];
                         
                        $duration = $toDate->diff($fromDate);
                         if($duration->d >=7){
                             Removal::where('Product_general_id', $value['Product_general_id'])
                            ->where('to_user',$value['member_id'])
                            ->delete();
                            ProductMember::where('Product_general_id', $value['Product_general_id'])
                                        ->where('member_id',$value['member_id'])
                                        ->delete();
                        }else{
                        $newData->push($value);
                        }
                    }
                } 
                 
                $Former = ProductMember::where('Product_general_id', $request->id)
                                     ->where('status',"Former")
                                     ->with('user_info')
                                      ->get();
                
                 return response()->json([
                    'Current' => $Current,
                    'Pending' => $newData,
                    'Former' => $Former,
                    'success' => true
                ],200); 
            }
            public function cancel_invite(Request $request){
               $delete = MemberRequestToUser::where('id', $request->id)->delete();
               if($delete != 0){
                   return response()->json([
                    'success' => true
                   ], 200);
               }else{
                    return response()->json([
                        'msg' => "Something went wrong .",
                        'success' => false
                    ],400);
                }
            }
            public function get_all_appointments(Request $request){
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
                $notification_seen = Notification::where('Product_general_id', $request->id)
                                        ->where('user_id', $user_id)
                                        ->where('type', "Appointment")
                                        ->update([
                                            'is_seen' => 1
                                        ]);
                $today_date = date("Y/m/d");
                $today_time = date("h:i:sa");
                $todays_list = Appointment::select('*',DB::raw('monthname(from_date) as month'), DB::raw('YEAR(from_date) AS Year'), DB::raw('DAY(from_date) AS day' ), DB::raw('DAYNAME(from_date) AS weekDay' ) )
                                            ->where('Product_general_id', $request->id)
                                            ->where('from_date', $today_date)
                                             ->orderBy('from_time', 'asc')
                                            ->get();
                if(sizeof($todays_list)>0){
                    foreach($todays_list as $key=> $value){
                        $value['month'] = $value['month']." ".$value['Year'];  
                        $kyes = [
                            'Saturday' => "Sat",
                            'Sunday' => "Sun",
                            'Monday' => "Mon",
                            'Tuesday' => "Tue",
                            'Wednesday' => "Wed",
                            'Thursday' => "Thu",
                            'Friday' => "Fri",
                        ];
                        if($value['from_time'] != null){
                            $value['from_time'] = date("g:iA", strtotime($value['from_time']));
                        }
                        if($value['to_time'] != null){
                            $value['to_time'] = date("g:iA", strtotime($value['to_time']));
                        }
                        $value['weekDay'] = $kyes[$value['weekDay']] ;
                    }
                }
                $all_appointments = Appointment::select('*',DB::raw('monthname(from_date) as month'), DB::raw('YEAR(from_date) AS Year'), DB::raw('DAY(from_date) AS day' ), DB::raw('DAYNAME(from_date) AS weekDay' ) )
                                    ->where('Product_general_id', $request->id)
                                    ->whereDate('from_date', '>', $today_date)
                                    ->orderBy('from_date','asc')
                                    ->orderBy('from_time', 'asc')
                                    ->get();
                if(sizeof($all_appointments)>0){
                    foreach($all_appointments as $key=> $value){
                        $value['month'] = $value['month']." ".$value['Year'];  
                        $kyes = [
                            'Saturday' => "Sat",
                            'Sunday' => "Sun",
                            'Monday' => "Mon",
                            'Tuesday' => "Tue",
                            'Wednesday' => "Wed",
                            'Thursday' => "Thu",
                            'Friday' => "Fri",
                        ];
                        if($value['from_date'] != null){
                            $time =new DateTime($value['from_date']); 
                            $value['from_date'] =  $time->format('m-d-Y');
                          }
                        if($value['to_date'] != null){
                            $time =new DateTime($value['to_date']); 
                            $value['to_date'] =  $time->format('m-d-Y');
                          }
                        if($value['from_time'] != null){
                            $value['from_time'] = date("g:iA", strtotime($value['from_time']));
                        }
                        if($value['to_time'] != null){
                            $value['to_time'] = date("g:iA", strtotime($value['to_time']));
                        }
                        $value['weekDay'] = $kyes[$value['weekDay']] ;
                    }
                }
                // $all_appointments = $all_appointments->groupBy('month');     
                // foreach($all_appointments as $key=> $value){
                //          $all_appointments[$key] = $all_appointments[$key]->groupBy('from_date');     
                // }
                // $all_appointments['today'] = $todays_list;
                //  \Log::info($all_appointments);  
                 return response()->json([
                    'today' => $todays_list,
                    'all_appointments' => $all_appointments,
                    'success' => true
                ],200); 
            }
            public function get_appointment_details(Request $request){
                $appointment_details = Appointment::where('id', $request->id)->with('attachments', 'people.people_info')->first();
                if($appointment_details == null){
                    return response()->json([
                        'msg' => "This appointment has been removed .",
                        'success' => false
                    ],400);
                }
                else if($appointment_details){
                    if($appointment_details->from_date != null){
                        $time =new DateTime($appointment_details->from_date); 
                        $appointment_details->from_date =  $time->format('m-d-Y');
                      }
                    if($appointment_details->to_date != null){
                        $time =new DateTime($appointment_details->to_date); 
                        $appointment_details->to_date =  $time->format('m-d-Y');
                      }
                    if($appointment_details->from_time != null){
                        $appointment_details->from_time  = date("g:iA", strtotime($appointment_details->from_time));
                    }
                    if($appointment_details->to_time != null){
                        $appointment_details->to_time  = date("g:iA", strtotime($appointment_details->to_time));
                    }
                    return response()->json([
                        'appointment_details' => $appointment_details,
                        'success' => true
                    ],200);
                }else{
                    return response()->json([
                        'msg' => "Something went wrong .",
                        'success' => false
                    ],400);
                }
            }
            public function get_Product_general(Request $request){
                $ProductGeneral = ProductGeneral::orderBy('id', 'desc')->get();
                return response()->json([
                    'ProductGeneral' => $ProductGeneral,
                    'success' => true
                ],200);
            }
            public function get_all_user(Request $request){
                $User = User::orderBy('id', 'desc')->get();
                if(sizeof($User)>0){
                    foreach($User as $value){
                        $value['checked'] = false;
                    }
                }
                return response()->json([
                    'User' => $User,
                    'success' => true
                ],200);
            }
            public function get_all_medicins(Request $request){
                $Medicin = Medication::orderBy('id', 'desc')->get();
                return response()->json([
                    'Medicin' => $Medicin,
                    'success' => true
                ],200);
            }
            public function get_all_doctors(Request $request){
                $Doctor = Doctor::orderBy('id', 'desc')->get();
                return response()->json([
                    'Doctor' => $Doctor,
                    'success' => true
                ],200);
            }
            public function make_logs_for_Product_general($prior, $new, $member_id, $Product_id){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id;
                $keys = [
                    "first_name" => "First Name",
                    "last_name" => "Last Name",
                    "birthday" => "Birthday",
                    "gender" => "Gender",
                    "place_of_birth" => "Place Of Birth",
                    "ethnicity" => "Ethnicity",
                    "ssn" => "SSN"
                ];
                foreach ($keys as $key => $value){
                    if($new[$key] != null){
                        if($prior[$key] == null){
                            foreach($member_id as $value2){
                                if($value2 == $AuthUserId){
                                    $log = LogHistory::create([
                                        'Product_general_id' =>$Product_id,
                                        'user_id' =>$AuthUserId,
                                        'to_user' =>$value2,
                                        'tag' => "Biographical",
                                        'seen' => 1,
                                        "message" =>  " added ".$key.": ".$new[$key]."."
                                    ]);
                                }
                                else{
                                    $log = LogHistory::create([
                                        'Product_general_id' =>$Product_id,
                                        'user_id' =>$AuthUserId,
                                        'to_user' =>$value2,
                                        'tag' => "Biographical",
                                        'seen' => 0,
                                        "message" => " added ".$key.": ".$new[$key]."."
                                    ]);
                                }
                            }                            
                        }
                        else if($new[$key]!= $prior[$key]){	
                            foreach($member_id as $value2){
                                if($value2 == $AuthUserId){
                                    $log = LogHistory::create([
                                        'Product_general_id' =>$Product_id,
                                        'user_id' =>$AuthUserId,
                                        'to_user' =>$value2,
                                        'tag' => "Biographical",
                                        'seen' => 1,
                                        "message" =>  " changed ".$key." from ".$prior[$key]." to ". $new[$key]."."
                                    ]);
                                }
                                else{
                                    $log = LogHistory::create([
                                        'Product_general_id' =>$Product_id,
                                        'user_id' =>$AuthUserId,
                                        'to_user' =>$value2,
                                        'tag' => "Biographical",
                                        'seen' => 0,
                                        "message" =>  " changed ".$key." from ".$prior[$key]." to ". $new[$key]."."
                                    ]);
                                }
                            } 
                        }
                    }
                    else{
                        if(prior[$key]!= null){
                            foreach($member_id as $value2){
                                if($value2 == $AuthUserId){
                                    $log = LogHistory::create([
                                        'Product_general_id' =>$Product_id,
                                        'user_id' =>$AuthUserId,
                                        'to_user' =>$value2,
                                        'tag' => "Biographical",
                                        'seen' => 1,
                                        "message" =>  " removed ".$key.". " 
                                    ]);
                                }
                                else{
                                    $log = LogHistory::create([
                                        'Product_general_id' =>$Product_id,
                                        'user_id' =>$AuthUserId,
                                        'to_user' =>$value2,
                                        'tag' => "Biographical",
                                        'seen' => 0,
                                        "message" =>  " removed ".$key.". " 
                                    ]);
                                }
                            } 
                        } 
                    }
                }
            }
            public function make_logs_for_object($prior, $new, $member_id, $Product_id){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $new= (array)$new;
                $prior= (array)$prior;
                $diffrence = array_diff($new,$prior);
                $text = "";
                $type = "";
                $text_array = new Collection([]);
                foreach($diffrence as $key => $value){
                    if($prior[$key] == null){
                        $type = "Added";
                    }else if($prior[$key] != null &&  $new[$key] != null){
                        $type = "Updated";
                    }else{
                        $type = "Removed";
                    }
                    $text = $this->return_text($key, $value, $type, $prior, $new);
                    $text_array->push($text);
                }
                foreach ($member_id as $value){
                    foreach( $text_array as $value2 ){
                        if( $value == $AuthUserId){
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Biographical",
                                'seen' => 1,
                                "message" =>  $value2 
                            ]);
                        }else{
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Biographical",
                                'seen' => 0,
                                "message" =>  $value2 
                            ]);
                        }
                    }
                }
               return $text_array;
            }
            public function make_logs_for_siblings($prior, $new, $member_id, $Product_id){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $new= (array)$new;
                $prior= (array)$prior;
                $diffrence = array_diff($new,$prior);
                $text = "";
                $type = "";
                $text_array = new Collection([]);
                foreach($diffrence as $key => $value){
                    if($prior[$key] == null){
                        $type = "Added";
                    }else if($prior[$key] != null &&  $new[$key] != null){
                        $type = "Updated";
                    }else{
                        $type = "Removed";
                    }
                    $text = $this->return_text_for_siblings($key, $value, $type, $prior, $new);
                    $text_array->push($text);
                }
                foreach ($member_id as $value){
                    foreach( $text_array as $value2 ){
                        if( $value == $AuthUserId){
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Biographical",
                                'seen' => 1,
                                "message" =>  $value2 
                            ]);
                        }else{
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Biographical",
                                'seen' => 0,
                                "message" =>  $value2 
                            ]);
                        }
                    }
                }
               return $text_array;
            }
            public function make_logs_for_medical_conditions($prior, $new, $member_id, $Product_id){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $new= (array)$new;
                $prior= (array)$prior;
                $diffrence = array_diff($new,$prior);
                $text = "";
                $type = "";
                $text_array = new Collection([]);
                foreach($diffrence as $key => $value){
                    if($prior[$key] == null){
                        $type = "Added";
                    }else if($prior[$key] != null &&  $new[$key] != null){
                        $type = "Updated";
                    }else{
                        $type = "Removed";
                    }
                    $text = $this->return_text_for_medical_conditions($key, $value, $type, $prior, $new);
                    $text_array->push($text);
                }
                foreach ($member_id as $value){
                    foreach( $text_array as $value2 ){
                        if( $value == $AuthUserId){
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Biographical",
                                'seen' => 1,
                                "message" =>  $value2 
                            ]);
                        }else{
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Biographical",
                                'seen' => 0,
                                "message" =>  $value2 
                            ]);
                        }
                    }
                }
               return $text_array;
            }
            public function make_logs_for_douments($prior, $new, $member_id, $Product_id, $doc_id){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $new= (array)$new;
                $prior= (array)$prior;
                $diffrence = array_diff($new,$prior);
                $text = "";
                $type = "";
                $text_array = new Collection([]);
                foreach($diffrence as $key => $value){
                    if($prior[$key] == null){
                        $type = "Added";
                    }else if($prior[$key] != null &&  $new[$key] != null){
                        $type = "Updated";
                    }else{
                        $type = "Removed";
                    }
                    $text = $this->return_text_for_documents($key, $value, $type, $prior, $new);
                    $text_array->push($text);
                }
                foreach ($member_id as $value){
                    foreach( $text_array as $value2 ){
                        if( $value == $AuthUserId){
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'doc_id' => $doc_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Biographical",
                                'seen' => 1,
                                "message" =>  $value2 
                            ]);
                        }else{
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'doc_id' => $doc_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Biographical",
                                'seen' => 0,
                                "message" =>  $value2 
                            ]);
                        }
                    }
                }
               return $text_array;
            }
            public function make_logs_for_Category($prior, $new, $member_id, $Product_id){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $new= (array)$new;
                $prior= (array)$prior;
                $diffrence = array_diff($new,$prior);
                $text = "";
                $type = "";
                $text_array = new Collection([]);
                foreach($diffrence as $key => $value){
                    if($prior[$key] == null){
                        $type = "Added";
                    }else if($prior[$key] != null &&  $new[$key] != null){
                        $type = "Updated";
                    }else{
                        $type = "Removed";
                    }
                    $text = $this->return_text_for_Category($key, $value, $type, $prior, $new);
                    $text_array->push($text);
                }
                foreach ($member_id as $value){
                    foreach( $text_array as $value2 ){
                        if( $value == $AuthUserId){
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Biographical",
                                'seen' => 1,
                                "message" =>  $value2 
                            ]);
                        }else{
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Biographical",
                                'seen' => 0,
                                "message" =>  $value2 
                            ]);
                        }
                    }
                }
               return $text_array;
            }
            public function make_logs_for_dental_condition($prior, $new, $member_id, $Product_id){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $new= (array)$new;
                $prior= (array)$prior;
                $diffrence = array_diff($new,$prior);
                $text = "";
                $type = "";
                $text_array = new Collection([]);
                foreach($diffrence as $key => $value){
                    if($prior[$key] == null){
                        $type = "Added";
                    }else if($prior[$key] != null &&  $new[$key] != null){
                        $type = "Updated";
                    }else{
                        $type = "Removed";
                    }
                    $text = $this->return_text_for_dental_condition($key, $value, $type, $prior, $new);
                    $text_array->push($text);
                }
                foreach ($member_id as $value){
                    foreach( $text_array as $value2 ){
                        if( $value == $AuthUserId){
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Dental",
                                'seen' => 1,
                                "message" =>  $value2 
                            ]);
                        }else{
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Dental",
                                'seen' => 0,
                                "message" =>  $value2 
                            ]);
                        }
                    }
                }
               return $text_array;
            }
            public function make_logs_for_dentist($prior, $new, $member_id, $Product_id){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $new= (array)$new;
                $prior= (array)$prior;
                $diffrence = array_diff($new,$prior);
                $text = "";
                $type = "";
                $text_array = new Collection([]);
                foreach($diffrence as $key => $value){
                    if($prior[$key] == null){
                        $type = "Added";
                    }else if($prior[$key] != null &&  $new[$key] != null){
                        $type = "Updated";
                    }else{
                        $type = "Removed";
                    }
                    $text = $this->return_text_for_dentist($key, $value, $type, $prior, $new);
                    $text_array->push($text);
                }
                foreach ($member_id as $value){
                    foreach( $text_array as $value2 ){
                        if( $value == $AuthUserId){
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Dental",
                                'seen' => 1,
                                "message" =>  $value2 
                            ]);
                        }else{
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Dental",
                                'seen' => 0,
                                "message" =>  $value2 
                            ]);
                        }
                    }
                }
               return $text_array;
            }
            public function make_logs_for_therapiest($prior, $new, $member_id, $Product_id){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $new= (array)$new;
                $prior= (array)$prior;
                $diffrence = array_diff($new,$prior);
                $text = "";
                $type = "";
                $text_array = new Collection([]);
                foreach($diffrence as $key => $value){
                    if($prior[$key] == null){
                        $type = "Added";
                    }else if($prior[$key] != null &&  $new[$key] != null){
                        $type = "Updated";
                    }else{
                        $type = "Removed";
                    }
                    $text = $this->return_text_for_therapiest($key, $value, $type, $prior, $new);
                    $text_array->push($text);
                }
                foreach ($member_id as $value){
                    foreach( $text_array as $value2 ){
                        if( $value == $AuthUserId){
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Therapy",
                                'seen' => 1,
                                "message" =>  $value2 
                            ]);
                        }else{
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Therapy",
                                'seen' => 0,
                                "message" =>  $value2 
                            ]);
                        }
                    }
                }
               return $text_array;
            }
            public function make_logs_for_education($prior, $new, $member_id, $Product_id){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $new= (array)$new;
                $prior= (array)$prior;
                $diffrence = array_diff($new,$prior);
                $text = "";
                $type = "";
                $text_array = new Collection([]);
                foreach($diffrence as $key => $value){
                    if($prior[$key] == null){
                        $type = "Added";
                    }else if($prior[$key] != null &&  $new[$key] != null){
                        $type = "Updated";
                    }else{
                        $type = "Removed";
                    }
                    $text = $this->return_text_for_education($key, $value, $type, $prior, $new);
                    $text_array->push($text);
                }
                foreach ($member_id as $value){
                    foreach( $text_array as $value2 ){
                        if( $value == $AuthUserId){
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Education",
                                'seen' => 1,
                                "message" =>  $value2 
                            ]);
                        }else{
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Education",
                                'seen' => 0,
                                "message" =>  $value2 
                            ]);
                        }
                    }
                }
               return $text_array;
            }
            public function make_logs_for_legal($prior, $new, $member_id, $Product_id){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $new= (array)$new;
                $prior= (array)$prior;
                $diffrence = array_diff($new,$prior);
                $text = "";
                $type = "";
                $text_array = new Collection([]);
                foreach($diffrence as $key => $value){
                    if($prior[$key] == null){
                        $type = "Added";
                    }else if($prior[$key] != null &&  $new[$key] != null){
                        $type = "Updated";
                    }else{
                        $type = "Removed";
                    }
                    $text = $this->return_text_for_legal($key, $value, $type, $prior, $new);
                    $text_array->push($text);
                }
                foreach ($member_id as $value){
                    foreach( $text_array as $value2 ){
                        if( $value == $AuthUserId){
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Legal",
                                'seen' => 1,
                                "message" =>  $value2 
                            ]);
                        }else{
                            LogHistory::create([
                                'Product_general_id' =>$Product_id,
                                'user_id' =>$AuthUserId,
                                'to_user' =>$value,
                                'tag' => "Legal",
                                'seen' => 0,
                                "message" =>  $value2 
                            ]);
                        }
                    }
                }
               return $text_array;
            }
            public function return_text($key, $value, $type, $prior, $new){
                $text = "";
                if($key == "first_name"  ){
                    if($type == "Updated") $text = " changed First Name from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added First Name: ".$new[$key].".";
                    if($type == "Removed") $text = " removed First Name.";
                }
                if($key == "last_name" ){
                    if($type == "Updated") $text = " changed Last Name from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Last Name: ".$new[$key].".";
                    if($type == "Removed") $text = " removed Last Name.";
                }
                if($key == "birthday" ){
                    if($type == "Updated") $text = " changed Date of Birth from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Date of Birth: ".$new[$key].".";
                    if($type == "Removed") $text = " removed Date of Birth.";
                }
                if($key == "gender" ){
                    if($type == "Updated") $text = " changed Gender from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Gender: ".$new[$key].".";
                    if($type == "Removed") $text = " removed Gender.";
                }
                if($key == "place_of_birth" ){
                    if($type == "Updated") $text = " changed Place of Birth from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Place of Birth: ".$new[$key].".";
                    if($type == "Removed") $text = " removed Place of Birth.";
                }
                if($key == "ethnicity" ){
                    if($type == "Updated") $text = " changed Ethnicity from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Ethnicity: ".$new[$key].".";
                    if($type == "Removed") $text = " removed Ethnicity.";
                }
                if($key == "ssn" ){
                    if($type == "Updated") $text = " changed SSN from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added SSN: ".$new[$key].".";
                    if($type == "Removed") $text = " removed SSN.";
                }
                if($key == 'placement_name' ){
                    if($type == "Updated") $text = " changed Placement Name linked to ".$prior['placement_name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Placement Name
                    linked to ".$prior['placement_name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Placement Name.";
                }
                if($key == 'placement_type' ){
                    if($type == "Updated") $text = " changed Placement Type linked to ".$prior['placement_name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Placement Type linked to ".$prior['placement_name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed ".$prior[$key]." from Placement Type linked to  ".$prior['placement_name'].".";
                }
                if($key == 'date' ){
                    if($type == "Updated") $text = " changed Date Placed linked to ".$prior['placement_name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Date Placed linked to ".$prior['placement_name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed ".$prior[$key]." from Date Placed linked to  ".$prior['placement_name'].".";
                }
                if($key == 'phone_number' ){
                    if($type == "Updated") $text = " changed Placement Phone Number linked to ".$prior['placement_name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Placement Phone Number linked to".$prior['placement_name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed ".$prior[$key]." from Placement Phone Number linked to ".$prior['placement_name'].".";
                }
                if($key == 'address' ){
                    if($type == "Updated") $text = " changed Placement Address linked to ".$prior['placement_name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Placement Address linked to ".$prior['placement_name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed ".$prior[$key]." from Placement Address linked to  ".$prior['placement_name'].".";
                }
                if($key == 'email' ){
                    if($type == "Updated") $text = " changed Placement Email linked to ".$prior['placement_name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Placement Email linked to ".$prior['placement_name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed ".$prior[$key]." from Placement Email linked to ".$prior['placement_name'].".";
                }
                return $text;
            }
            public function return_text_for_siblings($key, $value, $type, $prior, $new){
                $text = "";
                if($key == "name"  ){
                    if($type == "Updated") $text = " changed Sibling Name linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added ".$new[$key]." as a sibling.";
                    if($type == "Removed") $text = " removed ".$prior[$key]." as a sibling.";
                }
                if($key == "address"  ){
                    if($type == "Updated") $text = " changed Sibling Address linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Sibling Address linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Sibling Address linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "phone_number"  ){
                    if($type == "Updated") $text = " changed Sibling Phone Number linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Sibling Phone Number linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Sibling Phone Number linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "email"  ){
                    if($type == "Updated") $text = " changed Sibling Email linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Sibling Email linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Sibling Email linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "gender"  ){
                    if($type == "Updated") $text = " changed Sibling Gender linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Sibling Gender linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Sibling Gender linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "relation"  ){
                    if($type == "Updated") $text = " changed Sibling Relation linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Sibling Relation linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Sibling Relation linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "note"  ){
                    if($type == "Updated") $text = " changed Sibling Notes linked to ".$new['name']." from '".$prior[$key]."' to '".$new[$key]."'.";
                    if($type == "Added") $text = " added Sibling Notes linked to ".$new['name'].": '".$new[$key]."'.";
                    if($type == "Removed") $text = " removed Sibling Notes linked to ".$new['name'].": '".$prior[$key]."'.";
                }
                if($key == "status"  ){
                    if($type == "Updated") $text = " changed Sibling Status linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Sibling Status linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Sibling Status linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "placement_type"  ){
                    if($type == "Updated") $text = " changed Sibling Placement Type linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Sibling Placement Type linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Sibling Placement Type linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "birthday"  ){
                    if($type == "Updated") $text = " changed Sibling Date of Birth linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Sibling Date of Birth linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Sibling Date of Birth linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "contact_name"  ){
                    if($type == "Updated") $text = " changed Sibling Contact Name linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Sibling Contact Name linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Sibling Contact Name linked to ".$new['name'].": ".$prior[$key].".";
                }
                return $text;
            }
            public function return_text_for_documents($key, $value, $type, $prior, $new){
                $text = "";
                if($key == "doc_name"  ){
                    if($type == "Added") $text = " uploaded ".$new[$key]." in Documents.";
                    if($type == "Removed") $text = " removed ".$prior[$key]." in Documents.";
                }
                return $text;
            }
            public function return_text_for_dental_condition($key, $value, $type, $prior, $new){
                $text = ""; 
                if($key == "title"  ){
                    if($type == "Updated") $text = " changed dental condition name from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added ".$new[$key]." as a dental condition.";
                    if($type == "Removed") $text = " removed ".$prior[$key]." as a dental condition.";
                }
                if($key == "date"  ){
                    if($type == "Updated") $text = " changed Dental Condition Diagnosis Date linked to ".$new['title']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Dental Condition Diagnosis Date linked to ".$new['title'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Dental Condition Diagnosis Date linked to ".$new['title'].": ".$prior[$key].".";
                }
                if($key == "description"  ){
                    if($type == "Updated") $text = " changed Dental Condition Notes linked to ".$new['title']." from '".$prior[$key]."' to '".$new[$key]."'.";
                    if($type == "Added") $text = " added Dental Condition Notes linked to ".$new['title'].": '".$new[$key]."'.";
                    if($type == "Removed") $text = " removed Dental Condition Notes linked to ".$new['title'].": '".$prior[$key]."'.";
                }
                return $text;
            }
            public function return_text_for_Category($key, $value, $type, $prior, $new){
                $text = "";
                if($key == "name"  ){
                    if($type == "Updated") $text = " changed Category Name linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added ".$new[$key]." as a Category.";
                    if($type == "Removed") $text = " removed ".$prior[$key]." as a Category.";
                }
                if($key == "address"  ){
                    if($type == "Updated") $text = " changed Category Address linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Category Address linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Category Address linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "phone_number"  ){
                    if($type == "Updated") $text = " changed Category Phone Number linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Category Phone Number linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Category Phone Number linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "email"  ){
                    if($type == "Updated") $text = " changed Category Email linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Category Email linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Category Email linked to ".$new['name'].": ".$prior[$key].".";
                }
                 
                if($key == "relation"  ){
                    if($type == "Updated") $text = " changed Category Relation linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Category Relation linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Category Relation linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "note"  ){
                    if($type == "Updated") $text = " changed Category Notes linked to ".$new['name']." from '".$prior[$key]."' to '".$new[$key]."'.";
                    if($type == "Added") $text = " added Category Notes linked to ".$new['name'].": '".$new[$key]."'.";
                    if($type == "Removed") $text = " removed Category Notes linked to ".$new['name'].": '".$prior[$key]."'.";
                }
                
                if($key == "contact_name"  ){
                    if($type == "Updated") $text = " changed Category Contact Name linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Category Contact Name linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Category Contact Name linked to ".$new['name'].": ".$prior[$key].".";
                }
                return $text;
            }
            public function return_text_for_dentist($key, $value, $type, $prior, $new){
                $text = ""; 
                if($key == "name"  ){
                    if($type == "Updated") $text = " changed dental care provider name from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added ".$new[$key]." as a dental care provider.";
                    if($type == "Removed") $text = " removed ".$prior[$key]." as a dental care provider.";
                }
                if($key == "department"  ){
                    if($type == "Updated") $text = " changed Dental Care Provider Type linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Dental Care Provider Type linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Dental Care Provider Type linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "address"){
                    if($type == "Updated") $text = " changed Dental Care Provider Address linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Dental Care Provider Address linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed  Dental Care Provider Address linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "phone_number"){
                    if($type == "Updated") $text = " changed Dental Care Provider Phone Number linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Dental Care Provider Phone Number linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Dental Care Provider Phone Number linked to ".$new['name'].": ".$prior[$key].".";
                }
                return $text;
            }
            public function return_text_for_therapiest($key, $value, $type, $prior, $new){
                $text = ""; 
                if($key == "name"  ){
                    if($type == "Updated") $text = " changed therapist name from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added ".$new[$key]." as a therapist.";
                    if($type == "Removed") $text = " removed ".$prior[$key]." as a therapist.";
                }
                if($key == "department"  ){
                    if($type == "Updated") $text = " changed Therapist Type linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Therapist Type linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Therapist Type linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "address"){
                    if($type == "Updated") $text = " changed Therapist Address linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Therapist Address linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Therapist Address linked to ".$new['name'].": ".$prior[$key].".";
                }
                if($key == "phone_number"){
                    if($type == "Updated") $text = " changed Therapist Phone Number linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added Therapist Phone Number linked to ".$new['name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed Therapist Phone Number linked to ".$new['name'].": ".$prior[$key].".";
                }
                return $text;
            }
            public function return_text_for_education($key, $value, $type, $prior, $new){
                $text = ""; 
                if($key == "school_name"  ){
                    if($type == "Updated") $text = " changed school name from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added ".$new[$key]." as a school.";
                    if($type == "Removed") $text = " removed ".$prior[$key]." as a school.";
                }
                if($key == "grade"  ){
                    if($type == "Updated") $text = " changed School Grade linked to ".$new['school_name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added School Grade linked to ".$new['school_name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed School Grade linked to ".$new['school_name'].": ".$prior[$key].".";
                }
                if($key == "address"){
                    if($type == "Updated") $text = " changed School Address linked to ".$new['school_name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added School Address linked to ".$new['school_name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed School Address linked to ".$new['school_name'].": ".$prior[$key].".";
                }
                if($key == "phone"){
                    if($type == "Updated") $text = " changed School Phone Number linked to ".$new['school_name']." from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added School Phone Number linked to ".$new['school_name'].": ".$new[$key].".";
                    if($type == "Removed") $text = " removed School Phone Number linked to ".$new['school_name'].": ".$prior[$key].".";
                }
                if($key == "note"){
                    if($type == "Updated") $text = " changed School Notes linked to ".$new['school_name']." from '".$prior[$key]."' to '".$new[$key]."'.";
                    if($type == "Added") $text = " added School Notes linked to ".$new['school_name'].": '".$new[$key]."'.";
                    if($type == "Removed") $text = " removed School Notes linked to ".$new['school_name'].": '".$prior[$key]."'.";
                }
                return $text;
            }
            public function return_text_for_legal($key, $value, $type, $prior, $new){
                $text = ""; 
                if($key == "right_status"  ){
                    if($type == "Updated") $text = " changed parental rights status from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added parental rights status ".$new[$key].".";
                    if($type == "Removed") $text = " removed parental rights status ".$prior[$key].".";
                }
                if($key == "date"  ){
                    if($type == "Updated") $text = " changed termination date from ".$prior[$key]." to ".$new[$key].".";
                    if($type == "Added") $text = " added termination date: ".$new[$key].".";
                    if($type == "Removed") $text = " removed termination date: ".$prior[$key].".";
                }
                if($key == "url"){
                    if($type == "Updated") $text = " changed termination order.";
                    if($type == "Added") $text = " uploaded a termination order.";
                    if($type == "Removed") $text = " removed termination order.";
                }
                if($key == "note"){
                    if($type == "Updated") $text = " changed legal notes from '".$prior[$key]."' to '".$new[$key]."'.";
                    if($type == "Added") $text = " added legal notes: '".$new[$key]."'.";
                    if($type == "Removed") $text = " removed legal notes: '".$prior[$key]."'.";
                }
                return $text;
            }
            public function post_race($Product_id, $member_id, $races){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $find = Race::where('Product_general_id',$Product_id )->pluck('race')->toArray();
                $new =[];
                $old = [];
                $text_array = new Collection([]);
                // create
                $creating_data = [];
                foreach($races as $value){
                    if(!in_array($value,$find)){
                        $each_item = [
                            'Product_general_id' => $Product_id,
                            'race' => $value, 
                        ];
                        array_push($creating_data, $each_item);
                        array_push($new, $value);
                    }
                }
                Race::insert($creating_data);
                // Remove
                $deleteAbleFinds = [];
                foreach($find as $value){
                    if(!in_array($value,$races)){
                        array_push($deleteAbleFinds, $value);
                        array_push($old, $value);
                     }
                }
                Race::whereIn('race',$deleteAbleFinds )->where('Product_general_id',$Product_id )->delete();
                $size_of_new = sizeof($new);
                if($size_of_new >0){
                    if($size_of_new >1){
                        $size = $size_of_new - 1;
                        $lastItem = $new[$size];
                        array_splice($new, $size, 1);
                        $text = implode(", ",$new) . ' and '. $lastItem;
                    }else{
                        $text =$new[0];
                    }
                    $text =  " added Race: ".$text.".";
                    $text_array->push($text);
                }
                $size_of_old = sizeof($old);
                if(sizeof($old)>0){
                    if($size_of_old >1){
                        $size = $size_of_old - 1;
                        $lastItem = $old[$size];
                        array_splice($old, $size, 1);
                        $text = implode(", ",$old) . ' and '. $lastItem;
                    }else{
                        $text =$old[0];
                    }
                    $text =  " removed ".$text." from Race.";
                    $text_array->push($text);
                }
                $massData = [];
                $singleMemberNotiData = [
                    'Product_general_id' =>$Product_id,
                    'user_id' =>$AuthUserId,
                    'tag' => "Biographical",
                ]; 
                if(sizeof($text_array)<=0){
                   return $text_array; 
                }
                foreach($text_array as $value){
                    $singleMemberNotiData['message'] = $value;
                    foreach ($member_id as $value){
                        if( $value == $AuthUserId){
                            $seen = 1;
                        }else{
                            $seen = 0;
                        }
                        $singleMemberNotiData['seen'] =  $seen;
                        $singleMemberNotiData['to_user'] = $value;
                        array_push($massData, $singleMemberNotiData);
                    }
                }
                LogHistory::insert($massData);   
            }
            public function post_language($Product_id, $member_id, $languages){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $find = Language::where('Product_general_id',$Product_id )->pluck('language')->toArray();
                $new =[];
                $old = [];
                $text_array = new Collection([]);
                // create
                $creating_data = [];
                foreach($languages as $value){
                    if(!in_array($value,$find)){
                        $each_item = [
                            'Product_general_id' => $Product_id,
                            'language' => $value, 
                        ];
                        array_push($creating_data, $each_item);
                        array_push($new, $value);
                    }
                }
                Language::insert($creating_data);
                // Remove
                $deleteAbleFinds = [];
                foreach($find as $value){
                    if(!in_array($value,$languages)){
                        array_push($deleteAbleFinds, $value);
                        array_push($old, $value);
                     }
                }
                Language::whereIn('language',$deleteAbleFinds )->where('Product_general_id',$Product_id )->delete();
                $size_of_new = sizeof($new);
                if($size_of_new >0){
                    if($size_of_new >1){
                        $size = $size_of_new - 1;
                        $lastItem = $new[$size];
                        array_splice($new, $size, 1);
                        $text = implode(", ",$new) . ' and '. $lastItem;
                    }else{
                        $text =$new[0];
                    }
                    $text =  " added Language: ".$text.".";
                    $text_array->push($text);
                }
                $size_of_old = sizeof($old);
                if(sizeof($old)>0){
                    if($size_of_old >1){
                        $size = $size_of_old - 1;
                        $lastItem = $old[$size];
                        array_splice($old, $size, 1);
                        $text = implode(", ",$old) . ' and '. $lastItem;
                    }else{
                        $text =$old[0];
                    }
                    $text =  " removed ".$text." from Language.";
                    $text_array->push($text);
                }
                $massData = [];
                $singleMemberNotiData = [
                    'Product_general_id' =>$Product_id,
                    'user_id' =>$AuthUserId,
                    'tag' => "Biographical",
                ]; 
                if(sizeof($text_array)<=0){
                   return $text_array; 
                }
                foreach($text_array as $value){
                    $singleMemberNotiData['message'] = $value;
                    foreach ($member_id as $value){
                        if( $value == $AuthUserId){
                            $seen = 1;
                        }else{
                            $seen = 0;
                        }
                        $singleMemberNotiData['seen'] =  $seen;
                        $singleMemberNotiData['to_user'] = $value;
                        array_push($massData, $singleMemberNotiData);
                    }
                }
                LogHistory::insert($massData);   
            }
            public function post_activities($Product_id, $member_id, $activities){
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $find = ActivitiesAndInterest::where('Product_general_id',$Product_id )->pluck('title')->toArray();
                $new =[];
                $old = [];
                $text_array = new Collection([]);
                // create
                $creating_data = [];
                foreach($activities as $value){
                    if(!in_array($value,$find)){
                        $each_item = [
                            'Product_general_id' => $Product_id,
                            'title' => $value, 
                        ];
                        array_push($creating_data, $each_item);
                        array_push($new, $value);
                    }
                }
                ActivitiesAndInterest::insert($creating_data);
                // Remove
                $deleteAbleFinds = [];
                foreach($find as $value){
                    if(!in_array($value,$activities)){
                        array_push($deleteAbleFinds, $value);
                        array_push($old, $value);
                     }
                }
                ActivitiesAndInterest::whereIn('title',$deleteAbleFinds )->where('Product_general_id',$Product_id )->delete();
                $size_of_new = sizeof($new);
                if($size_of_new >0){
                    if($size_of_new >1){
                        $size = $size_of_new - 1;
                        $lastItem = $new[$size];
                        array_splice($new, $size, 1);
                        $text = implode(", ",$new) . ' and '. $lastItem;
                    }else{
                        $text =$new[0];
                    }
                    $text =  " added: ".$text." under Activities & Interest.";
                    $text_array->push($text);
                }
                $size_of_old = sizeof($old);
                if(sizeof($old)>0){
                    if($size_of_old >1){
                        $size = $size_of_old - 1;
                        $lastItem = $old[$size];
                        array_splice($old, $size, 1);
                        $text = implode(", ",$old) . ' and '. $lastItem;
                    }else{
                        $text =$old[0];
                    }
                    $text =  " removed ".$text." under Activities & Interest.";
                    $text_array->push($text);
                }
                $massData = [];
                $singleMemberNotiData = [
                    'Product_general_id' =>$Product_id,
                    'user_id' =>$AuthUserId,
                    'tag' => "Biographical",
                ]; 
                if(sizeof($text_array)<=0){
                   return $text_array; 
                }
                foreach($text_array as $value){
                    $singleMemberNotiData['message'] = $value;
                    foreach ($member_id as $value){
                        if( $value == $AuthUserId){
                            $seen = 1;
                        }else{
                            $seen = 0;
                        }
                        $singleMemberNotiData['seen'] =  $seen;
                        $singleMemberNotiData['to_user'] = $value;
                        array_push($massData, $singleMemberNotiData);
                    }
                }
                LogHistory::insert($massData);   
            }
            public function post_biographical_details(Request $request){
                $data = $request->all();
                $newData = new Collection([]);
                $user = $data['user_id'];
                $siblings= null;
                $seen = 0;
                $member_id = ProductMember::where('Product_general_id',$data['Product_general_id'] )
                                    ->whereIn('status',["Current", "PendingRemoval"] )
                                    ->pluck('member_id');
                $Product = ProductGeneral::where('id',$data['Product_general_id'] )->first();
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $AuthUser = User::where('id', $AuthUserId)->first();
                $email_data = [
                    'Product_first_name' => $Product->first_name,
                    'Product_last_name' => $Product->last_name, 
                    'user_first_name' => $AuthUser->first_name, 
                    'user_last_name' => $AuthUser->last_name, 
                    ];
                if(!$Product){
                    return response()->json([
                        'msg' => "Something  went wrong !",
                        "Success" => true
                    ], 400);
                }
                if($data['Product_generals']){
                    $log = $this->make_logs_for_object( $Product->toArray(), $data['Product_generals'], $member_id,$data['Product_general_id'] );
                     ProductGeneral::updateOrCreate(
                        ['id' => $data['Product_general_id'] ],
                        [
                            'user_id' =>$data['user_id'],
                            'first_name' => $data['Product_generals']['first_name'],
                            'last_name' => $data['Product_generals']['last_name'],
                            'birthday' => $data['Product_generals']['birthday'],
                            'gender' => $data['Product_generals']['gender'],
                            'place_of_birth' => $data['Product_generals']['place_of_birth'],
                            'ethnicity' => $data['Product_generals']['ethnicity'],
                            'ssn' => $data['Product_generals']['ssn'],
                        ]
                    );
                }
                if($data['Product_placements']){
                    $find = ProductPlacement::where('id',$data['Product_placements']['id'] )->first();
                    if($data['Product_placements']['id'] == 0){
                        $ProductPlacement = ProductPlacement::updateOrCreate(
                                    ['Product_general_id' => $data['Product_general_id'],
                                    'placement_name' => $data['Product_placements']['placement_name'],
                                    'date' => $data['Product_placements']['date'],
                                    'phone_number' => $data['Product_placements']['phone_number'],
                                    'placement_type' => $data['Product_placements']['placement_type'],
                                    'address' => $data['Product_placements']['address'],
                                    'email' => $data['Product_placements']['email'],
                                   ]);
                        $msg = $Product->first_name." ".$Product->last_name." moved to: ".$data['Product_placements']['placement_name']." Updated by ".$AuthUser->first_name." ".$AuthUser->last_name ;
                        $massData = [];
                        $singleMemberNotiData = [
                            'Product_general_id' =>$data['Product_general_id'],
                            'user_id' =>$AuthUserId,
                            'tag' => "Event",
                            "message" =>  $msg 
                        ]; 
                        foreach ($member_id as $value){
                            if( $value == $AuthUserId){
                                $seen = 1;
                            }else{
                                $seen = 0;
                            }
                            $singleMemberNotiData['seen'] =  $seen;
                            $singleMemberNotiData['to_user'] = $value;
                            array_push($massData, $singleMemberNotiData);
                        }
                        LogHistory::insert($massData);
                        $prior = (array)$data['Product_placements'];
                        $prior['placement_type'] = null;
                        $prior['date'] = null;
                        $prior['phone_number'] = null ;
                        $prior['address'] = null;
                        $prior['email'] = null;
                        $log = $this->make_logs_for_object( $prior, $data['Product_placements'], $member_id,$data['Product_general_id'] );
                    }
                    else if($find){
                        if($data['Product_placements']['id'] == $find->id){
                            $ProductPlacement = ProductPlacement::where('id',$data['Product_placements']['id'] )->update(
                                ['Product_general_id' => $data['Product_general_id'],
                                'placement_name' => $data['Product_placements']['placement_name'],
                                'date' => $data['Product_placements']['date'],
                                'phone_number' => $data['Product_placements']['phone_number'],
                                'placement_type' => $data['Product_placements']['placement_type'],
                                'address' => $data['Product_placements']['address'],
                                'email' => $data['Product_placements']['email'],
                               ]);
                               $log = $this->make_logs_for_object( $find->toArray(), $data['Product_placements'], $member_id,$data['Product_general_id'] );
                        }
                    }else{
                        return response()->json([
                            'msg' => "Something went wrong ",
                            "Success" => true
                        ], 400);
                    }     
                }
                if(sizeof($data['races'])>0){
                    $race = $this->post_race($data['Product_general_id'], $member_id, $data['races']);
                }
                if(sizeof($data['races']) <= 0){
                    $text_array = [];
                    $log_array = [];
                    $find = Race::where('Product_general_id',$data['Product_general_id'] )->pluck('race')->toArray();
                    if($find){
                        $size_of_find = sizeof($find);
                        if($size_of_find>0){
                            if($size_of_find >1){
                                $size = $size_of_find - 1;
                                $lastItem = $find[$size];
                                array_splice($find, $size, 1);
                                $text = implode(", ",$find) . ' and '. $lastItem;
                            }else{
                                $text =$find[0];
                            }
                            $text =  " Removed ".$text." from Race.";
                            array_push($text_array, $text);
                            $races = Race::where('Product_general_id',$data['Product_general_id'] )->delete();
                            foreach($text_array as $value){
                                $singleMemberNotiData = [
                                    'Product_general_id' =>$data['Product_general_id'],
                                    'user_id' =>$AuthUserId,
                                    'tag' => "Biographical",
                                    "message" =>  $value 
                                ]; 
                                foreach ($member_id as $value){
                                    if( $value == $AuthUserId){
                                        $seen = 1;
                                    }else{
                                        $seen = 0;
                                    }
                                    $singleMemberNotiData['seen'] =  $seen;
                                    $singleMemberNotiData['to_user'] = $value;
                                    array_push($log_array, $singleMemberNotiData);
                                }
                            }
                            LogHistory::insert($log_array);
                        }
                    }
                }
                if(sizeof($data['languages'])>0){
                    $language = $this->post_language($data['Product_general_id'], $member_id, $data['languages']);
                }
                if(sizeof($data['languages']) <= 0){
                    $text_array = [];
                    $log_array = [];
                    $find = Language::where('Product_general_id',$data['Product_general_id'] )->pluck('language')->toArray();
                    if($find){
                        $size_of_find = sizeof($find);
                        if($size_of_find>0){
                            if($size_of_find >1){
                                $size = $size_of_find - 1;
                                $lastItem = $find[$size];
                                array_splice($find, $size, 1);
                                $text = implode(", ",$find) . ' and '. $lastItem;
                            }else{
                                $text =$find[0];
                            }
                            $text =  " Removed ".$text." from Language.";
                            array_push($text_array, $text);
                            $languages = Language::where('Product_general_id',$data['Product_general_id'] )->delete();
                            foreach($text_array as $value){
                                $singleMemberNotiData = [
                                    'Product_general_id' =>$data['Product_general_id'],
                                    'user_id' =>$AuthUserId,
                                    'tag' => "Biographical",
                                    "message" =>  $value 
                                ]; 
                                foreach ($member_id as $value){
                                    if( $value == $AuthUserId){
                                        $seen = 1;
                                    }else{
                                        $seen = 0;
                                    }
                                    $singleMemberNotiData['seen'] =  $seen;
                                    $singleMemberNotiData['to_user'] = $value;
                                    array_push($log_array, $singleMemberNotiData);
                                }
                            }
                            LogHistory::insert($log_array);
                        }
                    }
                }
                if($data['activities_and_interests']>0){
                    $activities = $this->post_activities($data['Product_general_id'], $member_id, $data['activities_and_interests']);
                }
                if(sizeof($data['activities_and_interests']) <= 0){
                    $text_array = [];
                    $log_array = [];
                    $find = ActivitiesAndInterest::where('Product_general_id',$data['Product_general_id'] )->pluck('title')->toArray();
                    if($find){
                        $size_of_find = sizeof($find);
                        if($size_of_find>0){
                            if($size_of_find >1){
                                $size = $size_of_find - 1;
                                $lastItem = $find[$size];
                                array_splice($find, $size, 1);
                                $text = implode(", ",$find) . ' and '. $lastItem;
                            }else{
                                $text =$find[0];
                            }
                            $text =  " Removed ".$text." under Activities & Interests.";
                            array_push($text_array, $text);
                            $languages = ActivitiesAndInterest::where('Product_general_id',$data['Product_general_id'] )->delete();
                            foreach($text_array as $value){
                                $singleMemberNotiData = [
                                    'Product_general_id' =>$data['Product_general_id'],
                                    'user_id' =>$AuthUserId,
                                    'tag' => "Biographical",
                                    "message" =>  $value 
                                ]; 
                                foreach ($member_id as $value){
                                    if( $value == $AuthUserId){
                                        $seen = 1;
                                    }else{
                                        $seen = 0;
                                    }
                                    $singleMemberNotiData['seen'] =  $seen;
                                    $singleMemberNotiData['to_user'] = $value;
                                    array_push($log_array, $singleMemberNotiData);
                                }
                            }
                            LogHistory::insert($log_array);
                        }
                    }
                }
                if($data['documents']){
                    $newData4 = new Collection([]);
                    $find_array = ProductDocument::where('Product_general_id',$data['Product_general_id'] )->pluck('id');
                    $find = $find_array->toArray();
                    $member_id = ProductMember::where('Product_general_id',$data['Product_general_id'] )
                    ->pluck('member_id');
                    foreach($data['documents'] as $key =>$value){
                        if($value['id'] == 0){
                             $ProductFile = ProductDocument::create (
                                [
                                    'doc_type' =>$value['doc_type'],
                                    'doc_name'=>$value['doc_name'],
                                    'url_type' => $value['url_type'],
                                    'url'=> $value['url'],
                                    'extension'=> $value['extension'],
                                    'user_id' => $data['user_id'],
                                    'Product_general_id' => $data['Product_general_id'],
                                ]
                             );
                             $doc_id = $ProductFile->id;
                             $prior = [
                                 "doc_name"=>null,
                             ];
                             $new = [
                                 "doc_name"=>$value['doc_name'],
                             ];
                             $log = $this->make_logs_for_douments($prior, $new, $member_id, $data['Product_general_id'] , $doc_id);

                        }else if(in_array($value['id'], $find)){
                            $newData4->push($value['id']);
                        }
                        foreach($find_array as $value3){
                            $newData5 = $newData4->toArray();
                            if(!in_array($value3, $newData5)){ 
                                $find_doc = ProductDocument::where('id',$value3 )->first();
                                $doc_id = $find_doc->id;
                                $prior = [
                                    "doc_name"=> $find_doc->doc_name,
                                ];
                                $new = [
                                    "doc_name"=>null,
                                ];
                                $log = $this->make_logs_for_douments($prior, $new, $member_id, $data['Product_general_id'] , $doc_id);
                                ProductDocument::where('id',$value3 )->delete();
                            }
                        }
                    }
                }
                if(sizeof($data['documents']) <= 0){
                    $find = ProductDocument::where('Product_general_id',$data['Product_general_id'] )->pluck('id');
                    if(sizeof($find)>0){
                        foreach($find as $value){
                            $find_doc = ProductDocument::where('id',$value )->first();
                            $doc_id = $find_doc->id;
                            $prior = [
                                "doc_name"=> $find_doc->doc_name,
                            ];
                            $new = [
                                "doc_name"=>null,
                            ];
                            $log = $this->make_logs_for_douments($prior, $new, $member_id, $data['Product_general_id'], $doc_id );
                        }
                        ProductDocument::where('Product_general_id',$data['Product_general_id'] )->delete();
                    }
                }
                if($data['people_at_placement']){
                    $user_array = PlacementPeople::where('Product_general_id',$data['Product_general_id'] )->where('user_id', '!=', 0)->pluck('user_id')->toArray();
                    $email_array = PlacementPeople::where('Product_general_id',$data['Product_general_id'] )->where('user_id', 0)->pluck('email')->toArray();
                    $text_array = [];
                    $log_array = [];
                    if(sizeof($data['people_at_placement']['email'])>0){
                        $create_email_array =[];
                        foreach($data['people_at_placement']['email'] as  $value){
                            $find_user = User::where('email', $value)->first();
                            if(!$find_user){
                                array_push($create_email_array, $value);
                                if(in_array($value,$email_array) == false){
                                    PlacementPeople::firstOrCreate(
                                        [
                                            'Product_general_id' => $data['Product_general_id'] ,
                                            'user_id' => 0, 
                                            'email' => $value, 
                                        ]
                                    );
                                }
                            }
                            else{
                                array_push($data['people_at_placement']['user'], $find_user->id);
                            }
                        }
                        $size_of_user = sizeof($create_email_array);
                        if($size_of_user> 0){
                            if($size_of_user >1){
                                $size = $size_of_user - 1;
                                $lastItem = $create_email_array[$size];
                                array_splice($create_email_array, $size, 1);
                                $text = implode(", ",$create_email_array) . ' and '. $lastItem;
                            }else{
                                $text =$create_email_array[0];
                            }
                            $text =  " added People at Placement linked to ".$data['Product_placements']['placement_name'].": ".$text.".";
                            array_push($text_array, $text);
                        }
                    }
                    else{
                        if(sizeof($email_array)>0){
                                 PlacementPeople::where('Product_general_id',$data['Product_general_id'] )->delete();
                            $size_of_email_array = sizeof($email_array);
                            if($size_of_email_array >1){
                                $size = $size_of_email_array - 1;
                                $lastItem = $email_array[$size];
                                array_splice($email_array, $size, 1);
                                $text = implode(", ",$email_array) . ' and '. $lastItem;
                            }else{
                                $text =$email_array[0];
                            }
                            $text =  " Removed ".$text." from People at Placement linked to ".$data['Product_placements']['placement_name'].'.';
                            array_push($text_array, $text);
                        }
                    }
                    if(sizeof($data['people_at_placement']['user']) > 0){
                        $create_name_array = [];
                        foreach($data['people_at_placement']['user'] as  $value){
                            if(in_array($value,$user_array) == false){
                                $Find_user= User::where('id', $value)->first();
                                if(!$Find_user){
                                    return response()->json([
                                        'msg' => "Something went wrong ",
                                        "Success" => true
                                    ], 400);
                                }
                                // $find_member = ProductMember::where('Product_general_id',$data['Product_general_id'])->where('member_id', $value)->whereIn('status', ["Current", "PendingRemoval"])->count();
                                // if($find_member<=0){
                                //     MemberRequestToUser::firstOrCreate([
                                //         'Product_general_id' => $data['Product_general_id'],
                                //         'email' => $Find_user->email,
                                //         'user_id' => $AuthUserId,
                                //         'to_user' => $Find_user->id,
                                //     ]);
                                //     $mail = Mail::to($Find_user->email)->send(new InvitedToJoinProduct($email_data));
                                // }
                                PlacementPeople::firstOrCreate(
                                    [
                                        'Product_general_id' => $data['Product_general_id'] ,
                                        'user_id' => $value, 
                                        'email' => $Find_user->email, 
                                    ]
                                );
                                $user_name = $Find_user->first_name." ".$Find_user->last_name;
                                array_push($create_name_array, $user_name);
                            }
                        }
                        $size_of_user = sizeof($create_name_array);
                        if($size_of_user> 0){
                            if($size_of_user >1){
                                $size = $size_of_user - 1;
                                $lastItem = $create_name_array[$size];
                                array_splice($create_name_array, $size, 1);
                                $text = implode(", ",$create_name_array) . ' and '. $lastItem;
                            }else{
                                $text = $create_name_array[0];
                            }
                            $text =  " added People at Placement linked to ".$data['Product_placements']['placement_name'].": ".$text.".";
                            array_push($text_array, $text);
                        }
                        $name_array = [];
                        foreach($user_array as $value3){
                            if(in_array($value3,$data['people_at_placement']['user']) == false ){
                                $user = User::where('id',$value3)->first();
                                $user_name = $user->first_name." ".$user->last_name;
                                array_push($name_array, $user_name); 
                                PlacementPeople::where('Product_general_id',$data['Product_general_id'] )->where('user_id', $value3)->delete();
                            }
                        }
                        $size_of_user = sizeof($name_array);
                        if($size_of_user> 0){
                            if($size_of_user >1){
                                $size = $size_of_user - 1;
                                $lastItem = $name_array[$size];
                                array_splice($name_array, $size, 1);
                                $text = implode(", ",$name_array) . ' and '. $lastItem;
                            }else{
                                $text =$name_array[0];
                            }
                            $text =  " Removed ".$text." from People at Placement linked to ".$data['Product_placements']['placement_name'].'.';
                            array_push($text_array, $text);
                        }
                    }
                    else{
                        if(sizeof($user_array)>0){
                            PlacementPeople::where('Product_general_id',$data['Product_general_id'] )->delete();
                            $name_array = [];
                            foreach($user_array as $value){
                                $user = User::where('id',$value)->first();
                                $user_name = $user->first_name." ".$user->last_name;
                                array_push($name_array, $user_name);
                            }
                            $size_of_user = sizeof($name_array);
                            if($size_of_user>0){
                                if($size_of_user >1){
                                    $size = $size_of_user - 1;
                                    $lastItem = $name_array[$size];
                                    array_splice($name_array, $size, 1);
                                    $text = implode(", ",$name_array) . ' and '. $lastItem;
                                }else{
                                    $text =$name_array[0];
                                }
                                $text =  " Removed ".$text." from People at Placement linked to ".$data['Product_placements']['placement_name'].'.';
                                array_push($text_array, $text); 
                            }
                        }
                    }
                    if($text_array>0){
                        foreach($text_array as $value){
                            $singleMemberNotiData = [
                                'Product_general_id' =>$data['Product_general_id'],
                                'user_id' =>$AuthUserId,
                                'tag' => "Biographical",
                                "message" =>  $value 
                            ]; 
                            foreach ($member_id as $value){
                                if( $value == $AuthUserId){
                                    $seen = 1;
                                }else{
                                    $seen = 0;
                                }
                                $singleMemberNotiData['seen'] =  $seen;
                                $singleMemberNotiData['to_user'] = $value;
                                array_push($log_array, $singleMemberNotiData);
                            }
                        }
                        LogHistory::insert($log_array);
                    }
                }
                if(sizeof($data['siblings'])>0){
                    $newData4 = new Collection([]);
                    $find = Sibling::where('Product_general_id',$data['Product_general_id'] )->pluck('id');
                    $find_array = $find->toArray();
                    foreach($data['siblings'] as $value){
                        if($value['id'] == 0){
                            $siblings = Sibling::create([
                                'Product_general_id' => $data['Product_general_id'],
                                'name' => $value['name'],
                                'contact_name' => $value['contact_name'],
                                'address' => $value['address'],
                                'phone_number' => $value['phone_number'],
                                'email' => $value['email'],
                                'gender' => $value['gender'],
                                'birthday' => $value['birthday'],
                                'status' => $value['status'],
                                'relation' => $value['relation'],
                                'placement_type' => $value['placement_type'],
                                'note' => $value['note'],
                            ]);
                            $sibling_fields = $value;
                            $sibling_fields['id'] = $siblings->id;
                            $value['id'] = $siblings->id;
                            $sibling_fields['name'] = null;
                            $sibling_fields['contact_name'] = null;
                            $sibling_fields['phone_number'] = null ;
                            $sibling_fields['address'] = null;
                            $sibling_fields['email'] = null;
                            $sibling_fields['gender'] = null;
                            $sibling_fields['birthday'] = null;
                            $sibling_fields['status'] = null;
                            $sibling_fields['relation'] = null;
                            $sibling_fields['placement_type'] = null;
                            $sibling_fields['note'] = null;
                            $log = $this->make_logs_for_siblings( $sibling_fields, $value, $member_id, $data['Product_general_id'] );
                        }
                        else if(in_array($value['id'], $find_array)){
                            $newData4->push($value['id']);
                            $get_the_item =Sibling::where('id',$value['id'] )->first();
                            $log = $this->make_logs_for_siblings( $get_the_item->toArray(),$value, $member_id, $data['Product_general_id'] );
                            $siblings = Sibling::where('id',$value['id'] )->update (
                                [
                                    'Product_general_id' => $data['Product_general_id'],
                                    'name' => $value['name'],
                                    'contact_name' => $value['contact_name'],
                                    'address' => $value['address'],
                                    'phone_number' => $value['phone_number'],
                                    'email' => $value['email'],
                                    'gender' => $value['gender'],
                                    'birthday' => $value['birthday'],
                                    'status' => $value['status'],
                                    'relation' => $value['relation'],
                                    'placement_type' => $value['placement_type'],
                                    'note' => $value['note'],
                                ]
                            );
                        }
                    }
                    $text_array = [];
                    $log_array = [];
                    foreach($find as $value3){
                        $newData5 = $newData4->toArray();
                        if(!in_array($value3, $newData5)){
                            $get_sibling = Sibling::where('id',$value3 )->first();
                            $text = " removed ".$get_sibling->name." as a sibling . ";
                            $siblings = Sibling::where('id',$value3 )->delete();
                            array_push($text_array, $text);
                        }
                    }
                    foreach($text_array as $value){
                        $singleMemberNotiData = [
                            'Product_general_id' =>$data['Product_general_id'],
                            'user_id' =>$AuthUserId,
                            'tag' => "Biographical",
                            "message" =>  $value 
                        ]; 
                        foreach ($member_id as $value){
                            if( $value == $AuthUserId){
                                $seen = 1;
                            }else{
                                $seen = 0;
                            }
                            $singleMemberNotiData['seen'] =  $seen;
                            $singleMemberNotiData['to_user'] = $value;
                            array_push($log_array, $singleMemberNotiData);
                        }
                    }
                    LogHistory::insert($log_array);
                }
                if(sizeof($data['siblings'])<=0){
                    $text_array = [];
                    $log_array = [];
                    $find = Sibling::where('Product_general_id',$data['Product_general_id'] )->pluck('id');
                    if(sizeof($find)>0){
                        foreach($find as $value3){
                            $get_sibling = Sibling::where('id',$value3 )->first();
                            $text = " removed ".$get_sibling->name." as a sibling . ";
                            $siblings = Sibling::where('id',$value3 )->delete();
                            array_push($text_array, $text);
                        }
                        $siblings = Sibling::where('Product_general_id',$data['Product_general_id'] )->delete();
                        foreach($text_array as $value){
                            $singleMemberNotiData = [
                                'Product_general_id' =>$data['Product_general_id'],
                                'user_id' =>$AuthUserId,
                                'tag' => "Biographical",
                                "message" =>  $value 
                            ]; 
                            foreach ($member_id as $value){
                                if( $value == $AuthUserId){
                                    $seen = 1;
                                }else{
                                    $seen = 0;
                                }
                                $singleMemberNotiData['seen'] =  $seen;
                                $singleMemberNotiData['to_user'] = $value;
                                array_push($log_array, $singleMemberNotiData);
                            }
                        }
                        LogHistory::insert($log_array);
                    }
                }
                if(sizeof($data['Categorys'])>0){
                    $newData4 = new Collection([]);
                    $find = Category::where('Product_general_id',$data['Product_general_id'] )->pluck('id');
                    $find_array = $find->toArray();
                     foreach($data['Categorys'] as $value){
                        if($value['id'] == 0){
                            $Categorys = Category::create([
                                'Product_general_id' => $data['Product_general_id'],
                                'name' => $value['name'],
                                'contact_name' => $value['contact_name'],
                                'address' => $value['address'],
                                'phone_number' => $value['phone_number'],
                                'email' => $value['email'],
                                'relation' => $value['relation'],
                                'note' => $value['note'],
                                ]);
                            $Category_fields = $value;
                            $Category_fields['id'] = $Categorys->id;
                            $value['id'] = $Categorys->id;
                            $Category_fields['name'] = null;
                            $Category_fields['contact_name'] = null;
                            $Category_fields['phone_number'] = null ;
                            $Category_fields['address'] = null;
                            $Category_fields['email'] = null;
                            $Category_fields['relation'] = null;
                            $Category_fields['note'] = null;
                            $log = $this->make_logs_for_Category( $Category_fields, $value, $member_id, $data['Product_general_id'] );
                        }
                        else if(in_array($value['id'], $find_array)){
                                $newData4->push($value['id']);
                                $get_the_item = Category::where('id',$value['id'] )->first();
                                $log = $this->make_logs_for_Category( $get_the_item->toArray(), $value, $member_id, $data['Product_general_id'] );
                                $Categorys = Category::where('id',$value['id'] )->update (
                                    [
                                        'Product_general_id' => $data['Product_general_id'],
                                        'name' => $value['name'],
                                        'contact_name' => $value['contact_name'],
                                        'address' => $value['address'],
                                        'phone_number' => $value['phone_number'],
                                        'email' => $value['email'],
                                        'relation' => $value['relation'],
                                        'note' => $value['note'],
                                    ]
                                    );
                                    
                        }else{
                            return response()->json([
                                'msg' => "Something went wrong ",
                                "Success" => true
                            ], 400);
                        }                              
                    }
                    $text_array = [];
                    $log_array = [];
                    foreach($find as $value3){
                        $newData5 = $newData4->toArray();
                        if(!in_array($value3, $newData5)){
                            $get_Category = Category::where('id',$value3 )->first();
                            $text = " removed ".$get_Category->name." as a Category . ";
                            $Categorys = Category::where('id',$value3 )->delete();
                            array_push($text_array, $text);
                        }
                    }
                    foreach($text_array as $value){
                        $singleMemberNotiData = [
                            'Product_general_id' =>$data['Product_general_id'],
                            'user_id' =>$AuthUserId,
                            'tag' => "Biographical",
                            "message" =>  $value 
                        ]; 
                        foreach ($member_id as $value){
                            if( $value == $AuthUserId){
                                $seen = 1;
                            }else{
                                $seen = 0;
                            }
                            $singleMemberNotiData['seen'] =  $seen;
                            $singleMemberNotiData['to_user'] = $value;
                            array_push($log_array, $singleMemberNotiData);
                        }
                    }
                    LogHistory::insert($log_array);
                }
                if(sizeof($data['Categorys'])<=0){
                    $text_array = [];
                    $log_array = [];
                    $find = Category::where('Product_general_id',$data['Product_general_id'] )->pluck('id');
                    if(sizeof($find)>0){
                        foreach($find as $value3){
                            $get_Category = Category::where('id',$value3 )->first();
                            $text = " removed ".$get_Category->name." as a Category . ";
                            $Categorys = Category::where('id',$value3 )->delete();
                            array_push($text_array, $text);
                        }
                        $Categorys = Category::where('Product_general_id',$data['Product_general_id'] )->delete();
                        foreach($text_array as $value){
                            $singleMemberNotiData = [
                                'Product_general_id' =>$data['Product_general_id'],
                                'user_id' =>$AuthUserId,
                                'tag' => "Biographical",
                                "message" =>  $value 
                            ]; 
                            foreach ($member_id as $value){
                                if( $value == $AuthUserId){
                                    $seen = 1;
                                }else{
                                    $seen = 0;
                                }
                                $singleMemberNotiData['seen'] =  $seen;
                                $singleMemberNotiData['to_user'] = $value;
                                array_push($log_array, $singleMemberNotiData);
                            }
                        }
                        LogHistory::insert($log_array);
                    }
                }
                $Log_history = LogHistory::where('Product_general_id',  $data['Product_general_id'])
                                ->where('to_user',  $data['user_id'])
                                ->where('tag',"Biographical")
                                ->with('user_info','log_documents')
                                ->limit(3)
                                ->orderBy('id', 'desc')
                                ->get();
                                $log_post = Post::with('log_tags','log_documents')
                                ->whereHas('log_tags', function ($query){
                                    $query->where('tag',"Biographical");
                                 })->limit(1)->get();
                                if(sizeof($log_post)>0){
                                    foreach($log_post  as $value){
                                        $value['LogType'] = "Log";
                                        $newData->push($value);
                                    }
                                }
                if(sizeof($Log_history)>0){
                    foreach($Log_history  as $value){
                        $value['LogType'] = "Update";
                        // if($value['user_id'] == $data['user_id'] )
                        // unset($value['user_info']);
                        $newData->push($value);
                    }
                }
                $ProductBiographical = ProductGeneral::where('id', $data['Product_general_id'])
                        ->with('Product_documents', 'placements','language','races', 'people_of_placements', 'people_of_placements.people_info','activities','siblings','Categorys')
                        ->with(['people_of_placements' => function ($q)  use ($user) {
                            $q->where('user_id','!=', 0);
                        }])
                        ->first();
                if($ProductBiographical)
                {
                    return response()->json([
                        'ProductBiographical' => $ProductBiographical,
                        'log' => $newData,
                        'success' => true
                    ],200);
                }
                else{
                    return response()->json([
                        'msg' => "Something went wrong ",
                        "Success" => true
                    ], 400);
                }                
            }
            public function send_invitation(Request $request){
                // for only an email must be check is the user is a member or if the member request to user is sent
                $data = $request->all();
                $Product = ProductGeneral::where('id',$data['Product_general_id'] )->first();
                if(!$Product){
                    return response()->json([
                        'msg' => "Something went wrong! ",
                        "Success" => false
                    ], 400);
                }
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $AuthUser = User::where('id', $AuthUserId)->first();
                $user = User::where('email', $data['email'])->first();
                $find_member = 0;
                $email_data = [
                    'Product_first_name' => $Product->first_name,
                    'Product_last_name' => $Product->last_name, 
                    'user_first_name' => $AuthUser->first_name, 
                    'user_last_name' => $AuthUser->last_name, 
                    ];
                if($user){
                    $find_member = ProductMember::where('Product_general_id',$data['Product_general_id'])->where('member_id', $user->id)->whereIn('status', ["Current", "PendingRemoval"])->count();
                    \Log::info($find_member);
                    if(!$find_member>0){
                        // \Log::info("not member");
                        $find_request = MemberRequestToUser::where('Product_general_id',$data['Product_general_id'])->where('email', $data['email'])->count();
                        if(!$find_request>0){
                            MemberRequestToUser::firstOrCreate([
                                'Product_general_id' => $data['Product_general_id'],
                                'email' => $user->email,
                                'user_id' => $AuthUserId,
                                'to_user' => $user->id,
                            ]);
                            try{
                                $mail = Mail::to($user->email)->send(new InvitedToJoinProduct($email_data));
                            }catch (\Exception $e) {
                                return 0;
                            }
                            
                            return response()->json([
                                  "Success" => true
                            ], 200);
                            
                        }else{
                            return response()->json([
                                'msg' => "Already sent. ",
                                "Success" => false
                            ], 400);
                        }
                    }else{
                        \Log::info("already member");
                        return response()->json([
                            'msg' => "Already a member ",
                            "Success" => false
                        ], 400);
                    }
                }
                else{
                    MemberRequestToUser::firstOrCreate([
                        'Product_general_id' => $data['Product_general_id'],
                        'email' => $data['email'],
                        'user_id' => $AuthUserId,
                        'to_user' => 0,
                    ]);
                    try{
                        $mail = Mail::to($data['email'])->send(new ProductMemberAdd($email_data));
                    }catch (\Exception $e) {
                        return 0;
                    }
                    return response()->json([
                         "Success" => true
                    ], 200);
                }
            }
            public function post_dental(Request $request){
                $data = $request->all();
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $Product = ProductGeneral::where('id',$data['Product_general_id'] )->first();
                $dentist = null;
                $newData = new Collection([]);
                $physical_conditions = null;
                $member_id = ProductMember::where('Product_general_id',$data['Product_general_id'] )
                    ->pluck('member_id');
                if(sizeof($data['physical_conditions'])>0){
                    $newData4 = new Collection([]);
                    $find = PhysicalCondition::where('Product_general_id',$data['Product_general_id'] )->where('type', "Dental")->pluck('id');
                    $find_array = $find->toArray();
                    foreach($data['physical_conditions'] as $value){
                        if($value['id'] == 0){
                            $physical_conditions =  PhysicalCondition::create([  
                                'Product_general_id' => $data['Product_general_id'],
                                'title' => $value['title'], 
                                'date' => $value['date'], 
                                'type' => "Dental", 
                                'description' => $value['description'], 
                            ]);
                            $null_fields = $value;
                            $null_fields['id'] = $physical_conditions->id;
                            $value['id'] = $physical_conditions->id;
                            $null_fields['title'] = null ;
                            $null_fields['date'] = null;
                            $null_fields['description'] = null;
                            $log = $this->make_logs_for_dental_condition($null_fields, $value, $member_id, $data['Product_general_id'] );                   
                        }
                        else if(in_array($value['id'], $find_array)){
                                $newData4->push($value['id']);
                                $get_the_item =PhysicalCondition::where('id',$value['id'] )->first();
                                $log = $this->make_logs_for_dental_condition($get_the_item->toArray(), $value, $member_id, $data['Product_general_id'] );
                                $physical_conditions = PhysicalCondition::where('id',$value['id'] )->update([  
                                    'Product_general_id' => $data['Product_general_id'],
                                    'title' => $value['title'], 
                                    'date' => $value['date'], 
                                    'type' => "Dental", 
                                    'description' => $value['description'], 
                                ]);                             
                        }      
                    }
                    $text_array = [];
                    $log_array = [];
                    foreach($find as $value3){
                        $newData5 = $newData4->toArray();
                        if(!in_array($value3, $newData5)){
                            $get_condition = PhysicalCondition::where('id',$value3 )->first();
                            $text = " removed ".$get_condition->title." as a dental condition . ";
                            array_push($text_array, $text);
                            $physical_conditions = PhysicalCondition::where('id',$value3 )->delete();
                        }
                    }
                    foreach($text_array as $value){
                        $singleMemberNotiData = [
                            'Product_general_id' =>$data['Product_general_id'],
                            'user_id' =>$AuthUserId,
                            'tag' => "Dental",
                            "message" =>  $value 
                        ]; 
                        foreach ($member_id as $value){
                            if( $value == $AuthUserId){
                                $seen = 1;
                            }else{
                                $seen = 0;
                            }
                            $singleMemberNotiData['seen'] =  $seen;
                            $singleMemberNotiData['to_user'] = $value;
                            array_push($log_array, $singleMemberNotiData);
                        }
                    }
                    LogHistory::insert($log_array);
                }
                if(sizeof($data['physical_conditions'])<=0){
                    $find = PhysicalCondition::where('Product_general_id',$data['Product_general_id'] )->where('type', "Dental")->pluck('id');
                    if(sizeof($find)>0){
                        $text_array = [];
                        $log_array = [];
                        foreach ($find as $value3) {
                            $get_dental_condition = PhysicalCondition::where('id',$value3 )->first();
                            $text = " removed ".$get_dental_condition->title." as a dental condition . ";
                            array_push($text_array, $text);
                        }
                        foreach($text_array as $value){
                            $singleMemberNotiData = [
                                'Product_general_id' =>$data['Product_general_id'],
                                'user_id' =>$AuthUserId,
                                'tag' => "Dental",
                                "message" =>  $value 
                            ]; 
                            foreach ($member_id as $value){
                                if( $value == $AuthUserId){
                                    $seen = 1;
                                }else{
                                    $seen = 0;
                                }
                                $singleMemberNotiData['seen'] =  $seen;
                                $singleMemberNotiData['to_user'] = $value;
                                array_push($log_array, $singleMemberNotiData);
                            }
                        }
                        LogHistory::insert($log_array);
                        $physical_conditions = PhysicalCondition::where('Product_general_id',$data['Product_general_id'] )->where('type', "Dental")->delete();
                         
                    }else{
                        $physical_conditions  = 1;
                    }
                }
                if(sizeof($data['dentist'])>0){
                    $newData4 = new Collection([]);
                    $find = Doctor::where('Product_general_id',$data['Product_general_id'] )->where('doctor_type', "Dentist")->pluck('id');
                    $find_array = $find->toArray();
                    $member_id = ProductMember::where('Product_general_id',$data['Product_general_id'] )->pluck('member_id');
                    foreach($data['dentist'] as $value){
                        if($value['id'] == 0){
                            $dentist = Doctor::create([
                                'Product_general_id' => $data['Product_general_id'],
                                'doctor_type' => "Dentist", 
                                'department' => $value['department'], 
                                'name' => $value['name'], 
                                'profile_picture' => $value['profile_picture'], 
                                'address' => $value['address'], 
                                'phone_number' => $value['phone_number'], 
                            ]);
                            $null_fields = $value;
                            $null_fields['id'] = $dentist->id;
                            $value['id'] = $dentist->id;
                            $null_fields['name'] = null ;
                            $null_fields['department'] = null;
                            $null_fields['description'] = null;
                            $null_fields['address'] = null;
                            $null_fields['phone_number'] = null;
                            $log = $this->make_logs_for_dentist($null_fields, $value, $member_id, $data['Product_general_id'] );
                        }
                        else if(in_array($value['id'], $find_array)){
                                $newData4->push($value['id']);
                                $get_the_item =Doctor::where('id',$value['id'] )->first();
                                $log = $this->make_logs_for_dentist($get_the_item->toArray(), $value, $member_id, $data['Product_general_id'] );
                                $dentist = Doctor::where('id',$value['id'] )->update([
                                    'Product_general_id' => $data['Product_general_id'],
                                   'doctor_type' => "Dentist", 
                                   'department' => $value['department'], 
                                   'name' => $value['name'], 
                                   'profile_picture' => $value['profile_picture'], 
                                   'address' => $value['address'], 
                                   'phone_number' => $value['phone_number'], 
                                 ]);                                    
                        }      
                    }
                    $text_array = [];
                    $log_array = [];
                    foreach($find as $value3){
                        $newData5 = $newData4->toArray();
                        if(!in_array($value3, $newData5)){
                            $get_dentist = Doctor::where('id',$value3 )->first();
                            $text = " removed ".$get_dentist->name." as a dental care provider . ";
                            array_push($text_array, $text);
                            $dentist = Doctor::where('id',$value3 )->delete();
                        }
                    }
                    foreach($text_array as $value){
                        $singleMemberNotiData = [
                            'Product_general_id' =>$data['Product_general_id'],
                            'user_id' =>$AuthUserId,
                            'tag' => "Dental",
                            "message" =>  $value 
                        ]; 
                        foreach ($member_id as $value){
                            if( $value == $AuthUserId){
                                $seen = 1;
                            }else{
                                $seen = 0;
                            }
                            $singleMemberNotiData['seen'] =  $seen;
                            $singleMemberNotiData['to_user'] = $value;
                            array_push($log_array, $singleMemberNotiData);
                        }
                    }
                    LogHistory::insert($log_array);
                }
                if(sizeof($data['dentist'])<=0){
                    $find = Doctor::where('Product_general_id',$data['Product_general_id'] )->where('doctor_type', "Dentist")->pluck('id');
                    if(sizeof($find)>0){
                        $text_array = [];
                        $log_array = [];
                        foreach ($find as $value3) {
                            $get_dentist = Doctor::where('id',$value3 )->first();
                            $text = " removed ".$get_dentist->title." as a dental care provider . ";
                            array_push($text_array, $text);
                        }
                        foreach($text_array as $value){
                            $singleMemberNotiData = [
                                'Product_general_id' =>$data['Product_general_id'],
                                'user_id' =>$AuthUserId,
                                'tag' => "Dental",
                                "message" =>  $value 
                            ]; 
                            foreach ($member_id as $value){
                                if( $value == $AuthUserId){
                                    $seen = 1;
                                }else{
                                    $seen = 0;
                                }
                                $singleMemberNotiData['seen'] =  $seen;
                                $singleMemberNotiData['to_user'] = $value;
                                array_push($log_array, $singleMemberNotiData);
                            }
                        }
                        LogHistory::insert($log_array);
                        $dentist = Doctor::where('Product_general_id',$data['Product_general_id'] )->where('doctor_type', "Dentist")->delete();
                         
                    }else{
                        $dentist  = 1;
                    }
                }
                if($dentist!= null && $physical_conditions != null){
                    $dentist = Doctor::where('Product_general_id', $data['Product_general_id'])->where('doctor_type', "Dentist")->get();
                    $physical_conditions = PhysicalCondition::where('Product_general_id', $data['Product_general_id'])->where('type', "Dental")->get();
                    $Log_history = LogHistory::where('Product_general_id',  $data['Product_general_id'])
                                ->where('to_user',  $data['user_id'])
                                ->where('tag',"Dental")
                                ->with('user_info','log_documents')
                                ->limit(3)
                                ->orderBy('id', 'desc')
                                ->get();
                    if(sizeof($Log_history)>0){
                        foreach($Log_history  as $value){
                            $value['LogType'] = "Update";
                            // if($value['user_id'] == $data['user_id'] )
                            // unset($value['user_info']);
                            $newData->push($value);
                        }
                    }
                    $log_post = Post::with('log_tags','log_documents')
                    ->whereHas('log_tags', function ($query){
                        $query->where('tag',"Dental");
                    })->limit(1)->get();
                    if(sizeof($log_post)>0){
                        foreach($log_post  as $value){
                            $value['LogType'] = "Log";
                            $newData->push($value);
                        }
                    }
                    return response()->json([
                        'doctor' => $dentist,
                        'PhysicalCondition' => $physical_conditions,
                        'Log' => $newData,
                        'success' => true
                    ],200);
                }
                else{
                  return response()->json([
                        'msg' => "Something went wrong .",
                        'success' => false
                    ],400);
                }
            }
            public function post_Therapy(Request $request){
                $data = $request->all();
                $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                $newData = new Collection([]);
                $Product = ProductGeneral::where('id',$data['Product_general_id'] )->first();
                $therapiests = null;
                $member_id = ProductMember::where('Product_general_id',$data['Product_general_id'] )
                    ->pluck('member_id');
                if(sizeof($data['therapiests'])>0){
                    $newData4 = new Collection([]);
                    $find = Doctor::where('Product_general_id',$data['Product_general_id'] )->where('doctor_type', "Therapiest")->pluck('id');
                    $find_array = $find->toArray();
                    foreach($data['therapiests'] as $value){
                        if($value['id'] == 0){
                            $therapiests = Doctor::create([
                                'Product_general_id' => $data['Product_general_id'],
                                'doctor_type' => "Therapiest", 
                                'department' => $value['department'], 
                                'name' => $value['name'], 
                                'profile_picture' => $value['profile_picture'], 
                                'address' => $value['address'], 
                                'phone_number' => $value['phone_number'], 
                            ]);
                            $null_fields = $value;
                            $null_fields['id'] = $therapiests->id;
                            $value['id'] = $therapiests->id;
                            $null_fields['name'] = null ;
                            $null_fields['department'] = null;
                            $null_fields['address'] = null;
                            $null_fields['phone_number'] = null;
                            $log = $this->make_logs_for_therapiest($null_fields, $value, $member_id, $data['Product_general_id'] );
                        }
                        else if(in_array($value['id'], $find_array)){
                            $newData4->push($value['id']);
                            $get_the_item =Doctor::where('id',$value['id'] )->first();
                            $log = $this->make_logs_for_therapiest($get_the_item->toArray(), $value, $member_id, $data['Product_general_id'] );
                            $therapiests = Doctor::where('id',$value['id'] )->update([
                                'Product_general_id' => $data['Product_general_id'],
                                'doctor_type' => "Therapiest", 
                                'department' => $value['department'], 
                                'name' => $value['name'], 
                                'profile_picture' => $value['profile_picture'], 
                                'address' => $value['address'], 
                                'phone_number' => $value['phone_number'], 
                            ]);                                    
                        }      
                    }
                    $text_array = [];
                    $log_array = [];
                    foreach($find as $value3){
                        $newData5 = $newData4->toArray();
                        if(!in_array($value3, $newData5)){
                            $get_dentist = Doctor::where('id',$value3 )->first();
                            $text = " removed ".$get_dentist->title." as a therapist. ";
                            array_push($text_array, $text);
                            $therapiests = Doctor::where('id',$value3 )->delete();
                        }
                    }
                    foreach($text_array as $value){
                        $singleMemberNotiData = [
                            'Product_general_id' =>$data['Product_general_id'],
                            'user_id' =>$AuthUserId,
                            'tag' => "Therapy",
                            "message" =>  $value 
                        ]; 
                        foreach ($member_id as $value){
                            if( $value == $AuthUserId){
                                $seen = 1;
                            }else{
                                $seen = 0;
                            }
                            $singleMemberNotiData['seen'] =  $seen;
                            $singleMemberNotiData['to_user'] = $value;
                            array_push($log_array, $singleMemberNotiData);
                        }
                    }
                    LogHistory::insert($log_array);
                }
                if(sizeof($data['therapiests'])<=0){
                    $find = Doctor::where('Product_general_id',$data['Product_general_id'] )->where('doctor_type', "Therapiest")->pluck('id');
                    if(sizeof($find)>0){
                        $text_array = [];
                        $log_array = [];
                        foreach ($find as $value3) {
                            $get_dentist = Doctor::where('id',$value3 )->first();
                            $text = " removed ".$get_dentist->name." as a therapist . ";
                            array_push($text_array, $text);
                        }
                        foreach($text_array as $value){
                            $singleMemberNotiData = [
                                'Product_general_id' =>$data['Product_general_id'],
                                'user_id' =>$AuthUserId,
                                'tag' => "Therapy",
                                "message" =>  $value 
                            ]; 
                            foreach ($member_id as $value){
                                if( $value == $AuthUserId){
                                    $seen = 1;
                                }else{
                                    $seen = 0;
                                }
                                $singleMemberNotiData['seen'] =  $seen;
                                $singleMemberNotiData['to_user'] = $value;
                                array_push($log_array, $singleMemberNotiData);
                            }
                        }
                        LogHistory::insert($log_array);
                        $therapiests = Doctor::where('Product_general_id',$data['Product_general_id'] )->where('doctor_type', "Therapiest")->delete();
                         
                    }else{
                        $therapiests  = 1;
                    }
                }
                if($therapiests!= null){
                    $therapiests = Doctor::where('Product_general_id', $data['Product_general_id'])->where('doctor_type', "Therapiest")->get();
                    $Log_history = LogHistory::where('Product_general_id',  $data['Product_general_id'])
                                ->where('to_user',  $data['user_id'])
                                ->where('tag',"Therapy")
                                ->with('user_info','log_documents')
                                ->limit(3)
                                ->orderBy('id', 'desc')
                                ->get();
                    if(sizeof($Log_history)>0){
                        foreach($Log_history  as $value){
                            $value['LogType'] = "Update";
                            // if($value['user_id'] == $data['user_id'] )
                            // unset($value['user_info']);
                            $newData->push($value);
                        }
                    }
                    $log_post = Post::with('log_tags','log_documents')
                    ->whereHas('log_tags', function ($query){
                        $query->where('tag',"Therapy");
                    })->limit(1)->get();
                    if(sizeof($log_post)>0){
                        foreach($log_post  as $value){
                            $value['LogType'] = "Log";
                            $newData->push($value);
                        }
                    }
                    return response()->json([
                        'doctor' => $therapiests,
                        'Log' => $newData,
                        'success' => true
                    ],200);
                }else{
                    return response()->json([
                        'msg' => "Something went wrong .",
                        'success' => false
                    ],400);
                }
            }
            public function post_Education(Request $request){
                    $data = $request->all();
                    $newData = new Collection([]);
                    $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                    $Product = ProductGeneral::where('id',$data['Product_general_id'] )->first();
                    $education = null;
                    $member_id = ProductMember::where('Product_general_id',$data['Product_general_id'] )->pluck('member_id');
                    if(sizeof($data['educations'])>0){
                        $newData4 = new Collection([]);
                        $find = Education::where('Product_general_id',$data['Product_general_id'] )->pluck('id');
                        $find_array = $find->toArray();
                        foreach($data['educations'] as $value){
                            if($value['id'] == 0){
                                $education = Education::create([
                                    'Product_general_id' => $data['Product_general_id'],
                                    'school_name' => $value['school_name'],
                                    'address' => $value['address'],
                                    'phone' => $value['phone'],
                                    'grade' => $value['grade'],
                                    'note' => $value['note'],
                                ]);
                                $null_fields = $value;
                                $null_fields['id'] = $education->id;
                                $value['id'] = $education->id;
                                $null_fields['school_name'] = null ;
                                $null_fields['address'] = null;
                                $null_fields['phone'] = null;
                                $null_fields['grade'] = null;
                                $null_fields['note'] = null;
                                $log = $this->make_logs_for_education($null_fields, $value, $member_id, $data['Product_general_id'] );
                                }
                            else if(in_array($value['id'], $find_array)){
                                    $newData4->push($value['id']);
                                    $get_the_item =Education::where('id',$value['id'] )->first();
                                    $log = $this->make_logs_for_education($get_the_item->toArray(), $value, $member_id, $data['Product_general_id'] );
                                    $education = Education::where('id',$value['id'] )->update([
                                        'Product_general_id' => $data['Product_general_id'],
                                        'school_name' => $value['school_name'],
                                        'address' => $value['address'],
                                        'phone' => $value['phone'],
                                        'grade' => $value['grade'],
                                        'note' => $value['note'],
                                    ]);                                    
                            }      
                        }
                        $text_array = [];
                        $log_array = [];
                        foreach($find as $value3){
                            $newData5 = $newData4->toArray();
                            if(!in_array($value3, $newData5)){
                                $get_education = Education::where('id',$value3 )->first();
                                $text = " removed ".$get_education->school_name." as a school.";
                                array_push($text_array, $text);
                                $education = Education::where('id',$value3 )->delete();
                            }
                        }
                        foreach($text_array as $value){
                            $singleMemberNotiData = [
                                'Product_general_id' =>$data['Product_general_id'],
                                'user_id' =>$AuthUserId,
                                'tag' => "Education",
                                "message" =>  $value 
                            ]; 
                            foreach ($member_id as $value){
                                if( $value == $AuthUserId){
                                    $seen = 1;
                                }else{
                                    $seen = 0;
                                }
                                $singleMemberNotiData['seen'] =  $seen;
                                $singleMemberNotiData['to_user'] = $value;
                                array_push($log_array, $singleMemberNotiData);
                            }
                        }
                        LogHistory::insert($log_array);
                    }
                    if(sizeof($data['educations'])<=0){
                        $find = Education::where('Product_general_id',$data['Product_general_id'] )->pluck('id');
                        if(sizeof($find)>0){
                            $text_array = [];
                            $log_array = [];
                            foreach ($find as $value3) {
                                $get_education = Education::where('id',$value3 )->first();
                                $text = " removed ".$get_education->name." as a school . ";
                                array_push($text_array, $text);
                            }
                            foreach($text_array as $value){
                                $singleMemberNotiData = [
                                    'Product_general_id' =>$data['Product_general_id'],
                                    'user_id' =>$AuthUserId,
                                    'tag' => "Education",
                                    "message" =>  $value 
                                ]; 
                                foreach ($member_id as $value){
                                    if( $value == $AuthUserId){
                                        $seen = 1;
                                    }else{
                                        $seen = 0;
                                    }
                                    $singleMemberNotiData['seen'] =  $seen;
                                    $singleMemberNotiData['to_user'] = $value;
                                    array_push($log_array, $singleMemberNotiData);
                                }
                            }
                            LogHistory::insert($log_array);
                            Education::where('Product_general_id',$data['Product_general_id'] )->delete();
                        }else{
                            $education  = 1;
                        }
                    }
                    if($education!= null){
                        $education = Education::where('Product_general_id', $data['Product_general_id'])->get();
                        $Log_history = LogHistory::where('Product_general_id',  $data['Product_general_id'])
                                ->where('to_user',  $data['user_id'])
                                ->where('tag',"Education")
                                ->with('user_info','log_documents')
                                ->limit(3)
                                ->orderBy('id', 'desc')
                                ->get();
                        if(sizeof($Log_history)>0){
                            foreach($Log_history  as $value){
                                $value['LogType'] = "Update";
                                // if($value['user_id'] == $data['user_id'] )
                                // unset($value['user_info']);
                                $newData->push($value);
                            }
                        }
                        $log_post = Post::with('log_tags','log_documents')
                        ->whereHas('log_tags', function ($query){
                            $query->where('tag',"Education");
                        })->limit(1)->get();
                        if(sizeof($log_post)>0){
                            foreach($log_post  as $value){
                                $value['LogType'] = "Log";
                                $newData->push($value);
                            }
                        }
                        return response()->json([
                            'education' => $education,
                            'Log' => $newData,
                            'success' => true
                        ],200);
                    }else{
                        return response()->json([
                            'msg' => "Something went wrong .",
                            'success' => false
                        ],400);
                    }
            }           
            public function post_LegalInformation(Request $request){
                    $data= $request->all();
                    $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
                    $newData = new Collection([]);
                    $member_id = ProductMember::where('Product_general_id',$data['Product_general_id'] )->pluck('member_id');
                    $find = LegalInformation::where('Product_general_id',$data['Product_general_id'] )->first();
                    if(!$find){
                        return response()->json([
                            'msg' => "Invalid Request .",
                            'success' => false
                        ],400);
                    }
                    $prior = [];
                    $prior['right_status'] = $find->right_status ;
                    $prior['date'] = $find->date ;
                    $prior['url'] = $find->url ;
                    $prior['note'] = $find->note ;      
                    $new = [];
                    $new['right_status'] = $data['legal_info']['right_status'] ;
                    $new['date'] = $data['legal_info']['date'] ;
                    $new['url'] = $data['legal_info']['url'] ;
                    $new['note'] = $data['legal_info']['note'] ; 
                    $log = $this->make_logs_for_legal($find->toArray(), $new, $member_id, $data['Product_general_id'] );
                    // \Log::info($log);
                    $LegalInformation = LegalInformation::where('Product_general_id', $data['Product_general_id'])->update([
                                            'Product_general_id' => $data['Product_general_id'] ,
                                            'right_status' => $data['legal_info']['right_status'], 
                                            'date' => $data['legal_info']['date'], 
                                            'url' => $data['legal_info']['url'], 
                                            'extension' => $data['legal_info']['extension'], 
                                            'url_type' => $data['legal_info']['url_type'], 
                                            'note' => $data['legal_info']['note'],
                                        ]);
                    if($LegalInformation >0){
                        $LegalInformation =LegalInformation::where('Product_general_id',$data['Product_general_id'] )->first();
                        $Log_history = LogHistory::where('Product_general_id',  $data['Product_general_id'])
                                ->where('to_user',  $data['user_id'])
                                ->where('tag',"Legal")
                                ->with('user_info','log_documents')
                                ->limit(3)
                                ->orderBy('id', 'desc')
                                ->get();
                         if(sizeof($Log_history)>0){
                            foreach($Log_history  as $value){
                                $value['LogType'] = "Update";
                                // if($value['user_id'] == $data['user_id'] )
                                // unset($value['user_info']);
                                $newData->push($value);
                            }
                        }
                        $log_post = Post::with('log_tags','log_documents')
                        ->whereHas('log_tags', function ($query){
                            $query->where('tag',"Legal");
                        })->limit(1)->get();
                        if(sizeof($log_post)>0){
                            foreach($log_post  as $value){
                                $value['LogType'] = "Log";
                                $newData->push($value);
                            }
                        }
                        return response()->json([
                            'LegalInformation' => $LegalInformation,
                            'Log' => $newData,
                            'success' => true
                        ],200);
                    }else{
                        return response()->json([
                            'msg' => "Something went wrong .",
                            'success' => false
                        ],400);
                    }
            }
            public function post_Log(Request $request){
                    $data = $request->all();
                    $post = null;
                    $logPost = null;
                    $tag = null;
                    $file = null;
                    $user = JWTAuth::parseToken()->authenticate();
                    $user_id = $user->id;
                    $Product = ProductGeneral::where('id',$request->get('Product_general_id'))->first();
                    $member_id =  ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                    ->whereIn('status',[ "Current","PendingRemoval" ])
                    ->pluck('member_id');
                    $member_noti =  ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                    ->where('member_id','!=',$user_id )
                    ->whereIn('status',[ "Current","PendingRemoval" ])
                    ->pluck('member_id');
                    $post = Post::create([   
                        'Product_general_id' => $data['Product_general_id'],
                        'user_id' => $data['user_id'],
                        'description' => $data['description'],
                        'date' =>   $data['date'],
                    ]);
                    if($post){
                        foreach($member_id as $value2){
                            if($value2 == $data['user_id'] ){
                                $logPost = LogPost::create([
                                    'post_id' => $post->id ,
                                    'seen' => 1,
                                    'to_user' => $value2
                                ]);
                            }
                            else{
                                $logPost = LogPost::create([
                                    'post_id' => $post->id ,
                                    'seen' => 0,
                                    'to_user' => $value2
                                ]);
                            }
                        }
                        $logTag = $request->log_tags;
                        $logFile = $request->log_files;
                        foreach ($logTag as $value) {
                            $tag = LogTag::create ([
                                'post_id' => $post->id,
                                'tag' => $value,
                            ]);
                        }
                        if(sizeof($logFile)>0){
                            foreach($logFile as  $value) {
                                    $file = LogDocument::create ([
                                    'url_type' => $value['url_type'],
                                    'doc_name' => $value['doc_name'],
                                    'url'=> $value['url'],
                                    'extension'=> $value['extension'],
                                    'post_id' => $post->id,
                                    ]);
                            }
                        }
                        if( $post!= null ){
                            if(sizeof($logTag)>0){
                                if($tag == null){
                                    return response()->json([
                                        'msg' => " Something went wrong  .",
                                        'success' => true
                                    ],400);
                                }
                            }
                            if(sizeof($logFile)>0){
                                if($file == null){
                                    return response()->json([
                                        'msg' => "Something went wrong " ,
                                        'success' => true
                                    ],400);
                                }
                            }
                            $user = JWTAuth::parseToken()->authenticate();
                            $log  = Post::where('id',  $post->id)
                                    ->with('log_documents', 'log_tags')
                                    ->first();
                            $log['LogType'] = "Log";
                            $log['seen'] = 1;
                            $log['user_info'] = $user;
                            foreach($member_noti as $value){
                                $notification_create = Notification::create([
                                'user_id' => $value,
                                'Product_general_id' => $request->get('Product_general_id'),
                                'title' => "Log Created",
                                'message' => " A new log has been added at ".$Product->first_name." ".$Product->last_name."'s Product.",
                                'type' => "Log",
                                'is_seen' => 0
                                ]);
                                $notification =$this->notification_check($notification_create);
                            }
                            return response()->json([
                                'Log' => $log,
                                'success' => true
                            ],200);
                        }else{
                            return response()->json([
                                'msg' => "Something went wrong .",
                                'success' => true
                            ],400);
                        }
                    }
            }
            public function post_appointment(Request $request){
                    $from_time = null;
                    $to_time = null;
                    if($request->get('from_time') != null){ $from_time  = date("H:i", strtotime($request->get('from_time'))); }
                    if($request->get('to_time') != null){ $to_time  = date("H:i", strtotime($request->get('to_time'))); }
                    // $user = JWTAuth::parseToken()->authenticate();
                    $user = Auth::user();
                    $user_id = $user->id;
                    $Product = ProductGeneral::where('id',$request->get('Product_general_id'))->first();
                    $member_id =  ProductMember::where('Product_general_id', $request->get('Product_general_id'))
                    ->where('member_id', '!=', $user_id)
                    ->whereIn('status', ["Current", "PendingRemoval"])
                    ->pluck('member_id');
                    $men = null;
                    $Appointment = Appointment::create([  
                        'Product_general_id' => $request->get('Product_general_id'),
                        'doctor_id' => $request->get('doctor_id'),
                        'title' => $request->get('title'),
                        'type' => $request->get('type'),
                        'location' => $request->get('location'),
                        'message' => $request->get('message'), 
                        'from_date' => $request->get('from_date'), 
                        'from_time' => $from_time, 
                        'to_date' => $request->get('to_date'), 
                        'to_time' => $to_time, 
                    ]);
                    foreach($member_id as $value){
                        $notification_create = Notification::create([
                        'user_id' => $value,
                        'Product_general_id' => $request->get('Product_general_id'),
                        'title' => "Apointment Created",
                        'message' => "A new appointment has been added at ".$Product->first_name." ".$Product->last_name."'s Product.",
                        'type' => "Appointment",
                        'is_seen' => 0
                    ]);
                    $notification =$this->notification_check($notification_create);
                    }
                    $attachments = $request->get('attachments');
                    $people = $request->get('people');
                    if($Appointment){
                        if(sizeof($attachments)>0){
                            foreach ($attachments as  $value) {
                                $file = AppointmentAttachment::create ([
                                'doc_name' => $value['doc_name'],
                                'url_type' => $value['type'],
                                'url'=> $value['pic'],
                                'extension'=> $value['extension'],
                                'user_id' => $request->get('user_id'),
                                'Product_general_id' => $request->get('Product_general_id'),
                                'appointment_id' => $Appointment->id,
                                ]);
                            }
                        }
                        if($people){
                            if(sizeof($people)>0){
                                foreach ($people as  $value) {
                                    $men = ScheduledPeople::create ([
                                    'user_id' => $value,
                                    'appointment_id' => $Appointment->id,
                                    ]);
                                }
                            }  
                        }
                    }
                    if( $Appointment ){
                        if(sizeof($people)>0){
                            if($men == null){
                            return response()->json([
                                'msg' => " Something went wrong  .",
                                'success' => true
                            ],400);
                            }
                        }
                        if(sizeof($attachments)>0){
                        if($file == null){
                            return response()->json([
                                'msg' => "Something went wrong " ,
                                'success' => true
                            ],400);
                            }
                        }
                        $appointment = Appointment::select('*',DB::raw('monthname(from_date) as month'), DB::raw('YEAR(from_date) AS Year'), DB::raw('DAY(from_date) AS day' ), DB::raw('DAYNAME(from_date) AS weekDay' ) )
                                        ->where('id', $Appointment->id)
                                        ->first();
                        $appointment['month'] = $appointment['month']." ".$appointment['Year'];
                        $kyes = [
                            'Saturday' => "Sat",
                            'Sunday' => "Sun",
                            'Monday' => "Mon",
                            'Tuesday' => "Tue",
                            'Wednesday' => "Wed",
                            'Thursday' => "Thu",
                            'Friday' => "Fri",
                        ];
                        $appointment['weekDay'] = $kyes[$appointment['weekDay']] ;
                        if($appointment['from_time'] != null){
                            $appointment['from_time'] = date("g:iA", strtotime($appointment['from_time']));
                        }
                        if($appointment['to_time'] != null){
                            $appointment['to_time'] = date("g:iA", strtotime($appointment['to_time']));
                        }
                        if($appointment['from_date'] != null){
                            $time =new DateTime($appointment['from_date']); 
                            $appointment['from_date'] =  $time->format('m-d-Y');
                        }
                        if($appointment['to_date'] != null){
                            $time =new DateTime($appointment['to_date']); 
                            $appointment['to_date'] =  $time->format('m-d-Y');
                        }
                        return response()->json([
                            'appointment' => $appointment,
                            'success' => true
                        ],200);
                        }else{
                    return response()->json([
                        'msg' => "Something went wrong .",
                        'success' => false
                    ],400);
                }
            }
            public function edit_appointment(Request $request){
                $from_time = null;
                $to_time = null;
                 
                if($request->get('from_time') != null){ $from_time  = date("H:i", strtotime($request->get('from_time'))); }
                if($request->get('to_time') != null){ $to_time  = date("H:i", strtotime($request->get('to_time'))); }

                $appointment = Appointment::where('id', $request->get('id'))->update([  
                    'Product_general_id' => $request->get('Product_general_id'),
                    'doctor_id' => $request->get('doctor_id'),
                    'title' => $request->get('title'),
                    'type' => $request->get('type'),
                    'location' => $request->get('location'),
                    'message' => $request->get('message'), 
                    'from_date' => $request->get('from_date'), 
                    'from_time' => $from_time, 
                    'to_date' => $request->get('to_date'), 
                    'to_time' => $to_time, 
                ]);
                $attachments = $request->get('attachments');
                $people = $request->get('people');
                if($appointment){
                    if(sizeof($attachments)>0){
                        $delete = AppointmentAttachment::where('appointment_id', $request->get('id'))->delete();
                        foreach ($attachments as  $value) {
                            $file = AppointmentAttachment::create ([
                            'doc_name' => $value['doc_name'],
                            'url_type' => $value['url_type'],
                            'url'=> $value['url'],
                            'extension'=> $value['extension'],
                            'user_id' => $request->get('user_id'),
                            'Product_general_id' => $request->get('Product_general_id'),
                            'appointment_id' => $request->get('id'),
                            ]);
                        }
                    }
                    if($people){
                        $delete = ScheduledPeople::where('appointment_id', $request->get('id'))->delete();
                        if(sizeof($people)>0){
                            foreach ($people as  $value) {
                                $men = ScheduledPeople::create ([
                                'user_id' => $value,
                                'appointment_id' => $request->get('id'),
                                ]);
                            }
                        }  
                    }
                    $appointment = Appointment::select('*',DB::raw('monthname(from_date) as month'), DB::raw('YEAR(from_date) AS Year'), DB::raw('DAY(from_date) AS day' ), DB::raw('DAYNAME(from_date) AS weekDay' ) )
                                        ->where('id', $request->get('id'))
                                        ->first();
                        $appointment['month'] = $appointment['month']." ".$appointment['Year'];  
                        $kyes = [
                            'Saturday' => "Sat",
                            'Sunday' => "Sun",
                            'Monday' => "Mon",
                            'Tuesday' => "Tue",
                            'Wednesday' => "Wed",
                            'Thursday' => "Thu",
                            'Friday' => "Fri",
                        ];
                        $appointment['weekDay'] = $kyes[$appointment['weekDay']] ;
                        $appointment['from_time'] = date("g:iA", strtotime($appointment['from_time']));
                        $appointment['to_time'] = date("g:iA", strtotime($appointment['to_time']));
                        if($appointment['from_date'] != null){
                            $time =new DateTime($appointment['from_date']); 
                            $appointment['from_date'] =  $time->format('m-d-Y');
                        }
                        if($appointment['to_date'] != null){
                            $time =new DateTime($appointment['to_date']); 
                            $appointment['to_date'] =  $time->format('m-d-Y');
                        }
                        return response()->json([
                            'appointment' => $appointment,
                            'success' => true
                        ],200);
                }
                else{
                    return response()->json([
                        'msg' => "Something went wrong .",
                        'success' => false
                    ],400);
                }
            }
            public function delete_appointment(Request $request){
                $delete = Appointment::where('id', $request->get('id'))->delete();
                if($delete !=0){
                    return response()->json([
                        'success' => true
                    ],200);
                }else{
                    return response()->json([
                        'success' => false
                    ],400);
                }
            }
            public function get_user_profile(Request $request){
                $User = User::where('id',$request->id)->first();
                $Product_general_id_current = ProductMember::where('member_id',$request->id)
                                        ->where('status', 'Current')
                                        ->orWhere('status', 'PendingRemoval')
                                        ->pluck('Product_general_id');
                $Current  = ProductGeneral::WhereIn('id', $Product_general_id_current)
                                    ->with('placements')
                                    ->get();    
                $Product_general_id_former = ProductMember::where('member_id',$request->id)
                                        ->where('status', 'Former')
                                        ->pluck('Product_general_id');
                $Former  = ProductGeneral::WhereIn('id', $Product_general_id_former)
                                    ->with('placements')
                                    ->get();    
                $newData = new Collection([]);
                if(sizeof($Current)>0){
                    foreach ($Current as $key => $value) {
                        $value = $this->age_count($value);
                        $value['status'] = "Current";
                    }
                }
                if(sizeof($Current)>0){
                    foreach($Current  as $value){
                        $value = $this->age_count($value);
                        $value['status'] = "Current";
                        $newData->push($value);
                    }
                }
                if(sizeof($Former)>0){
                    foreach($Former  as $value){
                        $value = $this->age_count($value);
                        $value['status'] = "Former";
                        $newData->push($value);
                    }
                }
                return response()->json([
                    'User' => $User,
                    'Related_Product' => $newData,
                    'success' => true
                ],200);
            }
            public function profile_update(Request $request){
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
                if($request->get('email')){
                    if($request->get('email') != $user->email){
                        $validator = Validator::make($request->all(), [
                            'email' => 'required|string|email|max:255|unique:users',
                         ]);
                        if($validator->fails()){
                            return response()->json($validator->errors(), 400);
                        }
                    }
                }
                $User = User::where('id',$user_id)->update([
                    'first_name' => $request->get('first_name'),
                    'last_name' => $request->get('last_name'),
                    'optional_email' => $request->get('optional_email'),
                    'organization' => $request->get('organization'),
                    'city' => $request->get('city'),
                    'state' => $request->get('state'),
                    'email' => $request->get('email'),
                    'mobile' => $request->get('mobile'),
                    'home_phone' => $request->get('home_phone'),
                    'office_phone' => $request->get('office_phone'),
                    'user_type' => $request->get('user_type'),
                ]);
                if($User == 1){
                    $result = User::where('id',$user_id)->first();
                    return response()->json([
                        'User' => $result,
                        'success' => true
                    ],200);
                }
                else{
                    return  response()->json([
                        'msg' => "Something went wrong ! Please try again later.",
                        'success' => false
                    ],200);
                }
                
            }
            public function profile_picture_update(Request $request){
                    $user = JWTAuth::parseToken()->authenticate();
                    $user_id = $user->id;
                    $validator = Validator::make($request->all(), [
                        'profile_picture' => 'required|file|image|mimes:jpeg,png,jpg|max:2048'
                    ]);
                    if($validator->fails()){
                        return response()->json([
                            'msg'    => 'Error',
                            'errors' => $validator->errors(),
                            'success' => false
                        ], 400);
                    }
                    request()->file('profile_picture')->store('uploads');
                    $pic= $request->profile_picture->hashName();
                    // $pic= "/uploads/$pic";
                    $url = env('APP_URL');
                    $pic= "$url/uploads/$pic";
                    $upload  = User::where('id',$user_id)->update([
                        'profile_picture' => $pic
                    ]); 
                    if($upload == 0){
                        return  response()->json([
                            'msg' => "Something went wrong ! Please try again later.",
                            'success' => false
                        ],400);
                    }else{
                        $result = User::where('id', $user_id)->first();
                        return  response()->json([
                            'User' => $result,
                            'success' => true
                        ],200);
                    }
            }
            public function Product_profile_picture_upload(Request $request){
                            $data = $request->all();
                            $validator = Validator::make($request->all(), [
                                'profile_picture' => 'required|file|image|mimes:jpeg,png,jpg|max:2048'
                            ]);
                            // if($validator->fails()){
                            //     return response()->json($validator->errors(), 400);
                            // }
                            if($validator->fails()){
                                return response()->json([
                                    'msg'    => 'Error',
                                    'errors' => $validator->errors(),
                                    'success' => false
                                ], 400);
                            }
                            request()->file('profile_picture')->store('uploads');
                            $pic= $request->profile_picture->hashName();
                            $url = env('APP_URL');
                            $pic= "$url/uploads/$pic";
                            // $pic= "/uploads/$pic";
                            $upload  = ProductGeneral::where('id', $data['Product_general_id'])->update([
                                'profile_picture' => $pic
                            ]); 
                            if($upload == 0){
                                return  response()->json([
                                    'msg' => "Something went wrong! Please try again later.",
                                    'success' => false
                                ],400);
                            }
                           else{
                            $result = ProductGeneral::select('profile_picture')->where('id', $data['Product_general_id'])->first();
                            return  response()->json([
                                'result' => $result,
                                'success' => true
                            ],200);
                             
                           }
            }
            public function upload_file(Request $request){
                     // $type = Storage::mimeType($filename);
                    $validator = Validator::make($request->all(), [
                        'file' => 'required|file|max:25000'
                    ]);
                    if($validator->fails()){
                        return response()->json([
                            'msg'    => 'Error',
                            'errors' => $validator->errors(),
                            'success' => false
                        ], 200);
                        
                    }
                    $extension = $request->file('file')->extension();
                    $name = $request->file('file')->getClientOriginalName();
                    $type = $request->file('file')->getMimeType();
                    request()->file('file')->store('uploads');
                    $pic= $request->file->hashName();
                    $url = env('APP_URL');
                    $pic= "$url/uploads/$pic";
                    // $pic= "/uploads/$pic";
                    return  response()->json([
                        'name' => $name,
                        'pic' => $pic,
                        'type' => $type,
                        'extension' => $extension,
                        'success' => true
                    ],200);
                    
            }
            public function upload_register_image(Request $request){
                     // $type = Storage::mimeType($filename);
                    $validator = Validator::make($request->all(), [
                        'file' => 'required|file|max:25000'
                    ]);
                    if($validator->fails()){
                        return response()->json([
                            'msg'    => 'Error',
                            'errors' => $validator->errors(),
                            'success' => false
                        ], 200);
                        
                    }
                    $extension = $request->file('file')->extension();
                    $name = $request->file('file')->getClientOriginalName();
                    $type = $request->file('file')->getMimeType();
                    request()->file('file')->store('uploads');
                    $pic= $request->file->hashName();
                    $url = env('APP_URL');
                    $pic= "$url/uploads/$pic";
                    // $pic= "/uploads/$pic";
                    return  response()->json([
                        'name' => $name,
                        'pic' => $pic,
                        'type' => $type,
                        'extension' => $extension,
                        'success' => true
                    ],200);
                    
            }
            public function conversation_msg(Request $request){
                $data = $request->all();
                $user = $data['user_id'];
                $result = Chat::where('Product_general_id', $data['Product_general_id'])
                        ->with('user_details', 'mention_list', 'mention_list.user')
                        ->limit(15)
                        ->orderBy('id', 'desc')
                        ->get();
                $chat_id =  Chat::where('Product_general_id', $data['Product_general_id'])
                ->where('sender', '!=', $user)->pluck('id'); 
                foreach($chat_id as $value){
                 $seen_all = ConversationChatSeen::updateOrCreate([
                                    'chat_id' => $value,
                                    'user_id' => $user
                                ]); 
                }
                if(sizeof($result)>0){
                    foreach($result as $value){
                        $value['files'] = json_decode($value['files']);
                    }
                }
                if($result){
                    return  response()->json([
                        'data' => $result,
                        'success' => true
                    ],200); 
                }
                else{
                    return  response()->json([
                        'msg' => "Something went wrong ! Please try again later.",
                        'success' => false
                    ],400);
                }
            }
            public function sendPush($id, $Product_name,  $data){
                $ids[0] = User::where('id',$id)->value('device_id');
                $notification = $data;
                $url = 'https://fcm.googleapis.com/fcm/send';
                $fields = array (
                        'registration_ids' => $ids,
                        'data' => array (
                                'title' => "New Message",
                                "message" =>  $data,
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ),
                        'notification' => array (
                                'title' => "New Message",
                                "body" => "A new message has arrived at ".$Product_name."'s Product.",                 
                                "sound" => true, 
                                "badge" => 1,
                        ),
                        'time_to_live' => 6000,
                );
                $fields = json_encode ( $fields );
                $headers = array (
                        'Authorization: key=' . "AAAABj6MfPU:APA91bGcUQoIeAMfUfrb7dka-Uk2KFLjTCg3Vbyeg-dB0iUq5oowssu-VgBLIFEcZkVmtpAC4drKpxMdkbXAtdwEh9-uvq-GEBEFj7f4D5G4UofjhwoMF41eQg-c9ib2fVxxw1700SYH",
                        'Content-Type: application/json'
                );
                $ch = curl_init ();
                curl_setopt ( $ch, CURLOPT_URL, $url );
                curl_setopt ( $ch, CURLOPT_POST, true );
                curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
                curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
                curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
                $result = curl_exec ( $ch );
                curl_close ( $ch );
                if($result){
                    return $result;
                }
            }
            public function mentionPush($id, $Product_name,  $mention_user_name){
                $ids[0] = User::where('id',$id)->value('device_id');
                $url = 'https://fcm.googleapis.com/fcm/send';
                $fields = array (
                        'registration_ids' => $ids,
                        'data' => array (
                                'title' => "Mentioned In Chat",
                                "message" =>  $mention_user_name,
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ),
                        'notification' => array (
                                'title' => "Mentioned In Chat",
                                "body" => $mention_user_name. " mentioned you at ".$Product_name."'s Product.",                 
                                "sound" => true, 
                                "badge" => 1,
                        ),
                        'time_to_live' => 6000,
                );
                $fields = json_encode ( $fields );
                $headers = array (
                        'Authorization: key=' . "AAAABj6MfPU:APA91bGcUQoIeAMfUfrb7dka-Uk2KFLjTCg3Vbyeg-dB0iUq5oowssu-VgBLIFEcZkVmtpAC4drKpxMdkbXAtdwEh9-uvq-GEBEFj7f4D5G4UofjhwoMF41eQg-c9ib2fVxxw1700SYH",
                        'Content-Type: application/json'
                );
                $ch = curl_init ();
                curl_setopt ( $ch, CURLOPT_URL, $url );
                curl_setopt ( $ch, CURLOPT_POST, true );
                curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
                curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
                curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
                $result = curl_exec ( $ch );
                curl_close ( $ch );
                if($result){
                    return $result;
                }
            }
            public function msg_insert(Request $request){
                $data = $request->all();
                $files = $data['files'];
                $mention = $data['mention'];
                $files = json_encode($files);
                $user = JWTAuth::parseToken()->authenticate();
                $user_id = $user->id;
                $Product = ProductGeneral::where('id',$request->get('Product_general_id'))->first();
                $Product_name = $Product->first_name." ".$Product->last_name;
                $conversation = Conversation::where('Product_general_id', $data['Product_general_id'])->first();
                $member_id = ConversationMember::where('conversation_id',$conversation->id )
                            ->where('user_id', '!=', $user_id)
                             ->pluck('user_id');
                // $message = explode(' ',$data['message']);
                // foreach($message as $message_string){
                //     if (str_starts_with($message_string, '@')) {
                //         $trimmed = trim($message_string,"@");
                //     }
                // }
                $create = Chat::create([
                                'Product_general_id'=> $data['Product_general_id'],
                                'message'=> $data['message'],
                                'sender'=> $data['sender'],
                                'conversation_id'=> $conversation->id,
                                'files'=> $files
                            ]);
                if($create){
                    if(sizeof($mention)>0){
                        foreach ($mention as $value){
                            // push and email noti to user
                            $mention_create = Mention::create([
                                'chat_id' => $create->id,
                                'mention_id' => $value
                            ]);
                            $mention_user = User::where('id', $value)->first();
                            $mention_from = User::where('id', $data['sender'])->first();
                            $mention_user_from = $mention_from->first_name." ".$mention_from->last_name;
                            $checkPush = NotificationSetting::where('user_id', $value)->where('push_for_mentioned_in_chat', 1)->count();
                            if($checkPush > 0){
                                  $notification = $this->mentionPush($value, $Product_name, $mention_user_from);    
                            }
                            $checkEmail = NotificationSetting::where('user_id', $value)->where('email_for_mentioned_in_chat', 1)->count();
                            if($checkEmail > 0){
                                $email_data = [
                                    "Product_name" => $Product_name,
                                    "mention_user_from" => $mention_user_from,
                                    "msg" => $data['message']
                                ];
                                try{
                                    $mail = Mail::to($mention_user->email )->send(new MentionInProduct($email_data));
                                }catch (\Exception $e) {
                                    return 0;
                                }
                                
                            }
                        }
                    }
                    $result = Chat::where('id', $create->id)->with('mention_list', 'mention_list.user')->first();
                    if($result){
                        $result->files = json_decode($result->files);
                        $push_data = Chat::where('id', $result->id)->with('mention_list', 'mention_list.user','user_details')->first();
                        $push_data->files = json_decode($push_data->files);
                        $push_data = json_encode($push_data);
                        foreach($member_id as $value){
                            $notification = $this->sendPush($value, $Product_name, $push_data);
                        }
                        $seen = ConversationChatSeen::create([
                            'chat_id' => $result->id,
                            'user_id' => $user_id
                        ]);
                    }                    
                    return  response()->json([
                        'data' => $result,
                        'success' => true
                    ],200); 
                }
                else{
                    return  response()->json([
                        'msg' => "Something went wrong ! Please try again later.",
                        'success' => false
                    ],400);
                }
            }
            public function more_chat(Request $request){
                $data = $request->all();
                if(!$data){
                    return  response()->json([
                        'msg' => "Something went wrong ! Please try again later.",
                        'success' => false
                    ],400);
                }
                $user = $data['user_id'];
                $result = Chat::where('Product_general_id', $data['Product_general_id'])
                                ->where('id', '<', $data['id'])
                                ->with('mention_list', 'mention_list.user','user_details')
                                ->limit(15)
                                ->orderBy('id', 'desc')
                                ->get();
                if(sizeof($result)>0){
                    foreach($result as $value){
                        $value['files'] = json_decode($value['files']);
                    }
                }
                if($result){
                    return  response()->json([
                        'data' => $result,
                        'success' => true
                    ],200); 
                }
                else{
                    return  response()->json([
                        'msg' => "Something went wrong ! Please try again later.",
                        'success' => false
                    ],400);
                }

            }
            public function more_log(Request $request){
                $data = $request->all();
                $user = $data['user_id'];
                $newData = new Collection([]);
                $log_tags = new Collection([]);
                $log_documents = new Collection([]);
                $Log_appointment = LogAppointment::where('Product_general_id',  $data['Product_general_id'])
                    ->where('to_user',  $data['user_id'])
                    ->with('user_info');
                if($data['appointment_id'] != null){
                    $Log_appointment = $Log_appointment->where('id', '<', $data['appointment_id'])
                    ->limit(10)
                    ->orderBy('id', 'desc')
                    ->get();
                }else{
                    $Log_appointment = []; 
                }
                if(sizeof($Log_appointment)>0){
                    foreach($Log_appointment  as $value){
                        $value['LogType'] = "Event";
                        $value['log_tags'] = $log_tags;
                        $value['log_documents'] = $log_documents;
                        $newData->push($value);
                    }
                }
                $Log_history = LogHistory::where('Product_general_id',  $data['Product_general_id'])
                                ->where('to_user',  $data['user_id'])
                                ->with('user_info','log_documents');
                if($data['history_id'] != null){
                    $Log_history = $Log_history->where('id', '<', $data['history_id'])
                    ->limit(10)
                    ->orderBy('id', 'desc')
                    ->get();
                }
                else{
                    $Log_history = []; 
                }
                if(sizeof($Log_history)>0){
                    foreach($Log_history  as $value){
                        $value['LogType'] = "Update";
                        if($value['user_id'] == $data['user_id'] )
                        $value['log_tags'] = $log_tags;
                        // $value['log_documents'] = $log_documents;
                        // unset($value['user_info']);
                        $newData->push($value);
                    }
                }

                $Log_posts = Post::where('Product_general_id',  $data['Product_general_id'])
                                ->with('log_documents', 'log_tags', 'log_to_users', 'log_to_users.user_info')
                                ->with(['log_to_users' => function ($q)  use ($user) {
                                            $q->where('to_user',$user);
                                        }]);
                if($data['log_id'] != null){
                    $Log_posts = $Log_posts->where('id', '<', $data['log_id'])
                    ->limit(10)
                    ->orderBy('id', 'desc')
                    ->get();
                }
                else{
                    $Log_posts = []; 
                }
                if(sizeof($Log_posts)>0){
                    foreach($Log_posts  as $value){
                        $value['LogType'] = "Log";
                        if($value['log_to_users']){
                            $value['seen'] = $value['log_to_users'][0]['seen'];
                            $value['user_info'] = $value['log_to_users'][0]['user_info'];
                        } 
                        unset($value['log_to_users']);
                        $newData->push($value);
                    }
                }
                return response()->json([
                    'Log' => $newData,
                    'success' => true
                ],200); 

            }
    }