<?php

namespace App; 

use Illuminate\Database\Eloquent\Model;

class MedicinTakingMethod extends Model
{
    protected $fillable = [ 
        'medication_id', 'time'
    ];
}
