<?php

namespace App\Http\Middleware;

use App\Libraries\Res;
use App\Player;
use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoPlayer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
//        $user = $request->user();
//        return "eray";
        if($request->has("pt")){
            $player = Player::where("player_token",$request->pt)->first();
            $user = User::where("users.network_id",$player->network_id)
                ->where("users.site_id",$player->site_id)
                ->join("user_has_roles","user_has_roles.user_id","=","users.id")
                ->where("user_has_roles.role_id","4")->select("users.*")->first();
//            var_dump($user); die;
            $request->request->add(["player" => $player]);
            Auth::setUser($user);
            return $next($request);
        }else{
            return Res::fail(Response::HTTP_FORBIDDEN,"Forbidden");
        }
    }
}
