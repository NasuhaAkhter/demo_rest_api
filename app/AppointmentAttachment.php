<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppointmentAttachment extends Model
{
    protected $fillable = [
        'appointment_id','doc_name','url_type','url','extension'
    ];
}
