<?php

namespace App;

use Illuminate\Database\Eloquent\Model; 

class Immunization extends Model
{
    protected $fillable = [
        'case_general_id',	'title',	'description',	'date'
    ];
}
