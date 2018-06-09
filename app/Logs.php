<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    protected $table = "request_logs";
    protected $primaryKey = "id";
    protected $guarded = [];
}
