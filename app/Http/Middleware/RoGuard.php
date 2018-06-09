<?php
/**
 * Created by PhpStorm.
 * User: erayizgi
 * Date: 22.05.2018
 * Time: 22:47
 */

namespace App\Http\Middleware;


use App\Libraries\Res;
use Closure;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class RoGuard
{
    public function handle($request, Closure $next, ...$permission)
    {
        $user = $request->user();
        $network_id = $user->network_id;
        if($user->network_id != NULL){
            $user_has_role = DB::table('permissions')
                ->leftJoin('role_has_permissions', 'role_has_permissions.permission_id', '=', 'permissions.permission_id')
                ->leftJoin('user_has_roles', 'user_has_roles.role_id', '=', 'role_has_permissions.role_id')
                ->where('user_id', $user->id)
                ->whereIn('permissions.name', $permission)
                ->get();
        }else{
            $user_has_role = DB::table("permissions")
                ->leftJoin('role_has_permissions', 'role_has_permissions.permission_id', '=', 'permissions.permission_id')
                ->leftJoin('user_has_roles', 'user_has_roles.role_id', '=', 'role_has_permissions.role_id')
                ->where('user_id', $user->id)
                ->whereIn('permissions.name', $permission)
                ->get();
        }
        if(count($user_has_role) < count($permission)){
            return Res::fail(Response::HTTP_FORBIDDEN,"Not authorized!");
        }
        return $next($request);
    }

}
