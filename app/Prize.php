<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    protected $table = "prizes";
    protected $primaryKey = "prize_id";
    protected $guarded = [];
    protected $hidden = ["event_id"];
}
