<?php

namespace App; 

use Illuminate\Database\Eloquent\Model;

class PhysicalCondition extends Model
{
    protected $fillable = [
        'case_general_id',	'title','type',	'description',	'date'
    ];
}
