<?php

namespace App\Http\Controllers;

use App\EventType;
use App\Libraries\Res;
use App\Libraries\TReq;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EventTypesController extends Controller
{
    public function list(Request $request)
    {
        try {
            $query = TReq::multiple($request, EventType::class);
            $data = $query->get();
            if (count($data) === 0) {
                throw new \Exception("Çekiliş tipi bulunamadı", Response::HTTP_NOT_FOUND);
            }
            return Res::success(Response::HTTP_OK, "Event Types", $data);
        } catch (\Exception $e) {
            return Res::fail($e->getCode(), $e->getMessage());
        }
    }

}
