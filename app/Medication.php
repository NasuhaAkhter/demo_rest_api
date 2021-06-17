<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
 
class Medication extends Model  
{
    protected $fillable = [ 
        'case_general_id','medicin_name','power','taking_method','cause','time', 'note','doctor_id'
    ];
    public function medicin_taking_time(){
        return $this->hasMany('App\MedicinTakingMethod', 'medication_id');
    }
}
