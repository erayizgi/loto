<?php

namespace App\Http\Controllers;

use App\Libraries\Leveling;
use App\Libraries\Res;
use App\Libraries\TReq;
use App\Network;
use App\User;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class NetworksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Res
     */
    public function index(Request $request)
    {
        try {
            $query = TReq::multiple($request, Network::class);
            $fields = [
                "company_id" =>
                    [
                        "field" => "network_id",
                        "value" => $request->user()->network_id
                    ]
            ];
            $query2 = Leveling::make($request, $query["query"], $fields);
            $data = $query2->get();
            return Res::success(Response::HTTP_OK, "success", $data);
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return Res
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(),[
                "name" => "required,unique:networks,name",
                "link" => "required,unique:networks,link",
                "ip" => "required,unique:networks,ip"
            ]);
            if($validator->fails()){
                throw new ValidationException($validator,Response::HTTP_BAD_REQUEST,$validator->errors());
            }
            $network = Network::create($request->only(["name","link","ip"]));
            return Res::success(Response::HTTP_CREATED,"Network oluşturuldu",$network);
        } catch (ValidationException $e) {
            return Res::fail($e->getResponse(),$e->getMessage(),$e->errors());
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param  int $id
     * @return Res
     */
    public function show(Request $request,$id)
    {
        try{
            $query = TReq::multiple($request, Network::class);

            $fields = [
                "company_id" =>
                    [
                        "field" => "network_id",
                        "value" => $request->user()->network_id
                    ]
            ];
            $query2 = Leveling::make($request, $query["query"], $fields);
            $data = $query2->where("network_id",$id)->get();
            if($data){
                throw new \Exception("Couldn't find the network",Response::HTTP_NOT_FOUND);
            }
            return Res::success(Response::HTTP_OK,"Network",$data);
        }catch (\Exception $e){
            return Res::fail($e->getCode(),$e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return Res
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Res
     */
    public function destroy($id)
    {
        //
    }
}
