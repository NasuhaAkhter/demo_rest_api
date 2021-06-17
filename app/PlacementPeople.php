<?php

namespace App;

use Illuminate\Database\Eloquent\Model; 

class PlacementPeople extends Model
{
    protected $fillable = [
        'case_general_id','user_id'	,'email' 
    ];
    public function people_info(){
        return $this->belongsTo('App\User', 'user_id');
    }
}
