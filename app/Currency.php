<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = "currencies";
    protected $primaryKey = "currency_id";
    protected $hidden = [];
}
