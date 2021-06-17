<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CasePlacement extends Model
{
    protected $fillable = [
        'case_general_id',	'placement_name',	'placement_type','date','phone_number',	'address',	'email'
    ];
}
