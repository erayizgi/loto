<?php

namespace App\Http\Controllers;

use App\Event;
use App\Libraries\Leveling;
use App\Libraries\Res;
use App\Libraries\TReq;
use App\Player;
use App\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PlayersController extends Controller
{
    public function listBySite(Request $request, $site_id)
    {
        try {
            $query = TReq::multiple($request, Player::class);
            $fields = [
                "company_id" =>
                    [
                        "field" => "network_id",
                        "value" => $request->user()->network_id
                    ],
                "branch_id" =>
                    [
                        "field" => "site_id",
                        "value" => $request->user()->site_id
                    ]
            ];
            $query2 = Leveling::make($request, $query["query"], $fields);
            $data = $query2->where("site_id", $site_id)->with("site")->get();
            if (count($data) == 0) {
                throw new \Exception(
                    "Oyuncu bulunamadı ya da bu siteye ait oyuncuları görüntülemek için yeterli yetkiye sahip değilsiniz",
                    Response::HTTP_BAD_REQUEST
                );
            }
            return Res::success(Response::HTTP_OK, "success", $data);
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    public function authorizePlayer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "player_identifier" => "required"
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator, Response::HTTP_BAD_REQUEST, $validator->errors());
            }
            $player = Player::where("player_identifier", $request->player_identifier)
                ->first();
            if (!$player) {
                $player = Player::create([
                    "player_identifier" => $request->player_identifier,
                    "network_id" => $request->user()->network_id,
                    "site_id" => $request->user()->site_id,
                    "player_token" => Crypt::encrypt($request->user()->network_id . "-" . $request->user()->site_id . "-" . $request->player_identifier . "-" . now())
                ]);
                $token = $player->player_token;
            } else {
                $token = Crypt::encrypt($request->user()->network_id . "-" . $request->user()->site_id . "-" . $request->player_identifier . "-" . now());
                $player = $player->update(["player_token" => $token]);
            }
            return Res::success(Response::HTTP_OK, "Player Token", $token);
        } catch (ValidationException $e) {
            return Res::fail($e->getResponse(), $e->getMessage(), $e->errors());
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    public function check(Request $request)
    {
        try {
            $player = Player::where("player_token", $request->pt)->get();
            if (count($player) != 1) {
                throw new \Exception("Sorun oluştu. Hata Kodu: AUTH_PLAYER", Response::HTTP_FORBIDDEN);
            }
            return Res::success(Response::HTTP_OK, "Authorized", true);
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    public function setPromotion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "player_id" => "required|exists:players,player_identifier",
                "event_id" => "required|exists:events,event_id",
                "count" => "required|numeric"
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator, Response::HTTP_BAD_REQUEST, $validator->errors());
            }

            $event = Event::find($request->event_id);
            if ($event->network_id != $request->user()->network_id && $event->site_id != $request->user()->site_id) {
                throw new \Exception("Not authorizasded", Response::HTTP_FORBIDDEN);
            }

            $player = Player::where("player_identifier", $request->player_id)
                ->where("site_id", $request->user()->site_id)
                ->where("network_id", $request->user()->network_id)
                ->first();
            if (!$player) {
                throw new \Exception("Couldn't find the player", Response::HTTP_BAD_REQUEST);
            }
            for ($i = 0; $i < $request->count; $i++) {
                $promotion[] = Promotion::create([
                    "player_id" => $player->player_id,
                    "event_id" => $event->event_id,
                    "created_by" => $request->user()->id
                ]);
            }
            return Res::success(Response::HTTP_CREATED, "Bonus bilet tanımlandı", $promotion);
        } catch (ValidationException $e) {
            return Res::fail($e->getResponse(), $e->getMessage(), $e->errors());
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    public function getPromotions(Request $request)
    {
        try {
            $events = Promotion::where("player_id", $request->player->player_id)->get();
            $events = Event::whereIn("event_id",$events)->with("source")->get();
            foreach ($events as $k => $v) {
                $events[$k]->promotions = Promotion::where("player_id", $request->player->player_id)
                    ->where("event_id", $v->event_id)
                    ->get();
            }
            return Res::success(Response::HTTP_OK, "Promotions", $events);
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    public function createBonus(Request $request)
    {
        return "not ready yet";
    }
}
