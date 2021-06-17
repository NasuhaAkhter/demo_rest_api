<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    protected $fillable = [
        'case_general_id',	'school_name',	'address',	'phone',	'grade',	'note'	
    ];
}
