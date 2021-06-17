<?php

namespace App;

use Illuminate\Database\Eloquent\Model;  
 
class Sibling extends Model
{
    protected $fillable = [
        'case_general_id','name','address','phone_number','email','gender','relation','note','status',
        'placement_type'
        ,'birthday','contact_name'	
    ];
    public function countSiblings(){
        return $this->hasOne('App/Models/Sibling')->select( Database.raw('count(id) as total'));
    }
     
}
