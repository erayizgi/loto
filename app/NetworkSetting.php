<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetworkSetting extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    protected $table = "network_settings";
    protected $primaryKey = "setting_id";
}
