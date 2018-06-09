<?php

namespace App\Http\Controllers;

use App\Event;
use App\EventRule;
use App\Libraries\Leveling;
use App\Libraries\Res;
use App\Libraries\TReq;
use App\Logs;
use App\Network;
use App\Site;
use App\Ticket;
use App\Transaction;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EventsController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "event_name" => "required",
                "event_description" => "required",
                "event_ticket_price" => "required|numeric",
                "event_type" => "required|exists:event_types,event_type_id",
                "event_source" => "required|exists:event_sources,event_source_id",
                "event_start_date" => "required|date",
                "event_end_date" => "required|date",
                "currency_id" => "required|exists:currencies,currency_id",
                "network_id" => "required|exists:networks,network_id",
                "site_id" => "required|exists:sites,site_id",
                "total_digits" => "required|numeric",
                "last_num_of_digits" => "required_if:event_type,2"
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator, Response::HTTP_BAD_REQUEST, $validator->errors());
            }
            if ($request->user()->roles->role_level !== 0) {
                if ($request->network_id != $request->user()->network_id) {
                    throw new \Exception("You are not allowed to create an event for other network",
                        Response::HTTP_BAD_REQUEST);
                }
            }
            $site = Site::where("site_id", $request->site_id)
                ->where("network_id", $request->network_id)->get();
            if (count($site) == 0) {
                throw new \Exception("Couldn't find the site", Response::HTTP_BAD_REQUEST);
            }
            $data = $request->only([
                "event_name",
                "event_description",
                "event_ticket_price",
                "currency_id",
                "event_type",
                "event_source",
                "event_start_date",
                "event_end_date",
                "network_id",
                "site_id",
                "total_digits"
            ]);
            $data["event_start_date"] = Carbon::createFromFormat("d-m-Y H:i:s", $data["event_start_date"])
                ->format("Y-m-d H:i:s");
            $data["event_end_date"] = Carbon::createFromFormat("d-m-Y H:i:s", $data["event_end_date"])
                ->format("Y-m-d H:i:s");
            $data["created_by"] = $request->user()->id;
            $event = Event::create($data);
            if ($request->event_type == 2) {
                $last_number_of_digits = $request->last_num_of_digits;
                EventRule::create([
                    "digit_number" => $last_number_of_digits,
                    "event_id" => $event->event_id
                ]);
            }
            return Res::success(Response::HTTP_CREATED, "Event created", $event);
        } catch (ValidationException $e) {
            return Res::fail($e->getResponse(), $e->getMessage(), $e->errors());
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    public function list(Request $request)
    {
        try {
            $query = TReq::multiple($request, Event::class);
            $fields = [
                "company_id" =>
                    [
                        "field" => "events.network_id",
                        "value" => $request->user()->network_id
                    ],
                "branch_id" =>
                    [
                        "field" => "events.site_id",
                        "value" => $request->user()->site_id
                    ]
            ];
            $query2 = Leveling::make($request, $query["query"], $fields);
            $data = $query2->with(["type", "source", "prizes", "currency", "rule"])
                ->join("networks", "networks.network_id", "=", "events.network_id")
                ->join("sites", "sites.site_id", "=", "events.site_id")
                ->get();
            return Res::success(Response::HTTP_OK, "Event list", $data);
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    public function result(Request $request, $event)
    {
        try {
            $event = Event::find($event)->with("prizes")->first();
            foreach ($event->prizes as $index => $prize) {
                $numbers = substr($event->event_result, $prize->winning_number_count * -1);
//                $event->prizes[$index]->numbers = $numbers;
//                $event->prizes[$index]->tickets = Ticket::where("event_id", $event->event_id)
//                    ->where("ticket_number", "LIKE", '%'.$numbers)
//                    ->where("ticket_status",0)
//                    ->get();
                Ticket::where("event_id", $event->event_id)
                    ->where("ticket_number", "LIKE", '%' . $numbers)
                    ->where("ticket_status", 0)
                    ->update([
                        "ticket_status" => 1,
                        "digits_won" => $prize->winning_number_count,
                        "prize_id" => $prize->prize_id
                    ]);
//                $client = new Client();

//                $event->prizes[$index]->tickets = Ticket::where("event_id", $event->event_id)
//                    ->where("ticket_number", "LIKE", '%'.$numbers)
//                    ->where("ticket_status",1)->get();
            }
            Ticket::where("event_id", $event->event_id)
                ->where("ticket_status", 0)
                ->update(["ticket_status" => 2]);
            $tt = $this->sendToNetwork();
            return ["event"=> $event,"tickets" => $tt];
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function sendToNetwork()
    {
        try {
            $tickets = Ticket::select([
                "tickets.*",
                "players.network_id",
                "players.site_id",
                "players.player_identifier",
                "prizes.prize_amount",
                "event_rules.digit_number"
            ])
                ->join("players", "players.player_id", "=", "tickets.player_id")
                ->leftJoin("prizes", "prizes.prize_id", "=", "tickets.prize_id")
                ->leftJoin("event_rules", "event_rules.event_id", "=", "tickets.event_id")
                ->where("ticket_status", "!=", 0)
                ->whereNotBetween("send_status", [200, 400])
                ->get();
            foreach ($tickets as $ticket) {
                $settings = Network::find($ticket->network_id)->settings()->get();

                $api = [];
                foreach ($settings as $s) {
                    if ($s->setting_name == "withdraw_url") {
                        $api["withdraw_url"] = $s["setting_value"];
                    } elseif ($s->setting_name == "api_key") {
                        $api["api_key"] = $s["setting_value"];
                    }
                }
                $transaction = Transaction::create([
                    "player_id" => $ticket->player_id,
                    "amount" => $ticket->prize_amount,
                    "ticket_number" => $ticket->ticket_number,
                    "site_id" => $ticket->site_id,
                    "network_id" => $ticket->network_id
                ]);
                $counter = 0;
                $client = new Client();
                $balance = false;
                while ($counter < 3) {
                    $params = ["form_params" => [
                        "api_key" => $api["api_key"],
                        "user_id" => $ticket->player_identifier,
                        "amount" => $ticket->prize_amount/ pow(10,$ticket->digit_number-$ticket->digits_won),
                        "transaction_id" => $transaction->transaction_id // Bu transaction id olarak değişecek
                    ]];
                    $res = $client->post($api["withdraw_url"], $params);
                    Logs::create([
                        "requested_url" => $api["withdraw_url"],
                        "params" => json_encode($params),
                        "network_id" => $ticket->network_id,
                        "site_id" => $ticket->site_id,
                        "response_body" => $res->getBody(),
                        "response_headers" => json_encode($res->getHeaders())
                    ]);
                    if ($res->getStatusCode() >= 200 && $res->getStatusCode() < 400) {
                        Ticket::where("ticket_id",$ticket->ticket_id)->update(["send_status"=>$res->getStatusCode()]);
                        $counter = 4;
                    } else {
                        Ticket::where("ticket_id",$ticket->ticket_id)->update(["send_status"=>$res->getStatusCode()]);
                        sleep(3);
                        $counter++;
                    }
                }
                if ($counter == 3) {
                    $transaction->delete();
                }
            }
            $tickets = Ticket::select([
                "tickets.*",
                "players.network_id",
                "players.site_id",
                "players.player_identifier",
                "prizes.prize_amount"
            ])
                ->join("players", "players.player_id", "=", "tickets.player_id")
                ->leftJoin("prizes", "prizes.prize_id", "=", "tickets.prize_id")
                ->where("ticket_status", "!=", 0)
                ->whereNotBetween("send_status", [200, 400])
                ->get();
            return $tickets;
        } catch (\Exception $e) {
            return $e;
        }
    }
}
