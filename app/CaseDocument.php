<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CaseDocument extends Model  
{
    protected $fillable = [
        'case_general_id','url_type','url','extension','user_id','doc_type','doc_name'	
    ];
}
