<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = "promotions";
    protected $primaryKey = "promotion_id";
    protected $guarded = [];

    public function event()
    {
        return $this->hasOne("App\Event","event_id","event_id");
    }


}
