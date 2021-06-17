<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogTag extends Model
{
    protected $fillable = [
        	'post_id',	'tag'	
    ];
}
  