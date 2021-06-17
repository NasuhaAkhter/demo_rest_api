<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LegalInformation extends Model
{
    protected $fillable = [
        'case_general_id',	'right_status',	'date',	'url', 'url_type', 'extension','note'
    ];
}
