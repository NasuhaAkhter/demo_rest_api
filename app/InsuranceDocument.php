<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
 
class InsuranceDocument extends Model   
{
    protected $fillable = [
        'insurance_id','pic_type','url_type','url','extension'
    ]; 
}
