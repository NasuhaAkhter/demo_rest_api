<?php

namespace App; 

use Illuminate\Database\Eloquent\Model;

class Allergy extends Model
{
    protected $fillable = [
        'case_general_id',	'allergy_type',	'symptoms'	,'severity'
    ];
}
