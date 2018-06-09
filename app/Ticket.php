<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;
    protected $primaryKey = "ticket_id";
    protected $table = "tickets";
    protected $guarded = [];

    public function event()
    {
        return $this->hasOne("App\Event","event_id","event_id")
            ->select(["event_id","event_name","event_end_date","event_ticket_price"]);
    }
}
