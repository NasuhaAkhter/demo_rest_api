<?php

namespace App;
use Illuminate\Support\Carbon ;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Auth;

class CaseGeneral extends Model
{
    protected $fillable = [
        'first_name','last_name','birthday', 'gender','profile_picture','place_of_birth', 'race','ethnicity','ssn','user_id'
    ];
    // public function age($query)
    // { 
    //     $today = date("Y/m/d");
    //     return $query->whereDate($today,'birthday');
         
    // }
    // public function age(){
    //     $today = date("Y/m/d");
    //     $birthday = 'birthday';
    //     // $date1=date_create($today);
    //     // $date2=date_create('birthday');
    //     // $diff=date_diff($date1,$date2);
    //     return $birthday;
    // }
    public function pending_closes(){
        return $this->hasMany('App\Close');
    } 
    public function pending_removals(){
        return $this->hasMany('App\Removal');
    }
    public function case_request(){
        return $this->hasMany('App\JoinRequestsFromUser') ;
    }
    public function withdrawal_request(){
        return $this->hasMany('App\MemberRequestToUser');
    }
    public function case_documents(){
        return $this->hasMany('App\CaseDocument', 'case_general_id');
    }
    public function placements(){
        return $this->hasOne('App\CasePlacement', 'case_general_id');
    }
    public function people_of_placements(){
        return $this->hasMany('App\PlacementPeople', 'case_general_id');
    }
    public function immunizations(){
        return $this->hasOne('App\Immunization', 'case_general_id');
    }
    // public function insurances(){
    //     return $this->hasOne('App\Insurance', 'case_general_id');
    // }
    // public function insurance_document(){
    //     return $this->hasOne('App\InsuranceDocument', 'case_general_id');
    // }
    public function activities(){
        return $this->hasMany('App\ActivitiesAndInterest', 'case_general_id');
    }
    public function siblings(){
        return $this->hasMany('App\Sibling', 'case_general_id');                    
    }
    public function language(){
        return $this->hasMany('App\Language', 'case_general_id');                    
    }
    public function races(){
        return $this->hasMany('App\Race', 'case_general_id');                    
    }
    public function legalInformations(){
        return $this->hasOne('App\LegalInformation', 'case_general_id');
    }
    public function kinships(){
        return $this->hasMany('App\Kinship', 'case_general_id');
    }
    public function logs(){
        return $this->hasMany('App\LogPost', 'case_general_id')->limit(3);
    }
    public function user_info(){
        return $this->belongsTo('App\User', 'user_id');
    }
    public function member_list(){
        return $this->hasMany('App\CaseMember', 'case_general_id', 'id')
        ->where('status',"Current")
        ->orWhere('status', "PendingRemoval");
    }
    // public function notifications(){
    //     $user = JWTAuth::parseToken()->authenticate();
    //     // $user_id = Auth::user()->id;
    //     return $this->hasMany('App\Notification', 'case_general_id', 'id')
    //                 ->where('user_id', $user->id);
    // } 
    // public function log_documents(){
    //     return $this->hasMany('App\LogDocument', 'case_general_id');
    // }
}
