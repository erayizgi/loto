<?php

namespace App\Http\Controllers;

use App\Event;
use App\Libraries\Res;
use App\Logs;
use App\Network;
use App\Player;
use App\Promotion;
use App\Ticket;
use App\Transaction;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TicketsController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "pt" => "required",
                "event_id" => "required|exists:events,event_id",
                "ticket_number" => "required|numeric"
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator, Response::HTTP_BAD_REQUEST, $validator->errors());
            }
            $player = Player::where("player_token", $request->pt)->first();
            if (!$player) {
                throw new \Exception("Oyuncu bulunamadı", Response::HTTP_BAD_REQUEST);
            }
            $event = Event::where("event_id", $request->event_id)
                ->where("event_start_date", "<=", Carbon::now()->format("Y-m-d H:i:s"))
                ->where("event_end_date", ">=", Carbon::now()->format("Y-m-d H:i:S"))
                ->with("currency")
                ->first();
            if (!$event) {
                throw new \Exception("Çekiliş bulunamadı ya da katılım süresi geçti.", Response::HTTP_BAD_REQUEST);
            }
            if ($event->network_id != $player->network_id || $event->site_id != $player->site_id) {
                throw new \Exception("Bu çekilişe katılamazsınız", Response::HTTP_BAD_REQUEST);
            }
            $ticket = Ticket::where("ticket_number", $request->ticket_number)
                ->where("event_id", $event->event_id)
                ->get();
            if (count($ticket) > 0) {
                throw new \Exception("Bu bilet daha önce satın alınmış", Response::HTTP_BAD_REQUEST);
            }
            $transaction = Transaction::create([
                "player_id" => $player->player_id,
                "amount" => $event->event_ticket_price,
                "ticket_number" => $request->ticket_number,
                "site_id" => $event->site_id,
                "network_id" => $event->network_id
            ]);
            $promotion = Promotion::where("event_id",$event->event_id)
                ->where("player_id",$player->player_id)
                ->whereNull("used_date")
                ->first();
            if(!$promotion){
                $settings = Network::find($event->network_id)->settings()->get();

                $api = [];
                foreach ($settings as $s) {
                    if ($s->setting_name == "withdraw_url") {
                        $api["withdraw_url"] = $s["setting_value"];
                    } elseif ($s->setting_name == "api_key") {
                        $api["api_key"] = $s["setting_value"];
                    }
                }
                $counter = 0;
                $client = new Client();
                $balance = false;
                while ($counter < 3) {
                    $params = ["form_params" => [
                        "api_key" => $api["api_key"],
                        "user_id" => $player->player_identifier,
                        "amount" => $event->event_ticket_price * -1,
                        "transaction_id" => $transaction->transaction_id
                    ]];
                    $res = $client->post($api["withdraw_url"], $params);
                    Logs::create([
                        "requested_url" => $api["withdraw_url"],
                        "params" => json_encode($params),
                        "network_id" => $event->network_id,
                        "site_id" => $event->site_id,
                        "response_body" => $res->getBody(),
                        "response_headers" => json_encode($res->getHeaders())
                    ]);
                    if ($res->getStatusCode() >= 200 && $res->getStatusCode() < 400) {
                        $counter = 4;
                        $balance = true;
                    } else {
                        sleep(3);
                        $counter++;
                    }
                }
                if ($counter == 3) {
                    $transaction->delete();
                }
                $ticket = Ticket::where("ticket_number", $request->ticket_number)
                    ->where("event_id", $event->event_id)
                    ->get();
                if (count($ticket) > 0) {
                    throw new \Exception("Bu bilet daha önce satın alınmış", Response::HTTP_BAD_REQUEST);
                }
                $prom =false;
                /**
                 * 3 defa 3 er saniye aralıkla network sunucusuna istek yapılacak doğru cevap gelene kadar beklenecek.
                 * Doğru cevap gelmediği takdirde başarısız sonuç döndürülecek.
                 */
            }else{
                $balance = true;
                $promotion->update(["used_date"=>Carbon::now()]);
                $prom = true;
            }
            if ($balance) {
                $ticket = Ticket::create([
                    "player_id" => $player->player_id,
                    "event_id" => $event->event_id,
                    "ticket_number" => $request->ticket_number,
                    "is_promotion" => $prom
                ]);
                $message = ($prom)? $request->ticket_number." Numaralı bonus bileti aldınız":
                    $request->ticket_number. "Numaralı bileti ".$event->event_ticket_price." ".$event->currency->currency_abbr.
                    "karşılığında satın alındı";
                return Res::success(
                    Response::HTTP_CREATED,
                    $message,
                    $ticket
                );
            } else {
                $transaction->delete();
                throw new Exception("Satın alma işlemi başarısız. Bakiyeniz yetersiz", Response::HTTP_FORBIDDEN);
            }
        } catch (ValidationException $e) {
            return Res::fail($e->getResponse(), $e->getMessage(), $e->errors());
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    public function list(Request $request)
    {
        try {
            $events = Ticket::where("player_id", $request->player->player_id)
                ->select("event_id")
                ->groupBy("event_id")
                ->orderBy("event_id")
                ->get()->pluck("event_id");
            $events = Event::whereIn("event_id",$events)
                ->where("event_end_date",">=",Carbon::now())
                ->with("source")
                ->get();
            foreach ($events as $k => $v) {
                $events[$k]->tickets = Ticket::where("player_id",$request->player->player_id)
                    ->where("event_id",$v->event_id)->orderBy("ticket_id","DESC")->get();
            }
            return Res::success(Response::HTTP_OK,"Tickets",$events);

        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    public function checkSoldBulk(Request $request)
    {
        try {
            $data = $request->tickets;
            foreach ($data as $k => $v) {
                $find = Ticket::where("ticket_number", $v["ticket_number"])
                    ->where("event_id", $request->event)
                    ->select("ticket_id")
                    ->get();
                if (count($find) > 0) {
                    $data[$k]["sold"] = true;
                } else {
                    $data[$k]["sold"] = false;
                }
            }
            return Res::success(Response::HTTP_OK, "Tickets", $data);

        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }
}
