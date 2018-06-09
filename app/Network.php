<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Network extends Model
{
    use SoftDeletes;
    protected $table = "networks";
    protected $primaryKey = "network_id";
    protected $guarded =[];

    public function settings()
    {
        return $this->hasMany("App\NetworkSetting","network_id","network_id");
    }
}
