<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventSource extends Model
{
    protected $table = "event_sources";
    protected $primaryKey = "event_source_id";
    protected $guarded = [];
    protected $hidden = ["created_at","updated_at","deleted_at"];

}
