<?php

namespace App\Http\Controllers;

use App\Libraries\Leveling;
use App\Libraries\Res;
use App\Libraries\TReq;
use App\Site;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SitesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = TReq::multiple($request, Site::class);
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
            $data = $query2->with("network")->get();
            return Res::success(Response::HTTP_OK, "success", $data);
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $query = TReq::multiple($request, Site::class);
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
            $data = $query2->where("site_id", $id)->with("network")->get();
            if (count($data) == 0) {
                throw new \Exception(
                    "Site bulunamadı ya da bu siteyi görüntülemek için yeterli yetkiye sahip değilsiniz",
                    Response::HTTP_BAD_REQUEST
                );
            }
            return Res::success(Response::HTTP_OK, "success", $data);
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(),[
                "network_id"=>"required|exists:networks,network_id",
                "site_name" => "required"
            ]);
            if($validator->fails()){
                throw new ValidationException($validator,Response::HTTP_BAD_REQUEST,$validator->errors());
            }

            $site = Site::create($request->only(["network_id","site_name"]));
            return Res::success(Response::HTTP_CREATED,"Site created",$site);
        } catch (ValidationException $e) {
            return Res::fail($e->getResponse(),$e->getMessage(),$e->errors());
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }
}
