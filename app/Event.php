<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Event extends Model
{
    protected $table = "events";
    protected $primaryKey = "event_id";
    protected $guarded = [];
    protected $hidden = ["event_type", "event_source", "network_id", "site_id"];
    protected $appends =["total_sold_count"];
    public function source()
    {
        return $this->hasOne("App\EventSource", "event_source_id", "event_source");
    }

    public function type()
    {
        return $this->hasOne("App\EventType", "event_type_id", "event_type");
    }

    public function prizes()
    {
        return $this->hasMany("App\Prize", "event_id", "event_id")
            ->orderBy("prize_amount", "DESC");
    }

    public function currency()
    {
        return $this->hasOne("App\Currency", "currency_id", "currency_id");
    }

    public function rule()
    {
        return $this->hasOne("App\EventRule","event_id","event_id");
    }

    public function tickets()
    {
        return $this->hasMany("App\Ticket","event_id","event_id");
    }

    public function getTotalSoldCountAttribute()
    {
        $count = DB::table("tickets")->where("event_id",$this->attributes["event_id"])->count();
        return $count;
    }
}
