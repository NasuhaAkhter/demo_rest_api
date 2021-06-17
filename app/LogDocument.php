<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogDocument extends Model
{
    protected $fillable = [
        'post_id','doc_name','url_type','url','extension'
    ];
}
