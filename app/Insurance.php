<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
 
class Insurance extends Model  
{
    protected $fillable = [
        'case_general_id',	'plan_name',	'id_number',	'rx_bin',	'rx_group',	'rx_pcn',	'phone_number'
    ];
    public function Front_Photo(){ 
        return $this->hasOne('App\InsuranceDocument', 'insurance_id')->where('pic_type', 'Front_Photo');
    }
    public function Back_Photo(){
        return $this->hasOne('App\InsuranceDocument', 'insurance_id')->where('pic_type', 'Back_Photo');
    }
}
 