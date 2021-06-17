<?php
 
namespace App; 

use Illuminate\Database\Eloquent\Model;
class Doctor extends Model 
{
    protected $fillable = [
        'case_general_id','doctor_type'	,'department','name','profile_picture','address','phone_number'	
    ];
}
