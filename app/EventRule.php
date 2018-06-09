<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventRule extends Model
{
    protected $table = "event_rules";
    protected $primaryKey = "event_rule_id";
    protected $guarded = [];
}
