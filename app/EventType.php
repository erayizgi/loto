<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventType extends Model
{
    use SoftDeletes;
    protected $table="event_types";
    protected $primaryKey = "event_type_id";
    protected $guarded = [];
    protected $hidden = ["created_at","updated_at","deleted_at"];
}
