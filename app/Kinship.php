<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Kinship extends Model
{
    protected $fillable = [
        'case_general_id',	'name',	'contact_name', 'address',	'phone_number',	'email', 'relation',	'note'
    ];
}
