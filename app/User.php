<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'network_id', 'site_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    protected $appends = ["roles"];
    public function getRolesAttribute()
    {
        $roles = DB::table('user_has_roles')
            ->where('user_id',$this->attributes['id'])
            ->leftJoin('roles','roles.role_id','=','user_has_roles.role_id')
            ->select('roles.*','user_has_roles.network_id','user_has_roles.site_id')->first();
        return  $roles;
    }
}
