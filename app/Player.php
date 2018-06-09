<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use SoftDeletes;
    protected $table = "players";
    protected $primaryKey = "player_id";
    protected $guarded =[];

    public function network()
    {
        return $this->belongsTo("App\Network","network_id","network_id");
    }

    public function site()
    {
        return $this->belongsTo("App\Site","site_id","site_id");
    }

}
