<?php
/**
 * Created by PhpStorm.
 * User: erayizgi
 * Date: 22.05.2018
 * Time: 22:49
 */

namespace App\Libraries;
use Illuminate\Http\Request;

class Res
{

    public static function success($code = 200, $message = 'Request is successfull!', $data = null)
    {
        $response = [
            'status' => true,
            'code' => $code,
            'message' => $message,
            'result' => $data
        ];

        return response()->json($response, $code);
    }

    public static function fail($code = 404, $message = 'Not found!', $data = null)
    {
        $code = ($code < 200 || $code > 500) ? 500 : $code;
        $response = [
            'status' => false,
            'code' => $code,
            'message' => $message,
            'result' => $data
        ];
        return response()->json($response, $code);
    }
}
