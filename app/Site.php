<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use SoftDeletes;
    protected $table = "sites";
    protected $primaryKey = "site_id";
    protected $guarded =[];

    public function network()
    {
        return $this->hasOne("App\Network","network_id","network_id");
    }
}
